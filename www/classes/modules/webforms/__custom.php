<?php
	abstract class __custom_webforms {
		//TODO: Write here your own macroses

		public function mysend() {

			// Check captcha to know we should do anything

			if (isset($_REQUEST['captcha'])) {

				$_SESSION['user_captcha'] = md5((int) $_REQUEST['captcha']);

			}

			//---------------------- моя вставка -------------------------------- //
			if ( isset($_SERVER['HTTP_REFERER']) ) {

				// проверим поля
				$form_data = getRequest('data');
				$form_id = (int)getRequest('system_form_id');
				if (is_array($form_data) && is_array($form_data['new']) && $form_id) {
					$form_data = $form_data['new'];
					// удаляем пробелы по бокам
					foreach($form_data as $key => $value) {
						$form_data[$key] = trim($value);
					}
					// получаем поля типа данных
					$allFields = umiObjectTypesCollection::getInstance()->getType($form_id)->getAllFields();
					foreach($allFields as $field) {
						if($field->getIsRequired()) {
							$fieldName = $field->getName();
							$fieldType = $field->getRestrictionId();
							// если email
							if ( $fieldType == 2 && (!isset($form_data[$fieldName]) || !preg_match("/^[a-z0-9\-_\.]+@[a-z0-9\-_\.]{2,}\.[a-z]{2,4}$/iu", $form_data[$fieldName])) ) { 
								$mistaken_ids[] = $field->getId();
							// для других
							} elseif ( !isset($form_data[$fieldName]) || !strlen($form_data[$fieldName]) ) {
								$mistaken_ids[] = $field->getId();
							}
						}
					}


					
				}

				// проверим капча
				if (!umiCaptcha::checkCaptcha()) {
					$mistaken_ids[] = "captcha";
				}

				if ($mistaken_ids) {
					$redirect = preg_replace("/\?.*/iu", "", $_SERVER['HTTP_REFERER']);
					$this->redirect($redirect."?mistaken=|".implode("|", $mistaken_ids)."|");
					die();
				}
			}
			//------------------------------------------------------------------- //



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

	};
?>