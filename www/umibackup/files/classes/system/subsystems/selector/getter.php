<?php
	class selectorGetter {
		protected static $types = array('object', 'page', 'object-type', 'hierarchy-type', 'field', 'field-type', 'domain', 'lang');
		protected $requestedType;

		public function __construct($requestedType) {
			if(in_array($requestedType, self::$types) == false) {
				throw new selectorException("Wrong content type \"{$requestedType}\"");
			}
			$this->requestedType = $requestedType;
		}
		
		public function id($id) {
			if(is_array($id)) {
				$result = array();
				foreach($id as $i => $v) {
					$item = $this->id($v);
					if(is_object($item)) {
						$result[$i] = $item;
					}
					unset($item);
				}
				return $result;
			}
			if(!$id) return null;
		
			$collection = $this->collection();

			try {
				switch($this->requestedType) {
					case 'object':
						return $collection->getObject($id);
					case 'page':
						return $collection->getElement($id);
					case 'hierarchy-type':
					case 'object-type':
						return $collection->getType($id);
					case 'field':
						return $collection->getField($id);
					case 'field-type':
						return $collection->getFieldType($id);
					case 'domain':
						return $collection->getDomain($id);
					case 'lang':
						return $collection->getLang($id);
				}
			} catch (coreException $e) {
				return null;
			}
		}
		
		public function name($module, $method = '') {
			$collection = $this->collection();
		
			switch($this->requestedType) {
				case 'object-type': {
					$objectTypeId = $collection->getBaseType($module, $method);
					return $this->id($objectTypeId);
				}
				
				case 'hierarchy-type': {
					$hierarchyType = $collection->getTypeByName($module, $method);
					return ($hierarchyType instanceof iUmiHierarchyType) ? $hierarchyType : null;
				}
				
				default: throw new selectorException("Unsupported \"name\" method for \"{$this->requestedType}\"");
			}
		}
		
		public function prefix($prefix) {
			if($this->requestedType != 'lang') {
				throw new selectorException("Unsupported \"prefix\" method for \"{$this->requestedType}\"");
			}
			
			$collection = $this->collection();
			return $this->id($collection->getLangId($prefix));
		}
		
		public function host($host) {
			if($this->requestedType != 'domain') {
				throw new selectorException("Unsupported \"host\" method for \"{$this->requestedType}\"");
			}
			
			$collection = $this->collection();
			return $this->id($collection->getDomainId($host));
		}
		
		protected function collection() {
			switch($this->requestedType) {
				case 'object':
					return umiObjectsCollection::getInstance();
				case 'page':
					return umiHierarchy::getInstance();
				case 'object-type':
					return umiObjectTypesCollection::getInstance();
				case 'hierarchy-type':
					return umiHierarchyTypesCollection::getInstance();
				case 'field':
					return umiFieldsCollection::getInstance();
				case 'field-type':
					return umiFieldTypesCollection::getInstance();
				case 'domain':
					return domainsCollection::getInstance();
				case 'lang':
					return langsCollection::getInstance();
			}
		}
	};
?>