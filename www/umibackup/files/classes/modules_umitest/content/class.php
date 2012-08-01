<?php
	class content extends def_module {
		public function __construct() {
			parent::__construct();



			if(cmsController::getInstance()->getCurrentMode() == "admin") {
				cmsController::getInstance()->getModule('users');

				$configTabs = $this->getConfigTabs();
				if ($configTabs) {
					$configTabs->add("config");
					$configTabs->add("seo");
					$configTabs->add("content_control");
					$configTabs->add("tickets");
				}

				$this->__loadLib("__admin.php");
				$this->__implement("__content");

				// custom admin methods
				$this->__loadLib("__custom_adm.php");
				$this->__implement("__custom_adm_content");

				$this->__loadLib("__imanager.php");
				$this->__implement("__imanager_content");
			} else {
				$this->__loadLib("__custom.php");
				$this->__implement("__custom_content");

				$this->__loadLib("__tickets.php");
				$this->__implement("__tickets_content");

			}
			$this->__loadLib("__json.php");
			$this->__implement("__json_content");

			$this->__loadLib("__lib.php");
			$this->__implement("__lib_content");

			$this->__loadLib("__events.php");
			$this->__implement("__content_events");

			$this->__loadLib("__editor.php");
			$this->__implement("__editor_content");
	
		}


		public function __call($s_method_name, $arr_arguments) {
			if (!method_exists($this, $s_method_name)) { // если еще не имлементировали

				$s_match = "";
				$s_method_prefix = '';
				$s_method_base = '';
				$arr_matches = array();
				$bSucc = preg_match("/[A-Z_]/", $s_method_name, $arr_matches);
				if ($bSucc) {
					$s_match = $arr_matches[0];
					$i_match = strpos($s_method_name, $s_match);
					$s_method_prefix = substr($s_method_name, 0, $i_match)."/";
					$s_method_base = substr($s_method_name, 0, $i_match+($s_match === "_" ? 1 : 0));
				}

				$s_class_enter = "__".$s_method_name; // метод, общий для всех режимов
				if (!class_exists($s_class_enter)) {
					$s_entermethod_lib = "methods/".$s_method_prefix."__".$s_method_name.".lib.php";

					$this->__loadLib($s_entermethod_lib);
					$this->__implement($s_class_enter);

				}

				$s_class_mode = "__".$s_method_name."_"; // метод, выбираемый в зависимости от режима
				if (!class_exists($s_class_mode)) {

					$s_modemethod_lib = "methods/".$s_method_prefix."__".$s_method_name."_".cmsController::getInstance()->getCurrentMode().".lib.php";

					$this->__loadLib($s_modemethod_lib);
					$this->__implement($s_class_mode);

				}

			}

			return parent::__call($s_method_name, $arr_arguments);
		}


		public function content($elementId = false) {
			if($this->breakMe()) return;

			$cmsController = cmsController::getInstance();
			if(!$elementId) $elementId = $cmsController->getCurrentElementId();

			$hierarchy = umiHierarchy::getInstance();
			$element = $hierarchy->getElement($elementId);

			if($element instanceof iUmiHierarchyElement) {
				templater::pushEditable("content", "", $elementId);
				return $element->content;
			} else {
				return $this->gen404();
			}
		}


		public function gen404($template = 'default') {
			if(!$template) $template = 'default';

			$buffer = outputBuffer::current();
			$buffer->status('404 Not Found');

			$this->setHeader('%content_error_404_header%');
			list($tpl_block) = def_module::loadTemplates("content/not_found/".$template, 'block');
			return $tpl_block ? $tpl_block : '%content_usesitemap%';
		}


		public function menu($menu_tpl = "default", $max_depth = 1, $pid = false) {
			$cmsController = cmsController::getInstance();
			$hierarchy = umiHierarchy::getInstance();

			if($pid) {
				if(!is_numeric($pid)) {
					$pid = $hierarchy->getIdByPath($pid);
				}
				$parent_alt = $hierarchy->getPathById($pid, false, true);
			} else {
				$pid = 0;
				$parent_alt = $cmsController->pre_lang ."/". $cmsController->getUrlPrefix();
			}

			//$this->parents = $this->get_parents($cmsController->getCurrentElementId());

			if ($cmsController->getCurrentTemplater() instanceof tplTemplater) {
				if(!file_exists($cmsController->getCurrentTemplater()->getFolderPath() . "content/menu/" . $menu_tpl . ".tpl")) {
					return "%core_error_notemplate%";
				}

				$this->__loadLib($menu_tpl . ".tpl", $cmsController->getCurrentTemplater()->getFolderPath() . "content/menu/", "FORMS");
			}

			$templates = $this->FORMS;
			return $this->build_menu($pid, $templates, 0, $parent_alt, $max_depth);
		}


		public function sitemap($template = "default", $max_depth = false, $root_id = false) {
			if($this->breakMe()) return;

			$hierarchy = umiHierarchy::getInstance();
			$cmsController = cmsController::getInstance();

			if(!$max_depth) $max_depth = getRequest('param0');
			if(!$max_depth) $max_depth = 4;

			if(!$root_id) $root_id = (int) getRequest('param1');
			if(!$root_id) $root_id = 0;

			if($cmsController->getCurrentMethod() == "sitemap") {
				$this->setHeader("%content_sitemap%");
			}

			$site_tree = $hierarchy->getChilds($root_id, false, false, $max_depth - 1);
			return $this->gen_sitemap($template, $site_tree, $max_depth - 1);
		}


		public function get_page_url($element_id, $ignore_lang = false) {
			$ignore_lang = (bool) $ignore_lang;
			return umiHierarchy::getInstance()->getPathById($element_id, $ignore_lang);
		}


		public function get_page_id($url) {
			$hierarchy = umiHierarchy::getInstance();
			$elementId = $hierarchy->getIdByPath($url);
			if($elementId) return $elementId; else {
				throw new publicException(getLabel('error-page-does-not-exist', null, $url));
			}
		}


		public function redirect($url = "") {
			if(is_numeric($url)) {
				$url = $this->get_page_url($url);
			}
			parent::redirect($url);
		}


		public function insert($elementId) {
			$hierarchy = umiHierarchy::getInstance();
			$cmsController = cmsController::getInstance();
			$currentElementId = $cmsController->getCurrentElementId();
			$elementId = trim($elementId);

			if(!$elementId) return "%content_error_insert_null%";

			$elementId = (int) is_numeric($elementId) ? $elementId : $hierarchy->getIdByPath($elementId);
			if($elementId == $currentElementId) return "%content_error_insert_recursy%";
			if(!$elementId) return;

			if($element = $hierarchy->getElement($elementId)) {
				templater::pushEditable("content", "", $elementId);
				return $element->content;
			}
		}


		public function get_parents($elementId) {
			return umiHierarchy::getInstance()->getAllParents($elementId, true);
		}


		public function getEditLink($elementId, $element_type) {
			return array(
				$this->pre_lang . "/admin/content/add/{$elementId}/page/",
				$this->pre_lang . "/admin/content/edit/{$elementId}/"
			);
		}


		private function gen_sitemap($template = "default", $site_tree, $max_depth) {
			$hierarchy = umiHierarchy::getInstance();

			list($template_block, $template_item) = def_module::loadTemplates("content/sitemap/".$template, "block", "item");

			$block_arr = array(); $items = array();
			if(is_array($site_tree)) {
				foreach($site_tree as $elementId => $childs) {
					if($element = $hierarchy->getElement($elementId)) {
						$item_arr = array(
							'attribute:id'		=> $elementId,
							'attribute:link'	=> $element->link,
							'attribute:name'	=> $element->name,
							'xlink:href'		=> ("upage://" . $elementId)
						);

						if(($max_depth > 0) && $element->show_submenu) {
							$item_arr['nodes:items'] = $item_arr['void:sub_items'] = (sizeof($childs) && is_array($childs)) ? $this->gen_sitemap($template, $childs, ($max_depth - 1)) : "";
						} else {
							$item_arr['sub_items'] = "";
						}
						$items[] = self::parseTemplate($template_item, $item_arr, $elementId);
						$hierarchy->unloadElement($elementId);
					} else {
						continue;
					}
				}
			}

			$block_arr['subnodes:items'] = $items;
			return self::parseTemplate($template_block, $block_arr, 0);
		}


		private function getMenuTemplates($templates, $curr_depth) {
			$suffix = "_level" . $curr_depth;

			$block = getArrayKey($templates, "menu_block" . $suffix);
			$line = getArrayKey($templates, "menu_line" . $suffix);
			$line_a = (array_key_exists("menu_line" . $suffix . "_a", $templates)) ? $templates["menu_line" . $suffix . "_a"] : $line;
			$line_in = (array_key_exists("menu_line" . $suffix . "_in", $templates)) ? $templates["menu_line" . $suffix . "_in"] : $line;

			$class = getArrayKey($templates, "menu_class" . $suffix . "");
			$class_last = getArrayKey($templates, "menu_class" . $suffix . "_last");


			if(!$block) {
				switch($curr_depth) {
					case 1: $suffix = "_fl"; break;
					case 2: $suffix = "_sl"; break;
				}
				$block = getArrayKey($templates, 'menu_block' . $suffix);
				$line = getArrayKey($templates, 'menu_line' . $suffix);
				$line_a = (array_key_exists("menu_line" . $suffix . "_a", $templates)) ? $templates["menu_line" . $suffix . "_a"] : $line;
				$line_in = (array_key_exists("menu_line" . $suffix . "_in", $templates)) ? $templates["menu_line" . $suffix . "_in"] : $line;
			}

			if(!($separator = getArrayKey($templates, 'separator' . $suffix))) {
				$separator = getArrayKey($templates, 'separator');
			}

			if(!($separator_last = getArrayKey($templates, 'separator_last' . $suffix))) {
				$separator_last = getArrayKey($templates, 'separator_last');
			}

			return array($block, $line, $line_a, $line_in, $separator, $separator_last, $class, $class_last);
		}


		private function build_menu($page_id, &$templates, $curr_depth = 0, $parent_alt_name = "/", $max_depth = 1) {
			static $childsCache = array();
			$hierarchy = umiHierarchy::getInstance();
			$cmsController = cmsController::getInstance();

			$social_module = cmsController::getInstance()->getModule("social_networks");
			if($social_module) {
				$social = $social_module->getCurrentSocial();
			}
			else {
				$social = false;
			}

			list(
				$template_block, $template_line, $template_line_a, $template_line_in, $separator, $separator_last, $class, $class_last
			) = $this->getMenuTemplates($templates, ($curr_depth + 1));

			if(isset($childsCache[$page_id])) {
				$result = $childsCache[$page_id];
			} else {
				$allow_visible = false;
				if($social) $allow_visible = true;
				
				$childs = $hierarchy->getChilds($page_id, false, $allow_visible, 1);
				if($childs === false) $childs = array();
				$result = array_keys($childs);

				$childsCache[$page_id] = $result;
			}

			$sz = sizeof($result);
			if($sz == 0) {
				return "";
			}

			if ($page_id && ($parent_alt_name == $this->pre_lang . "/")) {
				$parentElement = $hierarchy->getElement($page_id);
				$parent_alt_name = $hierarchy->getPathById($page_id) . $parentElement->getAltName() . '/';
			}

			$lines = array();
			$arr_lines = array();
			$c = 0;

			$currentElementId = $cmsController->getCurrentElementId();
			$allParents = $hierarchy->getAllParents($currentElementId, true);

			foreach($result as $element_id) {
				if($social && !$social->isHierarchyAllowed($element_id)) { 
					continue;
				}
				
				$element = $hierarchy->getElement($element_id);
				if (!$element) continue;
				$text = $element->getName();

				$link = $parent_alt_name . $element->getAltName() . "/";

				if($template_line_in && $template_line_in != $template_line) {
					if ($max_depth > 1 && $this->isInPath($element_id, $templates, ($curr_depth + 1), $link, $max_depth - 1)) {
						$is_active = true;
						$line = $template_line_in;
					} else {
						$is_active = (in_array($element_id, $allParents) !== false);
						$line = ($is_active) ? $template_line_a : $template_line;
					}
				} else {
					$is_active = (in_array($element_id, $allParents) !== false);
					$line = ($is_active) ? $template_line_a : $template_line;
				}


				$sub_menu = '';
				if(strstr($line, "%sub_menu%") && $max_depth > 1) {
					if($element->getValue("show_submenu") && ($is_active || $element->getValue("is_expanded"))) {
						$sub_menu = $this->build_menu($element_id, $templates, ($curr_depth + 1), $link, $max_depth - 1);
					}
				}

				if($element->getIsDefault()) {
					$link = $element->link;
				}

				$item_arr = array();
				$item_arr['@id'] = $element_id;
				$item_arr['@link'] = $link;
				$item_arr['@name'] = $text;
				$item_arr['xlink:href'] = "upage://" . $element_id;

				if ($cmsController->getCurrentTemplater() instanceof xslTemplater) {
					if($max_depth > 1 && XSLT_NESTED_MENU && $element->getValue("show_submenu") && ($is_active || $element->getValue("is_expanded"))) {
					$xslt_submenu = $this->build_menu($element_id, $templates, ($curr_depth + 1), $link, $max_depth - 1);
						if(is_array($xslt_submenu) && isset($xslt_submenu['items'], $xslt_submenu['items']['nodes:item']) && sizeof($xslt_submenu['items']['nodes:item'])) {
							$item_arr['items']['nodes:item'] = $xslt_submenu['items']['nodes:item'];
						}
					}
				}

				if(XSLT_NESTED_MENU != 2) {
					$item_arr['node:text'] = $text;
				}

				$item_arr['void:num'] = ($c+1);
				$item_arr['void:sub_menu'] = $sub_menu;
				$item_arr['void:separator'] = (($sz == ($c + 1)) && $separator_last) ? $separator_last : $separator;

				if($is_active) {
					$item_arr['attribute:status'] = "active";
				}

				$item_arr['class'] = ($sz > ($c + 1)) ? $class : $class_last;

				$arr_lines[] = self::parseTemplate($line, $item_arr, $element_id);

				$c++;
				$hierarchy->unloadElement($element_id);
			}

			$block_arr = array(
				'subnodes:items'	=> $arr_lines,
				'void:lines'		=> $arr_lines,
				'id'				=> $page_id
			);
			return self::parseTemplate($template_block, $block_arr, $page_id);
		}


		private function isInPath($pageId) {
			$hierarchy = umiHierarchy::getInstance();
			$cmsController = cmsController::getInstance();
			$currentElementId = $cmsController->getCurrentElementId();

			$is_active_child = false;
			foreach(array_keys($hierarchy->getChilds($pageId)) as $elementId) {
				$element = $hierarchy->getElement($elementId);
				if(!$element || !$element->getIsActive()) continue;

				$is_active_child |= (in_array($elementId, $hierarchy->getAllParents($currentElementId, true)) !== false);
			}
			return $is_active_child;
		}
		
	};
?>
