<?php

abstract class __guides_data extends baseModuleAdmin {
	public $per_page = 50;

	public function guides() {
		$objectTypes = umiObjectTypesCollection::getInstance();

		if(isset($_REQUEST['search-all-text'][0])) {
			$searchAllText = array_extract_values($_REQUEST['search-all-text']);
			foreach($searchAllText as $i => $v) {
				$searchAllText[$i] = wa_strtolower($v);
			}
		} else {
			$searchAllText = false;
		}

		$rel = umiObjectTypesCollection::getInstance()->getTypeIdByGUID('root-guides-type');
		if(($rels = getRequest('rel')) && sizeof($rels)) {
			$rel = getArrayKey($rels, 0);
		}

		$curr_page = (int) getRequest('p');
		$per_page = 20;

		$types = umiObjectTypesCollection::getInstance();
		$guides_list = $types->getGuidesList(true, $rel);

		$tmp = Array();
		foreach($guides_list as $typeId => $name) {
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

		if(isset($_REQUEST['order_filter']['name'])) {
			natsort($tmp);
			if($_REQUEST['order_filter']['name'] == "desc") {
				$tmp = array_reverse($tmp, true);
			}
		}
		$guides_list = array_keys($tmp);
		unset($tmp);
		$guides_list = $this->excludeNestedTypes($guides_list);

		$total = sizeof($guides_list);
		$guides = array_slice($guides_list, $per_page * $curr_page, $per_page);

		$this->setDataType("list");
		$this->setActionType("view");
		$this->setDataRange($per_page, $curr_page * $per_page);

		$data = $this->prepareData($guides, "types");
		$this->setData($data, $total);
		return $this->doData();
	}

	public function guide_items($guide_id = false, $per_page = false, $curr_page = 0) {
		$this->setDataType("list");
		$this->setActionType("modify");

		if(!$curr_page) $curr_page = (int) getRequest('p');
		if(!$per_page) $per_page = $this->per_page;
		if(!$guide_id) $guide_id = (int) getRequest('param0');
		$mode = (string) getRequest('param1');

		if($guide = selector::get('object-type')->id($guide_id)) {
			$this->setHeaderLabel(getLabel('header-data-guide_items') . ' "' . $guide->getName() . '"');
		}
		if($this->ifNotXmlMode()) return $this->doData();

		$sel = new selector('objects');
		$sel->types('object-type')->id($guide_id);
		$sel->limit($per_page * $curr_page, $per_page);

		selectorHelper::detectFilters($sel);

		if($mode == "do") {
			$params = array(
				"type_id" => $guide_id
			);
			$this->saveEditedList("objects", $params);
			$this->chooseRedirect();
		}

		$this->setDataRange($per_page, $curr_page * $per_page);
		$data = $this->prepareData($sel->result, "objects");
		$this->setData($data, $sel->total);
		return $this->doData();
	}

	public function guide_item_add() {
		$type = (int) getRequest('param0');
		$mode = (string) getRequest('param1');

		$inputData = Array("type-id" => $type);

		if($mode == "do") {
			$object = $this->saveAddedObjectData($inputData);
			$this->chooseRedirect($this->pre_lang . '/admin/data/guide_item_edit/' . $object->getId() . '/');
		} else if ($mode == 'fast') {
			$objects = umiObjectsCollection::getINstance();
			try {
				$objects->addObject(null, $type);
			} catch(fieldRestrictionException $e) {}
		}

		$this->setDataType("form");
		$this->setActionType("create");

		$data = $this->prepareData($inputData, "object");

		$this->setData($data);
		return $this->doData();
	}

	public function guide_item_edit() {
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

	public function guide_item_del() {
		$objects = getRequest('element');
			if(!is_array($objects)) {
				$objects = Array($objects);
			}

			foreach($objects as $objectId) {
				$object = $this->expectObject($objectId, false, true);
				$params = array('object' => $object);
				$this->deleteObject($params);
			}

			$this->setDataType("list");
			$this->setActionType("view");
			$data = $this->prepareData($objects, "objects");
			$this->setData($data);

			return $this->doData();
	}


	public function guide_del() {
		$type_id = (int) getRequest('param0');
		umiObjectTypesCollection::getInstance()->delType($type_id);
		$this->redirect($this->pre_lang . "/admin/data/guides/");
	}

	public function guide_add() {

		$objectTypes = umiObjectTypesCollection::getInstance();

		$parent_type_id = (int) $this->expectObjectTypeId('param0');
		if($parent_type_id == 0) $parent_type_id = $objectTypes->getTypeIdByGUID('root-guides-type');

		$type_id = $objectTypes->addType($parent_type_id, "i18n::object-type-new-guide");
		$type = $objectTypes->getType($type_id);
		$type->setIsPublic(true);
		$type->setIsGuidable(true);
		$type->commit();

		$this->redirect($this->pre_lang . "/admin/data/type_edit/" . $type_id . "/");
	}

	public function guide_items_all() {
		$this->setDataType("list");
		$this->setActionType("modify");
		if($this->ifNotXmlMode()) return $this->doData();

		$per_page = $this->per_page;
		$guide_id = (int) getRequest('param0');

		$sel = new selector('objects');
		$sel->types('object-type')->id($guide_id);

		$maxItemsCount = (int) mainConfiguration::getInstance()->get("kernel", "max-guided-items");

		if ($maxItemsCount && $maxItemsCount <= 15 && $maxItemsCount > 0) {
			$maxItemsCount = 16;
		} elseif ($maxItemsCount <= 0) {
			$maxItemsCount = 50;
		}

		if ($textSearch = getRequest('search')) {
			foreach($textSearch as $searchString)
				$sel->where('name')->like('%' . $searchString . '%');
		}

		if (!permissionsCollection::getInstance()->isSv()) {
			$sel->where('guid')->notequals('users-users-15');
		}

		if(!is_null(getRequest('limit'))) {
			$sel->limit((15 * (int) getRequest('p')), 15);
		}

		$guide_items = $sel->result;

		$total = $sel->length;
		if(!is_null(getRequest('allow-empty')) && $total > $maxItemsCount) {
			$data = Array(
				'empty' => Array(
					'attribute:total'  => $total,
					'attribute:result' => 'Too much items'
				)
			);
			$this->setDataRange(0, 0);
			$this->setData($data, $total);
			return $this->doData();
		}

		if(!intval(regedit::getInstance()->getVal("//settings/ignore_guides_sort"))) {
			$tmp = Array();
			foreach($guide_items as $item) {
				if ($item instanceof umiObject) {
					$tmp[$item->getId()] = $item->getName();
				}
			}
			natsort($tmp);
			$guide_items = array_keys($tmp);
			unset($tmp);
		}

		$this->setDataRangeByPerPage($maxItemsCount, 0);
		$data = $this->prepareData($guide_items, "objects");
		$this->setData($data, $total);
		return $this->doData();
	}
};

?>
