<?php
	class umiObjectProxy {
		protected $object;
		
		protected function __construct(umiObject $object) {
			$this->object = $object;
		}
		
		public function getId() {
			return $this->object->getId();
		}
		
		public function setName($name) {
			$this->object->setName($name);
		}
		
		public function getName() {
			return $this->object->getName();
		}
		
		public function setValue($propName, $value) {
			return $this->object->setValue($propName, $value);
		}
		
		public function getValue($propName) {
			return $this->object->getValue($propName);
		}
		
		public function isFilled() {
			return $this->object->isFilled();
		}
		
		public function getObject() {
			return $this->object;
		}
		
		public function commit() {
			return $this->object->commit();
		}
		
		public function delete() {
			$objects = umiObjectsCollection::getInstance();
			return $objects->delObject($this->getId());
		}
		
		public function __get($prop) {
			switch($prop) {
				case 'id':		return $this->getId();
				case 'name':	return $this->getName();
				default:		return $this->getValue($prop);
			}
		}
		
		public function __set($prop, $value) {
			switch($prop) {
				case 'name':	return $this->setName($value);
				default:		return $this->setValue($prop, $value);
			}
		}
		
		public function __destruct() {
			$this->object->commit();
		}
	};
?>