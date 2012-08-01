<?php
	abstract class __data extends baseModuleAdmin {

		public function config() {
			$mode = getRequest("param0");

			if($mode == "do") {
				$this->saveEditedList("basetypes");
				$this->chooseRedirect();
			}

			$hierarchy_types = umiHierarchyTypesCollection::getInstance()->getTypesList();
			
			$this->setDataType("list");
			$this->setActionType("modify");
			$data = $this->prepareData($hierarchy_types, "hierarchy_types");
			$this->setData($data, sizeof($hierarchy_types));
			return $this->doData();
		}


		public function types() {
			$curr_page = (int) getRequest('p');
			$per_page = 50;

			if(isset($_REQUEST['rel'][0])) {
				$parent_type_id = $this->expectObjectTypeId($_REQUEST['rel'][0], false, true);
			} else {
				$parent_type_id = $this->expectObjectTypeId('param0');
			}
			
			if(isset($_REQUEST['search-all-text'][0])) {
				$searchAllText = array_extract_values($_REQUEST['search-all-text']);
				foreach($searchAllText as $i => $v) {
					$searchAllText[$i] = wa_strtolower($v);
				}
			} else {
				$searchAllText = false;
			}

			$params = Array();

			$types = umiObjectTypesCollection::getInstance();
			if($searchAllText && !$parent_type_id) {
				$sub_types = $types->getChildClasses(0);
			} else {
				$sub_types = $types->getSubTypesList($parent_type_id);
			}
			
			$tmp = Array();
			foreach($sub_types as $typeId) {
				$type = $types->getType($typeId);
				if($type instanceof umiObjectType) {
					$name = $type->getName();
					
					if($searchAllText) {
						$match = false;
						foreach($searchAllText as $searchString) {
							if(strstr(wa_strtolower($name), $searchString) !== false) {
								$match = true;
							}
						}
						if(!$match) {
							continue;
						}
					}
					$tmp[$typeId] = $name;
				}
			}
			
			if(isset($_REQUEST['order_filter']['name'])) {
				natsort($tmp);
				if($_REQUEST['order_filter']['name'] == "desc") {
					$tmp = array_reverse($tmp, true);
				}
			}
			
			$sub_types = array_keys($tmp);
			unset($tmp);
			$sub_types = $this->excludeNestedTypes($sub_types);
			
			$total = sizeof($sub_types);
	    	$sub_types = array_slice($sub_types, $curr_page * $per_page, $per_page, false);
		
			$this->setDataType("list");
			$this->setActionType("view");
			$this->setDataRange($per_page, $curr_page * $per_page);
		
			$data = $this->prepareData($sub_types, "types");
			$this->setData($data, $total);
			return $this->doData();
		}


		public function type_add() {
			$parent_type_id = (int) $this->expectObjectTypeId('param0');

			$objectTypes = umiObjectTypesCollection::getInstance();
			$type_id = $objectTypes->addType($parent_type_id, "i18n::object-type-new-data-type");

			$this->redirect($this->pre_lang . "/admin/data/type_edit/" . $type_id . "/");
		}


		public function type_edit() {
			$type = $this->expectObjectType('param0');
			
			$mode = (String) getRequest('param1');
			
			if($mode == "do") {
				$this->saveEditedTypeData($type);
				$this->chooseRedirect();
			}
			
			$this->setDataType("form");
			$this->setActionType("modify");
			
			$data = $this->prepareData($type, "type");
			
			$this->setData($data);
			return $this->doData();
		}
		
		
		public function type_field_add($redirectString = false) {
			$group_id = (int) getRequest('param0');
			$type_id = (int) getRequest('param1');
			$mode = (string) getRequest('param2');
			
			$inputData = Array("group-id" => $group_id, "type-id" => $type_id);
			
			if($mode == "do") {
				$field_id = $this->saveAddedFieldData($inputData);
				if(getRequest('noredirect')) { 
					$field = umiFieldsCollection::getInstance()->getField($field_id);
					$this->setDataType("form");
					$this->setActionType("modify");
					$data = $this->prepareData($field, "field");
					$this->setData($data);
					return $this->doData();					
				} else {
					$this->chooseRedirect(($redirectString ? $redirectString : ($this->pre_lang . '/admin/data/type_field_edit/')) . $field_id . '/' . $type_id . '/');
				}
			}
			
			$this->setDataType("form");
			$this->setActionType("create");
			
			$data = $this->prepareData($inputData, "field");
			
			$this->setData($data);
			return $this->doData();
		}


		public function type_field_edit() {
			$field_id = (int) getRequest('param0');
			$type_id = (int) getRequest('param1');
			$mode = (string) getRequest('param2');

			$field = umiFieldsCollection::getInstance()->getField($field_id);

			if($mode == "do") {
				$this->saveEditedFieldData($field);
				if(!getRequest('noredirect')) {
					$this->chooseRedirect();
				}
			}

			$this->setDataType("form");
			$this->setActionType("modify");
			
			$data = $this->prepareData($field, "field");
			
			$this->setData($data);
			return $this->doData();

		}



		public function type_group_edit() {
			$group_id = (int) getRequest('param0');
			$type_id = (int) getRequest('param1');
			$mode = (string) getRequest('param2');

			$group = umiObjectTypesCollection::getInstance()->getType($type_id)->getFieldsGroup($group_id);

			if($mode == "do") {
				$this->saveEditedGroupData($group);
				$this->chooseRedirect();
			}
			
			$this->setDataType("form");
			$this->setActionType("modify");
			
			$data = $this->prepareData($group, "group");
			
			$this->setData($data);
			return $this->doData();
		}


		public function type_group_add($redirectString = false) {
			$type_id = (int) getRequest('param0');
			$mode = (string) getRequest('param1');
			
			$inputData = Array("type-id" => $type_id);
			
			if($mode == "do") {
				$fields_group_id = $this->saveAddedGroupData($inputData);
				if(getRequest('noredirect')) {
					$group = umiObjectTypesCollection::getInstance()->getType($type_id)->getFieldsGroup($fields_group_id);
					$this->setDataType("form");
					$this->setActionType("modify");
					$data = $this->prepareData($group, "group");
					$this->setData($data);
					return $this->doData();
				} else {
					$this->chooseRedirect(($redirectString ? $redirectString : ($this->pre_lang . '/admin/data/type_group_edit/')) . $fields_group_id . '/' . $type_id . '/');
				}
			}
			
			$this->setDataType("form");
			$this->setActionType("create");
			
			$data = $this->prepareData($inputData, "group");
			
			$this->setData($data);
			return $this->doData();
		}

		public function type_del() {
			$types = getRequest('element');
			if(!is_array($types)) {
				$types = Array($types);
			}
			
			foreach($types as $typeId) {
				$d = $this->expectObjectTypeId($typeId, true, true);
				umiObjectTypesCollection::getInstance()->delType($typeId);
			}
			
			$this->setDataType("list");
			$this->setActionType("view");
			$data = $this->prepareData($types, "types");
			$this->setData($data);

			return $this->doData();
		}
		
		
		public function getEditLink($type_id) {
			$link_add = false;
			$link_edit = $this->pre_lang . "/admin/data/type_edit/{$type_id}/";
			return Array($link_add, $link_edit);
		}
		
		
		
		public function getDatasetConfiguration($param = '') {
			$deleteMethod = 'type_del';
			if($param == "guides") {
				$loadMethod = "guides";
			} else if ($param == "trash") {
				$loadMethod   = 'trash';
				$deleteMethod = 'trash_del';
			} else if (is_numeric($param)) {
				$loadMethod = "guide_items/" . $param;
				
				return array(
					'methods' => array(
						array('title'=>getLabel('smc-load'), 'forload'=>true, 			 'module'=>'data', '#__name'=>$loadMethod),
						array('title'=>getLabel('smc-delete'), 					     'module'=>'data', '#__name'=>'guide_item_del', 'aliases' => 'tree_delete_element,delete,del')),
					'types' => array(
						array('common' => 'true', 'id' => $param)
					)
				);
			} else {
				$loadMethod = "types";
			}

			$p = array(
					'methods' => array(
						array('title'=>getLabel('smc-load'), 'forload'=>true, 			 'module'=>'data', '#__name'=>$loadMethod),
						array('title'=>getLabel('smc-delete'), 					     'module'=>'data', '#__name'=>$deleteMethod, 'aliases' => 'tree_delete_element,delete,del')
						)
				);

			if ($param == "trash") {
				$p['methods'][] = array('title'=>getLabel('smc-restore'),  'module'=>'data', '#__name'=>'trash_restore', 'aliases' => 'restore_element');
			}
			
			return $p;
		}
		
		public function getObjectTypeEditLink($typeId) {
			return Array(
				'create-link' => $this->pre_lang . "/admin/data/type_add/" . $typeId . "/",
				'edit-link' => $this->pre_lang . "/admin/data/type_edit/" . $typeId . "/"
			);
		}

		public function excludeNestedTypes($arr) {
			$objectTypes = umiObjectTypesCollection::getInstance();
			
			$result = Array();
			foreach($arr as $typeId) {
				$type = $objectTypes->getType($typeId);
				if($type instanceof umiObjectType) {
					if(in_array($type->getParentId(), $arr)) {
						continue;
					} else {
						$result[] = $typeId;
					}
				}
			}
			return $result;
		}
		
		public function getObjectsByTypeId($objectTypeId) {
			$objects = umiObjectsCollection::getInstance();
			
			$sel = new umiSelection;
			$sel->addObjectType($objectTypeId);
			$result = umiSelectionsParser::runSelection($sel);
			
			$items_arr = Array();
			foreach($result as $objectId) {
				$object = $objects->getObject($objectId);
				if($object instanceof umiObject) {
					$items_arr[] = $object;
				}
			}
			$block_arr = Array("items" => Array("nodes:item" => $items_arr));
			return $block_arr;
		}
	};
?>