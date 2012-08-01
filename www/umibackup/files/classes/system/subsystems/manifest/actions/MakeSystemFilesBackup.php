<?php
	class MakeSystemFilesBackupAction extends atomicAction {
		protected $movedItems = Array(), $targetDirectory;

		public function execute() {
			$this->checkTargetDirectory();

			$param = $this->getParam('targets');

			if(is_array($param)) {
				$this->copyItems($param);
			} else {
				throw new Exception("No items to copy");
			}
		}

		public function rollback() {
			$movedItems = $this->movedItems;
			$movedItems = array_reverse($movedItems);

			foreach($movedItems as $item) {
				if(is_file($item)) {
					unlink($item);
				}

				if(is_dir($item)) {
					rmdir($item);
				}
			}

			if(is_dir($this->targetDirectory)) {
				rmdir($this->targetDirectory);
			}
		}


		protected function checkTargetDirectory() {
			$this->targetDirectory = $this->getEnviromentValue('temporary-directory-path');// . "system-files/";

			if(is_dir($this->targetDirectory)) {
				return;
			} else if(mkdir($this->targetDirectory, 0777)) {
				return;
			} else {
				throw new Exception("Can't create temporary directory");
			}
		}

		protected function copyItems($items) {
			clearstatcache();

			foreach($items as $item) {
				if(is_file($item)) {
					$this->copyFile($item);
				}

				if(is_dir($item)) {
					$this->copyDirectory($item);
				}
			}
		}

		protected function copyFile($item) {
			if(!is_writable($item)) {
				throw new Exception("This file should be writable: \"{$item}\"");
			}

			$newItemPath = $this->targetDirectory . $item;
			copy($item, $newItemPath);
			$this->movedItems[] = $newItemPath;
		}

		protected function copyDirectory($item) {
			$newItemPath = $this->targetDirectory . $item;
			$this->movedItems[] = $newItemPath;

			if(is_dir($newItemPath)) {
				if(!is_writable($newItemPath)) {
					throw new Exception("Directory is not writable: \"{$newItemPath}\"");
				}
			} else {
				if(mkdir($newItemPath , 0777) == false) {
					throw new Exception("Can't create directory \"{$newItemPath}\"");
				}
			}

			$dir = new umiDirectory($item);
			foreach($dir as $subItem) {
				if($subItem instanceof umiDirectory) {
					if($subItem->getName() == ".svn") {
						continue;
					}

					if($subItem->getName() == "cngeoip.dat") {
						continue;
					}

					$this->copyDirectory($subItem->getPath());
				}

				if($subItem instanceof umiFile) {
					$this->copyFile($subItem->getFilePath());
				}
			}
		}
	};
?>