<?php
	abstract class __emarket_admin_delivery extends baseModuleAdmin {
		
		public function delivery() {
			$this->setDataType("list");
			$this->setActionType("view");
			if($this->ifNotXmlMode()) return $this->doData();
			
			$limit = 50;
			$curr_page = (int) getRequest('p');
			$offset = $limit * $curr_page;
			
			$sel = new selector('objects');
			$sel->types('object-type')->name('emarket', 'delivery');
			$sel->limit($offset, $limit);
			selectorHelper::detectFilters($sel);
			
			$this->setDataRange($limit, $offset);
			$data = $this->prepareData($sel->result, "objects");
			$this->setData($data, $sel->length);
			return $this->doData();
		}
		

		public function delivery_add() {
			$mode = (string) getRequest('param0');
	
			$inputData = array(
				'type' => 'delivery',
				'type-id' => getRequest('type-id'),
				'allowed-element-types' => array('delivery')
			);
	
			if($mode == "do") {
				$data = getRequest("data");
				$deliveryType = $data["new"]["delivery_type_id"];
				if($typeObject = umiObjectsCollection::getInstance()->getObject($deliveryType)) {
					$inputData['type-id'] = umiObjectTypesCollection::getInstance()->getTypeIdByGUID($typeObject->delivery_type_guid);
				}
				$object = $this->saveAddedObjectData($inputData);
				self::checkDeliveryProps($object);
				
				$this->chooseRedirect($this->pre_lang . "/admin/emarket/delivery_edit/{$object->id}/");
			}
	
			$this->setDataType("form");
			$this->setActionType("create");
	
			$data = $this->prepareData($inputData, "object");
	
			$this->setData($data);
			return $this->doData();
		}


		public function delivery_edit() {
			$object = $this->expectObject('param0');
			$mode = (string) getRequest('param1');
			
			$inputData = array(
				'object' => $object,
				'allowed-element-types' => array('delivery')
			);
			
			if($mode == "do") {
				$object = $this->saveEditedObjectData($inputData);
				self::checkDeliveryProps($object);
				
				$this->chooseRedirect();
			}
	 
			$this->setDataType("form");
			$this->setActionType("modify");
	 
			$data = $this->prepareData($inputData, "object");
	 
			$this->setData($data);
			return $this->doData();
		}

		public function delivery_address_edit() {
			$object = $this->expectObject('param0');
			$mode = (string) getRequest('param1');

			$inputData = array(
				'object' => $object,
				'allowed-element-types' => array('delivery_address')
			);

			if($mode == "do") {
				$object = $this->saveEditedObjectData($inputData);				

				$this->chooseRedirect();
			}

			$this->setDataType("form");
			$this->setActionType("modify");

			$data = $this->prepareData($inputData, "object");

			$this->setData($data);
			return $this->doData();
		}
		
		private static function checkDeliveryProps(iUmiObject $object) {
			if($object->delivery_type_id) {
				$types = umiObjectTypesCollection::getInstance();
				$typeObject = selector::get('object')->id($object->delivery_type_id);
				$typeId = $types->getTypeIdByGUID($typeObject->delivery_type_guid);
				if($typeId != $object->getTypeId()) {
					$object->setTypeId($typeId);
					$object->commit();
				}
			}
		}
	};
?>