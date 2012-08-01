<?php
	class seo extends def_module {
		public function __construct() {
        	        parent::__construct();

			if(cmsController::getInstance()->getCurrentMode() == "admin") {
				$this->__loadLib("__admin.php");
				$this->__implement("__seo");

				$configTabs = $this->getConfigTabs();
				if ($configTabs) {
					$configTabs->add("config");
					$configTabs->add("megaindex");
				}

			} else {
				$this->__loadLib("__custom.php");
				$this->__implement("__custom_seo");
			}
		}

	};
?>
