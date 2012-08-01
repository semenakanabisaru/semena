<?php

class webforms extends def_module {

	public function __construct() {
				parent::__construct();

		if(cmsController::getInstance()->getCurrentMode() == "admin") {
			$this->__loadLib("__admin.php");
			$this->__implement("__webforms");
			// Creating tabs
			$commonTabs = $this->getCommonTabs();
			if($commonTabs) {
				$commonTabs->add("addresses", array("address_edit", "address_add"));
				$commonTabs->add("forms", array("form_edit", "form_add"));
				$commonTabs->add("templates", array("template_edit", "template_add"));
				$commonTabs->add("messages", array("message"));
			}
		} else {
			$this->__loadLib("__custom.php");
			$this->__implement("__custom_webforms");
		}
		$regedit = regedit::getInstance();
		if(!$regedit->getVal('//modules/webforms/imported')) {
			if(!defined('DB_DRIVER') || DB_DRIVER != 'xml') {
				$oCollection = umiObjectsCollection::getInstance();
				$iTypeId     = umiObjectTypesCollection::getInstance()->getBaseType('webforms', 'address');

				$sSQL    = 'SELECT * FROM cms_webforms';
				$rResult = l_mysql_query($sSQL);
				while($aRow = mysql_fetch_assoc($rResult)) {
					$iId     = $oCollection->addObject($aRow['id'], $iTypeId);
					$oObject = $oCollection->getObject($iId);
					$oObject->setValue('address_description', $aRow['descr']);
					$oObject->setValue('address_list', $aRow['email']);
					$oObject->setValue('insert_id', $aRow['id']);
					$oObject->commit();
				}
				l_mysql_query('TRUNCATE TABLE cms_webforms');
			}
			$regedit->setVal('//modules/webforms/imported', 1);
		}
	}
	public function getEditLink($element_id, $element_type) {
		return array(false, $this->pre_lang . '/admin/content/edit/'.$element_id.'/');
	}
	public function page() {
		$cmsControllerInstance = cmsController::getInstance();
		$pageId = $cmsControllerInstance->getCurrentElementId();
		if($contentModule = $cmsControllerInstance->getModule('content'))
			return $contentModule->content($pageId);
		return false;
	}
	public function getBindedPage($formId = false) {
		if($formId === false) {
			$formId = ($tmp = getRequest('param0')) ? $tmp : $formId;
		}
		if(!$formId) return array('page'=>array('attribute:id'=>0));
		$hType  = umiHierarchyTypesCollection::getInstance()->getTypeByName('webforms', 'page');
		$typeId = umiObjectTypesCollection::getInstance()->getTypeByHierarchyTypeId($hType->getId());
		$type   = umiObjectTypesCollection::getInstance()->getType($typeId);
		$sel = new umiSelection();
		$sel->addElementType($hType->getId());
		$sel->addActiveFilter(true);
		$sel->addPropertyFilterEqual( $type->getFieldId('form_id'), $formId );
		$result = umiSelectionsParser::runSelection($sel);
		if(!empty($result))
			return array('page'=>array('attribute:id'=>$result[0], 'attribute:href'=>umiHierarchy::getInstance()->getPathById($result[0])));
		$sel = new umiSelection();
		$sel->addElementType($hType->getId());
		$sel->addActiveFilter(false);
		$sel->addPropertyFilterEqual( $type->getFieldId('form_id'), $formId );
		$result = umiSelectionsParser::runSelection($sel);
		if(!empty($result))
			return array('page'=>array('attribute:id'=>$result[0], 'attribute:href'=>umiHierarchy::getInstance()->getPathById($result[0])));
		return array('page'=>array('attribute:id'=>0));
	}
	private function getPropertyValue(umiObject $obj, $propName) {
		if($prop = $obj->getPropByName($propName)) {
			switch($prop->getDataType()) {
				case 'date' : {
					if(($date = $prop->getValue()) instanceof umiDate)
						return $date->getFormattedDate();
					return '';
				}
				case 'relation': {
					$result = array();
					$ids    = $prop->getValue();
					if(!is_array($ids)) $ids = array($ids);
					foreach($ids as $id) {
						if($value = umiObjectsCollection::getInstance()->getObject($id)) {
							$result[] = $value->getName();
						}
					}
					return empty($result) ? '' : implode(', ', $result);
				}
				case 'boolean': {
					$langs = cmsController::getInstance()->langs;
					$value = $prop->getValue();
					return $value ? $langs['boolean_true'] : $langs['boolean_false'] ;
				}
				default: return $prop->getValue();
			}
		}
		return '';
	}
	public function formatMessage($_iMessageId, $_bProcessAll = false) {
		$oObjects    = umiObjectsCollection::getInstance();
		$iTplTypeId  = umiObjectTypesCollection::getInstance()->getBaseType('webforms', 'template');
		$sMsgBody    = array('from_email_template' => '', 'from_template'=>'', 'subject_template'=>'', 'master_template'=>'', 'autoreply_from_email_template'=>'', 'autoreply_from_template'=>'', 'autoreply_subject_template'=>'', 'autoreply_template'=>'');
		$oMessage    = $oObjects->getObject($_iMessageId);
		$iFormTypeId = $oMessage->getTypeId();
		$aFields     = umiObjectTypesCollection::getInstance()->getType($iFormTypeId)->getAllFields(true);
		$oTplType    = umiObjectTypesCollection::getInstance()->getType($iTplTypeId);
		//------------------------------------------------------------------------------
		$oSelection  = new umiSelection;
		$oSelection->addObjectType( $iTplTypeId );
		$oSelection->addPropertyFilterEqual($oTplType->getFieldId('form_id'), $iFormTypeId);
		$oSelection->setPropertyFilter();
		$aTemplates  = umiSelectionsParser::runSelection($oSelection);
		$oTemplate   = empty($aTemplates) ? false : $oObjects->getObject( $aTemplates[0] );
		//------------------------------------------------------------------------------
		if(!$oTemplate) {
			$sTmp = '';
			foreach($aFields as $oField) {
				$sTmp .= $this->getPropertyValue($oMessage, $oField->getName()) . "<br />\n";
			}
			if($_bProcessAll) $sMsgBody['master_template'] = $sTmp;
			else              $sMsgBody = $sTmp;
		} else {
			// All-templates processing
			if($_bProcessAll) {
				$sMsgBody = array();
				$aFields  = umiObjectTypesCollection::getInstance()->getType( $oTemplate->getTypeId() )->getAllFields();
				foreach($aFields as $oField) {
					$aMarks     = array();
					$sFieldName = $oField->getName();
					$sTemplate  = str_replace(array("&#037;", "&#37;"), "%", $oTemplate->getValue($sFieldName));
					preg_match_all("/%[A-z0-9_]+%/", $sTemplate, $aMarks);
					foreach($aMarks[0] as $sMark)
						$sTemplate = str_replace($sMark, $this->getPropertyValue($oMessage, trim($sMark, '% ')), $sTemplate);
					$sMsgBody[$sFieldName] = $sTemplate;
				}
			} else {
			// Only sended message body
				$sTemplate = str_replace("&#037;", "%", $oTemplate->getValue('master_template'));
				preg_match_all("/%[A-z0-9_]+%/", $sTemplate, $aMarks);
				foreach($aMarks[0] as $sMark)
					$sTemplate = str_replace($sMark, $this->getPropertyValue($oMessage, trim($sMark, '% ')), $sTemplate);
				$sMsgBody = $sTemplate;
			}
		}
		return $sMsgBody;
	}

