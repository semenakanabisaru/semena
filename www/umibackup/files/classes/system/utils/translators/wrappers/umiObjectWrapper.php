<?php
	class umiObjectWrapper extends translatorWrapper {
		public function translate($data) {
			return $this->translateData($data);
		}

		protected function translateData(iUmiObject $object) {
			$objectId = $object->getId();
			$resultArray = Array();

			$resultArray['attribute:id'] = $objectId;
			$resultArray['attribute:guid'] = $object->getGUID();
			$resultArray['attribute:name'] = $object->getName();
			$resultArray['attribute:type-id'] = $object->getTypeId();
			$resultArray['attribute:type-guid'] = $object->getTypeGUID();

			$ownerId = $object->getOwnerId();
			if($ownerId) {
				$resultArray['attribute:ownerId'] = $ownerId;
			}
			if($this->isFull === false) {
				$resultArray['xlink:href'] = "uobject://" . $objectId;
				return $resultArray;
			}

			$objectTypeId = $object->getTypeId();
			$objectType = umiObjectTypesCollection::getInstance()->getType($objectTypeId);
			$objectFieldsGroupsList = $objectType->getFieldsGroupsList();


			if(!is_null(getRequest('links'))) {
				$cmsController = cmsController::getInstance();
				$hierarchyTypesColleciton = umiHierarchyTypesCollection::getInstance();
				$objectTypesCollection    = umiObjectTypesCollection::getInstance();
				$workType = $objectType;

				$i = 0;
				do {
					$hierarchyTypeId = $workType->getHierarchyTypeId();
					$hierarchyType   = $hierarchyTypesColleciton->getType($hierarchyTypeId);
					if($workType->getParentId()) {
						$workType = $objectTypesCollection->getType($workType->getParentId());
						break;
					}
					if($workType->getParentId() == 0) break;
				} while(!$hierarchyType && $workType);

				if($hierarchyType instanceof iUmiHierarchyType) {
					$moduleName = $hierarchyType->getName();
					$methodName = $hierarchyType->getExt();

					if($objectModuleInstance = $cmsController->getModule($moduleName)) {
						$link = $objectModuleInstance->getObjectEditLink($objectId, $methodName);

						if($link !== false) {
							$resultArray['edit-link'] = $link;
						}
					}
				}

				if(!isset($resultArray['edit-link']) &&
				$cmsController->getCurrentModule() == 'data' && $cmsController->getCurrentMethod() == 'guide_items') {
					$dataModuleInstance = $cmsController->getModule('data');
					$resultArray['edit-link'] = $dataModuleInstance->getObjectEditLink($objectId);
				}
			}


			$resultArray['properties'] = Array();
			$resultArray['properties']['nodes:group'] = Array();
			$i = 0;
			foreach($objectFieldsGroupsList as $group) {
				$groupArray = Array();

				$groupWrapper = translatorWrapper::get($group);
				$groupArray = $groupWrapper->translateProperties($group, $object);

				if(!empty($groupArray)) {
					$resultArray['properties']['nodes:group'][(getRequest('jsonMode') == "force" ? $i++ : ++$i)] = $groupArray;
				}
			}

			if(sizeof($resultArray['properties']['nodes:group']) == 0) {
				unset($resultArray['properties']);
			}

			return $resultArray;
		}
	};
?>