<?php
/**
	* Классы, дочерние от класса baseRestriction отвечают за валидацию полей.
	* В таблицу `cms3_object_fields` добавилось поле `restriction_id`
	* Список рестрикшенов хранится в таблице `cms3_object_fields_restrictions`:
	* +----+------------------+-------------------------------+---------------+
	* | id | class_prefix     | title                         | field_type_id |
	* +----+------------------+-------------------------------+---------------+
	* | 1  | email            | i18n::restriction-email-title | 4             |
	* +----+------------------+-------------------------------+---------------+
	*
	* При модификации значения поля, которое связано с restriction'ом, загружается этот restriction,
	* В метод validate() передается значение. Если метод вернет true, работа продолжается,
	* если false, то получаем текст ошибки и делаем errorPanic() на предыдущую страницу.
*/
	abstract class baseRestriction {
		protected	$errorMessage = 'restriction-error-common',
					$id, $title, $classPrefix, $fieldTypeId;


		/**
			* Загрузить restriction
			* @param Integer $restrictionId id рестрикшена
			* @return baseRestriction потомок класса baseRestriction
		*/
		final public static function get($restrictionId) {
			$restrictionId = (int) $restrictionId;

			$sql = "SELECT `class_prefix`, `title`, `field_type_id` FROM `cms3_object_fields_restrictions` WHERE `id` = '{$restrictionId}'";
			$result = l_mysql_query($sql);

			if(list($classPrefix, $title, $fieldTypeId) = mysql_fetch_row($result)) {
				$filePath = CURRENT_WORKING_DIR . '/classes/system/subsystems/models/data/restrictions/' . $classPrefix . '.php';
				$className = $classPrefix . 'Restriction';
				if(is_file($filePath) == false) {
					return false;
				}

				if(!class_exists($className)) {
					require $filePath;
				}

				if(class_exists($className)) {
					$restriction = new $className($restrictionId, $classPrefix, $title, $fieldTypeId);
					if($restriction instanceof baseRestriction) {
						return $restriction;
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


		/**
			* Получить список всех рестрикшенов
			* @return Array массив из наследников baseRestriction
		*/
		final public static function getList() {
			$sql = "SELECT `id` FROM `cms3_object_fields_restrictions`";
			$result = l_mysql_query($sql);

			$restrictions = array();
			while(list($id) = mysql_fetch_row($result)) {
				 $restriction= self::get($id);
				 if($restriction instanceof baseRestriction) {
				 	$restrictions[] = $restriction;
				 }
			}
			return $restrictions;
		}


		/**
			* Добавить новый restriction
			* @param String $classPrefix название класса рестрикшена
			* @param String $title название рестрикшена
			* @param Integer $fieldTypeId id типа полей, для которого допустим этот рестрикшен
			* @return Integer|Boolean id созданного рестрикшена, либо false
		*/
		final public static function add($classPrefix, $title, $fieldTypeId) {
			$classPrefix = l_mysql_real_escape_string($classPrefix);
			$title = l_mysql_real_escape_string($title);
			$fieldTypeId = (int) $fieldTypeId;

			$sql = <<<SQL
INSERT INTO `cms3_object_fields_restrictions`
	(`class_prefix`, `title`, `field_type_id`)
	VALUES ('{$classPrefix}', '{$title}', '{$fieldTypeId}')
SQL;
			l_mysql_query($sql);
			return l_mysql_insert_id();
		}


		/**
			* Провалидировать значение поля
			* @param Mixed &$value валидируемое значение поля
			* @return Boolean результат валидации
		*/
		abstract public function validate($value, $objectId = false);

		/**
			* Получить текст сообщения об ошибке
			* @return String сообщение об ошибке
		*/
		public function getErrorMessage() {
			return getLabel($this->errorMessage);
		}


		/**
			* Получить название рестрикшена
			* @return String название рестрикшена
		*/
		public function getTitle() {
			return getLabel($this->title);
		}


		/**
			* Получить префикс класса рестрикшена
			* @return String префикс класса рестрикшена
		*/
		public function getClassName() {
			return $this->classPrefix;
		}


		/**
			* Получить id рестрикшена
			* @return Integer id рестрикшена
		*/
		public function getId() {
			return $this->id;
		}

		public function getFieldTypeId() {
			return $this->fieldTypeId;
		}

		public static function find($classPrefix, $fieldTypeId) {
			$restrictions = self::getList();

			foreach($restrictions as $restriction) {
				if($restriction->getClassName() == $classPrefix && $restriction->getFieldTypeId() == $fieldTypeId) {
					return $restriction;
				}
			}
		}


		/**
			* Конструктор класса
		*/
		protected function __construct($id, $classPrefix, $title, $fieldTypeId) {
			$this->id = (int) $id;
			$this->classPrefix = $classPrefix;
			$this->title = $title;
			$this->fieldTypeId = (int) $fieldTypeId;
		}
	};

	interface iNormalizeInRestriction {
		public function normalizeIn($value, $objectId = false);
	};

	interface iNormalizeOutRestriction {
		public function normalizeOut($value, $objectId = false);
	};
?>