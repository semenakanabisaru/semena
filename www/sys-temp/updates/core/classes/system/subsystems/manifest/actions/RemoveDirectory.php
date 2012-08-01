<?php
	class RemoveDirectoryAction extends atomicAction {
		
		public function execute() {
			$targetDirectory = $this->getParam("target-directory");
			
			$this->removeDirectory($targetDirectory);
		}
		
		public function rollback() {
			//Ooops... we can't rollback this action 8()
		}
		
		protected function removeDirectory($path) {
			$dir = new umiDirectory($path);
			
			foreach($dir as $item) {
				if($item instanceof umiDirectory) {
					$this->removeDirectory($item->getPath());
				}
				if($item instanceof umiFile) {
					$item->delete();
				}
			}
			
			$dir->delete();
		}
	};
?>