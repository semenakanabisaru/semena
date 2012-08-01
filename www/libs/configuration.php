<?php
	class mainConfiguration {
		private static $instance = null;
		private $ini    = array();
		private $edited = false;
		/**
		 *
		 */
		private function __construct() {
			if(!is_readable(CONFIG_INI_PATH)) {
				throw new Exception("Can't find configuration file");
			}
			$this->ini = parse_ini_file(CONFIG_INI_PATH, true);

			if(isset($this->ini['system']) && isset($this->ini['system']['session-lifetime']) && $this->ini['system']['session-lifetime'] < 1) {
 			   $this->ini['system']['session-lifetime'] = 1440;
			}
		}
		/**
		 *
		 */
		public function __destruct() {
			if($this->edited) {
				$this->writeIni();
			}
		}
		/**
		 * Возвращает экземпляр конфигурации
		 * return mainConfiguration
		 */
		public static function getInstance() {
			if(!self::$instance) {
				self::$instance = new mainConfiguration();
			}
			return self::$instance;
		}
		/**
		 * Возвращает конфигурацию в виде массива
		 * return Array
		 */
		public function getParsedIni() {
			return $this->ini;
		}
		/**
		 * Возвращает значение переменной
		 * @param String $section
		 * @param String $variable
		 * @return String
		 */
		public function get($section, $variable) {
			if(isset($this->ini[$section]) &&
			   isset($this->ini[$section][$variable])) {
				$value = $this->ini[$section][$variable];
				$value = $this->unescapeValue($value);
				if ($section == 'system' && $variable == 'session-lifetime' && $value < 1) $value = 1440;
				return $value;
			} else return null;
		}
		/**
		 * Устанавливает или стирает значение переменной
		 * @param String $section
		 * @param String $variable
		 * @param Mixed $value
		 */
		public function set($section, $variable, $value) {
			if(!isset($this->ini[$section])) {
				$this->ini[$section] = array();
			}
			if($value === null && isset($this->ini[$section][$variable])) {
				unset($this->ini[$section][$variable]);
			} else {
				if ($section == 'system' && $variable == 'session-lifetime' && $value < 1) $value = 1440;
				$this->ini[$section][$variable] = $value;
			}
			$this->edited = true;
		}

		/**
			* Возвращает список переметров в секции
			* @param String $section
			* @return Array
		*/
		public function getList($section) {
			if(isset($this->ini[$section]) && is_array($this->ini[$section])) {
				return array_keys($this->ini[$section]);
			} return null;
		}

		public function includeParam($key, array $params =  null) {
			static $defaultParams = Array();

			$path = $this->get('includes', $key);
			if(strpos($path, "{") !== false) {
				if(class_exists('cmsController') && !sizeof($defaultParams)) {
					$cmsController = cmsController::getInstance();

					if($lang = $cmsController->getCurrentLang()) {
						$defaultParams['lang'] = $cmsController->getCurrentLang()->getPrefix();
					}
					if($lang = $cmsController->getCurrentLang()) {
						$defaultParams['domain'] = $cmsController->getCurrentDomain()->getHost();
					}
				}

				$params = (is_null($params)) ? $defaultParams : array_merge($params, $defaultParams);
				foreach($params as $i => $v) $path = str_replace('{' . $i . '}', $v,  $path);
			}


			if(substr($path, 0, 2) == "~/") {
				$path = CURRENT_WORKING_DIR . substr($path, 1);
			}

			return $path;
		}

		/**
		 *
		 */
		private function writeIni() {
			$iniString = "";
			foreach($this->ini as $sname => $section) {
				if(empty($section)) continue;
				$iniString .= "[{$sname}]\n";
				foreach($section as $name => $value) {
					if(is_array($value)) {
						foreach($value as $sval) {
							$sval = ($sval !== '') ? '"' . $sval . '"' : '';
							$iniString .= "{$name}[] = {$sval}\n";
						}
					} else {
						$value = ($value !== '') ? '"' . $value . '"' : '';
						$iniString .= "{$name} = {$value}\n";
					}
				}
				$iniString .= "\n";
			}
			file_put_contents(CONFIG_INI_PATH, $iniString);
		}

		private function unescapeValue($value) {
			if(is_array($value)) {
				foreach($value as $i => $v) {
					$value[$i] = $this->unescapeValue($v);
				}
				return $value;
			}

			if(strlen($value) >= 2 && substr($value, 0, 1) == "'" && substr($value, -1, 1) == "'") {
				$value = substr($value, 1, strlen($value) - 2);
			}
			return $value;
		}
	};
?>