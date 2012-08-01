<?php
	class templateWrapper extends translatorWrapper {
		public function translate($data) {
			return $this->translateData($data);
		}
		
		protected function translateData(iTemplate $template) {
			$resultArray = Array();
			$resultArray['attribute:id'] = $template->getId();
			$resultArray['attribute:title'] = $template->getTitle();
			$resultArray['attribute:filename'] = $template->getFilename();
			$resultArray['attribute:domain-id'] = $template->getDomainId();
			$resultArray['attribute:lang-id'] = $template->getLangId();

			if($template->getIsDefault()) {
				$resultArray['attribute:is-default'] = true;
			}
			
			return $resultArray;
		}
	};
?>