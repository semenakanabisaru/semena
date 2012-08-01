<?php

class data extends def_module {
	public $alowed_source = Array(
						Array("forum", "topic"),
						Array("forum", "conf"),
						Array("news", "rubric"),
						Array("blogs", "blog"),
						Array("blogs20", "blog")
					);

	public function __construct() {
		parent::__construct();

		if(cmsController::getInstance()->getCurrentMode() == "admin") {
			$commonTabs = $this->getCommonTabs();
			if ($commonTabs) {
				$commonTabs->add("types", array("type_add", "type_edit"));
				$commonTabs->add("guides", array("guide_add", "guide_items", "guide_item_edit", "guide_item_add"));
			}

			$configTabs = $this->getConfigTabs();
			if ($configTabs) {
				$configTabs->add("config");
			}

			$this->__loadLib("__admin.php");
			$this->__implement("__data");

			$this->__loadLib("__json.php");
			$this->__implement("__json_data");

			$this->__loadLib("__trash.php");
			$this->__implement("__trash_data");

			$this->__loadLib("__guides.php");
			$this->__implement("__guides_data");

			$this->__loadLib("__files.php");
			$this->__implement("__files_data");

		} else {
			$this->__loadLib("__rss.php");
			$this->__implement("__rss_data");
		}

		$this->__loadLib("__client_reflection.php");
		$this->__implement("__client_reflection_data");

		$this->__loadLib("__search.php");
		$this->__implement("__search_data");

		$this->__loadLib("__custom.php");
		$this->__implement("__custom_data");
	}


	public function getProperty($element_id, $prop_id, $template = "default", $is_random = false) {
		if(!$template) $template = "default";
		$this->templatesMode('tpl');

		if(!is_numeric($element_id)) {
			$element_id = umiHierarchy::getInstance()->getIdByPath($element_id);
		}

		if($element = umiHierarchy::getInstance()->getElement($element_id)) {
			if($prop = (is_numeric($prop_id)) ? $element->getObject()->getPropById($prop_id) : $element->getObject()->getPropByName($prop_id)) {
				return self::parseTemplate($this->renderProperty($prop, $template, $is_random), Array(), $element_id);
			} else {
				list($template_not_exists) = def_module::loadTemplates("data/".$template, "prop_unknown");
				return $template_not_exists;
			}
		} else {
			list($template_not_exists) = def_module::loadTemplates("data/".$template, "prop_unknown");
			return $template_not_exists;
		}
	}

	public function getPropertyPrice($element_id, $prop_id, $template = "default", $is_random = false) {
		if(!$template) $template = "default";
		$this->templatesMode('tpl');

		if(!is_numeric($element_id)) {
			$element_id = umiHierarchy::getInstance()->getIdByPath($element_id);
		}

		if($element = umiHierarchy::getInstance()->getElement($element_id)) {
			if($prop = (is_numeric($prop_id)) ? $element->getObject()->getPropById($prop_id) : $element->getObject()->getPropByName($prop_id)) {
				return self::parseTemplate($this->renderProperty($prop, $template, $is_random), Array(), $element_id);
			} else {
				list($template_not_exists) = def_module::loadTemplates("data/".$template, "prop_unknown");
				return $template_not_exists;
			}
		} else {
			list($template_not_exists) = def_module::loadTemplates("data/".$template, "prop_unknown");
			return $template_not_exists;
		}
	}

