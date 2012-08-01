<?php

	class umiPagenum implements iPagenum {
		public static $max_pages = 5;

		public static function generateNumPage($total, $per_page, $template = "default", $varName = "p", $max_pages = false) {
			$per_page = intval($per_page);
			if($per_page == 0) $per_page = $total;
			if(!$template) $template = "default";
			if(!$varName) $varName = "p";
			list(
				$template_block, $template_block_empty, $template_item, $template_item_a, $template_quant,
				$template_tobegin, $template_tobegin_a, $template_toend, $template_toend_a, $template_toprev,
				$template_toprev_a, $template_tonext, $template_tonext_a
			) = def_module::loadTemplates("numpages/".$template,
				"pages_block", "pages_block_empty", "pages_item", "pages_item_a", "pages_quant", "pages_tobegin",
				"pages_tobegin_a", "pages_toend", "pages_toend_a", "pages_toprev", "pages_toprev_a", "pages_tonext", "pages_tonext_a"
			);

			$isXslt = def_module::isXSLTResultMode();

			if(($total <= 0) || ($total <= $per_page)) {
				return ($isXslt) ? "" : $template_block_empty;
			}


			$key = $varName;
			$page_current = (string) getRequest($key);

			$params = $_GET;
			if(array_key_exists($key, $params)) {
				unset($params[$key]);
			}
			unset($params['path']);

			if(array_key_exists('scheme', $params)) {
				unset($params['scheme']);
			}

			if($max_pages === false) {
				$max_pages = self::$max_pages;
			}

			$block_arr = Array();

			$pages = Array();
			$pages_count = ceil($total / $per_page);
			if(!$pages_count) $pages_count = 1;
			
			$params = self::protectParams($params);

			$q = (sizeof($params)) ? "&" . http_build_query($params, '', '&') : "";

			if ($isXslt == false) {
				$q = str_replace("%", "&#37;", $q);
			}

			$q = str_replace(array("<", ">", "%3C", "%3E"), 
							 array("&lt;", "&gt;", "&lt;", "&gt;"), $q);

			for($i = 0; $i < $pages_count; $i++) {
				$line_arr = Array();

				$n = $i + 1;

				if(($page_current - $max_pages) >= $i) continue;
				if(($page_current + $max_pages) <= $i) break;
				
				if($page_current != "all") {
					$tpl = ($i == $page_current) ? $template_item_a : $template_item;
				} else {
					$tpl = $template_item;
				}

				$link = "?{$key}={$i}" . $q;

				$line_arr['attribute:link'] = $link;
				$line_arr['attribute:page-num'] = $i;

				if($page_current == $i) {
					$line_arr['attribute:is-active'] = true;
				}
				
				$line_arr['node:num'] = $n;
				//Bugfix #0002780
				//$line_arr['void:quant'] = ($i < ($pages_count - 1)) ? $template_quant : "";
				$line_arr['void:quant'] = (($i < (($page_current + $max_pages)-1)) and ($i < ($pages_count - 1))) ? $template_quant : "";

				$pages[] = def_module::parseTemplate($tpl, $line_arr);
			}

			$block_arr['subnodes:items'] = $block_arr['void:pages'] = $pages;
			if (!$isXslt) {
				$block_arr['tobegin'] = ($page_current == 0 || $pages_count <= 1) ? $template_tobegin_a : $template_tobegin;
				$block_arr['toprev']  = ($page_current == 0 || $pages_count <= 1) ? $template_toprev_a  : $template_toprev;
				$block_arr['toend'] =  ($page_current == ($pages_count - 1) || $pages_count <= 1) ? $template_toend_a : $template_toend;
				$block_arr['tonext'] = ($page_current == ($pages_count - 1) || $pages_count <= 1) ? $template_tonext_a : $template_tonext;
			}

			if ($page_current != 0) {
				$tobegin_link = "?{$key}=0" . $q;
				if($isXslt) {
					$block_arr['tobegin_link'] = array(
						'attribute:page-num' => 0,
						'node:value' => $tobegin_link
					);
				} else {
					$block_arr['tobegin_link'] = $tobegin_link;
				}
			}

			if ($page_current < $pages_count - 1) {
				$toend_link = "?{$key}=" . ($pages_count - 1) . $q;
				if($isXslt) {
					$block_arr['toend_link'] = array(
						'attribute:page-num' => $pages_count - 1,
						'node:value' => $toend_link
					);
				} else {
					$block_arr['toend_link'] = $toend_link;
				}
			}

			if($page_current - 1 >= 0) {
				$toprev_link = "?{$key}=" . ($page_current -1)  . $q;
				if($isXslt) {
					$block_arr['toprev_link'] = array(
						'attribute:page-num' => $page_current -1,
						'node:value' => $toprev_link
					);
				} else {
					$block_arr['toprev_link'] = $toprev_link;
				}
			}

			if($page_current < $pages_count - 1) {
				$tonext_link = "?{$key}=" . ($page_current + 1) . $q;
				if($isXslt) {
					$block_arr['tonext_link'] = array(
						'attribute:page-num' => $page_current + 1,
						'node:value' => $tonext_link
					);
				} else {
					$block_arr['tonext_link'] = $tonext_link;
				}
			}
			
			$block_arr['current-page'] = (int) $page_current;
			return def_module::parseTemplate($template_block, $block_arr);
		}


		public static function generateOrderBy($fieldName, $type_id, $template = "default") {
			if(!$template) $template = "default";
			list($template_block, $template_block_a) = def_module::loadTemplates("numpages/".$template, "order_by", "order_by_a");

			if(!($type = umiObjectTypesCollection::getInstance()->getType($type_id))) {
				return "";
			}


			$block_arr = Array();

			if(($field_id = $type->getFieldId($fieldName)) || ($fieldName == "name")) {
				$params = $_GET;
				unset($params['path']);
				
				if(array_key_exists('scheme', $params)) {
					unset($params['scheme']);
				}
				
				$order_filter = getArrayKey($params, 'order_filter');
				
				if(is_array($order_filter)) {
					$tpl = (array_key_exists($fieldName, $order_filter)) ? $template_block_a : $template_block;
				} else {
					$tpl = $template_block;
				}
				
				unset($params['order_filter']);
				$params['order_filter'][$fieldName] = 1;
				
				$params = self::protectParams($params);

				$q = (sizeof($params)) ? "&" . http_build_query($params, '', '&') : "";

				$q = urldecode($q);
				$q = str_replace(array("%", "<", ">", "%3C", "%3E"), 
								 array("&#037;", "&lt;", "&gt;", "&lt;", "&gt;"), $q);

				$block_arr['link'] = "?" . $q;


				if($fieldName == "name") {
					$block_arr['title'] = getLabel('field-name');
				} else {
					$block_arr['title'] = umiFieldsCollection::getInstance()->getField($field_id)->getTitle();
				}

				return def_module::parseTemplate($tpl, $block_arr);
			}
			return "";
		}
		
		
		protected static function protectParams($params) {
			foreach($params as $i => $v) {
				if(is_array($v)) {
					$params[$i] = self::protectParams($v);
				} else {
					$v = htmlspecialchars($v);
					$params[$i] = str_replace(array("%", "<", ">", "%3C", "%3E"), 
											  array("&#037;", "&lt;", "&gt;", "&lt;", "&gt;"), $v);
				}
			}
			return $params;
		}
	};
?>