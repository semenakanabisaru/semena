<?php
/**
	* Базовый класс для классов, которые реализуют ключевые сущности ядра системы.
	* Реализует основные интерфейсы, которые должна поддерживать любая сущность.
*/
	abstract class umiEntinty {
		protected $id, $is_updated = false;

		protected $bNeedUpdateCache = false;

		/**
			* Конструктор сущности, должен вызываться из коллекций
			* @param Integer $id id сущности
			* @param Array $row=false массив значений, который теоретически может быть передан в конструктор для оптимизации
		*/
		public function __construct($id, $row = false) {
			$this->setId($id);
			$this->is_updated = false;
			if($this->loadInfo($row) === false) {
				throw new privateException("Failed to load info for {$this->store_type} with id {$id}");
			}
		}

		/**
			* Запрещаем копирование
		*/
		public function __clone() {
				throw new coreException('umiEntinty must not be cloned');
    	}

		/**
			* Деструктор сущности проверят, были ли внесены изменения. Если да, то они сохраняются
		*/
		public function __destruct() {
			if ($this->is_updated) {
				$this->save();
				$this->setIsUpdated(false);
				$this->updateCache();
			} elseif ($this->bNeedUpdateCache) {
				// В memcached кидаем только при деструкте и только если были какие-то изменения
				$this->updateCache();
			}
		}

		/**
			* Вернуть id сущности
			* @return Integer $id
		*/
		public function getId() {
			return $this->id;
		}

		/**
			* Изменить id сущности
			* @param Integer $id новый id сущности
		*/
		protected function setId($id) {
			$this->id = (int) $id;
		}

		/**
			* Узнать, есть ли несохраненные модификации
			* @return Boolean true если есть несохраненные изменения
		*/
		public function getIsUpdated() {
			return $this->is_updated;
		}

		/**
			* Установить флаг "изменен"
			* @param Boolean $is_updated=true значение флага "изменен"
		*/
		public function setIsUpdated($is_updated = true) {
			$this->is_updated 	    = (bool) $is_updated;
			$this->bNeedUpdateCache = $this->is_updated;
		}
		
		public function beforeSerialize() {}
		public function afterSerialize() {}
		
		public function afterUnSerialize() {}

		/**
			* Загрузить необходимую информацию о сущности из БД. Требует реализации в дочернем классе.
		*/
		abstract protected function loadInfo();

		/**
			* Сохранить в БД информацию о сущности. Требует реализации в дочернем классе.
		*/
		abstract protected function save();

		/**
			* Применить совершенные изменения, если они есть. Если нет, вернет false
			* @return Boolean true если изменения примененые и при этом не возникло ошибок
		*/
		public function commit() {
			if ($this->is_updated) {
				$this->disableCache();
				$res = $this->save();

				if (cacheFrontend::getInstance()->getIsConnected()) {
					// обновляем инфу об объекте из базы для корректного сохранения не применившихся свойств в memcached
					$this->update();
				} else {
					$this->setIsUpdated(false);
				}

				return $res;
			} else {
				return false;
			}
		}

		/**
			* Заново прочитать все данные сущности из БД. Внесенные изменения скорее всего будут утеряны
			* @return Boolean результат операции зависит от реализации loadInfo() в дочернем классе
		*/
		public function update() {
			$res = $this->loadInfo();
			$this->setIsUpdated(false);
			$this->updateCache();
			return $res;
		}

		/**
			* Отфильтровать значения, попадающие в БД
			* @param String $string значение
			* @return String отфильтрованное значение
		*/
		public static function filterInputString($string) {
			$string = l_mysql_real_escape_string($string);
			return $string;

		}
		
		/**
			* Magic method
			* @return id объекта
		*/
		public function __toString() {
			return (string) $this->getId();
		}
		
		/**
			* Обновить версию сущности, которая находится в кеше
		*/
		protected function updateCache() {
			cacheFrontend::getInstance()->save($this, $this->store_type);
		}
		
		/**
			* Отключить каширование повторных sql-запросов
		*/
		protected function disableCache() {
			if(!defined('MYSQL_DISABLE_CACHE')) {
				if(get_class($this) === "umiObjectProperty") {
					return;
				}
				define('MYSQL_DISABLE_CACHE', '1');
			}
		}

		/**
			* Перевести строковую константу по ее ключу
			* @param String $label ключ строковой константы
			* @return String значение константы в текущей локали
		*/
		protected function translateLabel($label) {
			$prefix = "i18n::";
			if(substr($label, 0, strlen($prefix)) == $prefix) {
				$str = getLabel(substr($label, strlen($prefix)));
			} else {
				$str = getLabel($label);
			}
			return (is_null($str)) ? $label : $str;
		}

		/**
			* Получить ключ строковой константы, если она определена, либо вернуть саму строку
			* @param String $str строка, для которых нужно определить ключ
			* @param String $pattern="" префикс ключа, используется внутри системы
			* @return String ключ константы, либо параметр $str, если такого значение нет в списке констант
		*/
		protected function translateI18n($str, $pattern = "") {
			$label = getI18n($str, $pattern);
			return (is_null($label)) ? $str : $label;
		}
	};
?>