	public function guessAddressId($_sAddress) {
		if(is_numeric($_sAddress)) return $_sAddress;
		$_sFind     = str_replace( array(' ', ','), array('%', '%'), $_sAddress );
		$oTypes     = umiObjectTypesCollection::getInstance();
		$iTypeId    = $oTypes->getBaseType('webforms', 'address');
		$oSelection = new umiSelection;
		$oSelection->addObjectType( $iTypeId );
		$oSelection->addPropertyFilterLike( $oTypes->getType($iTypeId)->getFieldId('address_list') , $_sFind);
		$aOIDs      = umiSelectionsParser::runSelection($oSelection);
		return (!empty($aOIDs)) ? $aOIDs[0] : $_sAddress;
	}

	public function checkAddressExistence($_sAddress) {
		$count = 0;
		
		if($_sAddress) {
		$find 		= '%' . $_sAddress . '%';
		$oTypes     = umiObjectTypesCollection::getInstance();
		$iTypeId    = $oTypes->getBaseType('webforms', 'address');
		$oSelection = new umiSelection;
		$oSelection->addObjectType( $iTypeId );
		$oSelection->addPropertyFilterLike( $oTypes->getType($iTypeId)->getFieldId('address_list') , $find);
		$count      = umiSelectionsParser::runSelectionCounts($oSelection);
		}
		
		if(!$count) {
			$this->errorNewMessage("%unknown_address%");
			$this->errorPanic();
		}
	}

