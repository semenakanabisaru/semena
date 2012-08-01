<?php
	abstract class __social_networks extends baseModuleAdmin {
	
		

		
		public function _network_settings($network) { 
			$mode = getRequest("param0");
			
			$type = $network->getCodeName();
			
			$this->setHeaderLabel(getLabel("header-social_networks-settings") . $network->getName());
			
			$inputData = array(
				'object'		=> $network -> getObject(),
				'type'			=> $type
			);

			if($mode == "do") {
				$object = $this->saveEditedObjectData($inputData);
				$this->chooseRedirect($this->pre_lang . '/admin/social_networks/' . $type . '/');
			}

			$this->setDataType("form");
			$this->setActionType("modify");

			$data = $this->prepareData($inputData, "object");

			$this->setData($data); 
			return $this->doData();
		}

	};
?>