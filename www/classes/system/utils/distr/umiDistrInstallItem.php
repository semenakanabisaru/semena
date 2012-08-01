<?php
	abstract class umiDistrInstallItem {
		abstract public function __construct($filePath = false);

		abstract public function pack();
		public static function unpack($data) {
			//abstract + static throws E_STRICT in last php snapshot
		}

		abstract public function restore();
		
		public function getFilePath() {
			return $this->filePath;
		}
	};
?>