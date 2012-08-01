<?php
/**
	* Этот класс служит для управления свойствами поля
*/
	class umiField extends umiEntinty implements iUmiEntinty, iUmiField {
		private $name, $title, $is_locked = false, $is_inheritable = false, $is_visible = true;
		private  $field_type_id, $guide_id, $isRequired, $restrictionId, $sortable = false;
		private $is_in_search = true, $is_in_filter = true, $tip = NULL, $is_system;
		protected $store_type = "field";


		/**
			* Получить имя поля (строковой идентификатор)
			* @return String имя поля (строковой идентификатор)
		*/
		public function getName() {
			return $this->name;
		}

		/**
			* Получить название поля
			* @return String название поля
		*/
		public function getTitle() {
			return $this->translateLabel($this->title);
		}

		/**
			* Узнать, заблокировано ли поле на изменение свойств
			* @return Boolean true если поле заблокировано
		*/
		public function getIsLocked() {
			return $this->is_locked;
		}

		/**
			* Узнать, наследуется ли значение поля. Зарезервировано, но пока не используется.
			* @return Boolean true если поле наследуется
		*/
		public function getIsInheritable() {
			return $this->is_inheritable;
		}

		/**
			* Узнать видимость поля для пользователя
			* @return Boolean true если поле видимое для пользователя
		*/
		public function getIsVisible() {
			return $this->is_visible;
		}

		/**
			* Получить id типа данных поля (см. класс umiFieldType)
			* @return Integer id типа данных поля
		*/
		public function getFieldTypeId() {
			return $this->field_type_id;
		}

		/**
			* Получить тип данных поля (экземпляр класса umiFieldType)
			* @return umiFieldType экземпляр класса umiFieldType, соответствующий полю, либо false в случае неудачи
		*/
		public function getFieldType() {
			return umiFieldTypesCollection::getInstance()->getFieldType($this->field_type_id);
		}

		/**
			* Получить id справочника, с которым связано поле (Справочник - это тип данных)
			* @return Integer id справочника, либо NULL, если полю не связано со справочником
		*/
		public function getGuideId() {
			return $this->guide_id;
		}

		/**
			* Узнать, индексируется ли поле для поиска
			* @return Boolean true если поле индексируется
		*/
		public function getIsInSearch() {
			return $this->is_in_search;
		}

		/**
			* Узнать, может ли поле учавствать в фильтрах
			* @return Boolean true если поле может учавствать в фильтрах
		*/
		public function getIsInFilter() {
			return $this->is_in_filter;
		}

		/**
			* Получить подсказку (короткую справку) для поля.
			* @return String подсказка (короткая справка) для поля
		*/
		public function getTip() {
			return $this->tip;
		}

		/**
		* Узнать, является ли поле системным
		*
		* @return Boolean
		*/
		public function getIsSystem() {
			return $this->is_system;
		}

		/**
		* Указать будет ли поле системным
		*
		* @param Boolean $isSystem true, если системное, иначе false
		*/
		public function setIsSystem($isSystem = false) {
			$this->is_system = (bool) $isSystem;
		}


		/**
			* Задать новое имя поля (строковой идентификатор).
			* Устанавливает флаг "Модифицирован".
			* @param String $name имя поля
		*/
		public function setName($name) {
			//$name = str_replace("-", "_", $name);
			$name = umiHierarchy::convertAltName($name, "_");
			$this->name = umiObjectProperty::filterInputString($name);
			if(!strlen($this->name)) $this->name = '_';
			$this->setIsUpdated();
		}


		/**
			* Задать новое описание поля.
			* Устанавливает флаг "Модифицирован".
			* @param String $title описание поля
		*/
		public function setTitle($title) {
			$title = $this->translateI18n($title, "field-");
			$this->title = umiObjectProperty::filterInputString($title);
			$this->setIsUpdated();
		}

		/**
			* Выставить полю статус "Заблокирован/Разблокирован".
			* Устанавливает флаг "Модифицирован".
			* @param Boolean $is_locked true, если заблокировано, иначе false
		*/
		public function setIsLocked($is_locked) {
			$this->is_locked = (bool) $is_locked;
			$this->setIsUpdated();
		}

		/**
			* Указать наследуется ли значение поля.
			* Зарезервировано, но пока не используется.
			* Устанавливает флаг "Модифицирован".
			* @param Boolean $is_inheritable зарезервировано, не используется
		*/
		public function setIsInheritable($is_inheritable) {
			$this->is_inheritable = (bool) $is_inheritable;
			$this->setIsUpdated();
		}

		/**
			* Указать видимо ли поле для пользователя.
			* Устанавливает флаг "Модифицирован".
			* @param Boolean $is_visible true, если видимо, иначе false
		*/
		public function setIsVisible($is_visible) {
			$this->is_visible = (bool) $is_visible;
			$this->setIsUpdated();
		}

		/**
			* Установить id типа поля (см. класс umiFieldType)
			* @param Integer $field_type_id идентификатор типа поля
		*/
		public function setFieldTypeId($field_type_id) {
			$this->field_type_id = (int) $field_type_id;
			$this->setIsUpdated();
			return true;
		}

		/**
			* Связать поле со указаным справочником (Справочник - это тип данных)
			* @param Integer $guide_id идентификтор справочника
		*/
		public function setGuideId($guide_id) {
			$this->guide_id = is_numeric($guide_id) ? (int) $guide_id : null;
			$this->setIsUpdated();
		}

		/**
			* Указать будет ли поле индексироваться для поиска.
			* Устанавливает флаг "Модифицирован".
			* @param Boolean $is_in_search true, если использовать для поиска, иначе false
		*/
		public function setIsInSearch($is_in_search) {
			$this->is_in_search = (bool) $is_in_search;
			$this->setIsUpdated();
		}

		/**
			* Указать может ли поле учавствовать в фильтрах.
			* Устанавливает флаг "Модифицирован".
			* @param Boolean $is_in_filter true, если использовать в фильтрах, иначе false
		*/
		public function setIsInFilter($is_in_filter) {
			$this->is_in_filter = (bool) $is_in_filter;
			$this->setIsUpdated();
		}

		/**
			* Установить новую подсказку (короткую справку) для поля.
			* Устанавливает флаг "Модифицирован".
			* @param String $tip подсказка
		*/
		public function setTip($tip) {
			$this->tip = umiObjectProperty::filterInputString($tip);
			$this->setIsUpdated();
		}

		/**
			* Проверить, является ли поле обязательным для заполнения
			* @return Boolean true, если поле обязательно для заполнения, иначе false
		*/
		public function getIsRequired() {
			return $this->isRequired;
		}

		/**
			* Установить, что поле является обязательным для заполнения
			* @param Boolean $isRequired true, если поле обязательно для заполнения, иначе false
		*/
		public function setIsRequired($isRequired = false) {
			$this->isRequired = (bool) $isRequired;
			$this->setIsUpdated();
		}

		/**
			* Получить идентификатор формата значение (restriction), по которому валидируется значение поля
			* @return Integer идентификатор формата значение (restriction) (класс baseRestriction и потомки)
		*/
		public function getRestrictionId() {
			return $this->restrictionId;
		}

		/**
			* Изменить id restriction'а, по которому валидируется значение поля
			* @param Integer|Boolean $restrictionId = false id рестрикшена, либо false
		*/
		public function setRestrictionId($restrictionId = false) {
			$this->restrictionId = (int) $restrictionId;
		}

		/**
			* Проверить, является ли поле сортируемым
			* @return Boolean состояние сортировки
		*/
		public function getIsSortable() {
			return $this->sortable;
		}

		/**
			* Установить поле сортируемым
			* @param Boolean $sortable = false флаг сортировки
		*/
		public function setIsSortable($sortable = false) {
			$this->sortable = (bool) $sortable;
		}

		/**
		* Получить идентификатор типа данных
		* @return String идентификатор типа данных
		*/
		public function getDataType() {
			$fieldTypes = umiFieldTypesCollection::getInstance();
			return $fieldTypes->getFieldType($this->field_type_id)->getDataType();
		}

		/**
			* Загружает необходимые данные для формирования объекта umiField из БД.
			* @return Boolean true в случае успеха
		*/
		protected function loadInfo($row = false) {
			if($row === false) {
				$sql = "SELECT id, name, title, is_locked, is_inheritable, is_visible, field_type_id, guide_id, in_search, in_filter, tip, is_required, sortable, is_system, restriction_id FROM cms3_object_fields WHERE id = '{$this->id}'";

				$result = l_mysql_query($sql);

				$row = mysql_fetch_row($result);
			}

			if(list($id, $name, $title, $is_locked, $is_inheritable, $is_visible, $field_type_id, $guide_id, $in_search, $in_filter, $tip, $is_required, $sortable, $is_system, $restrictionId) = $row) {
				$this->name = $name;
				$this->title = $title;
				$this->is_locked = (bool) $is_locked;
				$this->is_inheritable = (bool) $is_inheritable;
				$this->is_visible = (bool) $is_visible;
				$this->field_type_id = (int) $field_type_id;
				$this->guide_id = $guide_id;
				$this->is_in_search = (bool) $in_search;
				$this->is_in_filter = (bool) $in_filter;
				$this->tip = (string) $tip;
				$this->isRequired = (bool) $is_required;
				$this->sortable = (bool) $sortable;
				$this->is_system = (bool) $is_system;
				$this->restrictionId = (int) $restrictionId;
			} else {
				return false;
			}
		}

		/**
			* Сохранить все модификации объекта в БД.
			* @return Boolean true в случае успеха
		*/
		protected function save() {
			$name = l_mysql_real_escape_string($this->name);
			$title = l_mysql_real_escape_string($this->title);
			$is_locked = (int) $this->is_locked;
			$is_inheritable = (int) $this->is_inheritable;
			$is_visible = (int) $this->is_visible;
			$field_type_id = (int) $this->field_type_id;
			$guide_id = $this->guide_id ? (int) $this->guide_id : 'NULL';
			$in_search = (int) $this->is_in_search;
			$in_filter = (int) $this->is_in_filter;
			$tip = l_mysql_real_escape_string($this->tip);
			$isRequired = (int) $this->isRequired;
			$sortable = (int) $this->sortable;
			$restrictionId = (int) $this->restrictionId;
			$is_system = (int) $this->is_system;
			$restrictionSql = $restrictionId ? ", restriction_id = '{$restrictionId}'" : ", restriction_id = NULL";

			$sql = "UPDATE cms3_object_fields SET name = '{$name}', title = '{$title}', is_locked = '{$is_locked}', is_inheritable = '{$is_inheritable}', is_visible = '{$is_visible}', field_type_id = '{$field_type_id}', guide_id = {$guide_id}, in_search = '{$in_search}', in_filter = '{$in_filter}', tip = '{$tip}', is_required = '{$isRequired}', sortable = '{$sortable}', is_system = '{$is_system}' {$restrictionSql} WHERE id = '{$this->id}'";

			l_mysql_query($sql);
			cacheFrontend::getInstance()->flush();

			return true;
		}
	}
?>