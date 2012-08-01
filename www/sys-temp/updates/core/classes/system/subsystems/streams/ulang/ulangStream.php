<?php
	class ulangStream extends umiBaseStream {
		protected $scheme = "ulang", $prop_name = NULL;
		protected static $i18nCache = Array();

		public function stream_open($path, $mode, $options, $opened_path) {
			static $cache = array();
			$info = parse_url($path);
			
			$path = trim(getArrayKey($info, 'host') . getArrayKey($info, 'path'), "/");
			
			if(substr($path, -5, 5) == ':file') {
				$dtdContent = $this->getExternalDTD(substr($path, 0, strlen($path) - 5));
				return $this->setData($dtdContent);
			}
			
			if(strpos(getArrayKey($info, 'query'), 'js') !== false) {
				$mode = 'js';
			} else if(strpos($path, 'js') !== false) {
				$mode = 'js';
				$path = substr($path, 0, strlen($path) - 3);
			} else $mode = 'dtd';
			
			if($mode == 'js') {
				$buffer = outputBuffer::current();
				$buffer->contentType('text/javascript');
				$data = $this->generateJavaScriptLabels($path);
				return $this->setData($data);
			}
			
			if(isset($cache[$path])) {
				$data = $cache[$path];
			} else {
				$i18nMixed = self::loadI18NFiles($path);
				$data = $cache[$path] = $this->translateToDTD($i18nMixed);
			}
			return $this->setData($data);
		}

		protected function translateToDTD($phrases) {
			$dtd = "<!ENTITY quote '&#34;'>\n";
			$dtd .= "<!ENTITY nbsp '&#160;'>\n";
			$dtd .= "<!ENTITY middot '&#183;'>\n";
			$dtd .= "<!ENTITY reg '&#174;'>\n";
			$dtd .= "<!ENTITY copy '&#169;'>\n";
			$dtd .= "<!ENTITY raquo '&#187;'>\n";
			$dtd .= "<!ENTITY laquo '&#171;'>\n";
			
			$request_uri = getServer('REQUEST_URI');
			$request_uri = htmlspecialchars($request_uri);

			foreach($phrases as $ref => $phrase) {				
				$phrase = $this->protectEntityValue($phrase);
				$dtd .= "<!ENTITY {$ref} \"{$phrase}\">\n";
			}

			return $dtd;
		}
		
		protected function isRestrictedRef($ref) {
			$arr = Array('field-', 'object-type-', 'hierarchy-type-', 'fields-group-', 'field-type-');
			
			for($i = 0; $i < sizeof($arr); $i++) {
				if(substr($ref, 0, strlen($arr[$i])) == $arr[$i]) {
					return true;
				}
			}
			return false;
		}

		protected function protectEntityValue($val) {
			$from = array('&', '"', '%');
			$to = array('&amp;', '&quote;', '&#037;');

			$val = str_replace($from, $to, $val);

			return $val;
		}

		protected static function parseLangsPath($path) {
			$protocol = "ulang://";
			if(substr($path, 0, strlen($protocol)) == $protocol) {
				$path = substr($path, strlen($protocol));
			}
			$path = trim($path, "/");
			return explode("/", $path);
		}

		protected static function loadI18NFiles($path) {
			static $current_module, $c = 0;

			if(!$current_module) {
				$controller = cmsController::getInstance();
				$current_module = $controller->getCurrentModule();
			}

			$i18nCache = self::$i18nCache;

			$require_list = self::parseLangsPath($path);

			$lang_prefix = self::getLangPrefix();

			$i18nMixed = Array();

			if(!in_array($current_module, $require_list)) {
				$require_list[] = $current_module;
			}

			$sz = sizeof($require_list);
			for($i = 0; $i < $sz; $i++) {
				$require_name = $require_list[$i];
				
				if($require_name == false) continue;

				$filename_primary = "i18n." . $lang_prefix . ".php";
				$filename_secondary = "i18n.php";

				$folder = ($require_name == "common") ? "/classes/modules/" : "/classes/modules/" . $require_name . "/";
				$folder = CURRENT_WORKING_DIR . $folder;

				$path_primary = $folder . $filename_primary;
				$path_secondary = $folder . $filename_secondary;

				if(array_key_exists($require_name, $i18nCache)) {
					$i18n = $i18nCache[$require_name];
				} else {
					if(file_exists($path_primary)) {
						include $path_primary;
					} else if (file_exists($path_secondary)) {
						include $path_secondary;
					}
				}

				if(isset($i18n) && is_array($i18n)) {
					$i18nCache[$require_name] = $i18n;
					$i18nMixed = $i18n + $i18nMixed;
					unset($i18n);
				}
			}
			self::$i18nCache = $i18nCache;
			
			return $i18nMixed;
		}

		public static function getLabel($label, $path = false, $args = null) {
			static $cache = Array();
			static $langPrefix = false;
			if($langPrefix === false) {
				$langPrefix = self::getLangPrefix();
			}
			
			$lang_path = ($path == false) ? "common/data" : $path;

			if(isset($cache[$lang_path])) {
				$i18nMixed = $cache[$lang_path];
			} else {
				$i18nMixed = self::loadI18NFiles($lang_path);
				$cache[$lang_path] = &$i18nMixed;
			}
			if(isset($i18nMixed[$label])) {
				$res = $i18nMixed[$label];
			} elseif(!$path && strpos($label, 'module-') === 0) {
				$moduleName = str_replace('module-', '', $label);
				$res = self::getLabel($label, $moduleName, $args);
			} else {
				$res = "{$label}";
			}

			if(is_array($args) && sizeof($args) > 2) {
				$res = vsprintf($res, array_slice($args, 2));
			}

			return $res;
		}

		public static function getI18n($key, $pattern = "") {
			static $cache = Array();
			
			if(!$key) {
				return $key;
			}
			
			$lang_path = "common/data";
			$prefix = "i18n::";
			
			if(isset($cache[$lang_path])) {
				$i18nMixed = $cache[$lang_path];
			} else {
				$i18nMixed = self::loadI18NFiles($lang_path);
				$cache[$lang_path] = $i18nMixed;
			}

			$result = NULL;
			foreach($i18nMixed as $i => $v) {
				if($v == $key) {
					if($pattern) {
						if(substr($i, 0, strlen($pattern)) == $pattern) {
							$result = $prefix . $i;
							break;
						}
					} else {
						$result = $prefix . $i;
						break;
					}
				}
			}

			if(!is_null($result)) {
				$allowedPrefixes = Array(
					'object-type-',
					'hierarchy-type-',
					'field-',
					'fields-group-',
					'field-type-',
					'object-'
				);
				$allowed = false;
				$tmp_result = str_replace("i18n::", "", $pattern);
				foreach($allowedPrefixes as $pattern) {
					$pattern = $pattern;

					if(substr($tmp_result, 0, strlen($pattern)) == $tmp_result) {
						$allowed = true;
					}
				}
				if($allowed == false) {
					return NULL;
				}
			}
			return $result;
		}
		
		public static function getLangPrefix() {
			static $ilang;
			if(!is_null($ilang)) {
				return $ilang;
			}
			
			$cmsController = cmsController::getInstance();
			$prefix = $cmsController->getCurrentLang()->getPrefix();
			
			if($cmsController->getCurrentMode() != "admin" && !defined('VIA_HTTP_SCHEME')) {
				return $ilang = checkInterfaceLang($prefix);
			}
			
			if(!is_null($ilang = getArrayKey($_POST, 'ilang'))) {
				$ilang = checkInterfaceLang($ilang);
				setcookie('ilang', $ilang, time() + 3600*24*31, '/');
				return $ilang;
			}

			if(!is_null($ilang = getArrayKey($_GET, 'ilang'))) {
				$ilang = checkInterfaceLang($ilang);
				setcookie('ilang', $ilang, time() + 3600*24*31, '/');
				return $ilang;
			}

			
			if(!is_null($ilang = getCookie('ilang'))) {
				$ilang = checkInterfaceLang($ilang);
				setcookie('ilang', $ilang, time() + 3600*24*31, '/');
				return $ilang;
			}
			
			return $ilang = checkInterfaceLang($prefix);
		}
		
		public function __construct() {
			parent::__construct();
		}
		
		public function __destruct() {}
		
		protected function generateJavaScriptLabels($path) {

			$i18n = self::loadI18NFiles($path);
			
			$regedit = regedit::getInstance();
			$modulesList = $regedit->getList('//modules');
			foreach($modulesList as $moduleName) {
				list($moduleName) = $moduleName;
				if (!isset($i18n['module-' . $moduleName])) $i18n['module-' . $moduleName] = self::getLabel('module-' . $moduleName, $moduleName);
			}

			$result = <<<INITJS
function getLabel(key, str) {if(setLabel.langLabels[key]) {var res = setLabel.langLabels[key];if(str) {res = res.replace("%s", str);}return res;} else {return "[" + key + "]";}}
function setLabel(key, label) {setLabel.langLabels[key] = label;}setLabel.langLabels = new Array();


INITJS;
			foreach($i18n as $i => $v) {
				if(substr($i, 0, 3) == "js-" || strpos($i, "module-") === 0) {
					$i = self::filterOutputString($i);
					$v = self::filterOutputString($v);
					$result .= "setLabel('{$i}', '{$v}');\n";
				}
			}
			umiBaseStream::$allowTimeMark = false;
			return $result;
		}
		
		protected function filterOutputString($string) {
			$from = array("\r\n", "\n", "'");
			$to = array("\\r\\n", "\\n", "\\'");
			$string = str_replace($from, $to, $string);
			return $string;
		}

		protected function getExternalDTD($path) {
			$cmsController = cmsController::getInstance();
			$prefix = $cmsController->getCurrentLang()->getPrefix();

			$info = getPathInfo($cmsController->getTemplatesDirectory() . $path);

			$left = getArrayKey($info, 'dirname') . '/' . getArrayKey($info, 'filename');
			$right = getArrayKey($info, 'extension');
			
			$primaryPath = $left . '.' . $prefix . '.' . $right;
			$secondaryPath = $left . '.' . $right;

			if(is_file($primaryPath)) {
				return file_get_contents($primaryPath);
			}
			
			if(is_file($secondaryPath)) {
				return file_get_contents($secondaryPath);
			}

			return '';
		}
	};
?>