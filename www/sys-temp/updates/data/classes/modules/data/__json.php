<?php

class __json_data {
	public function json_move_field_after() {
		$field_id = (int) getRequest('param0');
		$before_field_id = (int) getRequest('param1');
		$is_last = (string) getRequest('param2');
		$type_id = (int) getRequest('param3');

		if($is_last != "false") {
			$new_group_id = (int) $is_last;
		} else {
			$sql = <<<SQL
SELECT fc.group_id FROM cms3_object_field_groups ofg, cms3_fields_controller fc WHERE ofg.type_id = '{$type_id}' AND fc.group_id = ofg.id AND fc.field_id = '{$before_field_id}'
SQL;
			$result = l_mysql_query($sql);
			list($new_group_id) = mysql_fetch_row($result);
		}

			$sql = <<<SQL
SELECT fc.group_id FROM cms3_object_field_groups ofg, cms3_fields_controller fc WHERE ofg.type_id = '{$type_id}' AND fc.group_id = ofg.id AND fc.field_id = '{$field_id}'
SQL;
			$result = l_mysql_query($sql);
			list($group_id) = mysql_fetch_row($result);
		if($is_last == "false") {
			$after_field_id = $before_field_id;
		} else {
			$sql = "SELECT field_id FROM cms3_fields_controller WHERE group_id = '{$group_id}' ORDER BY ord DESC LIMIT 1";

			$result = l_mysql_query($sql);
			if(mysql_num_rows($result)) {
				list($after_field_id) = mysql_fetch_row($result);
			} else {
				$after_field_id = 0;
			}
		}

		$res = (string) (int) umiObjectTypesCollection::getInstance()->getType($type_id)->getFieldsGroup($group_id)->moveFieldAfter($field_id, $after_field_id, $new_group_id, ($is_last != "false") ? false : true);
		$res = "";
		$this->flush($res);
	}


	public function json_move_group_after() {
		$group_id = (int) getRequest('param0');
		$before_group_id = (string) getRequest('param1');
		$type_id = (int) getRequest('param2');

		if($before_group_id != "false") {
			$sql = "SELECT ord FROM cms3_object_field_groups WHERE type_id = '{$type_id}' AND id = '".((int) $before_group_id)."'";
			$result = l_mysql_query($sql);
			if(!(list($neword) = mysql_fetch_row($result))) {
				$neword = 0;
			}
		} else {
			$sql = "SELECT MAX(ord) FROM cms3_object_field_groups WHERE type_id = '{$type_id}'";
			$result = l_mysql_query($sql);
			if(list($neword) = mysql_fetch_row($result)) {
				$neword += 5;
			} else {
				$neword = 5;
			}
		}

		//Getting previous group-target id
		$sql = "SELECT id FROM cms3_object_field_groups WHERE type_id = '{$type_id}' AND ord < '{$neword}' ORDER BY ord DESC LIMIT 1";
		$result = l_mysql_query($sql);
		if(!(list($after_group_id) = mysql_fetch_row($result))) {
			$after_group_id = 0;
		}

		umiObjectTypesCollection::getInstance()->getType($type_id)->setFieldGroupOrd($group_id, $neword, ($before_group_id == "false") ? true : false);

		$this->flush();
	}


	public function json_delete_field() {
		$field_id = (int) getRequest('param0');
		$type_id = (int) getRequest('param1');

		$sql = "SELECT fc.group_id FROM cms3_object_field_groups ofg, cms3_fields_controller fc WHERE ofg.type_id = '{$type_id}' AND fc.group_id = ofg.id AND fc.field_id = '{$field_id}'";
		$result = l_mysql_query($sql);
		list($group_id) = mysql_fetch_row($result);

		umiObjectTypesCollection::getInstance()->getType($type_id)->getFieldsGroup($group_id)->detachField($field_id);

		$this->flush();
	}


	public function json_delete_group() {
		$group_id = (int) getRequest('param0');
		$type_id = (int) getRequest('param1');

		umiObjectTypesCollection::getInstance()->getType($type_id)->delFieldsGroup($group_id);

		$this->flush("");
	}


	public function json_load_hierarchy_level() {
		$field_id = (string) getRequest('param0');
		$parent_id = (int) getRequest('param1');
		$domain_id = (int) getRequest('param2');

		if(((string) $domain_id) != getRequest('param2')) {
			$domain_host = getRequest('param2');
			$domain_id = domainsCollection::getInstance()->getDomainId($domain_host);
		}


		$domain_id = $domain_id ? $domain_id : false;

		$sel = new umiSelection;
		$sel->setDomainId($domain_id);
		
		$elementTypes = getRequest('element-types');
		if(is_array($elementTypes)) {
			$elementsFilter = true;
			foreach($elementTypes as $elementTypeId) {
				$sel->addElementType($elementTypeId);
			}
			
			if($parent_id == 0) {
				$sel->optimize_root_search_query = true;
			}
		} else {
			$elementsFilter = false;
		}
		
		$baseSel = clone $sel;
		$sel->addHierarchyFilter($parent_id);
		$result = umiSelectionsParser::runSelection($sel);

		$res = "var res = new Array();\n";

		$hierarchy = umiHierarchy::getInstance();
		if($parent_id != 0) {
			$parent_parent_id = $hierarchy->getParent($parent_id);
			$res .= "res[res.length] = new Array('{$parent_parent_id}', '..');\n";
		}

		foreach($result as $element_id) {
			$element = $hierarchy->getElement($element_id);
			if($element instanceof umiHierarchyElement == false) {
				continue;
			}
			$name = $element->getName();
			$name = mysql_escape_string($name);

			if($elementsFilter) {
				$childsSel = clone $baseSel;
				$childsSel->optimize_root_search_query = false;
				$childsSel->addHierarchyFilter($element_id);
				$childsCount = umiSelectionsParser::runSelectionCounts($childsSel);
			} else {
				$childsCount = $hierarchy->getChildsCount($element_id, true, true, 1, false, $domain_id);
			}
			if($childsCount) {
				$name .= " ({$childsCount})";
			}

			$res .= "res[res.length] = new Array('{$element_id}', '{$name}');\n";
		}
		$res .= "window.symlinkInputCollectionInstance.getObj('{$field_id}').onLoad(res);\n";
		
		header("Content-type: text/javascript; charset=utf-8");
		$this->flush($res);
	}
}

?>