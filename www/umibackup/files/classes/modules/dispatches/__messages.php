<?php
	abstract class __messages_messages {
		public function getReleaseMessages($iReleaseId = false) {
			$iHierarchyTypeId = umiHierarchyTypesCollection::getInstance()->getTypeByName("dispatches", "message")->getId();
			$iMsgTypeId =  umiObjectTypesCollection::getInstance()->getTypeByHierarchyTypeId($iHierarchyTypeId);
			$oMsgType = umiObjectTypesCollection::getInstance()->getType($iMsgTypeId);

			$sel = new selector('objects');
			$sel->types('object-type')->name('dispatches', 'message');
			if($iReleaseId) {
				$sel->where('release_reference')->equals($iReleaseId);
			}
			selectorHelper::detectFilters($sel);
			return $sel->result;
		}

		
		public function add_message() {
			$type = "message";
			$dispatch_rel = (int) getRequest("param0");
			$mode = (string) getRequest("param1");

			//Подготавливаем список параметров
			$inputData = Array("type" => $type);

			if($mode == "do") {
				$object = $this->saveAddedObjectData($inputData);
				$object->setValue('release_reference', $this->getNewReleaseInstanceId($dispatch_rel));
				$this->chooseRedirect($this->pre_lang . '/admin/dispatches/edit/' . $object->getId() . "/");
			}

			$this->setDataType("form");
			$this->setActionType("create");

			$data = $this->prepareData($inputData, "object");

			$this->setData($data);
			return $this->doData();
		}
	};
?>