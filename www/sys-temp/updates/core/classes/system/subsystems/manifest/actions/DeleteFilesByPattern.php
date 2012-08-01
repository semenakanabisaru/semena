<?php
	class DeleteFilesByPatternAction extends atomicAction {
		protected $movedItems = Array(), $targetDirectory;
		
		public function execute() {
			$targetDirectory = $this->getParam("target-directory");
			$this->pattern = $this->getParam('pattern');
			
			$this->checkDirectory($targetDirectory);
			$this->deleteFiles(new umiDirectory($targetDirectory));
		}
		
		public function rollback() {
		}
		
		
		protected function checkDirectory($directory) {if(is_dir($directory)) {
				return;
			} else if(mkdir($directory, 0777)) {
				return;
			} else {
				throw new Exception("Can't create temporary directory");
			}
		}
		
		protected function deleteFiles(umiDirectory $dir) {
			foreach($dir as $item) {
				if($item instanceof umiDirectory) {
					$this->deleteFiles($item);
				}
				
				if($item instanceof umiFile) {
					if($item->getExt() == "bak") {
						$item->delete();
					}
				}
			}
		}
	};
?>