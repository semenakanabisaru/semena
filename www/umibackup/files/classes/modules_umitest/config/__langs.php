<?php
	abstract class __langs_config extends baseModuleAdmin {
		public function langs() {
			$mode = getRequest("param0");

			if($mode == "do" && !is_demo()) {
				$this->saveEditedList("langs");
				$this->chooseRedirect();
			}


			$langs = langsCollection::getInstance()->getList();

			$this->setDataType("list");
			$this->setActionType("modify");

			$data = $this->prepareData($langs, "langs");

			$this->setData($data, sizeof($langs));
			return $this->doData();
		}

		public function lang_del() {
			$langId = (int) getRequest('param0');
			$langs = langsCollection::getInstance();
			
			if(sizeof($langs->getList()) == 1) {
				throw new publicAdminException(getLabel('error-minimum-one-lang-required'));
			}
			
			if ($langs->getDefaultLang()->getId() == $langId) {
				throw new publicAdminException(getLabel('error-try-delete-default-language'));
			}
			
			$cmsController = cmsController::getInstance();
			$currentLangId = $cmsController->getCurrentLang()->getId();
			
			$url = '/admin/config/langs/';
			if($currentLangId != $langId) {
				$url = $this->pre_lang . $url;
			}
			
			if(!is_demo()) {
				 $langs->delLang($langId);
			}
			$this->chooseRedirect($url);
		}
	};
?>