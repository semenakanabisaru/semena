<?php
	abstract class selectorWhereProp {
		protected $value, $mode,
		$modes = array('equals', 'notequals', 'ilike', 'like', 'more', 'eqmore', 'less', 'eqless', 'between', 'isnull', 'isnotnull');
		
		public function __call($method, $args) {      
			$method = strtolower($method);
			if(in_array($method, $this->modes)) {
				$value = sizeof($args) ? $args[0] : null;
				
				if($value instanceof iUmiEntinty) {
					$value = $value->getId();
				}
				
				if(isset($this->fieldId)) {
					$field = selector::get('field')->id($this->fieldId);
					if($restrictionId = $field->getRestrictionId()) {
						$restriction = baseRestriction::get($restrictionId);
						if($restriction instanceof iNormalizeInRestriction) {
							$value = $restriction->normalizeIn($value);
						}
					}
					
					if(is_numeric($value) && substr($value, 0, 1) !== "0") {
						$value = (double) $value;
					}
					
					if($field->getDataType() == 'relation' && is_string($value)) {
						if($guideId = $field->getGuideId()) {
							$sel = new selector('objects');
							$sel->types('object-type')->id($guideId);
							$sel->where('*')->ilike($value);
							
							$length = sizeof($sel->result); //fast length
							if($length > 0 && $length < 100) {
								$value = $sel->result;
							}
						}
					}
					
					if($field->getDataType() == 'date' && is_string($value)) {
						$date = new umiDate;
						$date->setDateByString(trim($value, ' %'));
						$value = $date->getDateTimeStamp();
					}
				}
				
				$this->value = $value;
				$this->mode = $method;
			} else {
				throw new selectorException("This property doesn't support \"{$method}\" method");
			}
		}
		
		public function between($start, $end) {
			return $this->__call('between', array(array($start, $end)));
		}
		
		public function __get($prop) { return $this->$prop; }
	};

	class selectorWhereSysProp extends selectorWhereProp {
		protected $name;
		
		public function __construct($name) {
			$this->name = $name;
		}
	};

	class selectorWhereFieldProp extends selectorWhereProp {
		protected $fieldId;
		
		public function __construct($fieldId) {
			$this->fieldId = $fieldId;
		}
	};
	
	class selectorWhereHierarchy {
		protected $elementId, $level = 1, $selfLevel;
		
		public function page($elementId)  {
			$hierarchy = umiHierarchy::getInstance();
			if(is_numeric($elementId) == false) {
				$elementId = $hierarchy->getIdByPath($elementId);
			}

			if($elementId !== false) {
				$this->elementId = (int) $elementId;
			}
			return $this;
		}
		
		public function childs($level = 1) {
			if(is_null($this->selfLevel)) {
				$sql = "SELECT level FROM cms3_hierarchy_relations WHERE child_id = {$this->elementId}";
				$result = l_mysql_query($sql);
				list($this->selfLevel) = mysql_fetch_row($result);
			}
			$this->level = ($level == 0) ? 0 : (int) $level + (int) $this->selfLevel;
		}
		
		public function __get($prop) { return $this->$prop; }
	};
	
	class selectorWherePermissions {
		protected $level = 0x1, $owners = array(), $isSv;
		
		public function __construct() {
			$permissions = permissionsCollection::getInstance();
			$userId = $permissions->getUserId();
			
			$this->isSv = $permissions->isSv();
			if(!$this->isSv) {
				$this->owners = array($userId);
				$user = umiObjectsCollection::getInstance()->getObject($userId);
				if($user) {
					$this->owners = array_merge($this->owners, $user->groups);
				}
			}
		}
		
		public function level($level) {
			$this->level = (int) $level;
		}
		
		public function owners($owners) {
			if(is_array($owners)) {
				foreach($owners as $owner) $this->owners($owner);
			} else {
				$this->addOwner($owners);
			}
			return $this;
		}
		
		public function __get($prop) { return $this->$prop; }
		
		protected function addOwner($ownerId) {
			if(in_array($ownerId, $this->owners)) return;
			//if($this->isSv) return;
			$permissions = permissionsCollection::getInstance();
			
			
			$objects = umiObjectsCollection::getInstance();
			$object = $objects->getObject($ownerId);
			
			if($object instanceof iUmiObject) {
				if($permissions->isSv($ownerId)) {
					$this->isSv = true;
					return;
				}
				
				$this->owners[] = $ownerId;
				if($object->groups) {
					$this->owners = array_merge($this->owners, $object->groups);
				}
			}
		}
	};
?>