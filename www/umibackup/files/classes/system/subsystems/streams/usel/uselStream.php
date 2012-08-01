<?php
	class uselStream extends umiBaseStream {
		protected $scheme = "usel", $selectionFilePath, $selectionName, $selectionParams = Array();
		protected $dom, $sel, $mode = "objects";
		protected $modes = Array("objects" => "objects", "pages" => "pages", "count" => "count", "objects count" => "objects", "pages count" => "pages");
		protected $lastTypeId = false;
		protected $forceCounts = true;


		public function stream_open($path, $mode, $options, $opened_path) {
			$cacheFrontend = cacheFrontend::getInstance();
			if($data = $cacheFrontend->loadData($path)) {
				return $this->setData($data);
			}
			
			try {
				$sel = $this->parsePath($path);
			} catch (Exception $e) {
				$result = array('error'  => $e->getMessage());
				$data = parent::translateToXml($result);
				
				return $this->setData($data);
			}
			
			if($sel instanceof selector) {
				if($this->mode !== "count") {
					$res = $sel->result;
					if($this->forceCounts) {
						$res['total'] = $sel->length;
					}
				} else {
					$res = $sel->length;
				}

				$data = $this->translateToXml($res);
				if($this->expire) $cacheFrontend->saveData($path, $data, $this->expire);
				return $this->setData($data);
			} else {
				return $this->setDataError('not-found');
			}
		}
		
		public function call($selectionName, $params = NULL) {
			$this->selectionFilePath = $selectionFilePath = realpath(cmsController::getInstance()->getCurrentTemplater()->getFolderPath() . "../usels/" . $selectionName . ".xml");
			if(!file_exists($selectionFilePath)) {
				throw new publicException("File ./usels/" . $selectionName . ".xml not found");
			}
			
			if(is_array($params)) {
				$this->selectionParams = $params;
			}
			
			return array(
				"sel" => $this->createSelection(),
				"mode" => $this->mode
			);
		}
		
		protected function parsePath($path) {
			$path = parent::parsePath($path);
			$path = trim($path, "/");

			$path = str_replace(")(", ") (", $path);
			$path = preg_replace("/\(([^\)]+)\)/Ue", "umiBaseStream::protectParams('\\1')", $path);
			
			$path_arr = explode("/", $path);
			
			if(sizeof($path_arr) == 0) {
				throw new publicException("File {$selectionFilePath} not found");
			}

			$this->selectionName = $selectionName = $path_arr[0];
			$this->selectionFilePath = $selectionFilePath = realpath(cmsController::getInstance()->getCurrentTemplater()->getFolderPath() . "../usels/" . $selectionName . ".xml");

			if(!file_exists($selectionFilePath)) {
				return false;
			}
			
			for($i = 1; $i < sizeof($path_arr); $i++) {
				$this->selectionParams[] = umiBaseStream::unprotectParams($path_arr[$i]);
			}
			
			return $this->createSelection();
		}
		
		
		protected function translateToXml() {
			$args = func_get_args();
			$arr = $args[0];

			$objectsCollection = umiObjectsCollection::getInstance();
			$hierarchy = umiHierarchy::getInstance();
			$request = Array();
			
			switch($this->mode) {
				case "pages": {
					$pages = Array();
					foreach($arr as $i => $element) {
						if("total" == (string) $i) {
							continue;
						}
						
						if($element instanceof umiHierarchyElement) {
							$pages[] = $element;
						}
					}
					
					$request['nodes:page'] = $pages;
					
					if(isset($arr['total'])) {
						$request['total'] = $arr['total'];
					}
					
					break;
				}
				
				
				case "objects": {
					$objects = Array();
					foreach($arr as $i => $object) {
						if("total" == (string) $i) {
							continue;
						}
						
						if($object instanceof umiObject) {
							$objects[] = $object;
						}
					}
					
					$request['nodes:item'] = $objects;
					
					if(isset($arr['total'])) {
						$request['total'] = $arr['total'];
					}
					
					break;
				}
				
				case "count": {
					$request['total'] = $arr;
					break;
				}

				default: {
					$request['error'] = "Unknown result mode \"{$this->mode}\"";
					break;
				}
			}
			
			$request['attribute:module']= $this->scheme;
			$request['attribute:method']= $this->selectionName;
			
			return parent::translateToXml($request);
		}
		

		protected function loadDomXml() {
			$this->dom = DOMDocument::load($this->selectionFilePath);
		}
		

		protected function createSelection() {
			$this->loadDomXml();
			
			$xpath = new DOMXPath($this->dom);
			$nodes = $xpath->evaluate("/selection");
			
			if(sizeof($nodes) == 1) {
				$selectionNode = $nodes->item(0);

				$this->getTargets($selectionNode);
				$this->getOptions($selectionNode);
				$this->getHierarchy($selectionNode);
				$this->getSorts($selectionNode);
				$this->getLimit($selectionNode);
				$this->getProperties($selectionNode);

				return $this->sel;
			} else {
				return false;
			}
		}
		
		protected function getOptions(DOMElement $selectionNode) {
			$selectionObj = $this->sel;
			
			$xpath = new DOMXPath($this->dom);
			$nodes = $xpath->evaluate("option", $selectionNode);
			foreach($nodes as $optionNode) {
				$name = (string) $optionNode->getAttribute("name");
				$name = $this->parseInputParams($name);
				
				$value = (string) $optionNode->getAttribute("value");
				$value = $this->parseInputParams($value);
				
				$selectionObj->option($name, $value);
			}
		}


		protected function getTargets(DOMElement $selectionNode) {
			$selectionObj = $this->sel;

			$xpath = new DOMXPath($this->dom);
			
			$nodes = $xpath->evaluate("target", $selectionNode);
			$targetNode = $nodes->item(0);
			if($targetNode) {
				$targetResult = (string) $targetNode->getAttribute("result");

				if(!$targetResult) {
					$targetResult = (string) $targetNode->getAttribute("expected-result");
				}
				
				$forceHierarchy = (string) $targetNode->getAttribute("force-hierarchy");

				if(isset($this->modes[$targetResult])) {
					if(strpos($targetResult, " ") !== false) {
						$this->forceCounts = true;
					}
					$targetResult = $this->modes[$targetResult];
					$this->mode = $targetResult;
					
					$selectorMode = ($this->mode != 'count') ? $this->mode : ($forceHierarchy ? 'pages' : 'objects');
					$this->sel = new selector($this->mode);

					// set targets domain if need...
					if ($this->mode == 'pages') {
						$domains_nl = $xpath->evaluate("domain", $targetNode);
						if ($domains_nl->length > 0) {
							$domainNode = $domains_nl->item(0);
							$domain = $domainNode->nodeValue;
							if (!is_numeric($domain)) {
								$domain = domainsCollection::getInstance()->getDomainId($domain);
							}
							if ($domain && domainsCollection::getInstance()->isExists($domain)) {
								$selectionObj->where('domain')->equals($domain);
							}
						}
					}

				} else {
					return false;
				}
			} else {
				return false;
			}
			
			
			$nodes = $xpath->evaluate("target/type");
			
			foreach($nodes as $typeNode) {
				$typeId = $typeNode->getAttribute("id");
				$typeModule = $typeNode->getAttribute("module");
				$typeMethod = $typeNode->getAttribute("method");
				
				$typeId = $this->parseInputParams($typeId);
				$typeModule = $this->parseInputParams($typeModule);
				$typeMethod = $this->parseInputParams($typeMethod);
				
				$selectionObj = $this->sel;
				
				if($typeId) {
					$this->lastTypeId = $typeId;
					$selectionObj->types('object-type')->id((int) $typeId);
					continue;
				}
				
				if($typeModule && $typeMethod) {
					$hierarchyType = umiHierarchyTypesCollection::getInstance()->getTypeByName($typeModule, $typeMethod);

					if($hierarchyType instanceof iUmiHierarchyType) {
						$typeId = umiObjectTypesCollection::getInstance()->getTypeByHierarchyTypeId($hierarchyType->getId());
						$this->lastTypeId = $typeId;

						if($this->mode == "pages") {
							$selectionObj->types('hierarchy-type')->id($hierarchyType->getId());
							continue;
						}
						
						if($this->mode == "objects") {
							$selectionObj->types('object-type')->id((int) $typeId);
						}
					} else {
						continue;
					}
				}
			}
		}


		protected function getSorts(DOMElement $selectionNode) {
			$hasTypeId = true;
		
			if($this->lastTypeId !== false) {
				$type = umiObjectTypesCollection::getInstance()->getType($this->lastTypeId);
				if(!($type instanceof umiObjectType)) {
					$hasTypeId = false;
				}
			} else {
				$hasTypeId = false;
			}
		
			$selectionObj = $this->sel;
			$xpath = new DOMXPath($this->dom);
			$nodes = $xpath->evaluate("sort", $selectionNode);

			foreach($nodes as $sortNode) {
				$order = (string) $sortNode->getAttribute("order");
				$order = $this->parseInputParams($order);
				
				$field_name = $sortNode->nodeValue;
				$field_name = $this->parseInputParams($field_name);
				
				$order = (strtolower($order) == "descending" || strtolower($order) == "desc") ? false : true;
				
				$sort = null;
				switch($field_name) {
					case "name": {
						if($hasTypeId) {
							$sort = $selectionObj->order('name');
						}
						break;
					}
					
					case "ord": {
						$sort = $selectionObj->order('ord');
						break;
					}
					
					
					case "rand()": {
						$sort = $selectionObj->order('rand');
						break;
					}
					
					default: {
						if($hasTypeId && $field_name) {
							$sort = $selectionObj->order($field_name);
						}
						break;
					}
				}

				if($sort) {
					if($order) $sort->asc(); else $sort->desc();
				}
			}
		}
		
		
		protected function getLimit(DOMElement $selectionNode) {
			$selectionObj = $this->sel;
			$xpath = new DOMXPath($this->dom);
			$nodes = $xpath->evaluate("limit", $selectionNode);
			
			if($limitNode = $nodes->item(0)) {
				$limit = $limitNode->nodeValue;
				$page = $limitNode->getAttribute("page");
				
				$limit = $this->parseInputParams($limit);
				$page = (int) $this->parseInputParams($page);
				
				if($limit) {
					$selectionObj->limit($limit * $page, $limit);
				}
			} else {
				return false;
			}
		}
		
		
		protected function getProperties(DOMElement $selectionNode) {
			if($this->lastTypeId !== false) {
				$type = umiObjectTypesCollection::getInstance()->getType($this->lastTypeId);
				if(!($type instanceof umiObjectType)) {
					return false;
				}
			} else {
				return false;
			}
			
			$selectionObj = $this->sel;
			$xpath = new DOMXPath($this->dom);
			$nodes = $xpath->evaluate("property", $selectionNode);
			
			foreach($nodes as $propertyNode) {
				$propertyName = $propertyNode->getAttribute("name");
				$propertyName = $this->parseInputParams($propertyName);
				
				$propertyValue = $propertyNode->getAttribute("value");
				$propertyValue = $this->parseInputParams($propertyValue);
					
				$compareMode = $propertyNode->getAttribute("mode") == "not" ? true : false;
				$likeMode = $propertyNode->getAttribute("mode") == "like" ? true : false;
				$interval = $this->getInterval($propertyNode);
				$pages = $this->getPages($propertyNode);
				$objects = $this->getObjects($propertyNode);

				switch(true) {
					case (bool) $propertyValue: {
						$this->filterSimpleProperty($propertyName, $propertyValue, $compareMode, $likeMode);
						break;
					}
						
					case ($interval['min'] !== false || $interval['max'] !== false): {
						$this->filterIntervalProperty($propertyName, $interval);
						break;
					}
						
					case sizeof($pages) > 0 : {
						$this->filterPagesProperty($propertyName, $pages, $compareMode);
						break;
					}
						
					case sizeof($objects) > 0 : {
						$this->filterObjectsProperty($propertyName, $objects, $compareMode);
						break;
					}
						
					default: {
						break;
					}
				}
			}
		}
		
		
		protected function getInterval(DOMElement $propertyNode) {
			$xpath = new DOMXPath($this->dom);
			$nodes = $xpath->evaluate("min-value|max-value", $propertyNode);

			$result = Array("min" => false, "max" => false);

			foreach($nodes as $node) {
				$value = $node->nodeValue;
				$value = $this->parseInputParams($value);

				if (!strlen($value)) $value = false;
				
				if($format = $node->getAttribute("format")) {
					$value = $this->formatValue($value, $format);
				}
				
			
				if($node->tagName == "min-value") {
					$result['min'] = $value;
				}
				
				if($node->tagName == "max-value") {
					$result['max'] = $value;
				}
			}

			return $result;
		}
		
		
		protected function formatValue($value, $format) {
			switch($format) {
				case "timestamp": {
					$value = (int) $value;
					break;
				}
				
				case "GMT":
				case "UTC": {
					$value = strtotime($value);
					break;
				}
			}
			
			return $value;
		}
		
		
		protected function getPages(DOMElement $propertyNode) {
			$xpath = new DOMXPath($this->dom);
			$nodes = $xpath->evaluate("page", $propertyNode);
			
			$result = Array();

			foreach($nodes as $node) {
				$value = $node->nodeValue;
				$value = $this->parseInputParams($value);
				
				if(is_numeric($value)) {
					$result[] = $value;
				} else {
					$value = umiHierarchy::getInstance()->getIdByPath($value);
					if($value) {
						$result[] = $value;
					} else {
						continue;
					}
				}
			}
			
			return $result;
		}
		
		
		protected function getHierarchy(DOMElement $selectionNode) {
			$selectionObj = $this->sel;	

			$xpath = new DOMXPath($this->dom);
			$nodes = $xpath->evaluate("target/category", $selectionNode);

			$result = Array();

			foreach($nodes as $node) {
				$value = $node->nodeValue;
				$value = $this->parseInputParams($value);
				
				$depth = $node->getAttribute("depth");
				$depth = $this->parseInputParams($depth);
				$depth = ($depth === '') ? 1 : (int) $depth;

				if(is_numeric($value)) {
					$value = (int) $value;
				} else {
					$hierarchy = umiHierarchy::getInstance();
					$value = (int) $hierarchy->getIdByPath($value);
				}

				if(is_numeric($value)) {
					$h = $selectionObj->where('hierarchy')->page($value)->childs($depth ? $depth : 100);
				}
			}
		}
		
		
		protected function getObjects(DOMElement $propertyNode) {
			$xpath = new DOMXPath($this->dom);
			$nodes = $xpath->evaluate("object", $propertyNode);
			
			$result = Array();
			
			foreach($nodes as $node) {
				if($value = $node->nodeValue) {
					if($value = $this->parseInputParams($value)) {
						$result[] = $value;
					}
				}
			}
			
			return $result;
		}
		
		
		protected function filterSimpleProperty($fieldName, $value, $mode, $like) {
			$selectionObj = $this->sel;

			if($fieldName != "name") {
				if($mode) {
					$selectionObj->where($fieldName)->notequals($value);
					return true;
				} else {
					if($like) {
						$selectionObj->where($fieldName)->like('%' . $value . '%');
					} else {
						$selectionObj->where($fieldName)->equals($value);
					}
					return true;
				}
			} else {
				if(!$mode) {
					if($like) {
						$selectionObj->where('name')->like('%' . $value . '%');
					} else {
						$selectionObj->where('name')->equals($value);
					}
					return true;
				} else {
					return false;
				}
			}
		}
		
		
		protected function filterIntervalProperty($fieldName, $interval) {
			$min = $interval['min'];
			$max = $interval['max'];
			
			$selectionObj = $this->sel;
			
			if($min !== false && $max !== false) {
				$selectionObj->where($fieldName)->between($min, $max);
				return true;
			} else {
				if($min !== false) {
					$selectionObj->where($fieldName)->more($min);
					return true;
				}
				
				if($max !== false) {
					$selectionObj->where($fieldName)->less($max);
					return true;
				}
				
				return false;
			}
		}
		
		
		protected function filterPagesProperty($fieldName, $pages, $mode) {
			$selectionObj = $this->sel;
			
			if($mode) {
				$selectionObj->where($fieldName)->notequals($pages);
			} else {
				$selectionObj->where($fieldName)->equals($pages);
			}
		}
		
		
		protected function filterObjectsProperty($fieldName, $objects, $mode) {
			$selectionObj = $this->sel;

			if($mode) {
				$selectionObj->where($fieldName)->notequals($objects);
			} else {
				$selectionObj->where($fieldName)->equals($objects);
			}
		}
		
		
		protected function parseInputParams($str) {
			$params = $this->selectionParams;
			$sz = sizeof($params);

			for($i = 0; $i < $sz; $i++) {
				$p = "{" . ($i + 1) . "}";
				$v = $params[$i];
				$str = str_replace($p, $v, $str);
			}
			
			foreach($this->params as $i => $v) {
				$p = "{" . $i . "}";
				$str = str_replace($p, $v, $str);
			}
			
			return preg_replace("/\{[^\{\}]+\}/", "", $str);
		}
	};
?>
