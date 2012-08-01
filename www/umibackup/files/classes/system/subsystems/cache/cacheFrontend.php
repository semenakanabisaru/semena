<?php
	/**
		* Внешний проксирующий класс для работы с кешем.
		* В зависимости от возможностей окружения выбирается оптимальный cacheEngine.
	*/

	class cacheFrontend extends singleton implements iCacheFrontend, iSingleton {
		protected $cacheEngine, $cacheEngineName = "", $connected = false, $enabled = false, $is_sleep = false, $mode = "";
		public static $cacheMode, $currentDomainId = false, $currentlangId = false, $adminMode = false;

		/**
			* Получть экземпляр коллекции
			* @return cacheFrontend экземпляр коллекции
		*/
		public static function getInstance() {
			return parent::getInstance(__CLASS__);
		}

		/**
			* @deprecated
		*/
		public function getIsConnected() {
			return $this->connected;
		}

		/**
			* Сохранить в кеш объект из ядра системы
			* @param umiEntinty $object объект класса дочернего от umiEntinty
			* @param String $objectType = "unknown" тип объекта
			* @param Integer $expire = 86400 TTL записи
			* @return Boolean результат операции
		*/
		public function save(umiEntinty $object, $objectType = "unknown", $expire = 86400) {
			if(!$this->enabled) return false;
			if(!self::$cacheMode) return false;
			if($this->is_sleep) return false;
			if(!$expire) return false;

			if($expire == 86400) {
				$config = mainConfiguration::getInstance();
				if ($config->get('cache', 'streams.cache-enabled')) {
					$newExpire = (int) $config->get('cache', 'streams.cache-lifetime');
					if ($newExpire > 0) {
						$expire = $newExpire;
					}
				}
			}

			if($this->cacheEngine instanceof iCacheEngine) {
				$key = $this->createObjectKey($object->getId(), $objectType);
				$this->clusterSync($key);

				$object->beforeSerialize();
				$result = $this->cacheEngine->saveObjectData($key, $object, $expire);
				$object->afterSerialize();
				return $result;
			} else {
				return false;
			}
		}

		/**
			* Загрузить из кеша объект ядра системы
			* @param Integer $objectId id объекта
			* @param String $objectType = "unknown" тип объекта
			* @return Boolean результат операции
		*/
		public function load($objectId, $objectType = "unknown") {
			if(!$this->enabled) return false;
			if(!self::$cacheMode) return false;
			if($this->is_sleep) return false;
			if(self::$adminMode) return false;

			if($this->cacheEngine instanceof iCacheEngine) {
				$key = $this->createObjectKey($objectId, $objectType);
				$object = $this->cacheEngine->loadObjectData($key);
				if($object instanceof umiEntinty) $object->afterUnSerialize();
				return $object;
			} else {
				return false;
			}
		}

		/**
			* Сохранить данные как результат запроса
			* @param String $sqlString текст запроса, будет использоваться в качестве ключа
			* @param Mixed $sqlResult результат запроса (не #Resoure)
			* @param Integer $expire = 2 TTL записи
			* @return Boolean результат операции
		*/
		public function saveSql($sqlString, $sqlResult, $expire = 30) {
			if(!$this->enabled) return false;
			if(!self::$cacheMode) return false;
			if($this->is_sleep) return false;
			if(!$expire) return false;

			if($this->cacheEngine instanceof iCacheEngine) {
				$key = $this->createObjectKey($this->convertSqlToHash($sqlString), "sql");
				$this->clusterSync($key);
				return $this->cacheEngine->saveRawData($key, $sqlResult, $expire);
			} else {
				return false;
			}
		}

		/**
			* Загрузить данные как результат запроса
			* @param String $sqlString текст запроса, будет использоваться в качестве ключа
			* @return Mixed результат запроса
		*/
		public function loadSql($sqlString) {
			if(!$this->enabled) return false;
			if(!self::$cacheMode) return false;
			if($this->is_sleep) return false;
			if(self::$adminMode) return false;

			if($this->cacheEngine instanceof iCacheEngine) {
				$key = $this->createObjectKey($this->convertSqlToHash($sqlString), "sql");
				return $this->cacheEngine->loadRawData($key);
			} else {
				return false;
			}
		}

		/**
			* Сохранить обычные данные (строка, число)
			* @param String $key ключ записи
			* @param Mixed $data данные
			* @param Integer $expire TTL записи
			* @return Mixed значение ключа
		*/
		public function saveData($key, $data, $expire = 5) {

			if(!$this->enabled) return false;
			if(!self::$cacheMode) return false;
			if($this->is_sleep) return false;
			if(!$expire) return false;

			if($this->cacheEngine instanceof iCacheEngine) {

				$config = mainConfiguration::getInstance();
				$rules = $config->get('cache', 'not-allowed-methods');
				if($rules) {
					foreach($rules as $rule) {
						if ($rule && strpos($key, $rule) !== false) return false;
					}
				}

				$rules = $config->get('cache', 'not-allowed-streams');
				if($rules) {
					foreach($rules as $rule) {
						if ($rule && strpos($key, $rule) !== false) return false;
					}
				}

				$key = $this->createKey($key);
				$this->clusterSync($key);
				return $this->cacheEngine->saveRawData($key, $data, $expire);
			} else {
				return false;
			}
		}

		/**
			* @deprecated
		*/
		public function saveObject($key, $data, $expire = 5) {
			if(!$this->enabled) return false;
			if(!self::$cacheMode) return false;
			if($this->is_sleep) return false;
			if(!$expire) return false;

			if($this->cacheEngine instanceof iCacheEngine) {
				$key = $this->createKey($key);
				$this->clusterSync($key);
				return $this->cacheEngine->saveRawData($key, $data, $expire);
			} else {
				return false;
			}
		}

		/**
			* @deprecated
		*/
		public function saveElement($key, $data, $expire = 10) {
			if(!$this->enabled) return false;
			if(!self::$cacheMode) return false;
			if($this->is_sleep) return false;
			if(!$expire) return false;

			if($this->cacheEngine instanceof iCacheEngine) {
				$key = $this->createKey($key);
				$this->clusterSync($key);
				return $this->cacheEngine->saveObjectData($key, $data, $expire);
			} else {
				return false;
			}
		}

		/**
			* Загрузить обычные данные (строка, число)
			* @param String $key ключ записи
			* @return Mixed значение ключа
		*/
		public function loadData($key) {

			if(!$this->enabled) return false;
			if(!self::$cacheMode) return false;
			if($this->is_sleep) return false;
			if(self::$adminMode) return false;

			if($this->cacheEngine instanceof iCacheEngine) {

				$config = mainConfiguration::getInstance();
				$rules = $config->get('cache', 'not-allowed-methods');
				if($rules) {
					foreach($rules as $rule) {
						if ($rule && strpos($key, $rule) !== false) return false;
					}
				}

				$rules = $config->get('cache', 'not-allowed-streams');
				if($rules) {
					foreach($rules as $rule) {
						if ($rule && strpos($key, $rule) !== false) return false;
					}
				}

				$key = $this->createKey($key);
				return $this->cacheEngine->loadRawData($key);
			} else {
				return false;
			}
		}

		/**
			* Приостановить работу кеша
			* @param Boolean $sleep = false флаг "сна"
		*/
		public function makeSleep($sleep = false) {
			$this->is_sleep = $sleep;
		}

		/**
			* Удалить ключ из кеша
			* @param String $id ключ
			* @param String $type = false тип данных
			* @return Boolean результат операции
		*/
		public function del($id, $type = false) {
			if(!$this->enabled) return false;
			if(!self::$cacheMode) return false;
			if($this->is_sleep) return false;

			if($this->cacheEngine instanceof iCacheEngine) {
				$key = $this->createObjectKey($id, $type);
				$this->clusterSync($key);
				return $this->cacheEngine->delete($key);
			} else {
				return false;
			}
		}

		/**
			* Удалить ключ из кеша без его дополнительной обработки
			* @param String $key чистый ключ
			* @param Boolean $addSuffix = false если true, то в конец ключа будет добавлен суффикс ключа текущей установки
			* @return Boolean результат операции
		*/
		public function deleteKey($key, $addSuffix = false) {
			if($this->cacheEngine instanceof iCacheEngine) {
				if($addSuffix) {
					$key .= $this->getKeySuffix();
				}
				return $this->cacheEngine->delete($key);
			} else {
				return false;
			}
		}

		/**
			* Сбросить весь кеш
		*/
		public function flush() {
			if($this->cacheEngine instanceof iCacheEngine) {
				$this->cacheEngine->flush();
			}
		}

		/**
			* Получить список возможных cacheEngines в порядке приоритета
			* @return Array список названий cacheEngine
		*/
		public static function getPriorityEnginesList($enabledOnly = false) {
			$list = Array('apc', 'eaccelerator', 'xcache', 'memcache', 'fs');
			if($enabledOnly) {
				foreach($list as $i => $engineName) {
					if(self::checkEngine($engineName) == false) {
						unset($list[$i]);
					}
				}
			}
			return $list;
		}

		/**
			* Выбрать наиболее подходящий кеширующий движок
			* @param Array список названий доступных кеширущих движков
			* @return String|Boolean название cacheEngine, либо false, если ничего не подходит
		*/
		public static function chooseCacheEngine($engines) {
			if(!is_array($engines)) {
				return false;
			}

			$result = array_intersect(self::getPriorityEnginesList(), $engines);
			if(sizeof($result)) {
				reset($result);
				return current($result);
			} else {
				return false;
			}
		}

		/**
			* Получить название текущего cache engine
			* @return String название cacheEngine'а
		*/
		public function getCurrentCacheEngineName() {
			return $this->cacheEngineName;
		}

		/**
			* Изменить cacheEngine, который используется системой
			* @param String $cacheEngineName название cacheEngine'а
			* @return Boolean true, если изменение прошло успешно
		*/
		public function switchCacheEngine($cacheEngineName) {
			if(!$cacheEngineName) {
				return $this->saveCacheEngineName("");
			}
			if($this->checkEngine($cacheEngineName)) {
				$this->flush();
				return $this->saveCacheEngineName($cacheEngineName);
			} else {
				return true;
			}
		}

		protected function __construct() {
			$this->detectCacheEngine();
			$cacheEngine = $this->loadEngine($this->cacheEngineName);
			if($cacheEngine instanceof iCacheEngine) {
				$this->connected = (bool) $cacheEngine->getIsConnected();
				if($this->connected) {
					$this->enabled = true;
					self::$cacheMode = true;

					$this->cacheEngine = $cacheEngine;
				}
			}
		}

		protected function convertSqlToHash($sql) {
			return sha1($sql);
		}

		protected function createObjectKey($id, $type) {
			return $id . "_" . $type . $this->getKeySuffix();
		}

		protected function createKey($id) {
			return $id . $this->getKeySuffix();
		}

		protected function getKeySuffix() {
			static $suffix = false, $domain_suffix = false, $postfix = false;
			if($domain_suffix == false) {
				$k = CURRENT_WORKING_DIR;
				if(defined("DEMO_DB_NAME")) {
					$k = DEMO_DB_NAME;
				}
				$domain_suffix = sha1($k . SYS_CACHE_SALT);
			}

			if($suffix && !$postfix) {
				if(self::$currentlangId && self::$currentDomainId) {
					$postfix = self::$currentlangId  . '_' . self::$currentDomainId;
				}

				$suffix =  $suffix . '_' . $postfix;
			}

			if($suffix == false) {
				$suffix = $this->mode . "_" . $domain_suffix;
				if(!$postfix && self::$currentlangId && self::$currentDomainId) {
					$postfix = self::$currentlangId  . '_' . self::$currentDomainId;
					$suffix =  $suffix . '_' . $postfix;
				}
			}

			return $suffix;
		}

		protected function loadEngine($engineName) {
			if(!$engineName) {
				return NULL;
			}

			$filepath = SYS_KERNEL_PATH . "subsystems/cache/engines/" . $engineName . ".php";
			if(file_exists($filepath)) {
				$classname = $engineName . "CacheEngine";

				if(!class_exists($classname)) {
					include $filepath;
				}

				if(class_exists($classname)) {
					return new $classname;
				} else {
					throw new coreException("Failed to load cache engine: class \"{$classname}\" not found in \"{$filepath}\"");
				}
			} else {
				return NULL;
			}
		}

		/**
			* Определить текущий cacheEngine и установить как текущий
		*/
		protected function detectCacheEngine() {
			if($cacheEngineName = $this->loadCacheEngineName()) {
				if($this->checkEngine($cacheEngineName)) {
					$this->cacheEngineName = $cacheEngineName;
					return true;
				}
			}

			if($cacheEngineName == "none") return false;

			if($cacheEngineName == "auto") {
				$cacheEngineName = $this->autoDetectCacheEngine();
			}

			if($cacheEngineName) {
				$this->cacheEngineName = $cacheEngineName;
				$this->saveCacheEngineName($cacheEngineName);
				return true;
			} else return false;
		}


		/**
			* Определяет backendEngine из списка доступных
			* @return String|Boolean название доступного cacheEngine, либо false
		*/
		protected function autoDetectCacheEngine() {
			$list = $this->getPriorityEnginesList();
			foreach($list as $cacheEngineName) {
				if($cacheEngineName == 'fs') continue;
				if($this->checkEngine($cacheEngineName)) {
					return $cacheEngineName;
				}
			}
			return false;
		}

		/**
			* Определить, доступен ли cacheEngine
			* @param String $engineName название cacheEngine'а
			* @return Boolean true, если cacheEngine доступен
		*/
		protected function checkEngine($engineName) {
			switch($engineName) {
				case "apc": {
					return function_exists("apc_store");
				}

				case "eaccelerator": {
					return function_exists("eaccelerator_put");
				}

				case "xcache": {
					return function_exists("xcache_set");
				}

				case "memcache": {
					return class_exists("Memcache");
				}

				case "shm": {
					return function_exists("shm_attach");
				}

				case "fs": {
					return true;
				}

				default: {
					return false;
				}
			}
		}

		/**
			* Сохранить название текущего cacheEngine во временный файл
			* @param String название cacheEngine
			* @return Boolean true, если сохранение произошло без ошибок
		*/
		protected function saveCacheEngineName($cacheEngineName) {
			$config = mainConfiguration::getInstance();
			return $config->set('cache', 'engine', $cacheEngineName);
		}

		/**
			* Получить название текущего cacheEngine из временного файла
			* @return String название текущего cacheEngine
		*/
		protected function loadCacheEngineName() {
			$config = mainConfiguration::getInstance();
			return $config->get('cache', 'engine');
		}

		protected function clusterSync($key) {
			static $inst;

			if(CLUSTER_CACHE_CORRECTION) {
				if(!$inst) $inst = clusterCacheSync::getInstance();
				$suffix = $this->getKeySuffix();
				$inst->notify(substr($key, 0, strlen($key) - strlen($suffix)));
			}
		}
	};
?>