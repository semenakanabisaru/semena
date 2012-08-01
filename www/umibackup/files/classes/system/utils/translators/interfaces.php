<?php
	interface iXmlTranslator {

		public function __construct(DOMDocument $dom);
		
		public function translateToXml(DOMElement $rootNode, $userData);
		
		public function getSubKey($key);
		public function getRealKey($key);

	};
?>