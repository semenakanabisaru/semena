<?php
/**
	* Коллекция для работы с типами данных (umiObjectType), синглтон.
*/
	class umiObjectTypesCollection extends singleton implements iSingleton, iUmiObjectTypesCollection {
		private $types = Array();

		/**
			* Конструктор, который ничего не делает
		*/
		protected function __construct() {
		}

		/**
			* Получить экземпляр коллекции
			* @return umiObjectTypesCollection экземпляр класса
		*/
		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}


		/**
			* Полчить тип по его id
			* @param Integer $type_id id типа данных
			* @return umiObjectType тип данных (класс umiObjectType), либо false
		*/
		public function getType($type_id) {
			if(!$type_id) {
				return false;
			}

			if(!is_numeric($type_id)) {
				$type_id = $this->getTypeIdByGUID($type_id);
			}

			if($this->isLoaded($type_id)) {
				return $this->types[$type_id];
			} else {
				$this->loadType($type_id);
				return getArrayKey($this->types, $type_id);
			}
			throw new coreException("Unknow error");
		}

		/**
		* Получить тип по его GUID
		*
		* @param mixed $guid Global Umi Identifier
		* @return umiObjectType тип данных (класс umiObjectType), либо false
		*/
		public function getTypeByGUID($guid) {
			$id = $this->getTypeIdByGUID($guid);
			return $this->getType($id);
		}

		/**
		* Получить числовой идентификатор типа по его GUID
		*
		* @param string $guid Global Umi Identifier
		* @return integer/boolean(false)
		*/
		public function getTypeIdByGUID($guid) {
			static $cache = array();

			if(!$guid) {
				return false;
			}

			if(!cmsController::$IGNORE_MICROCACHE && isset($cache[$guid])) return $cache[$guid];

			$guid = l_mysql_real_escape_string($guid);
			$query = "SELECT `id` FROM `cms3_object_types` WHERE `guid` = '{$guid}'";
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
			* Создать тип данных с названием $name, дочерний от типа $parent_id
			* @param Integer $parent_id id родительского типа данных, от которого будут унаследованы поля и группы полей
			* @param String $name название создаваемого типа данных
			* @param Boolean $is_locked=false статус блокировки. Этот параметр указывать не надо
			* @return Integer id созданного типа данных, либо false в случае неудачи
		*/
		public function addType($parent_id, $name, $is_locked = false, $ignore_parent_groups = false) {
			$this->disableCache();

			$parent_id = (int) $parent_id;

			$sql = "INSERT INTO cms3_object_types (parent_id) VALUES('{$parent_id}')";
			$result = l_mysql_query($sql);

			if($err = l_mysql_error()) {
				throw new coreException($err);
				return false;
			}

			$type_id = l_mysql_insert_id();

			//Making types inheritance...
			if (!$ignore_parent_groups) {

				$sql = "SELECT * FROM cms3_object_field_groups WHERE type_id = '{$parent_id}'";
				$result = l_mysql_query($sql);

				if($err = l_mysql_error()) {
					throw new coreException($err);
					return false;
				}

				while($row = mysql_fetch_assoc($result)) {
					$sql = "INSERT INTO cms3_object_field_groups (name, title, type_id, is_active, is_visible, ord, is_locked) VALUES ('" . l_mysql_real_escape_string($row['name']) . "', '" . l_mysql_real_escape_string($row['title']) . "', '{$type_id}', '{$row['is_active']}', '{$row['is_visible']}', '{$row['ord']}', '{$row['is_locked']}')";
					l_mysql_query($sql);

					if($err = l_mysql_error()) {
						throw new coreException($err);
						return false;
					}

					$old_group_id = $row['id'];
					$new_group_id = l_mysql_insert_id();

					$sql = "INSERT INTO cms3_fields_controller SELECT ord, field_id, '{$new_group_id}' FROM cms3_fields_controller WHERE group_id = '{$old_group_id}'";
					l_mysql_query($sql);

					if($err = l_mysql_error()) {
						throw new coreException($err);
						return false;
					}
				}
			}

			$parent_hierarchy_type_id = false;
			if($parent_id) {
				$parent_type = $this->getType($parent_id);
				if($parent_type) {
					$parent_hierarchy_type_id = $parent_type->getHierarchyTypeId();
				}
			}

			$type = new umiObjectType($type_id);
			$type->setName($name);
			$type->setIsLocked($is_locked);
			if($parent_hierarchy_type_id) {
				$type->setHierarchyTypeId($parent_hierarchy_type_id);
			}
			$type->commit();

			$this->types[$type_id] = $type;

			umiBranch::saveBranchedTablesRelations();
			return $type_id;
		}

		/**
			* Удалить тип данных с id $type_id.
			* Все объекты этого типа будут автоматически удалены без возможности восстановления
			* Все дочерние типы от $type_id будут удалены рекурсивно.
			* @param Integer $type_id id типа данных, который будет удален
			* @return Boolean true, если удаление было успешным
		*/
		public function delType($type_id) {
			if($this->isExists($type_id)) {

				$type = $this->getType($type_id);
				if ($type->getIsLocked()) throw new publicAdminException (getLabel('error-object-type-locked'));

				$this->disableCache();

				$childs = $this->getChildClasses($type_id);

				$sz = sizeof($childs);
				for($i = 0; $i < $sz; $i++) {
					$child_type_id = $childs[$i];

					if($this->isExists($child_type_id)) {
						$sql = "DELETE FROM cms3_objects WHERE type_id = '{$child_type_id}'";
						l_mysql_query($sql);

						$sql = "DELETE FROM cms3_object_types WHERE id = '{$child_type_id}'";
						l_mysql_query($sql);

						$sql = "DELETE FROM cms3_import_types WHERE new_id = '{$child_type_id}';";
						l_mysql_query($sql);

						if($err = l_mysql_error()) {
							throw new coreException($err);
							return false;
						}
						unset($this->types[$child_type_id]);
					}
				}

				$type_id = (int) $type_id;

				$sql = "DELETE FROM cms3_objects WHERE type_id = '{$type_id}'";
				l_mysql_query($sql);

				$sql = "DELETE FROM cms3_object_types WHERE id = '{$type_id}'";
				l_mysql_query($sql);

				$sql = "DELETE FROM cms3_import_types WHERE new_id = '{$type_id}';";
				l_mysql_query($sql);

				if($err = l_mysql_error()) {
					throw new coreException($err);
					return false;
				}

				unset($this->types[$type_id]);

				umiBranch::saveBranchedTablesRelations();
				return true;
			} else {
				return false;
			}
		}

		/**
			* @deprecated
			* @param Integer $type_id
			* @return Boolean true всегда
		*/
		public function isExists($type_id) {
			return true;
		}

		/**
			* Проверить, загружен ли тип данных $type_id в коллекцию
			* @param Integer $type_id id типа данных
			* @return Boolean true, если загружен
		*/
		private function isLoaded($type_id) {
			if($type_id === false) return false;
			return (bool) array_key_exists($type_id, $this->types);
		}

		/**
			* Загрузить тип данных в память
			* @param Integer $type_id id типа данных
			* @return Boolean true, если объект удалось загрузить
		*/
		private function loadType($type_id) {
			if($this->isLoaded($type_id)) {
				return true;
			} else {
			    $type = cacheFrontend::getInstance()->load($type_id, "object_type");
				if($type instanceof umiObjectType == false) {
					try {
						$type = new umiObjectType($type_id);
					} catch(privateException $e) {
						return false;
					}

					cacheFrontend::getInstance()->save($type, "object_type");
				}

				if(is_object($type)) {
					$this->types[$type_id] = $type;
					return true;
				} else {
					return false;
				}
			}
		}

		/**
			* Получить список дочерних типов по отношению к типу $type_id
			* @param Integer $type_id id родительского типа данных
			* @return Array массив, состоящий из id дочерних типов данных. Если не получилось, то false.
		*/
		public function getSubTypesList($type_id) {
			if(!is_numeric($type_id)) {
				throw new coreException("Type id must be numeric");
				return false;
			}

			$type_id = (int) $type_id;

			$sql = "SELECT id FROM cms3_object_types WHERE parent_id = '{$type_id}'";
			$result = l_mysql_query($sql);

			if($err = l_mysql_error()) {
				throw new coreException($err);
				return false;
			}

			$res = Array();
			while(list($type_id) = mysql_fetch_row($result)) {
				$res[] = (int) $type_id;
			}
			return $res;
		}

		/**
			* Получить id типа данных, который является непосредственным родителем типа $type_id
			* @param Integer $type_id id типа данных
			* @return Integer id родительского типа, либо false
		*/
		public function getParentClassId($type_id) {
			if($this->isLoaded($type_id)) {
				return $this->getType($type_id)->getParentId();
			} else {
				$type_id = (int) $type_id;
				$sql = "SELECT parent_id FROM cms3_object_types WHERE id = '{$type_id}'";
				$result = l_mysql_query($sql);

				if($err = l_mysql_error()) {
					throw new coreException($err);
					return false;
				}

				if(list($parent_type_id) = mysql_fetch_row($result)) {
					return (int) $parent_type_id;
				} else {
					return false;
				}
			}
		}

		/**
			* Получить список всех дочерних типов от $type_id на всю глубину наследования
			* @param Integer $type_id id типа данных
			* @param mixed $childs этот параметр указывать не требуется
			* @return Array массив, состоящий из $id типов данных
		*/
		public function getChildClasses($type_id, $childs = false) {
			$res = Array();
			if(!$childs) $childs = Array();

			$type_id = (int) $type_id;

			$sql = "SELECT id FROM cms3_object_types WHERE parent_id = '{$type_id}'";
			$result = l_mysql_query($sql);

			if($err = l_mysql_error()) {
				throw new coreException($err);
				return false;
			}

			while(list($id) = mysql_fetch_row($result)) {
				$res[] = $id;

				if(!in_array($id, $childs)) $res = array_merge($res, $this->getChildClasses($id, $res));
			}
			$res = array_unique($res);
			return $res;
		}

		/**
			* Получить список типов данных, которые можно использовать в качестве справочников
			* @param Boolean $public_only=false искать только те типа данных, у которых стоит флаг "Публичный"
			* @param Integer $parentTypeId = null искать только в этом родителе
			* @return Array массив, где ключ это id типа данных, а значение это его название
		*/
		public function getGuidesList($public_only = false, $parentTypeId = null) {
			$res = Array();

			if($public_only) {
				$sql = "SELECT id, name FROM cms3_object_types WHERE is_guidable = '1' AND is_public = '1'";
			} else {
				$sql = "SELECT id, name FROM cms3_object_types WHERE is_guidable = '1'";
			}

			if($parentTypeId) {
				$parentTypeId = (int) $parentTypeId;
				 $sql .= " AND parent_id = '{$parentTypeId}'";
			}

			$result = l_mysql_query($sql);

			while(list($id, $name) = mysql_fetch_row($result)) {
				$res[$id] = $this->translateLabel($name);
			}
			return $res;
		}

		/**
			* Получить список всех типов данных, связанных с базовым типом (umiHierarchyType) $hierarchy_type_id
			* @param Integer $hierarchy_type_id id базового типа
			* @param Boolean $ignoreMicroCache=false не использовать микрокеширование результата
			* @return Array массив, где ключ это id типа данных, а значние - название типа данных
		*/
		public function getTypesByHierarchyTypeId($hierarchy_type_id, $ignoreMicroCache = false) {
			static $cache = Array();
			$hierarchy_type_id = (int) $hierarchy_type_id;

			if(isset($cache[$hierarchy_type_id]) && $ignoreMicroCache == false) return $cache[$hierarchy_type_id];

			$sql = "SELECT id, name FROM cms3_object_types WHERE hierarchy_type_id = '{$hierarchy_type_id}'";
			$result = l_mysql_query($sql);

			if($err = l_mysql_error()) {
				throw new coreException($err);
				return false;
			}

			$res = Array();
			while(list($id, $name) = mysql_fetch_row($result)) {
				$res[$id] = $this->translateLabel($name);
			}

			return $cache[$hierarchy_type_id] = $res;
		}

		/**
			* Получить тип данных, связанный с базовым типом (umiHierarchyType) $hierarchy_type_id
			* @param Integer $hierarchy_type_id id базового типа
			* @param Boolean $ignoreMicroCache=false не использовать микрокеширование результата
			* @return Integer id типа данных, либо false
		*/
		public function getTypeByHierarchyTypeId($hierarchy_type_id, $ignoreMicroCache = false) {
			static $cache = Array();
			$hierarchy_type_id = (int) $hierarchy_type_id;

			if(isset($cache[$hierarchy_type_id]) && $ignoreMicroCache == false && cmsController::$IGNORE_MICROCACHE == false) return $cache[$hierarchy_type_id];

			$sql = "SELECT  id FROM cms3_object_types WHERE hierarchy_type_id = '{$hierarchy_type_id}' LIMIT 1";
			$result = l_mysql_query($sql);

			if($err = l_mysql_error()) {
				throw new coreException($err);
				return false;
			}

			if(list($id) = mysql_fetch_row($result)) {
				return $cache[$hierarchy_type_id] = $id;
			} else {
				return $cache[$hierarchy_type_id] = false;
			}
		}


		/**
			* Получить все тип данных
			* @return Array массив всех типов или false
		*/
		public function getAllTypes() {
			static $cache = Array();

               if(!empty($cache)) return $cache;

			$sql = "SELECT id, name, guid, is_locked, parent_id, is_guidable, is_public, hierarchy_type_id, sortable FROM cms3_object_types";
			$result = l_mysql_query($sql);

			if($err = l_mysql_error()) {
				throw new coreException($err);
				return false;
			}

			$res = Array();
			while($row = mysql_fetch_assoc($result)) {
				$row['name'] = $this->translateLabel($row['name']);

				$res [$row['id']] = $row;
			}

			$cache = $res;

			return $res;
		}


		/**
			* Получить тип данных, связанный с базовым типом (umiHierarchyType) $module/$method
			* @param String $module имя модуля
			* @param String $method имя метода
			* @return Integer id типа данных, либо false
		*/
		public function getBaseType($module, $method = "") {
			$hierarchy_type = umiHierarchyTypesCollection::getInstance()->getTypeByName($module, $method);

			if($hierarchy_type) {
				$hierarchy_type_id = $hierarchy_type->getId();
				$type_id = $this->getTypeByHierarchyTypeId($hierarchy_type_id);
				return (int) $type_id;
			} else {
				return false;
			}
		}

		public function clearCache() {
			$keys = array_keys($this->types);
			foreach($keys as $key) unset($this->types[$key]);
			$this->types = array();
		}
	}
?>