	public function guessAddressValue($_iID) {
		if(is_numeric($_iID)) {
			$oObjects = umiObjectsCollection::getInstance();
			if(!$oObjects->isExists($_iID)) return $_iID;
			return $oObjects->getObject($_iID)->getValue('address_list');
		} else {
			$this->checkAddressExistence($_iID);
			return $_iID;
		}
	}

	public function guessAddressName($_iID) {
		if(is_numeric($_iID)) {
			$oObjects = umiObjectsCollection::getInstance();
			if(!$oObjects->isExists($_iID)) return false;
			return $oObjects->getObject($_iID)->getName();
		} else {
			return $_iID;
		}
	}

	public function checkRequiredFields($typeId) {
		
		$type = umiObjectTypesCollection::getInstance()->getType($typeId);
		if (!$type instanceof umiObjectType) throw new coreException(getLabel('label-cannot-detect-type'));
		
		$allFields = $type->getAllFields();
		
		$inputData = getRequest('data');
		if((!$inputData || !@is_array($inputData['new'])) && (!isset($_FILES['data']['name']['new'])) ) {
			$inputData = array();
		} else {
			$tmp = array();
			if(@is_array($inputData['new']))
				$tmp = array_merge($tmp, $inputData['new']);
			if(isset($_FILES['data']['name']['new']) && is_array($_FILES['data']['name']['new']))
				$tmp = array_merge($tmp, $_FILES['data']['name']['new']);
			$inputData = $tmp;
		}
		
		$errorFields = array();
		foreach($allFields as $field) {
			if($field->getIsRequired()) {
				$fieldName = $field->getName();
				if(!isset($inputData[$fieldName]) || !strlen($inputData[$fieldName])) {
					$errorFields[] = $field->getId();
				}
			}
		}
		return !empty($errorFields) ? $errorFields : true;
	}

	public function assembleErrorFields($errorFields) {
		$result     = array();
		$collection = umiFieldsCollection::getInstance();
		foreach($errorFields as $fieldId){
			$field = $collection->getField($fieldId);
			$result[] = /*'error_field['.$field->getName().']='.*/$field->getTitle();
		}
		//return !empty($result) ? implode('&', $result) : '';
		return !empty($result) ? implode(', ', $result) : '';
	}

	private function writeAddressSelect($template, $iFormId = false) {
		list($block, $line) = def_module::loadTemplates("data/reflection/".$template, "address_select_block", 'address_select_block_line');
		$oObjects   = umiObjectsCollection::getInstance();
		$oSelection = new umiSelection;
		$oSelection->addObjectType( umiObjectTypesCollection::getInstance()->getBaseType('webforms', 'address') );
		$aOIDs      = umiSelectionsParser::runSelection($oSelection);
		$aItems		= array();
		foreach($aOIDs as $iID) {
			$oObject = $oObjects->getObject($iID);
			$sTitle  = $oObject->getValue('address_description');
			$aParam  = array();
			$aParam['attribute:id'] = $iID;
			$aParam['node:text']	= $sTitle;
			if ($iFormId && $sFormsId = $oObject->getValue('form_id')) {
				if (in_array($iFormId, explode(',', $sFormsId))) {
					$aParam['attribute:selected'] = 'selected';
				}
			}
			$aItems[] = self::parseTemplate($line, $aParam);
		}
		$aBlockParam = array();
		$aBlockParam['void:options'] = $aBlockParam['subnodes:items'] = $aItems;
		return self::parseTemplate($block, $aBlockParam);
	}

	private function writeSeparateAddresses($addressIds, $template) {
		list($block, $line) = def_module::loadTemplates("data/reflection/".$template, "address_separate_block", 'address_separate_block_line');
		$collection = umiObjectsCollection::getInstance();
		$aItems = array();
		foreach($addressIds as $id) {
			if($address = $collection->getObject($id)) {
				$aLine = array();
				$aLine['id']          = 'address_'.$id;
				$aLine['name']		  = 'system_email_to[]';
				$aLine['value']		  = $id;
				$aLine['description'] = $address->getValue('address_description');
				$aItems[] = self::parseTemplate($line, $aLine);
			}
		}
		$aBlockParam = array();
		$aBlockParam['void:lines'] = $aBlockParam['subnodes:items'] = $aItems;
		return self::parseTemplate($block, $aBlockParam);
	}