	public function getPropertyGroup($element_id, $group_id, $template = "default") {
		if(!$template) $template = "default";
		$this->templatesMode('tpl');

		if(!is_numeric($element_id)) {
			$element_id = umiHierarchy::getInstance()->getIdByPath($element_id);
		}

		if(strstr($group_id, " ") !== false) {
			$group_ids = explode(" ", $group_id);
			$res = "";
			foreach($group_ids as $group_id) {
				if(!($group_id = trim($group_id))) continue;
				$res .= $this->getPropertyGroup($element_id, $group_id, $template);
			}
			return $res;
		}

		if($element = umiHierarchy::getInstance()->getElement($element_id)) {
			if(!is_numeric($group_id)) $group_id = $element->getObject()->getPropGroupId($group_id);

			$type_id = $element->getObject()->getTypeId();
			if($group = umiObjectTypesCollection::getInstance()->getType($type_id)->getFieldsGroup($group_id)) {
				if($group->getIsActive() == false) return "";
				list($template_block, $template_line) = self::loadTemplates("data/".$template, "group", "group_line");

				$lines = array();
				$props = $element->getObject()->getPropGroupById($group_id);
				$sz = sizeof($props);
				for($i = 0; $i < $sz; $i++) {
					$prop_id = $props[$i];

					if($prop = $element->getObject()->getPropById($prop_id)) {
						if($prop->getIsVisible() === false) {
							continue;
						}
					}

					$line_arr = Array();
					$line_arr['id'] = $element_id;
					$line_arr['prop_id'] = $prop_id;

					if($prop_val = $this->getProperty($element_id, $prop_id, $template)) {
						$line_arr['prop'] = $prop_val;
					} else {
						continue;
					}

					$lines[] = self::parseTemplate($template_line, $line_arr);

				}
				if(!count($lines)) return "";	//TODO: check

				$block_arr = Array();
				$block_arr['name'] = $group->getName();
				$block_arr['title'] = $group->getTitle();
				$block_arr['+lines'] = $lines;
				$block_arr['template'] = $template;

				return self::parseTemplate($template_block, $block_arr);
			} else {
				return "";
			}
		} else {
			return "";
		}

	}


	public function getAllGroups($element_id, $template = "default") {
		if(!$template) $template = "default";
		$this->templatesMode('tpl');

		if(!is_numeric($element_id)) {
			$element_id = umiHierarchy::getInstance()->getIdByPath($element_id);
		}

		if($element = umiHierarchy::getInstance()->getElement($element_id)) {
			list($template_block, $template_line) = self::loadTemplates("data/".$template, "groups_block", "groups_line");

			$block_arr = Array();

			$object_type_id = $element->getObject()->getTypeId();
			$object_type = umiObjectTypesCollection::getInstance()->getType($object_type_id);
			$groups = $object_type->getFieldsGroupsList();

			$lines = array();
			foreach($groups as $group_id => $group) {
				if(!$group->getIsActive() || !$group->getIsVisible()) continue;

				$line_arr = Array();
					 $line_arr['id']         = $element_id;
				$line_arr['group_id']   = $group_id;
				$line_arr['group_name'] = $group->getName();

				$lines[] = self::parseTemplate($template_line, $line_arr);
			}


			$block_arr['+lines'] = $lines;
			$block_arr['id'] = $element_id;
			$block_arr['template'] = $template;
			return self::parseTemplate($template_block, $block_arr);
		} else {
			return "";
		}
	}


	/*	Of-object block. TODO: refactoring with element-block.		*/

	public function getPropertyOfObject($object_id, $prop_id, $template = "default", $is_random = false) {
		if(!$template) $template = "default";
		$this->templatesMode('tpl');

		if($object = umiObjectsCollection::getInstance()->getObject($object_id)) {
			if($prop = (is_numeric($prop_id)) ? $object->getPropById($prop_id) : $object->getPropByName($prop_id)) {
				return self::parseTemplate($this->renderProperty($prop, $template, $is_random), Array(), false, $object_id);
			} else {
				list($template_not_exists) = def_module::loadTemplates("data/".$template, "prop_unknown");
				return $template_not_exists;
			}
		} else {
			list($template_not_exists) = def_module::loadTemplates("data/".$template, "prop_unknown");
			return $template_not_exists;
		}
	}


