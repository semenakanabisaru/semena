<?php
/**
	* Этот класс-коллекция служит для управления/получения доступа к типам полей
	* Класс является синглтоном, экземпляр класса можно получить через статический метод getInstance()
*/
	class umiFieldTypesCollection extends singleton implements iSingleton, iUmiFieldTypesCollection {
		private $field_types = Array();

		protected function __construct() {
			$this->loadFieldTypes();
		}

		/**
			* Получить экземпляр коллекции
			* @return umiFieldTypesCollection экземпляр класса
		*/
		public static function getInstance() {
			return parent::getInstance(__CLASS__);
		}

		/**
			* Создать новый тип поля
			* @param String $name описание типа
			* @param String $data_type тип данных
			* @param Boolean $is_multiple является ли тип составным (массив значений)
			* @param Boolean $is_unsigned зарезервировано и пока не используется, рекомендуется выставлять в false
			* @return Integer идентификатор созданного типа, либо false в случае неудачи
		*/
		public function addFieldType($name, $data_type = "string", $is_multiple = false, $is_unsigned = false) {
			if(!umiFieldType::isValidDataType($data_type)) {
				throw new coreException("Not valid data type given");
				return false;
			}
			$this->disableCache();
			cacheFrontend::getInstance()->flush();

			$sql = "INSERT INTO cms3_object_field_types (data_type) VALUES('{$data_type}')";
			$result = l_mysql_query($sql);

			if($err = l_mysql_error()) {
				throw new coreException($err);
				return false;
			}

			$field_type_id = l_mysql_insert_id();

			$field_type = new umiFieldType($field_type_id);

			$field_type->setName($name);
			$field_type->setDataType($data_type);
			$field_type->setIsMultiple($is_multiple);
			$field_type->setIsUnsigned($is_unsigned);
			$field_type->commit();

			$this->field_types[$field_type_id] = $field_type;

			return $field_type_id;
		}

		/**
			* Удалить тип поля с заданным идентификатором из коллекции
			* @param Integer $field_type_id идентификатор поля
			* @return Boolean true, если удаление удалось
		*/
		public function delFieldType($field_type_id) {
			$this->disableCache();
			cacheFrontend::getInstance()->flush();

			if($this->isExists($field_type_id)) {
				$field_type_id = (int) $field_type_id;
				$sql = "DELETE FROM cms3_object_field_types WHERE id = '{$field_type_id}'";
				l_mysql_query($sql);

				if($err = l_mysql_error()) {
					throw new coreException($err);
					return false;
				}

				unset($this->field_types[$field_type_id]);
				return true;
			} else {
				return false;
			}
		}

		/**
			* Получтить экземпляр класса umiFieldType по идентификатору
			* @param Integer $field_type_id идентификатор поля
			* @return umiFieldType экземпляр класса umiFieldType, либо false в случае неудачи
		*/
		public function getFieldType($field_type_id) {
			if($this->isExists($field_type_id)) {
				return $this->field_types[$field_type_id];
			} else {
				return true;
			}
		}

		/**
			* Получтить экземпляр класса umiFieldType по типу данных
			* @param String $data_type тип данных
			* @param Boolean $multiple может ли значение поля данного типа состоять из массива значений (составной тип)
			* @return umiFieldType экземпляр класса umiFieldType, либо false в случае неудачи
		*/
		public function getFieldTypeByDataType($data_type, $multiple = false) {
			if(!strlen($data_type)) return false;

			$field_types = $this->getFieldTypesList();
			$field_type = false;

			foreach($field_types as $ftype) {
				if ($ftype->getDataType() == $data_type && $ftype->getIsMultiple() == $multiple) {
					$field_type = $ftype;
					break;
				}
			}
			return $field_type;
		}

		/**
			* Проверить, существует ли в БД тип поля с заданным идентификатором
			* @param Integer $field_type_id идентификатор типа
			* @return Boolean true, если тип поля существует в БД
		*/
		public function isExists($field_type_id) {
			return (bool) array_key_exists($field_type_id, $this->field_types);
		}

		/**
			* Загружает в коллекцию все типы полей, создает экземпляры класса umiFieldType для каждого типа
			* @return Boolean true, если удалось загрузить, либо строку - описание ошибки, в случае неудачи.
		*/
		private function loadFieldTypes() {
			$cacheFrontend = cacheFrontend::getInstance();

			$fieldTypeIds = $cacheFrontend->loadData("field_types");
			if(!is_array($fieldTypeIds)) {
				$sql = "SELECT id, name, data_type, is_multiple, is_unsigned FROM cms3_object_field_types ORDER BY name ASC";
				$result = l_mysql_query($sql);
				$fieldTypeIds = array();
				while(list($field_type_id) = $row = mysql_fetch_row($result)) {
					$fieldTypeIds[$field_type_id] = $row;
				}
				$cacheFrontend->saveData("field_types", $fieldTypeIds, 3600);
			} else $row = false;

			foreach($fieldTypeIds as $field_type_id => $row) {
				$field_type = $cacheFrontend->load($field_type_id, "field_type");
				if($field_type instanceof umiFieldType == false) {
					try {
						$field_type = new umiFieldType($field_type_id, $row);
					} catch(privateException $e) {
						continue;
					}

					$cacheFrontend->save($field_type, "field_type");
				}
				$this->field_types[$field_type_id] = $field_type;
			}

			return true;
		}

		/**
			* Возвращает список всех типов полей
			* @return Array список типов (экземпляры класса umiFieldType)
		*/
		public function getFieldTypesList() {
			return $this->field_types;
		}
		
		public function clearCache() {
			$keys = array_keys($this->field_types);
			foreach($keys as $key) unset($this->field_types[$key]);
			$this->field_types = array();
			$this->loadFieldTypes();
		}
	}
?>