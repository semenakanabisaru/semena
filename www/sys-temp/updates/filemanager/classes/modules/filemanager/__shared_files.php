<?php

	abstract class __shared_files extends baseModuleAdmin {
		public function shared_files() {
			$this->setDataType("list");
			$this->setActionType("view");
			if($this->ifNotXmlMode()) return $this->doData();
			
			$per_page = 20;
			$curr_page = getRequest('p');

			$hierarchyTypes = umiHierarchyTypesCollection::getInstance();
			$hierarchy_type_id = $hierarchyTypes->getTypeByName("filemanager", "shared_file")->getId();

			$sel = new umiSelection;
			$sel->addLimit($per_page, $curr_page);
			$sel->addElementType($hierarchy_type_id);
			$this->autoDetectAllFilters($sel);
			$sel->addPermissions();

			$result = umiSelectionsParser::runSelection($sel);
			$total = umiSelectionsParser::runSelectionCounts($sel);
			
			$this->setDataType("list");
			$this->setActionType("view");
			$this->setDataRange($per_page, $curr_page * $per_page);
			
			$data = $this->prepareData($result, "pages");
			
			$this->setData($data, $total);
			return $this->doData();
		}
		
		
		public function shared_file_activity() {
			$elements = getRequest('element');
			if(!is_array($elements)) {
				$elements = Array($elements);
			}
			$is_active = getRequest('active');
			
			foreach($elements as $elementId) {
				$element = $this->expectElement($elementId, false, true);
				
				$params = Array(
					"element" => $element,
					"allowed-element-types" => Array('shared_file'),
					"activity" => $is_active
				);
				
				$this->switchActivity($params);
				$element->commit();
			}
			
			$this->setDataType("list");
			$this->setActionType("view");
			$data = $this->prepareData($elements, "pages");
			$this->setData($data);

			return $this->doData();
		}

		
		public function add_shared_file() {
			$type = "shared_file";
			$mode = (string) getRequest("param0");
			$file = getRequest("file");
			$inputData = Array(	"type" => $type,
						"parent" => 0,
						"allowed-element-types" => Array('shared_file'));

			if($mode == "do") {

				$element_id = $this->saveAddedElementData($inputData);
				$element = umiHierarchy::getInstance()->getElement($element_id);
				
				if(getRequest("select_fs_file")) {
					$sFileName = getRequest("fs_dest_folder") . "/" . getRequest("select_fs_file");
					$oFile = new umiFile($sFileName);
					$element->setValue("fs_file", $oFile);
					$element->commit();
				}
				$this->chooseRedirect();
			}

			$this->setDataType("form");
			$this->setActionType("create");

			$data = $this->prepareData($inputData, "page");

			$this->setData($data);
			return $this->doData();
		}
		
		
		public function edit_shared_file() {
			$element = $this->expectElement("param0");
			$mode = (String) getRequest('param1');
			
			$inputData = Array(
				"element" => $element,
				"allowed-element-types" => Array('shared_file')
			);
			
			if($mode == "do") {
				$object = $this->saveEditedElementData($inputData);
				$this->chooseRedirect();
			}

			$this->setDataType("form");
			$this->setActionType("modify");
			
			$data = $this->prepareData($inputData, "page");

			$this->setData($data);
			return $this->doData();
		}
		
		public function del_shared_file() {
		    $elements = getRequest('element');
		    if(!is_array($elements)) {
		        $elements = Array($elements);
	        }
			
			foreach($elements as $elementId) {
			    $element = $this->expectElement($elementId, false, true);
			
    			$params = Array(
    				"element" => $element,
    				"allowed-element-types" => Array('shared_file')
    			);
    			$this->deleteElement($params);
			}
			
			$this->setDataType("list");
			$this->setActionType("view");
			$data = $this->prepareData($elements, "pages");
			$this->setData($data);

			return $this->doData();
		}
	};
?>