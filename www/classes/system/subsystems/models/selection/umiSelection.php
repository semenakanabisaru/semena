<?php
/**
	* Класс, который предоставляет средства для создания шаблонов выборок данных из базы данных.
*/
	class umiSelection implements iUmiSelection {
		private	$order = Array(),
			$limit = Array(),
			$object_type = Array(),
			$element_type = Array(),
			$props = Array(),
			$hierarchy = Array(),
			$perms = Array(),
			$names = Array(),
			$active = Array(),
			$owner = Array(),
			$objects_ids = Array(),
			$elements_ids = Array(),

			$is_order = false,  $is_limit = false, $is_object_type = false, $is_element_type = false, $is_props = false, $is_hierarchy = false, $is_permissions = false, $is_forced = false, $is_names = false, $is_active = false,
			$condition_mode_or = false, $is_owner = false,
			$is_objects_ids = false, $is_elements_ids = false,
			$is_domain_ignored = false, $isDomainIgnored = false, $isLangIgnored = false, $langId = false, $domainId = false,
			$permissionsLevel = 1,
			$searchStrings = Array();
			
		public	$result = false, $count = false, $switchIllegalBetween = true;

		// ========

		public $optimize_root_search_query = false;
		public $sql_part__hierarchy = "";
		public $sql_part__element_type = "";
		public $sql_part__owner = "";
		public $sql_part__objects = "";
		public $sql_part__elements = "";
		public $sql_part__perms = "";
		public $sql_part__perms_tables = "";
		public $sql_part__content_tables = "";
		public $sql_part__object_type = "";
		public $sql_part__props_and_names = "";
		public $sql_part__lang_cond = "";
		public $sql_part__domain_cond = "";
		public $sql_part__unactive_cond = "";

		public $sql_cond__total_joins = 0;
		public $sql_cond__content_tables_loaded = 0;
		public $sql_cond__need_content = false;
		public $sql_cond__need_hierarchy = false;
		public $sql_cond__domain_ignored = false;
		public $sql_cond_auto_domain = false;

		public $sql_arr_for_mark_used_fields = array();
		public $sql_arr_for_and_or_part = array();

		// ==

		public $sql_kwd_distinct = "";
		public $sql_kwd_distinct_count = "";
		public $sql_kwd_straight_join = "";

		public $sql_select_expr = "";
		public $sql_table_references = "";
		public $sql_where_condition_required = "";
		public $sql_where_condition_common = "";
		public $sql_where_condition_additional = "";

		public $sql_order_by = "";
		public $sql_limit = "";
		
		public $objectTableIsRequired = false;
		public $excludeNestedPages = false;
		
		public $usedContentTables = Array();

		// ========

		public function result() { return umiSelectionsParser::runSelection($this); }
		public function count() { return umiSelectionsParser::runSelectionCounts($this); }


		/**
		* @deprecated
		* @desc Включает/выключает фильтрацию по типу объектов
		* @param Boolean $is_enabled Разрешить фильтрацию (true) или запретить (false)
		*/
		public function setObjectTypeFilter($is_enabled = true) {
			$this->is_object_type = (bool) $is_enabled;
			if (!$is_enabled) $this->object_type = Array();
		}

		/**
		* @deprecated
		* @desc Включает/выключает фильтрацию по типу елементов иерархии
		* @param Boolean $is_enabled Разрешить фильтрацию (true) или запретить (false)
		*/
		public function setElementTypeFilter($is_enabled = true) {
			$this->is_element_type = (bool) $is_enabled;
			if (!$is_enabled) $this->element_type = Array();
		}

		/**
		* @deprecated
		* @desc Включает/выключает фильтрацию по свойствам объектов
		* @param Boolean $is_enabled Разрешить фильтрацию (true) или запретить (false)
		*/
		public function setPropertyFilter($is_enabled = true) {
			$this->is_props = (bool) $is_enabled;
			if (!$is_enabled) $this->props = Array();
		}

		/**
		* @deprecated
		* @desc Включает/выключает ограничение по количество элементов
		* @param Boolean $is_enabled Разрешить ограничение (true) или запретить (false)
		*/
		public function setLimitFilter($is_enabled = true) {
			$this->is_limit = (bool) $is_enabled;
			if (!$is_enabled) $this->limit = Array();
		}

		/**
		* @deprecated
		* @desc Включает/выключает фильтрацию по id элементов иерархии
		* @param Boolean $is_enabled  Разрешить фильтрацию (true) или запретить (false) 
		*/
		public function setHierarchyFilter($is_enabled = true) {
			$this->is_hierarchy = (bool) $is_enabled;
			if (!$is_enabled) $this->hierarchy = Array();
		}

		/**
		* @deprecated
		* @desc Включает/выключает сортировку
		* @param Boolean $is_enabled Разрешить сортировку (true) или запретить (false)
		*/
		public function setOrderFilter($is_enabled = true) {
			$this->is_order = (bool) $is_enabled;
			if (!$is_enabled) $this->order = Array();
		}

		/**
		* @deprecated
		* @desc Включает/выключает фильтрацию по правам
		* @param $is_enabled Разрешить фильтрацию (true) или запретить (false)
		*/ 
		public function setPermissionsFilter($is_enabled = true) {
			

			$this->is_permissions = $is_enabled;

			$user_id = $this->getCurrentUserId();
			if(cmsController::getInstance()->getModule("users")->isSv($user_id)) {
				$this->is_permissions = false;
			}
			if (!$is_enabled) $this->perms = Array();
		}

		/**
		* @deprecated
		* @desc Включает/выключает фильтрацию по активности элемента
		* @param $is_enabled Разрешить фильтрацию (true) или запретить (false)
		*/
		public function setActiveFilter($is_enabled = true) {
			$this->is_active = (bool) $is_enabled;
			if (!$is_enabled) $this->is_active = Array();
		}

		/**
		* @deprecated
		* @desc Включает/выключает фильтрацию по владельцу
		* @param $is_enabled Разрешить фильтрацию (true) или запретить (false)
		*/
		public function setOwnerFilter($is_enabled = true) {
			$this->is_owner = (bool) $is_enabled;
			if (!$is_enabled) $this->is_owner = Array();
		}

		/**
		* @deprecated
		* @desc Включает/выключает фильтрацию по id объектов
		* @param $is_enabled Разрешить фильтрацию (true) или запретить (false)
		*/
		public function setObjectsFilter($is_enabled = true) {
			$this->is_objects_ids = (bool) $is_enabled;
			if (!$is_enabled) $this->is_objects_ids = Array();
		}
		
		/**
		* @deprecated
		* @desc 
		*/
		public function setElementsFilter($is_enabled = true) {
			$this->is_elements_ids = (bool) $is_enabled;
			if (!$is_enabled) $this->is_elements_ids = Array();
		}

		/**
		* @deprecated
		* @desc Включает/выключает фильтрацию по имени объекта
		* @param $is_enabled Разрешить фильтрацию (true) или запретить (false)
		*/
		public function setNamesFilter($is_enabled = true) {
			$this->is_names = (bool) $is_enabled;
			if (!$is_enabled) $this->names = Array();
		}

		public function forceHierarchyTable($isForced = true) {
			$this->is_forced = (bool) $isForced;
		}

		/**
		* @desc Добавляет тип объекта к критерию фильтрации
		* @param Int $object_type Id типа объекта
		*/
		public function addObjectType($object_type_id) {
			$this->setObjectTypeFilter();

			if(is_array($object_type_id)) {
				foreach($object_type_id as $sub_object_type_id) {
					if(!$this->addObjectType($sub_object_type_id)) {
						return false;
					}
				}
				return true;
			}

			if(umiObjectTypesCollection::getInstance()->isExists($object_type_id)) {
				if(in_array($object_type_id, $this->object_type) === false) {
					$this->object_type[] = $object_type_id;
					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
		}

		/**
		* @desc Добавляет тип элемента к критерию фильтрации
		* @param Int $object_type Id типа элемента
		*/
		public function addElementType($element_type_id) {
			/*
			Не принимает массив !!! вызывайте несколько раз (TODO: переписать)
			*/
			$this->setElementTypeFilter();
		
			if(umiHierarchyTypesCollection::getInstance()->isExists($element_type_id)) {
				if(in_array($element_type_id, $this->element_type) === false) {
					$this->element_type[] = $element_type_id;
					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
		}

		/**
		* @desc Устанавливает количественные ограничения на выборку
		* @param Int $per_page	Количество объектов на странице
		* @param Int $page 		Номер выбираемой страницы
		*/
		public function addLimit($per_page, $page = 0) {
			$this->setLimitFilter();
		
			$per_page = (int) $per_page;
			$page = (int) $page;

			if($page < 0) {
				$page = 0;
			}
			
			$this->limit = Array($per_page, $page);
		}

		/**
		* @desc Устанавливает признак активности елемента
		* @param Boolean $active True - выбрать активные алементы, False - выбрать неактивные элементы
		*/
		public function addActiveFilter($active) {
			$this->setActiveFilter();
			$this->active = Array($active);
		}

		/**
		* @desc Устанавливает владельцев объекта/элемента
		* @param Array $vOwners Возможные id владельцев
		*/
		public function addOwnerFilter($vOwners) {
			$this->setOwnerFilter();
			$this->owner = $this->toIntsArray($vOwners);
		}

		/**
		* @desc Устанавливает возможные id объектов
		* @param Array $vOids возможные id объектов
		*/
		public function addObjectsFilter($vOids) {
			$this->setObjectsFilter();
			$this->objects_ids = $this->toIntsArray($vOids);
		}

		/**
		* @desc Устанавливает возможные id елементов иерархии
		* @param Array $vOids возможные id елементов иерархии
		*/
		public function addElementsFilter($vEids) {
			$this->setElementsFilter();
			$this->elements_ids = $this->toIntsArray($vEids);
		}

		/**
		* @desc Устанавливает поле и вид сортировки
		* @param Int 		$field_id 	id поля, по которому будет произведена сортировка
		* @param Boolean 	$asc 		порядок сортировки: true - прямой, false - обратный
		*/
		public function setOrderByProperty($field_id, $asc = true) {
			if(!$field_id) return false;
			$this->setOrderFilter();
		
			$data_type = $this->getDataByFieldId($field_id);

			$filter = Array("field_id" => $field_id, "asc" => $asc, "type" => $data_type, "native_field" => false);

			if(in_array($filter, $this->order) === false) {
				$this->order[] = $filter;
				return true;
			} else {
				return false;
			}
		}

		/**
		* @desc Устанавливает сортировку по расположению в иерархии
		* @param Boolean $asc порядок сортировки: true - прямой, false - обратный
		*/
		public function setOrderByOrd($asc = true) {
			$this->setOrderFilter();

			$filter = Array("type" => "native", "native_field" => "ord", "asc" => $asc);

			if(in_array($filter, $this->order) === false) {
				$this->order[] = $filter;
				return true;
			} else {
				return false;
			}
		}
		
		/**
		* @desc Устанавливает выборку случайных ID
		*/
		public function setOrderByRand() {
			$this->setOrderFilter();
		
			$filter = Array("type" => "native", "native_field" => "rand", "asc" => true);

			if(in_array($filter, $this->order) === false) {
				$this->order[] = $filter;
				return true;
			} else {
				return false;
			}
		}

		/**
		* @desc Устанавливает сортировку по имени
		* @param Boolean $asc порядок сортировки: true - прямой, false - обратный
		*/
		public function setOrderByName($asc = true) {
			$this->setOrderFilter();
		
			$filter = Array("type" => "native", "native_field" => "name", "asc" => $asc);

			if(in_array($filter, $this->order) === false) {
				$this->order[] = $filter;
				return true;
			} else {
				return false;
			}
		}
		
		/**
		* @desc Устанавливает сортировку по id объекта
		* @param Boolean $asc порядок сортировки: true - прямой, false - обратный
		*/
		public function setOrderByObjectId($asc = true) {
			$this->setOrderFilter();

			$filter = Array("type" => "native", "native_field" => "object_id", "asc" => $asc);
			
			if(in_array($filter, $this->order) === false) {
				$this->order[] = $filter;
				return true;
			} else {
				return false;
			}
		}

        /**
        * @desc Устанавливает параметры выбора элементов иерархии
        * @param Int 	 	$element_id 		Id корня выборки
        * @param Int 	 	$depth				Глубина выборки элементов от корня
        * @param Boolean	$ignoreIsDefault	игнорировать элемент по-умолчанию
        */
		public function addHierarchyFilter($element_id, $depth = 0, $ignoreIsDefault = true) {
			$this->setHierarchyFilter();
			
			if(is_array($element_id)) {
				foreach($element_id as $id) {
					$this->addHierarchyFilter($id, $depth);
				}
				return;
			}

			if(umiHierarchy::getInstance()->isExists($element_id) || (is_numeric($element_id) && $element_id == 0)) {
				if($element_id == umiHierarchy::getInstance()->getDefaultElementId() && $ignoreIsDefault == false) {
					$element_id = Array(0, 0);
				}
			
				if(in_array($element_id, $this->hierarchy) === false || $element_id == 0) {
					$this->hierarchy[] = Array((int) $element_id, $depth);
				}

				if($depth > 0) {
					$this->hierarchy[] = Array($element_id, $depth);
				}
			} else {
				return false;
			}
		}

        /**
        * @desc Устанавливает проверку попадания значения поля в интервал
        * @param Int 	$field_id 	Id поля
        * @param Mixed 	$min 		Минимальное значение
        * @param Mixed	$max		Максимальное значение
        */
		public function addPropertyFilterBetween($field_id, $min, $max) {
			if(!$field_id) return false;
			$this->setPropertyFilter();
			
			$data_type = $this->getDataByFieldId($field_id);
			
			if($this->switchIllegalBetween && $min > $max) {
				$tmp = $min;
				$min = $max;
				$max = $tmp;
				unset($tmp);
			}

			$filter = Array("type" => $data_type, "field_id" => $field_id, "filter_type" => "between", "min" => $min, "max" => $max);
			$this->props[] = $filter;
		}

		/**
		* @desc Устанавливает проверку значения поля на равенство
		* @param Int		$field_id			Id поля
		* @param Mixed		$value				Значение для сравнения
		* @param Boolean 	$case_insencetive   True - не учитывать регистр, false - учитывать регистр
		*/
		public function addPropertyFilterEqual($field_id, $value, $case_insencetive = true) {
			if(!$field_id || !sizeof($value)) return false;
			$this->setPropertyFilter();
		
			$data_type = $this->getDataByFieldId($field_id);

			$filter = Array("type" => $data_type, "field_id" => $field_id, "filter_type" => "equal", "value" => $value, "case_insencetive" => $case_insencetive);
			$this->props[] = $filter;
		}

		/**
		* @desc Устанавливает проверку значения поля на неравенство
		* @param Int		$field_id			Id поля
		* @param Mixed		$value				Значение для сравнения
		* @param Boolean 	$case_insencetive   True - не учитывать регистр, false - учитывать регистр 
		*/
		public function addPropertyFilterNotEqual($field_id, $value, $case_insencetive = true) {
			if(!$field_id) return false;
			$this->setPropertyFilter();
		
			$data_type = $this->getDataByFieldId($field_id);

			$filter = Array("type" => $data_type, "field_id" => $field_id, "filter_type" => "not_equal", "value" => $value, "case_insencetive" => $case_insencetive);
			$this->props[] = $filter;
		}

        /**
		* @desc Устанавливает проверку значения поля на включение поисковой строки
		* @param Int		$field_id			Id поля
		* @param Mixed		$value				Значение для поиска
		* @param Boolean 	$case_insencetive   True - не учитывать регистр, false - учитывать регистр 
		*/
		public function addPropertyFilterLike($field_id, $value, $case_insencetive = true) {
			if(!$field_id) return false;
			$this->setPropertyFilter();
		
			$data_type = $this->getDataByFieldId($field_id);

			$filter = Array("type" => $data_type, "field_id" => $field_id, "filter_type" => "like", "value" => $value, "case_insencetive" => $case_insencetive);
			$this->props[] = $filter;
		}

		/**
		* @desc Устанавливает проверку значения поля на "больше"
		* @param Int		$field_id			Id поля
		* @param Mixed		$value				Значение для сравнения		
		*/
		public function addPropertyFilterMore($field_id, $value) {
			if(!$field_id) return false;
			$this->setPropertyFilter();
		
			$data_type = $this->getDataByFieldId($field_id);

			$filter = Array("type" => $data_type, "field_id" => $field_id, "filter_type" => "more", "value" => $value);
			$this->props[] = $filter;
		}

		/**
		* @desc Устанавливает проверку значения поля на "меньше"
		* @param Int		$field_id			Id поля
		* @param Mixed		$value				Значение для сравнения		
		*/
		public function addPropertyFilterLess($field_id, $value) {
			if(!$field_id) return false;
			$this->setPropertyFilter();
		
			$data_type = $this->getDataByFieldId($field_id);

			$filter = Array("type" => $data_type, "field_id" => $field_id, "filter_type" => "less", "value" => $value);
			$this->props[] = $filter;
		}

		/**
		* @desc Устанавливает проверку значения поля на отсутствие значения
		* @param Int		$field_id			Id поля		
		*/
		public function addPropertyFilterIsNull($field_id) {
			if(!$field_id) return false;
			$this->setPropertyFilter();
		
			$data_type = $this->getDataByFieldId($field_id);

			$filter = Array("type" => $data_type, "field_id" => $field_id, "filter_type" => "null");
			$this->props[] = $filter;
		}

		/**
		* @desc Устанавливает проверку значения поля на отсутствие значения
		* @param Int		$field_id			Id поля		
		*/
		public function addPropertyFilterIsNotNull($field_id) {
			if(!$field_id) return false;
			$this->setPropertyFilter();
		
			$data_type = $this->getDataByFieldId($field_id);

			$filter = Array("type" => $data_type, "field_id" => $field_id, "filter_type" => "notnull");
			$this->props[] = $filter;
		}

        /**
        * @desc Устанавливает пользователя или группу для проверки прав на элемент
        * @param Int $user_id ID пользователя или группы
        */
		public function addPermissions($user_id = false) {
			$this->setPermissionsFilter();
		
			if($user_id === false) {
				$permissions = permissionsCollection::getInstance();
				if($permissions->isSv()) return;
				$user_id = $permissions->getUserId();
				
			}
			$owners = $this->getOwnersByUser($user_id);
			$this->perms = $owners;
		}
		
		/**
			* Устанавливает уровень прав, который должен быть у искомых страниц
		*/
		public function setPermissionsLevel($level = 1) {
			$this->permissionsLevel = (int) $level;
		}

		/**
		* @desc Устанавливает значение для проверки имени поля на равенство
		* @param Mixed $value Значение для проверки
		*/
		public function addNameFilterEquals($value) {
			$this->setNamesFilter();
		
			$value = Array("value" => $value, "type" => "exact");

			if(!in_array($value, $this->names)) {
				$this->names[] = $value;
			}
		}
		
		/**
		* @desc Устанавливает значение для поиска в имени
		* @param Mixed $value значение для поиска
		*/
		public function addNameFilterLike($value) {
			$this->setNamesFilter();
		
			$value = Array("value" => $value, "type" => "like");

			if(!in_array($value, $this->names)) {
				$this->names[] = $value;
			}
		}

		/**
		* @desc Возвращает параметры сортировки
		* @return Array | Boolean(False) 
		*/
		public function getOrderConds() {
			return ($this->is_order) ? $this->order : false;
		}

		/**
		* @desc Возвращает количественные ограничения на выборку
		* @return Array | Boolean(False) 
		*/
		public function getLimitConds() {
			return ($this->is_limit) ? $this->limit : false;
		}

		/**
		* @desc Возвращает признак активности
		* @return Boolean 
		*/
		public function getActiveConds() {
			return ($this->is_active) ? $this->active : false;
		}

		/**
		* @desc Возвращает список возможных владельцев
		* @return Array | Boolean(False) 
		*/
		public function getOwnerConds() {
			$arrAnswer = array();
			if (is_array($this->owner) && count($this->owner)) {
				$arrAnswer = array_map('intval', $this->owner);
			}
			return ($this->is_owner) ? $arrAnswer : false;
		}
		
		/**
		* @desc Возвращает список возможных id объектов
		* @return Array | Boolean(False) 
		*/
		public function getObjectsConds() {
			$arrAnswer = array();
			if (is_array($this->objects_ids) && count($this->objects_ids)) {
				$arrAnswer = array_map('intval', $this->objects_ids);
			}
			return ($this->is_objects_ids) ? $arrAnswer : false;
		}
		
		/**
		* @desc Возвращает список возможных id элементов иерархии
		* @return Array | Boolean(False) 
		*/
		public function getElementsConds() {
			$arrAnswer = array();
			if (is_array($this->elements_ids) && count($this->elements_ids)) {
				$arrAnswer = array_map('intval', $this->elements_ids);
			}
			return ($this->is_elements_ids) ? $arrAnswer : false;
		}

		/**
		* @desc Возвращает список условий на выборку по значению полей
		* @return Array | Boolean(False)
		*/
		public function getPropertyConds() {
			return ($this->is_props) ? $this->props : false;
		}

		/**
		* @desc Возвращает список возможных id типов объектов
		* @return Array | Boolean(False) 
		*/
		public function getObjectTypeConds() {
			return ($this->is_object_type) ? $this->object_type : false;
		}

		/**
		* @desc Возвращает список возможных id типов элементов иерархии
		* @return Array  | Boolean(False)
		*/
		public function getElementTypeConds() {
			if($this->getObjectTypeConds() !== false) {
				return false;
			}
			
			if($this->optimize_root_search_query) {
				if(is_array($this->element_type)) {
					if(sizeof($this->element_type) > 1) {
						reset($this->element_type);
						$this->element_type = Array(current($this->element_type));
					}
				}
			}

			return ($this->is_element_type) ? $this->element_type : false;
		}

		public function getHierarchyConds() {
			$this->hierarchy = array_unique_arrays($this->hierarchy, 0);
			return ($this->is_hierarchy && !$this->optimize_root_search_query) ? $this->hierarchy : false;
		}

		/**
		* @desc Возвращает список пользователей и/или групп с правами на элемент иерархии
		* @return Array | Boolean(False) 
		*/
		public function getPermissionsConds() {
			return ($this->is_permissions) ? $this->perms : false;
		}

		public function getForceCond() {
			return $this->is_forced;
		}

		/**
		* @desc Возвращает условия проверки имени
		* @return Array | Boolean(False)
		*/
		public function getNameConds() {
			return ($this->is_names) ? $this->names : false;
		}
		
		private function getDataByFieldId($field_id) {
			if($field = umiFieldsCollection::getInstance()->getField($field_id)) {
				$field_type_id = $field->getFieldTypeId();

				if($field_type = umiFieldTypesCollection::getInstance()->getFieldType($field_type_id)) {
					if($data_type = $field_type->getDataType()) {
						return umiFieldType::getDataTypeDB($data_type);
					} else {
						return false;
					}
				} else {
					return false;
				}
			} else {
				return false;
			}
		}

		private function getCurrentUserId() {
			if($users = cmsController::getInstance()->getModule("users")) {
				return $users->user_id;
			} else {
				return false;
			}
		}

		private function getOwnersByUser($user_id) {
			if($user = umiObjectsCollection::getInstance()->getObject($user_id)) {
				$groups = $user->getValue("groups");
				$groups[] = $user_id;
				return $groups;
			} else {
				return false;
			}
		}
		
		/**
		* @desc Устанавливает флаг "ИЛИ" группировки результатов выборки по значению полей.
		* 		Если этот флаг установлен, то выбираются объекты/элементы иерархии,
		* 		удовлетворяющие хотя бы одному условию, из указаных. В противном случае
		* 		требуется соблюдение всех указаных условий.
		*/
		public function setConditionModeOr() {
			$this->condition_mode_or = true;
		}
		
		/**
		* @desc Возвращает значение флага группировки результатов выборки по значению полей
		* @return Boolean
		*/
		public function getConditionModeOr() {
			return $this->condition_mode_or;
		}
		
		
		/**
		* @desc Устанавливает значение флага игнорирования текущего домена
		* @param Boolean $isDomainIgnored True - домен игнорируется, false - не игнорируется
		*/
		public function setIsDomainIgnored($isDomainIgnored = false) {
			$this->isDomainIgnored = (bool) $isDomainIgnored;
		}
		
		/**
		* @desc Устанавливает значение флага игнорирования текущей языковой версии
		* @param Boolean $isLangIgnored True - домен игнорируется, false - не игнорируется
		*/
		public function setIsLangIgnored($isLangIgnored = false) {
			$this->isLangIgnored = (bool) $isLangIgnored;
		}
		
		/**
		* @desc Возвращает значение  флага игнорирования текущего домена
		* @return Boolean
		*/
		public function getIsDomainIgnored() {
			return $this->isDomainIgnored;
		}

		/**
		* @desc Возвращает значение  флага игнорирования текущей языковой версии
		* @return Boolean
		*/
		public function getIsLangIgnored() {
			return $this->isLangIgnored;
		}
		
		/**
			* Искать только по указанному домену
			* @param Integer $domainId = false id домена, либо false, если поиск будет по всем доменам
		*/
		public function setDomainId($domainId = false) {
			$this->domainId = ($domainId === false) ? false : (int) $domainId;
		}
		
		/**
			* Искать только в указанной языковой версии
			* @param Integer $langId = false id языка, либо false
		*/
		public function setLangId($langId = false) {
			$this->langId = ($langId === false) ? false : (int) $langId;
		}
		
		/**
			* Поиск по строке в любом тектовом поле
			* @param String $searchString строка поиска
		*/
		public function searchText($searchString) {
			if(is_string($searchString)) {
				if(strlen($searchString) > 0 && !in_array($searchString, $this->searchStrings)) {
					$this->searchStrings[] = $searchString;
					return true;
				}
			}
			return false;
		}
		
		public function getDomainId() {
			return $this->domainId;
		}
		
		public function getLangId() {
			return $this->langId;
		}
		
		public function getRequiredPermissionsLevel() {
			return $this->permissionsLevel;
		}
		
		public function getSearchStrings() {
			return $this->searchStrings;
		}
		
		public function resetTextSearch() {
			$this->searchStrings = Array();
		}

		//

		private function toIntsArray($vValue) {
			$arrAnswer = Array();
			if (is_string($vValue)) {
				$arrAnswer = preg_split("/[^\d]/is", $vValue);
			} elseif (is_numeric($vValue)) {
				$arrAnswer = array(intval($vValue));
			} elseif (!is_array($vValue)) {
				$arrAnswer = array();
			} else {
			    $arrAnswer = $vValue;
			}
			return array_map('intval', $arrAnswer);
		}
	};
?>