	public function getPropertyGroupOfObject($object_id, $group_id, $template = "default") {
		if(!$template) $template = "default";
		$this->templatesMode('tpl');

		if(strstr($group_id, " ") !== false) {
			$group_ids = explode(" ", $group_id);
			$res = "";
			foreach($group_ids as $group_id) {
				if(!($group_id = trim($group_id))) continue;
				$res .= $this->getPropertyGroupOfObject($object_id, $group_id, $template);
			}
			return $res;
		}


		if($object = umiObjectsCollection::getInstance()->getObject($object_id)) {
			if(!is_numeric($group_id)) $group_id = $object->getPropGroupId($group_id);

			$type_id = $object->getTypeId();
			if($group = umiObjectTypesCollection::getInstance()->getType($type_id)->getFieldsGroup($group_id)) {
				if($group->getIsActive() == false) return "";

				try {
					list($template_block, $template_line) = self::loadTemplates("data/".$template, "group", "group_line");
				} catch(publicException $e) {
					return "";
				}

				$lines = array();
				$props = $object->getPropGroupById($group_id);
				$sz = sizeof($props);
				for($i = 0; $i < $sz; $i++) {
					$prop_id = $props[$i];

					if($prop = $object->getPropById($prop_id)) {
						if($prop->getIsVisible() === false) {
							continue;
						}
					}

					$line_arr = Array();
					$line_arr['id'] = $object_id;
					$line_arr['prop_id'] = $prop_id;

					if($prop_val = $this->getPropertyOfObject($object_id, $prop_id, $template)) {
						$line_arr['prop'] = $prop_val;
					} else {
						continue;
					}

					$lines[] = self::parseTemplate($template_line, $line_arr);
				}



				$block_arr = Array();
				$block_arr['name'] = $group->getName();
				$block_arr['title'] = $group->getTitle();
				$block_arr['+lines'] = $lines;
				$block_arr['template'] = $template;
				return self::parseTemplate($template_block, $block_arr);
			} else {
				return "";
			}
		} else {
			return "";
		}

	}


	public function getAllGroupsOfObject($object_id, $template = "default") {
		if(!$template) $template = "default";
		$this->templatesMode('tpl');

		if($object = umiObjectsCollection::getInstance()->getObject($object_id)) {
			list($template_block, $template_line) = self::loadTemplates("data/".$template, "groups_block", "groups_line");

			$block_arr = Array();

			$object_type_id = $object->getTypeId();
			$object_type = umiObjectTypesCollection::getInstance()->getType($object_type_id);
			$groups = $object_type->getFieldsGroupsList();

			$lines = array();
			foreach($groups as $group_id => $group) {
				if(!$group->getIsActive() || !$group->getIsVisible()) continue;

				$line_arr = Array();
				$line_arr['group_id'] = $group_id;
				$line_arr['group_name'] = $group->getName();

				$lines[] = self::parseTemplate($template_line, $line_arr);
			}


			$block_arr['+lines'] = $lines;
			$block_arr['id'] = $object_id;
			$block_arr['template'] = $template;
			return self::parseTemplate($template_block, $block_arr);
		} else {
			return "";
		}
	}





	private function renderProperty(umiObjectProperty &$property, $template, $is_random = false) {
		$data_type = $property->getDataType();

		switch($data_type) {
			case "string": {
				return $this->renderString($property, $template);
			}

			case "text": {
				return $this->renderString($property, $template, false, "text");
			}

			case "wysiwyg": {
				return $this->renderString($property, $template, false, "wysiwyg");
			}

			case "int": {
				return $this->renderInt($property, $template);
			}

			case "price": {
				return $this->renderPrice($property, $template);
			}

			case "float": {
				return $this->renderFloat($property, $template);
			}

			case "boolean": {
				return $this->renderBoolean($property, $template);
			}

			case "img_file": {
				return $this->renderImageFile($property, $template);
			}

			case "relation": {
				return $this->renderRelation($property, $template, false, $is_random);
			}

			case "symlink": {
				return $this->renderSymlink($property, $template, false, $is_random);
			}

			case "swf_file": {
				return $this->renderFile($property, $template, false, "swf_file");
			}

			case "file": {
				return $this->renderFile($property, $template);
			}

			case "date": {
				return $this->renderDate($property, $template);
			}

			case "tags": {
				return $this->renderTags($property,$template);
			}

			case "optioned": {
				return $this->renderOptioned($property, $template);
			}

			default: {
				return "I don't know, how to render this sort of property (\"{$data_type}\") :(";
			}
		}
	}

