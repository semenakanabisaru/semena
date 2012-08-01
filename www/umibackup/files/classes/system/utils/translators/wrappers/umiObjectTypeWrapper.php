<?php
	class umiObjectTypeWrapper extends translatorWrapper {
		public function translate($data) {
			return $this->translateData($data);
		}
		
		protected function translateData(iUmiObjectType $type) {
			$resultArray = Array();
			$resultArray['attribute:id'] = $type->getId();
			$resultArray['attribute:guid'] = $type->getGUID();
			$resultArray['attribute:title'] = $type->getName();
			$resultArray['attribute:parent-id'] = $type->getParentId();
			
			if(!is_null(getRequest('childs'))) {
				$resultArray['attribute:parentId'] = $type->getParentId();
			}

			if($type->getIsGuidable()) {
				$resultArray['attribute:guide'] = "guide";
			}

			if($type->getIsPublic()) {
				$resultArray['attribute:public'] = "public";
			}

			if($type->getIsLocked()) {
				$resultArray['attribute:locked'] = "locked";
			}

			$hierarchyTypeId = $type->getHierarchyTypeId();
			$hierarchyType = umiHierarchyTypesCollection::getInstance()->getType($hierarchyTypeId);
			$resultArray['base'] = $hierarchyType;
			
			if(!is_null(getRequest('childs'))) {
				$childs = umiObjectTypesCollection::getInstance()->getSubTypesList($type->getId());
				$resultArray['childs'] = sizeof($childs);
			}
			
			if(!is_null(getRequest('links'))) {
				
				$cmsController = cmsController::getInstance();
				$currentModuleName = $cmsController->getCurrentModule();
				$module = $cmsController->getModule($currentModuleName);
				if($module instanceof def_module) {
					$links = $module->getObjectTypeEditLink($type->getId());
					$resultArray['create-link'] = $links['create-link'];
					$resultArray['edit-link'] = $links['edit-link'];
				}
			}

			if($this->isFull) {
				$groupsArray = Array();
				$groupsArray['nodes:group'] = $type->getFieldsGroupsList(xmlTranslator::$showHiddenFieldGroups);
				$resultArray['fieldgroups'] = $groupsArray;
			}

			return $resultArray;
		}
	};
?>