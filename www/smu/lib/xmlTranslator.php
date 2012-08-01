<?php
	class xmlTranslator {
		protected	$domDocument = false;
		protected	$currentPageTranslated = false;
		protected   $rootNode    = null;

		public function __construct($rootName = "response") {
			$this->domDocument = new DOMDocument();
			$this->rootNode    = $this->domDocument->createElement($rootName);
			$this->domDocument->appendChild($this->rootNode);
		}
		
		public function translateToXml($userData) {
			if(is_string($userData)) return $userData;
			$this->chooseTranslator($this->rootNode, $userData);
			return $this->domDocument->saveXML();			
		}

		protected function chooseTranslator(DOMElement $rootNode, $userData, $is_full = false) {
			switch(gettype($userData)) {
				case "array": {
					$this->translateArray($rootNode, $userData);
					break;
				}
				default: {
					$this->translateBasic($rootNode, $userData);
					break;
				}
			}			
		}
        
		protected function translateBasic(DOMElement $rootNode, $userData) {
			$dom = $this->domDocument;
			$element = $dom->createTextNode( (string) $userData );
			$rootNode->appendChild($element);
		}
        
		protected function translateArray(DOMElement $rootNode, $userData) {
			$dom = $this->domDocument;

			foreach($userData as $key => $val) {
				if($this->isKeySubnodes($key)) {
					$key = $this->getRealKey($key);
					$res[$key] = Array();
					$res[$key]['nodes:item'] = $val;
					$val = $res;
					unset($res);
				}

				switch(true) {
					case $this->isKeyANull($key): {
						break;
					}

					case $this->isKeyAFull($key): {
						$key = $this->getRealKey($key);

						if($key == false) {
							$element = $rootNode;
						} else {
							$element = $dom->createElement($key);
						}

						$this->chooseTranslator($element, $val, true);

						if($key != false) {
							$rootNode->appendChild($element);
						}
						break;
					}

					case $this->isKeyAnAttribute($key): {
						$key = $this->getRealKey($key);
						if($val === "") break;
						$rootNode->setAttribute($key, $val);
						break;
					}


					case $this->isKeyANode($key): {
						$node = $dom->createTextNode($val);
						$rootNode->appendChild($node);
						break;
					}


					case $this->isKeyNodes($key): {
						$key = $this->getRealKey($key);
						foreach($val as $cval) {
							$element = $dom->createElement($key);
							$this->chooseTranslator($element, $cval);
							$rootNode->appendChild($element);
						}
						break;
					}

					case $this->isKeyXml($key): {
						$key = $this->getRealKey($key);

						$sxe = simplexml_load_string($val);

						if($sxe !== false) {
							if($dom_sxe = dom_import_simplexml($sxe)) {
								$dom_sxe = $dom->importNode($dom_sxe, true);
								$rootNode->appendChild($dom_sxe);
							}
							break;
						} else {
							$rootNode->appendChild($dom->createTextNode($val));
							break;
						}
					}



					case $this->isKeyXLink($key): {
						$key = $this->getRealKey($key);
						$rootNode->setAttributeNS("http://www.w3.org/TR/xlink", "xlink:" . $key, $val);
						break;
					}

					default: {
						if($key === 0) {
							throw new coreException("Can't translate to xml key {$key} with value {$val}");
							break;
						}

						$element = $dom->createElement($key);
						$this->chooseTranslator($element, $val);
						$rootNode->appendChild($element);
						break;
					}
				}
			}
		}
        
		protected function isKeyANull($key) {
			return $this->getSubKey($key) == "void";
		}

		protected function isKeyAFull($key) {
			return $this->getSubKey($key) == "full";
		}
        
		protected function isKeyAnAttribute($key) {
			$subKey = $this->getSubKey($key);

			if($subKey == "attr" || $subKey == "attribute") {
				return true;
			} else {
				return false;
			}
		}
        
		protected function isKeyANode($key) {
			$subKey = $this->getSubKey($key);
			return ($subKey == "node");
		}

		protected function isKeyNodes($key) {
			$subKey = $this->getSubKey($key);
			return ($subKey == "nodes");
		}

		protected function isKeySubnodes($key) {
			$subKey = $this->getSubKey($key);
			return ($subKey == "subnodes");
		}
        
		protected function isKeyXml($key) {
			$subKey = $this->getSubKey($key);
			return ($subKey == "xml");
		}
        
		protected function isKeyXLink($key) {
			$subKey = $this->getSubKey($key);
			return ($subKey == "xlink");
		}
        
		public function getRealKey($key) {
			if($pos = strpos($key, ":")) {
				++$pos;
			} else {
				$pos = 0;
			}
			return substr($key, $pos);
		}

		public function getSubKey($key) {
			if($pos = strpos($key, ":")) {
				return substr($key, 0, $pos);
			} else {
				return false;
			}

		}
        
		protected function translateException(DOMElement $rootNode, publicException $exception) {
			$resultArray = Array();


			$error = Array();
			$error['node:msg'] = $exception->getMessage();

			$resultArray['error'] = $error;

			$this->chooseTranslator($rootNode, $resultArray);
		}

	};
?>