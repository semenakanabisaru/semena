<?php
	class CompressDirectoryAction extends atomicAction {
		protected $outputFileName;
		
		public function execute() {
			$targetDirectory = $this->getParam("target-directory");
			$outputFileName = $this->getParam("output-file-name");
			
			$outputFileName = $this->replacePlaceHolders($outputFileName);
			
			$this->outputFileName = $outputFileName;
			
			if(substr($targetDirectory, 1, 1) == ":") {
				$removePath = substr($targetDirectory, 2);
			} else {
				$removePath = $targetDirectory;
			}
			
			$zip = new PclZip($outputFileName);
			$result = $zip->create($targetDirectory, PCLZIP_OPT_REMOVE_PATH, $removePath);
			
			if($result == 0) {
				throw new Exception("Failed to create zip file: \"" . $zip->errorInfo(true) . "\"");
			}
			
			chmod($outputFileName, 0777);
		}
		
		public function rollback() {
			if(is_file($this->outputFileName)) {
				unlink($this->outputFileName);
			}
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