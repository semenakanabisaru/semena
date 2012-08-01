<?php
	abstract class selectorGroupField {
		public function __get($prop) { return $this->$prop; }
	};

	class selectorGroupFieldProp extends selectorGroupField {
		protected $fieldId;

		public function __construct($fieldId) {
			$this->fieldId = $fieldId;
		}
	};

	class selectorGroupSysProp extends selectorGroupField {
		protected $name;

		public function __construct($name) {
			$this->name = $name;
		}
	}
?>