	public function add($form_id = false, $who = "", $template="webforms") {
		$aParam		 = array();
		$sForm       = '';
		$oTypes      = umiObjectTypesCollection::getInstance();
		$iBaseTypeId = $oTypes->getBaseType("webforms", "form");
		if(is_numeric($form_id)) {
			if(!$oTypes->isExists($form_id) || $oTypes->getParentClassId($form_id) != $iBaseTypeId) {
				$form_id = false;
			}
		}
		if(!is_numeric($form_id) || $form_id === false) {
			$aChilds = $oTypes->getChildClasses($iBaseTypeId);
			if(empty($aChilds)) {
				list($template) = def_module::loadTemplates("data/reflection/".$template, "error_no_form");
				return $template;
			} else {
				$i = 0;
				do{ $oForm = $oTypes->getType($aChilds[$i]); $i++; } while($i<count($aChilds) && $oForm->getName() != $form_id);
				$form_id = $oForm->getId();
			}
		}
		$aParam['attribute:form_id']  = $form_id;
		$aParam['attribute:template'] = $template;
		if(!strlen($who)) {
			$mSelect = $this->writeAddressSelect($template, $form_id);
			if(is_array($mSelect)) {
				if(isset($mSelect['items'])) $aParam['items'] =  $mSelect['items'];
				else return;
			} else {
				if(strlen($mSelect)) $aParam['address_select'] = $mSelect;
				else return;
			}
		} else {
			$addressId = $this->guessAddressId($who);
			if(func_num_args() > 3) {
				$addresses = array_slice(func_get_args(), 3);
				foreach($addresses as &$addr) $addr = $this->guessAddressId($addr);
				$addresses = array_merge(array($addressId), $addresses);
				$aParam['res_to'] = $aParam['address_select'] = $this->writeSeparateAddresses($addresses, $template);
			} else {
				$aParam['res_to'] = $aParam['address_select'] = '<input type="hidden" name="system_email_to" value="'.$addressId.'" />';
			}
		}
		$aParam['groups'] = cmsController::getInstance()->getModule('data')->getCreateForm($form_id, $template);
		list($sBlock) = def_module::loadTemplates("data/reflection/".$template, "form_block");
		return self::parseTemplate($sBlock, $aParam);
	}

	public function getAddresses($iFormId = false) {
		$sel = new selector('objects');
		$sel->types('object-type')->name('webforms', 'address');
		$result = $sel->result();
		$aBlock = Array();
		$aLines = Array();
		foreach ($result as $oObject) {
			$aLine = Array();
			$aLine['attribute:id'] = $oObject->getId();
			if (in_array($iFormId, explode(',', $oObject->getValue('form_id')))) {
				$aLine['attribute:selected'] = 'selected';
			}
			$aLine['node:text'] = $oObject->getName();
			$aLines[] = self::parseTemplate('', $aLine);
		}
		$aBlock['attribute:input_name'] = "data[address]";
		$aBlock['subnodes:items']       = $aLines;
		return self::parseTemplate('', $aBlock);
	}

