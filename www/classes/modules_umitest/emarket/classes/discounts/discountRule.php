<?php
	abstract class discountRule extends umiObjectProxy {
		
		public static function create(discount $discount, umiObject $ruleTypeObject) {
			$objects = umiObjectsCollection::getInstance();
			$ruleTypeId = null;
			if(strlen($ruleTypeObject->rule_type_guid)) {
				$ruleTypeId = umiObjectTypesCollection::getInstance()->getTypeIdByGUID($ruleTypeObject->rule_type_guid);
			} else {
				$ruleTypeId = $ruleTypeObject->rule_type_id;
			}
			$objectId = $objects->addObject('', $ruleTypeId);
			$object = $objects->getObject($objectId);
			if($object instanceof umiObject) {
				$object->setValue('rule_type_id', $ruleTypeObject->getId());
				$object->commit();
				return self::get($objectId, $discount);
			} else {
				return false;
			}
		}


		public static function get($modObjectId, discount $discount) {
			$objects = umiObjectsCollection::getInstance();
			
			$modObject = $objects->getObject($modObjectId);
			if($modObject instanceof umiObject == false) return false;
			
			$codeName = self::getCodeName($modObject->rule_type_id);
			$className = $codeName . 'DiscountRule';
			
			if(!$codeName) return false;
			if(!self::includeRule($codeName)) return false;
			if(!class_exists($className)) return false;
			
			$rule = new $className($discount, $modObject, $codeName);
			return ($rule instanceof discountRule) ? $rule : false;
		}


		public static function getList($discountTypeId = false) {
			$objectTypeId = self::getRuleType()->getId();
			
			$sel = new selector('objects');
			$sel->types('object-type')->id($objectTypeId);
			if($discountTypeId) {
				$sel->where('rule_discount_types')->equals($discountTypeId);
			}
			return $sel->result();
		}


		protected function init() {}


		protected function __construct(discount $discount, umiObject $ruleObject, $discountName) {
			parent::__construct($ruleObject);
			
			$this->name = $discountName;
			$this->discount = $discount;
			$this->init();
		}


		private static function includeRule($ruleName) {
			static $included = Array();
			
			if(isset($included[$ruleName])) {
				return $included[$ruleName];
			}
			
			$filepath = CURRENT_WORKING_DIR . '/classes/modules/emarket/classes/discounts/rules/' . $ruleName . '.php';
			
			if(is_file($filepath)) {
				require $filepath;
				return $included[$ruleName] = true;
			}
			return $included[$ruleName] = false;
		}


		private static function getRuleType() {
			$objectTypes = umiObjectTypesCollection::getInstance();
			$objectTypeId = $objectTypes->getBaseType('emarket', 'discount_rule_type');
			if(!$objectTypeId) {
				throw new coreException("Required data type (emarket::discount_rule_type) not found");
			}
			return $objectTypes->getType($objectTypeId);
		}
		
		
		private static function getCodeName($modTypeObjectId) {
			static $cache = Array();
			
			if(isset($cache[$modTypeObjectId])) {
				return $cache[$modTypeObjectId];
			}
			
			$objects = umiObjectsCollection::getInstance();
			
			$modTypeObject = $objects->getObject($modTypeObjectId);
			$cache[$modTypeObjectId] = ($modTypeObject instanceof umiObject) ? trim($modTypeObject->rule_codename) : false;
			return $cache[$modTypeObjectId];
		}
	};


	interface orderDiscountRule {
		public function validateOrder(order $order);
	};
	
	interface itemDiscountRule {
		public function validateItem(iUmiHierarchyElement $element);
	}
?>