	private function renderString(umiObjectProperty &$property, $template, $showNull = false, $templateBlock = "string") {
		$name = $property->getName();
		$title = $property->getTitle();
		$value = $property->getValue();



		if($property->getIsMultiple() === false) {
			list($tpl, $tpl_empty) = self::loadTemplates("data/".$template, "{$templateBlock}", "{$templateBlock}_empty");

			if(!$tpl) {
				list($tpl, $tpl_empty) = self::loadTemplates("data/".$template, "string", "string_empty");
			}

			$has_value =  (!is_array($value) && strlen($value));
			if(!$has_value && !$showNull) {
				return $tpl_empty;
			}

			$arr = Array();
			$arr['field_id'] = $property->getField()->getId();
			$arr['name'] = $name;
			$arr['title'] = $title;
			$arr['value'] = $value;
			return self::parseTemplate($tpl, $arr);
		} else {
			list($tpl_block, $tpl_empty, $tpl_item, $tpl_quant) = self::loadTemplates("data/".$template, "string_mul_block", "string_mul_block_empty", "string_mul_item", "string_mul_quant");

			if(empty($value) && !$showNull) {
				return $tpl_empty;
			}

			$items = array();
			$sz = sizeof($value);

			for($i = 0; $i < $sz; $i++) {
				$arr_item = Array();
				$arr_item['value'] = $value[$i];
				$arr_item['quant'] = ($sz != ($i + 1)) ? $tpl_quant : "";

				$items[] = self::parseTemplate($tpl_item, $arr_item);
			}


			$arr_block = Array();
			$arr['field_id'] = $property->getField()->getId();
			$arr_block['name'] = $name;
			$arr_block['title'] = $title;
			$arr_block['+items'] = $items;
			$arr_block['template'] = $template;
			return self::parseTemplate($tpl_block, $arr_block);
		}
	}


	private function renderInt(umiObjectProperty &$property, $template, $showNull = false) {
		$name = $property->getName();
		$title = $property->getTitle();
		$value = $property->getValue();



		if($property->getIsMultiple() === false) {
			list($tpl, $tpl_empty) = self::loadTemplates("data/".$template, "int", "int_empty");

			if((is_null($value) || $value === false || $value === "") && !$showNull) {
				return $tpl_empty;
			}

			$arr = Array();
			$arr['field_id'] = $property->getField()->getId();
			$arr['name'] = $name;
			$arr['title'] = $title;
			$arr['value'] = $value;
			return self::parseTemplate($tpl, $arr);
		} else {
			list($tpl_block, $tpl_empty, $tpl_item, $tpl_quant) = self::loadTemplates("data/".$template, "int_mul_block", "int_mul_block_empty", "int_mul_item", "int_mul_quant");

			if(empty($value) && !$showNull) {
				return $tpl_empty;
			}

			$items = array();
			$sz = sizeof($value);

			for($i = 0; $i < $sz; $i++) {
				$arr_item = Array();
				$arr_item['value'] = $value[$i];
				$arr_item['quant'] = ($sz != ($i + 1)) ? $tpl_quant : "";

				$items[] = self::parseTemplate($tpl_item, $arr_item);
			}


			$arr_block = Array();
			$arr_block['name'] = $name;
			$arr_block['title'] = $title;
			$arr_block['+items'] = $items;
			$arr_block['template'] = $template;
			return self::parseTemplate($tpl_block, $arr_block);
		}
	}


	private function renderPrice(umiObjectProperty &$property, $template, $showNull = false) {

		$name = $property->getName();
		$title = $property->getTitle();
		$value = $property->getValue();


		list($tpl, $tpl_empty) = self::loadTemplates("data/".$template, "price", "price_empty");
		if(empty($value) && !$showNull) {
			return $tpl_empty;
		}

		if($property->getIsMultiple() === false) {
			$arr = Array();
			$arr['field_id'] = $property->getField()->getId();
			$arr['name'] = $name;
			$arr['title'] = $title;
			$arr['currency_symbol'] = "";
			if ($currency = getSession("eshop_currency")) {
				if ($exchangeRate = $currency['exchange']) {
					$value = $value/$exchangeRate;
					$arr['currency_symbol'] = $currency['symbol'];
				}
			}

			$arr['value'] = number_format($value, (($value-floor($value)) > 0.005)?2:0, '.', ' ');


			return self::parseTemplate($tpl, $arr);
		} else {
		}
	}

