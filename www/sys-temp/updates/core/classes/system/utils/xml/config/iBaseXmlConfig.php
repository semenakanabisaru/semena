<?php
	/**
		* 
	*/
	interface iBaseXmlConfig {
		public function __construct($configFileName);
		public function read();
		
		public function getValue($xpath);
		public function getList($xpath, $info = false);
		
		public function addParam($paramName, $paramValue);
		
		public function getName();
	};
?>