	public function send() {
		// Check captcha to know we should do anything
		if (isset($_REQUEST['captcha'])) {
			$_SESSION['user_captcha'] = md5((int) $_REQUEST['captcha']);
		}
		if (!umiCaptcha::checkCaptcha()) {
			$this->errorNewMessage("%errors_wrong_captcha%");
			$this->errorPanic();
		}
		//-------------------------------------------------------------------
		// Get necessary data
		$sMsgBody    = '';
		$oTypes      = umiObjectTypesCollection::getInstance();
		$iBaseTypeId = $oTypes->getBaseType("webforms", "form");
		$iFormTypeId = getRequest('system_form_id');
		$sSenderIP   = getServer('REMOTE_ADDR');
		$iTime       = new umiDate( time() );
		$aAddresses  = getRequest('system_email_to');
		if(!is_array($aAddresses)) $aAddresses = array($aAddresses);
		$aRecipients = array();
		foreach($aAddresses as $address){
			$sEmailTo = $this->guessAddressValue($address);
			$sAddress = $this->guessAddressName($address);
			$aRecipients[] = array('email'=>$sEmailTo, 'name'=>$sAddress);
		}

		if(!$oTypes->isExists($iFormTypeId) || $oTypes->getParentClassId($iFormTypeId) != $iBaseTypeId) {
			$this->errorNewMessage("%wrong_form_type%");
			$this->errorPanic();
		}//
		if(($ef = $this->checkRequiredFields($iFormTypeId)) !== true) {
			$this->errorNewMessage(getLabel('error-required_list').$this->assembleErrorFields($ef));
		}
		//-------------------------------------------------------------------
		// Saving message and preparing it for sending
		$_REQUEST['data']['new']['sender_ip'] = $sSenderIP;  // Hack for saving files-only-forms
		$oObjectsCollection = umiObjectsCollection::getInstance();
		$iObjectId          = $oObjectsCollection->addObject($sAddress, $iFormTypeId);
		cmsController::getInstance()->getModule('data')->saveEditedObject($iObjectId, true);
		$oObject            = $oObjectsCollection->getObject($iObjectId);
		$oObject->setValue('destination_address', $sEmailTo);
		$oObject->setValue('sender_ip', $sSenderIP);
		$oObject->setValue('sending_time', $iTime);
		$aMessage           = $this->formatMessage($iObjectId, true);
		//--------------------------------------------------------------------
		// Make an e-mail
		$oMail = new umiMail();
		//--------------------------------------------------------------------
		// Determine file fields
		$aFTypes     = array('file', 'img_file', 'swf_file');
		$aFields     = $oTypes->getType($oObject->getTypeId())->getAllFields();
		$aFileFields = array();
		foreach($aFields as $oField) {
			$oType   = $oField->getFieldType();
			if(in_array($oType->getDataType(), $aFTypes)) {
				$oFile = $oObject->getValue($oField->getName());

				if($oFile instanceof umiFile) {
					$oMail->attachFile($oFile);
				} /*else {
					$this->errorNewMessage("%errors_wrong_file_type%");
					$this->errorPanic();
				}*/

			}
		}
		$recpCount = 0;
		foreach($aRecipients as $recipient) {
			foreach(explode(',', $recipient['email']) as $sAddress) {
				if(strlen(trim($sAddress))) {
					$oMail->addRecipient( trim($sAddress), $recipient['name'] );
					$recpCount++;
				}
			}
		}
		if(!$recpCount) {
			$this->errorNewMessage(getLabel('error-no_recipients'));
		}
		$oMail->setFrom($aMessage['from_email_template'], $aMessage['from_template']);
		$oMail->setSubject($aMessage['subject_template']);
		$oMail->setContent($aMessage['master_template']);
		$oMail->commit();
		$oMail->send();
		//--------------------------------------------------------------------
		// Send autoreply if should
		if(strlen($aMessage['autoreply_template'])) {
			$oMailReply = new umiMail();
			$oMailReply->addRecipient( $aMessage['from_email_template'], $aMessage['from_template'] );
			$oMailReply->setFrom($aMessage['autoreply_from_email_template'], $aMessage['autoreply_from_template']);
			$oMailReply->setSubject($aMessage['autoreply_subject_template']);
			$oMailReply->setContent($aMessage['autoreply_template']);
			$oMailReply->commit();
			$oMailReply->send();
		}
		//--------------------------------------------------------------------
		// Process events
		$oEventPoint = new umiEventPoint("webforms_post");
		$oEventPoint->setMode("after");
		$oEventPoint->setParam("email", $aMessage['from_email_template']);
		$oEventPoint->setParam("message_id", $iObjectId);
		$oEventPoint->setParam("fio", $aMessage['from_template']);
		$this->setEventPoint($oEventPoint);
		//--------------------------------------------------------------------
		// Redirecting
		$sRedirect = getRequest('ref_onsuccess');
		if($sRedirect) $this->redirect($sRedirect);
		//--------------------------------------------------------------------
		// Or showing the message
		$sTemplateName = getRequest('system_template');
		if($sTemplateName) {
			list($sSuccessString) = def_module::loadTemplates("data/reflection/".$sTemplateName, "send_successed");
			if(strlen($sSuccessString)) return $sSuccessString;
		}
		//--------------------------------------------------------------------
		// If we're still here
		if(isset($_SERVER['HTTP_REFERER'])) $this->redirect($_SERVER['HTTP_REFERER']);
		return '';
	}

//------------------------------------------------------------------------------------------------------
/**
* @desc Deprecated methods. Leave for backward compatibility. Not for a long time
*/
	public function insert($who = "", $template = "default") {
		$who = trim($who);

		if(!$template) $template = "default";
		list($template_block, $template_to_block, $template_to_line) = def_module::loadTemplates("webforms/".$template, "webforms_block", "webforms_to_block", "webforms_to_line");

		if(!defined("DB_DRIVER") || DB_DRIVER != "xml") {
			$sql = "SELECT `id`, `descr` FROM cms_webforms";
			$result = l_mysql_query($sql);

			$lines = "";
			$items = Array();
			while($row = mysql_fetch_assoc($result)) {
				$from = Array("%text%", "%id%");
				$to =   Array($row['descr'], $row['id']);

				$lines .= str_replace($from, $to, $template_to_line);

				$block = Array();
				$block['attribute:id'] = $row['id'];
				$block['node:descr'] = $row['descr'];
				$items[] = $block;
			}
		}
		$oObjects   = umiObjectsCollection::getInstance();
		$oSelection = new umiSelection;
		$oSelection->addObjectType( umiObjectTypesCollection::getInstance()->getBaseType('webforms', 'address') );
		$aOIDs      = umiSelectionsParser::runSelection($oSelection);
		foreach($aOIDs as $iID) {
			$sTitle  = $oObjects->getObject($iID)->getValue('address_description');
			$from = Array("%text%", "%id%");
			$to =   Array($sTitle, $iID);


			$lines .= str_replace($from, $to, $template_to_line);


			$block = Array();
			$block['attribute:id'] = $iID;
			$block['node:descr'] = $sTitle;
			$items[] = $block;
		}

		$res_to = str_replace("%lines%", $lines, $template_to_block);

		if($who) {
			if(is_numeric($who)) {
				$res_to = "<input type='hidden' name='email_to' value='" . $who . "' />";
			} else {
				$iAddrID = $this->guessAddressId($who);
				if(strval($iAddrID) != $who) {
					$res_to = "<input type='hidden' name='email_to' value='" . $iAddrID . "' />";
				} else {
					$sql = "SELECT id FROM cms_webforms WHERE email='$who'";
					$result = l_mysql_query($sql);
					if($row = mysql_fetch_assoc($result)) {
						$res_to = "<input type='hidden' name='email_to' value='" . $row['id'] . "' />";
					} else {
						$res_to = "<input type='hidden' name='email_to' value='" . $who . "' />";
					}
				}
			}
		}


		$block_arr = Array();
		$block_arr['to_block'] = $res_to;
		$block_arr['template'] = $template;
		$block_arr['subnodes:items'] = $items;
		return self::parseTemplate($template_block, $block_arr);
	}

