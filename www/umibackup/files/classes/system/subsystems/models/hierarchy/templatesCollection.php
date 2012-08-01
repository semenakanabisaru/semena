<?php
/**
	* Управляет шаблонами дизайна (класс template) в системе.
	* Синглтон, экземпляр коллекции можно получить через статический метод getInstance()
*/
	class templatesCollection extends singleton implements iSingleton, iTemplatesCollection {
		private $templates = Array(), $def_template;

		/**
			* Конструктор, при вызове загружает список шаблонов
		*/
		protected function __construct() {
			$this->loadTemplates();
		}

		/**
			* Получить экземпляр коллекции
			* @return templatesCollection экземпляр класса templatesCollection
		*/
		public static function getInstance() {
			return parent::getInstance(__CLASS__);
		}

		/**
			* Добавить новый шаблон дизайна (класс template)
			* @param String $filename название файла, который содержит шаблон дизайна
			* @param String $title название шаблона
			* @param Integer $domain_id=false id домена (класс domain), для которого создается шаблон. Если не указан, используется домен по умолчанию
			* @param Integer $lang_id=false id языка (класс lang), для которого создается шаблон. Если не указан, используется язык по умолчанию
			* @param Boolean $is_default=false если true, то шаблон станет шаблоном по умолчанию для комбинации домена /языка $domain_id/$lang_id
			* @return Integer id созданного шаблона
		*/
		public function addTemplate($filename, $title, $domain_id = false, $lang_id = false, $is_default = false) {
			$this->disableCache();
			cacheFrontend::getInstance()->flush();
		
			$domains = domainsCollection::getInstance();
			$langs = langsCollection::getInstance();

			if(!$domains->isExists($domain_id)) {
				if($domains->getDefaultDomain()) {
					$domain_id = $domains->getDefaultDomain()->getId();
				} else {
					return false;
				}
			}

			if(!$langs->isExists($lang_id)) {
				if($langs->getDefaultLang()) {
					$lang_id = $langs->getDefaultLang()->getId();
				} else {
					return false;
				}
			}

			$sql = "INSERT INTO cms3_templates VALUES()";
			$result = l_mysql_query($sql);

			$template_id = l_mysql_insert_id();

			$template = new template($template_id);
			$template->setFilename($filename);
			$template->setTitle($title);
			$template->setDomainId($domain_id);
			$template->setLangId($lang_id);
			$template->setIsDefault($is_default);

			if($is_default) {
				$this->setDefaultTemplate($template_id);
			}
			$template->commit();


			$this->templates[$template_id] = $template;

			return $template_id;
		}

		/**
			* Установить шаблон шаблоном по умолчанию для комбинации домена/языка
			* @param Integer $domain_id=false id домена (класс domain)
			* @param Integer $lang_id=false id языка (класс lang)
			* @return Boolean true, если не возникло ошибок
		*/
		public function setDefaultTemplate($template_id, $domain_id = false, $lang_id = false) {
			if($domain_id == false) $domain_id = domainsCollection::getInstance()->getDefaultDomain()->getId();	
			if($lang_id ==false) $lang_id = cmsController::getInstance()->getCurrentLang()->getId();
			
			if(!$this->isExists($template_id)) {
				return false;
			}
			
			$templates = $this->getTemplatesList($domain_id,$lang_id);
			foreach ($templates as $template) {
				if($template_id == $template->getId()) {
					$template->setIsDefault(true);					
				}
				else {
					$template->setIsDefault(false);
				}
				$template->commit();
			}
			return true;
			
			if(!($template = $this->getTemplate($templateId))) {
				return false;
			}

			if($this->def_template) {
				$this->def_template->setIsDefault(false);
				$this->def_template->commit();
			}

			$this->def_template = $template;
			$this->def_template->setIsDefault(true);
			$this->def_template->commit();

			return true;
		}

		/**
			* Удалить шаблон дизайна
			* @param Integer $template_id id шаблона дизайна
			* @return Boolean true, если удаление прошло успешно
		*/
		public function delTemplate($template_id) {
			$this->disableCache();
			cacheFrontend::getInstance()->flush();
		
			if($this->isExists($template_id)) {
				if($this->templates[$template_id]->getIsDefault()) {
					unset($this->def_template);
				}
				unset($this->templates[$template_id]);

				$o_deftpl = $this->getDefaultTemplate();
				if (!$o_deftpl || $o_deftpl->getId() == $template_id) return false;

				$upd_qry = "UPDATE cms3_hierarchy SET tpl_id = '".$o_deftpl->getId()."' WHERE tpl_id='{$template_id}'";
				l_mysql_query($upd_qry);

				$sql = "DELETE FROM cms3_templates WHERE id = '{$template_id}'";
				l_mysql_query($sql);
				
				return true;

			} else return false;
		}

		/**
			* Получить список всех шаблонов дизайна для комбинации домен/язык
			* @param Integer $domain_id id домена
			* @param Integer $lang_id id  языка
			* @return Array массив, состоящий из экземпляров класса template
		*/
		public function getTemplatesList($domain_id, $lang_id) {
			$res = array();

			foreach($this->templates as $template) {
				if($template->getDomainId() == $domain_id && $template->getLangId() == $lang_id) {
					$res[] = $template;
				}
			}

			return $res;
		}

		/**
			* Получить шаблон дизайна по умолчанию для комбинации домен/язык
			* @param Integer $domain_id=false id домена. Если не указан, берется домен по умолчанию.
			* @param Integer $lang_id=false id языка. Если не указан, берется язык по умолчанию.
			* @return template экземпляр класса template, либо false если шаблон дизайна не найден.
		*/
		public function getDefaultTemplate($domain_id = false, $lang_id = false) {
			if($domain_id == false) $domain_id = cmsController::getInstance()->getCurrentDomain()->getId();	
			if($lang_id == false) $lang_id = cmsController::getInstance()->getCurrentLang()->getId();

			$templates = $this->getTemplatesList($domain_id, $lang_id);
			foreach($templates as $template) {
				if($template->getIsDefault() == true) {
					return $template;
				}
			}
			
			//We have no default template, but something must be shown
			if(sizeof($templates)) {
				$first_template = $templates[0];
				$this->setDefaultTemplate($first_template->getId(), $domain_id, $lang_id);
				return $first_template;
			}
			return false;
		}

		public function getCurrentTemplate() {
			$controller = cmsController::getInstance();
			if ($element = umiHierarchy::getInstance()->getElement($controller->getCurrentElementId())) {
				$template = $this->getTemplate($element->getTplId());
			}
			elseif ($methodTemplateId = $this->getHierarchyTypeTemplate($controller->getCurrentModule(), $controller->getCurrentMethod())) {
				$template = $this->getTemplate($methodTemplateId);
			}
			else $template = $this->getDefaultTemplate();

			return $template;
		}

		public function getHierarchyTypeTemplate($module, $method) {
			$config = mainConfiguration::getInstance();
			$id = $config->get("templates", "{$module}.{$method}");
			return $this->isExists($id) ? $id : false;
		}

		/**
			* Получить шаблон дизайна по его id
			* @param Integer $template_id id шаблона дизайна
			* @return template шаблон дизайна, экземпляр класса template, либо false если не существует шаблона с id $template_id
		*/
		public function getTemplate($template_id) {
			return ($this->isExists($template_id)) ? $this->templates[$template_id] : false;
		}

		/**
			* Проверить, существует ли шаблон дизайна с id $template_id
			* @param Integer $template_id id шаблона дизайна
			* @return Boolean true, если шаблон существует
		*/
		public function isExists($template_id) {
			return (bool) @array_key_exists($template_id, $this->templates);
		}

		/**
			* Загрузить список всех шаблонов дизайна в системе из БД
			* @return Boolean false, если возникла ошибка
		*/
		private function loadTemplates() {
			$cacheFrontend = cacheFrontend::getInstance();
			
			$templateIds = $cacheFrontend->loadData('templates_list');
			if(!is_array($templateIds)) {
				$sql = "SELECT id, name, filename, type, title, domain_id, lang_id, is_default FROM cms3_templates";
				$result = l_mysql_query($sql);
				$templateIds = array();
				while(list($template_id) = $row = mysql_fetch_row($result)) {
					$templateIds[$template_id] = $row;
				}
				$cacheFrontend->saveData('templates_list', $templateIds, 3600);
			} else $row = false;

			foreach($templateIds as $template_id => $row) {
				 $template = $cacheFrontend->load($template_id, "template");
				if($template instanceof template == false) {
					try {
						$template = new template($template_id, $row);
					} catch (privateException $e) { continue; }

					$cacheFrontend->save($template, "template");
				}
				$this->templates[$template_id] = $template;

				if($template->getIsDefault()) {
					$this->def_template = $template;
				}
			}
			return true;
		}
		
		public function clearCache() {
			$keys = array_keys($this->templates);
			foreach($keys as $key) unset($this->templates[$key]);			
			$this->templates = array();
			$this->loadTemplates();
		}
	}
?>
