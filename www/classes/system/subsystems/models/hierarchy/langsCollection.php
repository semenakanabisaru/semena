<?php
/**
	* Используется для управления языками (класс lang), которые функционально представляют языковыми версиями сайта.
	* Класс является синглтоном, получить экземпляр класса можно через статический метод getInstance().
*/
	class langsCollection extends singleton implements iSingleton, iLangsCollection {
		private $langs = Array(),
			$def_lang;

		/**
			* Конструктор, подгружает список языков
		*/
		protected function __construct() {
			$this->loadLangs();
		}

		/**
			* Получить экземпляр коллекции
			* @return langsCollection экземпляр класса langsCollection
		*/
		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}

		/**
			* Загрузить список языков из БД
		*/
		private function loadLangs() {
			$cacheFrontend = cacheFrontend::getInstance();

			$langIds = $cacheFrontend->loadData('langs_list');
			if(!is_array($langIds)) {
				$sql = "SELECT id, prefix, is_default, title FROM cms3_langs ORDER BY id";
				$result = l_mysql_query($sql);
				$langIds = array();
				while(list($lang_id) = $row = mysql_fetch_row($result)) {
					$langIds[$lang_id] = $row;
				}
				$cacheFrontend->saveData('langs_list', $langIds, 3600);
			} else $row = false;

			foreach($langIds as $lang_id => $row) {
				$lang = $cacheFrontend->load($lang_id, 'lang');
				if($lang instanceof lang == false) {
					try {
						$lang = new lang($lang_id, $row);
					} catch (privateException $e) { continue; }

					$cacheFrontend->save($lang, 'lang');
				}

				$this->langs[$lang_id] = $lang;
				if($this->langs[$lang_id]->getIsDefault()) {
					$this->def_lang = $this->langs[$lang_id];
				}
			}
		}

		/**
			* Получить id языка (класс lang) по его префиксу
			* @param String $prefix префикс языка (его 2х-3х символьный код)
			* @return lang язык, либо false если языка с таким префиксом не существует
		*/
		public function getLangId($prefix) {
			foreach($this->langs as $lang) {
				if($lang->getPrefix() == $prefix) {
					return $lang->getId();
				}
			}
			return false;
		}

		/**
			* Создать новый язык
			* @param String $prefix префикс языка
			* @param String $title название языка
			* @param Boolean $is_default=false сделать языком по умолчанию (на данный момент не должно возникнуть необходимости ставить в true)
			* @return Integer id созданного языка, либо false
		*/
		public function addLang($prefix, $title, $is_default = false) {
			$this->disableCache();
			cacheFrontend::getInstance()->flush();

			if($lang_id = $this->getLangId($prefix)) {
				return $lang_id;
			}

			$sql = "INSERT INTO cms3_langs VALUES()";
			l_mysql_query($sql);
			$lang_id = l_mysql_insert_id();

			$lang = new lang($lang_id);

			$lang->setPrefix($prefix);
			$lang->setTitle($title);
			$lang->setIsDefault($is_default);

			$lang->commit();

			$this->langs[$lang_id] = &$lang;

			return $lang_id;
		}

		/**
			* Удалить язык с id $lang_id
			* @param id $lang_id языка, который необходимо удалить
			* @return Boolean true, если удаление прошло успешно
		*/
		public function delLang($lang_id) {
			$this->disableCache();
			cacheFrontend::getInstance()->flush();

			$lang_id = (int) $lang_id;

			if(!$this->isExists($lang_id)) return false;

			$sql = "DELETE FROM cms3_langs WHERE id = '{$lang_id}'";
			l_mysql_query($sql);
			unset($this->langs[$lang_id]);
		}

		/**
			* Получить язык (экземпляр касса lang) по его id
			* @param Integer $lang_id id языка
			* @return lang язык (экземпляр касса lang), либо false
		*/
		public function getLang($lang_id) {
			$lang_id = (int) $lang_id;
			return ($this->isExists($lang_id)) ? $this->langs[$lang_id] : false;
		}

		/**
			* Узнать, существует ли в системе язык с id $lang_id
			* @param Integer $lang_id id языка
			* @return Boolean true, если язык существует
		*/
		public function isExists($lang_id) {
			return (bool) @array_key_exists($lang_id, $this->langs);
		}

		/**
			* Получить список всех языков в системе
			* @return Array массив, значением которого являются экземпляры класса lang
		*/
		public function getList() {
			return $this->langs;
		}

		/**
			* Установить язык по умолчанию
			* @param Integer $lang_id id языка
		*/
		public function setDefault($lang_id) {
			if(!$this->isExists($lang_id)) return false;

			if($this->def_lang) {
				$this->def_lang->setIsDefault(false);
				$this->def_lang->commit();
			}

			$this->def_lang = $this->getLang($lang_id);
			$this->def_lang->setIsDefault(true);
			$this->def_lang->commit();
		}

		/**
			* Получить язык по умолчанию
			* @return lang экземпляр класса lang, либо false в случае неудачи
		*/
		public function getDefaultLang() {
			return ($this->def_lang) ? $this->def_lang : false;
		}

		/**
			* Получить список всех языков в системе в виде ассоциотивного массива
			* @return Array массив, где ключ это id языка, а значение - его название
		*/
		public function getAssocArray() {
			$res = array();

			foreach($this->langs as $lang) {
				$res[$lang->getId()] = $lang->getTitle();
			}

			return $res;
		}

		public function clearCache() {
			$keys = array_keys($this->langs);
			foreach($keys as $key) unset($this->langs[$key]);
			$this->langs = array();
			$this->loadLangs();
		}
	}
?>