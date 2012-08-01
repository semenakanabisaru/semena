<?php

	class selectorOption {
		protected $name, $value = array();

		public function __construct($name) {
			$allowedOptions = array('or-mode', 'root', 'exclude-nested', 'return');
			if (in_array($name, $allowedOptions)) $this->name = $name;
			else throw new selectorException("Unkown option \"{$name}\"");
		}

		public function __call($method, $args) {
			$allowedMethods = array('all', 'field', 'fields');
			$method = strtolower($method);
			if (in_array($method, $allowedMethods)) {
				$value = false;
				if ($method == 'all') $value = true;
				elseif (sizeof($args)) $value = $args;
				if ($value !== false) $this->value[$method] = $value;
			}
			elseif ($method == 'value') {
				if ($argsize = sizeof($args)) {
					if ($this->name == 'or-mode') $this->value['all'] = true;
					else {
						if ($argsize == 1 && (is_array($args[0]) || $args[0] === true || $args[0] === false)) {
							$this->value = $args[0];
						}
						else $this->value = $args;
					}
				}
				else $this->value = null;
			}
			else throw new selectorException("This property doesn't support \"{$method}\" method");
		}

		public function __get($prop) { return $this->$prop; }
	};

?>