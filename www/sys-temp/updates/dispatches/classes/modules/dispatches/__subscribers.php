<?php
	abstract class __subscribers_subscribers {
		public function subscribers_list() {
			// input
			$iDispId = $_REQUEST['param0'];
			// set tab
			$this->sheets_set_active("subscribers_list");
			//input:
			$this->load_forms();

			$iCurrPage = (int) $_REQUEST['p'];

			$params = array();
			// gen banners list
			$params['rows'] = "";
			//
			$oSbsSelection = new umiSelection;
			$oSbsSelection->setObjectTypeFilter();
			$oSbsSelection->setLimitFilter();
			$oSbsSelection->addLimit($this->per_page, $iCurrPage);

			$iHierarchyTypeId = umiHierarchyTypesCollection::getInstance()->getTypeByName("dispatches", "subscriber")->getId();
			$iSbsTypeId =  umiObjectTypesCollection::getInstance()->getTypeByHierarchyTypeId($iHierarchyTypeId);
			$oSbsType = umiObjectTypesCollection::getInstance()->getType($iSbsTypeId);
			$oSbsSelection->addObjectType($iSbsTypeId);
			// add curr dispatch filter
			if ($iDispId) {
				$oDispObj = umiObjectsCollection::getInstance()->getObject($iDispId);
				if ($oDispObj instanceof umiObject) {
					$oSbsSelection->setPropertyFilter();
					$oSbsSelection->addPropertyFilterEqual($oSbsType->getFieldId('subscriber_dispatches'), $iDispId);
				}
			}
			$arrSelResults = umiSelectionsParser::runSelection($oSbsSelection);
			$iCountResults = umiSelectionsParser::runSelectionCounts($oSbsSelection);

			$iSbsNum = $this->per_page*$iCurrPage;
			for ($iI=0; $iI<count($arrSelResults); $iI++) {
				$params['rows'] .= self::renderSubscriber($arrSelResults[$iI], ++$iSbsNum);
			}

			$params['pages'] = $this->generateNumPage($iCountResults, $this->per_page, $iCurrPage);

			return $this->parse_form("subscribers_list", $params);
		}

		private function renderSubscriber($iSbsId, $iSbsNum=0) {
			$sResult = "";
			$oSubscriber =  umiObjectsCollection::getInstance()->getObject($iSbsId);

			$oSubscriber = new umiSubscriber($iSbsId);

			$params = array();
			if ($oSubscriber instanceof umiSubscriber) {
				$params['sbs_name'] = $oSubscriber->getValue('lname')." ".$oSubscriber->getValue('fname')." ".$oSubscriber->getValue('father_name')." (".$oSubscriber->getName().")";
				if ($iSbsNum>0) {
					$params['sbs_num'] = $iSbsNum;
				}
				$arrDispatches = $oSubscriber->getValue('subscriber_dispatches');
				$sDispNames = "";
				if (is_array($arrDispatches) && count($arrDispatches)) {
					for ($iI=0; $iI<count($arrDispatches); $iI++) {
						$oNextDisp = umiObjectsCollection::getInstance()->getObject($arrDispatches[$iI]);
						$sDispNames .= "<a href=\"{$this->pre_lang}/admin/dispatches/dispatch_edit/{$arrDispatches[$iI]}/\">".$oNextDisp->getName()."</a>";
						if ($iI < count($arrDispatches)-1) $sDispNames .= ", ";
					}
				} else {
					$sDispNames = "<span style='color:red;'>нет</span>";
				}
				$params['sbs_dispatches'] = $sDispNames;
				$params['sbs_status'] = ($oSubscriber->getValue('uid') && umiObjectsCollection::getInstance()->getObject($oSubscriber->getValue('uid')) ? "зарегистрированный пользователь": "Гость");
				$oSbsDate = $oSubscriber->getValue('subscribe_date');
				$sSbsDate = "";
				if ($oSbsDate instanceof umiDate && $oSbsDate->timestamp) {
					$sSbsDate = $oSbsDate->getFormattedDate("d.m.Y H:m");
				}
				$params['sbs_date'] = $sSbsDate;
				$params['sbs_id'] = $oSubscriber->getId();
				$sResult = $this->parse_form("subscribers_list_row", $params);
			}
			return $sResult;
		}

		public function subscriber_del() {
			$iSbsId = $_REQUEST['param0'];
			umiObjectsCollection::getInstance()->delObject($iSbsId);
			$this->redirect($this->pre_lang . "/admin/dispatches/subscribers_list/");
		}
	};
?>