<?php
	interface iUmiExporter {
		public function __construct($type);
		static public function get($className);
		public function getFileExt();
	}

?>