<?php
	class domainWrapper extends translatorWrapper {
		public function translate($data) {
			return $this->translateData($data);
		}

		protected function translateData(iDomain $domain) {
			return array(
				'attribute:id'		=> $domain->getId(),
				'attribute:host'	=> $domain->getHost(),
				'attribute:lang-id'	=> $domain->getDefaultLangId(),
				'attribute:is-default'=> $domain->getIsDefault()
			);
		}
	};
?>