<?php
class catalog_custom extends def_module {

    public function check_object_qty() {
        $id = intval(getRequest('id'));

        $sql = 'SELECT sum(val.int_val) FROM cms3_hierarchy_relations as rel, cms3_objects as obj, cms3_hierarchy AS hy, cms3_object_content AS val
            where rel.rel_id = '.$id.' and hy.id = rel.child_id and obj.id = hy.obj_id and val.obj_id = obj.id and val.field_id = 221 and val.int_val IS NOT NULL and val.int_val > 0';
		$result = l_mysql_query($sql);
		$row = mysql_fetch_row($result);
        return array('qty' => intval($row[0]));

    }

	public function getObjectsList($template = "default", $path = false, $limit = false, $ignore_paging = false, $i_need_deep = 0, $field_id = false, $asc = true) {
		if(!$template) $template = "default";

		if (!$i_need_deep) $i_need_deep = intval(getRequest('param4'));
		if (!$i_need_deep) $i_need_deep = 0;
		$i_need_deep = intval($i_need_deep);
		if ($i_need_deep === -1) $i_need_deep = 100;

        $hierarchy = umiHierarchy::getInstance();
        
		list($template_block, $template_block_empty, $template_block_search_empty, $template_line) = def_module::loadTemplates("catalog/".$template, "objects_block", "objects_block_empty", "objects_block_search_empty", "objects_block_line");

		$hierarchy_type_id = umiHierarchyTypesCollection::getInstance()->getTypeByName("catalog", "object")->getId();

		$category_id = $this->analyzeRequiredPath($path);

		if($category_id === false && $path != KEYWORD_GRAB_ALL) {
			throw new publicException(getLabel('error-page-does-not-exist', null, $path));
		}

		$category_element = $hierarchy->getElement($category_id);
        $object = $category_element->getObject(); 
        $category_type_id = $category_element->getTypeid();

		$per_page = ($limit) ? $limit : $this->per_page;
		$curr_page = getRequest('p');
		if($ignore_paging) $curr_page = 0;

		$sel = new umiSelection;
		$sel->setElementTypeFilter();
		$sel->addElementType($hierarchy_type_id);

		if($path != KEYWORD_GRAB_ALL) {
			$sel->setHierarchyFilter();
			$sel->addHierarchyFilter($category_id, $i_need_deep);
		}

		$sel->setPermissionsFilter();
		$sel->addPermissions();

		$hierarchy_type = umiHierarchyTypesCollection::getInstance()->getType($hierarchy_type_id);
		$type_id = umiObjectTypesCollection::getInstance()->getBaseType($hierarchy_type->getName(), $hierarchy_type->getExt());


		if($path === KEYWORD_GRAB_ALL) {
			$curr_category_id = cmsController::getInstance()->getCurrentElementId();
		} else {
			$curr_category_id = $category_id;
		}


		if($path != KEYWORD_GRAB_ALL) {
			$type_id = $hierarchy->getDominantTypeId($curr_category_id, $i_need_deep, $hierarchy_type_id);
		}

		if(!$type_id) {
			$type_id = umiObjectTypesCollection::getInstance()->getBaseType($hierarchy_type->getName(), $hierarchy_type->getExt());
		}


		if($type_id) {
			$this->autoDetectOrders($sel, $type_id);
			$this->autoDetectFilters($sel, $type_id);

			if($this->isSelectionFiltered) {
				$template_block_empty = $template_block_search_empty;
				$this->isSelectionFiltered = false;
			}
		} else {
			$sel->setOrderFilter();
			$sel->setOrderByName();
		}
		if($curr_page !== "all" && $category_type_id == 49) {
			$curr_page = (int) $curr_page;
			$sel->setLimitFilter();
			$sel->addLimit($per_page, $curr_page);
        }

		if($field_id) {
			if (is_numeric($field_id)) {
				$sel->setOrderByProperty($field_id, $asc);
			} else {
				if ($type_id) {
					$field_id = umiObjectTypesCollection::getInstance()->getType($type_id)->getFieldId($field_id);
					if ($field_id) {
						$sel->setOrderByProperty($field_id, $asc);
					} else {
						$sel ->setOrderByOrd($asc);
					}
				} else {
					$sel ->setOrderByOrd($asc);
				}
			}
		}
		else {
			$sel ->setOrderByOrd($asc);
		}

		$result = umiSelectionsParser::runSelection($sel);
		$total = umiSelectionsParser::runSelectionCounts($sel);

        //если мы находимся не в карточке товара
        if($category_type_id) {
            $sz = sizeof($result);
            for ($j = 0; $j < $sz; $j++) {
                $element = umiHierarchy::getInstance()->getElement($result[$j]);
                $childs = array_keys($hierarchy->getChilds($element->id));

                $is_empty = true;
                $i = 0;
                while ($is_empty && $i < sizeof($childs)) {
                    $object = umiHierarchy::getInstance()->getElement($childs[$i])->getObject();
                    $is_empty = intval($object->getValue('common_quantity')) == 0; 
                    $i++;
                }


                if ($is_empty) {
                    unset($result[$j]);
                    $total--;
                }
            }
            $result = array_values($result);

            if($curr_page !== "all") {
                $curr_page = (int) $curr_page;
                $per_page = (int) $per_page;
                if ($curr_page != 0) {
                    $start = $curr_page*$per_page;
                } else {
                    $start = 0;
                }

                $result = array_slice($result, $start, $per_page);
            }
        }

		if(($sz = sizeof($result)) > 0) {
			$block_arr = Array();

			$lines = Array();
			for($i = 0; $i < $sz; $i++) {
				$element_id = $result[$i];
				$element = umiHierarchy::getInstance()->getElement($element_id);

				if(!$element) continue;

				$line_arr = Array();
				$line_arr['attribute:id'] = $element_id;
				$line_arr['attribute:alt_name'] = $element->getAltName();
				$line_arr['attribute:link'] = umiHierarchy::getInstance()->getPathById($element_id);
				$line_arr['xlink:href'] = "upage://" . $element_id;
				$line_arr['node:text'] = $element->getName();

				$lines[] = self::parseTemplate($template_line, $line_arr, $element_id);

				$this->pushEditable("catalog", "object", $element_id);
				umiHierarchy::getInstance()->unloadElement($element_id);
			}
			$block_arr['subnodes:lines'] = $lines;
			$block_arr['numpages'] = umiPagenum::generateNumPage($total, $per_page);
			$block_arr['total'] = $total;
			$block_arr['per_page'] = $per_page;
			$block_arr['category_id'] = $category_id;

			if($type_id) {
				$block_arr['type_id'] = $type_id;
			}

			return self::parseTemplate($template_block, $block_arr, $category_id);
		} else {
			$block_arr['numpages'] = umiPagenum::generateNumPage(0, 0);
			$block_arr['lines'] = "";
			$block_arr['total'] = 0;
			$block_arr['per_page'] = 0;
			$block_arr['category_id'] = $category_id;

			return self::parseTemplate($template_block_empty, $block_arr, $category_id);;
		}
    }

};
?>
