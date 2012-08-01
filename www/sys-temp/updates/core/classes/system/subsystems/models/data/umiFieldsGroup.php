<?php
/**
	* Этот класс реализует объединение полей в именованные группы.
*/
	class umiFieldsGroup extends umiEntinty implements iUmiEntinty, iUmiFieldsGroup {
		private	$name, $title,
			$type_id, $ord,
			$is_active = true, $is_visible = true, $is_locked = false,

			$autoload_fields = false,

			$fields = Array();
		
		protected $store_type = "fields_group";

		/**
			* Получить строковой id группы
			* @return String строковой id группы
		*/
		public function getName() {
			return $this->name;
		}

		/**
			* Получить название группы
			* @return String название группы в текущей языковой версии
		*/
		public function getTitle() {
			return $this->translateLabel($this->title);
		}

		/**
			* Получить id типа данных, к которому относится группа полей
			* @return Integer id типа данных (класс umiObjectType)
		*/
		public function getTypeId() {
			return $this->type_id;
		}

		/**
			* Получить порядковый номер группы, по которому она сортируется в рамках типа данных
			* @return Integer порядковый номер
		*/
		public function getOrd() {
			return $this->ord;
		}

		/**
			* Узнать, активна ли группа полей
			* @return Boolean значение флага активности
		*/
		public function getIsActive() {
			return $this->is_active;
		}

		/**
			* Узнать, видима ли группа полей
			* @return Boolean значение флага видимости
		*/
		public function getIsVisible() {
			return $this->is_visible;
		}

		/**
			* Узнать, заблокирована ли группа полей (разработчиком)
			* @return Boolean значение флага блокировка
		*/
		public function getIsLocked() {
			return $this->is_locked;
		}

		/**
			* Изменить строковой id группы на $name
			* @param String $name новый строковой id группы полей
		*/
		public function setName($name) {
			$this->name = umiObjectProperty::filterInputString($name);
			$this->setIsUpdated();
		}

		/**
			* Изменить название группы полей
			* @param $title новое название группы полей
		*/
		public function setTitle($title) {
			$title = $this->translateI18n($title, "fields-group");
			$this->title = umiObjectProperty::filterInputString($title);
			$this->setIsUpdated();
		}

		/**
			* Изменить тип данных, которому принадлежит группа полей
			* @param Integer $type_id id нового типа данных (класс umiObjectType)
			* @return Boolean true, если такое изменение возможно, иначе false
		*/
		public function setTypeId($type_id) {
			$types = umiObjectTypesCollection::getInstance();
			if($types->isExists($type_id)) {
				$this->type_id = $type_id;
				return true;
			} else {
				return false;
			}
		}

		/**
			* Установить новое значение порядка сортировки
			* @param Integer $ord новый порядковый номер
		*/
		public function setOrd($ord) {
			$this->ord = $ord;
			$this->setIsUpdated();
		}

		/**
			* Изменить активность группы полей
			* @param Boolean $is_active новое значение флага активности
		*/
		public function setIsActive($is_active) {
			$this->is_active = (bool) $is_active;
			$this->setIsUpdated();
		}

		/**
			* Изменить видимость группы полей
			* @param Boolean $is_visible новое значение флага видимости
		*/
		public function setIsVisible($is_visible) {
			$this->is_visible = (bool) $is_visible;
			$this->setIsUpdated();
		}

		/**
			* Изменить стостояние блокировки группы полей
			* @param Boolean $is_locked новое значение флага блокировки
		*/
		public function setIsLocked($is_locked) {
			$this->is_locked = (bool) $is_locked;
			$this->setIsUpdated();
		}
		protected function loadInfo($row = false) {
			if($row === false) {
				$sql = "SELECT id, name, title, type_id, is_active, is_visible, is_locked, ord FROM cms3_object_field_groups WHERE id = '{$this->id}'";
				$result = l_mysql_query($sql);

				if($err = l_mysql_error()) {
					throw new coreException($err);
					return false;
				}
				
				$row = mysql_fetch_row($result);
			}

			if(list($id, $name, $title, $type_id, $is_active, $is_visible, $is_locked, $ord) = $row) {
				if(!umiObjectTypesCollection::getInstance()->isExists($type_id)) {
					return false;
				}

				$this->name = $name;
				$this->title = $title;
				$this->type_id = $type_id;
				$this->is_active = (bool) $is_active;
				$this->is_visible = (bool) $is_visible;
				$this->is_locked = (bool) $is_locked;
				$this->ord = (int) $ord;

				if($this->autoload_fields) {
					return $this->loadFields();
				} else {
					return true;
				}
			} else {
				return false;
			}
		}


		protected function save() {
			$name = l_mysql_real_escape_string($this->name);
			$title = l_mysql_real_escape_string($this->title);
			$type_id = (int) $this->type_id;
			$is_active = (int) $this->is_active;
			$is_visible = (int) $this->is_visible;
			$ord = (int) $this->ord;
			$is_locked = (int) $this->is_locked;

			$sql = "UPDATE cms3_object_field_groups SET name = '{$name}', title = '{$title}', type_id = '{$type_id}', is_active = '{$is_active}', is_visible = '{$is_visible}', ord = '{$ord}', is_locked = '{$is_locked}' WHERE id = '{$this->id}'";
			$result = l_mysql_query($sql);

			if($err = l_mysql_error()) {
				throw new coreException($err);
				return false;
			} else {
				return true;
			}
		}

		/**
			* Не используйте этот метод в прикладном коде.
		*/
		public function loadFields($rows = false) {
			$fields = umiFieldsCollection::getInstance();

			if($rows === false) {
				$sql = "SELECT cof.id, cof.name, cof.title, cof.is_locked, cof.is_inheritable, cof.is_visible, cof.field_type_id, cof.guide_id, cof.in_search, cof.in_filter, cof.tip, cof.is_required FROM cms3_fields_controller cfc, cms3_object_fields cof WHERE cfc.group_id = '{$this->id}' AND cof.id = cfc.field_id ORDER BY cfc.ord ASC";
				$result = l_mysql_query($sql);

				if($err = l_mysql_error()) {
					throw new coreException($err);
					return false;
				}

				while(list($field_id) = $row = mysql_fetch_row($result)) {
					if($field = $fields->getField($field_id, $row)) {
						$this->fields[$field_id] = $field;
					}
				}

			} else {
				foreach($rows as $row) {
					list($field_id) = $row;
					if($field = $fields->getField($field_id, $row)) {
						$this->fields[$field_id] = $field;
					}
				}
			}
		}

		/**
			* Получить список всех полей в группе
			* @return Array массив из объектов umiField
		*/
		public function getFields() {
			return $this->fields;
		}

		private function isLoaded($field_id) {
			return (bool) array_key_exists($field_id, $this->fields);
		}

		/**
			* Присоединить к группе еще одно поле
			* @param Integer $field_id id присоединяемого поля
			* @param Boolean $ignor_loaded если true, то можно будет добавлять уже внесенные в эту группу поля
		*/
		public function attachField($field_id, $ignore_loaded = false) {
			if($this->isLoaded($field_id) && !$ignore_loaded) {
				return true;
			} else {
				$field_id = (int) $field_id;

				$sql = "SELECT MAX(ord) FROM cms3_fields_controller WHERE group_id = '{$this->id}'";
				$result = l_mysql_query($sql);

				if($err = l_mysql_error()) {
					throw new coreException($err);
					return false;
				}

				list($ord) = mysql_fetch_row($result);
				$ord += 5;

				$sql = "INSERT INTO cms3_fields_controller (field_id, group_id, ord) VALUES('{$field_id}', '{$this->id}', '{$ord}')";
				l_mysql_query($sql);

				if($err = l_mysql_error()) {
					throw new coreException($err);
					return false;
				}
				
				cacheFrontend::getInstance()->flush();

				$fields = umiFieldsCollection::getInstance();
				$field = $fields->getField($field_id);
				$this->fields[$field_id] = $field;
				
				$this->fillInContentTable($field_id);
			}
		}
		
		
		protected function fillInContentTable($field_id) {
			$type_id = $this->type_id;
		
			$sql = "INSERT INTO cms3_object_content (obj_id, field_id, int_val, varchar_val, text_val, rel_val, tree_val, float_val) SELECT id, {$field_id}, NULL, NULL, NULL, NULL, NULL, NULL FROM cms3_objects WHERE type_id = {$type_id}";
			l_mysql_query($sql);
			
			if($err = l_mysql_error()) {
				throw new coreException($err);
			}
			
		}

		/**
			* Вывести поле из группы полей.
			* При этом поле физически не удаляется, так как может одновременно фигурировать в разный группах полей разных типов данных.
			* @param Integer $field_id id поля (класс umiField)
			* @return Boolean результат операции
		*/
		public function detachField($field_id) {
			if($this->isLoaded($field_id)) {
				$field_id = (int) $field_id;

				$sql = "DELETE FROM cms3_fields_controller WHERE field_id = '{$field_id}' AND group_id = '{$this->id}'";
				l_mysql_query($sql);

				if($err = l_mysql_error()) {
					throw new coreException($err);
					return false;
				}

				unset($this->fields[$field_id]);

				$sql = "SELECT COUNT(*) FROM cms3_fields_controller WHERE field_id = '{$field_id}'";
				$result = l_mysql_query($sql);

				if($err = l_mysql_error()) {
					throw new coreException($err);
					return false;
				}
				
				cacheFrontend::getInstance()->flush();

				if(list($c) = mysql_fetch_row($result)) {
					return ($c == 0) ? umiFieldsCollection::getInstance()->delField($field_id) : true;
				} else return false;

				return true;
			} else {
				return false;
			}
		}

		/**
			* Переместить поле $field_id после поля $after_field_id в группе $group_id
			* @param Integer $field_id id перемещаемого поля
		 	* @param Integer $after_field_id id поля, после которого нужно расположить перемещаемое поле
		 	* @param Integer $group_id id группы полей, в которой производятся перемещения
		 	* @param Boolean $is_last переместить в конец
		 	* @return Boolean результат операции
		*/
		public function moveFieldAfter($field_id, $after_field_id, $group_id, $is_last) {
			if($after_field_id == 0) {
				$neword = 0;
			} else {
				$sql = "SELECT ord FROM cms3_fields_controller WHERE group_id = '{$group_id}' AND field_id = '{$after_field_id}'";
				$result = l_mysql_query($sql);

				if($err = l_mysql_error()) {
					throw new coreException($err);
					return false;
				} else {
					list($neword) = mysql_fetch_row($result);
				}
			}

			if($is_last) {
				$sql = "UPDATE cms3_fields_controller SET ord = (ord + 1) WHERE group_id = '{$this->id}' AND ord >= '{$neword}'";

				l_mysql_query($sql);

				if($err = l_mysql_error()) {
					throw new coreException($err);
					return false;
				}
			} else {
				$sql = "SELECT MAX(ord) FROM cms3_fields_controller WHERE group_id = '{$group_id}'";
				$result = l_mysql_query($sql);

				if($err = l_mysql_error()) {
					throw new coreException($err);
					return false;
				}

				list($neword) = mysql_fetch_row($result);
				++$neword;
			}

			$sql = "UPDATE cms3_fields_controller SET ord = '{$neword}', group_id = '$group_id' WHERE group_id = '{$this->id}' AND field_id = '{$field_id}'";
			l_mysql_query($sql);
			
			cacheFrontend::getInstance()->flush();

			if($err = l_mysql_error()) {
				throw new coreException($err);
				return false;
			} else {
				return true;
			}
		}

		public function commit() {
			parent::commit();
			cacheFrontend::getInstance()->flush();
		}
		
		/**
			* Получить список всех групп с названием $name вне зависимости от типа данных
			* @param String $name название группы полей
			* @return Array массив из объектов umiFieldsGroup
		*/
		public static function getAllGroupsByName($name) {
			if($name) {
				$name = l_mysql_real_escape_string($name);
			} else {
				return false;
			}
			
			$sql = "SELECT `id` FROM `cms3_object_field_groups` WHERE `name` = '{$name}'";
			$result = l_mysql_query($sql);
			
			$groups = Array();
			while(list($groupId) = mysql_fetch_row($result)) {
				$group = new umiFieldsGroup($groupId);
				if($group instanceof umiFieldsGroup) {
					$groups[] = $group;
				}
			}
			return $groups;
		}
	};
?>
