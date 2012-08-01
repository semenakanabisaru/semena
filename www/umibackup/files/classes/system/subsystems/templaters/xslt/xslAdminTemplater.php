<?php
	class xslAdminTemplater extends xslTemplater implements iTemplater {
		protected
			$data,
			$domDocument = false,
			$xmlTranslator = false,
			$file_path,
			$folder_path;
		public $currentEditElementId = false;
		
		protected function __construct() {
		}

		public static function getInstance() {
			return singleton::getInstance(__CLASS__);
		}

		public function setDataSet($data) {
			$this->data = $data;
		}
	
		protected function initGlobalVariables() {
			$cmsController = cmsController::getInstance();
			$permissions = permissionsCollection::getInstance();
			$domains = domainsCollection::getInstance();
			
			$this->globalVariables['attribute:module'] = $current_module = $cmsController->getCurrentModule();
			$this->globalVariables['attribute:method'] = $current_method = $cmsController->getCurrentMethod();
			
			if(defined('CURRENT_VERSION_LINE') and CURRENT_VERSION_LINE=='demo') {
				$this->globalVariables['attribute:demo'] = 1;
			}
			$this->globalVariables['attribute:lang'] = $cmsController->getCurrentLang()->getPrefix();
			$this->globalVariables['attribute:lang-id'] = $cmsController->getCurrentLang()->getId();
			$this->globalVariables['attribute:pre-lang'] = $cmsController->getPreLang();
			$this->globalVariables['attribute:domain'] = $cmsController->getCurrentDomain()->getHost();
			$this->globalVariables['attribute:domain-id'] = $cmsController->getCurrentDomain()->getId();
			$this->globalVariables['attribute:session-lifetime'] = SESSION_LIFETIME;
			$this->globalVariables['attribute:system-build'] = regedit::getInstance()->getVal("//modules/autoupdate/system_build");
			
			if(!is_null($domain_floated = getRequest('domain'))) {
				$this->globalVariables['attribute:domain-floated'] = $domain_floated;
			} else {
				if($this->currentEditElementId) {
					$element = umiHierarchy::getInstance()->getElement($this->currentEditElementId);
					if($element instanceof umiHierarchyElement) {
						$domain_id = $element->getDomainId();
						$domain = $domains->getDomain($domain_id);
						if($domain instanceof iDomain) {
							$this->globalVariables['attribute:domain-floated'] = $domain_floated = $domain->getHost();
						}
					}
				} else {
					$this->globalVariables['attribute:domain-floated'] = $this->globalVariables['attribute:domain'];
				}
			}
			
			$this->globalVariables['attribute:domain-floated-id'] = $domains->getDomainId($domain_floated);
			
			$this->globalVariables['attribute:referer-uri'] = $cmsController->getCalculatedRefererUri();
			$this->globalVariables['attribute:user-id'] = $permissions->getUserId();
			$this->globalVariables['attribute:interface-lang'] = ulangStream::getLangPrefix();

			if($requestUri = getServer('REQUEST_URI')) {
				$requestUriInfo = parse_url($requestUri);
				$requestUri = getArrayKey($requestUriInfo, 'path');
				$queryParams = getArrayKey($requestUriInfo, 'query');
				if($queryParams) {
					parse_str($queryParams, $queryParamsArr);
					if(isset($queryParamsArr['p'])) unset($queryParamsArr['p']);
					if(isset($queryParamsArr['xmlMode'])) unset($queryParamsArr['xmlMode']);
					
					$queryParams = http_build_query($queryParamsArr, '', '&');
					if($queryParams) $requestUri .= '?' . $queryParams;
				}
				$this->globalVariables['attribute:request-uri'] = $requestUri;
			}
			
			$this->globalVariables['attribute:edition'] = CURRENT_VERSION_LINE;
			$this->globalVariables['attribute:disableTooManyChildsNotification'] = (int)mainConfiguration::getInstance()->get('system', 'disable-too-many-childs-notification');

			$this->globalVariables['data'] = $this->data;
		}

	};
?>