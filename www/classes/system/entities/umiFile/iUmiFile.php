<?php
	interface iUmiFile {
		public function __construct($filePath);
		public function delete();

		public static function upload($variableGroupName, $variableName, $targetFolder);

		public function getSize();
		public function getExt();
		public function getFileName();
		public function getDirName();
		public function getModifyTime();
		public function getFilePath($webMode = false);

		public function getIsBroken();

		public function __toString();
		
		public static function getUnconflictPath($path);
		
		public function download($deleteAfterDownload = false);
	}
?>