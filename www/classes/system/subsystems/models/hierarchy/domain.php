<?php
/**
	* Предоставляет доступ свойствам домена и зеркалам доменов в системе
*/
	class domain extends umiEntinty implements iUmiEntinty, iDomainMirrow, iDomain {
		private	$host, $default_lang_id, $mirrows = Array();
		protected $store_type = "domain";

		/**
			* Загружает свойства домена из БД
			* @return Boolean true, если все прошло нормально
		*/
		protected function loadInfo($row = false) {
			if($row === false) {
				$sql = "SELECT id, host, is_default, default_lang_id FROM cms3_domains WHERE id = '{$this->id}'";
				$result = l_mysql_query($sql);

				$row = mysql_fetch_row($result);
			}

			if(list($id, $host, $is_default, $default_lang_id) = $row) {
				$this->host = $host;
				$this->is_default = (bool) $is_default;
				$this->default_lang_id = (int) $default_lang_id;

				return $this->loadMirrows();
			} else {
				return false;
			}
		}

		/**
			* Получить адрес домена (хост)
			* @return String адрес домена
		*/
		public function getHost() {
			return $this->host;
		}

		/**
			* Узнать, является ли этот домен доменом по умолчанию
			* @return Boolean true, если установлен флаг "по умолчанию"
		*/
		public function getIsDefault() {
			return $this->is_default;
		}

		/**
			* Изменить хост (адрес) домена
			* @param String $host адрес домена
		*/
		public function setHost($host) {
			$this->host = $this->filterHostName($host);
			$this->setIsUpdated();
		}

		/**
			* Установить флаг домена "по умолчанию".
			* Используется системой, в пользовательском коде нужно воспользоваться методом domainsCollection::setDefaultDomain()
			* @param Boolean $is_default флаг "по умолчанию"
		*/
		public function setIsDefault($is_default) {
			$this->is_default = (bool) $is_default;
			$this->setIsUpdated();
		}

		/**
			* Получить id языка (класс lang), который используется по умолчанию для этого домена
			* @return Integer id языка
		*/
		public function getDefaultLangId() {
			return $this->default_lang_id;
		}

		/**
			* Установить для домена язык по умолчанию
			* @param Integer $lang_id id языка (класс lang) по умолчанию
			* @return Boolean true, если операция прошла успешно
		*/
		public function setDefaultLangId($lang_id) {
			if(langsCollection::getInstance()->isExists($lang_id)) {
				$this->default_lang_id = $lang_id;
				$this->setIsUpdated();

				return true;
			} else throw new coreException("Language #{$lang_id} doesn't exists");
		}

		/**
			* Добавить зеркало (класс domainMirrow) для домена
			* @param String $mirrow_host хост (адрес) зеркала
			* @return Integer id созданного зеркала
		*/
		public function addMirrow($mirrow_host) {
			if($mirrow_id = $this->getMirrowId($mirrow_host)) {
				return $mirrow_id;
			} else {
				$this->setIsUpdated();

				$sql = "INSERT INTO cms3_domain_mirrows (rel) VALUES('{$this->id}')";
				l_mysql_query($sql);

				$mirrow_id = l_mysql_insert_id();

				$mirrow = new domainMirrow($mirrow_id);
				$mirrow->setHost($mirrow_host);
				$mirrow->commit();

				$this->mirrows[$mirrow_id] = $mirrow;

				return $mirrow_id;
			}
		}

		/**
			* Удалить зеркало домена, используея его id
			* @param Integer $mirrow_id id зеркала
			* @return Boolean true, если удаление прошло успешно
		*/
		public function delMirrow($mirrow_id) {
			if($this->isMirrowExists($mirrow_id)) {
				$this->setIsUpdated();

				$sql = "DELETE FROM cms3_domain_mirrows WHERE id = '{$mirrow_id}'";
				l_mysql_query($sql);

				unset($this->mirrows[$mirrow_id]);
				return true;
			} else return false;
		}

		/**
			* Удалить все зеркала домена
			* @return Boolean true, если удаление прошло без ошибок
		*/
		public function delAllMirrows() {
			$this->setIsUpdated();

			$sql = "DELETE FROM cms3_domain_mirrows WHERE rel = '{$this->id}'";
			l_mysql_query($sql);

			return true;
		}

		/**
			* Определить id зеркала домена по его хосту (адресу)
			* @param String $mirrow_host хост (адрес)
			* @return domainMirrow экземпляр класса domainMirrow, либо false, если зеркало не найдено
		*/
		public function getMirrowId($mirrow_host) {
			foreach($this->mirrows as $mirrow) {
				if($mirrow->getHost() == $mirrow_host) {
					return $mirrow->getId();
				}
			}
			return false;
		}

		/**
			* Получить зеркало домена (экземпляр класса domainMirrow) по его id
			* @param Integer $mirrow_id id зеркала домена
			* @return domainMirrow, либо false
		*/
		public function getMirrow($mirrow_id) {
			if($this->isMirrowExists($mirrow_id)) {
				return $this->mirrows[$mirrow_id];
			} else {
				return false;
			}
		}

		/**
			* Проверить, существует ли у домена зеркало с id $mirrow_id
			* @param $mirrow_id
			* @return Boolean true если существует
		*/
		public function isMirrowExists($mirrow_id) {
			return (bool) array_key_exists($mirrow_id, $this->mirrows);
		}

		/**
			* Получить список всех зеркал домена
			* @return Array массив, состоящий их экземпляров класса domainMirrow
		*/
		public function getMirrowsList() {
			return $this->mirrows;
		}

		/**
			* Загрузить все зеркала из БД
		*/
		private function loadMirrows() {
			$sql = "SELECT id, host FROM cms3_domain_mirrows WHERE rel = '{$this->id}'";
			$result = l_mysql_query($sql);

			while(list($mirrow_id) = $row = mysql_fetch_row($result)) {
				try {
					$this->mirrows[$mirrow_id] = new domainMirrow($mirrow_id, $row);
				} catch(privateException $e) {
					continue;
				}
			}

			return true;
		}

		/**
			* Сохранить изменения, сделанные с этим доменом
		*/
		protected function save() {
			$host = self::filterInputString($this->host);
			$is_default = (int) $this->is_default;
			$default_lang_id = (int) $this->default_lang_id;

			$sql = "UPDATE cms3_domains SET host = '{$host}', is_default = '{$is_default}', default_lang_id = '{$default_lang_id}' WHERE id = '{$this->id}'";
			l_mysql_query($sql);
			return true;
		}

		public static function filterHostName($host) {
			return preg_replace("/([^A-z0-9\-А-яёЁ\.:]+)|[\^_\\\\]/u", "", $host);
		}
	};
?>
