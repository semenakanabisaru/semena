<?php
	interface iUmiXmlExporter {
		public function __construct();

		public function setElements($elements);
		public function setObjects($objects);

		public function run();

		public function getResultFile();
		public function saveResultFile($filePath);
	};
?>