<?php
	class __webforms extends baseModuleAdmin {
		public $iFormFilter = 0, $iAddressFilter = 0;

		public function addresses() {
			$this->setDataType("list");
			$this->setActionType("view");
			if($this->ifNotXmlMode()) return $this->doData();

			$sel = new selector('objects');
			$sel->types('object-type')->name('webforms', 'address');
			selectorHelper::detectFilters($sel);

			$data = $this->prepareData($sel->result, "objects");
			$this->setData($data);
			return $this->doData();
		}

		public function address_add() {
			$mode      = (string) getRequest('param0');
			$inputData = Array('type' => 'address');
			if($mode == 'do') {
				if(!isset($_REQUEST['data']['new']['address_description']) ||
					!isset($_REQUEST['data']['new']['address_list']) ||
					!strlen($_REQUEST['data']['new']['address_description']) ||
					!strlen($_REQUEST['data']['new']['address_list']))
						throw new publicAdminException(getLabel('error-required_fields'));
				$aData   = getRequest('data');
				$oObject = $this->saveAddedObjectData($inputData);
				$this->chooseRedirect('/admin/webforms/address_edit/'.$oObject->getId().'/');
			}
			$this->setDataType("form");
			$this->setActionType("create");
			$data = $this->prepareData($inputData, "object");
			$this->setData($data);
			return $this->doData();
		}

		public function address_edit() {
			$object = $this->expectObject("param0");
			$id     = (int)    getRequest('param0');
			$mode   = (string) getRequest('param1');
			if($mode == "do") {
				if(!isset($_REQUEST['data'][$id]['address_description']) ||
					!isset($_REQUEST['data'][$id]['address_list']) ||
					!strlen($_REQUEST['data'][$id]['address_description']) ||
					!strlen($_REQUEST['data'][$id]['address_list']))
						throw new publicAdminException(getLabel('error-required_fields'));
				$this->saveEditedObjectData($object);
				$this->chooseRedirect();
			}
			$this->setDataType("form");
			$this->setActionType("modify");
			$data = $this->prepareData($object, "object");
			$this->setData($data);
			return $this->doData();
		}

		public function address_delete() {
			$iObjectId = (int)getRequest('param0');
			umiObjectsCollection::getInstance()->delObject($iObjectId);
			$this->chooseRedirect('/admin/webforms/addresses/');
		}

		public function templates() {
			$this->setDataType("list");
			$this->setActionType("view");
			if($this->ifNotXmlMode()) return $this->doData();

			$limit = 20;
			$curr_page = (int) getRequest("p");
			$offset =  $limit * $curr_page;

			$sel = new selector('objects');
			$sel->types('object-type')->name('webforms', 'template');
			$sel->limit($offset, $limit);
			selectorHelper::detectFilters($sel);

			$this->setDataRange($limit, $offset);
			$data = $this->prepareData($sel->result, "objects");
			$this->setData($data, $sel->length);
			return $this->doData();
		}

		public function template_add() {
			$oTypes    = umiObjectTypesCollection::getInstance();
			$iBaseId   = $oTypes->getBaseType('webforms', 'form');
			$mode      = (string) getRequest('param0');
			$inputData = Array('type' => 'template');
			if($mode == 'do') {
				$iFormId = (int)getRequest('system_form_id');
				if(!$iFormId || !$oTypes->isExists($iFormId)) throw new publicAdminException( getLabel('error-no-form-binded') );
				$inputData['name'] = $oTypes->getType($iFormId)->getName();
				$oTemplate = $this->saveAddedObjectData($inputData);
				$oTemplate->setValue('form_id', $iFormId);
				$this->chooseRedirect('/admin/webforms/template_edit/'.$oTemplate->getId().'/');
			}
			$this->setDataType("form");
			$this->setActionType("create");
			$data = $this->prepareData($inputData, "object");
			$data['nodes:group'][1] = array( 'attribute:name' => 'BindToForm',
											 'attribute:title' => 'Принадлежность к форме',
											 'attribute:base_type' => $iBaseId,
											 'attribute:selected_type' => '' );
			$this->setData($data);
			return $this->doData();
		}

		public function template_edit() {
			$oTypes    = umiObjectTypesCollection::getInstance();
			$iBaseId   = $oTypes->getBaseType('webforms', 'form');
			$object = $this->expectObject("param0");
			$mode = (string) getRequest('param1');
			if($mode == "do") {
				$iFormId = (int)getRequest('system_form_id');
				if(!$iFormId || !$oTypes->isExists($iFormId))
					throw new publicAdminException( getLabel('error-no-form-binded') );
				$this->saveEditedObjectData($object);
				$object->setName( $oTypes->getType($iFormId)->getName() );
				$object->setValue('form_id', $iFormId);
				$this->chooseRedirect();
			}
			$this->setDataType("form");
			$this->setActionType("modify");
			$data = $this->prepareData($object, "object");
			$data['nodes:group'][1] = array( 'attribute:name' => 'BindToForm',
											 'attribute:title' => 'Принадлежность к форме',
											 'attribute:base_type' => $iBaseId,
											 'attribute:selected_type' => $object->getValue('form_id') );
			$this->setData($data);
			return $this->doData();
		}

		public function template_delete() {
			$iObjectId = (int)getRequest('param0');
			umiObjectsCollection::getInstance()->delObject($iObjectId);
			$this->chooseRedirect('/admin/webforms/templates/');
		}

		public function messages() {
			$this->setDataType("list");
			$this->setActionType("view");
			if($this->ifNotXmlMode()) return $this->doData();

			$limit = 20;
			$curr_page = (int) getRequest("p");
			$offset = $limit * $curr_page;

			$sel = new selector('objects');
			if ($types = getRequest('object_types')) {
				$sel->types('object-type')->id($types);
			}
			else $sel->types('object-type')->name('webforms', 'form');
			$sel->limit($offset, $limit);
			selectorHelper::detectFilters($sel);

			$this->setDataRange($limit, $offset);
			$data = $this->prepareData($sel->result, "objects");
			$this->setData($data, $sel->length);
			return $this->doData();
		}

		public function message() {
			$iObjectId   = (int)getRequest('param0');
			$oCollection = umiObjectsCollection::getInstance();
			if(!$oCollection->isExists($iObjectId)) { throw new publicAdminException('The object does not exist'); }
			$oMessage = umiObjectsCollection::getInstance()->getObject($iObjectId);

			$this->validateEntityByTypes($oMessage, array(
				'module' => 'webforms'
			), true);

			$sMessage = $this->formatMessage($iObjectId);
			$sAddress = $oMessage->getName();
			$sForm    = umiObjectTypesCollection::getInstance()->getType($oMessage->getTypeId())->getName();
			$sIP      = $oMessage->getValue('sender_ip');
			/**
			* @var umiDate
			*/
			$oDate    = $oMessage->getValue('sending_time');
			$this->setDataType("settings");
			$this->setActionType("view");
			$data     = $this->prepareData(array( 'Message' => array(
											'string:message'=>$sMessage,
											'string:address'=>$sAddress,
											'string:form'=>$sForm,
											'string:date'=> (($oDate instanceof umiDate) ? $oDate->getFormattedDate() : ""),
											'string:ip'=> $sIP,
											'int:id'=>$iObjectId)), "settings");
			$this->setData($data);
			return $this->doData();
		}

		public function message_delete() {
			$iObjectId = (int)getRequest('param0');
			umiObjectsCollection::getInstance()->delObject($iObjectId);
			$this->chooseRedirect('/admin/webforms/messages/');
		}

		public function forms() {
			$curr_page = (int) getRequest('p');
			$per_page = 20;

			$oTypes   = umiObjectTypesCollection::getInstance();
			$iBaseTID = $oTypes->getBaseType("webforms", "form");
			if($iBaseTID === false) { throw new publicAdminException('No form base type was found'); }

			if(isset($_REQUEST['search-all-text'][0])) {
				$searchAllText = array_extract_values($_REQUEST['search-all-text']);
				foreach($searchAllText as $i => $v) {
					$searchAllText[$i] = wa_strtolower($v);
				}
			} else {
				$searchAllText = false;
			}

			$types = $oTypes;
			$sub_types = $types->getSubTypesList($iBaseTID);

			$tmp = Array();
			foreach($sub_types as $typeId) {
				$type = $types->getType($typeId);
				if($type instanceof umiObjectType) {
					$name = $type->getName();

					if($searchAllText) {
						$match = false;
						foreach($searchAllText as $searchString) {
							if(strstr(wa_strtolower($name), $searchString) !== false) {
								$match = true;
							}
						}
						if(!$match) {
							continue;
						}
					}
					$tmp[$typeId] = $name;
				}
			}

			if(isset($_REQUEST['order_filter']['name'])) {
				natsort($tmp);
				if($_REQUEST['order_filter']['name'] == "desc") {
					$tmp = array_reverse($tmp, true);
				}
			}

			$sub_types = array_keys($tmp);
			unset($tmp);
			$sub_types = $this->excludeNestedTypes($sub_types);

			$total = sizeof($sub_types);
			$aTypes = array_slice($sub_types, $curr_page * $per_page, $per_page, false);


			$this->setDataType("list");
			$this->setActionType("view");

			$this->setDataRange($per_page, $per_page * $curr_page);
			$data = $this->prepareData($aTypes, "types");
			$data['nodes:basetype'] = array( array('attribute:id' => $iBaseTID) );
			$this->setData($data, $total);
			return $this->doData();
		}

		public function form_add() {
			$sMode    = (string)getRequest('param1');
			$oTypes   = umiObjectTypesCollection::getInstance();
			$iBaseTID = $oTypes->getBaseType("webforms", "form");
			if($sMode == 'do') {
				if(!isset($_REQUEST['data']['name']) || !strlen($_REQUEST['data']['name']))
					$this->chooseRedirect($this->pre_lang . "/admin/webforms/forms/");
				$iTypeId     = $oTypes->addType($iBaseTID, $_REQUEST['data']['name']);
				$this->form_address_add($iTypeId);
				$this->chooseRedirect($this->pre_lang . "/admin/webforms/form_edit/" . $iTypeId . "/");
			}
			$this->setDataType("form");
			$this->setActionType("create");
			$data = $this->prepareData($iBaseTID, "type");
			$this->setData($data);
			return $this->doData();
		}

		public function form_edit() {
			$iTypeId = (int) getRequest('param0');
			$sMode   = (string) getRequest('param1');
			$oModuleData = cmsController::getInstance()->getModule('data');
			if (!$oModuleData) {
				throw new publicAdminException('Service unavailable');
			}
			if ($sMode == 'do') $this->form_address_add($iTypeId);

			$hierarchyType = umiHierarchyTypesCollection::getInstance()->getTypeByName('webforms', 'form');
			if ($hierarchyType instanceof umiHierarchyType) {
				$_REQUEST['data']['hierarchy_type_id'] = $hierarchyType->getId();
			}

			$_SESSION['referer'] = "/admin/webforms/forms/";
			$this->setDataType("form");
			$this->setActionType("modify");
			return $oModuleData->type_edit();
		}

		public function form_delete() {
			$type_id = (int) getRequest('param0');
			umiObjectTypesCollection::getInstance()->delType($type_id);
			$this->chooseRedirect();
		}

		public function form_address_add($iFormId) {
			$aData = getRequest('data');
			$aObjColl = umiObjectsCollection::getInstance();
			$this->form_address_del($iFormId);
			if (isset($aData['address']) && $aData['address'] != "") {
				$oAddress = $aObjColl->getObject($aData['address']);
				$sFormsId = (string) $oAddress->getValue('form_id');
				$sFormsIdOld = $sFormsId;
				if (!strlen($sFormsId)) $sFormsId = $iFormId;
				else {
					$aFormsId = explode(',', $sFormsId);
					if (!in_array($iFormId, $aFormsId)) {
						$aFormsId[] = $iFormId;
						$sFormsId = implode(',', $aFormsId);
					}
				}
				if ($sFormsId != $sFormsIdOld) {
					$oAddress->setValue('form_id', $sFormsId);
					$oAddress->commit();
				}
			}
		}

		public function form_address_del($iFormId) {
			$sel = new selector('objects');
			$sel->types('object-type')->name('webforms', 'address');
			$sel->where('form_id')->like('%' . $iFormId . '%');
			foreach ($sel->result() as $oAddress) {
				$aFormsId = explode(',', $oAddress->getValue('form_id'));
				if (in_array($iFormId, $aFormsId)) {
					$aFormsId = array_diff($aFormsId, array($iFormId));
					$oAddress->setValue('form_id', implode(',', $aFormsId));
					$oAddress->commit();
				}
			}
		}

		public function type_group_add() {
			$oModuleData = cmsController::getInstance()->getModule('data');
			if(!$oModuleData) {
				throw new publicAdminException('Service unavailable');
			}
			$formId = (int) getRequest('param0');
			$_SESSION['referer'] = "/admin/webforms/form_edit/".$formId."/";
			cmsController::getInstance()->calculateRefererUri();
			return $oModuleData->type_group_add('/admin/webforms/form_group_edit/');
		}

		public function type_group_edit() {
			$oModuleData = cmsController::getInstance()->getModule('data');
			if(!$oModuleData) {
				throw new publicAdminException('Service unavailable');
			}
			$formId = (int) getRequest('param1');
			$_SESSION['referer'] = "/admin/webforms/form_edit/".$formId."/";
			cmsController::getInstance()->calculateRefererUri();
			return $oModuleData->type_group_edit();
		}

		public function type_field_add() {
			$oModuleData = cmsController::getInstance()->getModule('data');
			if(!$oModuleData) {
				throw new publicAdminException('Service unavailable');
			}
			$formId = (int) getRequest('param1');
			$_SESSION['referer'] = "/admin/webforms/form_edit/".$formId."/";
			cmsController::getInstance()->calculateRefererUri();
			return $oModuleData->type_field_add('/admin/webforms/form_field_edit/');
		}

		public function type_field_edit() {
			$oModuleData = cmsController::getInstance()->getModule('data');
			if(!$oModuleData) {
				throw new publicAdminException('Service unavailable');
			}
			$formId = (int) getRequest('param1');
			$_SESSION['referer'] = "/admin/webforms/form_edit/".$formId."/";
			cmsController::getInstance()->calculateRefererUri();
			return $oModuleData->type_field_edit();
		}
		public function json_move_field_after() {
			$oModuleData = cmsController::getInstance()->getModule('data');
			if(!$oModuleData) {
				throw new publicAdminException('Service unavailable');
			}
			return $oModuleData->json_move_field_after();
		}
		public function json_move_group_after() {
			$oModuleData = cmsController::getInstance()->getModule('data');
			if(!$oModuleData) {
				throw new publicAdminException('Service unavailable');
			}
			return $oModuleData->json_move_group_after();
		}
		public function json_delete_field() {
			$oModuleData = cmsController::getInstance()->getModule('data');
			if(!$oModuleData) {
				throw new publicAdminException('Service unavailable');
			}
			return $oModuleData->json_delete_field();
		}
		public function json_delete_group() {
			$oModuleData = cmsController::getInstance()->getModule('data');
			if(!$oModuleData) {
				throw new publicAdminException('Service unavailable');
			}
			return $oModuleData->json_delete_group();
		}

		public function getMessageFilter() {
			$aFilter  = array('nodes:forms'=>array(array('attribute:id'=>$this->iFormFilter,'nodes:item'=>array())),
							  'nodes:addresses'=>array(array('attribute:id'=>$this->iAddressFilter,'nodes:item'=>array())));
			$oTypes   = umiObjectTypesCollection::getInstance();
			$iBaseTID = $oTypes->getBaseType("webforms", "form");
			$aFTypes  = $oTypes->getSubTypesList($iBaseTID);
			$aForms   = &$aFilter['nodes:forms'][0]['nodes:item'];
			$aForms[] = array('attribute:id'=>0,
							  'node:name'=>getLabel('label-all'));
			foreach($aFTypes as $iTypeId) {
				$oForm = $oTypes->getType($iTypeId);
				$aForms[] = array('attribute:id'=>$iTypeId,
								  'node:name'=>$oForm->getName());
			}
			$oObjects   = umiObjectsCollection::getInstance();
			$aAddresses = $oObjects->getGuidedItems( $oTypes->getBaseType('webforms', 'address') );
			$aAddrList  = &$aFilter['nodes:addresses'][0]['nodes:item'];
			$aAddrList[] = array('attribute:id'=>0,
								 'node:name'=>getLabel('label-all'));
			foreach($aAddresses as $ID=>$sAddr)
				$aAddrList[] = array('attribute:id'=>$ID,
									'node:name'=>$sAddr);
			return array($aFilter);
		}

		public function placeOnPage() {
			$formId = getRequest('param0');
			if(!$formId) $this->chooseRedirect(getServer('HTTP_REFERER'));
			$form      = umiObjectTypesCollection::getInstance()->getType($formId);
			$typeid    = umiHierarchyTypesCollection::getInstance()->getTypeByName('webforms', 'page')->getId();
			$formName  = $form->getName();
			$hierarchy = umiHierarchy::getInstance();
			$pageId    = $hierarchy->addElement(0, $typeid, $formName, $formName);
			permissionsCollection::getInstance()->setDefaultPermissions($pageId);
			$page      = $hierarchy->getElement($pageId);
			$page->setIsActive(true);
			$page->setValue('form_id', $formId);
			$page->setValue('title', $formName);
			$page->setValue('h1', $formName);
			$page->setValue('content', '%webforms add('.$formId.')%');
			$page->commit();
			$this->chooseRedirect('/admin/content/edit/'.$pageId.'/');
		}

		public function getPages(){
			$pages = getRequest('id');
			$this->setDataType("list");
			$this->setActionType("view");
			$data  = array();
			if($pages && !empty($pages))
			foreach($pages as $id) {
				$p = $this->getBindedPage($id);
				$data['nodes:page'][] = array_merge($p['page'], array('attribute:form'=>$id));
			}
			$this->setData($data);
			return $this->doData();
		}

		public function del() {
			$objects = getRequest('element');
			if(!is_array($objects) && $objects) $objects = array($objects);
			if(is_array($objects) && !empty($objects)) {
				$collection = umiObjectsCollection::getInstance();
				foreach($objects as $objectId) {
					$collection->delObject($objectId);
				}
			}
		}

		public function delType() {
			$objects = getRequest('element');
			if(!is_array($objects) && $objects) $objects = array($objects);
			if(is_array($objects) && !empty($objects)) {
				$collection = umiObjectTypesCollection::getInstance();
				foreach($objects as $objectId) {
					$collection->delType($objectId);
				}
			}
		}

		public function getDatasetConfiguration($param = '') {
			$objectTypes = umiObjectTypesCollection::getInstance();
			switch($param) {
				case 'templates':
					$loadMethod = 'templates';
					$delMethod  = 'del';
					$typeId		= $objectTypes->getBaseType('webforms', 'template');
					$defaults	= '';
					break;
				case 'messages' :
					$loadMethod = 'messages';
					$delMethod  = 'del';
					$typeId		= (getRequest('type_id')) ? getRequest('type_id') : $objectTypes->getBaseType('webforms', 'form');
					$defaults	= 'destination_address[150px]|sending_time[100px]';
					break;
				case 'forms' :
					$loadMethod = 'forms';
					$delMethod  = 'delType';
					$typeId		= $objectTypes->getBaseType('webforms', 'form');
					$defaults	= 'page[250px,static]';
					break;
				default:
					$loadMethod = 'addresses';
					$delMethod  = 'del';
					$typeId		= $objectTypes->getBaseType('webforms', 'address');
					$defaults	= 'address_description[200px]';
			}
			return array(
					'methods' => array(
						array('title'=>getLabel('smc-load'), 'forload'=>true, 'module'=>'webforms', '#__name'=>$loadMethod),
						array('title'=>getLabel('smc-delete'), 				  'module'=>'webforms', '#__name'=>$delMethod, 'aliases' => 'tree_delete_element,delete,del')),
					'types' => array(
						array('common' => 'true', 'id' => $typeId)
					),
					'stoplist' => array('form_id', 'rate_voters', 'rate_sum', 'destination_address', 'from_email_template', 'from_template', 'subject_template', 'master_template', 'autoreply_from_email_template', 'autoreply_from_template', 'autoreply_subject_template', 'autoreply_template'),
					'default' => $defaults
			);
		}

		public function excludeNestedTypes($arr) {
			$objectTypes = umiObjectTypesCollection::getInstance();

			$result = Array();
			foreach($arr as $typeId) {
				$type = $objectTypes->getType($typeId);
				if($type instanceof umiObjectType) {
					if(in_array($type->getParentId(), $arr)) {
						continue;
					} else {
						$result[] = $typeId;
					}
				}
			}
			return $result;
		}
	};
?>