	public function post() {
		global $_FILES;

		$iOldErrorReportingLevel = error_reporting(~E_ALL & ~E_STRICT);

		$res = "";

		$email_to = getRequest('email_to');
		$message  = getRequest('message');
		$data     = getRequest('data');

		$domain   = getRequest('domain');

		$subject = cmsController::getInstance()->getCurrentDomain()->getHost();


		$referer_url = $_SERVER['HTTP_REFERER'];
		$this->errorRegisterFailPage($referer_url);

		// check captcha
		if (isset($_REQUEST['captcha'])) {
			$_SESSION['user_captcha'] = md5((int) $_REQUEST['captcha']);
		}

		if (!umiCaptcha::checkCaptcha()) {
			$this->errorNewMessage("%errors_wrong_captcha%");
			$this->errorPanic();
		}

		$sRecipientName = "administrator";
		if(is_numeric($email_to)) {
			$to = $this->guessAddressValue($email_to);
			if(intval($to) != $email_to) {
				$sRecipientName = $this->guessAddressName($email_to);
			} else {
				$oTCollection = umiObjectTypesCollection::getInstance();
				$iTypeId      = $oTCollection->getBaseType('webforms', 'address');
				$oType        = $oTCollection->getType($iTypeId);
				$iFieldId     = $oType->getFieldId('insert_id');

				$oSelection = new umiSelection();
				$oSelection->addObjectType($iTypeId);
				$oSelection->addPropertyFilterEqual($iFieldId, $email_to);
				$aIDs = umiSelectionsParser::runSelection($oSelection);
				if(count($aIDs)) {
					$oObject 		= umiObjectsCollection::getInstance()->getObject($aIDs[0]);
					$to      		= $oObject->getValue('address_list');
					$sRecipientName	= $oObject->getValue('address_description');
				} else {
					if(!defined("DB_DRIVER") || DB_DRIVER != "xml") {
						$sql = "SELECT email, descr FROM cms_webforms WHERE id=$email_to";
						$result = l_mysql_query($sql);
						list($to, $sRecipientName) = mysql_fetch_row($result);
					} else {
						$this->redirect($this->pre_lang . "/webforms/posted/?template=error_no_recipient");
					}
				}
			}
		} else {
			$this->checkAddressExistence($email_to);
			$to = $email_to;
		}

		if(!$data['email_from'] && isset($data['email'])) {
			$data['email_from'] = $data['email'];
		}


		$someMail = new umiMail();
		$arrMails = explode(",", $to);
		$arrMails = array_map("trim", $arrMails);
		foreach ($arrMails as $sEmail) {
			$someMail->addRecipient($sEmail, $sRecipientName);
		}

		$from = $data['fname']." ".$data['lname'];
		$someMail->setFrom($data['email_from'], $from);

		$mess = "";

		if(is_array($data)) {
			if(isset($data['subject']))
				$subject = $data['subject'];

			if(isset($data['fio']))
				$from = $data['fio'];

			if($data['fname'] || $data['lname'] || $data['mname'])
				$from = $data['lname'] . " " . $data['fname'] . " " . $data['mname'];

			if($data['fio_frm'])
				$from = $data['fio_frm'];

			if($email_from = $data['email_from']) {
				$email_from = $data['email_from'];
			}


			$mess = <<<END

<table border="0" width="100%">

END;

			if(is_array($_FILES['data']['name'])) {
				$data = array_merge($data, $_FILES['data']['name']);
			}

			$max_size = getBytesFromString(mainConfiguration::getInstance()->get('system', 'quota-files-and-images'));
			$files_size = getDirSize(CURRENT_WORKING_DIR.'/files/');
			$images_size = getDirSize(CURRENT_WORKING_DIR.'/images/');

			$uploadDir = CURRENT_WORKING_DIR . "/sys-temp/uploads";
			
			foreach($data as $field => $cont) {
				if($filename = $_FILES['data']['name'][$field]) {
					if(!is_dir($uploadDir)) mkdir($uploadDir);
					clearstatcache();
					$uploadDir_size = getDirSize($uploadDir);
					$summ_size = ($files_size+$images_size+$uploadDir_size);
					if ( $max_size==0 || ($summ_size+$_FILES['data']['size'][$field])<=$max_size ) {
					$file = umiFile::upload('data', $field, $uploadDir);
						if (!$file) {
							$this->errorNewMessage("%errors_wrong_file_type%");
							$this->errorPanic();
						}
					$someMail->attachFile($file);
					} else {
						$cont = def_module::parseTPLMacroses("%not_enough_space_for_load_file%");
				}
					}

				if(!is_array($cont)) {
					$cont = str_replace("%", "&#37;", $cont);
				}

				if(!$cont) $cont = "&mdash;";

				if(is_array($cont)) {
					foreach($cont as $i => $v) {
						$cont[$i] = str_replace("%", "&#37;", $v);
					}

					$cont = implode(", ", $cont);
				}

				$label = ($_REQUEST['labels'][$field]) ? $_REQUEST['labels'][$field] : ("%" . $field . "%");

				$mess .= <<<END

	<tr>
		<td width="30%">
			{$label}:
		</td>

		<td>
			{$cont}
		</td>
	</tr>

END;
			}

			$mess .= <<<END

</table>
<hr />

END;

		}

		if($from) {
			$user_fio_from = $from;
		}

		$message = str_replace("%", "&#37;", $message);
		$mess .= nl2br($message);

		if(!$from) {
			$from = regedit::getInstance()->getVal("//settings/fio_from");
		}

		if(!$from_email) {
				$from_email = regedit::getInstance()->getVal("//settings/email_from");
		}


		$from = $from . "<" . $from_email . ">";

		$someMail->setSubject($subject);
		$someMail->setContent($mess);
		$someMail->commit();
		$someMail->send();


		if($template = (string) $_REQUEST['template']) {    //Sending auto-reply
			list($template_mail, $template_mail_subject) = def_module::loadTemplatesForMail("webforms/".$template, "webforms_reply_mail", "webforms_reply_mail_subject");

			$template_mail = def_module::parseTemplateForMail($template_mail, $arr);
			$template_mail_subject = def_module::parseTemplateForMail($template_mail, $arr);
			$check_param = false;

			if (!is_array($template_mail)) {
				if ((bool) strlen($template_mail)) $check_param = true;
			}

			if ($check_param) {
				$email_from = regedit::getInstance()->getVal("//settings/email_from");
				$fio_from = regedit::getInstance()->getVal("//settings/fio_from");

				$replyMail = new umiMail();
				$replyMail->addRecipient($data['email_from'], $from);
				$replyMail->setFrom($email_from, $fio_from);
				$replyMail->setSubject($template_mail_subject);
				$replyMail->setContent($template_mail);
				$replyMail->commit();
				$replyMail->send();
			}
		}

		$oEventPoint = new umiEventPoint("webforms_post");
		$oEventPoint->setMode("after");
		$oEventPoint->setParam("email", $data['email_from']);
		$oEventPoint->setParam("fio", $user_fio_from);
		$this->setEventPoint($oEventPoint);

		$url = getRequest('ref_onsuccess');
		if (!$url) $url = $this->pre_lang . "/webforms/posted/";

		if ($template) {
			$url .= ((strpos($url, '?') === false ? '?' : '&') . "template=" . $template);
		}
		error_reporting($iOldErrorReportingLevel);
		$this->redirect($url);
	}

