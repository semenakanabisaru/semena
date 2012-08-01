<?php
	abstract class payment extends umiObjectProxy {
		protected $order;
		
		final public static function create(iUmiObject $paymentTypeObject) {
			$objects = umiObjectsCollection::getInstance();
			$paymentTypeId = null;
			if(strlen($paymentTypeObject->payment_type_guid)) {
				$paymentTypeId = umiObjectTypesCollection::getInstance()->getTypeIdByGUID($paymentTypeObject->payment_type_guid);
			} else {
				$paymentTypeId = $paymentTypeObject->payment_type_id;
			}
			$objectId = $objects->addObject('', $paymentTypeId);
			$object = $objects->getObject($objectId);
			if($object instanceof umiObject) {
				$object->payment_type_id = $paymentTypeObject->id;
				$object->commit();
				
				return self::get($objectId);
			} else {
				return false;
			}
		}
		
		final public static function get($objectId, order $order = null) {
			if($objectId instanceof iUmiObject) {
				$object = $objectId;
			} else {
				$objects = umiObjectsCollection::getInstance();
				$object = $objects->getObject($objectId);
				
				if($object instanceof iUmiObject == false) {
					throw new coreException("Couldn't load order item object #{$objectId}");
				}
			}
			
			$classPrefix = objectProxyHelper::getClassPrefixByType($object->payment_type_id);
			
			objectProxyHelper::includeClass('emarket/classes/payment/systems/', $classPrefix);
			$className = $classPrefix . 'Payment';
			return new $className($object, $order);
		}
		
		final public static function getList() {
			$sel = new selector('objects');
			$sel->types('object-type')->name('emarket', 'payment');
			return $sel->result;
		}
		
		public function __construct(iUmiObject $object, order $order = null) {
			parent::__construct($object);
			$this->order = $order;
		}
		
		public function getCodeName() {
			$objects = umiObjectsCollection::getInstance();
			$paymentTypeId = $this->object->payment_type_id;
			$paymentType = $objects->getObject($paymentTypeId);
			return ($paymentType instanceof iUmiObject) ? $paymentType->class_name : false;
		}
		
		abstract function validate();
		abstract function process();
		abstract function poll();
	};
?>