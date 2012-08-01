<?php
	class umiHierarchyElementWrapper extends translatorWrapper {
		public static $currentPageTranslated = false;
		
		public function translate($data) {
			return $this->translateData($data);
		}
		
		protected function translateData(iUmiHierarchyElement $element) {
			$elementId = $element->getId();
			$resultArray = array();
			
			$cmsController = cmsController::getInstance();
			$hierarchy = umiHierarchy::getInstance();
			$hierarchyTypes = umiHierarchyTypesCollection::getInstance();
			$objectTypes = umiObjectTypesCollection::getInstance();
			$objects = umiObjectsCollection::getInstance();
			$regedit = regedit::getInstance();

			$resultArray['@id'] = $elementId;
			$resultArray['@parentId'] = $element->getParentId();
			$resultArray['@link'] = $hierarchy->getPathById($elementId);

			if($is_default = $element->getIsDefault()) {
				$resultArray['@is-default'] = $is_default;
			}

			if($is_visible = $element->getIsVisible()) {
				$resultArray['@is-visible'] = $is_visible;
			}

			if($is_active = $element->getIsActive()) {
				$resultArray['@is-active'] = $is_active;
			}
			
			if($is_deleted = $element->getIsDeleted()) {
				$resultArray['@is-deleted'] = $is_deleted;
			}
			
			$lockedId = $element->getObject()->getValue("lockuser");
			if($lockedId > 0) {
				$lockTime = $element->getObject()->getValue("locktime");
				$currentUser = $cmsController->getModule("users")->user_id;
				$lockDuration = $regedit->getVal("//settings/lock_duration");
				if ($lockTime && ($lockTime->timestamp + $lockDuration) > time()) {
					if ($currentUser!= $lockedId) {
						$lockInfo['user-id'] = $lockedId;
						$whoLocked = $objects->getObject($lockedId);
						$lockInfo['login'] = $whoLocked->getValue("login");
						$lockInfo['lname'] = $whoLocked->getValue("lname");
						$lockInfo['fname'] = $whoLocked->getValue("fname");
						$lockInfo['father-name'] = $whoLocked->getValue("father_name");
						$lockInfo ['locktime'] = $lockTime->getFormattedDate();
						$lockInfo ['@ts'] = $lockTime->timestamp;
						$resultArray['locked-by'] = $lockInfo;
					}
				} else {
					$object = $element->getObject();
					$object->setValue("lockuser", null);
					$object->setValue("locktime", null);
					$object->commit();
					$element->commit();
				}
			}
			$page = $element->getObject();
			$expirationTime = $page->getValue("expiration_date");

			$pubStatusId = $page->getValue("publish_status");
			$statusObject = $objects->getObject($pubStatusId);
			$status = array();
			if ($regedit->getVal("//settings/expiration_control")) {
				if ($statusObject) {
					$status['attribute:id'] = strlen($tmp = $statusObject->getValue("publish_status_id")) ? $tmp : 'page_status_publish';
					$status['node:name'] = $statusObject->getName();
					$expiration['status'] = $status;
					if ($expirationTime) {
						$expiration['attribute:ts'] = $expirationTime->timestamp;
						$expiration['date'] = $expirationTime->getFormattedDate();
						$expiration['comments'] = $page->getValue("publish_comments");
					}
				}else {
					$status['@id'] = 'page_status_publish';
					$status['#name'] = getLabel ('object-status-publish');
					$expiration['status'] = $status;
					$expiration['@ts'] = "";

				}
				$resultArray['expiration'] = $expiration;
			}
			if(!is_null(getRequest('virtuals'))) {
				$aVirtuals = $hierarchy->checkIsVirtual(array($elementId => false));
				if(isset($aVirtuals[$elementId]) && $aVirtuals[$elementId]) {
					$resultArray['virtual-copy'] = array('attribute:count' => $aVirtuals[$elementId]);
				}
			}

			$resultArray['@object-id'] = $element->getObject()->getId();
			$resultArray['@object-guid'] = $element->getObject()->getGUID();
			$resultArray['@type-id'] = $element->getObject()->getTypeId();
			$resultArray['@type-guid'] = $element->getObject()->getTypeGUID();
			$resultArray['@update-time'] = $element->getUpdatetime();
			$resultArray['@alt-name'] = $element->getAltName();



			if(!is_null(getRequest('templates'))) {
				$resultArray['@template-id'] = $element->getTplId();
				$resultArray['@domain-id'] = $element->getDomainId();
				$resultArray['@lang-id'] = $element->getLangId();
			}

			if(!is_null(getRequest('childs'))) {
				$childs = $hierarchy->getChildsCount($elementId, true, true, 1);
				$resultArray['childs'] = $childs;
			}

			if(!is_null(getRequest('permissions'))) {
				$permissionsColletion = permissionsCollection::getInstance();
				$permissionsLevel = $permissionsColletion->isAllowedObject($permissionsColletion->getUserId(), $elementId);
				$resultArray['permissions'] = ($permissionsLevel[4] ? 16 : 0) |
											  ($permissionsLevel[3] ?  8 : 0) |
											  ($permissionsLevel[2] ?  4 : 0) |
											  ($permissionsLevel[1] ?  2 : 0) |
											  ($permissionsLevel[0] ?  1 : 0);
			}

			$hierarchy_type_id = $element->getTypeId();
			$hierarchy_type = $hierarchyTypes->getType($hierarchy_type_id);

			if(!is_null(getRequest('links')) && !$element->isDeleted) {
				$elementModuleName = $hierarchy_type->getName();
				if($elementModuleInstance = $cmsController->getModule($elementModuleName)) {
					$links = $elementModuleInstance->getEditLink($elementId, $hierarchy_type->getExt());

					if(is_array($links)) {
						if($links[0]) {
							$resultArray['create-link'] = $links[0];
						}

						if($links[1]) {
							$resultArray['edit-link'] = $links[1];
						}
					}
				}
			}

			if($hierarchy_type instanceof iUmiHierarchyType) {
				$resultArray['basetype'] = $hierarchy_type;
			}

			$resultArray['name'] = str_replace(array("<",">"), array('&lt;', '&gt;'),$element->getName());

			if(!$this->isFull) {
				if($elementId == $cmsController->getCurrentElementId() && !self::$currentPageTranslated) {
					$this->isFull = true;
					self::$currentPageTranslated = true;
				} else {
					$this->isFull = false;
				}
			}

			if($this->isFull == false) {
				$resultArray['xlink:href'] = "upage://" . $elementId;
				return $resultArray;
			}


			$object = $element->getObject();
			$objectTypeId = $object->getTypeId();
			$objectType = $objectTypes->getType($objectTypeId);
			$objectFieldsGroupsList = $objectType->getFieldsGroupsList();

			$resultArray['properties'] = array('nodes:group' => array());

			$i = 0;
			foreach($objectFieldsGroupsList as $group) {
				$groupWrapper = translatorWrapper::get($group);
				$grouparray = $groupWrapper->translateProperties($group, $object);
				if(!empty($grouparray)) {
					$resultArray['properties']['nodes:group'][(getRequest('jsonMode') == "force" ? $i++ : ++$i)] = $grouparray;
				}
			}

			return $resultArray;
		}
	}
?>