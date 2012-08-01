<?php
/*
*/

class __pages_mklist_by_tags {
	/*
	*/

	public function pages_mklist_by_tags($s_tags, $i_domain_id = NULL, $s_template = "tags", $i_per_page = false, $b_ignore_paging = false, $s_base_types = '') {
		/*
		*/

		// init and context :
		$s_tpl_pages = "pages";
		$s_tpl_page = "page";
		$s_tpl_pages_empty = "pages_empty";

		// validate input :

		$i_per_page = intval($i_per_page);
		if (!$i_per_page) $i_per_page = 10;
		if ($i_per_page === -1) $b_ignore_paging = true;

		$s_template = strval($s_template);
		if (!strlen($s_template)) $s_template = "tags";

		$i_curr_page = intval(getRequest('p'));
		if ($b_ignore_paging) $i_curr_page = 0;

		$s_base_types = strval($s_base_types);

		// load templates :

		list(
			$tpl_pages,
			$tpl_page,
			$tpl_pages_empty
		) = def_module::loadTemplates("content/".$s_template,
			$s_tpl_pages,
			$s_tpl_page,
			$s_tpl_pages_empty
		);

		// process :

		$o_sel = new umiSelection();

		if ($i_domain_id) {
			$o_sel->setIsDomainIgnored(false);
		} else {
			$o_sel->setIsDomainIgnored(true);
		}

		if (strlen($s_base_types)) {
			$o_sel->setElementTypeFilter();
			$arr_base_types = preg_split("/\s+/is", $s_base_types);
			foreach ($arr_base_types as $s_next_type) {
				$arr_next_type = explode('.', $s_next_type);
				if (count($arr_next_type) === 2) {
					$o_hierarchy_type = umiHierarchyTypesCollection::getInstance()->getTypeByName($arr_next_type[0], $arr_next_type[1]);
					if ($o_hierarchy_type instanceof umiHierarchyType) {
						$i_hierarchy_type_id = $o_hierarchy_type->getId();
						$o_sel->addElementType($i_hierarchy_type_id);
					}
				}
			}
		}

		$o_sel->forceHierarchyTable();

		$o_object_type = umiObjectTypesCollection::getInstance()->getTypeByGUID('root-pages-type');
		$i_tags_field_id = $o_object_type->getFieldId('tags');
		$arr_tags = preg_split("/\s*,\s*/is", $s_tags);
		$o_sel->setPropertyFilter();
		$o_sel->addPropertyFilterEqual($i_tags_field_id, $arr_tags);

		$o_sel->setPermissionsFilter();
		$o_sel->addPermissions();

		if ($i_per_page !== -1) {
			$o_sel->setLimitFilter();
			$o_sel->addLimit($i_per_page, $i_curr_page);
		}

		$result = umiSelectionsParser::runSelection($o_sel);
		$total = umiSelectionsParser::runSelectionCounts($o_sel);
		$block_arr = array();

		if (($sz = sizeof($result)) > 0) {
			$arr_items = array();
			for ($i = 0; $i < $sz; $i++) {
				$line_arr = Array();
				$element_id = intval($result[$i]);

				$element = umiHierarchy::getInstance()->getElement($element_id);

				if(!$element) continue;

				$line_arr['attribute:id'] = $element_id;
				$line_arr['node:name'] = $element->getName();
				$line_arr['attribute:link'] = umiHierarchy::getInstance()->getPathById($element_id);
				$line_arr['void:header'] = $lines_arr['name'] = $element->getName();

				if ($publish_time = $element->getValue('publish_time')) {
					$line_arr['attribute:publish_time'] = $publish_time->getFormattedDate("U");
				}

				$arr_items[] = def_module::parseTemplate($tpl_page, $line_arr, $element_id);

				umiHierarchy::getInstance()->unloadElement($element_id);
			}

			$block_arr['subnodes:items'] = $arr_items;
			$block_arr['tags'] = $s_tags;

			$block_arr['total'] = $total;
			$block_arr['per_page'] = $i_per_page;

			return def_module::parseTemplate($tpl_pages, $block_arr);
		}
		else return def_module::parseTemplate($tpl_pages_empty, $block_arr);
	}
}