	private function renderFloat(umiObjectProperty &$property, $template, $showNull = false) {
		$name = $property->getName();
		$title = $property->getTitle();
		$value = $property->getValue();

		list($tpl, $tpl_empty) = self::loadTemplates("data/".$template, "float", "float_empty");
		if(empty($value) && !$showNull) {
			return $tpl_empty;
		}

		if($property->getIsMultiple() === false) {
			$arr = Array();
			$arr['field_id'] = $property->getField()->getId();
			$arr['name'] = $name;
			$arr['title'] = $title;
			$arr['value'] = $value;
			return self::parseTemplate($tpl, $arr);
		} else {
			throw new publicException("Not supported");
		}
	}


	private function renderBoolean(umiObjectProperty &$property, $template, $showNull = false) {
		$name = $property->getName();
		$title = $property->getTitle();
		$value = $property->getValue();

		list($tpl_yes, $tpl_no) = self::loadTemplates("data/".$template, "boolean_yes", "boolean_no");
		if(empty($value) && !$showNull) {
			$arr_block = Array();
			$arr_block['name'] = $name;
			$arr_block['title'] = $title;
			$arr_block['template'] = $template;
			return self::parseTemplate($tpl_no, $arr_block);
		}

		if($property->getIsMultiple() === false) {
			$tpl = ($value) ? $tpl_yes : $tpl_no;

			$arr_block = Array();
			$arr_block['name'] = $name;
			$arr_block['title'] = $title;
			$arr_block['template'] = $template;
			return self::parseTemplate($tpl, $arr_block);

		} else {
			//а зачем? O_o
			return "";
		}
	}

	private function renderImageFile(umiObjectProperty &$property, $template, $showNull = false) {
		$name = $property->getName();
		$title = $property->getTitle();
		$value = $property->getValue();



		if($property->getIsMultiple() === false) {
			list($tpl, $tpl_empty) = self::loadTemplates("data/".$template, "img_file", "img_file_empty");

			if(empty($value) && !$showNull) {
				return $tpl_empty;
			}

			$arr = Array();
			$arr['field_id'] = $property->getField()->getId();
			$arr['name'] = $name;
			$arr['title'] = $title;
			$arr['size'] = $value->getSize();
			$arr['filename'] = $value->getFileName();
			$arr['filepath'] = $value->getFilePath();
			$arr['src'] = $value->getFilePath(true);
			$arr['ext'] = $value->getExt();

			if(wa_strtolower($value->getExt()) == "swf") {
				list($tpl) = self::loadTemplates("data/".$template, "swf_file");
			}

			if($value instanceof iUmiImageFile) {
				$arr['width'] = $value->getWidth();
				$arr['height'] = $value->getHeight();
			}

			$arr['template'] = $template;

			return self::parseTemplate($tpl, $arr);
		} else {
		}
	}

