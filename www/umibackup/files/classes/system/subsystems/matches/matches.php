<?php
	class matches implements iMatches {
		protected	$sitemapFilePath, $uri, $dom, $matchNode;
		protected	$buffer, $pattern, $params, $cache = false;
		protected	$externalCall = true;


		public function __construct($sitemapFileName = "sitemap.xml") {
			static $sitemapFilePath;
			if (is_null($sitemapFilePath)) {
				$controller = cmsController::getInstance();
				$templater = $controller->getCurrentTemplater();
				if ($controller->getCurrentMode() == 'admin') {
					$newTemplater = $controller->setCurrentTemplater(system_get_tpl());
					$templatePath = $newTemplater->getFolderPath();
					$controller->setCurrentTemplater($templater);
				}
				else $templatePath = $templater->getFolderPath();
				if (system_is_mobile() && strpos($templatePath, "/mobile/")) $templatePath .= "../";
				$sitemapFilePath = realpath($templatePath . "../umaps/" . $sitemapFileName);
			}
			if(file_exists($sitemapFilePath)) {
				$this->sitemapFilePath = $sitemapFilePath;
			} else {
				throw new publicException("Can't find sitemap file in \"./umaps/{$sitemapFileName}\"");
			}
		}
		

		public function setCurrentURI($uri) {
			$this->uri = (string) $uri;
		}
		
		
		public function execute($externalCall = true) {
			$this->externalCall = $externalCall;
			$this->loadXmlDOM();
			
			$sitemapNode = $this->dom->firstChild;

			$cache = ($sitemapNode->tagName === "sitemap") ? (int) $sitemapNode->getAttribute("cache") : 0;
			$this->setCacheTimeout($cache);

			if($matchNode = $this->searchPattern()) {
				$xsltTemplater = xslTemplater::getInstance();
				
				if($externalCall) {
					$xsltTemplater->init();
				}

				return $this->beginProcessing($matchNode);
			} else {
				return false;
			}
		}
		
		
		private function setCacheTimeout($cache) {
			if((int) $cache > 0) {
				$this->cache = (int) $cache;
			} else {
				$this->cache = false;
			}
		}
		
		
		private function loadXmlDOM() {
			$this->dom = DOMDocument::load($this->sitemapFilePath);
		}
		
		
		private function searchPattern() {
			$xpath = new DOMXPath($this->dom);
			$matchNodes = $xpath->query("/sitemap/match");

			foreach($matchNodes as $matchNode) {
				$pattern = $matchNode->getAttribute("pattern");
			
				if($this->comparePattern($pattern)) {
					return $matchNode;
				}
			}
			return false;			
		}
		

		private function comparePattern($pattern) {
			if(preg_match("|" . $pattern . "|", $this->uri, $params)) {
				$this->pattern = $pattern;
				$this->params = $params;

				return true;
			} else {
				return false;
			}
		}
		
		
		private function beginProcessing(DOMElement $matchNode) {
			$this->processRedirection();
			
			$params = $this->extractParams($matchNode);
			if(isset($params['cache'])) {
				$this->cache = $params['cache'];
			}

			$this->processGeneration();
 			$this->processTransformation();
			$this->processValidation();
			
			if($this->externalCall) {
				$this->processSerialization();
				return true;
			} else {
				return $this->buffer;
			}
		}
		
		
		private function replaceParams($str) {
			$params = $this->params;
			$sz = sizeof($params);

			for($i = 0; $i < $sz; $i++) {
				$str = str_replace('{' . $i . '}', $params[$i], $str);
			}
			
			foreach($_GET as $i => $v) {
				$str = str_replace('{' . $i . '}', urlencode($v), $str);
			}
			
			foreach($_SERVER as $i => $v) {
				if(is_array($v)) continue;
				$str = str_replace('{_' . strtolower($i) . '}', $v, $str);
			}
			
			return $str;
		}
		
		
		private function processGeneration() {
			$xpath = new DOMXPath($this->dom);
			$nodes = $xpath->query("/sitemap/match[@pattern = '{$this->pattern}']/generate");

			$i = 0;
			foreach($nodes as $node) {
				if($i++) throw new coreException("Only 1 generate tag allowed in match section.");

				$src = $this->replaceParams($node->getAttribute("src"));
				
				$cnt = false;
				if($this->cache !== false) {
					$data = cacheFrontend::getInstance()->loadSql($src);
				} else {
					$data = false;
				}
				if(!$data) {
					$data = file_get_contents($src);
					if($this->cache !== false) {
						cacheFrontend::getInstance()->saveObject($src, $data, $this->cache);
					}
				}

				$this->buffer = $data;
			}
			
			
			return (bool) $i;
		}
		
		
		private function processTransformation() {
			$xpath = new DOMXpath($this->dom);
			$nodes = $xpath->query("/sitemap/match[@pattern = '{$this->pattern}']/transform");
			
			foreach($nodes as $node) {
				$src = $this->replaceParams($node->getAttribute("src"));
				
				if(file_exists($src)) {
					$xsltDom = DomDocument::load($src);
					$xslt = new xsltProcessor;
					$xslt->registerPHPFunctions();
					$xslt->importStyleSheet($xsltDom);
					
					$params = $this->extractParams($node);
					foreach($params as $name => $value) {
						$value = $this->replaceParams($value);
						$xslt->setParameter("", $name, $value);
					}
					
					$this->buffer = $xslt->transformToXML($this->loadBufferDom());
				} else {
					throw new coreException("Transformation failed. File {$src} doesn't exists.");
				}
			}
		}
		
		
		private function processSerialization() {
			$xpath = new DOMXpath($this->dom);
			$nodes = $xpath->query("/sitemap/match[@pattern = '{$this->pattern}']/serialize");
			
			if($nodes->length == 0) {
				throw new coreException("Serializer tag required, but not found in umap rule.");
			}
			
			$i = 0;
			foreach($nodes as $node) {
				if($i++) throw new coreException("Only 1 serialize tag allowed in match section.");

				$type = $node->getAttribute("type");
				if(!$type) $type = "xml";
				
				$params = $this->extractParams($node);
				baseSerialize::serializeDocument($type, $this->buffer, $params);
				exit();
			}
		}
		
		
		private function processRedirection() {
			$xpath = new DOMXpath($this->dom);
			$nodes = $xpath->query("/sitemap/match[@pattern = '{$this->pattern}']/redirect");

			$i = 0;
			foreach($nodes as $node) {
				if($i++) throw new coreException("Only 1 redirect tag allowed in match section.");

				$uri = $node->getAttribute("uri");
				$params = $this->extractParams($node);
			}
			if($i == 0) return false;
			
			header("Location: {$uri}");

			if(isset($params['status'])) {
				$status = $params['status'];
				header("Status: {$status}");
			}
			exit();
		}
		
		
		private function processValidation() {
			$xpath = new DOMXpath($this->dom);
			$nodes = $xpath->query("/sitemap/match[@pattern = '{$this->pattern}']/validate");

			$i = 0;
			foreach($nodes as $node) {
				if($i++) throw new coreException("Only 1 validate tag allowed in match section.");

				$src = $node->getAttribute("src");
				$type = $node->getAttribute("type");
			}

			if($i == 0) return false;
			
			switch($type) {
				case "xsd": {
					if($this->validateXmlByXsd($src)) {
						return true;
					} else {
						throw new coreException("Document is not valid according to xsd scheme \"{$src}\"");
					}
					break;
				}
				
				case "dtd": {
					if($this->validateXmlByDtd($src)) {
						return true;
					} else {
						throw new coreException("Document is not valid according to dtd scheme \"{$src}\"");
					}
					break;
				}
			
				default: {
					throw new coreException("Unknown validation method \"{$type}\"");
					break;
				}
			}
		}
		
		
		private function extractParams(DOMElement $node) {
			$params = Array();
		
			$xpath = new DOMXpath($this->dom);
			$subnodes = $xpath->query("param", $node);
			
			foreach($subnodes as $subnode) {
				$i = (string) $subnode->getAttribute("name");
				$v = (string) $subnode->getAttribute("value");
				
				$params[$i] = $v;
				
				$_subnodes = $xpath->query("param", $subnode);
				if($_subnodes->length > 0) {
					$params[$i] = $this->extractParams($subnode);
				}
			}
			
			return $params;
		}
		
		
		private function validateXmlByXsd($src) {
			if(file_exists($src)) {
				$dom = $this->loadBufferDom();
				return $dom->schemaValidate($src);
			} else {
				throw new coreException("Failed to validate, because xsd scheme not found \"{$src}\"");
				return false;
			}
		}
		
		
		private function validateXmlByDtd($src) {
			if(file_exists($src)) {
				$dom = $this->loadBufferDom();
				return $dom->validate($src);
			} else {
				throw new coreException("Failed to validate, because dtd scheme not found \"{$src}\"");
				return false;
			}
		}
		
		
		private function loadBufferDom() {
			return DOMDocument::loadXML($this->buffer);
		}
	};
?>