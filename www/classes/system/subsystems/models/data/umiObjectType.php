<?php
/**
	* Этот класс служит для управления свойствами типа данных
*/
	class umiObjectType extends umiEntinty implements iUmiEntinty, iUmiObjectType {
		private $name, $parent_id, $is_locked = false;
		private $field_groups = Array(), $field_all_groups = Array();
		private $is_guidable = false, $is_public = false, $hierarchy_type_id;
		private $sortable = false;
		private $guid = null;
		protected $store_type = "object_type";

		/**
			* Получить название типа.
			* @return String название типа
		*/
		public function getName() {
			return $this->translateLabel($this->name);
		}

		/**
			* Изменить название типа.
			* @param String $name новое название типа данных
		*/
		public function setName($name) {
			$name = $this->translateI18n($name, "object-type-");
			$this->name = $name;
			$this->setIsUpdated();
		}

		/**
			* Узнать, заблокирован ли тип данных. Если тип данных заблокирован, то его нельзя удалить из системы.
			* @return Boolean true если тип данных заблокирован
		*/
		public function getIsLocked() {
			return $this->is_locked;
		}

		/**
			* Изменить флаг блокировки у типа данных. Если тип данных заблокирован, его нельзя будет удалить.
			* @param Boolean $is_locked флаг блокировки
		*/
		public function setIsLocked($is_locked) {
			$this->is_locked = (bool) $is_locked;
			$this->setIsUpdated();
		}

		/**
			* Получить id родительского типа данных, от которого унаследованы группы полей и поля
			* @return Integer id родительского типа данных
		*/
		public function getParentId() {
			return $this->parent_id;
		}

		/**
			* Узнать, помечен ли тип данных как справочник.
			* @return Boolean true, если тип данных помечен как справочник
		*/
		public function getIsGuidable() {
			return $this->is_guidable;
		}

		/**
			* Изменить флаг "Справочник" у типа данных.
			* @param Boolean $is_guidable новое значение флага "Справочник"
		*/
		public function setIsGuidable($is_guidable) {
			$this->is_guidable = (bool) $is_guidable;
			$this->setIsUpdated();
		}

		/**
			* Установить флаг "Общедоступный" для справочника. Не имеет значение, если тип данных не является справочником.
			* @return Boolean true если тип данных общедоступен
		*/
		public function getIsPublic() {
			return $this->is_public;
		}

		/**
			* Изменить значение флага "Общедоступен" для типа данных. Не имеет значения, если тип данных не является справочником.
			* @param Boolean $is_public новое значение флага "Общедоступен"
		*/
		public function setIsPublic($is_public) {
			$this->is_public = (bool) $is_public;
			$this->setIsUpdated();
		}

		/**
			* Получить id базового типа, к которому привязан тип данных (класс umiHierarchyType).
			* @return Integer id базового типа данных (класс umiHierarchyType)
		*/
		public function getHierarchyTypeId() {
			return $this->hierarchy_type_id;
		}

		/**
			* Проверить, являются ли объекты этого типа сортируемыми
			* @return Boolean состояние сортировки
		*/
		public function getIsSortable() {
			return $this->sortable;
		}

		/**
			* Установить тип сортируемым
			* @param Boolean $sortable = false флаг сортировки
		*/
		public function setIsSortable($sortable = false) {
			$this->sortable = (bool) $sortable;
		}

		/**
			* Изменить базовый тип (класс umiHierarchyType), к которому привязан тип данных.
			* @param Integer $hierarchy_type_id новый id базового типа (класс umiHierarchyType)
		*/
		public function setHierarchyTypeId($hierarchy_type_id) {
			$this->hierarchy_type_id = (int) $hierarchy_type_id;
			$this->setIsUpdated();
		}

		/**
			* Добавить в тип данных новую группу полей (класс umiFieldsGroup)
			* @param String $name - строковой идентификатор группы полей
			* @param String $title - название группы полей
			* @param Boolean $is_active=true флаг активности группы полей (всегда true)
			* @param Boolean $is_visible=true видимость группы полей
			* @return Integer id созданной группы полей
		*/
		public function addFieldsGroup($name, $title, $is_active = true, $is_visible = true) {
			if($group = $this->getFieldsGroupByName($name)) {
				return $group->getId();
			}

			$sql = "SELECT MAX(ord) FROM cms3_object_field_groups WHERE type_id = '{$this->id}'";
			$result = l_mysql_query($sql);

			if($err = l_mysql_error()) {
				throw new coreException($err);
				return false;
			}

			if(list($ord) = mysql_fetch_row($result)) {
				$ord = ((int) $ord) + 5;
			} else {
				$ord = 1;
			}

			$sql = "INSERT INTO cms3_object_field_groups (type_id, ord) VALUES('{$this->id}', '{$ord}')";
			l_mysql_query($sql);

			if($err = l_mysql_error()) {
				throw new coreException($err);
				return false;
			}

			$field_group_id = l_mysql_insert_id();

			$field_group = new umiFieldsGroup($field_group_id);
			$field_group->setName($name);
			$field_group->setTitle($title);
			$field_group->setIsActive($is_active);
			$field_group->setIsVisible($is_visible);
			$field_group->commit();

			$this->field_groups[$field_group_id] = $field_group;
			$this->field_all_groups[$field_group_id] = $field_group;


			$child_types = umiObjectTypesCollection::getInstance()->getSubTypesList($this->id);
			$sz = sizeof($child_types);
			for($i = 0; $i < $sz; $i++) {
				$child_type_id = $child_types[$i];

				if($type = umiObjectTypesCollection::getInstance()->getType($child_type_id)) {
					$type->addFieldsGroup($name, $title, $is_active, $is_visible);
				} else {
					throw new coreException("Can't load object type #{$child_type_id}");
				}
			}

			cacheFrontend::getInstance()->flush();

			return $field_group_id;
		}

		/**
			* Удалить группу полей (класс umiFieldsGroup).
			* @param Integer $field_group_id id группы, которую необходимо удалить
			* @return Boolean true, если удаление прошло успешно
		*/
		public function delFieldsGroup($field_group_id) {
			if($this->isFieldsGroupExists($field_group_id)) {
				$field_group_id = (int) $field_group_id;
				$sql = "DELETE FROM cms3_object_field_groups WHERE id = '{$field_group_id}'";
				l_mysql_query($sql);

				if($err = l_mysql_error()) {
					throw new coreException($err);
					return false;
				}

				unset($this->field_groups[$field_group_id]);

				cacheFrontend::getInstance()->flush();
				return true;

			} else {
				return false;
			}
		}

		/**
			* Получить группу полей (класс umiFieldsGroup) по ее строковому идентификатору
			* @param String $field_group_name строковой идентификатор группы полей
			* @param String $allow_disabled разрешить получать не активные группы
			* @return umiFieldsGroup|Boolean группы полей (экземпляр класса umiFieldsGroup), либо false
		*/
		public function getFieldsGroupByName($field_group_name, $allow_disabled = false) {
			$groups = $this->getFieldsGroupsList($allow_disabled);
			foreach($groups as $group_id => $group) {
				if($group->getName()  == $field_group_name) {
					return $group;
				}
			}
			return false;
		}

		/**
			* Получить группу полей (класс umiFieldsGroup) по ее id
			* @param Integer $field_group_id id группы полей
			* @param Boolean $ignore_is_active  false, если поиск ведется только среди активных групп
			* @return umiFieldsGroup|Boolean группы полей (экземпляр класса umiFieldsGroup), либо false
		*/
		public function getFieldsGroup($field_group_id, $ignore_is_active = false) {
			if($this->isFieldsGroupExists($field_group_id)) {
				if ($ignore_is_active) {
					return $this->field_all_groups[$field_group_id];
				} else {
					if (array_key_exists($field_group_id, $this->field_groups)) {
						return $this->field_groups[$field_group_id];
					} else {
						return false;
					}
				}
			} else {
				return false;
			}
		}

		/**
			* Получить список всех групп полей у типа данных
			* @param Boolean $showDisabledGroups = false включить в результат неактивные группы полей
			* @return Array массив состоящий из экземпляров класса umiFieldsGroup
		*/
		public function getFieldsGroupsList($showDisabledGroups = false) {
			return $showDisabledGroups ? $this->field_all_groups : $this->field_groups;
		}


		/**
			* Проверить, существует ли у типа данных группа полей с id $field_group_id
			* @param Integer $field_group_id id группы полей
			* @return Boolean true, если группа полей существует у этого типа данных
		*/
		private function isFieldsGroupExists($field_group_id) {
			if(!$field_group_id) {
				return false;
			} else {
				return (bool) array_key_exists($field_group_id, $this->field_all_groups);
			}
		}

		/**
			* Загрузить информацию о типе данных из БД
		*/
		protected function loadInfo() {
			$sql = "SELECT name, parent_id, is_locked, is_guidable, is_public, hierarchy_type_id, sortable, guid FROM cms3_object_types WHERE id = '{$this->id}'";
			$result = l_mysql_query($sql);

			if(list($name, $parent_id, $is_locked, $is_guidable, $is_public, $hierarchy_type_id, $sortable, $guid) = mysql_fetch_row($result)) {
				$this->name = $name;
				$this->parent_id = (int) $parent_id;
				$this->is_locked = (bool) $is_locked;
				$this->is_guidable = (bool) $is_guidable;
				$this->is_public = (bool) $is_public;
				$this->hierarchy_type_id = (int) $hierarchy_type_id;
				$this->sortable = (bool) $sortable;
				$this->guid = $guid;

				return $this->loadFieldsGroups();
			} else {
				return false;
			}
		}

		/**
			* Загрузить группы полей и поля для типа данных из БД
			* @return Boolean true, если не возникло ошибок
		*/
		private function loadFieldsGroups() {
			$sql = <<<SQL
SELECT 
   ofg.id as groupId, cof.id, cof.name, cof.title, cof.is_locked, cof.is_inheritable, cof.is_visible, cof.field_type_id, cof.guide_id, cof.in_search, cof.in_filter, cof.tip, cof.is_required, cof.sortable, cof.is_system, cof.restriction_id
      FROM cms3_object_field_groups ofg, cms3_fields_controller cfc, cms3_object_fields cof
         WHERE ofg.type_id = '{$this->id}' AND cfc.group_id = ofg.id AND cof.id = cfc.field_id
            ORDER BY ofg.ord ASC, cfc.ord ASC
SQL;

			$result = l_mysql_query($sql);
			$fields = Array();
			while(list($group_id, $id, $name, $title, $is_locked, $is_inheritable, $is_visible, $field_type_id, $guide_id, $in_search, $in_filter, $tip, $is_required, $is_system, $sortable, $restriction_id) = mysql_fetch_row($result)) {
				if(!isset($fields[$group_id]) || !is_array($fields[$group_id])) {
					$fields[$group_id] = Array();
				}
				$fields[$group_id][] = Array($id, $name, $title, $is_locked, $is_inheritable, $is_visible, $field_type_id, $guide_id, $in_search, $in_filter, $tip, $is_required, $is_system, $sortable, $restriction_id);
			}

			$sql = "SELECT id, name, title, type_id, is_active, is_visible, is_locked, ord FROM cms3_object_field_groups WHERE type_id = '{$this->id}' ORDER BY ord ASC";
			$result = l_mysql_query($sql);

			while(list($field_group_id,,,,$isActive) = $row = mysql_fetch_row($result)) {
				$field_group = new umiFieldsGroup($field_group_id, $row);

				if(!isset($fields[$field_group_id])) {
					$fields[$field_group_id] = Array();
				}
				$field_group->loadFields($fields[$field_group_id]);
				$this->field_all_groups[$field_group_id] = $field_group;
				if($isActive) {
					$this->field_groups[$field_group_id] = $field_group;
				}
			}
			return true;
		}

		/**
			* Сохранить в БД внесенные изменения
		*/
		protected function save() {
			$name = umiObjectProperty::filterInputString($this->name);
			$guid = umiObjectProperty::filterInputString($this->guid);
			$parent_id = (int) $this->parent_id;
			$is_locked = (int) $this->is_locked;
			$is_guidable = (int) $this->is_guidable;
			$is_public = (int) $this->is_public;
			$hierarchy_type_id = (int) $this->hierarchy_type_id;
			$sortable = (int) $this->sortable;

			$sql = "UPDATE cms3_object_types SET name = '{$name}', guid = '{$guid}', parent_id = '{$parent_id}', is_locked = '{$is_locked}', is_guidable = '{$is_guidable}', is_public = '{$is_public}', hierarchy_type_id = '{$hierarchy_type_id}', sortable = '{$sortable}' WHERE id = '{$this->id}'";
			$result = l_mysql_query($sql);

			cacheFrontend::getInstance()->flush();

			if($err = l_mysql_error()) {
				throw new coreException($err);
				return false;
			} else {
				return false;
			}
		}

		/**
			* Изменить порядок следования группы полей
			* @param Integer $group_id идентификатор группы полей, порядоковый номер которой мы хотим изменить
			* @param Integer $neword новый порядковый номер группы полей
			* @param Boolean $is_last хотим ли сделать группу полей последней в списке
			* @return Boolean true, если порядок успешно изменен, false в противном случае
		*/
		public function setFieldGroupOrd($group_id, $neword, $is_last) {
			$neword = (int) $neword;
			$group_id = (int) $group_id;

			if(!$is_last) {
				$sql = "SELECT type_id FROM cms3_object_field_groups WHERE id = '{$group_id}'";
				$result = l_mysql_query($sql);

				if($err = l_mysql_error()) {
					throw new coreException($err);
					return false;
				}


				if(!(list($type_id) = mysql_fetch_row($result))) {
					return false;
				}

				$sql = "UPDATE cms3_object_field_groups SET ord = (ord + 1) WHERE type_id = '{$type_id}' AND ord >= '{$neword}'";
				l_mysql_query($sql);

				if($err = l_mysql_error()) {
					throw new coreException($err);
					return false;
				}
			}

			$sql = "UPDATE cms3_object_field_groups SET ord = '{$neword}' WHERE id = '{$group_id}'";
			l_mysql_query($sql);

			cacheFrontend::getInstance()->flush();

			if($err = l_mysql_error()) {
				throw new coreException($err);
				return false;
			}
			return true;
		}

		/**
			* Получить список всех полей типа данных
			* @param Boolean $returnOnlyVisibleFields=false если флаг установлен true, то метод вернет только видимые поля
			* @return Array массив, состоящий из экземпляров класса umiField
		*/
		public function getAllFields($returnOnlyVisibleFields = false) {
			$fields = Array();

			$groups = $this->getFieldsGroupsList();
			foreach($groups as $group) {
				if($returnOnlyVisibleFields) {
					if(!$group->getIsVisible()) {
						continue;
					}
				}

				$fields = array_merge($fields, $group->getFields());
			}

			return $fields;
		}

		/**
			* Получить id поля по его строковому идентификатору
			* @param String $field_name строковой идентификатор поля
			* @param Boolean $ignoreInactiveGroups true, если нужно найти поле только в активных группах
			* @return Integer|Boolean id поля, либо false если такого поля не существует
		*/
		public function getFieldId($field_name, $ignoreInactiveGroups = true) {
			$groups = $this->getFieldsGroupsList(!$ignoreInactiveGroups);
			foreach($groups as $group_id => $group) {

				$fields = $group->getFields();

				foreach($fields as $field_id => $field) {
					if($field->getName() == $field_name) {
						return $field->getId();
					}
				}
			}
			return false;
		}

		/**
			* Получить название модуля иерархического типа, если такой есть у этого типа данных
			* @return String название модуля
		*/
		public function getModule() {
			$hierarchyTypeId = $this->getHierarchyTypeId();
			$hierarchyType = umiHierarchyTypesCollection::getInstance()->getType($hierarchyTypeId);
			if($hierarchyType instanceof umiHierarchyType) {
				return $hierarchyType->getName();
			} else {
				return false;
			}
		}

		/**
			* Получить название метода иерархического типа, если такой есть у этого типа данных
			* @return String название метода
		*/
		public function getMethod() {
			$hierarchyTypeId = $this->getHierarchyTypeId();
			$hierarchyType = umiHierarchyTypesCollection::getInstance()->getType($hierarchyTypeId);
			if($hierarchyType instanceof umiHierarchyType) {
				return $hierarchyType->getExt();
			} else {
				return false;
			}
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
			$id = umiObjectTypesCollection::getInstance()->getTypeIdByGUID($guid);
			if($id && $id != $this->id) {
				throw new coreException("GUID {$guid} already in use");
			}
			$this->guid = $guid;
			$this->setIsUpdated();
		}
	};
?>