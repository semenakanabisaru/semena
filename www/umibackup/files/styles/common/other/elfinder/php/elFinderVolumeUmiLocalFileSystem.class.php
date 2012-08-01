<?php

	class elFinderVolumeUmiLocalFileSystem extends elFinderVolumeLocalFileSystem {
    	protected $driverId = 'umi';
		public function fullRoot() {
			return $this->root;
		}

		public function save($fp, $dst, $name, $cmd = 'upload') {

			if (($dir = $this->dir($dst, true, true)) == false) {
				return $this->setError(elFinder::ERROR_TRGDIR_NOT_FOUND, '#'.$dst);
			}

			if (!$dir['write']) {
				return $this->setError(elFinder::ERROR_PERM_DENIED);
			}

			if (!$this->nameAccepted($name)) {
				return $this->setError(elFinder::ERROR_INVALID_NAME, $name);
			}

			$dst = $this->decode($dst);

			//$path = $this->_save($fp, $dst, $name);

			$cwd = getcwd();
			chdir(CURRENT_WORKING_DIR);

			if (umiImageFile::getIsImage($name)) {
				if(getRequest('water_mark')) umiImageFile::setWatermarkOn();
				$file = umiImageFile::upload('upload', '0', $dst);
			} else {
				$file = umiFile::upload('upload', '0', $dst);
			}

			chdir($cwd);

			if(!$file instanceof umiFile || $file->getIsBroken()) {
				return $this->setError(elFinder::ERROR_UPLOAD);
			} else {
				$path = CURRENT_WORKING_DIR . $file->getFilePath(true);
			}

			$result = false;
			if ($path) {
				$result = $this->stat($path);
			}
			return $result;
		}

	}
?>
