<?php
	class CompareDirectoriesPermissionsAction extends atomicAction {
		protected $sourceDirectory, $targetDirectory;
		
		public function execute() {
			$this->sourceDirectory = $this->getParam('source-directory');
			$this->targetDirectory = $this->getParam('target-directory');
			
			$this->checkFolderPermissions($this->sourceDirectory);
		}
		
		public function rollback() {}
		
		protected function checkFolderPermissions($path) {
			$checkPath = $this->targetDirectory . substr($path, strlen($this->sourceDirectory));
			
			if(is_dir($checkPath)) {
				if(!is_writable($checkPath)) {
					throw new Exception("This directory must be writable \"{$path}\"");
				}
			}
			
			$dir = new umiDirectory($path);
			
			foreach($dir as $item) {
				if($item instanceof umiDirectory) {
					$this->checkFolderPermissions($item->getPath());
				}
				
				if($item instanceof umiFile) {
					$this->checkFilePermissions($item->getFilePath());
				}
			}
		}
		
		protected function checkFilePermissions($path) {
			$checkPath = $this->targetDirectory . substr($path, strlen($this->sourceDirectory));
			
			if(file_exists($checkPath)) {
				if(!is_writable($checkPath)) {
					throw new Exception("This file should be writable \"{$checkPath}\"");
				}
			}
		}
	};
?>