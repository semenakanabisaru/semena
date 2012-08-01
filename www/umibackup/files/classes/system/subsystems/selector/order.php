<?php
	abstract class selectorOrderField {
		protected $asc = true;
		
		public function asc() { $this->asc = true; }
		public function desc() { $this->asc = false; }
		public function rand() { $this->name = 'rand'; }

		public function __get($prop) { return $this->$prop; }
	};

	class selectorOrderFieldProp extends selectorOrderField {
		protected $fieldId;
		
		public function __construct($fieldId) {
			$this->fieldId = $fieldId;
		}
	};
	
	class selectorOrderSysProp extends selectorOrderField {
		protected $name;
		
		public function __construct($name) {
			$this->name = $name;
		}
	}
?>