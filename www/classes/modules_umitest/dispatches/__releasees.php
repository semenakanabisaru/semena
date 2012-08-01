<?php
	abstract class __releasees_releasees {
		//
		private $arrReleasees = array();
		public function getNewReleaseInstanceId($iDispId = false) {
			if (!$iDispId) {
				$iDispId = getRequest("param0");
			}

			$iReleaseId = false;
			if (isset($arrReleasees[$iDispId])) {
				$iReleaseId = $arrReleasees[$iDispId];
			} else {
				$oReleaseesSelection = new umiSelection;
				$oReleaseesSelection->setObjectTypeFilter();
				$iHierarchyTypeId = umiHierarchyTypesCollection::getInstance()->getTypeByName("dispatches", "release")->getId();
				$iReleaseTypeId =  umiObjectTypesCollection::getInstance()->getTypeByHierarchyTypeId($iHierarchyTypeId);
				$oReleaseType = umiObjectTypesCollection::getInstance()->getType($iReleaseTypeId);
				$oReleaseesSelection->addObjectType($iReleaseTypeId);
				$oReleaseesSelection->setPropertyFilter();

				$oReleaseesSelection->addPropertyFilterIsNull($oReleaseType->getFieldId('status'), 0, true);
				$oReleaseesSelection->addPropertyFilterEqual($oReleaseType->getFieldId('disp_reference'), $iDispId);
				$arrSelResults = umiSelectionsParser::runSelection($oReleaseesSelection);
				$bIsNewRelease = false;
				if (is_array($arrSelResults) && isset($arrSelResults[0])) {
					$iReleaseId = $arrSelResults[0];
					$oRelease = umiObjectsCollection::getInstance()->getObject($iReleaseId);
				} else {
					$iReleaseId = umiObjectsCollection::getInstance()->addObject("", $iReleaseTypeId);
					$bIsNewRelease = true;
				}
				$oRelease = umiObjectsCollection::getInstance()->getObject($iReleaseId);
				if ($oRelease instanceof umiObject) {
					if ($bIsNewRelease) {
						$oRelease->setName('-');
						$oRelease->setValue('status', false);
						$oRelease->setValue('disp_reference', $iDispId);
						$oRelease->commit();
					}
					$arrReleasees[$iDispId] = $oRelease->getId();
				}
			}
			return $iReleaseId;
		}
		
		public function fill_release($dispatch_id = false, $ignore_redirect = false) {
			$iDispId = $dispatch_id ? $dispatch_id : getRequest('param0');
			$oDispatch = umiObjectsCollection::getInstance()->getObject($iDispId);
			if ($oDispatch instanceof umiObject) {
				$iReleaseId = $this->getNewReleaseInstanceId($iDispId);
				$iNewsRelation = $oDispatch->getValue("news_relation");
				$arrNewsLents = umiHierarchy::getInstance()->getObjectInstances($iNewsRelation, false, true);

				// get all releases


				$iHierarchyTypeId = umiHierarchyTypesCollection::getInstance()->getTypeByName("dispatches", "release")->getId();
				$iReleaseTypeId =  umiObjectTypesCollection::getInstance()->getTypeByHierarchyTypeId($iHierarchyTypeId);
				$oReleaseType = umiObjectTypesCollection::getInstance()->getType($iReleaseTypeId);

				$sel = new umiSelection;

				$sel->setObjectTypeFilter();
				$sel->addObjectType($iReleaseTypeId);

				$sel->setPropertyFilter();
				$sel->addPropertyFilterEqual($oReleaseType->getFieldId('disp_reference'), $iDispId);
				
	
				$arrReleases =  umiSelectionsParser::runSelection($sel);
				
				if (count($arrNewsLents)) {

					$iElementId = (int) $arrNewsLents[0];
					$hierarchy_type_id = umiHierarchyTypesCollection::getInstance()->getTypeByName("news", "item")->getId();
					$object_type_id = umiObjectTypesCollection::getInstance()->getBaseType("news", "item");
					$object_type = umiObjectTypesCollection::getInstance()->getType($object_type_id);
					$publish_time_field_id = $object_type->getFieldId('publish_time');

					$sel = new umiSelection;
					$sel->setElementTypeFilter();
					$sel->addElementType($hierarchy_type_id);

					$sel->setOrderFilter();
					$sel->setOrderByProperty($publish_time_field_id, false);

					$sel->setHierarchyFilter();
					$sel->addHierarchyFilter($iElementId, 0, true);

					$sel->setLimitFilter();
					$sel->addLimit(50);
					
					$sel->setIsLangIgnored(true);

/*
					$sel->setPropertyFilter();
					$sel->addPropertyFilterBetween($publish_time_field_id, $date1, $date2);
*/

					$result = umiSelectionsParser::runSelection($sel);

					for ($iI = 0; $iI < count($result); $iI++) { 
						$iNextNewId = $result[$iI];
						$oNextNew = umiHierarchy::getInstance()->getElement($iNextNewId);
						if ($oNextNew instanceof umiHierarchyElement) {
							$sName = $oNextNew->getName();
							$sHeader = $oNextNew->getValue('h1');
							$sShortBody = $oNextNew->getValue('anons');
							$sBody = $oNextNew->getValue('content');
							$oPubTime = $oNextNew->getValue('publish_time');
							
							if(!strlen($sBody)) $sBody = $sShortBody;

							// add message
							$iHierarchyTypeId = umiHierarchyTypesCollection::getInstance()->getTypeByName("dispatches", "message")->getId();
							$iMsgTypeId =  umiObjectTypesCollection::getInstance()->getTypeByHierarchyTypeId($iHierarchyTypeId);
							$oMsgType = umiObjectTypesCollection::getInstance()->getType($iMsgTypeId);

							// check if new exists
							$oSel = new umiSelection;

							$oSel->setObjectTypeFilter();
							$oSel->addObjectType($iMsgTypeId);

							$oSel->setPropertyFilter();
							$oSel->addPropertyFilterEqual($oMsgType->getFieldId('new_relation'), $iNextNewId);


							$oSel->setPropertyFilter();
							$oSel->addPropertyFilterEqual($oMsgType->getFieldId('release_reference'), $arrReleases);

							$oSel->setLimitFilter();
							$oSel->addLimit(1);

							$iCount = umiSelectionsParser::runSelectionCounts($oSel);

							if ($iCount > 0) continue;

							//add new message
							$iMsgObjId = umiObjectsCollection::getInstance()->addObject($sName, $iMsgTypeId);
							// try get object
							$oMsgObj = umiObjectsCollection::getInstance()->getObject($iMsgObjId);
							if ($oMsgObj instanceof umiObject) {
								$oMsgObj->setValue('release_reference', $iReleaseId);
								$oMsgObj->setValue('header', $sHeader);
								$oMsgObj->setValue('body', $sBody);
								$oMsgObj->setValue('short_body', $sShortBody);
								$oMsgObj->setValue('new_relation', array($iNextNewId));
								if ($oPubTime instanceof umiDate) {
									$oMsgObj->setValue('msg_date', $oPubTime);
								}

								$oMsgObj->commit();
							}
						}
					}
				}

			}
            // WTF? IE7 does not send the HTTP_REFERER header, so we should set the redirect string manually
			if (!$ignore_redirect) $this->chooseRedirect('/admin/dispatches/edit/'.$iDispId.'/');
		}
		
		public function onAutosendDispathes($event) {
			$objects = umiObjectsCollection::getInstance();
			
			$sel = new selector('objects');
			$sel->types('object-type')->name('dispatches', 'dispatch');
			$dispatches = $sel->result;
			
			@set_time_limit(0);
			foreach ($dispatches as $dispatch) {
				// check last release date
				$disp_last_release = $dispatch->disp_last_release;
				if ($disp_last_release instanceof umiDate && $disp_last_release->timestamp > time() - 3600) {
					continue;
				}

				// check day
				$need_dispatch = false;
				$arr_days = $dispatch->days;
				if (is_array($arr_days)) {
					foreach ($arr_days as $i_day) {
						$day = $objects->getObject($i_day);
						if ($day->number == intval(date("N"))) {
							$need_dispatch = true;
							break;
						}
					}
				}
				if (!$need_dispatch) continue;
				// check hour
				$need_dispatch = false;
				$arr_hours = $dispatch->hours;
				if (is_array($arr_hours)) {
					foreach ($arr_hours as $i_hour) {
						$hour = $objects->getObject($i_hour);
						if ($hour->number == intval(date("H"))) {
							$need_dispatch = true;
							break;
						}
					}
				}
				
				if (!$need_dispatch) continue;
				// fill release
				$this->fill_release($dispatch->id, true);
				// send dispatch release
				$this->release_send_full($dispatch->id);
				
			}
			
			// этот exit мешает запуску других кронов
			//exit();
		}

		/* TODO: refactoring !!!! */
		public function release_send_full($iDispId) {
			$objectsColl = umiObjectsCollection::getinstance();
			$iReleaseId = $this->getNewReleaseInstanceId($iDispId);

			$oDispatch = $objectsColl->getObject($iDispId);
			$oRelease = $objectsColl->getObject($iReleaseId);
			if (!$oDispatch instanceof umiObject || !$oRelease instanceof umiObject) {
				return false;
			}

			if($oRelease->getValue('status')) {
				return false;
			}

			$sHost = cmsController::getInstance()->getCurrentDomain()->getHost();

			$oMailer = new umiMail();
			
			// mail template
			$arrMailBlocks = array();
			$arrMailBlocks['header'] = $oDispatch->getName();
			$arrMailBlocks['messages'] = "";

			list($sReleaseFrm, $sMessageFrm) = def_module::loadTemplates("dispatches/release", "release_body", "release_message");

			$sel = new selector('objects');
			$sel->types("object-type")->name("dispatches", "message");
			$sel->where("release_reference")->equals($iReleaseId);
			$arrSelResults = $sel->result();
			if (!$sel->length()) return false;

			foreach ($arrSelResults as $oNextMsg) {
				if ($oNextMsg instanceof umiObject) {
					$arrMsgBlocks = array();
					$arrMsgBlocks['body'] = $oNextMsg->getValue('body');
					$arrMsgBlocks['header'] = $oNextMsg->getValue('header');
					$arrMailBlocks['messages'] .= def_module::parseContent($sMessageFrm, $arrMsgBlocks);
					$oNextAttach = $oNextMsg->getValue('attach_file');
					if ($oNextAttach instanceof umiFile && !$oNextAttach->getIsBroken()) {
						$oMailer->attachFile($oNextAttach);
					}
				}
			}

			$oMailer->setFrom(regedit::getInstance()->getVal("//settings/email_from"), regedit::getInstance()->getVal("//settings/fio_from"));
			$oMailer->setSubject($arrMailBlocks['header']);

			$sel = new selector('objects');
			$sel->types("object-type")->name("dispatches", "subscriber");
			$sel->where("subscriber_dispatches")->equals($iDispId);

			$delay = 0;
			$max_messages = (int) mainConfiguration::getinstance()->get('modules', 'dispatches.max_messages_in_hour');
			if ($max_messages && $sel->length() >= $max_messages) {
				$delay = floor(3600 / $max_messages);
			}

			foreach($sel->result() as $recipient) {
				$oNextMailer = clone $oMailer;
				$oNextSbs = new umiSubscriber($recipient->getId());
				$sRecipientName = $oNextSbs->getValue('lname')." ".$oNextSbs->getValue('fname')." ".$oNextSbs->getValue('father_name');

				$mail = $oNextSbs->getValue('email');
				if (!strlen($mail)) {
					$mail = $oNextSbs->getName();
				}

				$arrMailBlocks['unsubscribe_link'] = "http://".$sHost."/dispatches/unsubscribe/".$oNextSbs->getId() . '/?email=' . $mail;
				$oNextMailer->setContent(def_module::parseContent($sReleaseFrm, $arrMailBlocks, false, $oNextSbs->getId()));
				$oNextMailer->addRecipient($mail, $sRecipientName);
				$oNextMailer->commit();
				$oNextMailer->send();

				if ($delay) sleep($delay);
			}

			$oDate = new umiDate(time());
			$oDispatch->setValue('disp_last_release', $oDate);
			$oDispatch->commit();

			$oRelease->setValue('status', true);
			$oRelease->setValue('date', $oDate);
			$oRelease->setName($oDate->getFormattedDate('d-m-Y H:i'));
			$oRelease->commit();

			return true;
		}


		public function release_send() {
			$buffer = outputBuffer::current('HTTPOutputBuffer');
			$buffer->charset('utf-8');
			$buffer->contentType('text/xml');
			$buffer->push('<?xml version="1.0" encoding="utf-8"?>');

			$iDispId = (int) getRequest('param0');
			$iReleaseId = $this->getNewReleaseInstanceId($iDispId);
			$arrPostData = getRequest("data_values");
			$objectsColl = umiObjectsCollection::getinstance();
			$controller = cmsController::getInstance();

			$oDispatch = $objectsColl->getObject($iDispId);
			$oRelease = $objectsColl->getObject($iReleaseId);
			if (!$oDispatch instanceof umiObject || !$oRelease instanceof umiObject) {
				$buffer->push("<error>Не указан идентификатор рассылки</error>");
				$buffer->end();
			}

			if($oRelease->getValue('status')) {
				$buffer->push("<error>Этот выпуск уже был отправлен</error>");
				$buffer->end();
			}

			$arrRecipients = array();
			if (is_null(getSession('umi_send_list_' . $iReleaseId))) {
				$sel = new selector('objects');
				$sel->types("object-type")->name("dispatches", "subscriber");
				$sel->where("subscriber_dispatches")->equals($iDispId);
				$sel->option('return')->value('id');
				foreach($sel->result() as $recipient) {
					$arrRecipients[] = $recipient['id'];
				}
				$_SESSION['umi_send_list_' . $iReleaseId] = $arrRecipients;
				$_SESSION['umi_send_list_' . $iReleaseId . '_count'] = count($arrRecipients);
			}
			else $arrRecipients = getSession('umi_send_list_' . $iReleaseId);

			$delay = getSession('umi_send_list_' . $iReleaseId . '_delay');
			$iTotal = (int) getSession('umi_send_list_' . $iReleaseId . '_count');

			if ($delay and  time() < $delay) {
				$iSended = $iTotal - count($arrRecipients);
				$sResult = <<<END
<release dispatch="{$iDispId}">
	<total>{$iTotal}</total>
	<sended>{$iSended}</sended>
</release>
END;
				$buffer->push($sResult);
				$buffer->end();
			}

			$sHost = $controller->getCurrentDomain()->getHost();
			$oMailer = new umiMail();

			$arrMailBlocks = array();
			$arrMailBlocks['header'] = $oDispatch->getName();
			$arrMailBlocks['messages'] = "";

			$new_templater = getSession($iDispId . '_new_templater');

			if (is_null($new_templater)) {
				$_SESSION[$iDispId . '_new_templater'] = system_get_tpl();
				$new_templater = getSession($iDispId . '_new_templater');
			}

			$controller->setCurrentTemplater($new_templater);

			list($sReleaseFrm, $sMessageFrm) = def_module::loadTemplates("dispatches/release", "release_body", "release_message");

			$sel = new selector('objects');
			$sel->types("object-type")->name("dispatches", "message");
			$sel->where("release_reference")->equals($iReleaseId);
			if ($sel->length()) {
				foreach($sel->result() as $oNextMsg) {
					if ($oNextMsg instanceof umiObject) {
						$arrMsgBlocks = array();
						$arrMsgBlocks['body'] = $oNextMsg->getValue('body');
						$arrMsgBlocks['header'] = $oNextMsg->getValue('header');
						$arrMailBlocks['messages'] .= def_module::parseContent($sMessageFrm, $arrMsgBlocks);
						$oNextAttach = $oNextMsg->getValue('attach_file');
						if ($oNextAttach instanceof umiFile && !$oNextAttach->getIsBroken()) {
							$oMailer->attachFile($oNextAttach);
						}
					}
				}
			}
			else {
				unset($_SESSION[$iDispId . '_new_templater']);
				$buffer->push("<error>В выпуске нет сообщений</error>");
				$buffer->end();
			}

			$oMailer->setFrom(regedit::getInstance()->getVal("//settings/email_from"), regedit::getInstance()->getVal("//settings/fio_from"));
			$oMailer->setSubject($arrMailBlocks['header']);

			$delay = 0;
			$max_messages = (int) mainConfiguration::getinstance()->get('modules', 'dispatches.max_messages_in_hour');

			if ($max_messages && $iTotal >= $max_messages) $delay= floor (3600 / $max_messages);

			$aSended = array();
			foreach($arrRecipients as $recipient_id) {
				$oNextMailer = clone $oMailer;
				$oNextSbs = new umiSubscriber($recipient_id);
				$sRecipientName =  $oNextSbs->getValue('lname')." ".$oNextSbs->getValue('fname')." ".$oNextSbs->getValue('father_name');

				$mail = $oNextSbs->getValue('email');
				if (!strlen($mail)) $mail = $oNextSbs->getName();

				$arrMailBlocks['unsubscribe_link'] = "http://".$sHost."/dispatches/unsubscribe/".$oNextSbs->getId() . '/?email=' . $mail;
				$sMailBody = def_module::parseContent($sReleaseFrm, $arrMailBlocks, false, $oNextSbs->getId());
				$oNextMailer->setContent($sMailBody);

				$oNextMailer->addRecipient($mail, $sRecipientName);
				$oNextMailer->commit();
				$oNextMailer->send();
				$aSended[] = $recipient_id;

				//Unload temporary objects
				unset($oNextMailer);

				if ($delay) {
					$_SESSION['umi_send_list_' . $iReleaseId . '_delay'] = $delay + time();
					$_SESSION['umi_send_list_' . $iReleaseId] = array_diff($arrRecipients, $aSended);
					$iTotal = (int) getSession('umi_send_list_' . $iReleaseId . '_count');
					$iSended = $iTotal - (count($arrRecipients) - count($aSended));
					$sResult = <<<END
<release dispatch="{$iDispId}">
	<total>{$iTotal}</total>
	<sended>{$iSended}</sended>
</release>
END;
					$buffer->push($sResult);
					$buffer->end();
					
				}
				
			}

			umiMail::clearFilesCache();
			$_SESSION['umi_send_list_' . $iReleaseId] = array_diff($arrRecipients, $aSended);

			if (!count(getSession('umi_send_list_' . $iReleaseId))) {
				$oRelease->setValue('status', true);
				$oDate = new umiDate(time());
				$oRelease->setValue('date', $oDate);
				$oRelease->setName($oDate->getFormattedDate('d-m-Y H:i'));
				$oRelease->commit();

				$oDispatch->setValue('disp_last_release', $oDate);
				$oDispatch->commit();
			}

			$iTotal = (int) getSession('umi_send_list_' . $iReleaseId . '_count');
			$iSended = $iTotal - (count($arrRecipients) - count($aSended));

			usleep(500000);

			$sResult = <<<END
<release dispatch="{$iDispId}">
	<total>{$iTotal}</total>
	<sended>{$iSended}</sended>
</release>
END;

			unset($_SESSION[$iDispId . '_new_templater']);
			$buffer->push($sResult);
			$buffer->end();
		}

		public function collectAllChanges($module, $method, $parentElementId = false) {
				$endTime = time();
				$beginTime = ($endTime - 3600*24*30);

				$hierarchy_type_id = umiHierarchyTypesCollection::getInstance()->getTypeByName($module, $method)->getId();
				$object_type_id = umiObjectTypesCollection::getInstance()->getBaseType($module, $method);
				$object_type = umiObjectTypesCollection::getInstance()->getType($object_type_id);
				$publish_time_field_id = $object_type->getFieldId('publish_time');

				$sel = new umiSelection;
				$sel->setElementTypeFilter();
				$sel->addElementType($hierarchy_type_id);

				$sel->setPermissionsFilter();
				$sel->addPermissions();

				$sel->setOrderFilter();
				$sel->setOrderByProperty($publish_time_field_id, false);

				$sel->setPropertyFilter();
				$sel->addPropertyFilterBetween($publish_time_field_id, $beginTime, $endTime);

				if($parentElementId !== false) {
			if(!is_numeric($parentElementId)) {
				$parentElementId = umiHierarchy::getInstance()->getIdByPath($parentElementId);
			}
			
						$sel->setHierarchyFilter();
						$sel->addHierarchyFilter($parentElementId);
				}

				$result = umiSelectionsParser::runSelection($sel);
				$total = umiSelectionsParser::runSelectionCounts($sel);

				$res = Array();
				foreach($result as $elementId) {
						$childs = sizeof(umiHierarchy::getInstance()->getChilds($elementId));
						$res[] = Array("id" => $elementId, "childs" => $childs);
				}

				return Array("result" => $res, "total" => $total);
		}



		public function getChanges($template = "default", $module, $method, $parentElementId = false) {
			list(
				$template_block, $template_block_empty, $template_block_line, $template_block_line_counts
			) = def_module::loadTemplates("dispatches/changes/".$template,
				"block", "block_empty", "block_line", "block_line_counts"
			);
			$result = $this->collectAllChanges($module, $method, $parentElementId);
			$block_arr = Array();
			$block_arr['total'] = $total = (int) $result['total'];

			if($total == 0) {
				return $template_block_empty;
			}
			
			umiHierarchy::getInstance()->forceAbsolutePath(true);
			
			$items = Array();
			foreach($result['result'] as $node) {
				$item_arr = Array();
				$item_arr['id'] = $elementId = $node['id'];
				
				$count = $node['childs'];
				if($count) {
					$counts_arr = Array();
					$counts_arr['count'] = $count;
					$counts = def_module::parseTemplate($template_block_line_counts, $counts_arr);
				} else {
					$counts = "";
				}
				$item_arr['counts'] = $counts;
				$item_arr['link'] = umiHierarchy::getInstance()->getPathById($elementId);
				
				$items[] = def_module::parseTemplate($template_block_line, $item_arr, $elementId);
			}

			umiHierarchy::getInstance()->forceAbsolutePath(false);

			$block_arr['subnodes:items'] = $items;
			return def_module::parseTemplate($template_block, $block_arr);
		}


		public function getAllChanges($template = "default") {
			list($template_block) = def_module::loadTemplates("dispatches/changes/".$template, "message");
			return def_module::parseTemplate($template_block);
		}
		
		public function publishChanges() {
			$sTitle = "Monthly dispatch";
			
			$iDispatchId = regedit::getInstance()->getVal("//modules/forum/dispatch_id");
			
			if(!$iDispatchId) return false;
			
			$dispatches_module = cmsController::getInstance()->getModule('dispatches');
			
			if(!$dispatches_module) {
					return false;
			}
		
			$iHierarchyTypeId = umiHierarchyTypesCollection::getInstance()->getTypeByName("dispatches", "message")->getId();
			$iMsgTypeId =  umiObjectTypesCollection::getInstance()->getTypeByHierarchyTypeId($iHierarchyTypeId);
			$oMsgType = umiObjectTypesCollection::getInstance()->getType($iMsgTypeId);
			$iMsgObjId = umiObjectsCollection::getInstance()->addObject($sTitle, $iMsgTypeId);
			
			$oMsgObj = umiObjectsCollection::getInstance()->getObject($iMsgObjId);
			if ($oMsgObj instanceof umiObject) {
				$iReleaseId = $dispatches_module->getNewReleaseInstanceId($iDispatchId);
				
				$body = $dispatches_module->getAllChanges();
				$body = templater::getInstance()->parseInput($body);
				
				$oMsgObj->setValue('release_reference', $iReleaseId);
				$oMsgObj->setValue('header', $sTitle);
				$oMsgObj->setValue('body', $body);
				$oMsgObj->commit();
				
				return true;
			} else {
					return false;
			}
		}
	};
?>