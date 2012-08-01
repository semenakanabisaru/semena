<?php
	class baseXmlConfig {
		protected $configFileName, $dom, $is_inited = false, $params = Array();
	
		public function __construct($configFileName) {
			$this->configFileName = (string) $configFileName;
		}
		
		public function read() {
			if(!$this->is_inited) {
				$this->init();
				$this->is_inited = true;
			}
		}
		
		public function getName() {
			$info = pathinfo($this->configFileName);
			return $info['filename'];
		}
		
		public function addParam($paramName, $paramValue) {
			$this->params[(string) $paramName] = (string) $paramValue;
		}
		
		protected function init() {
			$this->dom = self::loadDOM($this->configFileName);
			$this->xpath = new DOMXPath($this->dom);
		}
		
		protected static function loadDOM($configFileName) {
			static $cache = Array();
			
			if(isset($cache[$configFileName])) {
				return $cache[$configFileName];
			}
			
			if(file_exists($configFileName) == false) {
				throw new Exception("Can't find config file \"{$configFileName}\"");
			}
			$dom = new DOMDocument;
			$dom->load($configFileName);
			
			if($dom === false) {
				throw new Exception("Can't parse xml config \"{$configFileName}\"");
			}
			
			return $cache[$configFileName] = $dom;
		}
		
		protected function executeXPath($xpath) {
			$this->read();
			return $this->xpath->query((string) $xpath);
		}
		
		public function getValue($xpath) {
			$nodes = $this->executeXPath($xpath);
			
			switch($nodes->length) {
				case 0:
					return NULL;
				
				case 1:
					$node = $nodes->item(0);
					return (string) $node->nodeValue;
				
				default:
					throw new Exception("Parsing getValue \"{$xpath}\" xpath failed. More than 1 result in \"{$this->configFileName}\".");
			}
		}
		
		public function getList($xpath, $info = false) {
			$nodes =  $this->executeXPath($xpath);

			if($nodes->length > 0) {
				$result = Array();

				$count = $nodes->length;
				for($i = 0; $i < $count; $i++) {
					$node = $nodes->item($i);

					if($info === false) {
						$node_info = $node->nodeValue;
					} else {
						$node_info = Array();
						foreach($info as $key => $seek) {
							if($seek == "+params") {
								$node_info[$key] = $this->extractParams($node);
							} else if($seek == ".") {
								$node_info[$key] = $node->nodeValue;
							} else {
								if(substr($seek, 0, 1) == "/") {
									$subXPath = $this->xpath;
									$subnodes = $subXPath->evaluate(substr($seek, 1), $node);
									if($subnodes->length >= 1) {
										$subnode = $subnodes->item(0);
										$node_info[$key] = (string) $subnode->nodeValue;
									} else {
										$node_info[$key] = NULL;
									}
									continue;
								} else {
									if(substr($seek, 0, 1) == "@") {
										$seek = substr($seek, 1);
									}

									if($node->hasAttribute($seek)) {
										$node_info[$key] = (string) $node->getAttribute($seek);
									} else {
										$node_info[$key] = NULL;
									}
								}
							}
						}					
					}
					$result[$i] = $node_info;
				}

					return $result;
			} else {
					return Array();
			}
		}
		
		protected function extractParams(DOMElement $node) {
			$params = Array();
		
			$xpath = $this->xpath;
			$subnodes = $xpath->query("param", $node);
			
			foreach($subnodes as $subnode) {
				$i = (string) $subnode->getAttribute("name");
				$v = (string) $subnode->getAttribute("value");
				
				$paramValue = $v;
				
				$_subnodes = $xpath->query("param", $subnode);
				if($_subnodes->length > 0) {
					$paramValue = $this->extractParams($subnode);
				}
				
				if(is_array($paramValue) == false) {
					$paramValue = $this->putParamPlaceholders($paramValue);
				}
				
				$params[$i] = $paramValue;
			}
			
			return $params;
		}
		
		protected function putParamPlaceholders($paramValue) {
			foreach($this->params as $externalParamName => $externalParamValue) {
				$paramValue = str_replace("{" . $externalParamName . "}", $externalParamValue, $paramValue);
			}
			
			return $paramValue;
		}
	};
?>