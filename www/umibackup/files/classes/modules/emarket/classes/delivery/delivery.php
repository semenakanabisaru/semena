<?php
	abstract class delivery extends umiObjectProxy {
		final public static function create(umiObject $deliveryTypeObject) {
			$objects = umiObjectsCollection::getInstance();
			$deliveryTypeId = null;
			if(strlen($deliveryTypeObject->delivery_type_guid)) {
				$deliveryTypeId = umiObjectTypesCollection::getInstance()->getTypeIdByGUID($deliveryTypeObject->delivery_type_guid);
			} else {
				$deliveryTypeId = $deliveryTypeObject->delivery_type_id;
			}
			$objectId = $objects->addObject('', $deliveryTypeId);
			$object = $objects->getObject($objectId);
			if($object instanceof umiObject) {
				$object->setValue('delivery_type_id', $deliveryTypeObject->getId());
				$object->commit();
				
				return self::get($objectId);
			} else {
				return false;
			}
		}
		
		final public static function get($objectId) {
			if($objectId instanceof iUmiObject) {
				$object = $objectId;
			} else {
				$objects = umiObjectsCollection::getInstance();
				$object = $objects->getObject($objectId);
			
				if($object instanceof iUmiObject == false) {
					throw new coreException("Couldn't load order item object #{$objectId}");
				}
			}
			
			$classPrefix = objectProxyHelper::getClassPrefixByType($object->delivery_type_id);
			
			objectProxyHelper::includeClass('emarket/classes/delivery/systems/', $classPrefix);
			$className = $classPrefix . 'Delivery';
			return new $className($object);
		}
		
		final public static function getList() {
			$sel = new selector('objects');
			$sel->types('object-type')->name('emarket', 'delivery');
			return $sel->result();
		}
		
		abstract public function validate(order $order);
		abstract public function getDeliveryPrice(order $order);
	};
?>