	public function posted($template = false) {
		$templater = cmsController::getInstance()->getCurrentTemplater();
		$template = $template ? $template : (string) getRequest('template');
		$template = $template ? $template : (string) getRequest('param0');
		$res = false;
		if ($template) {
			if (is_numeric($template)) {
				$sel = new selector('objects');
				$sel->types('object-type')->name('webforms', 'template');
				$sel->where('form_id')->equals($template);
				$sel->limit(0, 1);
				if ($sel->result) {
					$oTemplate = $sel->result[0];
					$res = $oTemplate->getValue('posted_message');
				}
			}
			if (!$res) {
				try {
					list($template) = $this->loadTemplates("./tpls/webforms/".$template, "posted");
					$res = $template;
				} catch (publicException $e) {}
			}
		}
		$res = ($res) ? $res : "%webforms_thank_you%";
		return $templater->putLangs($res);
	}

	public function getUnbindedForms($currentTemplateId = false) {
		$objectsCollection = umiObjectsCollection::getInstance();
		$typesCollection   = umiObjectTypesCollection::getInstance();
		$baseType  = $typesCollection->getBaseType("webforms", "form");
		$forms	   = $typesCollection->getSubTypesList($baseType);
		$typeId    = $typesCollection->getBaseType('webforms', 'template');
		$selection = new umiSelection;
		$selection->addObjectType($typeId);
		$result  = umiSelectionsParser::runSelection($selection);
		$exclude = array();
		foreach($result as $templateId) {
			if($templateId == $currentTemplateId) continue;
			$template = $objectsCollection->getObject($templateId);
			if(!($template instanceof umiObject)) continue;
			$exclude[] = $template->getValue('form_id');
		}
		$forms  = array_diff($forms, $exclude);
		$result = array();
		foreach($forms as $id) {
			$item_arr = array();
			$item_arr['attribute:id'] = $id;
			$item_arr['node:name']    = $typesCollection->getType($id)->getName();
			$result[] = $item_arr;
		}
		return array("items" => array("nodes:item" => $result));

	}

