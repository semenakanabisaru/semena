<?php
	class backup extends def_module {
		public function __construct() {
			parent::__construct();

			$cmsController = cmsController::getInstance();
			$config = mainConfiguration::getInstance();

			if($cmsController->getCurrentMode() == 'admin') {
				$commonTabs = $this->getCommonTabs();
				if($commonTabs) {
					$commonTabs->add('config');
					$commonTabs->add('backup_copies');
				}
			}

			$this->__loadLib("__admin.php");
			$this->__implement("__backup");
		}

		public function config() {
			return __backup::config();
		}
	};
?>