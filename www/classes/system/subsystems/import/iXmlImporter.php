<?php
	interface iXmlImporter {
		public function __construct($source_name = false);
		public function loadXmlString($xml_string);
		public function loadXmlFile($xml_filepath);
		public function loadXmlDocument(DOMDocument $doc);
		public function setDestinationElement($element);
		public function execute();
	}

?>