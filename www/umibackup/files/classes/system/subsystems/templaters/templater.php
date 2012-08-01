<?php

	class templater extends singleton implements iTemplater {
		private $templater;
		protected
			$parsed = true,
			$xml_modes = array("xmlMode", "jsonMode", 'showStreamsCalls');
		public
			$cachePermitted = false,
			$LANGS = array(),
			$cacheEnabled = 0,
			$cacheMacroses = array(),
			$processingCache = array(), // For macrosess in process (infinite recursion preventing)
			$defaultMacroses = Array(
				Array("%content%", "macros_content"),
				Array("%menu%", "macros_menu"),
				Array("%header%", "macros_header"),
				Array("%pid%", "macros_returnPid"),
				Array("%parent_id%", "macros_returnParentId"),
				Array("%pre_lang%", "macros_returnPreLang"),
				Array("%curr_time%", "macros_curr_time"),
				Array("%domain%", "macros_returnDomain"),
				Array("%domain_floated%", "macros_returnDomainFloated"),
				Array("%system_build%", "macros_systemBuild"),
				Array("%title%", "macros_title"),
				Array("%keywords%", "macros_keywords"),
				Array("%describtion%", "macros_describtion"),
				Array("%description%", "macros_describtion"),
				Array("%adm_menu%", "macros_adm_menu"),
				Array("%adm_navibar%", "macros_adm_navibar"),
				Array("%skin_path%", "macros_skin_path"),
				Array("%ico_ext%", "macros_ico_ext"),
				Array("%current_user_id%", "macros_current_user_id"),
				Array("%current_version_line%", "macros_current_version_line"),
				Array("%context_help%", "macros_help"),
				Array("%current_alt_name%", "macros_current_alt_name")
			);

		protected function __construct() {
			global $includes;
			$params = current(func_get_args());
			if (is_array($params)) {
				if ($params['class_name'] == 'xslAdminTemplater') {
					if (strpos($params['file_path'], 'main_login.xsl')) {
						$this->parsed = false;
					}
					$includes['xslTemplater'] = array(
						SYS_KERNEL_PATH . 'subsystems/templaters/' . $params['type'] . '/xslTemplater.php'
					);
				}
				elseif (!isset($params['force'])) {
					foreach ($this->xml_modes as $mode) {
						if (is_null(getRequest($mode)) == false) {
							$params['class_name'] = 'xslTemplater';
							$params['type'] = 'xslt';
							break;
						}
					}
				}
				$includes[$params['class_name']] = array(
					SYS_KERNEL_PATH . 'subsystems/templaters/' . $params['type'] . '/' . $params['class_name'] . '.php'
				);
				$templater = parent::getInstance($params['class_name']);
				$templater->setFolderPath($params['dir_path']);
				$templater->setFilePath($params['file_path']);
				$templater->setParsed($this->parsed);
				$this->templater = $templater;
			}
		}

		public static function getInstance() {
			return parent::getInstance(__CLASS__);
			//return cmsController::getInstance()->getCurrentTemplater();
		}

		public function init() {}

		final public function get() {
			return $this->templater;
		}

		public function getParsed() {
			return $this->parsed;
		}

		public function setParsed($parsed) {
			$this->parsed = $parsed;
		}

		final public function setFilePath($file_path) {
			$this->file_path = $file_path;
		}

		final public function setFolderPath($folder_path) {
			$this->folder_path = $folder_path;
		}

		public function getFilePath() {
			return $this->file_path;
		}

		public function getFolderPath() {
			return $this->folder_path;
		}

		public function parseContent($arr, $templater) {}

		public function loadTemplates($filepath, $c, $args) {}

		public function parseResult() {}

		public function __destruct() {}

		public function loadLangs() {
			$try_path = CURRENT_WORKING_DIR . "/classes/modules/lang." . cmsController::getInstance()->getLang()->getPrefix() . ".php";
			if(!file_exists($try_path)) {
				$try_path = CURRENT_WORKING_DIR . "/classes/modules/lang.php";
			}

			include_once $try_path;

			if(isset($LANG_EXPORT)) {
				$cmsControllerInstance = cmsController::getInstance();
				$cmsControllerInstance->langs = array_merge($cmsControllerInstance->langs, $LANG_EXPORT);
				unset($LANG_EXPORT);
			}
			return true;
		}

		public function putLangs($input) {
			return self::putLangsStatic($input);
		}

		public static function putLangsStatic($input) {
			$res = $input;

			if(($p = strpos($res, "%")) === false) return $res;

			$langs = cmsController::getInstance()->langs;

			foreach($langs as $cv => $cvv) {
				if(is_array($cvv)) continue;

				$m = "%" . $cv . "%";

				if(($mp = strpos($res, $m, $p)) !== false) {
					$res = str_replace($m, $cvv, $res, $mp);
				}
			}

			return $res;
		}

		public $max_parse_level = 4;

		public function parseInput($input, $level = 1) {
			$res = $input;
			if (is_array($res)) return $res;

			if ($level > $this->max_parse_level) return $res;

			$pid = cmsController::getInstance()->getCurrentElementId();
			$input = str_replace("%pid%", $pid, $input);

			if (strrpos($res, "%") === false) return $res;

			$res = $this->findMacrosesAndReplace($input);

			$res = $this->putLangs($res);

			if ($pid) $res = system_parse_short_calls($res, $pid);

			$res = $this->parseInput($res, $level+1);

			$res = $this->cleanUpResult($res);

			return $res;
		}

		private function findMacrosesAndReplace($input) {
			$input = str_replace("%%", "%\r\n%", $input);

			if(preg_match_all("/%([A-z_]{3,})%/m", $input, $temp)) {
				$temp = $temp[0];
				$sz = sizeof($temp);
				for($i = 0; $i < $sz; $i++) {
					$r = $this->parseMacros($temp[$i]);
				}
			}

			$res = preg_replace("/<!--.*?-->/mu", "", $input);

			if(preg_match_all("/%([A-zА-Яа-я0-9]+\s+[A-zА-Яа-я0-9_]+\([A-zА-Яа-я \/\._\-\(\)0-9%:<>,!@\|'&=;\?\+#]*\))%/mu", $res, $temp)) {
				$temp = $temp[0];
				$sz = sizeof($temp);
				for($i = 0; $i < $sz; $i++) {
					$r = $this->parseMacros($temp[$i]);
				}
			}

			$cache = $this->cacheMacroses;
			$cache = array_reverse($cache);
			foreach($cache as $ms => $mr) {
				if(($p = strpos($input, $ms)) !== false) {

					$input = str_replace($ms, $mr, $input);
				}
			}

			return $input;
		}

		public function parseMacros($macrosStr) {
			$macrosArr = Array();

			if (strrpos($macrosStr, "%") === false) return $macrosArr;

			// Set up processing cache
			if (isset($this->processingCache[$macrosStr])) return $macrosStr;
			$this->processingCache[$macrosStr] = true;
			//--------------------------------------

			$str = trim($macrosStr, '%');
			$macrosStr = '%' . $this->findMacrosesAndReplace($str) . '%';

			if (preg_match("/%([A-z0-9]+)\s+([A-z0-9]+)\((.*)\)%/m", $macrosStr, $pregArr)) {
				$macrosArr['str']    = $pregArr[0];
				$macrosArr['module'] = $pregArr[1];
				$macrosArr['method'] = $pregArr[2];
				$macrosArr['args']   = $pregArr[3];

				if (array_key_exists($macrosArr['str'], $this->cacheMacroses)) {
					unset($this->processingCache[$macrosStr]);
					return $this->cacheMacroses[$macrosArr['str']];
				}

				$params = explode(",", $macrosArr['args']);

				$sz = sizeof($params);
				for($i = 0; $i < $sz; $i++) {
					$cparam = $params[$i];

					if(strpos($cparam, "%") !== false) {
						$cparam = $this->parseInput($cparam);
					}
					$params[$i] = trim($cparam, "'\" ");
				}
				$macrosArr['args'] = $params;

				$res = $macrosArr['result'] = $this->executeMacros($macrosArr);

				$this->cacheMacroses[$macrosArr['str']] = $macrosArr['result'];
				unset($this->processingCache[$macrosStr]);

				return $res;
			}
			else {
				$defMs = $this->defaultMacroses;

				$sz = sizeof($defMs);
				for($i = 0; $i < $sz; $i++) {
					if (stripos($macrosStr, $defMs[$i][0]) !== false) {
						if (array_key_exists($defMs[$i][0], $this->cacheMacroses)) {
							unset($this->processingCache[$macrosStr]);
							return $this->cacheMacroses[$defMs[$i][0]];
						}

						if (!isset($defMs[$i][2])) {
							$defMs[$i][2] = NULL;
						}

						$res = $this->executeMacros(
										Array(
											"module" => $defMs[$i][1],
											"method" => $defMs[$i][2],
											"args"   => Array()
											)
									);

						$res = $this->parseInput($res);
						$this->cacheMacroses[$defMs[$i][0]] = $res;
						unset($this->processingCache[$macrosStr]);
						return $res;
					}
				}
				$this->cacheMacroses[$macrosStr] = $macrosStr;
				unset($this->processingCache[$macrosStr]);

				return $macrosStr;
			}
		}

		public function executeMacros($macrosArr) {
			$controller = cmsController::getInstance();
			$config = mainConfiguration::getInstance();
			$debug  = $config->get('debug', 'enabled');
			$module = $macrosArr['module'];
			$method = $macrosArr['method'];

			if($module == "current_module")
				$module = $controller->getCurrentModule();
			$res = "";

			if(!$method) {
				$cArgs = $macrosArr['args'];
				$res = call_user_func_array($macrosArr['module'], $cArgs);
			}

			if($module == "core" || $module == "system" || $module == "custom") {
				$pk = &system_buildin_load($module);

				if($pk) {
					try {
						$res = $pk->cms_callMethod($method, $macrosArr['args']);
					}
					catch (Exception $e) {
						return ($debug) ? $e->getMessage() : false;
					}
				}
			}

			if($module != "core" && $module != "system") {
				if(system_is_allowed($module, $method)) {
					if($module_inst = $controller->getModule($module)) {
						try {
							$res = $module_inst->cms_callMethod($method, $macrosArr['args']);
						}
						catch (Exception $e) {
							return ($debug) ? $e->getMessage() : false;
						}
					}
				}
			}

			if (is_null($this->templater)) {
				if (!$config->get('system', 'use-old-templater')) {
					$res = $controller->getCurrentTemplater()->executeMacrosTemplate($res, $module, $method);
				}
				else $res = $this->executeMacrosTemplate($res, $module, $method);
			}
			else {
				if (!$this->templater instanceof tplTemplater && !$config->get('system', 'use-old-templater')) {
					$res = $this->templater->executeMacrosTemplate($res, $module, $method);
				}
				else $res = $this->executeMacrosTemplate($res, $module, $method);
			}

			if(strpos($res, "%") !== false) {
				$res = $this->parseInput($res);
			}

			return $res;
		}

		protected function executeMacrosTemplate($res) {
			if (is_array($res)) {
				$tmp = "";
				foreach($res as $s) {
					if (!is_array($s)) {
						$tmp .= $s;
					}
				}
				$res = $tmp;
			}
			return $res;
		}

		public function cleanUpResult($input) {
			$showBrokenMacro = mainConfiguration::getInstance()->get("kernel", "show-broken-macro");
			if(!$showBrokenMacro) {
				$input = preg_replace("/%(?!cut%)([A-z_]{3,})%/m", "", $input);
				$input = preg_replace("/%([A-zА-Яа-я0-9]+\s+[A-zА-Яа-я0-9_]+\([A-zА-Яа-я \/\._\-\(\)0-9%:<>,!@\|'&=;\?\+#]*\))%/mu", "", $input);
			}
			return $input;
		}

		final public static function getSomething($version_line = "pro", $forceHost = null) {
			$default_domain = domainsCollection::getInstance()->getDefaultDomain();
			$serverAddr = getServer('SERVER_ADDR');

			$cs2 = ($serverAddr) ? md5($serverAddr) : md5(str_replace("\\", "", getServer('DOCUMENT_ROOT')));
			$cs3 = '';

			$host = is_null($forceHost) ? $default_domain->getHost() : $forceHost;

			switch($version_line) {
				case "pro":
					$cs3 = md5(md5(md5(md5(md5(md5(md5(md5(md5(md5($host))))))))));
					break;

				case "shop":
					$cs3 =  md5(md5($host . "shop"));
					break;

				case "lite":
					$cs3 = md5(md5(md5(md5(md5($host)))));
					break;

				case "start":
					$cs3 = md5(md5(md5($host)));
					break;

				case "trial": {
					$cs3 = md5(md5(md5(md5(md5(md5($host))))));
				}
			}

			$licenseKeyCode = strtoupper(substr($cs2, 0, 11) . "-" . substr($cs3, 0, 11));
			return $licenseKeyCode;
		}

		public static $blocks = Array();

		public static function pushEditable($module, $method, $id) {
			if($module === false && $method === false) {

				if($element = umiHierarchy::getInstance()->getElement($id)) {
					$elementTypeId = $element->getTypeId();

					if($elementType = umiObjectTypesCollection::getInstance()->getType($elementTypeId)) {
						$elementHierarchyTypeId = $elementType->getHierarchyTypeId();

						if($elementHierarchyType = umiHierarchyTypesCollection::getInstance()->getType($elementHierarchyTypeId)) {
							$module = $elementHierarchyType->getName();
							$method = $elementHierarchyType->getExt();
						} else {
							return false;
						}
					}
				}
			}

			templater::$blocks[] = array($module, $method, $id);
		}

		public static function prepareQuickEdit() {
			$toFlush = templater::$blocks;

			if(sizeof($toFlush) == 0) return;

			$key = md5("http://" . getServer('HTTP_HOST') . getServer('REQUEST_URI'));
			$_SESSION[$key] = $toFlush;
		}

	};
?>