	private function renderRelation(umiObjectProperty &$property, $template, $showNull = false, $is_random = false) {
		$name = $property->getName();
		$title = $property->getTitle();
		$value = $property->getValue();


		if($property->getIsMultiple() === false) {
			list($tpl, $tpl_empty) = self::loadTemplates("data/".$template, "relation", "relation_empty");

			$arr = Array();
			$arr['field_id'] = $property->getField()->getId();
			$arr['name'] = $name;
			$arr['title'] = $title;
			$arr['object_id'] = $value;
			if(empty($value) && !$showNull) {
				return self::parseTemplate($tpl_empty, $arr, false, $value);
			}

			$arr['value'] = umiObjectsCollection::getInstance()->getObject($value)->getName();
			return self::parseTemplate($tpl, $arr);
		} else {
			list($tpl_block, $tpl_block_empty, $tpl_item, $tpl_quant) = self::loadTemplates("data/".$template, "relation_mul_block", "relation_mul_block_empty", "relation_mul_item", "relation_mul_quant");

			if(empty($value) && !$showNull) {
				return $tpl_block_empty;
			}

			if($is_random) {
				$value = $value[rand(0, sizeof($value) - 1)];
				$value = Array($value);
			}

			$items = array();
			$sz = sizeof($value);

			for($i = 0; $i < $sz; $i++) {
				$arr_item = Array();
				$arr_item['object_id'] = $value[$i];
				$arr_item['value'] = umiObjectsCollection::getInstance()->getObject($value[$i])->getName();
				$arr_item['quant'] = ($sz != ($i + 1)) ? $tpl_quant : "";

				$items[] = self::parseTemplate($tpl_item, $arr_item, false, $value[$i]);
			}


			$arr_block = Array();
			$arr_block['name'] = $name;
			$arr_block['title'] = $title;
			$arr_block['+items'] = $items;
			$arr_block['template'] = $template;
			return self::parseTemplate($tpl_block, $arr_block);
		}
	}


	private function renderSymlink(umiObjectProperty &$property, $template, $showNull = false, $is_random = false) {
		$name = $property->getName();
		$title = $property->getTitle();
		$value = $property->getValue();

		$is_random = ($is_random) ? true : false;

		list($tpl_block, $tpl_empty, $tpl_item, $tpl_quant) = self::loadTemplates("data/".$template, "symlink_block", "symlink_block_empty", "symlink_item", "symlink_quant");

		if(empty($value) && !$showNull) {
			return $tpl_empty;
		}

		if($is_random) {
			$value = $value[rand(0, sizeof($value) - 1)];
			$value = Array($value);
		}


		$items = array();
		$sz = sizeof($value);

		for($i = 0; $i < $sz; $i++) {
			$arr_item = Array();

			$element = $value[$i];
			$element_id = $element->getId();

			$arr_item['id'] = $element_id;
			$arr_item['object_id'] = $element->getObject()->getId();
			$arr_item['value'] = $element->getName();
			$arr_item['link'] = umiHierarchy::getInstance()->getPathById($element_id);
			$arr_item['quant'] = ($sz != ($i + 1)) ? $tpl_quant : "";

			$items[] = self::parseTemplate($tpl_item, $arr_item, $element_id);
		}


		$arr_block = Array();
		$arr_block['name'] = $name;
		$arr_block['title'] = $title;
		$arr_block['+items'] = $items;
		$arr_block['template'] = $template;

		return self::parseTemplate($tpl_block, $arr_block);
	}

	private function renderFile(umiObjectProperty &$property, $template, $showNull = false, $templateBlock = "file") {
		$name = $property->getName();
		$title = $property->getTitle();
		$value = $property->getValue();



		if($property->getIsMultiple() === false) {
			list($tpl, $tpl_empty) = self::loadTemplates("data/".$template, "{$templateBlock}", "{$templateBlock}_empty");

			if(!$tpl) {
				list($tpl, $tpl_empty) = self::loadTemplates("data/".$template, "file", "file_empty");
			}

			if(empty($value) && !$showNull) {
				return $tpl_empty;
			}

			$arr = Array();
			$arr['field_id'] = $property->getField()->getId();
			$arr['name'] = $name;
			$arr['title'] = $title;
			$arr['size'] = $value->getSize();
			$arr['filename'] = $value->getFileName();
			$arr['filepath'] = $value->getFilePath();
			$arr['src'] = $value->getFilePath(true);
			$arr['ext'] = $value->getExt();
			$arr['modifytime'] = $value->getModifyTime();

			if ($value instanceof umiImageFile) {
				$arr['width'] = $value->getWidth();
				$arr['height'] = $value->getHeight();
			}

			$arr['template'] = $template;

			return self::parseTemplate($tpl, $arr);
		} else {
		}
	}


