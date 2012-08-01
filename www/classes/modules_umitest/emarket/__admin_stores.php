<?php
	abstract class __emarket_admin_stores extends baseModuleAdmin {
		public function stores() {
			$this->setDataType("list");
			$this->setActionType("view");
			if($this->ifNotXmlMode()) return $this->doData();
			
			$limit = 50;
			$curr_page = (int) getRequest('p');
			$offset = $curr_page * $limit;
			
			$sel = new selector('objects');
			$sel->types('object-type')->name('emarket', 'store');
			$sel->limit($offset, $limit);
			selectorHelper::detectFilters($sel);

			$this->setDataRange($limit, $offset);
			$data = $this->prepareData($sel->result, "objects");
			$this->setData($data, $sel->length);
			return $this->doData();
		}
		
		public function store_add() {
			$mode = (string) getRequest('param0');
	
			$inputData = array(
				'type' => 'store',
				'allowed-element-types' => array('store')
			);
	
			if($mode == "do") {
				if(!empty( $_REQUEST ['data'] [ 'new' ] ['primary'])) 
				{
					stores::clearPrimary();
				}
				
				$object = $this->saveAddedObjectData($inputData);
				$this->chooseRedirect($this->pre_lang . "/admin/emarket/store_edit/{$object->id}/");
			}
	
			$this->setDataType("form");
			$this->setActionType("create");
	
			$data = $this->prepareData($inputData, "object");
	
			$this->setData($data);
			return $this->doData();
		}


		public function store_edit() {
			$object = $this->expectObject('param0');
			$mode = (string) getRequest('param1');
			
			$inputData = array(
				'object' => $object,
				'allowed-element-types' => array('store')
			);
			
			if($mode == "do") { 
				if(!empty( $_REQUEST ['data'] [ $object->id ] ['primary'])) 
				{
					stores::clearPrimary();
				}
				
				
				$this->saveEditedObjectData($inputData);
				$this->chooseRedirect();
			}
	 
			$this->setDataType("form");
			$this->setActionType("modify");
	 
			$data = $this->prepareData($inputData, "object");
	 
			$this->setData($data);
			return $this->doData();
		}
		
		
		public function onOrderPropChange(iUmiEventPoint $e) {
			if($e->getMode() != 'after') return;
			
			$propName = $e->getParam('property');
			$entity = $e->getRef('entity');

			if($entity instanceof iUmiObject && $propName == 'status_id') {
				$type = selector::get('object-type')->id($entity->getTypeId());
				if($type && $type->getMethod() == 'order') {
					$status = selector::get('object')->id($e->getParam('newValue'));
					if(($status instanceof iUmiObject) && $status->codename) {
						$order = order::get($entity->id);
						switch($status->codename) {
							case 'waiting': {
							$order->reserve();
							break;
						}
						
						case 'canceled': {
							$order->unreserve();
							break;
						}
						
						case 'ready': {
							$order->writeOff();
							break;
						}
						}
						$order->commit();
					}
				}
			}
		}		
		public function onStorePropChange(iUmiEventPoint $e) {
			if($e->getMode() != 'after') return;
			
			$propName = $e->getParam('property');
			$value = $e->getParam('newValue');
			$entity = $e->getRef('entity');
			
			if($entity instanceof iUmiObject && $propName == 'primary' && $value==1) {
				stores::clearPrimary( $entity -> getid() );
			}
		}
		
		public function onOrderDelete(iUmiEventPoint $e) {
			if($e->getMode() != 'before') return;
			$object = $e->getRef('object');
			if($object instanceof iUmiObject) {
				$type = selector::get('object-type')->id($object->getTypeId());
				if($type && $type->getMethod() == 'order') {
					$order = order::get($object->id);
					$order->unreserve();
					$order->commit();
				}
			}
		}
	}
?>