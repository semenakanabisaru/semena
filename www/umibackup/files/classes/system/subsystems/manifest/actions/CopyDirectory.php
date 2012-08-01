<?php
	class CopyDirectoryAction extends atomicAction {
		protected $movedItems = Array(), $targetDirectory;
		
		public function execute() {
			$sourceDirectory = $this->getParam("source-directory");
			$targetDirectory = $this->getParam("target-directory");
			
			$this->checkDirectory($sourceDirectory);
			$this->checkDirectory($targetDirectory);
			
			$this->targetDirectory = $targetDirectory;
			$this->sourceDirectory = $sourceDirectory;
			
			if($sourceDirectory) {
				$this->copyDirectory(new umiDirectory($sourceDirectory));
			} else {
				throw new Exception("No items to copy");
			}
		}
		
		public function rollback() {
			$movedItems = $this->movedItems;
			$movedItems = array_reverse($movedItems);
			
			foreach($movedItems as $item) {
				if(is_file($item . ".bak")) {
					copy($item . ".bak", $item);
					unlink($item . ".bak");
				} else if (is_file($item)) {
					unlink($item);
				} else if (is_dir($item)) {
					rmdir($item);
				}
			}
		}
		
		
		protected function checkDirectory($directory) {if(is_dir($directory)) {
				return;
			} else if(mkdir($directory, 0777)) {
				return;
			} else {
				throw new Exception("Can't create temporary directory");
			}
		}
		
		protected function copyDirectory(umiDirectory $dir) {
			$targetPath = $this->targetDirectory . substr($dir->getPath(), strlen($this->sourceDirectory));
			if($targetPath) {
				if(is_dir($targetPath) == false) {
					mkdir($targetPath, 0777, true);
					$this->movedItems[] = $targetPath;
				}
			}
			
			foreach($dir as $item) {
				if($item instanceof umiDirectory) {
					$this->copyDirectory($item);
				}
				
				if($item instanceof umiFile) {
					$this->copyFile($item);
				}
			}
		}
		
		protected function copyFile(umiFile $file) {
			$sourcePath = $file->getFilePath();
			$targetPath = $this->targetDirectory . substr($sourcePath, strlen($this->sourceDirectory));
			
			if(is_file($targetPath)) {
				copy($targetPath, $targetPath . ".bak");
			}
			copy($sourcePath, $targetPath);
			$this->movedItems[] = $targetPath;
		}
	};
?>