	private function renderDate(umiObjectProperty &$property, $template, $showNull = false) {
		$name = $property->getName();
		$title = $property->getTitle();
		$value = $property->getValue();



		if($property->getIsMultiple() === false) {
			list($tpl, $tpl_empty) = self::loadTemplates("data/".$template, "date", "date_empty");

			if(empty($value) && !$showNull) {
				return $tpl_empty;
			}

			$arr = Array();
			$arr['field_id'] = $property->getField()->getId();
			$arr['name'] = $name;
			$arr['title'] = $title;
			$arr['timestamp'] = $value->getFormattedDate("U");
			$arr['value'] = $value->getFormattedDate();

			$arr['template'] = $template;

			return self::parseTemplate($tpl, $arr);
		} else {
		}
	}

	public function renderTags($property, $template) {
		$values = $property->getValue();
		list($tpl_block, $tpl_block_item, $tpl_block_empty) = self::loadTemplates("data/".$template, "tags_block", "tags_item", "tags_empty");

		$items_arr = array();
		foreach($values as $key => $value) {
			$items_arr[] = self::parseTemplate($tpl_block_item, array(
				'tag' => $value,
				'name' => $value
			));
		}

		if(count($items_arr) < 1) return $tpl_block_empty;

		return self::parseTemplate($tpl_block, array(
			'+items' => $items_arr,
		));
	}

	private function renderOptioned(umiObjectProperty &$property, $template, $showNull = false) {
		$name = $property->getName();
		$title = $property->getTitle();
		$value = $property->getValue();


		list($tpl_block, $tpl_block_empty, $tpl_item) = self::loadTemplates("data/".$template, "optioned_block", "optioned_block_empty", "optioned_item");

		if(empty($value) && !$showNull) {
			return $tpl_block_empty;
		}

		$items_arr = array();
		foreach($value as $info) {
			$objectId = getArrayKey($info, 'rel');
			$elementId = getArrayKey($info, 'symlink');

			$item_arr = array(
				'int'			=> getArrayKey($info, 'int'),
				'float'			=> getArrayKey($info, 'float'),
				'text'			=> getArrayKey($info, 'text'),
				'varchar'		=> getArrayKey($info, 'varchar'),
				'field_name'	=> $name
			);

			if($objectId) {
				if($object = selector::get('object')->id($objectId)) {
					$item_arr['object-id'] = $object->id;
					$item_arr['object-name'] = $object->name;
				}
			}

			if($elementId) {
				if($element = selector::get('element')->id($elementId)) {
					$item_arr['element-id'] = $element->id;
					$item_arr['element-name'] = $element->name;
					$item_arr['element-link'] = $element->link;
				}
			}

			$items_arr[] = self::parseTemplate($tpl_item, $item_arr, false, $objectId);
		}

		$arr = array(
			'field_id'			=> $property->getField()->getId(),
			'field_name'		=> $name,
			'name'				=> $name,
			'title'				=> $title,
			'subnodes:items'	=> $items_arr
		);

		return self::parseTemplate($tpl_block, $arr);
	}

