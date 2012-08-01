<?php
	abstract class __search_data {
		public function parseSearchRelation(umiField $field, $template, $template_item, $template_separator) {
			$block_arr = Array();

			$name = $field->getName();
			$title = $field->getTitle();

			$guide_id = $field->getGuideId();
			$guide_items = umiObjectsCollection::getInstance()->getGuidedItems($guide_id);

			$fields_filter = getRequest('fields_filter');
			$value = getArrayKey($fields_filter, $name);

			$items = Array();
			$i = 0;
			$sz = sizeof($guide_items);

			$is_tpl = (cmsController::getInstance()->getCurrentTemplater() instanceof tplTemplater);
			if (!$is_tpl) $template_item = true;

			$unfilter_link = "";

			foreach($guide_items as $object_id => $object_name) {
				if (is_array($value)) {
					$selected = (in_array($object_id, $value)) ? "selected" : "";
				}
				else {
					$selected = ($object_id == $value) ? "selected" : "";
				}

				if ($template_item) {
					$line_arr = Array();
					$line_arr['attribute:id'] = $line_arr['void:object_id'] = $object_id;
					$line_arr['node:object_name'] = $object_name;

					$params = $_GET;
					unset($params['path']);
					unset($params['p']);
					$params['fields_filter'][$name] = $object_id;
					$filter_link = "?" . http_build_query($params, '', '&amp;');

					unset($params['fields_filter'][$name]);
					$unfilter_link = "?" . http_build_query($params, '', '&amp;');

					$line_arr['attribute:filter_link'] = $filter_link;
					$line_arr['attribute:unfilter_link'] = $unfilter_link;

					if ($selected) {
						$line_arr['attribute:selected'] = "selected";
					}

					$items[] = def_module::parseTemplate($template_item, $line_arr);

					if (++$i < $sz) {
						if ($is_tpl) {
							$items[] = $template_separator;
						}
					}
				}
				else {
					$items[] = "<option value=\"{$object_id}\" {$selected}>{$object_name}</option>";
				}
			}

			$block_arr['attribute:unfilter_link'] = $unfilter_link;
			$block_arr['attribute:name'] = $name;
			$block_arr['attribute:title'] = $title;
			$block_arr['subnodes:values'] = $block_arr['void:items'] = $items;
			$block_arr['void:selected'] = ($value) ? "" : "selected";
			return def_module::parseTemplate($template, $block_arr);
		}

		public function parseSearchText(umiField $field, $template) {
			$block_arr = Array();

			$name = $field->getName();
			$title = $field->getTitle();

			if ($fields_filter = getRequest('fields_filter')) {
				$value = (string) getArrayKey($fields_filter, $name);
			}
			else $value = NULL;

			$block_arr['attribute:name'] = $name;
			$block_arr['attribute:title'] = $title;
			$block_arr['value'] = self::protectStringVariable($value);
			return def_module::parseTemplate($template, $block_arr);
		}

		public function parseSearchPrice(umiField $field, $template) {
			$block_arr = Array();

			$name = $field->getName();
			$title = $field->getTitle();

			$fields_filter = getRequest('fields_filter');
			$value = (array) getArrayKey($fields_filter, $name);

			$block_arr['attribute:name'] = $name;
			$block_arr['attribute:title'] = $title;
			$block_arr['value_from'] = self::protectStringVariable(getArrayKey($value, 0));
			$block_arr['value_to'] = self::protectStringVariable(getArrayKey($value, 1));
			return def_module::parseTemplate($template, $block_arr);
		}

		public function parseSearchInt(umiField $field, $template) {
			$block_arr = Array();

			$name = $field->getName();
			$title = $field->getTitle();

			$fields_filter = getRequest('fields_filter');
			$value = (array) getArrayKey($fields_filter, $name);

			$block_arr['attribute:name'] = $name;
			$block_arr['attribute:title'] = $title;
			$block_arr['value_from'] = intval(getArrayKey($value, 0));
			$block_arr['value_to'] = intval(getArrayKey($value, 1));
			return def_module::parseTemplate($template, $block_arr);
		}

		public function parseSearchBoolean(umiField $field, $template) {
			$block_arr = Array();

			$name = $field->getName();
			$title = $field->getTitle();

			$fields_filter = getRequest('fields_filter');
			$value = (array) getArrayKey($fields_filter, $name);

			$block_arr['attribute:name'] = $name;
			$block_arr['attribute:title'] = $title;
			$block_arr['checked'] = ((bool) getArrayKey($value, 0)) ? " checked" : "";
			return def_module::parseTemplate($template, $block_arr);
		}

		public function parseSearchDate(umiField $field, $template) {
			$block_arr = Array();

			$name = $field->getName();
			$title = $field->getTitle();
			
			if($fields_filter = getRequest('fields_filter')) {
				$value = (array) getArrayKey($fields_filter, $name);
			} else {
				$value = NULL;
			}

			$block_arr['attribute:name'] = $name;
			$block_arr['attribute:title'] = $title;
			
			$from = getArrayKey($value, 0);
			$to = getArrayKey($value, 1);
			
			$values = Array(
				"from"	=> self::protectStringVariable($from),
				"to"	=> self::protectStringVariable($to)
			);
			$block_arr['value'] = $values;
			return def_module::parseTemplate($template, $block_arr);
		}

		public function parseSearchSymlink(umiField $field, $template, $category_id) {
			$block_arr = Array();
			$items = Array();

			$name = $field->getName();
			$title = $field->getTitle();

			$sel = new selector('pages');
			$sel->types('hierarchy-type');
			$sel->where('hierarchy')->page($category_id)->childs(1);

			$guide_items = array();

			foreach($sel->result as $element) {
				foreach($element->getValue($name) as $object) {
					$guide_items[$object->id] = $object->name;
				}
			}

			$fields_filter = getRequest('fields_filter');
			$value = getArrayKey($fields_filter, $name);

			$is_tpl = (cmsController::getInstance()->getCurrentTemplater() instanceof tplTemplater);
			$unfilter_link = "";

			foreach($guide_items as $object_id => $object_name) {
				if (is_array($value)) {
					$selected = (in_array($object_id, $value)) ? "selected" : "";
				}
				else {
					$selected = ($object_id == $value) ? "selected" : "";
				}

				if ($is_tpl) {
					$items[] = "<option value=\"{$object_id}\" {$selected}>{$object_name}</option>";
				}
				else {
					$line_arr = Array();
					$line_arr['attribute:id'] = $line_arr['void:object_id'] = $object_id;
					$line_arr['node:object_name'] = $object_name;

					$params = $_GET;
					unset($params['path']);
					unset($params['p']);
					$params['fields_filter'][$name] = $object_id;

					$filter_link = "?" . http_build_query($params, '', '&amp;');

					unset($params['fields_filter'][$name]);
					$unfilter_link = "?" . http_build_query($params, '', '&amp;');

					$line_arr['attribute:filter_link'] = $filter_link;
					$line_arr['attribute:unfilter_link'] = $unfilter_link;

					if ($selected) $line_arr['attribute:selected'] = "selected";

					$items[] = def_module::parseTemplate('', $line_arr);
				}
			}

			$block_arr['attribute:unfilter_link'] = $unfilter_link;
			$block_arr['attribute:name'] = $name;
			$block_arr['attribute:title'] = $title;
			$block_arr['subnodes:values'] = $block_arr['void:items'] = $items;
			$block_arr['void:selected'] = ($value) ? "" : "selected";
			
			return def_module::parseTemplate($template, $block_arr);
		}



		public function applyFilterName(umiSelection $sel, $value) {
			if(empty($value)) return false;
			
			if(is_array($value)) {
				foreach($value as $key => $val) {
					if($key == "eq") {
						$sel->addNameFilterEquals($val);
					}
					
					if($key == "like") {
						$sel->addNameFilterLike($val);
					}
				}
				return;
			}
			
			$sel->addNameFilterLike($value);
		}


		public function applyFilterText(umiSelection $sel, umiField $field, $value) {
			if(empty($value)) return false;
			
			if($this->applyKeyedFilters($sel, $field, $value)) {
				return;
			}
			
			if(is_array($value)) {
				return;
			}

			$sel->addPropertyFilterLike($field->getId(), $value);
		}

		public function applyFilterInt(umiSelection $sel, umiField $field, $value) {
			if(empty($value)) return false;

			if($this->applyKeyedFilters($sel, $field, $value)) {
				return;
			}

			$tmp = array_extract_values($value);
			if(empty($tmp)) return false;

			if($value[1]) {
				$sel->addPropertyFilterBetween($field->getId(), $value[0], $value[1]);
			} else {
				if($value[0]) {
					$sel->addPropertyFilterMore($field->getId(), $value[0]);
				}
			}
		}

		public function applyFilterRelation(umiSelection $sel, umiField $field, $value) {
			if(empty($value)) return false;
			
			if($this->applyKeyedFilters($sel, $field, $value)) {
				return;
			}
			
			$value = $this->searchRelationValues($field, $value);

			$sel->addPropertyFilterEqual($field->getId(), $value);
		}

		public function applyFilterPrice(umiSelection $sel, umiField $field, $value) {
			if(empty($value)) return false;
			
			if($this->applyKeyedFilters($sel, $field, $value)) {
				return;
			}
			
			$tmp = array_extract_values($value);
			if(empty($tmp)) return false;
			
			if(isset($value[1]) && !empty($value[1])) {
				if($value[0] <= $value[1]) {
					$minValue = $value[0];
					$maxValue = $value[1];
				} else {
					$minValue = $value[1];
					$maxValue = $value[0];
				}
				
				$sel->addPropertyFilterBetween($field->getId(), $minValue, $maxValue);
			} else {
				if(isset($value[0])) {
					$sel->addPropertyFilterMore($field->getId(), $value[0]);
				}
			}
		}


		public function applyFilterDate(umiSelection $sel, umiField $field, $value) {
			if(empty($value)) return false;
			
			foreach($value as $i => $val) {
				$value[$i] = umiDate::getTimeStamp($val);
			}
			
			if($this->applyKeyedFilters($sel, $field, $value)) {
				return;
			}

			if($value[1]) {
				$sel->addPropertyFilterBetween($field->getId(), $value[0], $value[1]);
			} else {
				if($value[0]) {
					$sel->addPropertyFilterMore($field->getId(), $value[0]);
				}
			}
		}
		
		public function applyFilterFloat(umiSelection $sel, umiField $field, $value) {
			if(empty($value)) return false;
			
			if($this->applyKeyedFilters($sel, $field, $value)) {
				return;
			}
			
			$tmp = array_extract_values($value);
			if(empty($tmp)) return false;

			if($value[1]) {
				$sel->addPropertyFilterBetween($field->getId(), $value[0], $value[1]);
			} else {
				if($value[0]) {
					$sel->addPropertyFilterMore($field->getId(), $value[0]);
				}
			}
		}

		public function applyFilterBoolean(umiSelection $sel, umiField $field, $value) {
			if(empty($value)) return false;
			
			if($this->applyKeyedFilters($sel, $field, $value)) {
				return;
			}

			if($value) {
				$sel->addPropertyFilterEqual($field->getId(), $value);
			}
		}


		public static function protectStringVariable($stringVariable = "") {
			$stringVariable = htmlspecialchars($stringVariable);
			return $stringVariable;
		}
		
		public function applyKeyedFilters(umiSelection $sel, umiField $field, $values) {
			if(is_array($values) == false) {
				return false;
			}
			
			foreach($values as $key => $value) {
				if(is_numeric($key) || $value === "") {
					return false;
				}
				
				$dataType = $field->getFieldType()->getDataType();
				
				switch($key) {
					case "eq": {
						if(is_array($value)) {
							foreach($value as $v) {
								$this->applyKeyedFilters($sel, $field, Array($key => $v));
							}
							break;
						}
						
						$value = $this->searchRelationValues($field, $value);
						if($dataType == "date") {
							$value = strtotime(date("Y-m-d", $value));
							$sel->addPropertyFilterBetween($field->getId(), $value, ($value + 3600*24));
							break;
						}
						
						if($dataType == "file" || $dataType == "img_file" || $dataType == "swf_file") {
							if($value > 0) {
								$sel->addPropertyFilterIsNotNull($field->getId());
							} else {
								$sel->addPropertyFilterIsNull($field->getId());
							}
						} else {
							$sel->addPropertyFilterEqual($field->getId(), $value);
						}
						break;
					}
					
					case "ne": {
						$sel->addPropertyFilterNotEqual($field->getId(), $value);
						break;
					}
					
					case "lt": {
						$sel->addPropertyFilterLess($field->getId(), $value);
						break;
					}
					
					case "gt": {
						$sel->addPropertyFilterMore($field->getId(), $value);
						break;
					}
					
					case "like": {
						$value = $this->searchRelationValues($field, $value);
						
						if(is_array($value)) {
							foreach($value as $val) {
								if($val) {
									$sel->addPropertyFilterLike($field->getId(), $val);
								}
							}
						} else {
							$sel->addPropertyFilterLike($field->getId(), $value);
						}
						break;
					}
					
					default: {
						return false;
					}
				}
				
			}
			return true;
		}
		
		public function searchRelationValues($field, $value) {
			if(is_array($value)) {
				$result = Array();
				foreach($value as $sval) {
					$result[] = $this->searchRelationValues($field, $sval);
				}
				return $result;
			}

			$guideId = $field->getGuideId();
			
			if($guideId) {
				if(is_numeric($value)) {
					return $value;
				} else {
					$sel = new umiSelection;
					$sel->addObjectType($guideId);
					$sel->searchText($value);
					$result = umiSelectionsParser::runSelection($sel);
					return sizeof($result) ? $result : Array(-1);
				}
			} else {
				return $value;
			}
		}
			
	};
?>