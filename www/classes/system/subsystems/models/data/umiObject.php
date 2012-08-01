<?php
/**
 * Общий класс для взаимодействия с объектами системы.
 * @author lyxsus <sa@umisoft.ru>
 */
	class umiObject extends umiEntinty implements iUmiEntinty, iUmiObject {
		private $name, $type_id, $is_locked, $owner_id = false,
			$type, $properties = Array(), $prop_groups = Array(), $guid = null, $type_guid = null;
		protected $store_type = "object";

		/**
			* Получить название объекта
			* @param Boolean $translate_ignored = false
			* @return String название объекта
		*/
		public function getName($translate_ignored = false) {
			return $translate_ignored ? $this->name : $this->translateLabel($this->name);
		}

		/**
			* Получить id типа объекта
			* @return Integer id типа объекта (для класса umiObjectType)
		*/
		public function getTypeId() {
			return $this->type_id;
		}

		/**
			* Получить guid типа объекта
			* @return String guid типа объекта (для класса umiObjectType)
		*/
		public function getTypeGUID(){
			return $this->type_guid;
		}

		public function getType() {
			if(!$this->type) {
				$this->loadType();
			}
			return $this->type;
		}

		/**
			* Узнать, заблокирован ли объект. Метод зарезервирован, но не используется. Предполагается, что этот флаг будет блокировать любое изменение объекта
			* @return Boolean true если обект заблокирован
		*/
		public function getIsLocked() {
			return $this->is_locked;
		}

		/**
			* Задать новое название объекта. Устанавливает флаг "Модифицирован".
			* @param String $name
		*/
		public function setName($name) {

			$not_allowed_symbols = array(1, 2, 3, 4, 5, 6, 7, 8, 11, 12, 14, 15, 16, 17, 18, 19, 20,21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31);
			$pattern ='';
			foreach ($not_allowed_symbols as $symbol) {
				$pattern = $pattern . chr($symbol);
			}
			$name = preg_replace("/[" . $pattern . "]/isu", "", $name);

			$name = $this->translateI18n($name, "object-");
			$this->name = $name;
			$this->setIsUpdated();
		}

		/**
			* Установить новый id типа данных (класс umiObjectType) для объекта.
			* Используйте этот метод осторожно, потому что он просто переключает id типа данных.
			* Уже заполненные значения остануться в БД, но станут недоступны через API, если не переключить тип данных для объекта назад.
			* Устанавливает флаг "Модифицирован".
			* @return Boolean true всегда
		*/
		public function setTypeId($type_id) {
			if ($this->type_id !== $type_id) {
				$this->type_id = $type_id;
				$this->setIsUpdated();
			}
			return true;
		}

		/**
			* Выставить объекту статус "Заблокирован". Этот метод зарезервирован, но в настоящее время не используется.
		*/
		public function setIsLocked($is_locked) {
			if ($this->is_locked !== ((bool) $is_locked)) {
				$this->is_locked = (bool) $is_locked;
				$this->setIsUpdated();
			}
		}

		/**
			* Установить id владельца объекта. Это означает, что пользователь с id $ownerId полностью владеет этим объектом:
			* создал его, может модифицировать, либо удалить.
			* @param Integer $ownerId id нового владельца. Обязательно действительный id объекта (каждый пользователь это объект в umi)
			* @return Boolean true в случае успеха, false если $ownerId не является нормальным id для umiObject
		*/
		public function setOwnerId($ownerId) {
			if(!is_null($ownerId) and umiObjectsCollection::getInstance()->isExists($ownerId)) {
				if ($this->owner_id !== $ownerId) {
					$this->owner_id = $ownerId;
					$this->setIsUpdated();
				}
				return true;
			}
			else {
				if (!is_null($this->owner_id)) {
					$this->owner_id = NULL;
					$this->setIsUpdated();
				}
				return false;
			}
		}

		/**
			* Получить id пользователя, который владеет этим объектом
			* @return Integer id пользователя. Всегда действительный id для umiObject или NULL если не задан.
		*/
		public function getOwnerId() {
			return $this->owner_id;
		}

		/**
			* Проверить, заполены ли все необходимые поля у объекта
			* @return Boolean
		*/
		public function isFilled() {
			$fields = $this->type->getAllFields();
			foreach($fields as $field)
				if($field->getIsRequired() && is_null($this->getValue($field->getName())))
						return false;
			return true;
		}

		/**
			* Сохранить все модификации объекта в БД. Вызывает метод commit() на каждом загруженом свойстве (umiObjectProperty)
		*/
		protected function save() {
			if ($this->is_updated) {

				$name = umiObjectProperty::filterInputString($this->name);
				$guid = umiObjectProperty::filterInputString($this->guid);
				$type_id = (int) $this->type_id;
				$is_locked = (int) $this->is_locked;
				$owner_id = (int) $this->owner_id;

				$sql = "START TRANSACTION /* Updating object #{$this->id} info */";
				$result = l_mysql_query($sql);

				if($err = l_mysql_error()) {
					throw new coreException($err);
				}

				$nameSql = $name ? "'{$name}'" : "NULL";
				$sql = "UPDATE cms3_objects SET name = {$nameSql}, type_id = '{$type_id}', is_locked = '{$is_locked}', owner_id = '{$owner_id}', guid = '{$guid}' WHERE id = '{$this->id}'";
				l_mysql_query($sql);
				if($err = l_mysql_error()) {
					throw new coreException($err);
				}

				foreach($this->properties as $prop) {
					if(is_object($prop)) $prop->commit();
				}

				$sql = "COMMIT";
				l_mysql_query($sql);

				if($err = l_mysql_error()) {
					throw new coreException($err);
				}

				$this->setIsUpdated(false);

			}
			return true;
		}

		public function __construct($id, $row = false) {
			parent::__construct($id, $row);
		}

		/**
			* Загружает необходимые данные для формирования объекта. Этот метод не подгружает значения свойств.
			* Значения свойств запрашиваются по требованию
			* В случае нарушения целостности БД, когда с загружаемым объектом в базе не связан ни один тип данных, объект удаляется.
			* @return Boolean true в случае успеха
		*/
		protected function loadInfo($row = false) {

			if($row === false || count($row) < 6) {
				$sql = "SELECT o.name, o.type_id, o.is_locked, o.owner_id, o.guid as `guid`, t.guid as `type_guid` FROM cms3_objects `o`, cms3_object_types `t` WHERE o.id = '{$this->id}' and o.type_id = t.id";
				$result = l_mysql_query($sql, true);

				if($err = l_mysql_error()) {
					cacheFrontend::getInstance()->del($object->getId(), "object");
					throw new coreException($err);
					return false;
				}
				$row = mysql_fetch_row($result);
				if(!$row) {
					throw new coreException("Object #{$this->id} doesn't exists");
					return false;
				}
			}
//var_dump($sql, $this->id, $row);
			list($name, $type_id, $is_locked, $owner_id, $guid, $type_guid) = $row;
			if(!$type_id) {	//Foregin keys check failed, or manual queries made. Delete this object.
				umiObjectsCollection::getInstance()->delObject($this->id);
				return false;
			}

			$this->name = $name;
			$this->type_id = (int) $type_id;
			$this->is_locked = (bool) $is_locked;
			$this->owner_id = (int) $owner_id;
			$this->guid = $guid;
			$this->type_guid = $type_guid;
			return $this->loadType();
		}


		/**
			* Загрузить тип данных (класс umiObjectType), который описывает этот объект
		*/
		private function loadType() {
			$type = umiObjectTypesCollection::getInstance()->getType($this->type_id);

			if(!$type) {
				throw new coreException("Can't load type in object's init");
			}

			$this->type = $type;
			return $this->loadProperties();
		}

		/**
			* Подготовить внутреннеие массивы для свойств и групп свойств на основе структуры типа данных, с которым связан объект
		*/
		private function loadProperties() {
			$type = $this->type;
			$groups_list = $type->getFieldsGroupsList();
			foreach($groups_list as $group) {
				if($group->getIsActive() == false) continue;

				$fields = $group->getFields();

				$this->prop_groups[$group->getId()] = Array();

				foreach($fields as $field) {
					$this->properties[$field->getId()] = $field->getName();
					$this->prop_groups[$group->getId()][] = $field->getId();
				}
			}
		}

		/**
			* Получить свойство объекта по его строковому идентификатору
			* @param String $prop_name строковой идентификатор свойства
			* @return umiObjectProperty или NULL в случае неудачи
		*/
		public function getPropByName($prop_name) {
			$prop_name = strtolower($prop_name);

			foreach($this->properties as $field_id => $prop) {
				if(is_object($prop)) {
					if($prop->getName() == $prop_name) {
						return $prop;
					}
				} else {
					if(strtolower($prop) == $prop_name) {
						$prop = cacheFrontend::getInstance()->load($this->id . "." . $field_id, "property");
						if($prop instanceof umiObjectProperty == false) {
							$prop = umiObjectProperty::getProperty($this->id, $field_id, $this->type_id);
							cacheFrontend::getInstance()->save($prop, "property");
						}
						$this->properties[$field_id] = $prop;
						return $prop;
					}
				}
			}
			return NULL;
		}

		/**
			* Получить свойство объекта по его числовому идентификатору (просто id)
			* @param Integer $field_id id поля
			* @return umiObjectProperty или NULL в случае неудачи
		*/
		public function getPropById($field_id) {
			if(!$this->isPropertyExists($field_id)) {
				return NULL;
			} else {
				if(!is_object($this->properties)) {
					$this->properties[$field_id] = umiObjectProperty::getProperty($this->id, $field_id, $this->type_id);
				}
				return $this->properties[$field_id];
			}
		}

		/**
			* Узнать, существует ли свойство с id $field_id
			* @param Integer $field_id id поля
			* @return Boolean true, если поле существует
		*/
		public function isPropertyExists($field_id) {
			return (bool) array_key_exists($field_id, $this->properties);
		}

		/**
			* Узнать, существует ли группа полей с id $prop_group_id у объекта
			* @param Integer $prop_group_id id группы полей
			* @return Boolean true, если группа существует
		*/
		public function isPropGroupExists($prop_group_id) {
			return (bool) array_key_exists($prop_group_id, $this->prop_groups);
		}

		/**
			* Получить id группы полей по ее строковому идентификатору
			* @param String $prop_group_name Строковой идентификатор группы полей
			* @return Integer id группы полей, либо false, если такой группы не существует
		*/
		public function getPropGroupId($prop_group_name) {
			$groups_list = $this->getType()->getFieldsGroupsList();

			foreach($groups_list as $group) {
				if($group->getName() == $prop_group_name) {
					return $group->getId();
				}
			}
			return false;
		}

		/**
			* Получить группу полей по ее строковому идентификатору
			* @param String $prop_group_name Строковой идентификатор группы полей
			* @return umiFieldsGroup, либо false, если такой группы не существует
		*/
		public function getPropGroupByName($prop_group_name) {
			if($group_id = $this->getPropGroupId($prop_group_name)) {
				return $this->getPropGroupById($group_id);
			} else {
				return false;
			}
		}

		/**
			* Получить группу полей по ее id
			* @param Integer $prop_group_id id группы полей
			* @return umiFieldsGroup, либо false, если такой группы не существует
		*/
		public function getPropGroupById($prop_group_id) {
			if($this->isPropGroupExists($prop_group_id)) {
				return $this->prop_groups[$prop_group_id];
			} else {
				return false;
			}
		}


		/**
			* Получить значение свойства $prop_name объекта
			* @param String $prop_name строковой идентификатор поля
			* @param Array $params = NULL дополнительные параметры (обычно не используется)
			* @return Mixed значение поле. Тип значения зависит от типа поля. Вернет false, если свойства не существует.
		*/
		public function getValue($prop_name, $params = NULL) {
			if($prop = $this->getPropByName($prop_name)) {
				return $prop->getValue($params);
			} else {
				return false;
			}
		}

		/**
			* Установить значение свойства с $prop_name данными из $prop_value. Устанавливает флаг "Модифицирован".
			* Значение в БД изменится только когда на объекте будет вызван темод commit(), либо в деструкторе объекта
			* @param String $prop_name строковой идентификатор поля
			* @param Mixed $prop_value новое значение для поля. Зависит от типа поля
			* @return Boolean true если прошло успешно
		*/
		public function setValue($prop_name, $prop_value) {
			if($prop = $this->getPropByName($prop_name)) {
				$this->setIsUpdated();
				return $prop->setValue($prop_value);
			} else {
				return false;
			}
		}

		/*
			* Сохранить все значения в базу, если объект модификирован
		*/
		public function commit() {
			l_mysql_query("START TRANSACTION /* Saving object {$this->id} */");
			$USE_TRANSACTIONS = umiObjectProperty::$USE_TRANSACTIONS;
			umiObjectProperty::$USE_TRANSACTIONS = false;

			if($this->checkSelf()) {
				foreach($this->properties as $prop) {
					if(is_object($prop)) {
						$prop->commit();
					}
				}
			}

			parent::commit();
			l_mysql_query("COMMIT");
			umiObjectProperty::$USE_TRANSACTIONS = $USE_TRANSACTIONS;
		}

		public function checkSelf() {
			static $res;
			if($res !== null) {
				return $res;
			}

			if(!cacheFrontend::getInstance()->getIsConnected()) {
				return $res = true;
			}

			$sql = "SELECT id FROM cms3_objects WHERE id = '{$this->id}'";
			$result = l_mysql_query($sql);
			if($err = l_mysql_error()) {
				throw new coreException($err);
			}
			$res = (bool) mysql_num_rows($result);
			if(!$res) {
				cacheFrontend::getInstance()->flush();
			}
			return $res;
		}


		/**
			* Вручную установить флаг "Модифицирован"
		*/
		public function setIsUpdated($isUpdated = true) {
			umiObjectsCollection::getInstance()->addUpdatedObjectId($this->id);
			return parent::setIsUpdated($isUpdated);
		}

		/**
			* Удалить объект
		*/
		public function delete() {
			umiObjectsCollection::getInstance()->delObject($this->id);
		}

		public function __get($varName) {
			switch($varName) {
				case "id":		return $this->id;
				case "name":	return $this->getName();
				case "ownerId":	return $this->getOwnerId();
				case "typeId":	return $this->getTypeId();
				case "GUID":	return $this->getGUID();
				case "typeGUID":return $this->getTypeGUID();
				case "xlink":	return 'uobject://' . $this->id;

				default:		return $this->getValue($varName);
			}
		}

		public function __set($varName, $value) {
			switch($varName) {
				case "id":		throw new coreException("Object id could not be changed");
				case "name":	return $this->setName($value);
				case "ownerId":	return $this->setOwnerId($value);

				default:		return $this->setValue($varName, $value);
			}
		}

		public function beforeSerialize($reget = false) {
			static $types = array();
			if ($reget && isset($types[$this->type_id])) {
				$this->type = $types[$this->type_id];
			}
			else {
				$types[$this->type_id] = $this->type;
				$this->type = null;
			}
		}

		public function afterSerialize() {
			$this->beforeSerialize(true);
		}

		public function afterUnSerialize() {
			$this->getType();
		}

		public function getModule() {
			return $this->type->getModule();
		}

		public function getMethod() {
			return $this->type->getMethod();
		}

		/**
		* Получить GUID
		* @return string GUID
		*/
		public function getGUID() {
			return $this->guid;
		}

		/**
		* Установить GUID
		* @deprecated
		* @throws coreException если GUID уже используется
		* @param string $guid
		*/
		public function setGUID($guid) {
			$id = umiObjectsCollection::getInstance()->getObjectIdByGUID($guid);
			if($id && $id != $this->id) {
				throw new coreException("GUID {$guid} already in use");
			}
			$this->guid = $guid;
			$this->setIsUpdated();
		}
	}
?>