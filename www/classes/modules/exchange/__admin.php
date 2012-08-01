<?php

	abstract class __exchange extends baseModuleAdmin {

		public function import() {
			$this->setDataType("list");
			$this->setActionType("view");

			$limit = 20;
			$curr_page = (int) getRequest('p');
			$offset = $limit * $curr_page;

			$sel = new selector('objects');
			$sel->types('object-type')->name('exchange', 'import');
			$sel->limit($offset, $limit);

			selectorHelper::detectFilters($sel);

			$this->setDataRange($limit, $offset);
			$data = $this->prepareData($sel->result, "objects");

			$this->setData($data, $sel->length);
			return $this->doData();
		}

		public function export() {
			$this->setDataType("list");
			$this->setActionType("view");

			$limit = 20;
			$curr_page = (int) getRequest('p');
			$offset = $limit * $curr_page;

			$sel = new selector('objects');
			$sel->types('object-type')->name('exchange', 'export');
			$sel->limit($offset, $limit);

			selectorHelper::detectFilters($sel);

			$this->setDataRange($limit, $offset);
			$data = $this->prepareData($sel->result, "objects");

			$this->setData($data, $sel->length);
			return $this->doData();
		}


		public function add() {
			$type = (string) getRequest('param0');
			$mode = (string) getRequest('param1');

			$this->setHeaderLabel("header-exchange-add-" . $type);
			$inputData = array(
				'type'					=> $type,
				'allowed-element-types'	=> array('import', 'export')
			);

			if($mode == "do") {
				$object = $this->saveAddedObjectData($inputData);
				$this->getElementsToExport($object->getId());
				$this->chooseRedirect($this->pre_lang . '/admin/exchange/edit/' . $object->getId() . '/');
			}

			$this->setDataType("form");
			$this->setActionType("create");

			$data = $this->prepareData($inputData, "object");

			$this->setData($data);
			return $this->doData();
		}

		public function edit() {
			$object = $this->expectObject("param0", true);
			$mode = (string) getRequest('param1');
			$objectId = $object->getId();

			$this->setHeaderLabel("header-exchange-edit-" . $this->getObjectTypeMethod($object));

			$inputData = Array("object"	=> $object,
				"allowed-element-types"	=> Array('import', 'export')
			);

			if($mode == "do") {
				$object = $this->saveEditedObjectData($inputData);
				$this->getElementsToExport($objectId);
				$objectId = $object->getId();
				$this->chooseRedirect();
			}

			$this->setDataType("form");
			$this->setActionType("modify");

			$data = $this->prepareData($inputData, "object");

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
					'allowed-element-types' => Array('import', 'export')
				);

				$this->deleteObject($params);
			}

			$this->setDataType("list");
			$this->setActionType("view");
			$data = $this->prepareData($objects, "objects");
			$this->setData($data);

			return $this->doData();
		}

		public function getElementsToExport($objectId) {

			$objects = umiObjectsCollection::getInstance();
			$object = $objects->getObject($objectId);
			$format_id = $object->format;
			$exportFormat = $objects->getObject($format_id);
			if (!$exportFormat instanceof umiObject) {
				throw new publicException(getLabel("exchange-err-format_undefined"));
			}
			$suffix = $exportFormat->sid;
			if($suffix == 'YML') {

				$dirName = CURRENT_WORKING_DIR . "/sys-temp/yml/";
				if (!is_dir($dirName)) mkdir($dirName, 0777, true);

				$objectId = $object->getId();
				$array = $dirName . $objectId . 'el';
				$array2 = $dirName . $objectId . 'cat';
				if(file_exists($array)) unlink($array);
				if(file_exists($array2)) unlink($array2);

				$elements = $object->elements;

				if (!count($elements)) {
					$sel = new selector('pages');
					$sel->where('hierarchy')->page(0)->childs(1);
					$elements = $sel->result;
				}

				$elementsToExport = $this->getArrayToExport($elements);
				file_put_contents($array, serialize($elementsToExport));

				$parentsToExport = $this->getParentArrayToExport($elements);
				file_put_contents($array2, serialize($parentsToExport));
			}
		}

		public function getArrayToExport($elements) {
			$elementsToExport = array();

			foreach($elements as $element) {
				if(!$element instanceof umiHierarchyElement) continue;
				$sel = new selector('pages');
				$sel->types('hierarchy-type')->name('catalog', 'object');
				$sel->option('return')->value('id');
				$sel->where('hierarchy')->page($element->getId())->childs(100);
				foreach($sel->result() as $res) {
					$elementsToExport[] = $res['id'];
				}
				$elementsToExport[] = $element->getId();
			}
			$elementsToExport = array_unique($elementsToExport);
			sort($elementsToExport);
			return $elementsToExport;
		}

		public function getParentArrayToExport($elements) {
			$elementsToExport = array();
			$hierarchy = umiHierarchy::getInstance();
			foreach ($elements as $el) {
				if ($el instanceof umiHierarchyElement) {
					$id = $el->getId();
					$elementsToExport[] = $id;
					}
				}
			foreach ($elementsToExport as $key => $id) {
				$parents = $hierarchy->getAllParents($id, false, true);
				if (count(array_intersect($elementsToExport, $parents))) {
					unset($elementsToExport[$key]);
				}
			}
			$elementsToExport = array_unique($elementsToExport);
			sort($elementsToExport);
			return $elementsToExport;
		}

		public function getDatasetConfiguration($param = '') {
			switch($param) {
				case 'export' :
					$loadMethod = 'export';
					$delMethod  = 'del';
					$typeId		= umiObjectTypesCollection::getInstance()->getBaseType('exchange', 'export');
					$defaults	= 'format[200px]';
					break;
				default:
					$loadMethod = 'import';
					$delMethod  = 'del';
					$typeId		= umiObjectTypesCollection::getInstance()->getBaseType('exchange', 'import');
					$defaults	= 'format[200px]';
					break;
			}

			return array(
					'methods' => array(
						array('title'=>getLabel('smc-load'), 'forload'=>true, 'module'=>'exchange', '#__name'=>$loadMethod),
						array('title'=>getLabel('smc-delete'), 				  'module'=>'exchange', '#__name'=>'del', 'aliases' => 'tree_delete_element,delete,del')
					),
					'types' => array(
						array('common' => 'true', 'id' => $typeId)
					),
					'stoplist' => array(''),
					'default' => $defaults
			);
		}

	}



?>