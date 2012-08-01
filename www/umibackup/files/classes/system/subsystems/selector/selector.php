<?php
	class selector implements IteratorAggregate {
		protected
			$mode, $permissions = null, $limit, $offset,
			$types = array(), $hierarchy = array(),
			$whereFieldProps = array(), $whereSysProps = array(),
			$orderSysProps = array(), $orderFieldProps = array(),
			$executor, $result = null, $length = null,
			$options = array();

		protected static
			$modes = array('objects', 'pages'),
			$sysPagesWhereFields = array('name', 'owner', 'domain', 'lang', 'is_deleted',
				'is_active', 'is_visible', 'updatetime', 'is_default', 'template_id', '*'),
			$sysObjectsWhereFields = array('name', 'owner', 'guid', '*'),
			$sysOrderFields = array('name', 'ord', 'rand', 'updatetime', 'id');

		public static function get($requestedType) {
			return new selectorGetter($requestedType);
		}

		public function __construct($mode) {
			$this->setMode($mode);
		}

		public function types($typeClass = false) {
			$this->checkExecuted();
			if($typeClass === false)
				return $this->types;
			else
				return $this->types[] = new selectorType($typeClass, $this);
		}

		public function where($fieldName) {
			$this->checkExecuted();
			if($fieldName == 'hierarchy') {
				if($this->mode == 'objects')
					throw new selectorException("Hierarchy filter is not suitable for \"objects\" selector mode");
				return $this->hierarchy[] = new selectorWhereHierarchy;
			}

			if($fieldName == 'permissions') {
				if($this->mode == 'objects')
					throw new selectorException("Permissions filter is not suitable for \"objects\" selector mode");
				if(is_null($this->permissions)) $this->permissions = new selectorWherePermissions;
				return $this->permissions;
			}

			if(in_array($fieldName, ($this->mode == 'pages') ? self::$sysPagesWhereFields : self::$sysObjectsWhereFields)) {
				return $this->whereSysProps[$fieldName] = new selectorWhereSysProp($fieldName);
			} else {
				$fieldId = $this->searchField($fieldName);
				if($fieldId) {
					return $this->whereFieldProps[] = new selectorWhereFieldProp($fieldId);
				} else {
					throw new selectorException("Field \"{$fieldName}\" is not presented in selected object types");
				}
			}
		}

		public function order($fieldName) {
			$this->checkExecuted();
			if(in_array($fieldName, self::$sysOrderFields)) {
				return $this->orderSysProps[] = new selectorOrderSysProp($fieldName);
			} else {
				$fieldId = $this->searchField($fieldName);
				if($fieldId) {
					return $this->orderFieldProps[] = new selectorOrderFieldProp($fieldId);
				} else {
					throw new selectorException("Field \"{$fieldName}\" is not presented in selected object types");
				}
			}
		}

		public function limit($offset, $limit) {
			$this->checkExecuted();
			$this->limit = (int) $limit;
			$this->offset = (int) $offset;
		}

		public function result() {
			if(is_null($this->result)) {
				if($this->mode == 'pages') {
					if(sizeof($this->orderSysProps) == 0) $this->order('ord')->asc();
					if(is_null($this->permissions)) $this->where('permissions');
				}
				$return = $this->option('return')->value;
				if (is_array($return) && in_array('count', $return)) {
					$this->result = $this->executor()->length();
				}
				else $this->result = $this->executor()->result();
				$this->length = $this->executor()->length();
			}
			$this->unloadExecutor();
			return $this->result;
		}

		public function length() {
			if(is_null($this->length)) {
				if($this->mode == 'pages' && is_null($this->permissions)) {
					$this->where('permissions');
				}
				$length = $this->executor()->length();
				if (in_array('count', $this->option('return')->value)) {
					$this->result = $length;
				}
				else $this->result = $this->executor()->result();
				$this->length = $length;
			}
			$this->unloadExecutor();
			return $this->length;
		}

		public function option($name, $value = null) {
			$this->checkExecuted();
			if (!isset($this->options[$name])) {
				$selectorOption = new selectorOption($name);
				$this->options[$name] = $selectorOption;
			}
			if (!is_null($value)) $this->options[$name]->value($value);
			return $this->options[$name];
		}

		public function flush() {
			$this->result = null;
			$this->length = null;
		}

		public function __get($prop) {
			switch($prop) {
				case 'length':
				case 'total':
					return $this->length();
				case 'result':
					return $this->result();
				case 'first':
					return (sizeof($this->result())) ? $this->result[0] : null;
				case 'last':
					return (sizeof($this->result())) ? $this->result[sizeof($this->result) - 1] : null;
			}

			$allowedProps = array('mode', 'offset', 'limit', 'whereFieldProps', 'orderFieldProps',
				'whereSysProps', 'orderSysProps', 'types', 'permissions', 'hierarchy', 'options');

			if(in_array($prop, $allowedProps)) {
				return $this->$prop;
			}
		}

		public function getIterator() {
			$this->result();
			return new ArrayIterator($this->result);
		}

		public function query() {
			if($this->mode == 'pages') {
				if(sizeof($this->orderSysProps) == 0) $this->order('ord')->asc();
				if(is_null($this->permissions)) $this->where('permissions');
			}

			return $this->executor()->query();
		}

		public function searchField($fieldName) {
			foreach($this->types as $type) {
				$fieldId = $type->getFieldId($fieldName);
				if($fieldId) return $fieldId;
			}
			if($this->mode == 'pages') {
				$types = umiObjectTypesCollection::getInstance();
				$type = $types->getTypeByGUID('root-pages-type');
				$fieldId = $type->getFieldId($fieldName);
				if($fieldId) return $fieldId;
			}
		}

		protected function checkExecuted() {
			if($this->executor && $this->executor->getSkipExecutedCheckState()) return;
			if(!is_null($this->result) || !is_null($this->length)) {
				throw new selectorException("Selector has been already executed. You should create new one or use selector::flush() method instead.");
			}
		}

		protected function executor() {
			if(!$this->executor) $this->executor = new selectorExecutor($this);
			return $this->executor;
		}

		protected function unloadExecutor() {
			if(!is_null($this->length) && !is_null($this->result)) {
				unset($this->executor);
			}
		}

		protected function setMode($mode) {
			if(in_array($mode, self::$modes)) {
				$this->mode = $mode;
				if($mode == 'pages') {
					$this->setDefaultPagesWhere();
				}
			} else {
				throw new selectorException(
					"This mode \"{$mode}\" is not supported, choose one of these: " . implode(', ', self::$modes)
				);
			}
		}

		protected function setDefaultPagesWhere () {
			$cmsController = cmsController::getInstance();
			$this->where('domain')->equals($cmsController->getCurrentDomain());
			$this->where('lang')->equals($cmsController->getCurrentLang());
			$this->where('is_deleted')->equals(0);

			if($cmsController->getCurrentMode() != 'admin') {
				$this->where('is_active')->equals(1);
			}
		}
	};
?>