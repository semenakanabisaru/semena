<?php

	interface iUmiXmlImporter {
		public function __construct();

		public function ignoreNewFields($ignoreNewFields = false);
		public function ignoreNewItems($ignoreNewItems = false);

		public function loadXmlString($xmlString);
		public function loadXmlFile($xmlFilePath);

		public function analyzeXml();
		public function importXml();


		public function getImportedElementsCount();
	};

?>