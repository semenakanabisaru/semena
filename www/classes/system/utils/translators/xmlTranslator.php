<?php
	class xmlTranslator implements iXmlTranslator {
		public static $showHiddenFieldGroups = false;
		public static $showUnsecureFields = false;

		protected	$domDocument = false;
		protected	$currentPageTranslated = false;
		protected	static $shortKeys = array(
			'@' => 'attribute',
			'#' => 'node',
			'+'	=> 'nodes',
			'%' => 'xlink',
			'*' => 'comment'
		);

		public function __construct(DOMDocument $domDocument) {
			$this->domDocument = $domDocument;
		}


		public function translateToXml(DOMElement $rootNode, $userData) {
			return $this->chooseTranslator($rootNode, $userData);
		}


		public function chooseTranslator(DOMElement $rootNode, $userData, $is_full = false) {
			switch(gettype($userData)) {
				case "array": {
					$this->translateArray($rootNode, $userData);
					break;
				}


				case "object": {
					$wrapper = translatorWrapper::get($userData);
					$wrapper->isFull = $is_full;
					$this->chooseTranslator($rootNode, $wrapper->translate($userData));
					break;
				}

				default: {
					$this->translateBasic($rootNode, $userData);
					break;
				}
			}
		}


		public static function executeMacroses($userData, $scopeElementId = false, $scopeObjectId = false) {
			$cmsController = cmsController::getInstance();

			if($cmsController->getCurrentMode() != "admin" &&
				(!defined('XML_MACROSES_DISABLE') || !XML_MACROSES_DISABLE)) {
				$userData = def_module::parseTPLMacroses($userData, $scopeElementId, $scopeObjectId);
			}

			return $userData;
		}


		protected function translateBasic(DOMElement $rootNode, $userData) {
			$dom = $this->domDocument;

			$userData = self::executeMacroses($userData);

			$element = $dom->createTextNode($userData);
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
						if($val === "" || is_null($val) || is_array($val)) break;
						$rootNode->setAttribute($key, $val);
						break;
					}


					case $this->isKeyANode($key): {
						$node = $dom->createTextNode((string) $val);
						$rootNode->appendChild($node);
						break;
					}


					case $this->isKeyNodes($key): {
						$key = $this->getRealKey($key);

						if(is_array($val)) {
							foreach($val as $cval) {
								$element = $dom->createElement($key);
								$this->chooseTranslator($element, $cval);
								$rootNode->appendChild($element);
							}
						}
						break;
					}

					case $this->isKeyXml($key): {
						$key = $this->getRealKey($key);

						$val = html_entity_decode($val, ENT_COMPAT, "utf-8");
						$val = str_replace('&', '&amp;', $val);

						$sxe = @simplexml_load_string($val); // try load xml. ignore warnings

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
						$rootNode->setAttribute("xlink:" . $key, $val);
						break;
					}

					case $this->isKeyComment($key): {
						$rootNode->appendChild(new DOMComment(' ' . $val . ' '));
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
			return ($subKey == "attr" || $subKey == "attribute");
		}


		protected function isKeyANode($key) {
			return ($this->getSubKey($key) == "node");
		}

		protected function isKeyNodes($key) {
			return ($this->getSubKey($key) == "nodes");
		}

		protected function isKeySubnodes($key) {
			return ($this->getSubKey($key) == "subnodes");
		}


		protected function isKeyXml($key) {
			return ($this->getSubKey($key) == "xml");
		}


		protected function isKeyXLink($key) {
			return ($this->getSubKey($key) == "xlink");
		}

		protected function isKeyComment($key) {
			return ($this->getSubKey($key) == "comment");
		}


		public static function getRealKey($key) {
			$first = substr($key, 0, 1);
			if(isset(self::$shortKeys[$first])) {
				return substr($key, 1);
			}

			if($pos = strpos($key, ":")) {
				++$pos;
			} else {
				$pos = 0;
			}
			return substr($key, $pos);
		}

		public static function getSubKey($key) {
			$first = substr($key, 0, 1);
			if(isset(self::$shortKeys[$first])) {
				return self::$shortKeys[$first];
			}

			if($pos = strpos($key, ":")) {
				return substr($key, 0, $pos);
			} else {
				return false;
			}
		}
	};
?>
