<?php
	class baseModuleAdmin {
		protected	$dataTypes = array('list', 'message', 'form'),
				$actionTypes = array('modify', 'create', 'view');


		public function setDataRange($limit, $offset = 0) {
			$this->limit = (int) $limit;
			$this->offset = (int) $offset;
		}

		public function setDataRangeByPerPage($per_page, $curr_page = 0) {
			$this->setDataRange($per_page, $curr_page * $per_page);
		}


		public function setDataType($dataType) {
			$this->limit = false;
			$this->offset = false;

			$this->dataType = $dataType;
		}


		public function setActionType($actionType) {
			$this->actionType = $actionType;
		}


		public function setData($data, $total = false) {
			$this->total = $total;
			$this->data = $data;
		}


		public function doData() {
			$dataSet = array();
			$dataSet['attribute:type'] = $this->dataType;
			$dataSet['attribute:action'] = $this->actionType;

			if($this->total) {
				$dataSet['attribute:total'] = $this->total;

				if(!is_null($this->offset)) {
					$dataSet['attribute:offset'] = $this->offset;
				}

				if(!is_null($this->limit)) {

					$dataSet['attribute:limit'] = $this->limit;
				}
			}

			$dataSet = array_merge($dataSet, $this->data);

			cmsController::getInstance()->getCurrentTemplater()->setDataSet($dataSet);
		}


		public function prepareData($inputData, $type) {
			$data = array();

			$this->requireSlashEnding();

			switch($type) {
				case "page": {
					$data = $this->prepareDataPage($inputData);
					break;
				}


				case "pages": {
					$data = $this->prepareDataPages($inputData);
					break;
				}


				case "object": {
					$data = $this->prepareDataObject($inputData);
					break;
				}


				case "objects": {
					$data = $this->prepareDataObjects($inputData);
					break;
				}

				case "type": {
					$data = $this->prepareDataType($inputData);
					break;
				}

				case "field": {
					$data = $this->prepareDataField($inputData);
					break;
				}

				case "group": {
					$data = $this->prepareDataGroup($inputData);
					break;
				}

				case "types": {
					$data = $this->prepareDataTypes($inputData);
					break;
				}

				case "hierarchy_types": {
					$data = $this->prepareDataHierarchyTypes($inputData);
					break;
				}


				case "domains": {
					$data = $this->prepareDataDomains($inputData);
					break;
				}

				case "domain_mirrows": {
					$data = $this->prepareDataDomainMirrows($inputData);
					break;
				}


				case "templates": {
					$data = $this->prepareDataTemplates($inputData);
					break;
				}

				case "template": {
					$data = $this->prepareDataTemplate($inputData);
					break;
				}


				case "settings": {
					$data = $this->prepareDataSettings($inputData);
					break;
				}

				case "modules": {
					$data = $this->prepareDataModules($inputData);
					break;
				}


				case "langs": {
					$data = $this->prepareDataLangs($inputData);
					break;
				}



				default: {
					throw new coreException("Data type \"{$type}\" is unknown.");
				}
			}

			return $data;
		}


		public function prepareDataPage($inputData) {
			$element = getArrayKey($inputData, "element");
			$oUsersMdl = cmsController::getInstance()->getModule("users");
			if ($this->systemIsLocked($element, $oUsersMdl->user_id)){
				throw new wrongElementTypeAdminException(getLabel("error-element-locked"));
			}
			$oEventPoint = new umiEventPoint("sysytemBeginPageEdit");



			$oEventPoint->setMode("before");
			$oEventPoint->setParam("user_id", $oUsersMdl->user_id);
			$oEventPoint->setParam("lock_time", time());

			$oEventPoint->addRef("element", $element);

			$oEventPoint->call();
			$data = array();

			$dataModule = cmsController::getInstance()->getModule("data");
			$cmsController = cmsController::getInstance();

			$page = array();
			if($this->actionType == "create") {
				$module = get_class($this);
				if(getArrayKey($inputData, 'module')) $module = getArrayKey($inputData, 'module');

				if($this->checkAllowedElementType($inputData) == false) {
						throw new wrongElementTypeAdminException(getLabel("error-unexpected-element-type"));
				}

				$method = $inputData['type'];
				if($method == "page" && $module == "content") {
					$method = "";
				}

				if(is_numeric($method)) {
					$base_type_id = $type_id = $method;
				} else {
					$base_type_id = $type_id = umiObjectTypesCollection::getInstance()->getBaseType($module, $method);
				}

				$parent = $inputData['parent'];
				$methodTemplateId = templatesCollection::getInstance()->getHierarchyTypeTemplate($module, $method);
				if($parent instanceof iUmiHierarchyElement) {
					$parent_id = $parent->getId();

					$this->checkDomainPermissions($parent->getDomainId());
					$this->checkElementPermissions($parent_id);

					$cmsController->getCurrentTemplater()->currentEditElementId = $parent_id;

					$dominant_type_id = umiHierarchy::getInstance()->getDominantTypeId($parent_id);
					if($dominant_type_id) {
						$type_id = $dominant_type_id;
					}


					if($methodTemplateId !== false) {
						$tpl_id = $methodTemplateId;
					} else {
						$dominant_tpl_id = umiHierarchy::getInstance()->getDominantTplId($parent_id);
						if($dominant_tpl_id) {
							$tpl_id = $dominant_tpl_id;
						}
					}
				} else {
					$parent_id = 0;

					$this->checkDomainPermissions();

					$dominant_type_id = umiHierarchy::getInstance()->getDominantTypeId(0);
					if($dominant_type_id) {
						$type_id = $dominant_type_id;
					}

					$lang_id = $cmsController->getCurrentLang()->getId();
					$domain_id = $cmsController->getCurrentDomain()->getId();

					if($floated_domain_id = $this->getFloatedDomain()) {
						$domain_id = $floated_domain_id;
					}

					if($methodTemplateId !== false) {
						$tpl_id = $methodTemplateId;
					} else {
						$default_template = templatesCollection::getInstance()->getDefaultTemplate($domain_id, $lang_id);
						if($default_template instanceof iTemplate) {
							$tpl_id = $default_template->getId();
						} else {
							throw new publicAdminException(getLabel('error-require-default-template'));
						}
					}
				}

				if($this->compareObjectTypeByHierarchy($module, $method, $type_id) == false) {
					$type_id = $base_type_id;
				}

				if(isset($inputData['type_id'])) {
					$type_id = $inputData['type_id'];
				} elseif(isset($inputData['type-id'])) {
					$type_id = $inputData['type-id'];
				}

				if($type_id > 0) {
					$page['attribute:name'] = "";
					$page['attribute:parentId'] = $parent_id;
					$page['attribute:type-id'] = $type_id;
					$page['attribute:tpl-id'] = $tpl_id;
					$page['attribute:active'] = "active";

					$page['basetype'] = umiHierarchyTypesCollection::getInstance()->getTypeByName($module, $method);
					$page['properties'] = $dataModule->getCreateForm($type_id, false, false, true);
				} else {
					throw new coreException("Give me a normal type to create ;)");
				}

				if($module == 'content' && $method == '') {
					$page['attribute:visible'] = 'visible';
				}
			} else if ($this->actionType == "modify") {
				if($inputData instanceof umiHierarchyElement) {
					$element = $inputData;
				} else if (is_array($inputData)){
					$element = $inputData['element'];
				} else {
					throw new coreException("Unknown type of input data");
				}

				if($this->checkAllowedElementType($inputData) == false) {
					throw new wrongElementTypeAdminException(getLabel("error-unexpected-element-type"));
				}

				$this->checkDomainPermissions($element->getDomainId());
				$this->checkElementPermissions($element->getId());

				$cmsController->getCurrentTemplater()->currentEditElementId = $element->getId();

				$object_id = $element->getObject()->getId();

				$page['attribute:id'] = $element->getId();
				$page['attribute:parentId'] = $element->getParentId();
				$page['attribute:object-id'] = $object_id;
				$page['attribute:guid'] = $element->getObject()->getGUID();
				$page['attribute:type-id'] = $element->getObject()->getTypeId();
				$page['attribute:type-guid'] = $element->getObject()->getTypeGUID();
				$page['attribute:alt-name'] = $element->getAltName();
				$page['attribute:tpl-id'] = $element->getTplId();


				if($element->getIsActive()) {
					$page['attribute:active'] = "active";
				}

				if($element->getIsVisible()) {
					$page['attribute:visible'] = "visible";
				}

				if($element->getIsDefault()) {
					$page['attribute:default'] = "default";
				}

				$page['basetype'] = $element->getHierarchyType();

				$page['name'] = $element->getName();

				$page['properties'] = $dataModule->getEditForm($object_id, false, false, true, true);
			}
			$data['page'] = $page;
			return $data;
		}


		public function prepareDataPages($inputData) {
			$data = array();
			$hierarchy = umiHierarchy::getInstance();
			$pages = array();
			$sz = sizeof($inputData);
			for($i = 0; $i < $sz; $i++) {
				$element = $inputData[$i];
				if(is_numeric($element)) {
					$element = $hierarchy->getElement($element, false, true);
				}

				if($element instanceof umiHierarchyElement) {
					if(getRequest('viewMode') == 'full') {
						$pages[] = array('full:' => $element);
					} else {
						$pages[] = $element;
						$hierarchy->unloadElement($element->getId());
					}
				}
			}

			$data['nodes:page'] = $pages;

			return $data;
		}


		public function prepareDataObjects($inputData) {
			$data = array();

			$objectsCollection = umiObjectsCollection::getInstance();
			$objects = array();
			$sz = sizeof($inputData);
			for($i = 0; $i < $sz; $i++) {
				$object = $inputData[$i];
				if(is_numeric($object)) {
					$object = $objectsCollection->getObject($object);
				}

				if($object instanceof umiObject) {
					if(getRequest('viewMode') == 'full') {
						$objects[] = array('full:' => $object);
					} else {
						$objects[] = $object;
						$objectsCollection->unloadObject($object->getId());
					}
				}
			}
			$data['nodes:object'] = $objects;

			return $data;
		}


		public function prepareDataObject($inputData) {
			$data = array();

			$dataModule = cmsController::getInstance()->getModule("data");

			if($this->checkAllowedElementType($inputData, true) == false) {
					throw new wrongElementTypeAdminException(getLabel("error-unexpected-element-type"));
			}


			$object = array();
			if($this->actionType == "create") {
				$typeId = false;
				$module = get_class($this);
				$method = getArrayKey($inputData, 'type');

				if($module && $method) {
					$typeId = umiObjectTypesCollection::getInstance()->getBaseType($module, $method);
				}

				if(isset($inputData['type-id'])) {
					$typeId = $inputData['type-id'];
				}

				if($typeId == false) {
					throw new publicAdminException("Object type id is required to create new object");
				}

				$object['attribute:type-id'] = $typeId;
				$object['properties'] = $dataModule->getCreateForm($typeId, false, false, true);
			} else {
				if($inputData instanceof umiObject == false) {
					if(is_object($inputData = getArrayKey($inputData, 'object')) === false) {
						throw new publicAdminException(getLabel("error-expect-object"));
					}
				}

				$object['attribute:id'] = $inputData->getId();
				$object['attribute:name'] = $inputData->getName();
				$object['attribute:guid'] = $inputData->getGUID();
				$object['attribute:type-id'] = $inputData->getTypeId();
				$object['attribute:type-guid'] = $inputData->getTypeGUID();
				$object['attribute:owner-id'] = $inputData->getOwnerId();
				$object['properties'] = $dataModule->getEditForm($inputData->getId(), false, false, true, true);
			}



			$data['object'] = $object;

			return $data;
		}


		public function prepareDataType($inputData) {
			$data = array();
			$data['full:type'] = $inputData;
			xmlTranslator::$showHiddenFieldGroups = true;
			return $data;
		}


		public function prepareDataField($inputData) {
			$data = array();

			if($this->actionType == "create") {
				$field = array();
				$field['attribute:visible'] = 'visible';
				$data['field'] = $field;
			} else {
				$data['full:field'] = $inputData;
			}
			return $data;
		}


		public function prepareDataGroup($inputData) {
			$data = array();

			if($this->actionType == "create") {
				$group_arr = array();
				$group_arr['attribute:visible'] = true;
				$data['group'] = $group_arr;
			} else {
				if($inputData instanceof umiFieldsGroup) {
					$data['group'] = $inputData;
				} else {
					throw new coreException("Expected instance of umiFieldsGroup");
				}
			}
			return $data;
		}


		public function prepareDataTypes($inputData) {
			$data = array();

			$typesCollection = umiObjectTypesCollection::getInstance();
			$types = array();
			$sz = sizeof($inputData);
			for($i = 0; $i < $sz; $i++) {
				$type_id = $inputData[$i];
				$type = $typesCollection->getType($type_id);
				if($type instanceof umiObjectType) {
					$types[] = $type;
				}
			}
			$data['nodes:type'] = $types;
			return $data;
		}


		public function prepareDataHierarchyTypes($inputData) {
			$data = array();

			$typesCollection = umiHierarchyTypesCollection::getInstance();
			$types = array();

			foreach($inputData as $item) {
				if($item instanceof iUmiHierarchyType) {
					$types[] = $item;
				} else {
					$type_id = $item;
					$type = $typesCollection->getType($type_id);

					if($type instanceof iUmiHierarchyType) {
						$types[] = $type;
					}
				}
			}
			$data['nodes:basetype'] = $types;
			return $data;
		}


		public function prepareDataDomains($inputData) {
			$data = array();

			$domains = array();
			foreach($inputData as $item) {
				$domains[] = $item;
			}
			$data['nodes:domain'] = $domains;
			return $data;
		}


		public function prepareDataDomainMirrows($inputData) {
			$data = array();

			$domains = array();
			foreach($inputData as $item) {
				$domains[] = $item;
			}
			$data['nodes:domainMirrow'] = $domains;
			return $data;
		}


		public function prepareDataTemplates($inputData) {
			$data = array();
			$domainsCollection = domainsCollection::getInstance();

			$domains = array();
			foreach($inputData as $host => $templates) {
				$domain = array();
				$domain['attribute:id'] = $domainsCollection->getDomainId($host);
				$domain['attribute:host'] = $host;
				$domain['nodes:template'] = $templates;
				$domains[] = $domain;
			}
			$data['nodes:domain'] = $domains;
			return $data;
		}


		public function prepareDataTemplate(iTemplate $template) {
			$hierarchy = umiHierarchy::getInstance();

			$data = array();
			$info = array();
			$info['attribute:id'] = $template->getId();
			$info['attribute:name'] = $template->getName();
			$info['attribute:title'] = $template->getTitle();
			$info['attribute:filename'] = $template->getFileName();
			$info['attribute:type'] = $template->getType();
			$info['attribute:lang-id'] = $template->getLangId();
			$info['attribute:domain-id'] = $template->getDomainId();

			$used_pages = $template->getUsedPages();

			$pages = array();
			foreach($used_pages as $element_info) {
				$element = $hierarchy->getElement($element_info[0]);
				if($element instanceof umiHierarchyElement) {
					$element_id = $element->getId();
					$page_arr['attribute:id'] = $element_id;
					$page_arr['xlink:href'] = "upage://" . $element_id;
					$page_arr['basetype'] = selector::get('hierarchy-type')->id($element->getTypeId());
					$page_arr['name'] = str_replace("\"", "\\\"", $element->getName());
					$pages[] = $page_arr;
				}
			}
			$info['used-pages']['nodes:page'] = $pages;
			$data['template'] = $info;
			return $data;
		}


		public function prepareDataSettings($inputData) {
			$data = array();
			$data['nodes:group'] = array();

			foreach($inputData as $group_name => $params) {
				if(!is_array($params)) {
					continue;
				}

				$group = array();
				$group['attribute:name'] = $group_name;
				$group['attribute:label'] = getLabel("group-" . $group_name);

				$options = array();
				foreach($params as $param_key => $param_value) {
					$param_name = def_module::getRealKey($param_key);
					$param_type = def_module::getRealKey($param_key, true);

					$option = array();
					$option['attribute:name'] = $param_name;
					$option['attribute:type'] = $param_type;
					$option['attribute:label'] = getLabel("option-" . $param_name);

					switch($param_type) {
						case "select": {
							$items = array();
							$value = isset($param_value['value']) ? $param_value['value'] : false;
							foreach($param_value as $item_id => $item_name) {
								if($item_id === "value") continue;

								$item_arr = array();
								$item_arr['attribute:id'] = $item_id;
								$item_arr['node:name'] = $item_name;
								$items[] = $item_arr;
							}
							$option['value'] = array("nodes:item" => $items);

							if($value !== false) {
								$option['value']['attribute:id'] = $value;
							}
							break;
						}

						case "password": {
							if($param_value) {
								$param_value = "********";
							} else {
								$param_value = "";
							}

							break;
						}

						case "symlink": {
							$hierarchy = umiHierarchy::getInstance();

							$param_value = @unserialize($param_value);
							if(!is_array($param_value)) {
								$param_value = array();
							}
							$items = array();
							foreach($param_value as $item_id) {
								$item = $hierarchy->getElement($item_id);
								if($item instanceof umiHierarchyElement == false) {
									continue;
								}

								$item_arr = array();
								$item_arr['attribute:id'] = $item_id;
								$item_arr['node:name'] = $item->getName();
								$items[] = $item_arr;
							}
							$option['value'] = array('nodes:item' => $items);
							break;
						}

						default: {
							$option['value'] = $param_value;
							break;
						}
					}

					$options[] = $option;
				}

				$group['nodes:option'] = $options;
				$data['nodes:group'][] = $group;
			}
			return $data;
		}


		public function prepareDataModules($inputData) {
			$data = array();
			$modules = array_values($inputData);

			$items = array();
			foreach($modules as $module_name) {
				$item_arr = array();
				$item_arr['attribute:label'] = getLabel('module-' . $module_name);
				$item_arr['node:module'] = $module_name;
				$items[] = $item_arr;
			}

			$data['nodes:module'] = $items;
			return $data;
		}


		public function prepareDataLangs($inputData) {
			$data = array();

			$langs = array();

			foreach($inputData as $lang) {
				$lang_arr = array();
				$lang_arr['attribute:id'] = $lang->getId();
				$lang_arr['attribute:title'] = $lang->getTitle();
				$lang_arr['attribute:prefix'] = $lang->getPrefix();
				$langs[] = $lang_arr;
			}

			$data['nodes:lang'] = $langs;
			return $data;
		}


		public function expectParams($params) {
			foreach($params as $group_key => $group) {
				foreach($group as $param_key => $param) {
					$param_name = def_module::getRealKey($param_key);
					$param_type = def_module::getRealKey($param_key, true);

					$params[$group_key][$param_key] = $this->getExpectedParam($param_name, $param_type, $param);
				}
			}
			return $params;
		}


		public function getExpectedParam($param_name, $param_type, $param = NULL) {
			global $_FILES;

			$value = getRequest($param_name);

			if($param_type == "status") {
				return NULL;
			}

			if(is_null($value) && !in_array($param_type, array('file', 'weak_guide'))) {
				throw new requireAdminParamException("I expect value in request for param \"" . $param_name . "\"");
			}

			switch($param_type) {
				case "float": {
					return (float) $value;
				}

				case "bool":
				case "boolean":
				case "templates":
				case "guide":
				case "weak_guide":
				case "int": {
					return (int) $value;
				}

				case "password": {
					$value = ($value == "********") ? NULL : (string) $value;
					if($value) {
						try {
							$oOpenSSL = new umiOpenSSL();
							$bFilesOk = $oOpenSSL->supplyDefaultKeyFiles();
							if ($bFilesOk) {
								$value = 'umipwd_b64::' . base64_encode($oOpenSSL->encrypt($value));
							} else {
								$value = NULL;
							}
						} catch(publicException $e) {
							$value = NULL;
						}
					}
					return $value;
				}

				case "email":
				case "status":
				case "string": {
					return (string) $value;
				}

				case "symlink": {
					return serialize($value);
				}

				case "file": {

					$destination_folder = $param['destination-folder'];
					$group = isset($param['group']) ? $param['group'] : "pics";

					if($value = umiFile::upload($group, $param_name, $destination_folder)) {
						return $value;
					} else {
						$path = $destination_folder . getRequest('select_' . $param_name);
						return new umiFile($path);
					}
					break;
				}

				case "select": {
					return $value;
					break;
				}

				default: {
					throw new wrongParamException("I don't expect param \"" . $param_type . "\"");
				}
			}
		}


		public function expectElement($var, $strict = false, $byValue = false, $ignoreDeleted = false) {
			$element_id = ($byValue) ? $var : (int) getRequest($var);
			$element = umiHierarchy::getInstance()->getElement((int) $element_id, false, $ignoreDeleted);

			if($element instanceof umiHierarchyElement) {
				return $element;
			} else {
				if($strict) {
					throw new expectElementException(getLabel("error-expect-element"));
				} else {
					return false;
				}
			}
		}


		public function expectObject($var, $strict = false, $byValue = false) {
			$object_id = ($byValue) ? $var : (int) getRequest($var);
			$object = umiObjectsCollection::getInstance()->getObject((int) $object_id);

			if($object instanceof umiObject) {
				return $object;
			} else {
				if($strict) {
					throw new expectObjectException(getLabel("error-expect-object"));
				} else {
					return false;
				}
			}
		}


		public function expectElementId($var, $strict = false) {
			$element_id = (int) getRequest($var);

			if($element_id === 0 || umiHierarchy::getInstance()->isExists($element_id)) {
				return $element_id;
			} else {
				if($strict) {
					throw new expectElementException(getLabel("error-expect-element"));
				} else {
					return false;
				}
			}
		}


		public function expectObjectId($var, $strict = false) {
			$object_id = (int) getRequest($var);

			if($object_id === 0 || umiObjectsCollection::getInstance()->isExists($object_id)) {
				return $object_id;
			} else {
				if($strict) {
					throw new expectObjectException(getLabel("error-expect-object"));
				} else {
					return false;
				}
			}
		}


		public function expectObjectType($var, $strict = false) {
			$object_type_id = (int) getRequest($var);

			if($object_type_id === 0 || umiObjectTypesCollection::getInstance()->isExists($object_type_id)) {
				return umiObjectTypesCollection::getInstance()->getType($object_type_id);
			} else {
				if($strict) {
					throw new expectObjectTypeException(getLabel("error-expect-object-type"));
				} else {
					return false;
				}
			}
		}


		public function expectObjectTypeId($var, $strict = false, $byValue = false) {
			$object_type_id = (int) getRequest($var);
			if($byValue) {
				$object_type_id = $var;
			}

			$objectTypes = umiObjectTypesCollection::getInstance();
			if($object_type_id === 0 || $objectTypes->getType($object_type_id)) {
				return $object_type_id;
			} else {
				if($strict) {
					throw new expectObjectTypeException(getLabel("error-expect-object-type"));
				} else {
					return false;
				}
			}
		}


		public function saveEditedElementData($inputData) {
			if($this->checkAllowedElementType($inputData) == false) {
					throw new wrongElementTypeAdminException(getLabel("error-unexpected-element-type"));
			}

			$element = getArrayKey($inputData, "element");
			$oUsersMdl = cmsController::getInstance()->getModule("users");
			$event = new umiEventPoint("systemModifyElement");
			$event->addRef("element", $element);
			$event->addRef("inputData", $inputData);
			$event->setParam("user_id", $oUsersMdl->user_id);
			$event->setMode("before");
			$event->call();

			if($element instanceof umiHierarchyElement === false) {
				throw new expectElementException(getLabel("error-expect-element"));
			}

			$this->checkDomainPermissions($element->getDomainId());
			$this->checkElementPermissions($element->getId());

			$module_name = $element->getModule();
			$method_name = $element->getMethod();

			if(!is_null(getRequest('alt-name'))) {
				$alt_name = strlen(getRequest('alt-name')) ? getRequest('alt-name') : getRequest('name');
				$element->setAltName($alt_name);
			}

			if(!is_null($is_active = getRequest('active'))) {
				$permissions = permissionsCollection::getInstance();
				$user_id = $permissions->getUserId();
				if($permissions->isAllowedMethod($user_id, $module_name, "publish") != false) {
					$element->setIsActive($is_active);
				}
			}

			if(!is_null($is_visible = getRequest('is-visible'))) {
				$element->setIsVisible($is_visible);
			}

			if(!is_null($is_default = getRequest('is-default'))) {
				$element->setIsDefault($is_default);
			}

			if(!is_null($tpl_id = getRequest('template-id'))) {
				$element->setTplId($tpl_id);
			}

			$users = cmsController::getInstance()->getModule('users');
			if($users instanceof users) {
				$users->setPerms($element->getId());
			}

			backupModel::getInstance()->save($element->getId());

			$object = $element->getObject();
			if($object instanceof umiObject) {
				$this->saveEditedObjectData($object);
			}

			$element->commit();

			$this->currentEditedElementId = $element->getId();

			$event->setMode("after");
			$event->call();

			return $element;
		}


		public function saveAddedElementData($inputData) {
			$cmsController = cmsController::getInstance();
			$hierarchyTypes = umiHierarchyTypesCollection::getInstance();
			$objectTypes = umiObjectTypesCollection::getInstance();
			$hierarchy = umiHierarchy::getInstance();
			$templates = templatesCollection::getInstance();

			$module = get_class($this);
			if(isset($inputData['module'])) $module = $inputData['module'];
			$method = $inputData['type'];
			$parent = $inputData['parent'];

			if($this->checkAllowedElementType($inputData) == false) {
					throw new wrongElementTypeAdminException(getLabel("error-unexpected-element-type"));
			}

			if($module == "content" && $method == "page") {
				$method = "";
			}

			if($parent) {
				$this->checkElementPermissions($parent->getId(), permissionsCollection::E_CREATE_ALLOWED);
			}


			$event = new umiEventPoint("systemCreateElement");
			$event->addRef("inputData", $inputData);
			$event->setMode("before");
			$event->call();

			$methodTemplateId = $templates->getHierarchyTypeTemplate($module, $method);
			if($parent instanceof iUmiHierarchyElement) {
				$parent_id = $parent->getId();
				$lang_id = $parent->getLangId();
				$domain_id = $parent->getDomainId();

				if($methodTemplateId !== false) {
					$tpl_id = $methodTemplateId;
				} else {
					$dominant_tpl_id = umiHierarchy::getInstance()->getDominantTplId($parent_id);
					if($dominant_tpl_id) {
						$tpl_id = $dominant_tpl_id;
					} else {
						throw new coreException(getLabel('error-dominant-template-not-found'));
					}
				}
			} else {
				$parent_id = 0;
				$lang_id = $cmsController->getCurrentLang()->getId();
				$domain_id = $cmsController->getCurrentDomain()->getId();

				if($floated_domain_id = $this->getFloatedDomain()) {
					$domain_id = $floated_domain_id;
				}

				if($methodTemplateId !== false) {
					$tpl_id = $methodTemplateId;
				} else {
					$tpl_id = $templates->getDefaultTemplate()->getId();
				}
			}


			$this->checkDomainPermissions($domain_id);

			if(getRequest('template-id')) {
				$tpl_id = getRequest('template-id');
			}

			$hierarchy_type = $hierarchyTypes->getTypeByName($module, $method);
			if($hierarchy_type instanceof iUmiHierarchyType) {
				$hierarchy_type_id = $hierarchy_type->getId();
			} else {
				throw new coreException(getLabel('error-element-type-detect-failed'));
			}

			if(is_null($name = getRequest('name'))) {
				throw new coreException(getLabel('error-require-name-param'));
			}

			if(is_null($alt_name = getRequest('alt-name'))) {
				$alt_name = $name;
			}

			$type_id = getArrayKey($inputData, 'type-id');

			if(!$type_id && !($type_id = getRequest("type-id"))) {
				$type_id = $objectTypes->getBaseType($module, $method);

				if($parent instanceof iUmiHierarchyElement) {
					$dominant_type_id = $hierarchy->getDominantTypeId($parent->getId(), 1, $hierarchy_type_id);
					if($dominant_type_id) {
						$type_id = $dominant_type_id;
					}
				}
			}

			if(!$type_id) {
				throw new coreException("Base type for {$module}::{$method} doesn't exists");
			}

			$element_id = $hierarchy->addElement($parent_id, $hierarchy_type_id, $name, $alt_name, $type_id, $domain_id, $lang_id, $tpl_id);

			$users = $cmsController->getModule('users');
			if($users instanceof users) {
				backupModel::getInstance()->save($element_id);
				$users->setPerms($element_id);
			}

			$element = $hierarchy->getElement($element_id);

			if($element instanceof iUmiHierarchyElement) {
				$module_name = $element->getModule();
				$method_name = $element->getMethod();

				if(!is_null($is_active = getRequest('active'))) {
					$permissions = permissionsCollection::getInstance();
					$user_id = $permissions->getUserId();
					if($permissions->isAllowedMethod($user_id, $cmsController->getCurrentModule(), "publish") == false) {
						$is_active = false;
					}

					$element->setIsActive($is_active);
				}

				if(!is_null($is_visible = getRequest('is-visible'))) {
					$element->setIsVisible($is_visible);
				}

				if(!is_null($tpl_id = getRequest('template-id'))) {
					$element->setTplId($tpl_id);
				}

				if(!is_null($is_default = getRequest('is-default'))) {
					$element->setIsDefault($is_default);
				}

				if(!is_null($name = getRequest('name'))) {
					$element->setValue('h1', $name);
				}



				$object = $element->getObject();

				$this->saveAddedObject($object);

				$element->commit();
				$newObject = $element->getObject();
				//Set up "publish" status to new page
				if (!$newObject->getValue("publish_status")){
					$newObject->setValue("publish_status", $this->getPageStatusIdByStatusSid());
					$newObject->commit();
				}
				$event_after = new umiEventPoint("systemCreateElement");
				$event_after->addRef("element", $element);
				$event_after->setMode("after");
				$event_after->call();

				$this->currentEditedElementId = $element_id;
				return $element_id;
			} else {
				throw new coreException("Can't get created element instance");
			}
		}


		public function saveEditedObjectData($inputData) {
			if(is_array($inputData)) {
				$object = getArrayKey($inputData, 'object');
			} else {
				$object = $inputData;
			}

			if($object instanceof umiObject === false) {
				throw new coreException("Expected instance of umiObject in param");
			}

			if(is_array($inputData)) {
				$this->setRequestDataAliases(getArrayKey($inputData, 'aliases'), $object->getId());
			}

			$event = new umiEventPoint("systemModifyObject");
			$event->addRef("object", $object);
			$event->setMode("before");
			$event->call();

			if(!is_null($name = getRequest('name'))) {
				$object->setName($name);
				$object->setValue('nazvanie', $name);
			}

			if(!is_null($type_id = getRequest('type-id'))) {
				$object->setTypeId($type_id);
			}

			$dataModule = cmsController::getInstance()->getModule("data");
			$dataModule->saveEditedObject($object->getId(), false, true, true);

			$object->commit();

			$event->setMode("after");
			$event->call();

			return $object;
		}


		public function saveAddedObject(umiObject $object) {
			$event = new umiEventPoint("systemCreateObject");
			$event->addRef("object", $object);
			$event->setMode("before");
			$event->call();

			$dataModule = cmsController::getInstance()->getModule("data");
			$dataModule->saveEditedObject($object->getId(), true, true, true);

			if(!is_null($name = getRequest('name'))) {
				$object->setValue('nazvanie', $object->getName());
			}


			$object->commit();

			$event->setMode("after");
			$event->call();

			return $object->getId();
		}


		public function saveAddedObjectData($inputData) {
			$objectsCollection = umiObjectsCollection::getInstance();
			$typesCollection = umiObjectTypesCollection::getInstance();

			if($this->checkAllowedElementType($inputData, true) == false) {
				throw new wrongElementTypeAdminException(getLabel("error-unexpected-element-type"));
			}


			$this->setRequestDataAliases(getArrayKey($inputData, 'aliases'));

			if(is_null($name = getArrayKey($inputData, 'name'))) {
				$name = getRequest('name');
			}

			if(is_null($name)) {
				throw new publicAdminException("Require 'name' param in _REQUEST array.");
			}

			$module = get_class($this);
			$method = getArrayKey($inputData, 'type');
			$typeId = getArrayKey($inputData, 'type-id');

			if(!$typeId) {
				$typeId = $typesCollection->getBaseType($module, $method);
			}

			$objectId = $objectsCollection->addObject($name, $typeId);
			$object = $objectsCollection->getObject($objectId);
			if($object instanceof umiObject) {
				$this->saveAddedObject($object);
				return $object;
			} else {
				throw new coreException("Can't create object #{$objectId} \"{$name}\" of type #{$typeId}");
			}
		}


		public function saveEditedList($type, $params = false) {
			$data = getRequest("data");
			$dels = getRequest("dels");

			switch($type) {
				case "objects": {
					return $this->saveEditedObjectsList($data, $dels, $params);
				}

				case "basetypes": {
					return $this->saveEditedBaseTypesList($data, $dels);
				}

				case "domains": {
					return $this->saveEditedDomains($data, $dels);
				}

				case "domain_mirrows": {
					return $this->saveEditedDomainMirrows($data, $dels);
				}

				case "langs": {
					return $this->saveEditedLangs($data, $dels);
				}

				case "templates": {
					return $this->saveEditedTemplatesList($data, $dels, $params);
				}

				default: {
					throw new coreException("Can't save edited list of type \"{$type}\"");
				}
			}
		}


		public function saveEditedObjectsList($data, $dels, $params) {
			$collection = umiObjectsCollection::getInstance();
			$objectTypes = umiObjectTypesCollection::getInstance();
			$new_item_id = false;

			if(is_array($data)) {
				foreach($data as $id => $info) {
					$name = getArrayKey($info, 'name');
					$type_id = getArrayKey($params, 'type_id');
					$method = getArrayKey($params, 'type');
					if(!$type_id && $method) {
						$type_id = $objectTypes->getBaseType(get_class($this), $method);
					}

					if($id == "new") {
						if($name && $type_id) {
							$id = $collection->addObject($name, $type_id);
							$item = $collection->getObject($id);
							if($item instanceof umiObject) {
								$new_item_id = $this->saveAddedObject($item);
								$item->commit();
							}
						}
					} else {
						$item = $collection->getObject($id);

						if($item instanceof umiObject) {
							$item->setName($name);
							$this->saveEditedObjectData($item);
							$item->commit();
						} else {
							throw new coreException("Object #{$id} doesn't exists");
						}
					}
				}
			}

			if(is_array($dels)) {
				foreach($dels as $id) {
					$collection->delObject($id);
				}
			}

			return $new_item_id;
		}


		public function saveEditedBaseTypesList($data, $dels) {
			$collection = umiHierarchyTypesCollection::getInstance();

			if(is_array($data)) {
				foreach($data as $id => $info) {
					$title = getArrayKey($info, 'title');
					$module = getArrayKey($info, 'module');
					$method = getArrayKey($info, 'method');

					if($id == "new") {
						if($module && $title) {
							$collection->addType($module, $title, $method);
						}
					} else {
						$item = $collection->getType($id);

						if($item instanceof iUmiHierarchyType) {
							$item->setTitle($title);
							$item->setName($module);
							$item->setExt($method);
							$item->commit();
						} else {
							throw new coreException("Hierarchy type #{$id} doesn't exists");
						}
					}
				}
			}

			if(is_array($dels)) {
				foreach($dels as $id) {
					$collection->delType($id);
				}
			}
		}


		public function saveEditedTemplatesList($data, $dels, $params) {
			$collection = templatesCollection::getInstance();
			$default = getArrayKey($data, 'default');

			foreach($params as $host => $templates) {
				$domain_id = domainsCollection::getInstance()->getDomainId($host);
				$host_data = getArrayKey($data, $host);

				$default_tpl_id = getArrayKey($default, $domain_id);

				foreach($templates as $template) {
					$template_data = getArrayKey($host_data, $template->getId());

					$title = getArrayKey($template_data, 'title');
					$filename = getArrayKey($template_data, 'filename');

					if(!$title || !$filename) {
						continue;
					}

					$template->setTitle($title);
					$template->setFileName($filename);

					if(is_numeric($default_tpl_id)) {
						if($template->getId() == $default_tpl_id) {
							$template->setIsDefault(true);
						} else {
							$template->setIsDefault(false);
						}
					}

					$template->commit();
				}

				if(!is_null($template_data = getArrayKey($host_data, 'new'))) {
					$title = getArrayKey($template_data, 'title');
					$filename = getArrayKey($template_data, 'filename');

					if($title && $filename) {
						$lang_id = cmsController::getInstance()->getCurrentLang()->getId();
						$is_default = ($default_tpl_id == "new") ? true : false;
						$collection->addTemplate($filename, $title, $domain_id, $lang_id, $is_default);
					}
				}
			}

			if(is_array($dels)) {
				foreach($dels as $id) {
					$template = $collection->getTemplate($id);
					if($template->getIsDefault() == false) {
						unset($template);
						$collection->delTemplate($id);
					}
				}
			}
		}


		public function saveEditedTemplateData(iTemplate $template) {
			$name = getRequest('name');
			$title = getRequest('title');
			$filename = getRequest('filename');
			$type = getRequest('type');
			$used_pages = getRequest('used_pages');

			$template->setName($name);
			$template->setTitle($title);
			$template->setFilename($filename);
			$template->setType($type);
			$template->setUsedPages($used_pages);
			$template->commit();
		}


		public function saveEditedDomains($data, $dels) {
			$collection = domainsCollection::getInstance();

			if(is_array($data)) {
				foreach($data as $id => $info) {
					$host = getArrayKey($info, 'host');
					$lang_id = getArrayKey($info, 'lang_id');

					if($id == "new") {
						$host = domain::filterHostName($host);
						if($host && $lang_id) {
							$edition = regedit::getInstance()->getVal("//modules/autoupdate/system_edition");
							if ($edition=='gov' && count($collection->getList())>0) {
								throw new publicAdminException(getLabel('error-disabled-in-demo'));
							}

							if(defined("CURRENT_VERSION_LINE") &&
							in_array(CURRENT_VERSION_LINE, array('start', 'lite', 'shop'))) {
								throw new publicAdminException(getLabel('error-disabled-in-demo'));
							}

							if($collection->getDomainId($host)) {
								throw new publicAdminException(getLabel('error-domain-already-exists'));
							}

							$collection->addDomain($host, $lang_id);
						}
					} else {
						if(!$host) {
							$item = $collection->getDomain($id);
							$item->setDefaultLangId($lang_id);
							$item->commit();

							continue;
						}

						$item = $collection->getDomain($id);

						if($item instanceof iDomain) {
							if($item->getIsDefault() == false) {
								$item->setHost($host);
							}
							$item->setDefaultLangId($lang_id);
							$item->commit();
						} else {
							throw new coreException("Domain #{$id} doesn't exists");
						}
					}
				}
			}

			if(is_array($dels)) {
				foreach($dels as $id) {
					$collection->delDomain($id);
				}
			}
		}


		public function saveEditedDomainMirrows($data, $dels) {
			$collection = domainsCollection::getInstance();
			$domain = $collection->getDomain(getRequest('param0'));

			if(is_array($data)) {
				foreach($data as $id => $info) {
					$host = getArrayKey($info, 'host');

					if($id == "new") {
						$host = domain::filterHostName($host);
						if($host) {
							if($collection->getDomainId($host)) {
								throw new publicAdminException(getLabel('error-domain-already-exists'));
							}
							$domain->addMirrow($host);
						}
					} else {
						if(!$host) {
							continue;
						}

						$item = $domain->getMirrow($id);

						if($item instanceof iDomainMirrow) {
							$item->setHost($host);
							$item->commit();
						} else {
							throw new coreException("Domain #{$id} doesn't exists");
						}
					}
				}
			}

			if(is_array($dels)) {
				foreach($dels as $id) {
					$domain->delMirrow($id);
				}
			}

			$domain->setIsUpdated();
			$domain->commit();
		}


		public function saveEditedLangs($data, $dels) {
			$collection = langsCollection::getInstance();

			if(is_array($data)) {
				foreach($data as $id => $info) {
					$title  = getArrayKey($info, 'title');
					$prefix = getArrayKey($info, 'prefix');

					if(!strlen($title) || !strlen($prefix)) continue;

					$title  = trim($title);
					$prefix = preg_replace("/[^A-z0-9]*/", "", $prefix);

					if(!strlen($title) || !strlen($prefix)) continue;

					if($id == "new") {
						$id = $collection->addLang($prefix, $title);
					}

					$item = $collection->getLang($id);

					if($item instanceof iLang) {
						$item->setTitle($title);
						$item->setPrefix($prefix);
						$item->commit();
					} else {
						throw new coreException("Lang #{$id} doesn't exists");
					}
				}
			}
		}


		public function saveEditedTypeData($data) {
			$info = getRequest('data');

			$name = getArrayKey($info, 'name');
			$is_guidable = getArrayKey($info, 'is_guidable');
			$is_public = getArrayKey($info, 'is_public');
			$hierarchy_type_id = getArrayKey($info, 'hierarchy_type_id');

			$type = $data;
			if($type instanceof umiObjectType) {

				if (is_null($name) || $name == '') throw new coreException("Expected to get type name");
				if ($type->getHierarchyTypeId() && !$hierarchy_type_id && !$type->getIsGuidable()) throw new coreException("Expected to get hierarchy type id");

				$type->setName($name);
				$type->setIsGuidable($is_guidable);
				$type->setIsPublic($is_public);
				$type->setHierarchyTypeId($hierarchy_type_id);
				$type->commit();
			} else {
				throw new coreException("Expected instance of type umiObjectType");
			}

		}


		public function saveEditedGroupData($group) {
			$info = getRequest('data');

			$title = getArrayKey($info, 'title');
			$name = getArrayKey($info, 'name');
			$is_visible = getArrayKey($info, 'is_visible');

			if($group instanceof iUmiFieldsGroup) {
				$group->setName($name);
				$group->setTitle($title);
				$group->setIsVisible($is_visible);
				$group->setIsActive(true);
				$group->commit();
			} else {
				throw new coreException("Expected instance of type umiFieldsGroup");
			}
		}


		public function saveEditedFieldData($field) {
			$info = getRequest('data');

			$title = getArrayKey($info, 'title');
			$name = getArrayKey($info, 'name');
			$is_visible = getArrayKey($info, 'is_visible');
			$field_type_id = getArrayKey($info, 'field_type_id');
			$guide_id = getArrayKey($info, 'guide_id');
			$in_search = getArrayKey($info, 'in_search');
			$in_filter = getArrayKey($info, 'in_filter');
			$tip = getArrayKey($info, 'tip');
			$isRequired = getArrayKey($info, 'is_required');
			$restrictionId = getArrayKey($info, 'restriction_id');

			if($field instanceof umiField) {
				$field->setTitle($title);
				$field->setName($name);
				$field->setIsVisible($is_visible);
				$field->setFieldTypeId($field_type_id);
				$field->setIsInSearch($in_search);
				$field->setIsInFilter($in_filter);
				$field->setTip($tip);
				$field->setIsRequired($isRequired);
				$field->setRestrictionId($restrictionId);

				//Choose or create public guide for unlinked relation field
				$field_type_obj = umiFieldTypesCollection::getInstance()->getFieldType($field_type_id);
				$field_data_type = $field_type_obj->getDataType();

				if($field_data_type == "relation" && $guide_id == 0) {
					$guide_id = self::getAutoGuideId($title);
				}

				if($field_data_type == "optioned" && $guide_id == 0) {
					$parent_guide_id = umiObjectTypesCollection::getInstance()->getTypeIdByGUID('emarket-itemoption');
					$guide_id = self::getAutoGuideId($title, $parent_guide_id);
				}

				$field->setGuideId($guide_id);

				$field->commit();
			} else {
				throw new coreException("Expected instance of type umiField");
			}
		}


		public function saveAddedGroupData($inputData) {
			$info = getRequest('data');

			$name = getArrayKey($info, 'name');
			$title = getArrayKey($info, 'title');
			$is_visible = getArrayKey($info, 'is_visible');

			$type_id = getArrayKey($inputData, 'type-id');
			$type = umiObjectTypesCollection::getInstance()->getType($type_id);

			if($type instanceof umiObjectType) {
				$fields_group_id = $type->addFieldsGroup($name, $title, true, $is_visible);
				$type->commit();
				return $fields_group_id;
			} else {
				throw new coreException("Expected instance of type umiObjectType");
			}
		}


		public function saveAddedFieldData($inputData) {

			$group_id = $inputData['group-id'];
			$type_id = $inputData['type-id'];

			$info = getRequest('data');

			$title = getArrayKey($info, 'title');
			$name = getArrayKey($info, 'name');
			$is_visible = getArrayKey($info, 'is_visible');
			$field_type_id = getArrayKey($info, 'field_type_id');
			$guide_id = getArrayKey($info, 'guide_id');
			$in_search = getArrayKey($info, 'in_search');
			$in_filter = getArrayKey($info, 'in_filter');
			$tip = getArrayKey($info, 'tip');
			$isRequired = getArrayKey($info, 'is_required');
			$restrictionId = getArrayKey($info, 'restriction_id');

			$objectTypes = umiObjectTypesCollection::getInstance();
			$fields = umiFieldsCollection::getInstance();
			$fieldTypes = umiFieldTypesCollection::getInstance();

			//Check for non-unique field name
			$type = $objectTypes->getType($type_id);
			if($type instanceof umiObjectType) {
				if($type->getFieldId($name)) {
					throw new publicAdminException(getLabel('error-non-unique-field-name'));
				}
			}

			$field_type_obj = $fieldTypes->getFieldType($field_type_id);
			$field_data_type = $field_type_obj->getDataType();

			if($field_data_type == "relation" && $guide_id == 0) {
				$guide_id = self::getAutoGuideId($title);
			}

			if($field_data_type == "optioned" && $guide_id == 0) {
				$parent_guide_id = $objectTypes->getTypeIdByGUID('emarket-itemoption');
				$guide_id = self::getAutoGuideId($title, $parent_guide_id);
			}

			//Ищем аналог в горизонтальной иерархии
			$field_id = false;
			$parentTypeId = $type->getParentId();
			$horisontalTypes = $objectTypes->getSubTypesList($parentTypeId);
			foreach($horisontalTypes as $horisontalTypeId) {
				if($horisontalTypeId == $type_id) continue;
				$horisontalType = $objectTypes->getType($horisontalTypeId);
				if($horisontalType instanceof umiObjectType == false) continue;

				if($horisontalFieldId = $horisontalType->getFieldId($name)) {
					$horisontalField = $fields->getField($horisontalFieldId);
					if($horisontalField instanceof umiField == false) continue;
					if(($horisontalField->getFieldTypeId() == $field_type_id) && ($horisontalField->getTitle() == $title)) {
						$field_id = $horisontalFieldId;
						break;
					}
				}
			}

			//Ищем аналоги в вертикальной иерархии
			$verticalTypes = $objectTypes->getSubTypesList($type_id);
			foreach($verticalTypes as $verticalTypeId) {
				$verticalType = $objectTypes->getType($verticalTypeId);
				if($verticalType instanceof umiObjectType == false) continue;

				if($verticalFieldId = $verticalType->getFieldId($name)) {
					$verticalField = $fields->getField($verticalFieldId);
					if($verticalField instanceof umiField == false) continue;
					if(($verticalField->getFieldTypeId() == $field_type_id) && ($verticalField->getTitle() == $title)) {
						$field_id = $verticalFieldId;
						break;
					}
				}
			}

			if($field_id === false) {
				//Содаем новое поле, если не нашли в вертикальной и горизонтальной иерархии аналогов
				$field_id = $fields->addField($name, $title, $field_type_id, $is_visible, false, false);

				$field = $fields->getField($field_id);
				$field->setGuideId($guide_id);
				$field->setIsInSearch($in_search);
				$field->setIsInFilter($in_filter);
				$field->setTip($tip);
				$field->setIsRequired($isRequired);
				$field->setRestrictionId($restrictionId);
				$field->commit();
			}

			if($type instanceof umiObjectType) {
				$group = $type->getFieldsGroup($group_id);
				if($group instanceof umiFieldsGroup) {
					$group->attachField($field_id);
					$group_name = $group->getName();

					$childs = $objectTypes->getChildClasses($type_id);
					$sz = sizeof($childs);

					for($i = 0; $i < $sz; $i++) {
						$child_type_id = $childs[$i];
						$child_type = $objectTypes->getType($child_type_id);

						if($child_type instanceof umiObjectType) {

							if($child_type->getFieldId($name) == $field_id) continue;

							$child_group = $child_type->getFieldsGroupByName($group_name);
							if($child_group instanceof umiFieldsGroup) {
								$child_group->attachField($field_id, true);
							} else {
								throw new coreException("Can't find umiFieldsGroup #{$group_name} in umiObjectType #{$child_type_id}");
							}
						} else {
							throw new coreException("Can't find umiObjectType #{$child_type_id}");
						}
					}
					return $field_id;
				} else {
					throw new coreException("Can't find umiFieldsGroup #{$group_id}");
				}
			} else {
				throw new coreException("Can't find umiObjectType #{$type_id}");
			}
		}


		public function chooseRedirect($redirect_string = false) {
			$cmsController = cmsController::getInstance();
			$hierarchy = umiHierarchy::getInstance();
			$referer_uri = $cmsController->getCalculatedRefererUri();

			$save_mode_str = getRequest('save-mode');

			switch($save_mode_str) {
				case getLabel('label-save-exit'):
				case getLabel('label-save-add-exit'): {
					$save_mode = 1;
					break;
				}

				case getLabel('label-save-view'):
				case getLabel('label-save-add-view'): {
					$save_mode = 2;
					break;
				}

				case getLabel('label-save'):
				case getLabel('label-save-add'): {
					$save_mode = 3;
					break;
				}

				default: {
					$save_mode = false;
				}
			}

			if($forceRedirectUrl = getArrayKey($_GET, 'force-redirect')) {
				$this->redirect($forceRedirectUrl);
			}

			if($save_mode == 1) {
				$this->redirect($referer_uri);
			}

			if($save_mode == 2) {
				$element_id = $this->currentEditedElementId;
				if($element_id) {
					$element_path = $hierarchy->getPathById($element_id);
					$this->redirect($element_path);
				}
			}

			if($redirect_string !== false) {
				$this->redirect($redirect_string);
			}

			if($save_mode && $element_id = $this->currentEditedElementId) {
				$element = $hierarchy->getElement($element_id);

				if($element instanceof umiHierarchyElement) {
					$element_module = $element->getHierarchyType()->getName();
					$element_method = $element->getHierarchyType()->getExt();
					$module = cmsController::getInstance()->getModule($element_module);

					if($module instanceof def_module) {
						$links = $module->getEditLink($element_id, $element_method);
						$edit_link = isset($links[1]) ? $links[1] : false;

						if($edit_link) {
							$this->redirect($edit_link);
						}
					}
				}
			}
			$request_uri = $this->removeErrorParam(getServer('HTTP_REFERER'));
			outputBuffer::current()->redirect($request_uri);
		}


		public function checkAllowedElementType($inputData, $ignoreIfNull = true) {
			$element = getArrayKey($inputData, 'element');
			$object = getArrayKey($inputData, 'object');
			$type = getArrayKey($inputData, 'type');
			$allowed_types = getArrayKey($inputData, 'allowed-element-types');

			$commentsHierarchyType = umiHierarchyTypesCollection::getInstance()->getTypeByName("comments", "comment");
			if($commentsHierarchyType) {
				$commentsHierarchyTypeId = $commentsHierarchyType->getId();
			} else {
				$commentsHierarchyTypeId = false;
			}

			if(is_array($allowed_types) === false) {
				if($ignoreIfNull === false) {
					throw new coreException("Allowed types expected to be array");
				} else {
					return true;
				}
			}


			if($type) {
				if(in_array($type, $allowed_types)) {
					return true;
				} else {
					return false;
				}
			}

			if($element instanceof umiHierarchyElement === true) {
				$hierarchy_type_id = $element->getTypeId();
			} else if($object instanceof umiObject === true) {
				$object_type_id = $object->getTypeId();
				$object_type = umiObjectTypesCollection::getInstance()->getType($object_type_id);
				$hierarchy_type_id = $object_type->getHierarchyTypeId();
			} else {
				throw new coreException("If you are doing 'add' method, you should pass me 'type' key in 'inputData' array. If you have 'edit' method, pass me 'element' key in 'inputData' array.");
			}

			$hierarchy_type = umiHierarchyTypesCollection::getInstance()->getType($hierarchy_type_id);

			if($hierarchy_type instanceof iUmiHierarchyType) {
				$method = $hierarchy_type->getExt();
				if(in_array($method, $allowed_types) || $hierarchy_type->getId() == $commentsHierarchyTypeId) {
					return true;
				} else {
					return false;
				}
			} else {
				throw new coreException("This should never happen");
			}
		}


		public function checkElementPermissions($element_id, $requiredPermission = permissionsCollection::E_EDIT_ALLOWED) {
			static $permissions = NULL, $user_id = NULL;
			if(is_null($permissions)) {
				$permissions = permissionsCollection::getInstance();
				$user_id = $permissions->getUserId();
			}

			$allow = $permissions->isAllowedObject($user_id, $element_id);

			if(!isset($allow[$requiredPermission]) || $allow[$requiredPermission] == false) {
				throw new requreMoreAdminPermissionsException(getLabel("error-require-more-permissions"));
			} else {
				return true;
			}
		}


		public function switchActivity($params) {
			if($this->checkAllowedElementType($params) == false) {
				throw new wrongElementTypeAdminException(getLabel("error-unexpected-element-type"));
			}

			$element = getArrayKey($params, 'element');
			$activity = getArrayKey($params, 'activity');

			if($element instanceof umiHierarchyElement === false) {
				throw new expectElementException(getLabel('error-expect-element'));
			}

			$this->checkElementPermissions($element->getId());

			if(is_null($activity)) {
				$activity = !$element->getIsActive();
			}

			$module_name = $element->getModule();
			$method_name = $element->getMethod();

			$permissions = permissionsCollection::getInstance();
			$user_id = $permissions->getUserId();
			if($permissions->isAllowedMethod($user_id, $module_name, "publish") == false) {
				throw new requreMoreAdminPermissionsException(getLabel('error-no-publication-permissions'));
			}

			if($activity == $element->getIsActive()) {	//Don't raise event, if no modifications planned
				return $activity;
			}

			$event = new umiEventPoint("systemSwitchElementActivity");
			$event->addRef("element", $element);
			$event->setParam("activity", $activity);
			$event->setMode("before");

			try {
				$event->call();
			} catch (coreBreakEventsException $e) {
				return $element->getIsActive();
			}

			$element->setIsActive($activity);
			$element->commit();

			$event->setMode("after");

			try {
				$event->call();
			} catch (coreBreakEventsException $e) {
				return $element->getIsActive();
			}

			return $element->getIsActive();
		}


		public function moveElement($params) {
			if($this->checkAllowedElementType($params) == false) {
				throw new wrongElementTypeAdminException(getLabel("error-unexpected-element-type"));
			}
			$hierarchy = umiHierarchy::getInstance();
			$domains = domainsCollection::getInstance();

			$element = getArrayKey($params, 'element');
			$parentId = getArrayKey($params, 'parent-id');
			$domainId = getArrayKey($params, 'domain');


			$asSibling = getArrayKey($params, 'as-sibling');
			$beforeId = getArrayKey($params, 'before-id');

			$this->checkElementPermissions($element->getId(), permissionsCollection::E_MOVE_ALLOWED);

			$event = new umiEventPoint("systemMoveElement");
			$event->addRef("element", $element);
			$event->setParam("parent-id", $parentId);
			$event->setParam("domain-host", $domainId);
			$event->setParam("as-sibling", $asSibling);
			$event->setParam("before-id", $beforeId);
			$event->setMode("before");

			try {
				$event->call();
			} catch (coreBreakEventsException $e) {
				return false;
			}

			if(is_numeric($domainId) == false)
				$domainId = $domains->getDomainId($domainId);

			$oldParentId = $element->getParentId();

			if ($domainId) {
				$element->setDomainId($domainId);
			}
			$element->commit();

			if ($asSibling) {
				$hierarchy->moveBefore($element->getId(), $parentId, (($beforeId) ? $beforeId : false));
			} else {
				$hierarchy->moveFirst($element->getId(), $parentId);
			}
			$element->update();
			$element->setIsUpdated();
			$element->commit();
			$event->setMode("after");
			try {
				$event->call();
				return true;
			} catch (coreBreakEventsException $e) {
				return false;
			}
		}


		public function deleteElement($params) {
			$element = getArrayKey($params, 'element');

			if($element instanceof umiHierarchyElement === false) {
				throw new expectElementException(getLabel('error-expect-element'));
			}

			if($this->checkAllowedElementType($params) == false) {
				throw new wrongElementTypeAdminException(getLabel("error-unexpected-element-type"));
			}

			$this->checkElementPermissions($element->getId(), permissionsCollection::E_DELETE_ALLOWED);

			$event = new umiEventPoint("systemDeleteElement");
			$event->addRef("element", $element);
			$event->setMode("before");
			$event->call();

			umiHierarchy::getInstance()->delElement($element->getId());

			$event->setMode("after");
			$event->call();
		}


		public function deleteObject($params) {
			$objectsCollection = umiObjectsCollection::getInstance();
			$objectTypesCollection = umiObjectTypesCollection::getInstance();
			$hierarchyTypesCollection = umiHierarchyTypesCollection::getInstance();

			$object = getArrayKey($params, 'object');
			if($object instanceof umiObject == false) {
				throw new coreException("You should pass \"object\" key containing umiObject instance.");
			}
			$object_id = $object->getId();

			$object_type_id = $object->getTypeId();
			$object_type = $objectTypesCollection->getType($object_type_id);

			if($object_type instanceof umiObjectType == false) {
				throw new coreException("Object #{$object_id} hasn't type #{$object_type_id}. This should not happen.");
			}

			if(!is_null(getArrayKey($params, 'type'))) {
				$hierarchy_type_id = $object_type->getHierarchyTypeId();
				$hierarchy_type = $hierarchyTypesCollection->getType($hierarchy_type_id);
				if($hierarchy_type instanceof iUmiHierarchyType == false) {
					throw new coreException("Object type #{$object_type_id} doesn't have hierarchy type #{$hierarchy_type_id}. This should not happen.");
				}
				$params['type'] = $hierarchy_type->getExt();

				if($this->checkAllowedElementType($params) == false) {
					throw new wrongElementTypeAdminException(getLabel("error-unexpected-element-type"));
				}
			}

			$event = new umiEventPoint("systemDeleteObject");
			$event->addRef("object", $object);
			$event->setMode("before");
			$event->call();

			$result = $objectsCollection->delObject($object_id);

			$event->setMode("after");
			$event->call();

			return $result;
		}


		public function getFloatedDomain() {
			if(!is_null($domain_floated = getRequest('domain'))) {
				$domain_floated_id = domainsCollection::getInstance()->getDomainId($domain_floated);
				if($domain_floated_id) {
					return $domain_floated_id;
				}
			}
			return false;
		}


		public static function getAutoGuideId($title, $baseGuideId = false) {
			$objectTypesCollection = umiObjectTypesCollection::getInstance();
			if(!$baseGuideId) $baseGuideId = $objectTypesCollection->getTypeIdByGUID('root-guides-type');
			$guide_name = getLabel('autoguide-for-field') . " \"{$title}\"";

			$child_types = $objectTypesCollection->getChildClasses($baseGuideId);
			foreach($child_types as $child_type_id) {
				$child_type = $objectTypesCollection->getType($child_type_id);
				$child_type_name = $child_type->getName();

				if($child_type_name == $guide_name) {
					$child_type->setIsGuidable(true);
					return $child_type_id;
				}
			}

			$guide_id = $objectTypesCollection->addType($baseGuideId, $guide_name);
			$guide = $objectTypesCollection->getType($guide_id);
			$guide->setIsGuidable(true);
			$guide->setIsPublic(true);
			$guide->commit();

			return $guide_id;
		}


		public function checkDomainPermissions($domain_id = false) {
			$permissions = permissionsCollection::getInstance();
			$domains = domainsCollection::getInstance();
			$cmsController = cmsController::getInstance();

			if($domain_id == false) {
				if(!is_null($domain_host = getRequest('domain'))) {
					$domain_id = $domains->getDomainId($domain_host);
				} else {
					$domain_id = $cmsController->getCurrentDomain()->getId();
				}
			}

			if(!$domain_id) {
				throw new coreException("Require domain id to check domain permissions");
			}

			$user_id = $permissions->getUserId();
			$is_allowed = $permissions->isAllowedDomain($user_id, $domain_id);

			if($is_allowed == 0) {
				throw new requreMoreAdminPermissionsException(getLabel('error-no-domain-permissions'));
			} else {
				return NULL;
			}
		}


		public function setRequestDataAlias($key1, $key2) {
			if(isset($_REQUEST[$key2])) {
				$_REQUEST[$key1] = &$_REQUEST[$key2];
				return true;
			} else {
				return false;
			}
		}


		public function setRequestDataAliases($aliases, $id = "new") {
			if(!is_array($aliases)) {
				return false;
			}

			foreach($aliases as $key1 => $key2) {
				if(isset($_REQUEST['data'][$id][$key2])) {
					$_REQUEST[$key1] = &$_REQUEST['data'][$id][$key2];
				}

			}
		}

		public function compareObjectTypeByHierarchy($module, $method, $type_id) {
			$typesCollection = umiObjectTypesCollection::getInstance();
			$hierarchyTypesCollection = umiHierarchyTypesCollection::getInstance();

			$type = $typesCollection->getType($type_id);
			$hierarchy_type = $hierarchyTypesCollection->getTypeByName($module, $method);

			if($type instanceof umiObjectType && $hierarchy_type instanceof umiHierarchyType) {
				return $type->getHierarchyTypeId() == $hierarchy_type->getId();
			} else {
				return false;
			}
		}
		public function systemIsLocked($element, $user_id){
			if ($element){
				$oPage = $element->getObject();
				$lockTime = $oPage->getValue("locktime");
				$lockUser = $oPage->getValue("lockuser");
				if ($lockTime == null || $lockUser == null){
					return false;
				}
				$lockDuration = regedit::getInstance()->getVal("//settings/lock_duration");
				if (($lockTime->timestamp + $lockDuration) > time() && $lockUser!=$user_id){
					return true;
				}else{
					$oPage->setValue("lockuser", null);
					$oPage->setValue("locktime", null);
					$oPage->commit();
					$element->commit();
					return false;
				}
			}
		}

		/* Deprecated. use selector */
		public function autoDetectAllFilters(umiSelection $sel, $objectsMode = false) {
			$arr_parents = getRequest('rel');
			$i_depth = (int) getRequest('depth');
			$arr_domains = getRequest('domain_id');
			$arr_langs = getRequest('lang_id');

			if(!$i_depth) {
				$i_depth = 0;
			}

			$hierarchy = umiHierarchy::getInstance();
			$objectTypesCollection = umiObjectTypesCollection::getInstance();
			$hierarchyTypesCollection = umiHierarchyTypesCollection::getInstance();

			if(is_null(getRequest('or-mode')) == false) {
				$sel->setConditionModeOr();
			}

			if (isset($arr_domains[0])) {
				$sel->setDomainId($arr_domains[0]);
			}

			if (isset($arr_langs[0])) {
				$sel->setLangId($arr_langs[0]);
			}

			if (is_array($arr_parents) && count($arr_parents)) {
				foreach ($arr_parents as $s_parent_id) {
					$i_parent_id = intval($s_parent_id);
					if (is_numeric($i_parent_id)) {
						$sel->addHierarchyFilter(intval($s_parent_id), $i_depth, true);
					}
				}
			}

			$objectTypes = array_extract_values($sel->getObjectTypeConds());
			$elementTypes = array_extract_values($sel->getElementTypeConds());
			$hierarchyParents = array_extract_values($sel->getHierarchyConds(), $foo, true);

			$searchAllTextCond = getRequest('search-all-text');
			$searchAllTextCond = array_extract_values($searchAllTextCond);
			$filterCond = getRequest('fields_filter');
			$filterCond = array_extract_values($filterCond, $foo, true);

			if(sizeof($elementTypes)) {
				$lastElementTypeId = $elementTypes[sizeof($elementTypes) - 1];
			} else {
				$lastElementTypeId = false;
			}

			if(sizeof($objectTypes)) {
				reset($objectTypes);
				$typeId = current($objectTypes);
			} else if(sizeof($hierarchyParents)) {
				reset($hierarchyParents);
				$typeId = $hierarchy->getDominantTypeId(current($hierarchyParents));
			} else if(sizeof($elementTypes) && (!empty($searchAllTextCond) || sizeof($elementTypes) == 1)) {
				reset($elementTypes);
				$typeId = $objectTypesCollection->getTypeByHierarchyTypeId(array_pop($elementTypes));
			} else {
				$typeId = $objectTypesCollection->getTypeIdByGUID('root-pages-type');
			}

			if(empty($hierarchyParents) && !empty($elementTypes) && empty($searchAllTextCond) && empty($filterCond)) {
				$sel->optimize_root_search_query = true;
			} else {
				if(!empty($filterCond)) {
					if($lastElementTypeId) {
						$typeId = $objectTypesCollection->getTypeByHierarchyTypeId($lastElementTypeId);
					}
				}
			}

			if($typeId) {
				$this->autoDetectFilters($sel, $typeId);
				$this->autoDetectOrders($sel, $typeId);
			}

			if(!$objectsMode) {
				$sel->excludeNestedPages = true;
			}

			if(sizeof($hierarchyParents) && sizeof($elementTypes)) {
				$hierarchy_type_id = $hierarchyTypesCollection->getTypeByName("comments", "comment")->getId();
				$sel->addElementType($hierarchy_type_id);
			}

			if(getRequest('import')) {
				quickCsvImporter::autoImport($sel, $objectsMode, (bool) getRequest('force-hierarchy'));
			}

			if(getRequest('export')) {
				quickCsvExporter::autoExport($sel, (bool) getRequest('force-hierarchy'));
			}
			return true;
		}

		public function getPageStatusIdByStatusSid($statusId = 'page_status_publish') {
			$sel = new umiSelection;
			$sel->setObjectTypeFilter();
			$objectTypeId = $this->getGuideIdByFieldName('publish_status');
			if(!$objectTypeId) {
				return false;
			}
			$sel->addObjectType($objectTypeId);

			$result = umiSelectionsParser::runSelection($sel);
			foreach ($result as $objectId) {
				$statusStringId = umiObjectsCollection::getInstance()->getObject($objectId)->getValue("publish_status_id");
				if ($statusStringId == $statusId) {
					return $objectId;
				}
			}
			return false;
		}

		public function getGuideIdByFieldName($fieldName) {
			$fields = umiObjectTypesCollection::getInstance()->getTypeByGUID('root-pages-type');
			foreach ($fields->getAllFields() as $field) {
				if ($field->getName() == $fieldName) {
					return $field->getGuideId();
				}
			}
			return false;
		}

		public function setHeaderLabel($label) {
			$cmsController = cmsController::getInstance();
			$cmsController->headerLabel = $label;
		}

		public function getObjectTypeMethod($object) {
			if($object instanceof umiObject == false) {
				throw new coreException("Expected instance of umiObject as param.");
			}

			$objectTypes = umiObjectTypesCollection::getInstance();
			$objectTypeId = $object->getTypeId();
			$objectType = $objectTypes->getType($objectTypeId);
			if($objectType instanceof umiObjectType) {
				$hierarchyTypes = umiHierarchyTypesCollection::getInstance();
				$hierarchyTypeId = $objectType->getHierarchyTypeId();
				$hierarchyType = $hierarchyTypes->getType($hierarchyTypeId);

				if($hierarchyType instanceof umiHierarchyType) {
					return $hierarchyType->getExt();
				} else {
					throw new coreException("Can't get hierarchy type #{$hierarchyTypeId}");
				}
			} else {
				throw new coreException("Can't get object type #{$objectTypeId}");
			}
		}

		public function getDatasetConfiguration($param = '') {
			return array(
					'methods' => array(
						array('title'=>getLabel('smc-load'), 'forload'=>true, 			 'module'=>'content', '#__name'=>'load_tree_node'),
						array('title'=>getLabel('smc-delete'), 					     'module'=>'content', '#__name'=>'tree_delete_element'),
						array('title'=>getLabel('smc-activity'), 		 'module'=>'content', '#__name'=>'tree_set_activity'),
						array('title'=>getLabel('smc-copy'), 'module'=>'content', '#__name'=>'tree_copy_element'),
						array('title'=>getLabel('smc-move'), 					 'module'=>'content', '#__name'=>'tree_move_element'),
						array('title'=>getLabel('smc-change-template'), 						 'module'=>'content', '#__name'=>'change_template'),
						array('title'=>getLabel('smc-change-lang'), 					 'module'=>'content', '#__name'=>'copy_to_lang_old'),
						array('title'=>getLabel('smc-change-lang'), 					 'module'=>'content', '#__name'=>'move_to_lang'),
					)
				);
		}

		final public function dataset_config() {
			$param = getRequest('param');

			$childMap = array('methods'=>'method', 'types'=>'type', 'stoplist'=>'exclude', 'default'=>'column');

			$datasetConfig = $this->getDatasetConfiguration($param);

			$document = new DOMDocument();
			$document->encoding = "utf-8";

			$root	  = $document->createElement('dataset');
			$document->appendChild($root);

			if(is_array($datasetConfig)) {
				$objectTypes = umiObjectTypesCollection::getInstance();

				foreach($datasetConfig as $sectionName => $sectionRecords) {
					$section = $document->createElement($sectionName);
					$root->appendChild($section);
					if(is_array($sectionRecords)) {
						foreach($sectionRecords as $record) {
							$element = $document->createElement($childMap[$sectionName]);
							if(is_array($record)) {
								foreach($record as $propertyName => $propertyValue) {
									if($propertyName === "#__name") {
										$element->appendChild( $document->createTextNode($propertyValue) );
										continue;
									}

									if($propertyName == "id" && !is_numeric($propertyValue)) {
										$propertyValue = $objectTypes->getBaseType(get_class($this), $propertyValue);
									}
									$element->setAttribute($propertyName, is_bool($propertyValue) ? ($propertyValue ? "true" : "false") : $propertyValue );
								}
							} else {
								$element->appendChild( $document->createTextNode($record) );
							}
							$section->appendChild($element);
						}
					} else {
						$section->appendChild( $document->createTextNode($sectionRecords) );

					}
				}
			}

			$buffer = outputBuffer::current();
			$buffer->contentType('text/xml');
			$buffer->charset('utf-8');
			$buffer->push($document->saveXML());
			$buffer->end();
		}

		public function change_template() {
			$elements = getRequest('element');
			if(!is_array($elements)) {
				$elements = array($elements);
			}

			$element = $this->expectElement("element");
			$templateId = getRequest('template-id');

			if (!is_null($templateId)) {
				foreach($elements as $elementId) {
					$element = $this->expectElement($elementId, false, true);

					if ($element instanceof umiHierarchyElement) {
						$element->setTplId($templateId);
						$element->commit();
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

		public function switchGroupsActivity($groupName, $activity) {
			$groups = umiFieldsGroup::getAllGroupsByName($groupName);
			foreach($groups as $group) {
				if($group instanceof umiFieldsGroup) {
					$group->setIsActive($activity);
					$group->commit();
				}
			}
		}
	};
?>