	public function doSelection($template = "default", $uselName) {
		$this->templatesMode('tpl');

		$scheme_old = getRequest('scheme');
		$params = func_get_args();
		$params = array_slice($params, 2, sizeof($params) - 2);
		$stream = new uselStream;
		$result = $stream->call($uselName, $params);

		$oldResultMode = def_module::isXSLTResultMode(false);
		list($objects_block, $objects_line, $objects_empty,
		$elements_block, $elements_line, $elements_empty,
		$separator, $separator_last) = self::loadTemplates("data/usel/".$template,
			"objects_block", "objects_block_line", "objects_block_empty",
			"elements_block", "elements_block_line", "elements_block_empty",
			"separator", "separator_last"
		);

		switch($result['mode']) {
			case "objects":
				$tpl_block = $objects_block;
				$tpl_line = $objects_line;
				$tpl_empty = $objects_empty;
				break;

			case "pages":
				$tpl_block = $elements_block;
				$tpl_line = $elements_line;
				$tpl_empty = $elements_empty;
				break;

			default: {
				throw new publicException("Unsupported return mode \"{$result['mode']}\"");
			}
		}


		if($result['sel'] instanceof selector) {
			$sel = $result['sel'];
			$results = $sel->result;
			$total = $sel->length;
			$limit = $sel->limit;

			if($total == 0) {
				$tpl_block = $tpl_empty;
			}

			$objectsCollection = umiObjectsCollection::getInstance();
			$hierarchy = umiHierarchy::getInstance();

			$block_arr = Array();
			$lines = Array();
			$objectId = false;
			$elementId = false;
			$sz = sizeof($results);
			$c = 0;

			foreach($results as $item) {
				$line_arr = array();

				if($result['mode'] == "objects") {
					$object = $item;
					if($object instanceof iUmiObject) {
						$objectId = $object->id;
						$line_arr['attribute:id'] = $object->id;
						$line_arr['attribute:name'] = $object->getName();
						$line_arr['attribute:type-id'] = $object->getTypeId();
						$line_arr['xlink:href'] = "uobject://" . $objectId;
					} else {
						continue;
					}
				} else {
					$element = $item;
					if($element instanceof iUmiHierarchyElement) {
						$elementId = $element->id;
						$line_arr['attribute:id'] = $element->id;
						$line_arr['attribute:name'] = $element->getName();
						$line_arr['attribute:link'] = $hierarchy->getPathById($element->id);
						$line_arr['xlink:href'] = "upage://" . $element->id;
					} else {
						continue;
					}
				}
				$line_arr['void:separator'] = (($sz == ($c + 1)) && $separator_last) ? $separator_last : $separator;
				$lines[] = self::parseTemplate($tpl_line, $line_arr, $elementId, $objectId);
				++$c;
			}

			$block_arr['subnodes:items'] = $lines;
			$block_arr['total'] = $total;
			$block_arr['per_page'] = $limit;
			$result = self::parseTemplate($tpl_block, $block_arr);
			def_module::isXSLTResultMode($oldResultMode);
			return $result;
		} else {
			throw new publicException("Can't execute selection");
		}
	}

	public function getRestrictionsList() {
		$this->templatesMode('xslt');

		$block_arr = array();

		$restrictions = baseRestriction::getList();
		$items_arr = array();
		foreach($restrictions as $restriction) {
			if($restriction instanceof baseRestriction) {
				$items_arr[] = $restriction;
			}
		}
		$block_arr['items']['nodes:item'] = $items_arr;

		return $block_arr;
	}

	public function config() {
		if(class_exists("__data")) {
			return __data::config();
		}
	}

	public function getObjectEditLink($objectId, $type = false) {
		return $this->pre_lang . '/admin/data/guide_item_edit/' . $objectId . '/';
	}

	public function getGuideItems($template = "default", $guide_id = false, $per_page = 100, $curr_page = 0) {
		if(!$curr_page) $curr_page = (int) getRequest('p');
		if(!$guide_id) $guide_id = (int) getRequest('param0');

		if(!$template) $template = "default";
		list($template_block, $template_block_empty, $template_line) = def_module::loadTemplates("data/".$template, "guide_block", "guide_block_empty", "guide_block_line");

		$sel = new selector('objects');
		$sel->types('object-type')->id($guide_id);
		$sel->limit($per_page * $curr_page, $per_page);

		selectorHelper::detectFilters($sel);

		$block_arr = array();
		$lines = array();

		foreach ($sel->result as $element) {
			$line_arr = array();
			$line_arr['attribute:id'] = $element->getId();
			$line_arr['xlink:href'] = "uobject://" . $element->getId();
			$line_arr['node:text'] = $element->getName();
			$lines[] = self::parseTemplate($template_line, $line_arr);
		}

		$block_arr['attribute:guide_id']  = $guide_id;
		$block_arr['subnodes:items'] = $lines;
		$block_arr['total'] = $sel->total;

		return self::parseTemplate($template_block, $block_arr);
	}

};

?>
