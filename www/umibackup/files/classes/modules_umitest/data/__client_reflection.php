<?php
	abstract class __client_reflection_data {

		public function getEditForm($object_id, $template = "default", $groups_names = "", $all = false, $ignorePermissions = false) {
			if(!$template) $template = "default";

			$b_allow = false;
			$inst_users = cmsController::getInstance()->getModule("users");
			$permissions = permissionsCollection::getInstance();

			if($permissions->isSv()) {
				$ignorePermissions = true;
			}

			if($permissions->isSv()) {
				$ignorePermissions = true;
			}

			if(!$ignorePermissions) {
				$b_allow = $permissions->isOwnerOfObject($object_id);

				$arr_helements = umiHierarchy::getInstance()->getObjectInstances($object_id);
				foreach ($arr_helements as $i_element_id) {
					$arr_allow = $inst_users->isAllowedObject($inst_users->user_id, $i_element_id);
					if (is_array($arr_allow) && count($arr_allow) > 1) {
						$b_allow = intval($arr_allow[1]);
						if ($b_allow) break;
					}
				}

				if (!$b_allow) {
					return cmsController::getInstance()->getCurrentTemplater()->putLangs("%data_edit_foregin_object%");
				}
			}

			$groups_names = trim($groups_names);
			$groups_names = strlen($groups_names) ? explode(" ", $groups_names) : array();

			list(
				$template_block, $template_block_empty, $template_line
			) = def_module::loadTemplates("data/reflection/{$template}",
				"reflection_block", "reflection_block_empty", "reflection_group"
			);

			if(!($object = umiObjectsCollection::getInstance()->getObject($object_id))) {
				return $template_block_empty;
			}


			$object_type_id = $object->getTypeId();
			$groups_arr = $this->getTypeFieldGroups($object_type_id);

			$groups = Array();
			foreach($groups_arr as $group) {
				if(!$group->getIsActive()) {
					continue;
				}

				if(sizeof($groups_names)) {
					if(!in_array($group->getName(), $groups_names)) {
						continue;
					}
				} else {
					if(!$group->getIsActive() || (!$group->getIsVisible() && !$all)) {
						continue;
					}
				}

				$line_arr = Array();

				$fields_arr = $group->getFields();
				$fields = Array();
				foreach($fields_arr as $field) {
					if(!$field->getIsVisible() && !$all) continue;
					if($field->getIsSystem()) continue;

					$fields[] = $this->renderEditField($template, $field, $object);
				}

				if(empty($fields)) continue;

				$line_arr['attribute:name'] = $group->getName();
				$line_arr['attribute:title'] = $group->getTitle();
				$line_arr['nodes:field'] = $line_arr['void:fields'] = $fields;

				$groups[] = def_module::parseTemplate($template_line, $line_arr);
			}

			$block_arr['nodes:group'] = $block_arr['void:groups'] = $groups;

			return def_module::parseTemplate($template_block, $block_arr, false, $object_id);
		}


		public function getCreateForm($object_type_id, $template = "default", $groups_names = "", $all = false) {
			if(!$template) $template = "default";

			list(
				$template_block, $template_block_empty, $template_line
			) = def_module::loadTemplates("data/reflection/{$template}",
				"reflection_block", "reflection_block_empty", "reflection_group"
			);
			$groups_names = trim($groups_names);

			$groups_names = strlen($groups_names) ? explode(" ", $groups_names) : array();

			$groups_arr = $this->getTypeFieldGroups($object_type_id);

			if(!is_array($groups_arr)) {
				return "";
			}

			$groups = Array();
			foreach($groups_arr as $group) {
				if(!$group->getIsActive()) {
					continue;
				}
				if ($group->getName() == "locks") {
					continue;
				}
				if(sizeof($groups_names)) {
					if(!in_array($group->getName(), $groups_names)) {
						continue;
					}

				} else {
					if(!$group->getIsActive() || (!$group->getIsVisible() && !$all)) {
						continue;
					}
				}

				$line_arr = Array();

				$fields_arr = $group->getFields();
				$fields = Array();
				foreach($fields_arr as $field) {
					if(!$field->getIsVisible() && !$all) continue;
					if($field->getIsSystem()) continue;

					$fields[] = $this->renderEditField($template, $field);
				}

				if(empty($fields)) continue;

				$line_arr['attribute:name'] = $group->getName();
				$line_arr['attribute:title'] = $group->getTitle();

				$line_arr['nodes:field'] = $line_arr['void:fields'] = $fields;

				$groups[] = def_module::parseTemplate($template_line, $line_arr);
			}

			$block_arr['nodes:group'] = $block_arr['void:groups'] = $groups;
			return def_module::parseTemplate($template_block, $block_arr);
		}


		public function getTypeFieldGroups($type_id) {
			if (!is_numeric($type_id)) $type_id = umiObjectTypesCollection::getInstance()->getTypeIdByGUID($type_id);
			if ($type = umiObjectTypesCollection::getInstance()->getType($type_id)) {
				return $type->getFieldsGroupsList();
			} else {
				return false;
			}
		}

		public function renderEditField($template, umiField $field, $object = false) {
			$field_type_id = $field->getFieldTypeId();
			$field_type = umiFieldTypesCollection::getInstance()->getFieldType($field_type_id);
			$is_multiple = $field_type->getIsMultiple();

			$data_type = $field_type->getDataType();

			switch($data_type) {
				case "counter":
				case "int": {
					$res = $this->renderEditFieldInt($field, $is_multiple, $object, $template);
					$data_type = "int";
					break;
				}

				case "price":
				case "float": {
					$res = $this->renderEditFieldInt($field, $is_multiple, $object, $template);
					break;
				}

				case "string": {
					$res = $this->renderEditFieldString($field, $is_multiple, $object, $template);
					break;
				}

				case "date": {
					$res = $this->renderEditFieldDate($field, $is_multiple, $object, $template);
					break;
				}

				case "password": {
					$res = $this->renderEditFieldPassword($field, $is_multiple, $object, $template);
					break;
				}

				case "relation": {
					$res = $this->renderEditFieldRelation($field, $is_multiple, $object, $template);
					break;
				}


				case "symlink": {
					$res = $this->renderEditFieldSymlink($field, $is_multiple, $object, $template);
					break;
				}

				case "img_file": {
					$res = $this->renderEditFieldImageFile($field, $is_multiple, $object, $template);
					break;
				}

				case "video_file" :
				case "swf_file": {
					$res = $this->renderEditFieldFile($field, $is_multiple, $object, $template);
					break;
				}

				case "file": {
					$res = $this->renderEditFieldFile($field, $is_multiple, $object, $template);
					break;
				}

				case "text": {
					$res = $this->renderEditFieldText($field, $is_multiple, $object, $template);
					break;
				}

				case "wysiwyg": {
					$res = $this->renderEditFieldWYSIWYG($field, $is_multiple, $object, $template);
					break;
				}

				case "boolean": {
					$res = $this->renderEditFieldBoolean($field, $is_multiple, $object, $template);
					break;
				}

				case "tags": {
					$res = $this->renderEditFieldTags($field, $is_multiple, $object, $template);
					break;
				}

				case "optioned": {
					$res = $this->renderEditFieldOptioned($field, $is_multiple, $object, $template);
					break;
				}

				default: {
					$res = "";
				}
			}

			if($res === false) {
				return NULL;
			}

			if (cmsController::getInstance()->getCurrentTemplater() instanceof tplTemplater) {
				$required = $field->getIsRequired();
				$res = def_module::parseTemplate($res, array(
					'required' => ($required ? 'required' : ''),
					'required_asteriks' => ($required ? '*' : '')
				));
			}
			else {
				$res['attribute:type'] = $data_type;
				$res['attribute:id'] = $field->getId();

				if ($field->getIsRequired()) {
					$res['attribute:required'] = 'required';
				}
				if($tip = $field->getTip()) {
					$res['attribute:tip'] = $tip;
				}
			}

			return $res;
		}

		public function getAllowedMaxFileSize($fileType = false) {

			$sizes = array();
			$sizes[] = $this->getMegaBytes(ini_get('upload_max_filesize'));
			$sizes[] =  $this->getMegaBytes(ini_get('post_max_size'));
			$sizes[] =  $this->getMegaBytes(ini_get('memory_limit'));
			if($fileType) $sizes[] = regedit::getInstance()->getVal("//settings/max_img_filesize");

			return min($sizes);
		}

		public function getMegaBytes($val) {

			$val = strtolower(trim($val));
			$last = substr($val, -1);
			$val = (int) $val;

			switch($last) {
				case 'g':
				    $val *= 1024;
				    break;
				case 'm':
				    $val = $val;
				    break;
				case 'k':
				    $val = $val / 1024;
				    break;
			}
    		return $val;
		}


		public function renderEditFieldString($field, $is_multiple, $object, $template) {
			list($template_block) = def_module::loadTemplates("data/reflection/{$template}", "reflection_field_string");

			$block_arr = Array();

			if($is_multiple) {
				//TODO: Подумать, имеет ли смысл вводить поля на несколько строк?
			} else {
				$field_name = $field->getName();
				$block_arr['attribute:name'] = $field_name;
				$block_arr['attribute:title'] = $field->getTitle();
				$block_arr['attribute:tip'] = $field->getTip();

				$block_arr['node:value'] = ($object) ? $object->getValue($field->getName()) : "";

				if($object) {
					$block_arr['void:object_id'] = $object->getId();
				}


				$block_arr['attribute:input_name'] = ($object) ? "data[" . $object->getId() . "][{$field_name}]" : "data[new][{$field_name}]";
			}

			return def_module::parseTemplate($template_block, $block_arr);
		}

		public function renderEditFieldDate($field, $is_multiple, $object, $template) {
			list($template_block_string, $template_block) = def_module::loadTemplates("data/reflection/{$template}", "reflection_field_string", "reflection_field_date");

			if(!$template_block) $template_block = $template_block_string;

			$block_arr = Array();

			if($is_multiple) {
				// по-моему не нужно...
			} else {
				$field_name = $field->getName();
				$block_arr['attribute:name'] = $field_name;
				$block_arr['attribute:title'] = $field->getTitle();
				$block_arr['attribute:tip'] = $field->getTip();

				$block_arr['node:value'] = "";
				$block_arr['attribute:timestamp'] = 0;

				if($object) {
					$oDate = $object->getValue($field->getName());
					if ($oDate instanceof umiDate) {
						$block_arr['attribute:timestamp'] = $oDate->getDateTimeStamp();
						$block_arr['node:value'] = $oDate->getDateTimeStamp() >0 ? $oDate->getFormattedDate() : "";
					}
					$block_arr['void:object_id'] = $object->getId();
				}


				$block_arr['attribute:input_name'] = ($object) ? "data[" . $object->getId() . "][{$field_name}]" : "data[new][{$field_name}]";
			}

			return def_module::parseTemplate($template_block, $block_arr);
		}

		public function renderEditFieldText($field, $is_multiple, $object, $template) {
			list($template_block) = def_module::loadTemplates("data/reflection/{$template}", "reflection_field_text");

			$block_arr = Array();

			if($is_multiple) {
				//Оно тут не нужно
			} else {
				$field_name = $field->getName();
				$block_arr['attribute:name'] = $field_name;
				$block_arr['attribute:title'] = $field->getTitle();
				$block_arr['attribute:tip'] = $field->getTip();

				$block_arr['node:value'] = ($object) ? $object->getValue($field->getName()) : "";

				if($object) {
					$block_arr['void:object_id'] = $object->getId();
				}


				$block_arr['attribute:input_name'] = ($object) ? "data[" . $object->getId() . "][{$field_name}]" : "data[new][{$field_name}]";
			}

			return def_module::parseTemplate($template_block, $block_arr);
		}


		public function renderEditFieldWYSIWYG($field, $is_multiple, $object, $template) {
			list($template_block) = def_module::loadTemplates("data/reflection/{$template}", "reflection_field_wysiwyg");

			$block_arr = Array();

			if($is_multiple) {
				//Оно тут не нужно
			} else {
				$field_name = $field->getName();
				$block_arr['attribute:name'] = $field_name;
				$block_arr['attribute:title'] = $field->getTitle();
				$block_arr['attribute:tip'] = $field->getTip();

				$block_arr['node:value'] = ($object) ? $object->getValue($field->getName()) : "";

				if($object) {
					$block_arr['void:object_id'] = $object->getId();
				}


				$block_arr['attribute:input_name'] = ($object) ? "data[" . $object->getId() . "][{$field_name}]" : "data[new][{$field_name}]";
			}

			return def_module::parseTemplate($template_block, $block_arr);
		}


		public function renderEditFieldInt($field, $is_multiple, $object, $template) {
			list($template_block) = def_module::loadTemplates("data/reflection/{$template}", "reflection_field_int");

			$block_arr = Array();

			if($is_multiple) {
				//TODO
			} else {
				$field_name = $field->getName();
				$block_arr['attribute:name'] = $field_name;
				$block_arr['attribute:title'] = $field->getTitle();
				$block_arr['attribute:tip'] = $field->getTip();

				$block_arr['node:value'] = ($object) ? $object->getValue($field->getName()) : "";

				if($object) {
					$block_arr['void:object_id'] = $object->getId();
				}


				$block_arr['attribute:input_name'] = ($object) ? "data[" . $object->getId() . "][{$field_name}]" : "data[new][{$field_name}]";
			}

			return def_module::parseTemplate($template_block, $block_arr);
		}

		public function renderEditFieldBoolean($field, $is_multiple, $object, $template) {
			list($template_block) = def_module::loadTemplates("data/reflection/{$template}", "reflection_field_boolean");

			$block_arr = Array();

			if ($is_multiple) {
				// Хм. Зачем? )
			} else {
				$field_name = $field->getName();
				$block_arr['attribute:name'] = $field_name;
				$block_arr['attribute:title'] = $field->getTitle();
				$block_arr['attribute:tip'] = $field->getTip();

				$block_arr['attribute:checked'] = "";
				$block_arr['node:value'] = 0;
				if ($object) {
					$block_arr['node:value'] = (int) $object->getValue($field->getName());
					$block_arr['attribute:checked'] = (bool) $object->getValue($field->getName())? "checked" : "";
					$block_arr['void:object_id'] = $object->getId();
				}


				$block_arr['attribute:input_name'] = ($object) ? "data[" . $object->getId() . "][{$field_name}]" : "data[new][{$field_name}]";
			}

			return def_module::parseTemplate($template_block, $block_arr);
		}

		public function renderEditFieldPassword($field, $is_multiple, $object, $template) {
			list($template_block) = def_module::loadTemplates("data/reflection/{$template}", "reflection_field_password");

			$block_arr = Array();

			if($is_multiple) {
				//TODO
			} else {
				$field_name = $field->getName();
				$block_arr['attribute:name'] = $field_name;
				$block_arr['attribute:title'] = $field->getTitle();
				$block_arr['attribute:tip'] = $field->getTip();

				//$block_arr['value'] = ($object) ? $object->getValue($field->getName()) : "";
				$block_arr['node:value'] = "";

				if($object) {
					$block_arr['void:object_id'] = $object->getId();
				}


				$block_arr['attribute:input_name'] = ($object) ? "data[" . $object->getId() . "][{$field_name}][]" : "data[new][{$field_name}][]";
			}

			return def_module::parseTemplate($template_block, $block_arr);
		}


		public function renderEditFieldRelation($field, $is_multiple, $object, $template) {
			if(!($field instanceof umiField)) return;

			$controller = cmsController::getInstance();
			$objects = umiObjectsCollection::getInstance();
			$guide_items = array();
			$fieldName = $field->getName();

			if($guide_id = $field->getGuideId()) {
				if($controller->getCurrentMode() != "admin") {
					$guide_items = $objects->getGuidedItems($guide_id);
				} else {
					try {
						$sel = new selector('objects');
						$sel->option('return')->value('count');
						$sel->types('object-type')->id($guide_id);
						$total = $sel->length;
					} catch (selectorException $e) {
						$total = 16;
					}

					if ($total <= 15) {
						$sel->flush();
						$sel->option('return')->value('id', 'name');
						foreach($sel->result as $item) {
							$guide_items[$item['id']] = $item['name'];
						}
					}
					else {
						if ($object instanceof iUmiObject) {
							$val = $object->getValue($fieldName);
						}
						else $val = false;

						if ($val && !is_array($val)) {
							$val = Array($val);
						}

						if (is_array($val)) {
							foreach($val as $item_id) {
								$item = $objects->getObject($item_id);
								if ($item instanceof iUmiObject) {
									$guide_items[$item_id] = $item->getName();
								}
							}
							unset($item_id, $item, $val);
						}
					}
				}
			}

			if(sizeof($guide_items) == 0) {
				if($object instanceof iUmiObject) {
					$val = $object->getValue($fieldName);
					if($val && !is_array($val)) $val = array($val);
					if(sizeof($val)) {
						foreach($val as $itemId) {
							if($item = selector::get('object')->id($itemId)) {
								$guide_items[$itemId] = $item->name;
							}
						}
					}
				}
			}

			list(
				$template_block, $template_block_line, $template_block_line_a, $template_mul_block, $template_mul_block_line, $template_mul_block_line_a
			) = def_module::loadTemplates("data/reflection/{$template}",
				"reflection_field_relation", "reflection_field_relation_option", "reflection_field_relation_option_a", "reflection_field_multiple_relation",
				"reflection_field_multiple_relation_option", "reflection_field_multiple_relation_option_a"
			);

			$block_arr = Array();

			$value = $object ? $object->getValue($fieldName) : array();
			if ($fieldName == 'publish_status' && $controller->getCurrentMode() != "admin") {
				return "";
			}
			$block_arr['attribute:name'] = $fieldName;
			$block_arr['attribute:title'] = $field->getTitle();
			$block_arr['attribute:tip'] = $field->getTip();
			if ($is_multiple) $block_arr['attribute:multiple'] = "multiple";

			if ($guide_id) {
				$block_arr['attribute:type-id'] = $guide_id;

				$guide = umiObjectTypesCollection::getInstance()->getType($guide_id);
				if ($guide instanceof umiObjectType) {
					if ($guide->getIsPublic()) {
						$block_arr['attribute:public-guide'] = true;
					}
				}
			}

			$options = ($is_multiple) ? Array() : "";
			foreach($guide_items as $item_id => $item_name) {
				$item_object = $objects->getObject($item_id);
				if (!is_object($item_object)) {
					continue;
				}

				if ($is_multiple) {
					$selected = (in_array($item_id, $value)) ? " selected" : "";
				}
				else $selected = ($item_id == $value) ? " selected" : "";

				if ($item_object->getValue("is_hidden") && !$selected) {
					continue;
				}

				if (!$template_block_line && $controller->getCurrentTemplater() instanceof tplTemplater) {
					$options .= "<option value=\"{$item_id}\"{$selected}>{$item_name}</option>\n";
				}
				else {
					$line_arr = Array();
					$line_arr['attribute:id'] = $item_id;
					$line_arr['xlink:href'] = "uobject://" . $item_id;
					$line_arr['node:name'] = $item_name;
					if ($selected) {
						$line_arr['attribute:selected'] = "selected";
						$line = $is_multiple ? $template_mul_block_line_a : $template_block_line_a;
					}
					else $line = $is_multiple ? $template_mul_block_line : $template_block_line;

					$options[] = def_module::parseTemplate($line, $line_arr, false, $item_id);
				}
			}

			if ($object) $block_arr['void:object_id'] = $object->getId();

			$block_arr['subnodes:values'] = $block_arr['void:options'] = $options;
			$block_arr['attribute:input_name'] = ($object) ? "data[" . $object->getId() . "][{$fieldName}]" . (($is_multiple) ? "[]" : "") : "data[new][{$fieldName}]" . (($is_multiple) ? "[]" : "");

			return def_module::parseTemplate((($is_multiple) ? $template_mul_block : $template_block), $block_arr);
		}



		public function renderEditFieldSymlink($field, $is_multiple, $object, $template) {
			list(
				$template_block, $template_block_line, $template_block_line_a, $template_mul_block, $template_mul_block_line, $template_mul_block_line_a
			) = def_module::loadTemplates("data/reflection/{$template}",
				"reflection_field_relation", "reflection_field_relation_option", "reflection_field_relation_option_a", "reflection_field_multiple_relation", "reflection_field_multiple_relation_option", "reflection_field_multiple_relation_option_a"
			);

			$block_arr = Array();

			if($object) {
				$value = $object->getValue($field->getName());
			} else {
				$value = Array();
			}

			$field_name = $field->getName();
			$block_arr['attribute:name'] = $field_name;
			$block_arr['attribute:title'] = $field->getTitle();
			$block_arr['attribute:tip'] = $field->getTip();

			$options = ($object) ? $object->getValue($field->getName()) : Array();

			$block_arr['subnodes:values'] = $block_arr['void:options'] = $options;
			$block_arr['attribute:input_name'] = ($object) ? "data[" . $object->getId() . "][{$field_name}][]" : "data[new][{$field_name}][]";

			return def_module::parseTemplate($template_block, $block_arr);

		}


		public function renderEditFieldTags($field, $is_multiple, $object, $template) {
			list($template_block) = def_module::loadTemplates("data/reflection/{$template}", "reflection_field_tags");

			$block_arr = Array();

			if($is_multiple) {
				$field_name = $field->getName();
				$block_arr['attribute:name'] = $field_name;
				$block_arr['attribute:title'] = $field->getTitle();
				$block_arr['attribute:tip'] = $field->getTip();

				$value = ($object) ? $object->getValue($field->getName()) : "";
				if(is_array($value)) {
					$value = implode(", ", $value);
				}
				$block_arr['node:value'] = $value;

				if($object) {
					$block_arr['void:object_id'] = $object->getId();
				}

				$block_arr['attribute:input_name'] = ($object) ? "data[" . $object->getId() . "][{$field_name}]" : "data[new][{$field_name}]";
			}

			return def_module::parseTemplate($template_block, $block_arr);
		}

		public function renderEditFieldOptioned($field, $is_multiple, $object, $template) {
			$block_arr = Array();
			$objects = umiObjectsCollection::getInstance();
			$hierarchy = umiHierarchy::getInstance();

			$field_name = $field->getName();
			$block_arr['attribute:name'] = $field_name;
			$block_arr['attribute:title'] = $field->getTitle();
			$block_arr['attribute:tip'] = $field->getTip();

			if($guideId = $field->getGuideId()) {
				$block_arr['attribute:guide-id'] = $guideId;
			}

			$inputName = ($object) ? "data[" . $object->getId() . "][{$field_name}]" : "data[new][{$field_name}]";
			$values = ($object) ? $object->getValue($field->getName()) : Array();


			$values_arr = Array();
			foreach($values as $value) {
				$value_arr = Array();
				foreach($value as $type => $subValue) {
					switch($type) {
						case "tree": {
							$element = $hierarchy->getElement($subValue);
							if($element instanceof umiHierarchyElement) {
								$value_arr['page'] = $element;
							}
							break;
						}

						case "rel": {
							$object = $objects->getObject($subValue);
							if($object instanceof umiObject) {
								$value_arr['object'] = $object;
							}
							break;
						}

						default: {
							$value_arr['attribute:' . $type] = $subValue;
							break;
						}
					}
				}

				$values_arr[] = $value_arr;
			}

			$block_arr['values']['nodes:value'] = $values_arr;
			$block_arr['attribute:input_name'] = $inputName;

			return $block_arr;
		}



		public function renderEditFieldImageFile($field, $is_multiple, $object, $template) {
			list($template_block) = def_module::loadTemplates("data/reflection/{$template}", "reflection_field_img_file");

			$block_arr = Array();

			if($is_multiple) {
				//TODO
			} else {
				$field_name = $field->getName();
				$block_arr['attribute:name'] = $field_name;
				$block_arr['attribute:title'] = $field->getTitle();
				$block_arr['attribute:tip'] = $field->getTip();
				$block_arr['attribute:maxsize'] = $this->getAllowedMaxFileSize("img");

				$value = ($object) ? $object->getValue($field->getName()) : "";


				if($value instanceof umiFile) {
					$block_arr['attribute:relative-path'] = $value->getFilePath(true);

					switch ($field_name) {
						case "menu_pic_ua" : $destination_folder = "./images/cms/menu/"; break;
						case "header_pic" : $destination_folder = "./images/cms/headers/"; break;
						case "menu_pic_a" : $destination_folder = "./images/cms/menu/"; break;
						default : $destination_folder = "./images/cms/data/"; break;
					}

					//$block_arr['node:value'] = basename ($value->getFilePath());
					$info = getPathInfo ($value->getFilePath(true));
					$info['dirname'] = '.'.$info['dirname'];

					$relative_path = substr ($info['dirname'], strlen ($destination_folder))."/".$info['basename'];
					if (substr($relative_path,0,1) == "/") $relative_path = substr($relative_path,1);
					$block_arr['node:value'] = $relative_path;

					$block_arr['attribute:destination-folder'] = $info['dirname'];


				} else {

					$block_arr['node:value'] = "";
					//$block_arr['attribute:destination-folder'] = "";

					$folder_name = $field_name . '/';
					$general_name = "./images/cms/";
					$destination_folder = $general_name . ((is_dir($general_name . $folder_name)) ? $folder_name : '');

					switch ($field_name) {
						case "menu_pic_ua" :
							$pFolder = "menu";
							break;

						case "header_pic" :
							$pFolder = "headers";
							break;

						case "menu_pic_a" :
							$pFolder = "menu";
							break;

						default :
							$pFolder = "data";
							break;
					}

					$block_arr['attribute:destination-folder'] = $destination_folder.$pFolder;
				}

				if($object) {
					$block_arr['void:object_id'] = $object->getId();
				}

				$block_arr['attribute:input_name'] = ($object) ? "data[" . $object->getId() . "][{$field_name}]" : "data[new][{$field_name}]";

			}

			return def_module::parseTemplate($template_block, $block_arr);
		}


		public function renderEditFieldFile($field, $is_multiple, $object, $template) {
			list($template_block) = def_module::loadTemplates("data/reflection/{$template}", "reflection_field_file");

			$regexp = "|^".CURRENT_WORKING_DIR."|";

			$block_arr = Array();

			if($is_multiple) {
				//TODO
			} else {
				$field_name = $field->getName();
				$block_arr['attribute:name'] = $field_name;
				$block_arr['attribute:title'] = $field->getTitle();
				$block_arr['attribute:tip'] = $field->getTip();
				$block_arr['attribute:maxsize'] = $this->getAllowedMaxFileSize();

				$value = ($object) ? $object->getValue($field->getName()) : "";
				if($value) {
					$block_arr['attribute:relative-path'] = $value->getFilePath(true);
					$block_arr['node:value'] = $value->getFilePath();
				} else {
					$block_arr['node:value'] = "";
				}

				if($object) {
					$block_arr['void:object_id'] = $object->getId();
				}

				$block_arr['attribute:input_name'] = ($object) ? "data[" . $object->getId() . "][{$field_name}]" : "data[new][{$field_name}]";

				$folder_name = $field_name . '/';
				$general_name = "./files/";

				if($value instanceof umiFile) {
					if($value->getIsBroken() == false) {
						$value = false;
					}
				}
				if($value) {
					$destination_folder = "." . preg_replace($regexp, "", $value->getDirName());
				} else {
					$destination_folder = $general_name . ((is_dir($general_name . $folder_name)) ? $folder_name : '');
				}
				$block_arr['attribute:destination-folder'] = $destination_folder;
			}

			return def_module::parseTemplate($template_block, $block_arr);
		}


		public function saveEditedObject($object_id, $is_new = false, $b_force_owner = false, $all = false) {
			global $_FILES;
			$cmsController = cmsController::getInstance();
			$permissions = permissionsCollection::getInstance();

			if(!($object = umiObjectsCollection::getInstance()->getObject($object_id))) {
				return false;
			}

			if(!$b_force_owner && !$permissions->isOwnerOfObject($object_id)) {
				return false;
			}

			$object_type_id = $object->getTypeId();
			$object_type = umiObjectTypesCollection::getInstance()->getType($object_type_id);

			$key = ($is_new) ? "new" : $object_id;

			if(is_null(getRequest('data'))) {
				if(is_null($_FILES)) {
					return true;
				} else {
					$_REQUEST['data'][$key] = array();
				}
			}

			$data = isset($_REQUEST['data'][$key]) ? $_REQUEST['data'][$key] : Array();
			foreach($_REQUEST as $skey => $value) {
				$real_key = substr($skey, 7);
				if(substr($skey, 0, 7)=='select_' && !isset($data[$real_key])) {
					$data[$real_key] = $value;
				}
			}

			if(isset($_FILES['data']['tmp_name'][$key])) {
				foreach($_FILES['data']['tmp_name'][$key] as $i=>$v) {
					$data[$i] = $v;
				}
			}

			$data = $this->checkRequiredData($object_type, $data, $object_id, $is_new);

			if ((!$permissions->isSv()) && ($object->getType()->getGUID() == 'users-user')) {
				if (isset($data['groups'])) {
					foreach ($data['groups'] as $i => $v) {
						if ($user_group = umiObjectsCollection::getInstance()->getObject($v)) {
							if ($user_group->getGUID() == 'users-users-15') {
								unset($data['groups'][$i]);
								break;
							}
						}
					}
					if (sizeof($data['groups']) < 1) {
						$data['groups'] = $object->getValue('groups');
					}
				}
				if (isset($data['filemanager_directory'])) {
					unset($data['filemanager_directory']);
				}
			}

			foreach($data as $field_name => $field_value) {

				if(!($field_id = $object_type->getFieldId($field_name))) {
					continue;
				}

				$field = umiFieldsCollection::getInstance()->getField($field_id);

				if(!$field->getIsVisible() && !$all) {
					//continue;
				}

				$field_type = $field->getFieldType();
				$data_type  = $field_type->getDataType();
				$fldr_name  = $field_name . '/';


				switch($data_type) {
					case "password": {
						if(isset($field_value[1])) {
							$field_value = ($field_value[0] == $field_value[1]) ? md5($field_value[0]) : NULL;
						} else {
							if(is_array($field_value)) {
								$field_value = ($field_value[0]) ? md5($field_value[0]) : NULL;
							} else {
								$field_value = ($field_value) ? md5($field_value) : NULL;
							}
						}
						break;
					}

					case "date" : {
						$oDate = new umiDate();
						$oDate->setDateByString($field_value);
						$field_value = $oDate;
						break;
					}

					case "img_file": {
						switch ($field_name) {
							case "menu_pic_ua" : $destination_folder = "./images/cms/menu/"; break;
							case "header_pic" : $destination_folder = "./images/cms/headers/"; break;
							case "menu_pic_a" : $destination_folder = "./images/cms/menu/"; break;
							default : $destination_folder = "./images/cms/data/"; break;
						}

						// TODO: вставить проверку на необходимость наложения на картинку водного знака (by lauri)
						$oldValue = $object->getValue($field_name);
						if($value = umiImageFile::upload("data", $field_name, substr ($destination_folder,2), $key)) {
							$field_value = $value;
						} else {
							$file_name = (substr($field_value, 0, 2) == "./") ? $field_value : ($destination_folder . $field_value);
							$field_value = new umiImageFile($file_name);
						}
						break;
					}

					case "video_file" :
					case "swf_file": {
						$destination_folder = "./files/" . ((is_dir("./files/".$fldr_name))? $fldr_name : '');
						if($value = umiFile::upload("data", $field_name, $destination_folder, $key)) {
							$field_value = $value;
						} else {
							$oldvalue = $object->getValue($field_name);
							if ($oldvalue) {
								$destination_folder = $oldvalue->getDirName() . "/";
							}
							$file_name = (substr($field_value, 0, 2) == "./") ? $field_value : ($destination_folder . $field_value);
							$field_value = new umiFile($file_name);
						}
						break;
					}

					case "file": {
						$destination_folder = "./files/" . ((is_dir("./files/".$fldr_name))? $fldr_name : '');


						if($value = umiFile::upload("data", $field_name, $destination_folder, $key)) {
							$field_value = $value;
						} else {
							$oldvalue = $object->getValue($field_name);
							if ($oldvalue) {
								$destination_folder = $oldvalue->getDirName() . "/";
							}
							$file_name = (substr($field_value, 0, 2) == "./") ? $field_value : ($destination_folder . $field_value);
							$field_value = new umiFile($file_name);
						}

						break;
					}

					case "string":
					case "text":
					case "wysiwyg": {
						if($cmsController->getCurrentMode() != "admin") {
							$field_value = strip_tags($field_value);
						}
						break;
					}
				}
				$object->setValue($field_name, $field_value);
			}
			$object->commit();

			return true;
		}

		/**
			* Проверить, все ли обязательные для заполения поля имеют значения
			* @param umiObjectType $type тип данных редактируемого объекта
			* @param Array $data массив передаваемых значений
			* @param Integer $objectId id текущего объекта
			* @param Boolean $isNew true, если мы создаем новый объект или страницу
			* @param Array массив значений, при необходимости скорректированный
		*/
		public function checkRequiredData(iUmiObjectType $objectType, $data, $objectId, $isNew) {
			if(!is_array($data)) return $data;
			$cmsController = cmsController::getInstance();
			$admin = ($cmsController->getCurrentMode() == "admin");

			$wrongFieldsCount = 0;
			$fields = umiFieldsCollection::getInstance();
			foreach($data as $fieldName => &$value) {
				$fieldId = $objectType->getFieldId($fieldName);
				$field = $fields->getField($fieldId);

				if($field instanceof umiField == false) continue;

				if($field->getIsRequired()) {
					if(is_null($value) || $value === false || $value === "") {
						$fieldTitle = $field->getTitle();

						$errstr = ($admin) ? "%errors_missed_field_value%" : getLabel('error-missed-field-value');
						$this->errorNewMessage($errstr . " \"{$fieldTitle}\"", false, 100, "input-missed-field");
						++$wrongFieldsCount;
					}
				}

				if($restrictionId = $field->getRestrictionId()) {
					$restriction = baseRestriction::get($restrictionId);
					if($restriction instanceof baseRestriction) {
						if($restriction instanceof iNormalizeInRestriction) {
							$value = $restriction->normalizeIn($value);
						}

						if($restriction->validate($value) == false) {
							$fieldTitle = $field->getTitle();

							$errstr = ($admin) ? "%errors_wrong_field_value%" : getLabel('error-wrong-field-value');
							$errstr .=  " \"{$fieldTitle}\" - " . $restriction->getErrorMessage();

							$this->errorNewMessage($errstr, false, 101, "input-wrong-field");
							++$wrongFieldsCount;
						}
					}
				}
			}

			if($wrongFieldsCount > 0) {
				if($isNew && $objectId) {
					//Delete object and page if exists (thes don't fit for us)
					$hierarchy = umiHierarchy::getInstance();
					$elementIds = $hierarchy->getObjectInstances($objectId);
					if(sizeof($elementIds)) {
						//Delete created page. For ever.
						foreach($elementIds as $elementId) {
							$hierarchy->delElement($elementId);
							$hierarchy->removeDeletedElement($elementId);
						}
					}
					umiObjectsCollection::getInstance()->delObject($objectId);
				}

				$this->errorPanic();
			}

			return $data;
		}

	};
?>