<?php
	class DecompressDirectoryAction extends atomicAction {
		protected $outputFileName;

		public function execute() {
			$archiveFileName = $this->getParam("archive-filepath");
			$targetDirectory = $this->getParam("target-directory");

			$archiveFileName = $this->replaceParams($archiveFileName);
			$archiveFileName = $this->replacePlaceHolders($archiveFileName);

			$this->archiveFileName = $archiveFileName;

			if(substr($targetDirectory, 1, 1) == ":") {
				$removePath = substr($targetDirectory, 2);
			} else {
				$removePath = $targetDirectory;
			}

			$zip = new PclZip($archiveFileName);
			$result = $zip->extract($targetDirectory, $removePath);

			if($result == 0) {
				throw new Exception("Failed to create zip file: \"" . $zip->errorInfo(true) . "\"");
			}

			//chmod($outputFileName, 0777);
		}

		public function rollback() {
			//TODO:
		}

		protected function replacePlaceHolders($str) {
			if(preg_match_all("/\{([^\}]+)\}/", $str, $out)) {
				foreach($out[1] as $pattern) {
					$str = str_replace("{" . $pattern . "}", date($pattern), $str);
				}
			}

			return $str;
		}
	};
?>
