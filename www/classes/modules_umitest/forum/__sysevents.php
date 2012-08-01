<?php
	abstract class __sysevents_forum {
		public function onDispatchChanges(iUmiEventPoint $oEvent) {
			$sTemplate = "default";

			try {
				list($sTemplateSubject, $sTemplateMessage) = $this->loadTemplates("forum/mails/".$sTemplate, "mail_subject", "mail_message");
			} catch (publicException $e) {
				return false;
			}

			$iTopicId = $oEvent->getParam("topic_id");
			$iMessageId = $oEvent->getParam("message_id");
			$message = umiHierarchy::getInstance()->getElement($iMessageId);

			$sel = new selector('objects');
			$sel->types('object-type')->name("users", "user");
			$sel->where('subscribed_pages')->equals($iTopicId);

			if (!$sel->length()) return false;

			$hierarchy = umiHierarchy::getInstance();

			$block_arr = Array();

			$sTemplateSubject = def_module::parseContent($sTemplateSubject, $block_arr, $iMessageId);

			$sFromEmail = regedit::getInstance()->getVal("//settings/email_from");
			$sFromFio = regedit::getInstance()->getVal("//settings/fio_from");

			$oMail = new umiMail();
			$oMail->setFrom($sFromEmail, $sFromFio);
			$oMail->setSubject($sTemplateSubject);

			foreach($sel->result() as $oUser) {
				$oMailUser = clone $oMail;
				$sUserMail = $oUser->getValue('e-mail');
				$block_arr['h1'] = $message->getValue('h1');
				$block_arr['message'] = $message->getValue('message');

				$hierarchy->forceAbsolutePath(true);
				$block_arr['unsubscribe_link'] = $hierarchy->getPathById($iTopicId) . "?unsubscribe=" . base64_encode($iUserId);
				$sTemplateMessageUser = def_module::parseContent($sTemplateMessage, $block_arr, $iMessageId);
				$oMail->setContent($sTemplateMessageUser);
				$hierarchy->forceAbsolutePath(false);

				if (umiMail::checkEmail($sUserMail)) {
					$sUserFio = $oUser->getValue('lname') . " ". $oUser->getValue('fname') . " " . $oUser->getValue('father_name');
					$oMailUser->addRecipient($sUserMail, $sUserFio);
					$oMailUser->commit();
					$oMailUser->send();
				}
				else continue;
			}
			return true;
		}

		public function onAddTopicToDispatch(iUmiEventPoint $oEvent) {
			$iDispatchId = regedit::getInstance()->getVal("//modules/forum/dispatch_id");

			if(!$iDispatchId) return false;
			
			$dispatches_module = cmsController::getInstance()->getModule('dispatches');
			
			if(!$dispatches_module) {
				return false;
			}

			$iTopicId = (int) $oEvent->getParam('topic_id');
			$oTopicElement = umiHierarchy::getInstance()->getElement($iTopicId);
			if($oTopicElement instanceof umiHierarchyElement) {
				$sTitle = (string) getRequest('title');
				$sMessage = (string) getRequest('body');

				$iHierarchyTypeId = umiHierarchyTypesCollection::getInstance()->getTypeByName("dispatches", "message")->getId();
				$iMsgTypeId =  umiObjectTypesCollection::getInstance()->getTypeByHierarchyTypeId($iHierarchyTypeId);
				$oMsgType = umiObjectTypesCollection::getInstance()->getType($iMsgTypeId);
				$iMsgObjId = umiObjectsCollection::getInstance()->addObject($sTitle, $iMsgTypeId);

				$oMsgObj = umiObjectsCollection::getInstance()->getObject($iMsgObjId);
				if ($oMsgObj instanceof umiObject) {
					$iReleaseId = $dispatches_module->getNewReleaseInstanceId($iDispatchId);

					$oMsgObj->setValue('release_reference', $iReleaseId);
					$oMsgObj->setValue('header', $sTitle);
					$oMsgObj->setValue('body', $sMessage);
					$oMsgObj->commit();

					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
		}
	};
?>