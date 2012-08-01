<?php
	class seo extends def_module {
		public function __construct() {
        	        parent::__construct();

			if(cmsController::getInstance()->getCurrentMode() == "admin") {
				$this->__loadLib("__admin.php");
				$this->__implement("__seo");
			} else {
				$this->__loadLib("__custom.php");
				$this->__implement("__custom_seo");
			}
		}
		
	};
?>