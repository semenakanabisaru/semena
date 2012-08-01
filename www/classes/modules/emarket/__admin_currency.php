<?php
	class __emarket_admin_currency extends baseModuleAdmin {
		
		public function currency() {
			$this->setDataType("list");
			$this->setActionType("view");
			if($this->ifNotXmlMode()) return $this->doData();
			
			$limit = 50;
			$curr_page = (int) getRequest('p');
			$offset = $limit * $curr_page;
	
			$sel = new selector('objects');
			$sel->types('object-type')->name('emarket', 'currency');
			$sel->limit($offset, $limit);
			selectorHelper::detectFilters($sel);
			
			$this->setDataRange($limit, $offset);
			$data = $this->prepareData($sel->result, "objects");
			$this->setData($data, $sel->length);
			return $this->doData();
		}


		public function currency_add() {
			$mode = (string) getRequest('param0');
	
			$inputData = array(
				'type' => 'currency',
				'allowed-element-types' => array('currency')
			);
	
			if($mode == "do") {
				$object = $this->saveAddedObjectData($inputData);
				$this->chooseRedirect($this->pre_lang . "/admin/emarket/currency_edit/{$object->id}/");
			}
	
			$this->setDataType("form");
			$this->setActionType("create");
	
			$data = $this->prepareData($inputData, "object");
	
			$this->setData($data);
			return $this->doData();
		}


		public function currency_edit() {
			$object = $this->expectObject('param0');
			$mode = (string) getRequest('param1');
			
			$inputData = array(
				'object' => $object,
				'allowed-element-types' => array('currency')
			);
			
			if($mode == "do") {
				$this->saveEditedObjectData($inputData);
				$this->chooseRedirect();
			}
	 
			$this->setDataType("form");
			$this->setActionType("modify");
	 
			$data = $this->prepareData($inputData, "object");
	 
			$this->setData($data);
			return $this->doData();
		}
	};
?>