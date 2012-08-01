<?php
	class CheckPermissionsAction extends atomicAction {
		
		public function execute() {
			$targetPath = $this->getParam('target');
			
			if(file_exists($targetPath) == false) {
				throw new Exception("Doesn't exsist target \"{$targetPath}\"");
			}
			
			if(is_writable($targetPath) == false) {
				throw new Exception("Target must be writable \"{$targetPath}\"");
			}
		}
		
		public function rollback() {}
	};
?>