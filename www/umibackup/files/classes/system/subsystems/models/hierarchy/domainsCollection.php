<?php
/**
	* Служит для управления доменами (класс domain) в системе. Синглтон, экземпляр коллекции можно получить через статический метод getInstance.
	* Участвует в роутинге урлов в условиях мультидоменности.
*/
	class domainsCollection extends singleton implements iSingleton, iDomainsCollection {
		private $domains = Array(), $def_domain;

		/**
			* Конструктор, подгружает список доменов
		*/
		protected function __construct() {
			$this->loadDomains();
		}

		/**
			* Получить экземпляр коллекции
			* @return domainsCollection экземпляр коллекции
		*/
		public static function getInstance() {
			return parent::getInstance(__CLASS__);
		}

		/**
			* Добавить в систему новый домен
			* @param String $host адрес домен (хост)
			* @param Integer $lang_id id языка (класс lang) по умолчанию для этого домена
			* @param Boolean $is_default=false если true, то этот домен станет основным. Будтье осторожны, при этом может испортиться лицензия
			* @return Integer id созданного домена
		*/
		public function addDomain($host, $lang_id, $is_default = false) {
			if($domain_id = $this->getDomainId($host)) {
				return $domain_id;
			} else {
				cacheFrontend::getInstance()->flush();

				$sql = "INSERT INTO cms3_domains VALUES()";
				l_mysql_query($sql);

				$domain_id = l_mysql_insert_id();

				$this->domains[$domain_id] = $domain = new domain($domain_id);
				$domain->setHost($host);
				$domain->setIsDefault($is_default);
				$domain->setDefaultLangId($lang_id);
				if($is_default) $this->setDefaultDomain($domain_id);
				$domain->commit();

				return $domain_id;
			}
		}

		/**
			* Установить домен по умолчанию
			* @param Integer $domain_id id домена, который нужно сделать доменом по умолчанию
		*/
		public function setDefaultDomain($domain_id) {
			if($this->isExists($domain_id)) {
				cacheFrontend::getInstance()->flush();

				$sql = "UPDATE cms3_domains SET is_default = '0' WHERE is_default = '1'";
				l_mysql_query($sql);

				if($def_domain = $this->getDefaultDomain()) {
					$def_domain->setIsDefault(false);
					$def_domain->commit();
				}

				$this->def_domain = $this->getDomain($domain_id);
				$this->def_domain->setIsDefault(true);
				$this->def_domain->commit();
			} else return false;
		}

		/**
			* Удалить домен из системы
			* @param Integer $domain_id id домена, который необходимо удалить
			* @return Boolean true, если удалось удалить домен
		*/
		public function delDomain($domain_id) {
			if($this->isExists($domain_id)) {
				$domain = $this->getDomain($domain_id);
				$domain->delAllMirrows();
				cacheFrontend::getInstance()->flush();

				if($domain->getIsDefault()) {
					$this->def_domain = false;
				}

				unset($domain);
				unset($this->domains[$domain_id]);


				$sql = "DELETE FROM cms3_hierarchy WHERE domain_id = '{$domain_id}'";
				l_mysql_query($sql);

				$sql = "DELETE FROM cms3_domains WHERE id = '{$domain_id}'";
				l_mysql_query($sql);

				return true;
			} else throw new coreException("Domain #{$domain_id} doesn't exists.");
		}

		/**
			* Получить экземпляр домена (класс domain)
			* @param Integer $domain_id id домена, который необходимо получить
			* @return domain экземпляр домен или false в случае неудачи
		*/
		public function getDomain($domain_id) {
			return $this->isExists($domain_id) ? $this->domains[$domain_id] : false;
		}

		/**
			* Получить домен по умолчанию
			* @return domain экземпляр класса domain или false
		*/
		public function getDefaultDomain() {
			return ($this->def_domain) ? $this->def_domain : false;
		}

		/**
			* Получить список доменов в системе
			* @return Array массив, состоящий из экземпляров класса domain
		*/
		public function getList() {
			return $this->domains;
		}

		/**
			* Проверить, существует ли домен $domain_id в системе
			* @param id $domain_id домена
			* @return Boolean true, если домен существует
		*/
		public function isExists($domain_id) {
			return (bool) @array_key_exists($domain_id, $this->domains);
		}

		/**
			* Получть id домена по его хосту (адресу домена)
			* @param String $host адрес домена
			* @param Boolean $user_mirrow=true если параметр равен true, то поиск будет осуществляться в т.ч. и во всех зеркалах домена
			* @return Integer id домена, либо false если домен с таким хостом не найден
		*/
		public function getDomainId($host, $use_mirrows = true) {
			foreach($this->domains as $domain) {
				if($domain->getHost() == $host) {
					return $domain->getId();
				} else {
					if($use_mirrows) {
						$mirrows = $domain->getMirrowsList();
						foreach($mirrows as $domainMirrow) {
							if($domainMirrow->getHost() == $host) {
								return $domain->getId();
							}
						}
					}
				}
			}
			return false;
		}

		/**
			* Загружает список доменов из БД в коллекцию
			* @return Boolean true, если операция прошла успешно
		*/
		private function loadDomains() {
			$cacheFrontend = cacheFrontend::getInstance();

			$domainIds = $cacheFrontend->loadData('domains_list');
			if(!is_array($domainIds)) {
				$sql = "SELECT id, host, is_default, default_lang_id FROM cms3_domains";
				$result = l_mysql_query($sql);

				$domainIds = array();
				while(list($domain_id) = $row = mysql_fetch_row($result)) {
				    $domainIds[$domain_id] = $row;
				}
				$cacheFrontend->saveData('domains_list', $domainIds, 3600);
			} else $row = false;

			foreach($domainIds as $domain_id => $row) {
				$domain = $cacheFrontend->load($domain_id, 'domain');
				if($domain instanceof domain == false) {
					try {
						$domain = new domain($domain_id, $row);
					} catch(privateException $e) {
						continue;
					}

					$cacheFrontend->save($domain, 'domain');
				}
				$this->domains[$domain_id] = $domain;

				if($domain->getIsDefault()) {
					$this->def_domain = $domain;
				}
			}

			return true;
		}
		
		public function clearCache() {
			$keys = array_keys($this->domains);
			foreach($keys as $key) unset($this->domains[$key]);			
			$this->domains = array();
			$this->loadDomains();
		}
	}
?>