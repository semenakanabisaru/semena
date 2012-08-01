<?php
/**
	* Этот класс служит для управления/получения доступа к объектам.
	* Класс является синглтоном, экземпляр класса можно получить через статический метод getInstance()
*/
	class umiObjectsCollection extends singleton implements iSingleton, iUmiObjectsCollection {
		private	$objects = Array();
		private $updatedObjects = Array();

		protected function __construct() {
		}

		/**
			* Получить экземпляр коллекции
			* @return umiObjectsCollection экземпляр класса
		*/
		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}

		/**
			* Проверить, загружен ли в память объект с id $object_id
			* @param Interger $object_id id объекта
			* @return Boolean true, если объект загружен
		*/
		private function isLoaded($object_id) {
			if(gettype($object_id) == "object") {
			throw new coreException("Object given!");
			}
			return (bool) array_key_exists($object_id, $this->objects);
		}

		/**
			* Проверить, существует ли в БД объект с id $object_id
			* @deprecated
			* @param Integer $object_id id объекта
			* @return Boolean true, если объект существует в БД
		*/
		public function isExists($object_id) {
			$object_id = (int) $object_id;
			$result = l_mysql_query("SELECT COUNT(*) FROM cms3_objects WHERE id = '{$object_id}'");

			if($err = l_mysql_error()) {
				throw new coreException($err);
			} else {
				list($count) = mysql_fetch_row($result);
			}
			return ($count > 0);
		}

		/**
			* Получтить экземпляр объекта с id $object_id
			* @param Integer $object_id id объекта
			* @return umiObject экземпляр объекта $object_id, либо false в случае неудачи
		*/
		public function getObject($object_id, $row = false) {
			$object_id = (int) $object_id;

			if(!$object_id) {
				return false;
			}

			if($this->isLoaded($object_id)) {
				return $this->objects[$object_id];
			}

			$object = cacheFrontend::getInstance()->load($object_id, "object");
			if($object instanceof umiObject == false) {
				try {
					$object = new umiObject($object_id, $row);
				} catch (baseException $e) {
					return false;
				}
				cacheFrontend::getInstance()->save($object, "object");
			}


			if(is_object($object)) {
				$this->objects[$object_id] = $object;
				return $this->objects[$object_id];
			} else {
				return false;
			}
		}

		/**
		* Получить тип по его GUID
		*
		* @param mixed $guid Global Umi Identifier
		* @return umiObject тип данных (класс umiObjectType), либо false
		*/
		public function getObjectByGUID($guid) {
			$id = $this->getObjectIdByGUID($guid);
			return $this->getObject($id);
		}

		/**
		* Получить числовой идентификатор типа по его GUID
		*
		* @param string $guid Global Umi Identifier
		* @return integer/boolean(false)
		*/
		public function getObjectIdByGUID($guid) {
			static $cache = array();

			if(!$guid) {
				return false;
			}

			if(!cmsController::$IGNORE_MICROCACHE && isset($cache[$guid])) return $cache[$guid];

			$guid = l_mysql_real_escape_string($guid);
			$query = "SELECT `id` FROM `cms3_objects` WHERE `guid` = '{$guid}'";
			$result = l_mysql_query($query);

			if($error = l_mysql_error()) {
				throw new coreException($error);
			}

			if(list($id) = mysql_fetch_row($result)) {
				return $cache[$guid] = $id;
			} else {
				return $cache[$guid] = false;
			}
		}

		/**
			* Удалить объект с id $object_id. Если объект заблокирован, он не будет удален.
			* При удалении принудительно вызывается commit() на удаляемом объекте
			* Нельзя удалить пользователей с guid равными system-guest и system-supervisor, нельзя удалить группу супервайзеров.
			* @param Integer $object_id id объекта
			* @return Boolean true, если удаление удалось
		*/
		public function delObject($object_id) { 
			if($this->isExists($object_id)) {
				$this->disableCache();

				$object_id = (int) $object_id;

				if(defined("SV_USER_ID")) {
					if($object_id == SV_USER_ID || $object_id == SV_GROUP_ID || $object_id == $this->getObjectIdByGUID('system-guest')) {
						throw new coreException("You are not allowed to delete object #{$object_id}. Never. Don't even try.");
					}
				}


				//Make sure, we don't will not try to commit it later
				$object = $this->getObject($object_id);
				$object->commit();

				$sql = "DELETE FROM cms3_objects WHERE id = '{$object_id}' AND is_locked='0'";
				l_mysql_query($sql);

				if($err = l_mysql_error()) {
					throw new coreException($err);
					return false;
				}

				if($this->isLoaded($object_id)) {
					unset($this->objects[$object_id]);
				}

				cacheFrontend::getInstance()->del($object_id, "object");

				return true;
			} else {
				return false;
			}
		}

		/**
			* Создать новый объект в БД
			* @param String $name название объекта.
			* @param Integer $type_id id типа данных (класс umiObjectType), которому будет принадлежать объект.
			* @param Boolean $is_locked=false Состояние блокировки по умолчанию. Рекоммендуем этот параметр не указывать.
			* @return Integer id созданного объекта, либо false в случае неудачи
		*/
		public function addObject($name, $type_id, $is_locked = false) {
			$this->disableCache();

			$type_id = (int) $type_id;

			if(!$type_id) {
				throw new coreException("Can't create object without object type id (null given)");
			}

			$sql = "INSERT INTO cms3_objects (type_id) VALUES('$type_id')";
			l_mysql_query($sql);

			if($err = l_mysql_error()) {
				throw new coreException($err);
				return false;
			}

			$object_id = l_mysql_insert_id();
			$object = new umiObject($object_id);

			$object->setName($name);
			$object->setIsLocked($is_locked);

			//Set current user
			if($users_inst = cmsController::getInstance()->getModule("users")) {
				if($users_inst->is_auth()) {
					$user_id = cmsController::getInstance()->getModule("users")->user_id;
					$object->setOwnerId($user_id); 
				}
			} else {
				$object->setOwnerId(NULL);
			}

			//try {
				$this->resetObjectProperties($object_id);
			//} catch (valueRequiredException $e) {
			//	$e->unregister();
			//}

			$object->commit();
			$this->objects[$object_id] = $object;

			return $object_id;
		}

		/**
			* Сделать копию объекта и всех его свойств
			* @param id $iObjectId копируемого объекта
			* @return Integer id объекта-копии
		*/
		public function cloneObject($iObjectId) {
			$vResult = false;

			$oObject = $this->getObject($iObjectId);
			if ($oObject instanceof umiObject) {
				// clone object definition
				$sSql = "INSERT INTO cms3_objects (name, is_locked, type_id, owner_id) SELECT name, is_locked, type_id, owner_id FROM cms3_objects WHERE id = '{$iObjectId}'";
				l_mysql_query($sSql);

				if ($err = l_mysql_error()) {
					throw new coreException($err);
					return false;
				}

				$iNewObjectId = l_mysql_insert_id();

				// clone object content
				$sSql = "INSERT INTO cms3_object_content (obj_id, field_id, int_val, varchar_val, text_val, rel_val, tree_val,float_val)  SELECT '{$iNewObjectId}' as obj_id, field_id, int_val, varchar_val, text_val, rel_val, tree_val,float_val FROM cms3_object_content WHERE obj_id = '$iObjectId'";
				l_mysql_query($sSql);

				if ($err = l_mysql_error()) {
					throw new coreException($err);
					return false;
				}

				$vResult = $iNewObjectId;
			}

			return $vResult;
		}

		/**
			* Получить отсортированный по имени список всех объектов в справочнике $guide_id (id типа данных).
			* Равнозначно если бы мы хотели получить этот список для всех объектов определенного типа данных
			* @param id $guide_id справочника (id типа данных)
			* @return Array массив, где ключи это id объектов, а значения - названия объектов
		*/
		public function getGuidedItems($guide_id) {
			$res = Array();			
			
			if (is_int($guide_id)) {
				$guide_id = (int) $guide_id;
			}
			else {
				$guide_id = addslashes($guide_id);
				$query = "SELECT `id` FROM `cms3_object_types` WHERE `guid`='". $guide_id ."' LIMIT 1";				
				$result = l_mysql_query($query);
				if ( 0<mysql_numrows($result) ) {
					$guide_id = mysql_result($result, 0);				
				}
				else {
					$guide_id = (int) $guide_id;
				}
				
			}
			
			$ignoreSorting = intval(regedit::getInstance()->getVal("//settings/ignore_guides_sort")) ? true : false;			
			
			if($ignoreSorting)
				$sql = "SELECT id, name FROM cms3_objects WHERE type_id = '{$guide_id}' ORDER BY id ASC";
			else
				$sql = "SELECT id, name FROM cms3_objects WHERE type_id = '{$guide_id}' ORDER BY name ASC";
			

			$result = l_mysql_query($sql);

			if($err = l_mysql_error()) {
				throw new coreException($err);
				return false;
			}

			while(list($id, $name) = mysql_fetch_row($result)) {
				$res[$id] = $this->translateLabel($name);
			}

			if(!$ignoreSorting)
				natsort($res);

			return $res;
		}

		public function getCountByTypeId($typeId) {
			$count = false;
			$typeId = (int) $typeId;
			$connection = ConnectionPool::getInstance()->getConnection();
			$result = $connection->queryResult("SELECT COUNT(id) FROM cms3_objects WHERE type_id = '{$typeId}'");
			if($error = l_mysql_error()) {
				throw new databaseException($error);
			}
			list($count) = $result->fetch();
			return $count;
		}

		/**
			* Обнулить все свойства у объекта $object_id
			* @param Integer $object_id id объекта
		*/
		protected function resetObjectProperties($object_id) {
			$object = $this->getObject($object_id);
			$object_type_id = $object->getTypeId();
			$object_type = umiObjectTypesCollection::getInstance()->getType($object_type_id);
			$tableName = umiBranch::getBranchedTableByTypeId($object_type_id);

			$query = "INSERT INTO {$tableName} (obj_id, field_id, int_val, varchar_val, text_val, rel_val, tree_val, float_val) VALUES ";

			$object_fields = $object_type->getAllFields();

			$vals = array();
			foreach($object_fields as $object_field) {
				$vals[] = "('{$object_id}', '{$object_field->getId()}', NULL, NULL, NULL, NULL, NULL, NULL)";
			}

			if(sizeof($object_fields) != 0) {
				l_mysql_query($query . implode($vals, ", "));
			} else {
				$sql = "INSERT INTO {$tableName} (obj_id, field_id) VALUES ('{$object_id}', NULL)";
				l_mysql_query($sql);
				if($err = l_mysql_error()) {
					throw new coreException($err);
				}
			}
		}


		/**
			* Выгрузить объект из коллекции
			* @param Integer $object_id id объекта
		*/
		public function unloadObject($object_id) {
			if($this->isLoaded($object_id)) {
				unset($this->objects[$object_id]);
			} else {
				return false;
			}
		}

		/**
			* Выгрузить объекты из коллекции
			* @param Integer $object_id id объекта
		*/
		public function unloadAllObjects() {
			
			foreach($this->objects as $object_id => $v)
			{
                    unset($this->objects[$object_id]);
			}
		}

		/**
			* Получить id всех объектов, загруженных в коллекцию
			* @return Array массив, состоящий из id объектов
		*/
		public function getCollectedObjects() {
			return array_keys($this->objects);
		}

		/**
			* Указать, что $object_id был изменен во время сессии. Используется внутри ядра.
			* Явный вызов этого метода клиентским кодом не нужен.
			* @param Integer $object_id id объекта
		*/
		public function addUpdatedObjectId($object_id) {
			if(!in_array($object_id, $this->updatedObjects)) {
				$this->updatedObjects[] = $object_id;
			}
		}

		/**
			* Получить список измененных объектов за текущую сессию
			* @return Array массив, состоящий из id измененных значений
		*/
		public function getUpdatedObjects() {
			return $this->updatedObjects;
		}

		/**
			* Деструктор коллекции. Явно вызывать его не нужно никогда.
		*/
		public function __destruct() {
			if(sizeof($this->updatedObjects)) {
				if(function_exists("deleteObjectsRelatedPages")) {
					deleteObjectsRelatedPages();

				}
			}
		}
		
		public function clearCache() {
			$keys = array_keys($this->objects);
			foreach($keys as $key) unset($this->objects[$key]);			
			$this->objects = array();
		}
	}
?>