<?php
	class systemDomainRestriction extends baseRestriction implements iNormalizeInRestriction, iNormalizeOutRestriction {
		protected $errorMessage = 'restriction-error-domain-id';
		
		public function validate($value) {
			$domainId = (int) $value;
			$domains = domainsCollection::getInstance();
			return ($domains->getDomain($domainId) instanceof iDomain);
		}
		
		public function normalizeOut($value) {
			if(is_numeric($value)) {
				$domain = selector::get('domain')->id($value);
				return ($domain instanceof iDomain) ? $domain->getHost() : $value;
			} else return $value;
		}
		
		public function normalizeIn($value) {
			$domain = null;
			if(is_numeric($value)) {
				$domain = selector::get('domain')->id($value);
			} else {
				$domain = selector::get('domain')->host($value);
			}
			return ($domain instanceof iDomain) ? (int) $domain->getId() : null;
		}
	};
?>