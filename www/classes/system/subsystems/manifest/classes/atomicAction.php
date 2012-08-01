<?php
	abstract class atomicAction implements iAtomicAction {
		protected $params = Array(), $name, $title = "", $callback, $enviroment = Array(),
			$transaction, $afterHibernate = false;
		
		public function __construct($name, transaction $transaction) {
			$this->name = (string) $name;
			$this->transaction = $transaction;
		}
		
		public function getName() {
			return $name;
		}
		
		public function setTitle($title) {
			$this->title = (string) $title;
		}
		
		public function getTitle() {
			return $this->title;
		}
		
		public function setParams($params) {
			if(is_array($params)) {
				$this->params = $params;
				return true;
			} else {
				return false;
			}
		}
		
		public function addParam($name, $value) {
			$this->params[$name] = $value;
		}
		
		protected function replaceParams($str) {
			foreach($this->params as $name => $value) {
				$str = str_replace("{" . $name . "}", $value, $str);
			}
			return $str;
		}
		
		public static function getSourceFilePath($actionName, $package = false) {
			if($package) {
				$filepath = SYS_KERNEL_PATH . "subsystems/manifest/actions/" . $package . "/" . $actionName . ".php";
			} else {
				$filepath = SYS_KERNEL_PATH . "subsystems/manifest/actions/" . $actionName . ".php";
			}
			return $filepath;
		}
		
		public static function load($actionName, transaction $transaction, $package = false) {
			$filepath = self::getSourceFilePath($actionName, $package);
			$classname = $actionName . "Action";
			
			if(file_exists($filepath)) {
				include_once $filepath;
				
				if(class_exists($classname)) {
					return new $classname($actionName, $transaction);
				}
			}
			throw new Exception("Can't load action \"{$actionName}\"");
		}
		
		public function getParam($key) {
			return isset($this->params[$key]) ? $this->params[$key] : NULL;
		}
		
		public function setCallback(iManifestCallback $callback) {
			$this->callback = $callback;
		}
		
		public function setEnviroment($enviroment) {
			if(is_array($enviroment)) {
				$this->enviroment = $enviroment;
			} else {
				throw new Exception("Expected array as first param");
			}
		}
		
		public function getTransaction() {
			return $this->transaction;
		}
		
		public function hibernate($returnIfAfterHibernation = true) {
			$return = ($returnIfAfterHibernation && $this->afterHibernate);
			$this->afterHibernate = false;
			if($return) {
				@ob_clean();
				return false;
			}
			$count = &$this->getTransaction()->getManifest()->hibernationsCount;
			if(--$count < 0) {
				return false;
			}
			
			$this->afterHibernate = true;
			
			$manifest = $this->getTransaction()->getManifest();
			return $manifest->hibernate();
		}
		
		public function refresh() {
			if(isset($_REQUEST['manifest-refresh-try'])) {
				$try = (int) $_REQUEST['manifest-refresh-try'];
			} else {
				$try = 0;
			}

			parse_str($_SERVER['QUERY_STRING'], $query);
			$query['manifest-refresh-try'] = ++$try;
			$url = "http://" . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'] . "?" . http_build_query($query, '', '&');

			header("Location: {$url}");
			exit();
		}

		
		protected function mysql_query($sql) {
			$result = l_mysql_query($sql);
			if($err = l_mysql_error()) {
				l_mysql_query("ROLLBACK");
				throw new Exception("Query \"$sql\" raised error \"{$err}\"");
			} else {
				return $result;
			}
		}
		
		protected function getEnviromentValue($key) {
			if(isset($this->enviroment[$key])) {
				return $this->enviroment[$key];
			} else {
				return NULL;
			}
		}
	};
?>