	public function getObjectEditLink($objectId, $type = false) {
		$object = umiObjectsCollection::getInstance()->getObject($objectId);
		$oType   = umiObjectTypesCollection::getInstance()->getType($object->getTypeId());
		if($oType->getParentId() == umiObjectTypesCollection::getInstance()->getBaseType("webforms", "form")) $type = "message";
		switch($type) {
			case 'form'     :
			case 'message'  : return $this->pre_lang . "/admin/webforms/message/" 	    . $objectId . "/";
			case 'template' : return $this->pre_lang . "/admin/webforms/template_edit/" . $objectId . "/";
			default 		: return $this->pre_lang . "/admin/webforms/address_edit/"  . $objectId . "/";

		}
	}

	public function getObjectTypeEditLink($typeId) {
		return Array(
			'create-link' => $this->pre_lang . "/admin/webforms/form_add/",
			'edit-link'   => $this->pre_lang . "/admin/webforms/form_edit/" . $typeId . "/"
		);
	}

	public function getForms($form_id = false) {
		$objectTypes = umiObjectTypesCollection::getInstance();
		$type_id     = $objectTypes->getBaseType('webforms', 'form');
		$sub_types   = $objectTypes->getSubTypesList($type_id);
		$block_arr   = Array();
		$lines       = Array();

		foreach($sub_types as $typeId) {
			$type = $objectTypes->getType($typeId);
			if ($type instanceof umiObjectType) {
				$name = $type->getName();
				$line_arr = Array();
				$line_arr['attribute:id'] = $typeId;
				if ($form_id == $typeId) {
					$line_arr['attribute:selected'] = 'selected';
				}
				$line_arr['node:text'] = $type->getName();
				$lines[] = def_module::parseTemplate('', $line_arr);
			}
		}

		$block_arr['subnodes:items']       = $lines;
		return def_module::parseTemplate('', $block_arr);
	}

};
?>