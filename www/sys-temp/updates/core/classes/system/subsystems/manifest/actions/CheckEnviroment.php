<?php
	class CheckEnviromentAction extends atomicAction {
		
		public function execute() {
			if($this->checkDirectory($this->getEnviromentValue("temporary-directory-path")) == false) {
				throw new Exception("Failed enviroment check");
			}
			
			if($this->checkDirectory($this->getEnviromentValue("backup-directory-path")) == false) {
				throw new Exception("Failed enviroment check");
			}
			
			if($this->checkDirectory($this->getEnviromentValue("logger-directory-path")) == false) {
				throw new Exception("Failed enviroment check");
			}
		}
		
		public function rollback() {
			//Well, we don't need to rollback created enviroment dirs.
		}
		
		protected function checkDirectory($path) {
			if(is_dir($path)) {
				return true;
			}
			
			return mkdir($path, 0777, true);
		}
	};
?>