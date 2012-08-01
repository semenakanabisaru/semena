<?php
	abstract class __editor_content {
		public function editValue() {
			$this->flushAsXml('editValue');
			$hierarchy = umiHierarchy::getInstance();
			$objects = umiObjectsCollection::getInstance();

			$mode = getRequest('param0');
			$elementId = getRequest('element-id');
			$objectId = getRequest('object-id');
			$element = null; $object = null;

			if($elementId) {
				$permissions = permissionsCollection::getInstance();
				list($r, $w) = $permissions->isAllowedObject($permissions->getUserId(), $elementId);
				if(!$w)
					throw new publicException(getLabel('eip-no-permissions'));
				$element = $hierarchy->getElement($elementId);
				if($element instanceof iUmiHierarchyElement) {
					$object = $element->getObject();
				} else throw new publicException(getLabel('eip-no-element') . ": #{$elementId}");
			} else if($objectId) {
				$pages  = $hierarchy->getObjectInstances($objectId);
				if(!empty($pages)) {
					$permissions = permissionsCollection::getInstance();
					$userId = $permissions->getUserId();
					$allow  = false;
					foreach($pages as $elementId) {
						 list($r, $w) = $permissions->isAllowedObject($userId, $elementId);
						 if($w) {
							 $allow = true;
							 break;
						 }
					}
					if(!$allow) throw new publicException(getLabel('eip-no-permissions'));
				}
				$object = $objects->getObject($objectId);
				if($object instanceof iUmiObject == false) {
					throw new publicException(getLabel('eip-no-object') . ": #{$elementId}");
				}
			} else throw new publicException(getLabel('eip-nothing-found'));

			$target = $element ? $element : $object;
			$fieldName = getRequest('field-name');
			$value = getRequest('value');

			$result = array();
			if(is_array($fieldName)) {
				$properties = array();
				for($i = 0; $i < count($fieldName); $i++) {
					$properties[] = self::saveFieldValue($fieldName[$i], $value[$i], $target, ($mode == 'save'));
				}
				$result['nodes:property'] = $properties;
			} else {
				$property = self::saveFieldValue($fieldName, $value, $target, ($mode == 'save'));
				$result['property'] = $property;
			}

			return $result;
		}

		protected static function saveFieldValue($name, $value, $target, $save = false) {
			$hierarchy = umiHierarchy::getInstance();

			if($i = strpos($name, '[')) {
				if(preg_match_all("/\[([^\[^\]]+)\]/", substr($name, $i), $out)) {
					$optionParams = array(
						'filter' => array(),
						'field-type' => null
					);

					foreach($out[1] as $param) {
						if(strpos($param, ':')) {
							list($seekType, $seekValue) = explode(':', $param);
							$optionParams['filter'][$seekType] = $seekValue;
						} else {
							$optionParams['field-type'] = $param;
						}
					}
				}
				$name = substr($name, 0, $i);
			} else $optionParams = null;

			if($name != 'name' && $name != 'alt_name') {
				$object = ($target instanceof iUmiHierarchyElement) ? $target->getObject() : $target;
				$property = $object->getPropByName($name);
				if($property instanceof iUmiObjectProperty == false) {
					throw new publicException(getLabel('eip-no-field') . ": \"{$name}\"");
				}
				$field = $property->getField();
			}

			if($name == 'name' || $name == 'alt_name') {
				$type = 'string';
			} else {
				$type = $field->getDataType();
			}

			$value = __editor_content::filterStringValue($value);

			$oldLink = null; $newLink = null;

			if($save) {
				umiObjectProperty::$IGNORE_FILTER_INPUT_STRING = true;
				if($name == 'h1' || $name == 'name') {
					$value = strip_tags($value);
					$value = str_replace("&nbsp;", " ", $value);
					$target->setName($value);
					$target->setValue('h1', $value);

					if($target instanceof iUmiHierarchyElement) {
						$oldLink = $hierarchy->getPathById($target->id);

						$altName = $target->getAltName();
						if(!$altName || substr($altName, 0, 1) == '_') {
							$target->setAltName($value);
							$target->commit();
						}

						$newLink = $hierarchy->getPathById($target->id, false, false, true);
					}
				} elseif($name == 'alt_name'){
					if($target instanceof iUmiHierarchyElement) {
						$target->setAltName($value);
						$target->commit();
						$newLink = $hierarchy->getPathById($target->id, false, false, true);
					}
				} else {
					if($type == 'date') {
						$date = new umiDate();
						$date->setDateByString($value);
						$value = $date; unset($date);
						$value = $value->getFormattedDate('U');
					}

					if($type == 'optioned') {
						$seekType = getArrayKey($optionParams, 'field-type');
						$filter = getArrayKey($optionParams, 'filter');
						$oldValue = $target->getValue($name);
						foreach($oldValue as $i => $v) {
							foreach($filter as $t => $s) {
								if(getArrayKey($v, $t) != $s) continue 2;
								$oldValue[$i][$seekType] = $value;
							}
						}
						$value = $oldValue; unset($oldValue);
					}

					if($type == 'wysiwyg') {
						if(preg_match_all("/href=[\"']?([^ ^\"^']+)[\"']?/i", $value, $out)) {
							foreach($out[1] as $link) {
								$id = $hierarchy->getIdByPath($link);
								if($id) {
									$link  = str_replace("/", "\\/", $link);
									$value = preg_replace("/(href=[\"']?)" . $link . "([\"']?)/i", "\\1%content get_page_url({$id})%\\2", $value);
								}
							}
						}
					} else {
						$value = str_replace("&nbsp;", " ", $value);
					}

					if(in_array($type, array('text', 'string', 'int', 'float', 'price', 'date', 'tags', 'counter'))) {
						$value = preg_replace("/<br ?\/?>/i", "\n", $value);
						$value = strip_tags($value);
					}

					if(in_array($type, array('img_file', 'swf_file', 'file', 'video_file')) && $value) {
						if(substr($value, 0, 1) != '.') $value = '.' . $value;
					}
					$target->setValue($name, $value);
				}
				$target->commit();
				umiObjectProperty::$IGNORE_FILTER_INPUT_STRING = false;

				if($target instanceof iUmiHierarchyElement) {
					$backup = backupModel::getInstance();
					$backup->fakeBackup($target->id);
				}

				$oEventPoint = new umiEventPoint("eipSave");
				$oEventPoint->setMode("after");
				$oEventPoint->setParam("field_name", $name);
				$oEventPoint->setParam("obj", $target);
				def_module::setEventPoint($oEventPoint);

			}

			if($name == 'name') {
				$value = $target->getName();
			} else {
				$value = $target->getValue($name, $optionParams);
			}

			if($save) {
				$value = xmlTranslator::executeMacroses($value);
			}

			if($type == 'date') {
				if(!$value) $value = time();
				$date = new umiDate();
				$date->setDateByString($value);
				$value = $date->getFormattedDate('Y-m-d H:i');
			}

			if($type == 'tags' && is_array($value)) {
				$value = implode(', ', $value);
			}

			if($type == 'optioned' && !is_null($optionParams)) {
				$value = isset($value[0]) ? $value[0] : '';
				$type = getArrayKey($optionParams, 'field-type');
			}

			$result = array(
				'attribute:name'		=> $name,
				'attribute:type'		=> $type
			);

			if($type == 'relation') {
				$items_arr = array();
				if($value) {
					if(!is_array($value)) $value = array($value);

					$objects = umiObjectsCollection::getInstance();
					foreach($value as $objectId) {
						$object = $objects->getObject($objectId);
						$items_arr[] = $object;
					}
				}

				$result['attribute:guide-id'] = $field->getGuideId();
				if($field->getFieldType()->getIsMultiple()) {
					$result['attribute:multiple'] = 'multiple';
				}

				$type = selector::get('object-type')->id($field->getGuideId());
				if($type && $type->getIsPublic()) {
					$result['attribute:public'] = 'public';
				}
				$result['nodes:item'] = $items_arr;
			} else if($type == 'symlink') {
				$result['nodes:page'] = is_array($value) ? $value : array();
			} else {
				$result['node:value'] = $value;
			}

			if($oldLink != $newLink) {
				$result['attribute:old-link'] = $oldLink;
				$result['attribute:new-link'] = $newLink;
			}

			return $result;
		}


		protected static function loadEiPTypes() {
			static $types;
			if(is_array($types)) return $types;

			$config = mainConfiguration::getInstance();
			$types = array();
			$rules = $config->get('edit-in-place', 'allowed-types');
			foreach($rules as $rule) {
				list($type, $parents) = preg_split("/ ?<\- ?/", $rule);
				list($module, $method) = explode("::", $type);
				$types[$module][$method] = $parents;
			}
			return $types;
		}

		protected static function prepareTypesList($targetModule, $parent = null) {
			$types = self::loadEiPTypes();
			$hierarchyTypes = umiHierarchyTypesCollection::getInstance();
			$cmsController = cmsController::getInstance();
			$modulesList = $cmsController->getModulesList();

			if($parent instanceof iUmiHierarchyElement) {
				$targetModule = $parent->getModule();
			}

			$matched = array();
			foreach($types as $module => $stypes) {
				if($parent && ($module != $targetModule && $targetModule != 'content')) continue;

				asort($stypes, true);

				foreach($stypes as $method => $rule) {
					if($rule != '*' && $rule != '@') {
						if(!$parent) continue;

						$arr = explode('::', $rule);
						if(sizeof($arr) != 2) continue;
						list($seekModule, $seekMethod) = $arr;
						if($parent->getModule() != $seekModule || $parent->getMethod() != $seekMethod) {
							continue;
						}
					}

					if($rule == '@' && $parent) continue;

					$hierarchyType = $hierarchyTypes->getTypeByName($module, $method);

					if($hierarchyType instanceof iUmiHierarchyType) {
						//Compare with installed modules list
						if(!in_array($module, $modulesList)) {
							continue;
						}
						$matched[] = $hierarchyType;
					}
				}
			}

			$event = new umiEventPoint("eipPrepareTypesList");
			$event->setParam("targetModule", $targetModule);
			$event->setParam("parent", $parent);
			$event->addRef("types", $matched);
			$event->setMode("after");
			$event->call();

			return $matched;
		}

		public function eip_quick_add() {
			$this->setDataType("form");
			$this->setActionType("create");

			$parentElementId = (int) getRequest('param0');
			$objectTypeId = (int) getRequest('type-id');
			$forceHierarchy = (int) getRequest('force-hierarchy');

			$objectType = selector::get('object-type')->id($objectTypeId);
			if(!$forceHierarchy && $objectType instanceof iUmiObjectType) {
				$objects = umiObjectsCollection::getInstance();
				$objectId = $objects->addObject(NULL, $objectTypeId);

				$data = array(
				    'attribute:object-id' => $objectId,
				    'status' => 'ok'
				);
			} else {

				$permissions = permissionsCollection::getInstance();
				if($parentElementId) {
					$userId = $permissions->getUserId();
					$allow = $permissions->isAllowedObject($userId, $parentElementId);
					if (!$allow[2]) {
						throw new publicAdminException(getLabel("error-require-add-permissions"));
					}
				}

				$hierarchy = umiHierarchy::getInstance();
				$objectTypes = umiObjectTypesCollection::getInstance();

				if(!$objectTypeId) $objectTypeId = $hierarchy->getDominantTypeId($parentElementId);

				if(!$objectTypeId) {
					throw new publicAdminException("No dominant object type found");
				}

				$objectType = $objectTypes->getType($objectTypeId);
				$hierarchyTypeId = $objectType->getHierarchyTypeId();

				$elementId = $hierarchy->addElement($parentElementId, $hierarchyTypeId, '', '', $objectTypeId);
				$permissions->setDefaultPermissions($elementId);
				//$permissions->setInheritedPermissions($elementId);
				$element = $hierarchy->getElement($elementId);
				$element->isActive = true;
				$element->isVisible = true;
				$element->show_submenu = true;
				$element->commit();

				$event = new umiEventPoint('eipQuickAdd');
				$event->setParam('objectTypeId', $objectTypeId);
				$event->setParam('elementId', $elementId);
				$event->setMode('after');
				$event->call();

				$data = array(
					'attribute:element-id' => $elementId,
					'status' => 'ok'
				);
			}

			cacheFrontend::getInstance()->flush();
			$this->setData($data);
			return $this->doData();
		}

		public function eip_add_page() {
			$mode = (string) getRequest('param0');
			$parent = $this->expectElement("param1");
			$module = (string) getRequest('param2');
			$method = (string) getRequest('param3');

			$hierarchy = umiHierarchy::getInstance();
			$hierarchyTypes = umiHierarchyTypesCollection::getInstance();
			$permissions = permissionsCollection::getInstance();
			$objectTypes = umiObjectTypesCollection::getInstance();

			if($mode == 'choose') {
				$types = self::prepareTypesList($module, $parent);
				if(sizeof($types) == 1) { //Redirect to next step
					list($hierarchyType) = $types;
					$module = $hierarchyType->getModule();
					$method = $hierarchyType->getMethod();
					if($module == 'content' && !$method) $method = 'page';
					$parentId = $parent ? $parent->id : '0';
					$url = $this->pre_lang . "/admin/content/eip_add_page/form/{$parentId}/{$module}/{$method}/";
					if($hierarchyTypeId = getRequest('hierarchy-type-id')) {
						$url .= '?hierarchy-type-id=' . $hierarchyTypeId;
					}

					$this->chooseRedirect($url);
				}

				if(sizeof($types) > 1) { //Show type choose list
					if($hierarchyTypeId = getRequest('hierarchy-type-id')) {

						$hierarchyType = $hierarchyTypes->getType($hierarchyTypeId);
						if($hierarchyType instanceof iUmiHierarchyType) {
							$module = $hierarchyType->getModule();
							$method = $hierarchyType->getMethod();

							if($module == 'content' && !$method) $method = 'page';
							$parentId = $parent ? $parent->id : '0';

							$url = $this->pre_lang . "/admin/content/eip_add_page/form/{$parentId}/{$module}/{$method}/?0";
							if(isset($_REQUEST['object-type'][$hierarchyTypeId])) {
								$url .= '&type-id=' . $_REQUEST['object-type'][$hierarchyTypeId];
							}

							if($hierarchyTypeId = getRequest('hierarchy-type-id')) {
								$url .= '&hierarchy-type-id=' . $hierarchyTypeId;
							}
							$this->chooseRedirect($url);
						}
					}

					$this->setDataType("list");
					$this->setActionType("view");

					$data = array(
						'nodes:hierarchy-type' => $types
					);
					$this->setData($data, sizeof($types));
					return $this->doData();

				}

				if(sizeof($types) == 0) { //Display and error
					$buffer = outputBuffer::current();
					$buffer->contentType('text/html');
					$buffer->clear();
					$buffer->push("An error (temp message)");
					$buffer->end();
				}
			}

			$inputData = array(
				'type'		=> $method,
				'parent'	=> $parent,
				'module'	=> $module
			);

			if($objectTypeId = getRequest('type-id')) {
				$inputData['type-id'] = $objectTypeId;
			} else if ($hierarchyTypeId = getRequest('hierarchy-type-id')) {
				$inputData['type-id'] = $objectTypes->getTypeByHierarchyTypeId($hierarchyTypeId);
			}


			if(getRequest('param4') == "do") {
				$elementId = $this->saveAddedElementData($inputData);
				$element = $hierarchy->getElement($elementId, true);
				if($element instanceof iUmiHierarchyElement) {
					$element->setIsActive();
					$element->commit();
				} else {
					throw new publicException("Can't get create umiHierarchyElement");
				}

				$permissions->setInheritedPermissions($elementId);
				cacheFrontend::getInstance()->flush();

				$buffer = outputBuffer::current();
				$buffer->contentType('text/html');
				$buffer->clear();
				$buffer->push("<script>window.parent.location.reload();</script>");
				$buffer->end();
			}

			$this->setDataType("form");
			$this->setActionType("create");

			$data = $this->prepareData($inputData, "page");

			$this->setData($data);
			return $this->doData();
		}

		public function eip_del_page() {
			$this->flushAsXml('eip_del_page');

			$config = mainConfiguration::getInstance();
			$permissions = permissionsCollection::getInstance();
			$hierarchy = umiHierarchy::getInstance();
			$objects = umiObjectsCollection::getInstance();

			$userId = $permissions->getUserId();
			$elementId = (int) getRequest('element-id');
			$objectId = (int) getRequest('object-id');

			$fakeDelete = $config->get('system', 'eip.fake-delete');

			if($objectId) {
				if($permissions->isSv() || $permissions->isAdmin() || $permissions->isOwnerOfObject($objectId, $permissions->getUserId())) {
					$objects->delObject($objectId);
					cacheFrontend::getInstance()->flush();
					return array(
						'status'	=> 'ok'
					);
				} else {
					return array(
						'error' => getLabel('error-require-delete-permissions')
					);
				}
			} else {
				$allow = $permissions->isAllowedObject($userId, $elementId);

				if($allow[3]) {
					$element = $hierarchy->getElement($elementId);
					if($element instanceof iUmiHierarchyElement) {
						if(!$element->name && !trim($element->altName, '_0123456789') || !$fakeDelete) {

                                   $oEventPoint = new umiEventPoint("systemDeleteElement");
                                   $oEventPoint->setMode("before");
                                   $oEventPoint->addRef("element", $element);
                                   $this->setEventPoint($oEventPoint);


							$hierarchy->delElement($elementId);

                                   // after del event
                                   $oEventPoint2 = new umiEventPoint("systemDeleteElement");
                                   $oEventPoint2->setMode("after");
                                   $oEventPoint2->addRef("element", $element);
                                   $this->setEventPoint($oEventPoint2);

						} else {
                                   // fake delete
                                   $oEventPoint = new umiEventPoint("systemSwitchElementActivity");
                                   $oEventPoint->setMode("before");
                                   $oEventPoint->addRef("element", $element);
                                   $this->setEventPoint($oEventPoint);

							$element->setIsActive(false);
							$element->commit();

                                   $oEventPoint2 = new umiEventPoint("systemSwitchElementActivity");
                                   $oEventPoint2->setMode("after");
                                   $oEventPoint2->addRef("element", $element);
                                   $this->setEventPoint($oEventPoint2);
						}
						cacheFrontend::getInstance()->flush();
					}

					return array(
						'status'	=> 'ok'
					);
				} else {
					return array(
						'error' => getLabel('error-require-delete-permissions')
					);
				}
			}
		}


		public function eip_move_page() {
			$this->flushAsXml('eip_move_page');

			$permissions = permissionsCollection::getInstance();
			$hierarchy = umiHierarchy::getInstance();

			$userId = $permissions->getUserId();
			$elementId = (int) getRequest('param0');
			$nextElementId = (int) getRequest('param1');

			$parentElementId = getRequest('parent-id');
			if(is_null($parentElementId)) {
				if($nextElementId) {
					$parentElementId = $hierarchy->getParent($nextElementId);
				} else {
					$parentElementId = $hierarchy->getParent($elementId);
				}
			}

			$parents = $hierarchy->getAllParents($parentElementId);
			if(in_array($elementId, $parents)) {
				throw new publicAdminException(getLabel('error-illegal-moving'));
			}

			$allow = $permissions->isAllowedObject($userId, $elementId);
			if($allow[4]) {
				$event = new umiEventPoint('systemMoveElement');
				$event->setParam('parentElementId', $parentElementId);
				$event->setParam('elementId', $elementId);
				$event->setParam('beforeElementId', $nextElementId);
				$event->setMode('before');
				$event->call();

				$hierarchy->moveBefore($elementId, $parentElementId, $nextElementId ? $nextElementId : false);
				cacheFrontend::getInstance()->flush();

				$event2 = new umiEventPoint('systemMoveElement');
				$event2->setParam('parentElementId', $parentElementId);
				$event2->setParam('elementId', $elementId);
				$event2->setParam('beforeElementId', $nextElementId);
				$event2->setMode('after');
				$event2->call();

				return array(
					'status'	=> 'ok'
				);
			} else {
				return array(
					'error' => getLabel('error-require-move-permissions')
				);
			}
		}


		public function frontendPanel() {
			$this->flushAsXml('frontendPanel');
			$permissions = permissionsCollection::getInstance();
			$cmsController = cmsController::getInstance();
			$regedit = regedit::getInstance();
			$maxRecentPages = 5;
			$sysModules = array( 'config', 'trash' ,'search', 'autoupdate');
			$utilModules = array('data','backup', 'webo', 'filemanager');

			$modules     = array();
			$modulesList = array();
			foreach($regedit->getList('//modules') as $module) $modulesList[] = $module[0];
			$modulesList[] = 'trash';

			foreach($modulesList as $module) {
				if($permissions->isAllowedModule(false, $module) == false) continue;

				if(in_array($module, $sysModules))
					$type = 'system';
				else if(in_array($module, $utilModules))
					$type = 'util';
				else $type = null;

				$modules[] = array(
					'attribute:label'	=> getLabel('module-' . $module),
					'attribute:type'	=> $type,
					'node:name'			=> $module
				);
			}

			$hierarchy = umiHierarchy::getInstance();
			$key = md5(getServer('HTTP_REFERER'));
			$currentIds = is_array(getSession($key)) ? getSession($key) : array();
			foreach($currentIds as $i => $id) $currentIds[$i] = $id[2];
			$currentIds = array_unique($currentIds);
			$current = array();
			foreach($currentIds as $id) {
				$current[] = $hierarchy->getElement($id);
			}


			$recent = new selector('pages');
			$recent->where('is_deleted')->equals(0);
			$recent->where('is_active')->equals(1);
			$recent->where('lang')->equals(langsCollection::getInstance()->getList());
			$recent->order('updatetime')->desc();
			$recent->limit(0, $maxRecentPages);

			if(sizeof($currentIds) && $permissions->isAllowedModule($permissions->getUserId(), 'backup')) {
				$backup = $cmsController->getModule('backup');
				$changelog = $backup->backup_panel($currentIds[0]);
			} else {
				$changelog = null;
			}

			$user = selector::get('object')->id($permissions->getUserId());
			$tickets = array();
			$referer = getRequest('referer') ? getRequest('referer') : getServer('HTTP_REFERER');

			$tickets = new selector('objects');
			$tickets->types('object-type')->name('content', 'ticket');
			$tickets->where('url')->equals($referer);
			$tickets->limit(0, 100);

			$ticketsResult = array();
			foreach($tickets as $ticket) {
				$user = selector::get('object')->id($ticket->user_id);
				if(!$user) continue;

				$ticketsResult[] = array(
					'attribute:id' => $ticket->id,
					'author' => array(
						'attribute:fname' => $user->fname,
						'attribute:lname' => $user->lname,
						'attribute:login' => $user->login
					),
					'position' => array(
						'attribute:x' => $ticket->x,
						'attribute:y' => $ticket->y,
						'attribute:width' => $ticket->width,
						'attribute:height' => $ticket->height
					),
					'message' => $ticket->message
				);
			}

			$result = array(
				'user'		=> array(
					'attribute:id' => $user->id,
					'attribute:fname' => $user->fname,
					'attribute:lname' => $user->lname,
					'attribute:login' => $user->login
				),
				'tickets' => array(
					'nodes:ticket' => $ticketsResult
				),
				'modules'	=> array('nodes:module' => $modules),
				'documents'		=> array(
					'editable'		=> array('nodes:page' => $current),
					'recent'		=> array('nodes:page' => $recent->result())
				)
			);

			if(!$permissions->isAllowedMethod($permissions->getUserId(), 'content', 'tickets')) {
				unset($result['tickets']);
			}

			if($changelog && sizeof($changelog['nodes:revision'])) {
				$result['changelog'] = $changelog;
			}

			$event = new umiEventPoint('eipFrontendPanelGet');
			$event->setParam("id", getArrayKey($currentIds, 0));
			$event->addRef("result", $result);
			$event->setMode('after');
			$event->call();

			return $result;
		}

		static function filterStringValue($value) {
			$trims = array('&nbsp;', ' ', '\n');
			foreach($trims as $trim) {
				if(substr($value, 0, strlen($trim)) == $trim) {
					$value = substr($value, strlen($trim));
				}

				if(substr($value, strlen($value) - strlen($trim)) == $trim) {
					$value = substr($value, 0, strlen($value) - strlen($trim));
				}
			}
			return $value;
		}
	};
?>