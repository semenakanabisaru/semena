<?php
/**
	* Этот класс служит для управления свойствами типа поля
*/
	class umiFieldType extends umiEntinty implements iUmiEntinty, iUmiFieldType {
		private $name, $data_type, $is_multiple = false, $is_unsigned = false;
		protected $store_type = "field_type";

		/**
			* Получить описание типа
			* @return String описание типа
		*/
		public function getName() {
			return $this->translateLabel($this->name);
		}

		/**
			* Узнать, может ли значение поля данного типа состоять из массива значений (составной тип)
			* @return Boolean true, если тип составной
		*/
		public function getIsMultiple() {
			return $this->is_multiple;
		}


		/**
			* Узнать, может ли значение поля данного типа иметь знак.
			* Зарезервировано и пока не используется
			* @return Boolean true, если значение поля не будет иметь знак
		*/
		public function getIsUnsigned() {
			return $this->is_unsigned;
		}

		/**
			* Получить идентификатор типа
			* @return String идентификатор типа
		*/
		public function getDataType() {
			return $this->data_type;
		}

		/**
			* Получить список всех поддерживаемых идентификаторов типа
			* @return Array список идентификаторов
		*/
		public static function getDataTypes() {
			return

			Array	(
				"int",
				"string",
				"text",
				"relation",
				"file",
				"img_file",
				"video_file",
				"swf_file",
				"date",
				"boolean",
				"wysiwyg",
				"password",
				"tags",
				"symlink",
				"price",
				"formula",
				"float",
				"counter",
				"optioned"
				);
		}

		/**
			* Получить имя поля таблицы БД, где будут хранится данные по идентификатору типа
			* @param String $data_type идентификатор типа
			* @return String имя поля таблицы БД, либо false, если связь не обнаружена
		*/
		public static function getDataTypeDB($data_type) {
			$rels = Array	(
				"int"		 => "int_val",
				"string"	 => "varchar_val",
				"text"		 => "text_val",
				"relation"	 => "rel_val",
				"file"		 => "text_val",
				"img_file"	 => "text_val",
				"swf_file"	 => "text_val",
				"video_file" => "text_val",
				"date"		 => "int_val",
				"boolean"	 => "int_val",
				"wysiwyg"	 => "text_val",
				"password"	 => "varchar_val",
				"tags"		 => "varchar_val",
				"symlink"	 => "tree_val",
				"price"		 => "float_val",
				"formula"	 => "varchar_val",
				"float"		 => "float_val",
				"counter"	 => "counter",
				"optioned"	 => "optioned"
				);

			if(array_key_exists($data_type, $rels) === false) {
				return false;
			} else {
				return $rels[$data_type];
			}
		}

		/**
			* Узнать, поддерживается ли идентификатор типа 
			* @param String $data_type идентификатор типа
			* @return Boolean true, если идентификатор типа поддерживается
		*/
		public static function isValidDataType($data_type) {
			return in_array($data_type, self::getDataTypes());
		}



		/**
			* Задать новое описание типа
			* Устанавливает флаг "Модифицирован".
			* @param String $name
		*/
		public function setName($name) {
			$name = $this->translateI18n($name, "field-type-");
			$this->name = $name;
			$this->setIsUpdated();
		}

		/**
			* Указать может ли значение поля данного типа состоять из массива значений (составной тип)
			* Устанавливает флаг "Модифицирован".
			* @param Boolean $is_multiple
		*/
		public function setIsMultiple($is_multiple) {
			$this->is_multiple = (bool) $is_multiple;
			$this->setIsUpdated();
		}

		/**
			* Указать может ли значение поля данного типа иметь знак.
			* Зарезервировано и пока не используется
			* Устанавливает флаг "Модифицирован".
			* @param Boolean $is_unsigned
		*/
		public function setIsUnsigned($is_unsigned) {
			$this->is_unsigned = (bool) $is_unsigned;
			$this->setIsUpdated();
		}

		/**
			* Установить идентификатор типа
			* Устанавливает флаг "Модифицирован".
			* @param String $data_type идентификатор типа
			* @return Boolean true, если удалось установить, false - если идентификатор не поддерживается
		*/
		public function setDataType($data_type) {
			if(self::isValidDataType($data_type)) {
				$this->data_type = $data_type;
				$this->setIsUpdated();
				return true;
			} else {
				return false;
			}
		}


		/**
			* Загружает необходимые данные для формирования объекта umiFieldType из БД.
			* @return Boolean true в случае успеха
		*/
		protected function loadInfo($row = false) {
			if($row === false) {
				$sql = "SELECT id, name, data_type, is_multiple, is_unsigned FROM cms3_object_field_types WHERE id = '{$this->id}'";
				$result = l_mysql_query($sql);

				if($err = l_mysql_error()) {
					throw new coreException($err);
					return false;
				}
			
				$row = mysql_fetch_row($result);
			}

			if(list($id, $name, $data_type, $is_multiple, $is_unsigned) = $row) {
				if(!self::isValidDataType($data_type)) {
					throw new coreException("Wrong data type given for filed type #{$this->id}");
					return false;
				}

				$this->name = $name;
				$this->data_type = $data_type;
				$this->is_multiple= (bool) $is_multiple;
				$this->is_unsigned = (bool) $is_unsigned;

				return true;
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
			$data_type = l_mysql_real_escape_string($this->data_type);
			$is_multiple = (int) $this->is_multiple;
			$is_unsigned = (int) $this->is_unsigned;

			$sql = "UPDATE cms3_object_field_types SET name = '{$name}', data_type = '{$data_type}', is_multiple = '{$is_multiple}', is_unsigned = '{$is_unsigned}' WHERE id = '{$this->id}'";
			l_mysql_query($sql);
			
			cacheFrontend::getInstance()->flush();

			if($err = l_mysql_error()) {
				throw new coreException($err);
				return false;
			} else {
				return true;
			}
		}
	}
?>