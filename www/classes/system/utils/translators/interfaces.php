<?php
	interface iXmlTranslator {

		public function __construct(DOMDocument $dom);
		
		public function translateToXml(DOMElement $rootNode, $userData);
		
		public static function getSubKey($key);
		public static function getRealKey($key);

	};
?>