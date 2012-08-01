<?php
	interface iUmiImportSplitter {
		public function __construct($type);

		static public function get($className);
		public function load($file_path, $block_size = 100, $offset = 0);
		public function translate(DomDocument $doc);
		public function getXML();
		public function getDocument();
		public function getOffset();
	}
?>