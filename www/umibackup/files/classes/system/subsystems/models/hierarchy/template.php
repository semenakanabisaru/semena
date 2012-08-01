<?php
/**
	* Предоставляет доступ к свойствам шаблона дизайна
*/
	class template extends umiEntinty implements iUmiEntinty, iTemplate {
		private $name, $filename, $type, $title, $domain_id, $lang_id, $is_default;
		protected $store_type = "template";

		/**
			* Получить название шаблона дизайна
			* @return String название шаблона дизайна
		*/
		public function getName() {
			return $this->name;
		}

		/**
			* Получить название файла шаблона дизайна
			* @return String название файла шаблона дизайна
		*/
		public function getFilename() {
			return $this->filename;
		}

		/**
			* Получить тип шаблона дизайна
			* @return String тип шаблона дизайна
		*/
		public function getType() {
			return $this->type;
		}

		/**
			* Получить название шаблона дизайна
			* @return String название шаблона дизайна
		*/
		public function getTitle() {
			return $this->title;
		}

		/**
			* Получить id домена, к которому привязан шаблон
			* @return Integer id домена (класс domain)
		*/
		public function getDomainId() {
			return $this->domain_id;
		}

		/**
			* Получить id языка, к которому привязан шаблон
			* @return Integer id язык (класс lang)
		*/
		public function getLangId() {
			return $this->lang_id;
		}

		/**
			* Узнать, является ли данный шаблон шаблоном по умолчанию
			* @return Boolean true, если шаблон является шаболоном по умолчанию
		*/
		public function getIsDefault() {
			return $this->is_default;
		}

		/**
			* Изменить название шаблона
			* @param String $name название шаблона
		*/
		public function setName($name) {
			$this->name = $name;
			$this->setIsUpdated();
		}

		/**
			* Изменить название файла шаблона
			* @param String $filename название файла шаблона
		*/
		public function setFilename($filename) {
			$this->filename = $filename;
			$this->setIsUpdated();
		}

		/**
			* Изменить название шаблона дизайна
			* @param String $title название шаблона
		*/
		public function setTitle($title) {
			$this->title = $title;
			$this->setIsUpdated();
		}

		/**
			* Изменить тип шаблона
			* @param String $filename тип шаблона
		*/
		public function setType($type) {
			$this->type = $type;
			$this->setIsUpdated();
		}

		/**
			* Изменить домен, к которому привязан шаблон дизайна
			* @param Integer $domain_id id домена (класс domain)
			* @return Boolean true в случае успеха
		*/
		public function setDomainId($domain_id) {
			$domains = domainsCollection::getInstance();
			if($domains->isExists($domain_id)) {
				$this->domain_id = (int) $domain_id;
				$this->setIsUpdated();

				return true;
			} else {
				return false;
			}
		}

		/**
			* Изменить язык, к которому привязан шаблон
			* @param Integer $lang_id id языка (класс lang)
			* @return Boolean true в случае успеха
		*/
		public function setLangId($lang_id) {
			$langs = langsCollection::getInstance();
			if($langs->isExists($lang_id)) {
				$this->lang_id = (int) $lang_id;
				$this->setIsUpdated();

				return true;
			} else {
				return false;
			}
		}

		/**
			* Изменить флаг "по умолчанию"
			* @param Boolean $is_default значение флага "по умолчанию"
		*/
		public function setIsDefault($is_default) {
			$this->is_default = (bool) $is_default;
			$this->setIsUpdated();
		}
		
		/**
			* Получить список страниц, которые используют этот шаблон
			* @return Array массив, в котором каждое значение тоже массив, где 0 индекс - id страницы (класс umiHierarchyElement), 1 индекс - название страницы
		*/
		public function getUsedPages() {
			$sql = "SELECT h.id, o.name FROM cms3_hierarchy h, cms3_objects o WHERE h.tpl_id = '{$this->id}' AND o.id = h.obj_id AND h.is_deleted = '0' AND h.domain_id = '{$this->domain_id}'";
			$result = l_mysql_query($sql);

			$res = array();
			while(list($id, $name) = mysql_fetch_row($result)) {
				$res[] = Array($id, $name);
			}
			return $res;
		}
		
		/**
			* Привязать страницы сайта к шаблону
			* @param Array $pages массив, в котором каждое значение тоже массив, где 0 индекс - id страницы (класс umiHierarchyElement), 1 индекс - название страницы
			* @return Boolean true в случае, если не возникло ошибок
		*/
		public function setUsedPages($pages) {
			if(is_null($pages)) return false;

			$default_tpl_id = templatesCollection::getInstance()->getDefaultTemplate($this->domain_id, $this->lang_id)->getId();

			$sql = "UPDATE cms3_hierarchy SET tpl_id = '{$default_tpl_id}' WHERE tpl_id = '{$this->id}' AND is_deleted = '0' AND domain_id = '{$this->domain_id}'";
			l_mysql_query($sql);
			
			$cacheFrontend = cacheFrontend::getInstance();
			$cacheFrontend->flush();
			
			$hierarchy = umiHierarchy::getInstance();
			
			if(!is_array($pages)) return false;
			
			if(is_array($pages)&&!empty($pages)) {
                foreach($pages as $element_id) {
				    $page = $hierarchy->getElement($element_id);
				    if($page instanceof iUmiHierarchyElement) {
					    $page->setTplId($this->id);
					    $page->commit();
					    unset($page);
					    $hierarchy->unloadElement($element_id);
				    }
			    }
            }
			return true;
		}

		/**
			* Загрузить информацию о шаблоне из БД
			* @return Boolean true, если не произошло ошибки
		*/
		protected function loadInfo($row = false) {
			if($row === false) {
				$sql = "SELECT id, name, filename, type, title, domain_id, lang_id, is_default FROM cms3_templates WHERE id = '{$this->id}'";
				$result = l_mysql_query($sql);
				$row = mysql_fetch_row($result);
			}

			if(list($id, $name, $filename, $type, $title, $domain_id, $lang_id, $is_default) = $row) {
				$this->name = $name;
				$this->filename = $filename;
				$this->type = $type;
				$this->title = $title;
				$this->domain_id = (int) $domain_id;
				$this->lang_id = (int) $lang_id;
				$this->is_default = (bool) $is_default;

				return true;
			} else return false;
		}

		/**
			* Сохранить изменения в БД
			* @return Boolean true, если не возникло ошибки
		*/
		protected function save() {
			$name = self::filterInputString($this->name);
			$filename = self::filterInputString($this->filename);
			$type = self::filterInputString($this->type);
			$title = self::filterInputString($this->title);
			$domain_id = (int) $this->domain_id;
			$lang_id =  (int) $this->lang_id;
			$is_default = (int) $this->is_default;

			$sql = "UPDATE cms3_templates SET name = '{$name}', filename = '{$filename}', type = '{$type}', title = '{$title}', domain_id = '{$domain_id}', lang_id = '{$lang_id}', is_default = '{$is_default}' WHERE id = '{$this->id}'";
			$result = l_mysql_query($sql);
			return true;
		}
	}
?>