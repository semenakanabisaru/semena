<?php
	abstract class __json_content extends baseModuleAdmin {




		/* Deprecated: only for 2.7.4 -> 2.8.0 migration */
		protected function old_eip_get_editable_region() {
			$iEntityId = getRequest('param0');
			$sPropName = getRequest('param1');
			$bIsObject = (bool) getRequest('is_object');

			$buffer = outputBuffer::current('HTTPOutputBuffer');
			$buffer->charset('utf-8');
			$buffer->contentType('text/xml');

			$oEntity = ($bIsObject) ? umiObjectsCollection::getInstance()->getObject($iEntityId) : umiHierarchy::getInstance()->getElement($iEntityId);

			// Checking rights
			$bDisallowed = false;
			$oUsers      = cmsController::getInstance()->getModule('users');
			$svId = umiObjectsCollection::getInstance()->getObjectIdByGUID('system-supervisor');
			if($oUsers->user_id != $svId) {
				if($bIsObject) {
					$bDisallowed = !($oEntity->getOwnerId() == $oUsers->user_id);
				} else {
					list($r, $w) = permissionsCollection::getInstance()->isAllowedObject($oUsers->user_id, $iEntityId);
					if(!$w) $bDisallowed = true;
				}
			}
			if($bDisallowed) {
				$sResult = "<"."?xml version=\"1.0\" encoding=\"utf-8\"?"."><error>".getLabel('error-no-permissions')."</error>";
				$buffer->push($sResult);
				$buffer->end();
			}


			$sPropValues = "";

			$sPropType = "";

			if ($oEntity) {
				switch($sPropName) {
					case "name":
						$sPropType = "string";
						$sVal = $oEntity->getName();
						$sPropValues = <<<END
							<value><![CDATA[$sVal]]></value>
END;
					break;

					default:
						$vVal = $oEntity->getValue($sPropName);
						$oObject = (!$bIsObject)? $oEntity->getObject() : $oEntity;

						$oProperty = $oObject->getPropByName($sPropName);

						if ($oProperty instanceof umiObjectProperty) {
							$oField = $oProperty->getField();
							$sPropType = $oField->getFieldType()->getDataType();

							switch($sPropType) {
								case "int":
								case "price":
								case "float":

								case "string":
								case "wysiwyg":
								case "text":

									$sVal = $oEntity->getValue($sPropName);
									$sPropValues = <<<END
										<value><![CDATA[$sVal]]></value>
END;
								break;
								case "relation" :
									$iGuideId = $oField->getGuideId();
									$arrGuidedItems = umiObjectsCollection::getInstance()->getGuidedItems($iGuideId);
									$iSelG = $oProperty->getValue();
									foreach ($arrGuidedItems as $iGId => $sGName) {
										$sPropValues .= "<value id=\"" . $iGId . "\" selected=\"" . ($iGId == $iSelG ? 1 : 0) . "\"><![CDATA[".$sGName."]]></value>";
									}
								break;

								case "boolean" :
									$val = $oProperty->getValue();
									$yes = $val ? "selected='1'" : "";
									$no = !$val ? "selected='1'" : "";

									$sPropValues .= <<<END
										<value id="1" {$yes}>Да</value>
										<value id="0" {$no}>Нет</value>
END;
								break;

								case "tags": {
									$sVal = implode(", ", $oEntity->getValue($sPropName));
									$sPropValues = <<<END
										<value><![CDATA[$sVal]]></value>
END;
									break;
								}

								case "date": {
									$date_obj = $oEntity->getValue($sPropName);
									$sVal = (is_object($date_obj)) ? $date_obj->getFormattedDate("Y-m-d H:i") : "";
									$sPropValues = <<<END
										<value><![CDATA[$sVal]]></value>
END;
									break;
								}
							}
						}
					break;
				}
			}


			$sResult = "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">";
			$sResult .= <<<END

				<get_editable>
					<property name="{$sPropName}" type="{$sPropType}">
						{$sPropValues}
					</property>
				</get_editable>

END;

			$buffer->push($sResult);
			$buffer->end();
		}


		/* Deprecated: only for 2.7.4 -> 2.8.0 migration */
		public function old_eip_save_editable_region() {
			$iEntityId = getRequest('param0');
			$sPropName = getRequest('param1');
			$sContent = getRequest('data');
			$bIsObject = (bool) getRequest('is_object');

			$buffer = outputBuffer::current('HTTPOutputBuffer');
			$buffer->charset('utf-8');
			$buffer->contentType('text/xml');

			$oEntity = ($bIsObject) ? umiObjectsCollection::getInstance()->getObject($iEntityId) : umiHierarchy::getInstance()->getElement($iEntityId);

			// Checking rights
			$bDisallowed = false;
			$svId = umiObjectsCollection::getInstance()->getObjectIdByGUID('system-supervisor');
			$oUsers      = cmsController::getInstance()->getModule('users');
			if($oUsers->user_id != $svId) {
				if($bIsObject) {
					$bDisallowed = !($oEntity->getOwnerId() == $oUsers->user_id);
				} else {
					list($r, $w) = permissionsCollection::getInstance()->isAllowedObject($oUsers->user_id, $iEntityId);
					if(!$w) $bDisallowed = true;
				}
			}
			if($bDisallowed) {
				$sResult = "<"."?xml version=\"1.0\" encoding=\"utf-8\"?"."><error>".getLabel('error-no-permissions')."</error>";
				header("Content-type: text/xml; charset=utf-8");
				$buffer->push($sResult);
				$buffer->end();
			}

			if($oEntity instanceof umiHierarchyElement) {
				$backupModel = backupModel::getInstance();
				$backupModel->addLogMessage($oEntity->getId());

				if($sPropName == "name" && strlen($sContent)) {
					if($oEntity->getValue("h1") == $oEntity->getName()) {
						$oEntity->setValue("h1", $sContent);
					}
				}

				if($sPropName == "h1" && strlen($sContent)) {
					if($oEntity->getValue("h1") == $oEntity->getName()) {
						$oEntity->setName($sContent);
					}
				}
			}


			$sPropValue = "";
			if ($oEntity) {
				$bOldVal = umiObjectProperty::$IGNORE_FILTER_INPUT_STRING;
				umiObjectProperty::$IGNORE_FILTER_INPUT_STRING = true;
				switch($sPropName) {
					case "name" :
						if (strlen($sContent)) {
							$oEntity->setName($sContent);
						}
						break;

					default:
						$oEntity->setValue($sPropName, $sContent); break;
				}
				$oEntity->commit();
				umiObjectProperty::$IGNORE_FILTER_INPUT_STRING = $bOldVal;

				$oObject = (!$bIsObject)? $oEntity->getObject() : $oEntity;
				$oObject->update();
				$oEntity->update();


				switch($sPropName) {
					case "name" :
						$sPropValue = $oEntity->getName(); break;
					default:
						$oProperty = $oObject->getPropByName($sPropName);
						if ($oProperty instanceof umiObjectProperty) {
							$oField = $oProperty->getField();
							$sPropType = $oField->getFieldType()->getDataType();
							switch($sPropType) {
								case "text":
								case "int":
								case "price":
								case "float":
								case "string" :
									$sVal = $oEntity->getValue($sPropName);
									$sPropValue = $sVal;
								break;
								case "wysiwyg" :
									$sVal = $oEntity->getValue($sPropName);
									$sPropValue = templater::getInstance()->parseInput($sVal);
								break;
								case "boolean" :
									$val = $oEntity->getValue($sPropName);
									$sPropValue = $val ? "Да" : "Нет";
								break;
								case "relation" :
									$oGuide = umiObjectsCollection::getInstance()->getObject((int) $sContent);
									if ($oGuide instanceof umiObject) {
										$sPropValue = $oGuide->getName();
									}
								break;

								case "tags": {
									$sVal = $oEntity->getValue($sPropName);
									$sPropValue = implode(", ", $sVal);
									break;
								}

								case "date": {
									$oEntity->setValue($sPropName, umiDate::getTimeStamp($sContent));
									$sVal = $oEntity->getValue($sPropName);
									$sPropValue = (is_object($sVal)) ? $sVal->getFormattedDate("Y-m-d H:i") : "";
									break;
								}
							}
						}
					break;
				}

			}

			if($oEntity instanceof umiEntinty) {
				$oEntity->commit();
			}

			$sResult = "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">";
			$sResult .= <<<END

				<save_editable>
					<property name="{$sPropName}">
						<value><![CDATA[$sPropValue]]></value>
					</property>
				</save_editable>

END;

			$buffer->push($sResult);
			$buffer->end();
		}


		public function get_editable_region() {
			// fix for 2.7.4 eip
			if (cmsController::getInstance()->getCurrentMode() != 'admin') {
				return self::old_eip_get_editable_region();
			}

			$itemId = getRequest('param0');
			$propName = getRequest('param1');
			$isObject = (bool) getRequest('is_object');

			$objects = umiObjectsCollection::getInstance();
			$hierarchy = umiHierarchy::getInstance();
			$cmsController = cmsController::getInstance();

			$oEntity = ($isObject) ? $objects->getObject($itemId) : $hierarchy->getElement($itemId);

			// Checking rights
			$bDisallowed = false;

			$permissions = permissionsCollection::getInstance();
			$userId = $permissions->getUserId();
			$groupIds = umiObjectsCollection::getInstance()->getObject($userId)->getValue('groups');
			$svGroupId = umiObjectsCollection::getInstance()->getObjectIdByGUID('users-users-15');
			$svId = umiObjectsCollection::getInstance()->getObjectIdByGUID('system-supervisor');

			if($userId != $svId && !in_array($svGroupId, $groupIds)) {
				if($isObject) {
					$bDisallowed = !($oEntity->getOwnerId() == $userId);
					if($bDisallowed) {
						//Check module permissions

						$object = selector::get('object')->id($itemId);
						$module = $object->getModule();
						$method = $object->getMethod();
						if($module && $method) {
							$bDisallowed = !$permissions->isAllowedMethod($userId, $module, $method);
						} else {
							throw new publicAdminException(getLabel('js-edcell-unsupported-type'));
						}
					}
				} else {
					list($r, $w) = $permissions->isAllowedObject($userId, $itemId);
					if(!$w) $bDisallowed = true;
				}
			}

			if($bDisallowed) {
				throw new publicAdminException(getLabel('error-no-permissions'));
			}

			$result = false;
			if ($oEntity) {
				switch($propName) {
					case "name":
						$result = array('name' => $oEntity->name);
					break;

					default:
						$oObject = (!$isObject)? $oEntity->getObject() : $oEntity;
						$prop = $oObject->getPropByName($propName);
						if (!$prop instanceof umiObjectProperty) {
							throw new publicAdminException(getLabel('error-property-not-exists'));
						}
						$result = array('property' => $prop);
						translatorWrapper::get($oObject->getPropByName($propName));
						umiObjectPropertyWrapper::$showEmptyFields = true;
				}
			}

			if (!is_array($result)) {
				throw new publicAdminException(getLabel('error-entity-not-exists'));
			}

			$this->setData($result);
			return $this->doData();
		}


		public function save_editable_region() {
			// fix for 2.7.4 eip
			if (cmsController::getInstance()->getCurrentMode() != 'admin') {
				return self::old_eip_save_editable_region();
			}

			$iEntityId = getRequest('param0');
			$sPropName = getRequest('param1');
			$content = getRequest('data');
			$bIsObject = (bool) getRequest('is_object');

			if (is_array($content) && count($content) == 1) {
				$content = $content[0];
			} else if(is_array($content) && isset($content[0])) {
				$temp = array();
				foreach($content as $item) {
					$temp[] = is_array($item) ? $item[0] : $item;
				}
				$content = $temp;
			}

			$oEntity = ($bIsObject) ? umiObjectsCollection::getInstance()->getObject($iEntityId) : umiHierarchy::getInstance()->getElement($iEntityId);

			// Checking rights
			$bDisallowed = false;

			$permissions = permissionsCollection::getInstance();
			$userId = $permissions->getUserId();
			$svId = umiObjectsCollection::getInstance()->getObjectIdByGUID('system-supervisor');

			if($userId != $svId) {
				if($bIsObject) {
					$bDisallowed = !($oEntity->getOwnerId() == $userId);
					if($bDisallowed) {
						//Check module permissions
						$object = selector::get('object')->id($iEntityId);
						$module = $object->getModule();
						$method = $object->getMethod();
						if($module && $method) {
							$bDisallowed = !$permissions->isAllowedMethod($userId, $module, $method);
						}
					}
				} else {
					list($r, $w) = $permissions->isAllowedObject($userId, $iEntityId);
					if(!$w) $bDisallowed = true;
				}
			}

			if($bDisallowed) {
				throw new publicAdminException(getLabel('error-no-permissions'));
			}

			if($oEntity instanceof iUmiHierarchyElement) {
				$backupModel = backupModel::getInstance();
				$backupModel->addLogMessage($oEntity->getId());
			}

			if($bIsObject && $sPropName == 'is_activated') {
				$permissions = permissionsCollection::getInstance();
				$userId = $permissions->getUserId();
				$guestId = $permissions->getGuestId();

				if($iEntityId == SV_USER_ID) {
					throw new publicAdminException(getLabel('error-users-swtich-activity-sv'));
				}

				if($iEntityId == $guestId) {
					throw new publicAdminException(getLabel('error-users-swtich-activity-guest'));
				}

				if($iEntityId == $userId) {
					throw new publicAdminException(getLabel('error-users-swtich-activity-self'));
				}
			}

			$sPropValue = "";
			if ($oEntity) {
				$bOldVal = umiObjectProperty::$IGNORE_FILTER_INPUT_STRING;
				umiObjectProperty::$IGNORE_FILTER_INPUT_STRING = true;

				$oObject = (!$bIsObject)? $oEntity->getObject() : $oEntity;

				$oldValue = null;

				try  {
					if($sPropName == 'name') {
						if (is_string($content) && strlen($content)) {
							$oldValue = $oEntity->name;
							$oEntity->name = $content;
							if($oEntity instanceof iUmiHierarchyElement) {
								$oEntity->h1 = $content;
							}
						}
						$result = array('name' => $content);
					} else {
						$property = $oObject->getPropByName($sPropName);
						if($property->getDataType() == 'date') {
							$date = new umiDate();
							$date->setDateByString($content);
							$content = $date;
						}

						$oldValue = $oEntity->getValue($sPropName);
						$oEntity->setValue($sPropName, $content);
						if($oEntity instanceof iUmiHierarchyElement && $sPropName == 'h1') {
							$oEntity->name = $content;
						}
						$result = array('property' => $property);

						translatorWrapper::get($property);
						umiObjectPropertyWrapper::$showEmptyFields = true;
					}
				} catch (fieldRestrictionException $e) {
					throw new publicAdminException($e->getMessage());
				}
				$oEntity->commit();
				umiObjectProperty::$IGNORE_FILTER_INPUT_STRING = $bOldVal;

				$oObject->update();
				$oEntity->update();

				if($oEntity instanceof umiEntinty) {
					$oEntity->commit();
				}

				$event = new umiEventPoint("systemModifyPropertyValue");
				$event->addRef("entity", $oEntity);
				$event->setParam("property", $sPropName);
				$event->setParam("oldValue", $oldValue);
				$event->setParam("newValue", $content);
				$event->setMode("after");
				$event->call();

				$this->setData($result);
				return $this->doData();
			}
		}


		public function json_uf() {
			$dir = getRequest('dir');
			$var = getRequest('var');

			if($var) {
				$file = umiImageFile::upload("pics", $var, $dir);

				if(is_object($file)) {
					if($file->getIsBroken() == false) {
						header("Content-type: text/xml; charset=utf-8");
						echo file_get_contents("ufs://" . $file->getFilePath());
						exit();
					}
				}
			}
			header("Content-type: text/xml; charset=utf-8");
			echo file_get_contents("ufs://" . $dir);
			exit();
		}


		public function filterString($string) {
			return str_replace("\"", "\\\"", str_replace("'", "\'", $string));
		}


		public function load_tree_node() {
			$this->setDataType("list");
			$this->setActionType("view");

			$sel = new selector('pages');
			selectorHelper::detectFilters($sel);

			$data = $this->prepareData($sel->result, "pages");
			$this->setData($data, $sel->length);
			return $this->doData();
		}

		public function tree_set_activity() {
			$elements = getRequest('element');
			if(!is_array($elements)) {
				$elements = Array($elements);
			}

			$active = getRequest('active');

			if (!is_null($active)) {
				foreach($elements as $elementId) {
					$element = $this->expectElement($elementId, false, true);

					if ($element instanceof umiHierarchyElement) {
						$active = intval($active) > 0 ? true : false;

						$params = Array(
							"element" => $element,
							"activity" => $active
						);

                              $oEventPoint = new umiEventPoint("systemSwitchElementActivity");
                              $oEventPoint->setMode("before");

                              $oEventPoint->addRef("element", $element);
                              $this->setEventPoint($oEventPoint);

						$this->switchActivity($params);

                              // after del event
                              $oEventPoint->setMode("after");
                              $this->setEventPoint($oEventPoint);	
					
					} else {
						throw new publicAdminException(getLabel('error-expect-element'));
					}
				}

				$this->setDataType("list");
				$this->setActionType("view");
				$data = $this->prepareData($elements, "pages");
				$this->setData($data);

				return $this->doData();
			} else {
				throw new publicAdminException(getLabel('error-expect-action'));
			}
		}


		public function tree_move_element() {
			$element =  $this->expectElement("element");
			$parentId = (int) getRequest("rel");
			$domain = getRequest('domain');
			$asSibling = (int) getRequest('as-sibling');
			$beforeId = getRequest('before');

			if ($element instanceof umiHierarchyElement) {
				$oldParentId = $element->getParentId();

				$params = array(
					"element" => $element,
					"parent-id" => $parentId,
					"domain" => $domain,
					"as-sibling" => $asSibling,
					"before-id" => $beforeId
				);

				$this->moveElement($params);
			} else {
				throw new publicAdminException(getLabel('error-expect-element'));
			}

			$this->setDataType("list");
			$this->setActionType("view");
			$data = $this->prepareData(array($element->getId(), $element->getParentId(), $oldParentId), "pages");
			$this->setData($data);

			return $this->doData();
		}


		public function tree_delete_element() {
			$elements = getRequest('element');
			if(!is_array($elements)) {
				$elements = Array($elements);
			}

            $parentIds = Array();

			foreach($elements as $elementId) {
				$element = $this->expectElement($elementId, false, true, true);

				if ($element instanceof umiHierarchyElement) {
					// before del event
					$element_id = $element->getId();
					$parentIds[] = $element->getParentId();
					$oEventPoint = new umiEventPoint("content_del_element");
					$oEventPoint->setMode("before");
					$oEventPoint->setParam("element_id", $element_id);
					$oEventPoint->addRef("answer", $sRes);
					$this->setEventPoint($oEventPoint);

					// try delete
					$params = Array(
						"element" => $element
					);

					$this->deleteElement($params);
					
					// after del event
					$oEventPoint->setMode("after");
					$this->setEventPoint($oEventPoint);	
				} else {
					throw new publicAdminException(getLabel('error-expect-element'));
				}
			}

			$parentIds = array_unique($parentIds);

			// retrun parent element for update
			$this->setDataType("list");
			$this->setActionType("view");
			$data = $this->prepareData($parentIds, "pages");

			$this->setData($data);

			return $this->doData();
		}

		public function tree_copy_element() {
			$element =  $this->expectElement("element");
			$cloneMode = (bool) getRequest('clone_mode');
			$copyAll = (bool) getRequest('copy_all');

			$new_element_id = false;

			if ($element instanceof umiHierarchyElement) {
				$element_id = $element->getId();
				$parent_id = umiHierarchy::getInstance()->getParent($element_id);

				if((defined("DB_DRIVER") && DB_DRIVER != "xml") || (!defined("DB_DRIVER"))) {
					l_mysql_query("START TRANSACTION");
				}

				if ($cloneMode) {
					// create real copy
					$clone_allowed = true;

					if ($clone_allowed) {
						$event = new umiEventPoint("systemCloneElement");
						$event->addRef("element", $element);
						$event->setParam("elementId", $element_id);
						$event->setParam("parentId", $parent_id);
						$event->setMode("before");
						$event->call(); 

						$new_element_id = umiHierarchy::getInstance()->cloneElement($element_id, $parent_id, $copyAll);

						$event->setParam("newElementId", $new_element_id);
						$event->setMode("after");
						$event->call();
						
						$new_element = umiHierarchy::getInstance()->getElement((int) $new_element_id, false, false);
						
						$event = new umiEventPoint("systemCreateElementAfter");
						$event->addRef("element", $new_element);
						$event->setParam("elementId", $new_element_id);
						$event->setParam("parentId", $parent_id);
						$event->setMode("after");
						$event->call(); 

					}
				} else {
					// create virtual copy
					$event = new umiEventPoint("systemVirtualCopyElement");
					$event->setParam("elementId", $element_id);
					$event->setParam("parentId", $parent_id);
                         $event->addRef("element", $element);
					$event->setMode("before");
					$event->call();

					$new_element_id = umiHierarchy::getInstance()->copyElement($element_id, $parent_id, $copyAll);

					$event->setParam("newElementId", $element_id);
					$event->setMode("after");
					$event->call();

//
                         $new_element = umiHierarchy::getInstance()->getElement((int) $new_element_id, false, false);
                         
                         $event = new umiEventPoint("systemCreateElementAfter");
                         $event->addRef("element", $new_element);
                         $event->setParam("elementId", $new_element_id);
                         $event->setParam("parentId", $parent_id);
                         $event->setMode("after");
                         $event->call(); 
				}

				if ($new_element_id) {
					$this->setDataType("list");
					$this->setActionType("view");
					$data = $this->prepareData(array($new_element_id), "pages");
					$this->setData($data);

					if((defined("DB_DRIVER") && DB_DRIVER != "xml") || (!defined("DB_DRIVER"))) {
						l_mysql_query("COMMIT");
					}

					return $this->doData();
				} else {
					throw new publicAdminException(getLabel('error-copy-element'));
				}

			} else {
				throw new publicAdminException(getLabel('error-expect-element'));
			}
		}

		public function tree_unlock_page() {
			$pageId = getRequest("param0");
			$this->unlockPage($pageId);
			$result = "<"."?xml version=\"1.0\" encoding=\"utf-8\"?"."> \n";
			$result.= "<is_unlocked>true</is_unlocked> \n";
			header("Content-type: text/xml; charset=utf-8");
			$this->flush($result);
		}

		public function json_unlock_page() {
			$this->tree_unlock_page();
		}

		public function copy_to_lang() {
			$langId = (int) getRequest('lang-id');
			$domainId = (int) getRequest('domain-id');
			$alias_new = (array) getRequest('alias');
			$move_old = (array) getRequest('move');
			$force = (int) getRequest('force');
			$mode = (string) getRequest('mode');
			
			$elements = getRequest('element');
			if(!is_array($elements)) {
				$elements = Array($elements);
			}

			foreach($alias_new as $k=>$v) {
				$alias_new[$k] = umiHierarchy::convertAltName($v);
			} 
			
			if (!is_null($langId)) {
				$hierarchy = umiHierarchy::getInstance();

				if(!$force) {
					$aliases_old = array();
				foreach($elements as $elementId) {
						if(!empty($move_old[$elementId])) {
							continue;
						}
						
					$element = $this->expectElement($elementId, false, true);
					
						$alt_name = $element->getAltName();
						
						if(!empty($alias_new[$element->getId()])) {
							$alt_name = $alias_new[$element->getId()];
						}
						
						$errors = array();
						$element_dst =  umiHierarchy::getInstance()->getIdByPath( $alt_name , false, $errors,$domainId , $langId);
						$element_dst = $this->expectElement($element_dst, false, true);
						
						
						
						if($element_dst && $element_dst->getAltName() == $alt_name) {
							$alt_name_normal = $hierarchy->getRightAltName($alt_name, $element_dst, false, true);
							
							$aliases_old[$element->getId()] = array($alt_name, $alt_name_normal);
					}
					} 
					
					if(count($aliases_old) ) 
					{
							$this->setDataType("list");
							$this->setActionType("view");
							$data = array('error'=>array());//$this->prepareData(array(), "pages");
							
							
							$data['error']['nodes:item'] = array();
							$data['error']['type'] = '__alias__';
							
							$path = "http://".domainsCollection::getInstance()->getDomain($domainId)->getHost() ."/";
							
							if( !langsCollection::getInstance()->getLang($langId)->getIsDefault()) {
								$path .= langsCollection::getInstance()->getLang($langId)->getPrefix() . '/';
				}
							
							foreach($aliases_old as $k=>$v) {
								$data['error']['nodes:item'] [] = array('attribute:id'=>$k, 'attribute:path'=>$path , 'attribute:alias'=>$v[0], 'attribute:alt_name_normal'=>$v[1]);
			}
							
							$this->setData($data);
							return $this->doData();
					}
				}
				
				$templatesCollection = templatescollection::getInstance();
				
				$templates = $templatesCollection->getTemplatesList($domainId, $langId);
				
				$template_error = false;
				if(empty($templates)) {
					$template_error = true;
				}

				if($template_error) {
			$this->setDataType("list");
			$this->setActionType("view");
			$data = $this->prepareData(array(), "pages");
					
					$dstLang = langsCollection::getInstance()->getLang($langId); 
					$lang = '';			
					if( !$dstLang->getIsDefault()) {
						$lang .= $dstLang->getPrefix() . '/';
					}
					
					$data['error'] = array();
					$data['error'] ['type'] = "__template_not_exists__";
					$data['error'] ['text'] = sprintf(getLabel('error-no-template-in-domain'), $lang);
			$this->setData($data);
			return $this->doData();
		}

					$template_def = $templatesCollection->getDefaultTemplate($domainId, $langId);;
		

				foreach($elements as $elementId) {
					$element = $this->expectElement($elementId, false, true);
		
					$element_template = $templatesCollection->getTemplate($element->getTplId());
					
					$template_has = false;
					foreach($templates as $v) {
						if($v->getFilename() == $element_template->getFilename()) {
							$template_has = $v;
			}
					}
					
					if(!$template_has) 
						$template_has = $template_def;
						
					if(!$template_has) 
						$template_has = reset($templates);
					
					//if($element->getLangId() != $langId || true) {
					
					if($mode=='move') {
						$copyElement = $element;
						$copyElementId = $element->getId();
					}
					else {
						$copyChilds = (bool) getRequest('copy_all');
						$copyElementId = $hierarchy->cloneElement($element->getId(), 0, $copyChilds, false);
						$copyElement = $hierarchy->getElement($copyElementId);
					}


					if($copyElement instanceof umiHierarchyElement) {
						$alt_name = $element->getAltName();
					
						if(!empty($alias_new[$element->getId()])) {
							$alt_name = $alias_new[$element->getId()];
						}
					
						if(!empty($move_old[$element->getId()])) {
							$element_dst =  umiHierarchy::getInstance()->getIdByPath( $alt_name , false, $errors,$domainId , $langId);
							$element_dst = $this->expectElement($element_dst, false, true);
					
							if($element_dst && $element_dst->getAltName() == $alt_name) {
								$hierarchy->delElement($element_dst->getId());
							}
						}

						$copyElement->setLangId($langId);
					
						if($domainId) {
							$copyElement->setDomainId($domainId);
						}

						$copyElement->setAltName( $alt_name );
						
						if($template_has) {
							$copyElement->setTplId($template_has->getId());
						}
						
						$copyElement->commit();


						$childs = $hierarchy->getChilds($copyElementId);
					self::changeChildsLang($childs, $langId, $domainId);
				}
					//}
			}
			}

			$this->setDataType("list");
			$this->setActionType("view");
			$data = $this->prepareData(array(), "pages");
			
			$this->setData($data);
			return $this->doData();
		}
		
		
		public function copy_to_lang_old() {
			$langId = (int) getRequest('lang-id');
			$elements = getRequest('element');
			if(!is_array($elements)) {
				$elements = Array($elements);
			}

			if (!is_null($langId)) {
				$hierarchy = umiHierarchy::getInstance();

				foreach($elements as $elementId) {
					$element = $this->expectElement($elementId, false, true);
					if($element->getLangId() != $langId || true) {
						$copyElementId = $hierarchy->cloneElement($element->getId(), 0, true);
						$copyElement = $hierarchy->getElement($copyElementId);
						if($copyElement instanceof umiHierarchyElement) {
							$copyElement->setLangId($langId);
							$copyElement->commit();

							$childs = $hierarchy->getChilds($copyElementId);
							self::changeChildsLang($childs, $langId);
						}
					}
				}
			}

			$this->setDataType("list");
			$this->setActionType("view");
			$data = $this->prepareData(array(), "pages");
			$this->setData($data);
			return $this->doData();
		}


		public function move_to_lang() {
			$_REQUEST['mode'] = 'move';
			
			return $this->copy_to_lang();exit;
		}
		
		

		protected function changeChildsLang($childs, $langId, $domainId = false) {
			$hierarchy = umiHierarchy::getInstance();

			foreach($childs as $elementId => $subChilds) {
				$element = $hierarchy->getElement($elementId);
				if($element instanceof umiHierarchyElement) {
					$element->setLangId($langId);
					
					if( $domainId ) {
						$element->setDomainId($domainId);
					}
					
					$element->commit();

					if(is_array($subChilds) && sizeof($subChilds))  {
						self::changeChildsLang($subChilds, $langId, $domainId);
					}
				}
			}
		}

	};
?>
