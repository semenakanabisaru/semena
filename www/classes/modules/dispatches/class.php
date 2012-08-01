<?php
	class dispatches extends def_module {
		public function __construct() {
			parent::__construct();


			if(cmsController::getInstance()->getCurrentMode() == "admin") {
				cmsController::getInstance()->getModule('users');
				$this->__loadLib("__admin.php");
				$this->__implement("__dispatches");

				$this->__loadLib("__messages.php");
				$this->__implement("__messages_messages");

				$this->__loadLib("__subscribers.php");
				$this->__implement("__subscribers_subscribers");


				$commonTabs = $this->getCommonTabs();
				if($commonTabs) {
					$commonTabs->add('lists');
					$commonTabs->add('subscribers');
					$commonTabs->add('messages', array('releases'));
				}


			} else {
				// кастомы
				$this->__loadLib("__custom.php");
				$this->__implement("__custom_dispatches");
			}
			$this->__loadLib("__releasees.php");
			$this->__implement("__releasees_releasees");

			$this->__loadLib("__subscribers_import.php");
			$this->__implement("__subscribers_import_dispatches");

			$regedit = regedit::getInstance();
			$this->per_page = (int) $regedit->getVal("//modules/dispatches/per_page");
			if (!$this->per_page) $this->per_page = 15;
		}

		public function unsubscribe() {
			$templater = cmsController::getInstance()->getCurrentTemplater();
			$sResult = "%subscribe_unsubscribed_failed%";

			$iSbsId = (int) getRequest('param0');
			$email = getRequest('email');

			$oSubscriber = umiObjectsCollection::getInstance()->getObject($iSbsId);

			if ($oSubscriber instanceof umiObject) {
				$iSubscriberType = $oSubscriber->getTypeId();
				$oSubscriberType = umiObjectTypesCollection::getInstance()->getType($iSubscriberType);
				if ($oSubscriberType) {
					$iSubscriberHierarchyType = $oSubscriberType->getHierarchyTypeId();
					$oSubscriberHierarchyType = umiHierarchyTypesCollection::getInstance()->getType($iSubscriberHierarchyType);

					if ($oSubscriberHierarchyType->getName() != "dispatches" || $oSubscriberHierarchyType->getExt() != "subscriber") {
						return $templater->putLangs($sResult);
					}

					if ($oSubscriber->name != $email) {
						return $templater->putLangs($sResult);
					}

					if ($oSubscriber->getValue("uid")) {
						$oSubscriber->setValue('subscriber_dispatches', null);
						$oSubscriber->commit();
					} else {
						umiObjectsCollection::getInstance()->delObject($iSbsId);
					}
				}
				$sResult = "%subscribe_unsubscribed_ok%";
			}


			return $templater->putLangs($sResult);
		}

		protected function getSubscriberByUserId($iUserId) {
			$oSubscriber = null;

			$iSbsHierarchyTypeId = umiHierarchyTypesCollection::getInstance()->getTypeByName("dispatches", "subscriber")->getId();
			$iSbsTypeId =  umiObjectTypesCollection::getInstance()->getTypeByHierarchyTypeId($iSbsHierarchyTypeId);
			$oSbsType = umiObjectTypesCollection::getInstance()->getType($iSbsTypeId);

			$oSbsSelection = new umiSelection;
			$oSbsSelection->setObjectTypeFilter();
			$oSbsSelection->addObjectType($iSbsTypeId);
			$oSbsSelection->setPropertyFilter();
			$oSbsSelection->addPropertyFilterEqual($oSbsType->getFieldId('uid'), $iUserId);
			$arrSbsSelResults = umiSelectionsParser::runSelection($oSbsSelection);
			if (is_array($arrSbsSelResults) && count($arrSbsSelResults)) {
				$iSbsId = $arrSbsSelResults[0];
				$oSubscriber = umiObjectsCollection::getInstance()->getObject($iSbsId);
			}

			return $oSubscriber;
		}

		protected function getSubscriberByMail($sEmail) {
			$oSubscriber = null;

			$iSbsHierarchyTypeId = umiHierarchyTypesCollection::getInstance()->getTypeByName("dispatches", "subscriber")->getId();
			$iSbsTypeId =  umiObjectTypesCollection::getInstance()->getTypeByHierarchyTypeId($iSbsHierarchyTypeId);
			$oSbsType = umiObjectTypesCollection::getInstance()->getType($iSbsTypeId);

			$oSbsSelection = new umiSelection;
			$oSbsSelection->setObjectTypeFilter();

			$oSbsSelection->addObjectType($iSbsTypeId);
			$oSbsSelection->setNamesFilter();
			$oSbsSelection->addNameFilterEquals($sEmail);
			$arrSbsSelResults = umiSelectionsParser::runSelection($oSbsSelection);

			if (is_array($arrSbsSelResults) && count($arrSbsSelResults)) {
				$iSbsId = $arrSbsSelResults[0];
				$oSubscriber = umiObjectsCollection::getInstance()->getObject($iSbsId);
			}

			return $oSubscriber;
		}

		public function subscribe($sTemplate = "default") {
			$sResult = "";
			if(!$sTemplate) $sTemplate = "default";
			list(
				$sUnregistredForm, $sRegistredForm, $sDispatchesForm, $sDispatchRowForm
			) = def_module::loadTemplates("dispatches/".$sTemplate,
				"subscribe_unregistred_user", "subscribe_registred_user", "subscriber_dispatches", "subscriber_dispatch_row"
			);

			$iSbsHierarchyTypeId = umiHierarchyTypesCollection::getInstance()->getTypeByName("dispatches", "subscriber")->getId();
			$iSbsTypeId =  umiObjectTypesCollection::getInstance()->getTypeByHierarchyTypeId($iSbsHierarchyTypeId);
			$oSbsType = umiObjectTypesCollection::getInstance()->getType($iSbsTypeId);

			// check user registred
			$this->is_auth = false;
			if($oMdlUsers = cmsController::getInstance()->getModule("users")) {
				if($oMdlUsers->is_auth()) {
					$iUserId = (int) $oMdlUsers->user_id;
					$this->is_auth = true;
					$this->user_id = $iUserId;
				}
			}

			if ($this->is_auth) {
				$arrRegBlock = array();
				// gen subscribe_registred_user form
				// check curr user in subscribers list
				$arrSbsDispatches = array();
				$oSubscriber = self::getSubscriberByUserId($this->user_id);
				if ($oSubscriber instanceof umiObject) {
					$arrSbsDispatches = $oSubscriber->getValue('subscriber_dispatches');
				}
				$arrRegBlock['subscriber_dispatches'] = self::parseDispatches($sDispatchesForm, $sDispatchRowForm, $arrSbsDispatches);
				$sResult = def_module::parseTemplate($sRegistredForm, $arrRegBlock);
			} else {
				// gen subscribe_unregistred_user form
				$arrUnregBlock = array();
				$iSbsGenderFldId = $oSbsType->getFieldId('gender');
				$oSbsGenderFld = umiFieldsCollection::getInstance()->getField($iSbsGenderFldId);
				$arrGenders = umiObjectsCollection::getInstance()->getGuidedItems($oSbsGenderFld->getGuideId());
				$sGenders = Array();
				foreach ($arrGenders as $iGenderId => $sGenderName) {
					$sGenders[] = "<option value=\"".$iGenderId."\">".$sGenderName."</option>";
				}
				$arrUnregBlock['void:sbs_genders'] = $sGenders;
				$sResult = def_module::parseTemplate($sUnregistredForm, $arrUnregBlock);
			}
			//$block_arr['action'] = $this->pre_lang . "/dispatcher/subscribe_do/";

			return $sResult;
		}

		public function getAllDispatches() {
			$oDispsSelection = new umiSelection;
			$oDispsSelection->setObjectTypeFilter();
			$iHierarchyTypeId = umiHierarchyTypesCollection::getInstance()->getTypeByName("dispatches", "dispatch")->getId();
			$iDispTypeId =  umiObjectTypesCollection::getInstance()->getTypeByHierarchyTypeId($iHierarchyTypeId);
			$oDispType = umiObjectTypesCollection::getInstance()->getType($iDispTypeId);

			$iActiveFldId = $oDispType->getFieldId('is_active');
			$oDispsSelection->addPropertyFilterEqual($iActiveFldId, true, true);

			$oDispsSelection->addObjectType($iDispTypeId);
			return umiSelectionsParser::runSelection($oDispsSelection);
		}

		protected function parseDispatches($sDispatchesForm, $sDispatchRowForm, $arrChecked=array(), $bOnlyChecked=false) {

			$arrDispSelResults = self::getAllDispatches();
			$arrDispsBlock = array();
			$arrDispsBlock['void:rows'] = Array();

			if (is_array($arrDispSelResults) && count($arrDispSelResults)) {
				for ($iI=0; $iI<count($arrDispSelResults); $iI++) {
					$iNextDispId = $arrDispSelResults[$iI];
					$oNextDisp = umiObjectsCollection::getInstance()->getObject($iNextDispId);
					if ($oNextDisp instanceof umiObject) {
						$arrDispRowBlock = "";
						$arrDispRowBlock['attribute:id'] = $arrDispRowBlock['void:disp_id'] = $oNextDisp->getId();
						$arrDispRowBlock['node:disp_name'] = $oNextDisp->getName();
						$arrDispRowBlock['attribute:is_checked'] = (in_array($iNextDispId, $arrChecked)? 1: 0);
						$arrDispRowBlock['void:checked'] = ($arrDispRowBlock['attribute:is_checked']? "checked": "");

						if ($arrDispRowBlock['attribute:is_checked']  || !$bOnlyChecked) {
							$arrDispsBlock['void:rows'][] = def_module::parseTemplate($sDispatchRowForm, $arrDispRowBlock, false, $iNextDispId);
						}
					}
				}
			}
			$arrDispsBlock['nodes:items'] = $arrDispsBlock['void:rows'];
			return def_module::parseTemplate($sDispatchesForm, $arrDispsBlock);
		}

		public function subscribe_do() {
			$sResult = "";
			// input
			$sSbsMail = trim(getRequest('sbs_mail'));
			$sSbsLName = getRequest('sbs_lname');
			$sSbsFName = getRequest('sbs_fname');
			$iSbsGender = (int) getRequest('sbs_gender');
			$sSbsFatherName = getRequest('sbs_father_name');
			$arrSbsDispatches = getRequest('subscriber_dispatches');


			if(is_array($arrSbsDispatches)) {
				$arrSbsDispatches = array_map('intval', $arrSbsDispatches);
			} else {
				$arrSbsDispatches = array();
			}

			$controller = cmsController::getInstance();
			$templater = $controller->getCurrentTemplater();
			$oSubscriber = null;
			// check user registred
			$this->is_auth = false;
			if($oMdlUsers = $controller->getModule("users")) {
				if($oMdlUsers->is_auth()) {
					$iUserId = (int) $oMdlUsers->user_id;
					$this->is_auth = true;
					$this->user_id = $iUserId;
					if($oUserObj = umiObjectsCollection::getInstance()->getObject($iUserId)) {
						$sSbsMail = $oUserObj->getValue("e-mail");
						$sSbsLName = $oUserObj->getValue("lname");
						$sSbsFName = $oUserObj->getValue("fname");
						$sSbsFatherName = $oUserObj->getValue("father_name");
						$iSbsGender = $oUserObj->getValue("gender");
					}
					$oSubscriber = self::getSubscriberByUserId($iUserId);
				} elseif(umiMail::checkEmail($sSbsMail)) {
					$oSubscriber = self::getSubscriberByMail($sSbsMail);
					$arrSbsDispatches = self::getAllDispatches();
				}
				else {
					$sResult = "%subscribe_incorrect_email%";
					return (!def_module::isXSLTResultMode()) ? $sResult : Array("result" => $sResult);
				}

				if (!$oSubscriber instanceof umiObject && !empty($sSbsMail)) {
					$oSubscriber = self::getSubscriberByMail($sSbsMail);
				}
			}
			elseif (!umiMail::checkEmail($sSbsMail)) {
				$sResult = "%subscribe_incorrect_email%";
				return (!def_module::isXSLTResultMode()) ? $sResult : Array("result" => $sResult);
			}

			if ($oSubscriber instanceof umiObject) {
				$iSbsObjId = $oSubscriber->getId();
				if(!$this->is_auth) {
					list($template_block) = def_module::loadTemplates("dispatches/default", "subscribe_guest_alredy_subscribed");
					$block_arr = Array();
					$block_arr['unsubscribe_link'] = $this->pre_lang . "/dispatches/unsubscribe/" . $oSubscriber->getId() . "/?email=" . $oSubscriber->name;
					return def_module::parseTemplate($template_block, $block_arr);
				}
			} else {
				// create sbs
				$iSbsHierarchyTypeId = umiHierarchyTypesCollection::getInstance()->getTypeByName("dispatches", "subscriber")->getId();
				$iSbsTypeId =  umiObjectTypesCollection::getInstance()->getTypeByHierarchyTypeId($iSbsHierarchyTypeId);

				$iSbsObjId = umiObjectsCollection::getInstance()->addObject($sSbsMail, $iSbsTypeId);
			}

			if (count($arrSbsDispatches)) {
				$from = regedit::getInstance()->getVal("//settings/fio_from");
				$from_email = regedit::getInstance()->getVal("//settings/email_from");

				list($template_mail, $template_mail_subject) = def_module::loadTemplatesForMail("dispatches/default", "subscribe_confirm", "subscribe_confirm_subject");

				$mail_arr = Array();
				$mail_arr['domain'] = $domain = $_SERVER['HTTP_HOST'];
				$mail_arr['unsubscribe_link'] = "http://" . $domain . $this->pre_lang . "/dispatches/unsubscribe/" . $iSbsObjId . "/?email=" . $sSbsMail;
				$mail_subject = def_module::parseTemplateForMail($template_mail_subject, $mail_arr);
				$mail_content = def_module::parseTemplateForMail($template_mail, $mail_arr);

				$confirmMail = new umiMail();
				$confirmMail->addRecipient($sSbsMail);
				$confirmMail->setFrom($from_email, $from);
				$confirmMail->setSubject($mail_subject);
				$confirmMail->setContent($mail_content);
				$confirmMail->commit();
			}

			// try get object
			$oSubscriber = umiObjectsCollection::getInstance()->getObject($iSbsObjId);
			if ($oSubscriber instanceof umiObject) {
				$oSubscriber->setName($sSbsMail);
				$oSubscriber->setValue('lname', $sSbsLName);
				$oSubscriber->setValue('fname', $sSbsFName);
				$oSubscriber->setValue('father_name', $sSbsFatherName);
				$oCurrDate = new umiDate(time());
				$oSubscriber->setValue('subscribe_date', $oCurrDate);
				$oSubscriber->setValue('gender', $iSbsGender);
				if ($this->is_auth) {
					$oSubscriber->setValue('uid', $this->user_id);
					$oSubscriber->setValue('subscriber_dispatches', $arrSbsDispatches);
					$sDispForm = "%subscribe_subscribe_user%:<br /><ul>%rows%</ul>";
					$sDispFormRow = "<li>%disp_name%</li>";

					$sResult = self::parseDispatches($sDispForm, $sDispFormRow, $arrSbsDispatches, true);
				} else {
					// subscriber has all dispatches
					$oSubscriber->setValue('subscriber_dispatches', $arrSbsDispatches);
					$sResult = "%subscribe_subscribe%";
				}
				$oSubscriber->commit();
			}


			return (!def_module::isXSLTResultMode()) ? $sResult : Array("result" => $sResult);
		}

		public function getObjectEditLink($objectId, $type = false) {
			return $this->pre_lang . "/admin/dispatches/edit/"  . $objectId . "/";
    		switch($type) {
    			case 'dispatch'   : return $this->pre_lang . "/admin/dispatches/edit/" 	     . $objectId . "/";
    			case 'subscriber' : return $this->pre_lang . "/admin/dispatches/edit/" . $objectId . "/";
    			case 'release'    : return $this->pre_lang . "/admin/dispatches/edit/" 	 . $objectId . "/";
    			default 		 : return $this->pre_lang . "/admin/dispatches/edit/"  . $objectId . "/";

			}
		}
	};
?>