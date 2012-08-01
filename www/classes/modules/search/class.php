<?php
	class search extends def_module {

		public function __construct() {
			parent::__construct();

			if(cmsController::getInstance()->getCurrentMode() == "admin") {
				$this->__loadLib("__admin.php");
				$this->__implement("__search");
			} else {
				$this->__loadLib("__custom.php");
				$this->__implement("__custom_search");

				$this->per_page = regedit::getInstance()->getVal("//modules/search/per_page");
			}
		}

		public function search_do($template = "default", $search_string = "", $search_types = "", $search_branches = "", $per_page = 0) {
			list(
				$template_block, $template_line, $template_empty_result, $template_line_quant
			) = self::loadTemplates("search/".$template,
				"search_block", "search_block_line", "search_empty_result", "search_block_line_quant"
			);

			// поисковая фраза :
			if (!$search_string) $search_string = (string) getRequest('search_string');

			$search_string = urldecode($search_string);
			$search_string = htmlspecialchars($search_string);
			$search_string = str_replace(".", " ", $search_string);
			$search_string = trim($search_string, " \t\r\n%");
			$search_string = str_replace(array('"', "'"), "", $search_string);
			
			$orMode = (bool) getRequest('search-or-mode');

			if (!$search_string) return $this->insert_form($template);

			// если запрошен поиск только по определенным веткам :
			$arr_search_by_rels = array();
			if (!$search_branches) $search_branches = (string) getRequest('search_branches');
			$search_branches = trim(rawurldecode($search_branches));
			if (strlen($search_branches)) {
				$arr_branches = preg_split("/[\s,]+/", $search_branches);
				foreach ($arr_branches as $i_branch => $v_branch) {
					$arr_branches[$i_branch] = $this->analyzeRequiredPath($v_branch);
				}
				$arr_branches = array_map('intval', $arr_branches);
				$arr_search_by_rels = array_merge($arr_search_by_rels, $arr_branches);
				$o_selection = new umiSelection;
				$o_selection->addHierarchyFilter($arr_branches, 100, true);
				$o_result = umiSelectionsParser::runSelection($o_selection);
				$sz = sizeof($o_result);
				for ($i = 0; $i < $sz; $i++) $arr_search_by_rels[] = intval($o_result[$i]);
			}
			// если запрошен поиск только по определенным типам :
			if (!$search_types) $search_types = (string) getRequest('search_types');
			$search_types = rawurldecode($search_types);
			if (strlen($search_types)) {
				$search_types = preg_split("/[\s,]+/", $search_types);
				$search_types = array_map('intval', $search_types);
			}

			$block_arr = Array();

			$lines = Array();
			$result = searchModel::getInstance()->runSearch($search_string, $search_types, $arr_search_by_rels, $orMode);
			$p = (int) getRequest('p');
			$total = sizeof($result);

			// если запрошена нетипичная постраничка
			if (!$per_page) $per_page = intval(getRequest('per_page'));
			if (!$per_page) $per_page = $this->per_page;

			$result = array_slice($result, $per_page * $p, $per_page);

			$i = $per_page * $p;

			foreach($result as $num => $element_id) {
				$line_arr = Array();

				$element = umiHierarchy::getInstance()->getElement($element_id);

				if(!$element) continue;

				$line_arr['void:num'] = ++$i;
				$line_arr['attribute:id'] = $element_id;
				$line_arr['attribute:name'] = $element->getName();
				$line_arr['attribute:link'] = umiHierarchy::getInstance()->getPathById($element_id);
				$line_arr['xlink:href'] = "upage://" . $element_id;
				$line_arr['node:context'] = searchModel::getInstance()->getContext($element_id, $search_string);
				$line_arr['void:quant'] = ($num < count($result)-1? self::parseTemplate($template_line_quant, array()) : "");
				$lines[] = self::parseTemplate($template_line, $line_arr, $element_id);

				$this->pushEditable(false, false, $element_id);
				
				umiHierarchy::getInstance()->unloadElement($element_id);
			}

			$block_arr['subnodes:items'] = $block_arr['void:lines'] = $lines;
			$block_arr['total'] = $total;
			$block_arr['per_page'] = $per_page;
			$block_arr['last_search_string'] = "";

			return self::parseTemplate(($total > 0 ? $template_block : $template_empty_result), $block_arr);
		}


		public function insert_form($template = "default") {
			if(defined("DB_DRIVER") && DB_DRIVER == "xml") return;
			list($template_block) = self::loadTemplates("search/".$template, "search_form");

			$search_string = (string) getRequest('search_string');
			$search_string = strip_tags($search_string);
			$search_string = trim($search_string, " \t\r\n%");
			$search_string = htmlspecialchars(urldecode($search_string));			
			$search_string = str_replace(array('"', "'"), "", $search_string);
			
			$orMode = (bool) getRequest('search-or-mode');

			$block_arr = Array();
			$block_arr['last_search_string'] = ($search_string) ? $search_string : "%search_input_text%";
			
			if($orMode) {
				$block_arr['void:search_mode_and_checked'] = "";
				$block_arr['void:search_mode_or_checked'] = " checked";
			} else {
				$block_arr['void:search_mode_and_checked'] = " checked";
				$block_arr['void:search_mode_or_checked'] = "";
			}
			return self::parseTemplate($template_block, $block_arr);
		}
		
		public function suggestions($template = 'default', $string = false, $limit = 10) {
			if($string == false) $string = getRequest('suggest-string');
			
			list($template_block, $template_line, $template_block_empty) = self::loadTemplates(
				"tpls/search/".$template, "suggestion_block", "suggestion_block_line", "suggestion_block_empty"
			);

			$search = searchModel::getInstance();
			$words = $search->suggestions($string, $limit);
			$total = sizeof($words);
			
			if($total == 0) {
				return self::parseTemplate($template_block_empty, array());
			}
			
			$items_arr = array();
			foreach($words as $word) {
				$item_arr = array(
					'attribute:count'	=> $word['cnt'],
					'node:word'			=> $word['word']
				);
				
				$items_arr[] = self::parseTemplate($template_line, $item_arr);
			}

			$block_arr = array(
				'words'	=> array('nodes:word' => $items_arr),
				'total'	=> $total
			);
			
			return self::parseTemplate($template_block, $block_arr);
		}
	};
?>