<?php
	class umiFileWrapper extends translatorWrapper {
		public function translate($data) {
			return $this->translateData($data);
		}
		
		protected function translateData(iUmiFile $file) {
			$resultArray = array(
				'attribute:path'	=> $file->getFilePath(),
				'attribute:size'	=> $file->getSize(),
				'attribute:ext'		=> $file->getExt(),
				'node:src'			=> $file->getFilePath(true)
			);

			if(get_class($file) === "umiImageFile") {
				$resultArray['attribute:width'] = $file->getWidth();
				$resultArray['attribute:height'] = $file->getHeight();
			}
			
			return $resultArray;
		}
	};
?>