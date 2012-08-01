<?php
	abstract class __emarket_compare {
		public function compare($template = "default", $groups_names = '') {
			if($this->breakMe()) return;
			if(!$template) $template = "default";
			list(
				$template_block, $template_block_empty, $template_block_header, $template_block_header_item,
				$template_block_line, $template_block_line_item, $template_list_block, $template_list_block_line
			) = $this->loadTemplates(
				"emarket/compare/{$template}", "compare_block", "compare_block_empty", "compare_block_header",
				"compare_block_header_item", "compare_block_line", "compare_block_line_item", "compare_list_block",
				"compare_list_block_line"
			);
			$elements = $this->getCompareElements();

			if(sizeof($elements) == 0) return $template_block_empty;

			$hierarchy = umiHierarchy::getInstance();
			$block_arr = array(); $items = array();
			$headers = array(); $headers_arr = array();
			foreach($elements as $element_id) {
				$element = $hierarchy->getElement($element_id);
				if(!$element) continue;

				$item_arr = array(
					'attribute:id'		=> $element_id,
					'attribute:link'	=> $hierarchy->getPathById($element_id),
					'node:title'		=> $element->getName()
				);

				$items[] = def_module::parseTemplate($template_block_header_item, $item_arr, $element_id);
			}
			$headers_arr['subnodes:items'] = $items;
			$headers = def_module::parseTemplate($template_block_header, $headers_arr);

			$fields = array();
			foreach($elements as $element_id) {
				$fields = array_merge($fields, $this->getComparableFields($element_id,$groups_names));
			}

			$lines = array(); $iCnt = 0;
			foreach($fields as $field_name => $field) {
				$field_title = $field->getTitle();

				$items = array(); $is_void = true;
				foreach($elements as $element_id) {
					$element = $hierarchy->getElement($element_id);

					$item_arr = array(
						'attribute:id'		=> $element_id,
						'void:name'			=> $field_name,
						'void:field_name'	=> $field_name,
						'value'				=> $element->getObject()->getPropByName($field_name)
					);

					if($is_void && $element->$field_name) $is_void = false;
					$items[] = def_module::parseTemplate($template_block_line_item, $item_arr, $element_id);
				}

				if($is_void) continue;

				$iCnt++;

				$line_arr = array(
					'attribute:title'	=> $field_title,
					'attribute:name'	=> $field_name,
					'attribute:type'	=> $field->getFieldType()->getDataType(),
					'attribute:par'		=> intval($iCnt / 2 == ceil($iCnt / 2)),
					'subnodes:values'	=> $line_arr['void:items'] = $items
				);
				$lines[] = def_module::parseTemplate($template_block_line, $line_arr);
			}

			$block_arr['headers'] = $headers;
			$block_arr['void:lines'] = $block_arr['void:fields'] = $lines;
			$block_arr['fields'] = array();
			$block_arr['fields']['nodes:field'] = $lines;
			return def_module::parseTemplate($template_block, $block_arr);
		}


		public function getCompareElements() {
			static $elements;
			if(is_array($elements)) {
				return $elements;
			}

			if(!is_array(getSession("compare_list"))) {
				$_SESSION['compare_list'] = array();
			}

			if(is_array(getRequest('compare_list'))) {
				$_SESSION['compare_list'] = getRequest('compare_list');
			}

			$elements = getSession("compare_list");
			$elements = array_unique($elements);
			return $elements;
		}


		public function getComparableFields($element_id, $groups_names = '') {
			$element = umiHierarchy::getInstance()->getElement($element_id);

			if(!$element) return false;


			$type_id = $element->getObject()->getTypeId();
			$type = umiObjectTypesCollection::getInstance()->getType($type_id);
			if(empty($groups_names)) {
				$fields = $type->getAllFields(true);
			}
			else {
				$groups_names = trim($groups_names);
				$groups_names = strlen($groups_names) ? explode(" ", $groups_names) : array();

				$groups_arr =  $type->getFieldsGroupsList();
				$fields = array();
				foreach($groups_arr as $group) {
					if(!$group->getIsActive()) continue;
					if(!in_array($group->getName(), $groups_names)) {
						continue;
					}
					$fields = array_merge($fields,$group->getFields());
				}
			}

			$res = array();

			foreach($fields as $field) {
				if(!$field->getIsVisible()) continue;
				if(($field_name = $field->getName()) == "price") continue;
				$res[$field_name] = $field;
			}

			return $res;
		}


		public function addToCompare() {
			$this->add_to_compare(getRequest("param0"));
			$this->redirect(getServer('HTTP_REFERER'));
		}


		public function jsonAddToCompareList() {
			$element_id = getRequest("param0");

			list($add_to_compare_tpl, $already_exists_tpl) = $this->loadTemplates("emarket/compare/default", "json_add_to_compare", "json_compare_already_exists");

			$template = $this->add_to_compare($element_id) ? $add_to_compare_tpl : $already_exists_tpl;

			$block_arr = array('id' => $element_id);

			header("Content-type: text/javascript; charset=utf-8");
			$this->flush(def_module::parseTemplate($template, $block_arr, $element_id));
		}


		public function removeFromCompare() {
			$this->remove_from_compare(getRequest("param0"));

			$referer = getServer('HTTP_REFERER');
			if(stristr(getServer('HTTP_USER_AGENT'), 'msie')) {
				$referer = preg_replace(array("/\b\d{10,}\b/", "/&{2,}/", "/&$/"), array("", "&", ""), $referer);
				$referer.= (strstr($referer, "?") ? "&" : "?") . time();
				$referer = str_replace("?&", "?", $referer);
			}
			$this->redirect($referer);
		}


		public function jsonRemoveFromCompare() {
			$element_id = getRequest("param0");
			$this->remove_from_compare($element_id);

			list($template) = $this->loadTemplates("emarket/compare/default", "json_remove_from_compare");

			$block_arr = array('id' => $element_id);

			header("Content-type: text/javascript; charset=utf-8");
			$this->flush($template, $block_arr, $element_id);
		}


		public function resetCompareList() {
			$this->reset_compare();
			$this->redirect(getServer('HTTP_REFERER'));
		}

		public function jsonResetCompareList() {
			$this->reset_compare();

			list($template) = $this->loadTemplates("emarket/compare/default", "json_reset_compare_list");

			header("Content-type: text/javascript; charset=utf-8");
			$this->flush($template);

		}

		public function getCompareList($template = "default") {
			if(!$template) $tempalte = "default";

			list(
				$template_block, $template_block_empty, $template_block_line, $template_block_link
			) = $this->loadTemplates("emarket/compare/{$template}",
				"compare_list_block", "compare_list_block_empty", "compare_list_block_line", "compare_list_block_link"
			);

			$block_arr = array();

			$elements = $this->getCompareElements();

			if(sizeof($elements) == 0) {
				$block_arr['void:max_elements'] = $this->iMaxCompareElements ? $this->iMaxCompareElements : "не ограничено";
				if ($this->iMaxCompareElements) {
					$block_arr['attribute:max-elements'] = $this->iMaxCompareElements;
				}
				return def_module::parseTemplate($template_block_empty, $block_arr);
			}

			$items = "";

			$hierarchy = umiHierarchy::getInstance();
			foreach($elements as $element_id) {
				$el = $hierarchy->getElement($element_id);
				if ($el instanceof iUmiHierarchyElement) {
					$line_arr = array();
					$line_arr['attribute:id'] = $element_id;
					$line_arr['node:value'] = $el->getName();
					$line_arr['attribute:link'] = umiHierarchy::getInstance()->getPathById($element_id);
					$line_arr['xlink:href'] = 'upage://' . $element_id;
					$items[] = def_module::parseTemplate($template_block_line, $line_arr, $element_id);
				}
			}

			$block_arr['compare_link'] = (sizeof($elements) >= 2) ? $template_block_link : "";
			$block_arr['void:max_elements'] = $this->iMaxCompareElements ? $this->iMaxCompareElements : "не ограничено";
			if ($this->iMaxCompareElements) {
				$block_arr['attribute:max-elements'] = $this->iMaxCompareElements;
			}

			$block_arr['subnodes:items'] = $items;
			return def_module::parseTemplate($template_block, $block_arr);
		}


		public function reset_compare() {
			$_SESSION['compare_list'] = array();
		}

		public function add_to_compare($element_id) {
			if(!isset($_SESSION['compare_list']) || !is_array($_SESSION['compare_list'])) {
				$_SESSION['compare_list'] = array();
			}

			if ($this->iMaxCompareElements && count($_SESSION['compare_list']) >= $this->iMaxCompareElements) {
				$this->errorNewMessage("%errors_max_items_compare%");
				$this->errorPanic();
			}

			$oEventPoint = new umiEventPoint("emarket_add_to_compare");
			$oEventPoint->setMode("before");
			$oEventPoint->setParam("element_id", $element_id);
			$oEventPoint->setParam("compare_list", $_SESSION['compare_list']);
			$this->setEventPoint($oEventPoint);

			if(!in_array($element_id, $_SESSION['compare_list'])) {
				$_SESSION['compare_list'][] = $element_id;
				$oEventPoint = new umiEventPoint("emarket_add_to_compare");
				$oEventPoint->setMode("after");
				$oEventPoint->setParam("element_id", $element_id);
				$oEventPoint->setParam("compare_list", $_SESSION['compare_list']);
				$this->setEventPoint($oEventPoint);
				return true;
			}
			return false;
		}


		public function remove_from_compare($element_id) {
			if(!is_array($_SESSION['compare_list'])) {
				$_SESSION['compare_list'] = array();
				return;
			}

			if(in_array($element_id, $_SESSION['compare_list'])) {
				unset($_SESSION['compare_list'][array_search($element_id, $_SESSION['compare_list'])]);
			}

		}


		public function getCompareLink($elementId = null, $template = 'default') {
			if(!$elementId) return;
			if(!$template) $template = "default";
			list($tpl_add_link, $tpl_del_link) = def_module::loadTemplates("emarket/compare/{$template}", 'add_link', 'del_link');
			$elements = $this->getCompareElements();
			$inCompare = in_array($elementId, $elements);

			$addLink = $this->pre_lang . '/emarket/addToCompare/' . $elementId . '/';
			$delLink = $this->pre_lang . '/emarket/removeFromCompare/' . $elementId . '/';

			$block_arr = array(
				'add-link' => $inCompare ? null : $addLink,
				'del-link' => $inCompare ? $delLink : null
			);
			return def_module::parseTemplate(($inCompare ? $tpl_del_link : $tpl_add_link), $block_arr, $elementId);
		}
	};
?>