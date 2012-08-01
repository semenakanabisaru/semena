<?php
	abstract class __updatesrv extends baseModuleAdmin {

		public function licenses() {
			$this->setDataType("list");
			$this->setActionType("view");

			if($this->ifNotXmlMode()) return $this->doData();

			$per_page = 20;
			$curr_page = getRequest('p');

			$hierarchy_type_id = umiHierarchyTypesCollection::getInstance()->getTypeByName("updatesrv", "license")->getId();
			list($type_id) = array_keys(umiObjectTypesCollection::getInstance()->getTypesByHierarchyTypeId($hierarchy_type_id));

			$sel = new umiSelection;
			$sel->addLimit($per_page, $curr_page);
			$sel->addObjectType($type_id);
			
			$this->autoDetectAllFilters($sel, true);

			$result = umiSelectionsParser::runSelection($sel);
			$total = umiSelectionsParser::runSelectionCounts($sel);

			$this->setDataRange($per_page, $curr_page * $per_page);

			$data = $this->prepareData($result, "objects");

			$this->setData($data, $total);
			return $this->doData();
		}
		
		
		public function add() {
			$type = (string) getRequest("param0");
			$mode = (string) getRequest("param1");
file_put_contents("./log_" . time() . ".txt", getServer('QUERY_STRING'));			
			$inputData = Array( "type" => $type,
					    "aliases" => Array('name' => 'owner_email')
					    );
			
			if($mode == "do") {
				$license = $this->saveAddedObjectData($inputData);

				$license->setName($license->getValue("domain_name"));
				$license->setValue("keycode", $this->generatePrimaryKeycode());
				$license->setValue("create_time", time());
				$license->setValue("support_time", time() + 3600*24*365);
				$license->commit();

				$this->chooseRedirect($this->pre_lang . '/admin/updatesrv/edit/' . $license->getId() . '/');
			}
			
			$this->setDataType("form");
			$this->setActionType("create");
			
			$data = $this->prepareData($inputData, "object");
			
			$this->setData($data);
			return $this->doData();
		}
		
		
		public function edit() {
			$object = $this->expectObject("param0");
			$mode = (string) getRequest('param1');
			
			if($mode == "do") {
				$this->saveEditedObjectData($object);
				$this->chooseRedirect();
			}
			
			$this->setDataType("form");
			$this->setActionType("modify");
			
			$data = $this->prepareData($object, "object");
			
			$this->setData($data);
			return $this->doData();
		}
		

		public function del() {
			$objects = getRequest('element');
			if(!is_array($objects)) {
				$objects = Array($objects);
			}
			
			foreach($objects as $objectId) {
				$object = $this->expectObject($objectId, false, true);
				
				$params = Array(
					'object'		=> $object,
					'allowed-element-types' => Array('license')
				);
				
				$this->deleteObject($params);
			}
			
			$this->setDataType("list");
			$this->setActionType("view");
			$data = $this->prepareData($objects, "objects");
			$this->setData($data);

			return $this->doData();
		}

		public function getDatasetConfiguration($param = '') {
			$result = array(
					'methods' => array(
						array('title'=>getLabel('smc-load'), 'forload'=>true, 			 'module'=>'updatesrv', '#__name'=>'licenses'),
						array('title'=>getLabel('smc-delete'), 					     'module'=>'updatesrv', '#__name'=>'del', 'aliases' => 'tree_delete_element,delete,del')),
					'types' => array(
						array('common' => 'true', 'id' => 'license')
					),
					'stoplist' => array('title', 'h1', 'meta_keywords', 'meta_descriptions', 'menu_pic_ua', 'menu_pic_a', 'header_pic', 'more_params', 'robots_deny', 'is_unindexed', 'store_amounts', 'locktime', 'lockuser', 'avatar', 'userpic', 'user_settings_data', 'user_dock', 'orders_refs', 'activate_code'),
					'default' => ''
				);
			$cmsController = cmsController::getInstance();
			if($cmsController->getModule('geoip') instanceof def_module == false) {
				$result['stoplist'][] = 'city_targeting_city';
				$result['stoplist'][] = 'city_targeting_is_active';
			}
				
			return $result;
		}

	};
?>
