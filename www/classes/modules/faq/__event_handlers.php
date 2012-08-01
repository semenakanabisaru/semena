<?php

	abstract class __faq_handlers {

		public function onChangeActivity(iUmiEventPoint $oEventPoint) {
			if ($oEventPoint->getMode() === "after") {
				$element = $oEventPoint->getRef("element");

				if (!$element->getIsActive()) return true;

				$type_id = $element->getTypeId();
				$type = umiHierarchyTypesCollection::getInstance()->getType($type_id);
				if ($type->getName() == "faq" && $type->getExt() == "question") {
					return $this->confirmUserAnswer($element);
				}
			}
		}

		public function confirmUserAnswer($oElement) {
			$bConfirmUserAnswer = (bool) regedit::getInstance()->getVal("//modules/faq/confirm_user_answer");
			if (!$bConfirmUserAnswer) return true;

			if ($oElement instanceof umiHierarchyElement && $oElement->getIsActive()) {
				$iAuthorId = $oElement->getValue("author_id");

				$author_name = "";
				$author_email = "";

				$oAuthor = umiObjectsCollection::getInstance()->getObject($iAuthorId);

				if ($oAuthor instanceof umiObject) {
					$author_user = null;
					if($oAuthor->getValue("is_registrated")) {
						$user_id = $oAuthor->getValue("user_id");
						$author_user = umiObjectsCollection::getInstance()->getObject($user_id);
					}
					if ($author_user instanceof umiObject) {
						// author user
						$author_name = $author_user->getValue("lname")." ".$author_user->getValue("fname");
						$author_email = $author_user->getValue("e-mail");
					} else {
						// author guest
						$author_name = $oAuthor->getValue("nickname");
						$author_email = $oAuthor->getValue("email");
					}
				}

				if (umiMail::checkEmail($author_email)) {
					list($sMailSubject, $sMailBody) = def_module::loadTemplatesForMail("faq/default", "answer_mail_subj", "answer_mail");

					$block_arr = Array();
					$block_arr['domain'] = $sDomain = $_SERVER['HTTP_HOST'];
					$block_arr['element_id'] = $iElementId = $oElement->getId();
					$block_arr['author_id'] = $oElement->getValue("author_id");

					$bOldFHStatus = umiHierarchy::getInstance()->forceAbsolutePath(true);
					$block_arr['question_link'] = umiHierarchy::getInstance()->getPathById($iElementId);
					umiHierarchy::getInstance()->forceAbsolutePath($bOldFHStatus);

					$block_arr['ticket'] = $iElementId;

					$sSubject = def_module::parseTemplateForMail($sMailSubject, $block_arr, $iElementId);
					$sBody = def_module::parseTemplateForMail($sMailBody, $block_arr, $iElementId);

					$from = regedit::getInstance()->getVal("//settings/fio_from");
					$from_email = regedit::getInstance()->getVal("//settings/email_from");

					$oMail = new umiMail();
					$oMail->addRecipient($author_email);
					$oMail->setFrom($from_email, $from);
					$oMail->setSubject($sSubject);
					$oMail->setContent($sBody);
					$oMail->commit();
				}
			}

			return true;
		}
	}

?>
