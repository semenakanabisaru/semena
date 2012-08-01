<?php
	class cmsController extends singleton implements iSingleton, iCmsController {
		protected
				$modules = array(),
				$current_module = false,
				$current_method = false,
				$current_mode = false,
				$current_element_id = false,
				$current_lang = false,
				$current_domain = false,
				$current_templater = false,
				$default_templater = false,
				$calculated_referer_uri = false,
				$modulesPath,
				$url_prefix = '';

		public
				$parsedContent = false,
				$currentTitle = false,
				$currentHeader = false,
				$currentMetaKeywords = false,
				$currentMetaDescription = false,

				$langs = array(),
				$langs_export = array(),
				$pre_lang = "",
				$errorUrl, $headerLabel = false;

		public		$isContentMode = false;

		public static $IGNORE_MICROCACHE = false;

		protected function __construct() {
			$config = mainConfiguration::getInstance();
			$this->modulesPath = $config->includeParam('system.modules');
			$this->init();
		}

		/**
		* @desc
		* @return cmsController
		*/
		public static function getInstance() {
			return parent::getInstance(__CLASS__);
		}


		private function loadModule($module_name) {
			$xpath = "//modules/" . $module_name;

			if(!defined("CURRENT_VERSION_LINE")) {
				define("CURRENT_VERSION_LINE", "");
			}

			if(regedit::getInstance()->getVal($xpath) == $module_name) {
				$module_path = $this->modulesPath . $module_name . "/class.php";
				if(file_exists($module_path)) {
					require_once $module_path;

					if(class_exists($module_name)) {
						$new_module = new $module_name();
						$new_module->pre_lang = $this->pre_lang;
						$new_module->pid = $this->getCurrentElementId();
						$this->modules[$module_name] = $new_module;

						return $new_module;
					}
				} else {
					return false;
				}
			} else {
				return false;
			}
		}


		public function loadBuildInModule($moduleName) {
			//TODO
		}

		public function getModule($module_name) {
			if(!$module_name) return false;

			if(array_key_exists($module_name, $this->modules)) {
				return $this->modules[$module_name];
			} else {
				return $this->loadModule($module_name);
			}
		}

		public function installModule($installPath) {
			if(!file_exists($installPath)) {
				throw new publicAdminException(getLabel("label-errors-13052"), 13052);
			}
			require_once $installPath;

			preg_match("|\/modules\/(\S+)\/|i", $installPath, $matches);
			$name_by_path = $matches[1];

			if ($name_by_path!=$INFO['name']) {
				throw new publicAdminException(getLabel("label-errors-13053"), 13053);
			}

			// Проверяем, что модуль разрешен для данной системы
			$this->checkModuleByName($name_by_path);

			$this->checkModuleComponents($COMPONENTS);

			def_module::install($INFO);
		}

		/** Проверка наличия всех компонентов модуля
		*
		* @param mixed $module_name - имя модуля
		*/
		private function checkModuleComponents($components) {

			if (!is_array($components)) return false;

			$files = array();
			foreach ($components as $component) {
				$file = preg_replace("/.\/(.+)/", CURRENT_WORKING_DIR . '/' . "$1", $component);
				if (!file_exists($file) || !is_readable($file)) $files[] = $file;
			}

			if(count($files)) {
				$error = getLabel("label-errors-13058") . "\n";
				foreach($files as $file) {
					$error .= getLabel('error-file-does-not-exist', null, $file) . "\n";
				}

				throw new coreException($error);
			}
		}

		/** Проверяет, что модуль доступен для данной лицензии
		*
		*
		* @param mixed $module_name - имя модуля
		*/
		private function checkModuleByName($module_name) {
			if (!defined("UPDATE_SERVER")) define("UPDATE_SERVER", base64_decode('aHR0cDovL3Vkb2QudW1paG9zdC5ydS91cGRhdGVzZXJ2ZXIv'));

			$regedit = regedit::getInstance();
			$domainsCollection = domainsCollection::getInstance();

			$info = array();
			$info['type']='get-modules-list';
			$info['revision'] = $regedit->getVal("//modules/autoupdate/system_build");
			$info['host'] = $domainsCollection->getDefaultDomain()->getHost();
			$info['ip'] = getServer('SERVER_ADDR');
			$info['key'] = $regedit->getVal("//settings/keycode");
			$url = UPDATE_SERVER . "?" . http_build_query($info, '', '&');

			$result = $this->get_file($url);

			if (!$result) {
				throw new publicAdminException(getLabel("label-errors-13054"), 13054);
			}

			$xml = new DOMDocument();
			if (!$xml->loadXML($result)) {
				throw new publicAdminException(getLabel("label-errors-13055"), 13055);
			}

			$xpath = new DOMXPath($xml);

			// Проверяем, возможно сервер возвратил ошибку.
			$errors = $xpath->query("error");

			if ($errors->length!=0) {
				$code = $errors->item(0)->getAttribute("code");
				throw new publicAdminException(getLabel("label-errors-".$code), $code);
			}

			$modules = $xpath->query("module");
			if ($modules->length==0) {
				throw new publicAdminException(getLabel("label-errors-13056"), 13056);
			}

			$module_name = strtolower($module_name);

			$modules = $xpath->query("module[@name='".$module_name."']");
			if ($modules->length!=0) {
				$module = $modules->item(0);
				if ($module->getAttribute("active")!="1") {
					throw new publicAdminException(getLabel("label-errors-13057"), 13057);
				}
			}
		}

		/**
		* Выполняет запрос к серверу обновлений
		*
		* @param mixed $url - сформированная строка запроса
		* @return string;
		*/
		private function get_file($url) {
			if (function_exists("curl_init")) {
				$ch = curl_init($url);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				return curl_exec($ch);
			}
			elseif ($fp=fsockopen("http://yandex.ru")) {
				fclose($fp);
				return file_get_contents($url);
			}
			else {
				throw new publicAdminException(getLabel("label-errors-13041"), 13041);
			}
		}


		public function getSkinPath() {
			//TODO
		}


		public function getCurrentModule() {
			return $this->current_module;
		}

		public function getCurrentMethod() {
			return $this->current_method;
		}

		public function getCurrentElementId() {
			return $this->current_element_id;
		}

		public function getLang() {
			return $this->current_lang;
		}

		public function getCurrentLang() {
			return $this->getLang();
		}

		public function getCurrentMode() {
			return $this->current_mode;
		}


		public function getCurrentDomain() {
			return $this->current_domain;
		}

		/**
			* Получить текущий шаблонизатор
			* @return templater экземпляр класса templater
		*/
		public function getCurrentTemplater() {
			return $this->current_templater;
		}

		/**
			* Получить основной шаблонизатор
			* @return templater экземпляр класса templater
		*/
		public function getDefaultTemplater() {
			if ($this->default_templater) {
				$templater = new templater(system_get_tpl());
				$this->default_templater = $templater->get();
			}
			return $this->default_templater;
		}

		private function init() {
			$this->detectMode();
			$this->detectDomain();
			$this->detectLang();
			$this->loadLangs();

			cacheFrontend::$currentlangId = $this->getCurrentLang()->getId();
			cacheFrontend::$currentDomainId = $this->getCurrentDomain()->getId();

			$LANG_EXPORT = array();
			$lang_file = CURRENT_WORKING_DIR . "/classes/modules/lang.php";
			if (file_exists($lang_file)) {
				require $lang_file;
			}
			$this->langs = array_merge($this->langs, $LANG_EXPORT);


			$ext_lang = CURRENT_WORKING_DIR . "/classes/modules/lang." . $this->getCurrentLang()->getPrefix() . ".php";
			if(file_exists($ext_lang)) {
				require $ext_lang;
				$this->langs = array_merge($this->langs, $LANG_EXPORT);
			}

			$this->errorUrl = getServer('HTTP_REFERER');
			$this->doSomething();
			$this->calculateRefererUri();
		}

		private function detectDomain() {
			$domains = domainsCollection::getInstance();
			$host = getServer('HTTP_HOST');
			if($domain_id = $domains->getDomainId($host)) {
				$domain = $domains->getDomain($domain_id);
			} else {
				$domain = $domains->getDefaultDomain();
				if (!$domain instanceof domain) throw new coreException("Default domain could not be found");
			}

			if(getServer('HTTP_HOST') != $domain->getHost()) {
				$config = mainConfiguration::getInstance();

				if($config->get('seo', 'primary-domain-redirect')) {
					$uri = 'http://' . $domain->getHost() . getServer('REQUEST_URI');

					$buffer = outputBuffer::current();
					$buffer->header('Location', $uri);
					$buffer->clear();
					$buffer->end();
				}
			}

			if(is_object($domain)) {
				$this->current_domain = $domain;
				return true;
			} else {
				$domain = $domains->getDefaultDomain();
				if($domain instanceof domain) {
					$this->current_domain = $domain;
					$domain->addMirrow($host);
					return false;
				} else {
					throw new coreException("Current domain could not be found");
				}
			}
		}

		private function detectLang() {
			$LangIDs = getRequest('lang_id');

			$lang_id = false;
			if($LangIDs != null) {
				if(is_array($LangIDs)) list($LangIDs) = $LangIDs;
				$lang_id = intval($LangIDs);
			} else if (!is_null(getRequest('links')) && is_array($rel = getRequest('rel'))) {
				if(sizeof($rel) && ($elementId = array_pop($rel))) {
					$element = umiHierarchy::getInstance()->getElement($elementId, true);
					if($element instanceof umiHierarchyElement) {
						$lang_id = $element->getLangId();
					}
				}
			} else {
				list($sub_path) = $this->getPathArray();
				$lang_id = langsCollection::getInstance()->getLangId($sub_path);
			}

			if (!langsCollection::getInstance()->getDefaultLang()) {
				throw new coreException('Cannot find default language');
			}

			if(($this->current_lang = langsCollection::getInstance()->getLang($lang_id)) === false ) {
				if($this->current_domain) {
					if($lang_id = $this->current_domain->getDefaultLangId()) {
						$this->current_lang = langsCollection::getInstance()->getLang($lang_id);
					} else {
						$this->current_lang = langsCollection::getInstance()->getDefaultLang();
					}
				} else {
					$this->current_lang = langsCollection::getInstance()->getDefaultLang();
				}
			}

			if($this->current_lang->getId() != $this->current_domain->getDefaultLangId()) {
				$this->pre_lang = "/" . $this->current_lang->getPrefix();
				$_REQUEST['pre_lang'] = $this->pre_lang;
			}
		}

		private function getPathArray() {
			$path = getRequest('path');
			$path = trim($path, "/");

			return explode("/", $path);
		}

		private function detectMode() {
			if (isset($_SERVER['argv']) && 1<=count($_SERVER['argv'])
				&& !(isset($_SERVER['QUERY_STRING']) && $_SERVER['argv'][0]==$_SERVER['QUERY_STRING'])) {
				$this->current_mode = "cli";
				cacheFrontend::$cacheMode = true;
				return;
			}

			$path_arr = $this->getPathArray();

			if(sizeof($path_arr) < 2) {
				$path_arr[1] = NULL;
			}

			list($sub_path1, $sub_path2) = $path_arr;

			if($sub_path1 == "admin" || $sub_path2 == "admin") {
				$this->current_mode = "admin";
				cacheFrontend::$adminMode = true;
			} else {
				$this->current_mode = "";
				cacheFrontend::$cacheMode = true;
				cacheFrontend::$adminMode = false;
			}
		}

		private function getSubPathType($sub_path) {
			$regedit = regedit::getInstance();

			if(!$this->current_module) {

				if($sub_path == "trash") {
					def_module::redirect($this->pre_lang . "/admin/data/trash/");
				}

				if($regedit->getVal("//modules/" . $sub_path)) {
					$this->setCurrentModule($sub_path);
					return "MODULE";
				}
			}

			if($this->current_module && !$this->current_method) {
				$this->setCurrentMethod($sub_path);
				return "METHOD";
			}

			if($this->current_module && $this->current_method) {
				return "PARAM";
			}

			return "UNKNOWN";
		}

		private function reset() {
			$this->current_module = $this->current_method = '';

			for($i=0;$i<10;$i++) {
				if(isset($_REQUEST['param'.$i])) {
					unset($_REQUEST['param'.$i]);
				}
				else break;
			}
		}

		public function analyzePath($reset = false) {
			$path = getRequest('path');
			$path = trim($path, "/");

			if (!is_null(getRequest('scheme'))) {
				if (preg_replace("/[^\w]/im", "", getRequest('scheme')) == 'upage') {
					preg_match_all("/[\d]+/", $path, $element_id);
					$this->current_element_id = $element_id[0][0];
				}
				return;
			}

			$regedit = regedit::getInstance();
			$hierarchy = umiHierarchy::getInstance();
			$config = mainConfiguration::getInstance();
			$buffer = outputBuffer::current();

			if($reset === true) {
				$this->reset();
			}

			if($reset === true) {
				$this->reset();
			}

			if ($config->get('seo', 'folder-redirect')) {
				def_module::requireSlashEnding();
			}

			if($config->get('seo', 'watch-redirects-history')) {
				redirects::getInstance()->init();
			}

			$path_arr = $this->getPathArray();

			$sz = sizeof($path_arr);
			$url_arr = Array();
			$p = 0;
			for($i = 0; $i < $sz; $i++) {
				$sub_path = $path_arr[$i];

				if($i <= 1) {
					if(($sub_path == $this->current_mode) || ($sub_path == $this->current_lang->getPrefix())) {
						continue;
					}
				}

				$url_arr[] = $sub_path;

				$sub_path_type = $this->getSubPathType($sub_path);

				if($sub_path_type == "PARAM") {
					$_REQUEST['param' . $p++] = $sub_path;
				}
			}


			if(!$this->current_module) {
				if($this->current_mode == "admin") {
					$module_name = $regedit->getVal("//settings/default_module_admin");
					$this->autoRedirectToMethod($module_name);
				} else {
					$module_name = $regedit->getVal("//settings/default_module");
				}
				$this->setCurrentModule($module_name);
			}

			if(!$this->current_method) {
				if($this->current_mode == "admin") {
					return $this->autoRedirectToMethod($this->current_module);
				} else {
					$method_name = $regedit->getVal("//modules/" . $this->current_module . "/default_method");
				}
				$this->setCurrentMethod($method_name);
			}


			if($this->getCurrentMode() == "admin") {
				return;
			}



			$element_id = false;
			$sz = sizeof($url_arr);
			$sub_path = "";
			for($i = 0; $i < $sz; $i++) {
				$sub_path .= "/" . $url_arr[$i];

				if(!($tmp = $hierarchy->getIdByPath($sub_path, false, $errors_count))) {
					$element_id = false;
					break;
				} else {
					$element_id = $tmp;
				}
			}

			if($element_id) {
				if($errors_count > 0 && !defined("DISABLE_AUTOCORRECTION_REDIRECT")) {
					$path = $hierarchy->getPathById($element_id);

					if($i == 0) {
						if($this->isModule($url_arr[0])) {
							$element_id = false;
							break;
						}
					}

					$buffer->status('301 Moved Permanently');
					$buffer->redirect($path);
				}

				$element = $hierarchy->getElement($element_id);
				if($element instanceof umiHierarchyElement) {
					if($element->getIsDefault()) {
						$path = $hierarchy->getPathById($element_id);

						$buffer->status('301 Moved Permanently');
						$buffer->redirect($path);
					}
				}
			}

			if(($path == "" || $path == $this->current_lang->getPrefix() ) && $this->current_mode != "admin") {
				if($element_id = $hierarchy->getDefaultElementId($this->getCurrentLang()->getId(), $this->getCurrentDomain()->getId())) {
					$this->current_element_id = $element_id;
				}
			}

			if($element = $hierarchy->getElement($element_id, true)) {
				$type = umiHierarchyTypesCollection::getInstance()->getType($element->getTypeId());

				if(!$type) return false;

				$this->current_module = $type->getName();

				if($ext = $type->getExt()) {
					$this->setCurrentMethod($ext);
				} else {
					$this->setCurrentMethod("content");	//Fixme: content "constructor". Maybe, fix in future?
				}

				$this->current_element_id = $element_id;
			}

			if($this->current_module == "content" && $this->current_method == "content" && !$element_id) {
				redirects::getInstance()->redirectIfRequired($path);
			}
		}



		public function setCurrentModule($module_name) {
			$this->current_module = $module_name;
		}


		public function setCurrentMethod($method_name) {
			$magic = array("__construct", "__destruct", "__call", "__callStatic", "__get", "__set", "__isset",
							"__unset", "__sleep", "__wakeup", "__toString", "__invoke", "__set_state", "__clone");
			if(in_array($method_name, $magic)) {
				$this->current_module = "content";
				$this->current_method = "page";
				return false;
			}

			$this->current_method = $method_name;
		}

		/**
			* Установить текущий шаблонизатор
			* @return templater экземпляр класса templater
		*/
		public function setCurrentTemplater($params) {
			if (is_array($params)) {
				$templater = new templater($params);
				$this->current_templater = $templater->get();
			}
			elseif ($params instanceof templater) {
				$this->current_templater = $params;
			}
			return $this->current_templater;
		}

		public function loadLangs() {
			$modules = regedit::getInstance()->getList("//modules");
			foreach($modules as $module) {
				$module_name = $module[0];

				$lang_path = CURRENT_WORKING_DIR . '/classes/modules/' . $module_name . '/';
				$lang_path .= "lang.php";

				if (file_exists($lang_path)) {
					require $lang_path;
				}

				if(isset($C_LANG)) {
					if(is_array($C_LANG)) {
						$this->langs[$module_name] = $C_LANG;
						unset($C_LANG);
					}
				}

				if(isset($LANG_EXPORT)) {
					if(is_array($LANG_EXPORT)) {
						$this->langs = array_merge($this->langs, $LANG_EXPORT);
						//$this->langs = $this->langs + $LANG_EXPORT;
						unset($LANG_EXPORT);
					}
				}

				$lang_path = CURRENT_WORKING_DIR . '/classes/modules/' . $module_name . '/';
				$lang_path .= "lang." . $this->getCurrentLang()->getPrefix() .".php";

				if(file_exists($lang_path)) {
					require $lang_path;

					if(isset($C_LANG) && is_array($C_LANG)) {
						$this->langs[$module_name] = $C_LANG;
						unset($C_LANG);
					}

					if(isset($LANG_EXPORT) && is_array($LANG_EXPORT)) {
						$this->langs = array_merge($this->langs, $LANG_EXPORT);
						//$this->langs = $this->langs + $LANG_EXPORT;
						unset($LANG_EXPORT);
					}
				}
			}
		}

		public function getModulesList() {
			$regedit = regedit::getInstance();
			$list = $regedit->getList('//modules');
			$result = array();
			foreach($list as $arr) {
				$result[] = getArrayKey($arr, 0);
			}
			return $result;
		}


		final private function doSomething () {
			if(defined("CRON") && (constant('CRON') == 'CLI')) {
				return true;
			}

			if(defined("CURRENT_VERSION_LINE")) {
				if(CURRENT_VERSION_LINE != "demo") {
					require CURRENT_WORKING_DIR . "/errors/invalid_license.html";
					exit();
				} else {
					return true;
				}
			}

			$keycode = regedit::getInstance()->getVal("//settings/keycode");

			if($this->doStrangeThings($keycode)) {
				return true;
			}


			$comp_keycode = Array();
			$comp_keycode['pro'] = templater::getSomething("pro");
			$comp_keycode['shop'] = templater::getSomething("shop");
			$comp_keycode['lite'] = templater::getSomething("lite");
			$comp_keycode['start'] = templater::getSomething("start");
			$comp_keycode['trial'] = templater::getSomething("trial");

			if(regedit::checkSomething($keycode, $comp_keycode)) {
				return true;
			} else {
				require CURRENT_WORKING_DIR . "/errors/invalid_license.html";
				exit();
			}
		}





		final private function doStrangeThings($keycode) {
			$license_file = SYS_CACHE_RUNTIME . 'trash';
			$cmp_keycode = false;
			$expire = 604800;

			if(file_exists($license_file)) {
				if((time() - filemtime($license_file)) > $expire) {
					$cmp_keycode = base64_decode(file_get_contents($license_file));
				}
			} else {
				file_put_contents($license_file, base64_encode($keycode));
			}

			if($cmp_keycode !== false && $keycode) {
				if($keycode === $cmp_keycode) {
					return true;
				}
			}
			return false;
		}


		public function getRequestId() {
			static $requestId = false;
			if($requestId === false) $requestId = time();
			return $requestId;
		}

		public function getPreLang() {
			return $this->pre_lang;
		}


		protected function autoRedirectToMethod($module) {
			$pre_lang = $this->pre_lang;
			$method = regedit::getInstance()->getVal("//modules/" . $module . "/default_method_admin");

			$url = $pre_lang . "/admin/" . $module . "/" . $method . "/";

			outputBuffer::current()->redirect($url);
		}


		public function calculateRefererUri() {
			if($referer = getRequest('referer')) {
				$_SESSION['referer'] = $referer;
			} else {
				if($referer = getSession('referer')) {
					unset($_SESSION['referer']);
				} else {
					$referer = getServer('HTTP_REFERER');
				}
			}
			$this->calculated_referer_uri = $referer;
		}


		public function getCalculatedRefererUri() {
			if($this->calculated_referer_uri === false) {
				$this->calculateRefererUri();
			}
			return $this->calculated_referer_uri;
		}


		public function isModule($module_name) {
			$regedit = regedit::getInstance();

			if($regedit->getVal('//modules/' . $module_name)) {
				return true;
			} else {
				return false;
			}

		}

		public function setUrlPrefix($prefix = '') {
			$this->url_prefix = $prefix;
		}

		public function getUrlPrefix(){
			return $this->url_prefix ? $this->url_prefix : '';
		}


	};
?>
