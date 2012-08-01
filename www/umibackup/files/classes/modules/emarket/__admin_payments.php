<?php
	abstract class __emarket_admin_payment extends baseModuleAdmin {
		public function payment() {
			$this->setDataType("list");
			$this->setActionType("view");
			if($this->ifNotXmlMode()) return $this->doData();
			
			$limit = 25;
			$curr_page = (int) getRequest('p');
			$offset = $limit * $curr_page;
			
			$sel = new selector('objects');
			$sel->types('object-type')->name('emarket', 'payment');
			$sel->limit($offset, $limit);
			selectorHelper::detectFilters($sel);
			
			$this->setDataRange($limit, $offset);
			$data = $this->prepareData($sel->result, "objects");
			$this->setData($data, $sel->length);
			return $this->doData();
		}
		

		public function payment_add() {
			$mode = (string) getRequest('param0');
	
			$inputData = array(
				'type' => 'payment',
				'type-id' => getRequest('type-id'),
				'allowed-element-types' => array('payment')
			);
	
			if($mode == "do") {
				$data = getRequest("data");
				$paymentType = $data["new"]["payment_type_id"];
				if($typeObject = umiObjectsCollection::getInstance()->getObject($paymentType)) {
					$inputData['type-id'] = umiObjectTypesCollection::getInstance()->getTypeIdByGUID($typeObject->payment_type_guid);
				}
				$object = $this->saveAddedObjectData($inputData);
				self::checkPaymentProps($object);
				$this->chooseRedirect($this->pre_lang . "/admin/emarket/payment_edit/{$object->id}/");
			}
	
			$this->setDataType("form");
			$this->setActionType("create");
	
			$data = $this->prepareData($inputData, "object");
	
			$this->setData($data);
			return $this->doData();
		}


		public function payment_edit() {
			$object = $this->expectObject('param0');
			$mode = (string) getRequest('param1');
			
			$inputData = array(
				'object' => $object,
				'allowed-element-types' => array('payment')
			);
			
			if($mode == "do") {
				$object = $this->saveEditedObjectData($inputData);
				self::checkPaymentProps($object);
				
				$this->chooseRedirect();
			}
	 
			$this->setDataType("form");
			$this->setActionType("modify");
	 
			$data = $this->prepareData($inputData, "object");
	 
			$this->setData($data);
			return $this->doData();
		}
		
		private static function checkPaymentProps(iUmiObject $object) {
			if($object->payment_type_id) {
				$types = umiObjectTypesCollection::getInstance();
				$typeObject = selector::get('object')->id($object->payment_type_id);
				$typeId = $types->getTypeIdByGUID($typeObject->payment_type_guid);
				if($typeId != $object->getTypeId()) {
					$object->setTypeId($typeId);
					$object->commit();
				}
			}
		}
	};
?>