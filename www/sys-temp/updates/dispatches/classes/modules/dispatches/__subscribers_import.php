<?php
	abstract class __subscribers_import_dispatches extends baseModuleAdmin {

		public function subscribers_import() {
			$mode = (string) getRequest('param0');
			
			$params = Array(
				'options' => Array(
						'file:csvfile' => Array('value' => NULL, 'destination-folder' => './files/')
					)
			);
			
			if($mode == "do") {
				$this->subscribers_import_do();
			}

			$this->setDataType('settings');
			$this->setActionType('modify');
			
			$data = $this->prepareData($params, 'settings');
			
			$this->setData($data);
			return $this->doData();
		}
		
		public function subscribers_import_do() {
			$select_csvfile = getRequest('select_csvfile');
			if(!($csvfile = umiFile::upload("data", "csvfile", "./files/"))) $csvfile = new umiFile("./files/" . $select_csvfile);
			
			if($filepath = $csvfile->getFilePath()) {
				$csv = file_get_contents($filepath);
				$csv = iconv("CP1251", "UTF-8//IGNORE", $csv);
				$csv_arr = explode("\n", $csv);
				
				foreach($csv_arr as $csv_line) {
					$arr = explode(";", $csv_line);
					
					if(sizeof($arr) < 2) continue;
					
					list($email, $fname) = $arr;
					$lname = (isset($arr[2])) ? $arr[2] : false;
					$this->import_subscriber($email, $fname, $lname);
				}
			}
			
			$this->redirect($this->pre_lang . "/admin/dispatches/subscribers/");
		}
		
		
		public function import_subscriber ($email, $fname, $lname) {
			if(!$email) return false;

			$oSbsSelection = new umiSelection;
			$oSbsSelection->setObjectTypeFilter();
			$iSbsHierarchyTypeId = umiHierarchyTypesCollection::getInstance()->getTypeByName("dispatches", "subscriber")->getId();
			$iSbsTypeId =  umiObjectTypesCollection::getInstance()->getTypeByHierarchyTypeId($iSbsHierarchyTypeId);
			$oSbsType = umiObjectTypesCollection::getInstance()->getType($iSbsTypeId);
			$oSbsSelection->addObjectType($iSbsTypeId);
			$oSbsSelection->setNamesFilter();
			$oSbsSelection->addNameFilterEquals($email);
			$arrSbsSelResults = umiSelectionsParser::runSelection($oSbsSelection);
			
			if(sizeof($arrSbsSelResults)) {
				list($object_id) = $arrSbsSelResults;
			} else {
				$object_id = umiObjectsCollection::getInstance()->addObject($email, $iSbsTypeId);
			}
			
			if($oSubscriber = umiObjectsCollection::getInstance()->getObject($object_id)) {
				$oSubscriber->setName($email);
				$oSubscriber->setValue('fname', $fname);
				$oSubscriber->setValue('lname', $lname);
				$oCurrDate = new umiDate(time());
				$oSubscriber->setValue('subscribe_date', $oCurrDate);
				$oSubscriber->setValue('subscriber_dispatches', $this->getAllDispatches());
				$oSubscriber->commit();
			} else {
				return false;
			}

			
			return $object_id;
		}

	};
?>