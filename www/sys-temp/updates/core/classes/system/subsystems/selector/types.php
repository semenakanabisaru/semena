<?php
	class selectorType {
		protected $typeClass, $objectType, $hierarchyType, $selector;
		protected static $typeClasses = array('object-type', 'hierarchy-type');
		
		public function __construct($typeClass, $selector) {
			$this->setTypeClass($typeClass);
			$this->selector = $selector;
		}
		
		public function name($module, $method) {
			if(!$method && $module == 'content') $method = 'page';
			
			switch($this->typeClass) {
				case 'object-type': {
					$objectTypes = umiObjectTypesCollection::getInstance();
					$objectTypeId = $objectTypes->getBaseType($module, $method);
					return $this->setObjectType($objectTypes->getType($objectTypeId));
				}
				
				case 'hierarchy-type': {
					$hierarchyTypes = umiHierarchyTypesCollection::getInstance();
					$hierarchyType = $hierarchyTypes->getTypeByName($module, $method);
					return $this->setHierarchyType($hierarchyType);
				}
			}
		}
		
		public function id($id) {
			if(is_array($id)) {
				$result = null;
				foreach($id as $iid) {
					$this->selector->types($this->typeClass)->id($iid);
				}
				return $result;
			}
			
			switch($this->typeClass) {
				case 'object-type': {
					$objectTypes = umiObjectTypesCollection::getInstance();
					return $this->setObjectType($objectTypes->getType($id));
				}
				
				case 'hierarchy-type': {
					$hierarchyTypes = umiHierarchyTypesCollection::getInstance();
					return $this->setHierarchyType($hierarchyTypes->getType($id));
				}
			}
		}
		
		public function guid($guid) {
			if($this->typeClass != 'object-type') {
				throw new selectorException("Select by guid is allowed only for object-type");
			}
			
			if(is_array($guid)) {
				$guids[] = array();
				//$result = null;
				foreach($guid as $v) {
					$guids[] = umiObjectTypesCollection::getInstance()->getTypeIdByGUID($v);
				}
				$guid = $guids;
			}
			else {
				$guid = umiObjectTypesCollection::getInstance()->getTypeIdByGUID($guid);
			}
	
			return $this->id($guid);
		}
		
		public function setTypeClass($typeClass) {
			if(in_array($typeClass, self::$typeClasses)) {
				$this->typeClass = $typeClass;
			} else {
				throw new selectorException(
					"Unkown type class \"{$typeClass}\". These types are only supported: " . implode(", ", self::$typeClasses)
				);
			}
		}
		
		public function getFieldId($fieldName) {
			if(is_null($this->objectType)) {
				if(is_null($this->hierarchyType)) {
					throw new selectorException("Object and hierarchy type prop can't be empty both");
				}
				$objectTypes = umiObjectTypesCollection::getInstance();
				$objectTypeId = $objectTypes->getTypeByHierarchyTypeId($this->hierarchyType->getId());
				if($objectType = $objectTypes->getType($objectTypeId)) {
					$this->setObjectType($objectType);
				} else {
					return false;
				}
			}
			return $this->objectType->getFieldId($fieldName);
		}
		
		public function __get($prop) {
			$allowedProps = array('objectType', 'hierarchyType');
			
			if(in_array($prop, $allowedProps)) {
				return $this->$prop;
			}
		}
		
		protected function setObjectType($objectType) {
			if($objectType instanceof iUmiObjectType) {
				$this->objectType = $objectType;
			} else {
				throw new selectorException("Wrong object type given");
			}
		}
		
		protected function setHierarchyType($hierarchyType) {
			if($hierarchyType instanceof iUmiHierarchyType) {
				$this->hierarchyType = $hierarchyType;
			} else {
				throw new selectorException("Wrong hierarchy type given");
			}
		}
	};
?>