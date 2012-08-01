<?php
	class langWrapper extends translatorWrapper {
		public function translate($data) {
			return $this->translateData($data);
		}
		
		protected function translateData(iLang $lang) {
			$resultArray = array();
			$resultArray['attribute:id'] = $lang->getId();
			$resultArray['attribute:prefix'] = $lang->getPrefix();
			if ($lang->getIsDefault()) {
				$resultArray['attribute:is-default'] = 1;
			}
			
			$cmsController = cmsController::getInstance();
			$langId = $cmsController->getCurrentLang()->getId();
			if($langId == $lang->getId()) {
				$resultArray['attribute:is-current'] = 1;
			}
			
			$resultArray['node:title'] = $lang->getTitle();

			return $resultArray;
		}
	};
?>