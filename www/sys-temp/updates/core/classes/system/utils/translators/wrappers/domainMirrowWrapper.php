<?php
	class domainMirrowWrapper extends translatorWrapper {
		public function translate($data) {
			return $this->translateData($data);
		}
		
		protected function translateData(iDomainMirrow $domainMirrow) {
			return array(
				'attribute:id'		=> $domainMirrow->getId(),
				'attribute:host'	=> $domainMirrow->getHost(),
			);
		}
	};
?>