<?php
	abstract class __content_events {
		public function cronSendNotification($oEvent){
			$object_type = umiObjectTypesCollection::getInstance()->getTypeByGUID('root-pages-type');
			$field_id = $object_type->getFieldId("notification_date");
			$field_id_expiration = $object_type->getFieldId("expiration_date");
			$sel = new umiSelection();
			$sel->addPropertyFilterLess($field_id, time());
			$sel->addPropertyFilterMore($field_id_expiration, time());
			$sel->addPropertyFilterNotEqual($field_id, 0);
			$sel->addActiveFilter(true);
			$sel->forceHierarchyTable(true);
			$result = umiSelectionsParser::runSelection($sel);

			foreach ($result as $key => $page_id){
				$ePage = umiHierarchy::getInstance()->getElement($page_id, true, true);
				if($ePage instanceof umiHierarchyElement == false) {
					continue;
				}

				$oPage = $ePage->getObject();
				$oPage->setValue("publish_status", $this->getPageStatusIdByStatusSid("page_status_preunpublish"));
				if(!$publishComments = $ePage->getValue("publish_comments")) $publishComments = "Отсутствуют.";
				$user_id = $oPage->getOwnerId();

				$oUser = umiObjectsCollection::getInstance()->getObject($user_id);
				if ($oUser instanceof umiObject && $user_email = $oUser->getValue("e-mail")){
					//Составляем и посылаем сообщение пользователю
					$mail_message = new umiMail();
					$from = regedit::getInstance()->getVal("//settings/email_from");
					$mail_message->setFrom($from);
					$mail_message->setPriorityLevel("high");
					$mail_message->setSubject(getLabel('label-notification-mail-header'));
					list ($body) = def_module::loadTemplates("mail/notify", "body");
					$block['notify_header']	= getLabel('label-notification-mail-header');
					$block['page_header'] =  $ePage->getName();
					$block['publish_comments'] = $publishComments;
					$domain = domainsCollection::getInstance()->getDomain($ePage->getDomainId());
					$page_host = "http://".$domain->getHost().umiHierarchy::getInstance()->getPathById($page_id);
					$block['page_link'] = $page_host;
					$mail_html = def_module::parseContent($body, $block, $page_id);
					$mail_message->addRecipient($user_email);
					$mail_message->setContent($mail_html);

					$mail_message->commit();
					$mail_message->send();
	 			}
	 			$oPage->commit();
	 			$ePage->commit();
			}
		}

		public function cronUnpublishPage($oEvent){
			$object_type = umiObjectTypesCollection::getInstance()->getTypeByGUID('root-pages-type');
			$field_id = $object_type->getFieldId("expiration_date");
			$sel = new umiSelection();
			$sel->addPropertyFilterLess($field_id, time());
			$sel->addPropertyFilterNotEqual($field_id, 0);
			$sel->addActiveFilter(true);
			$sel->forceHierarchyTable(true);
			$result = umiSelectionsParser::runSelection($sel);
			$res = Array();

			foreach ($result as $key=>$page_id){
				$ePage = umiHierarchy::getInstance()->getElement($page_id, true);
				$ePage->setIsActive(false);
				$pageObject = $ePage->getObject();
				$pageObject->setValue("publish_status", $this->getPageStatusIdByStatusSid("page_status_unpublish"));
				$pageObject->commit();
				$ePage->commit();

				if(!$publishComments = $ePage->getValue("publish_comments")) $publishComments = "Отсутствуют.";

				$user_id = $ePage->getObject()->getOwnerId();
				$oUser = umiObjectsCollection::getInstance()->getObject($user_id);
				if ($oUser instanceof umiObject && $user_email = $oUser->getValue("e-mail")){
					//Составляем и посылаем сообщение пользователю
					$mail_message = new umiMail();
					$from = regedit::getInstance()->getVal("//settings/email_from");
					$mail_message->setFrom($from);
					$mail_message->setPriorityLevel("high");
					$mail_message->setSubject(getLabel('label-notification-expired-mail-header'));
					list ($body) = def_module::loadTemplates("mail/expired", "body");
					$block['notify_header']	= getLabel('label-notification-expired-mail-header');
					$block['page_header'] =  $ePage->getName();
					$block['publish_comments'] = $publishComments;
					$domain = domainsCollection::getInstance()->getDomain($ePage->getDomainId());
					$page_host = "http://".$domain->getHost().umiHierarchy::getInstance()->getPathById($page_id);
					$block['page_link'] = $page_host;
					$mail_html = def_module::parseContent($body, $block, $page_id);
					$mail_message->addRecipient($user_email);
					$mail_message->setContent($mail_html);
					$mail_message->commit();
					$mail_message->send();
	 			}
			}
		}

		public function pageCheckExpiration($event) {
			if ($inputData = $event->getRef("inputData")) {
				$page = getArrayKey($inputData, "element");
				$this->saveExpiration($page);
			}
		}

		public function pageCheckExpirationAdd($event) {
			if ($page = $event->getRef("element")) {
				$this->saveExpiration($page);
			}
		}

		public function saveExpiration ($page) {
			$pageObject = $page->getObject();
			$expirationTime = $pageObject->expiration_date;

			if ($expirationTime instanceof umiDate) {
				if ($expirationTime->timestamp > time()) {
					$pageObject->publish_status = $this->getPageStatusIdByStatusSid("page_status_publish");
					$page->setIsActive(true);
				} elseif ($expirationTime->timestamp < time() && $expirationTime->timestamp != NULL ) {
					$pageObject->publish_status = $this->getPageStatusIdByStatusSid("page_status_unpublish");
					$page->setIsActive(false);
				}
				$pageObject->commit();
				$page->commit();
			}/* else {
				$pageObject->setValue("publish_status", $this->getPageStatusIdByStatusSid("page_status_publish"));
				$page->setIsActive(true);
				$pageObject->commit();
				$page->commit();
			}
			*/
		}

		public function onModifyPoropertyAntispam(iUmiEventPoint $event) {
			$entity = $event->getRef("entity");
			if(($entity instanceof iUmiHierarchyElement) && ($event->getParam("property") == "is_spam")) {
				$type = umiHierarchyTypesCollection::getInstance()->getTypeByName("faq", "question");
				$contentField = ($type->getId() == $entity->getTypeId()) ? 'question' : 'content';
				antiSpamHelper::report($entity->getId(), $contentField);
			}
		}

		public function onModifyElementAntispam(iUmiEventPoint $event) {
			static $cache = array();
			$element  = $event->getRef("element");
			if(!$element) return;
			if($event->getMode() == "before") {
				$data = getRequest("data");
				if(isset($data[ $element->getId() ])) {
					$oldValue = getArrayKey($data[ $element->getId() ], 'is_spam');
					if($oldValue != $element->getValue("is_spam")) {
						$cache[ $element->getId() ] = true;
					}
				}
			} else if(isset($cache[ $element->getId() ])) {
				$type = umiHierarchyTypesCollection::getInstance()->getTypeByName("faq", "question");
				$contentField = ($type->getId() == $element->getTypeId()) ? 'question' : 'content';
				antiSpamHelper::report($element->getId(), $contentField);
			}
		}
	};
?>