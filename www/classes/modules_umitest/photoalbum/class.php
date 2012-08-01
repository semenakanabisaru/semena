<?php
	class photoalbum extends def_module {
		public $per_page = 10;

		public function __construct() {
        	        parent::__construct();

			if(cmsController::getInstance()->getCurrentMode() == "admin") {
				$this->__loadLib("__admin.php");
				$this->__implement("__photoalbum");

				$this->__loadLib("__picasa.php");
				$this->__implement("__picasa_photoalbum");
			} else {
				$this->__loadLib("__custom.php");
				$this->__implement("__custom_photoalbum");
			}

			if($per_page = (int) regedit::getInstance()->getVal("//modules/photoalbum/per_page")) {
				$this->per_page = $per_page;
			}
		}


		public function subalbums($elementId = false, $template = "default", $limit = false, $ignore_paging = false) {
			$elementId = $this->analyzeRequiredPath($elementId);
			return $this->albums($template, $limit, $ignore_paging, $elementId);
		}


		public function albums($template = "default", $limit = false, $ignore_paging = false, $parentElementId = false, $order = 'asc') {
			list(
				$template_block, $template_block_empty, $template_line
			) = def_module::loadTemplates("photoalbum/".$template,
				"albums_list_block", "albums_list_block_empty", "albums_list_block_line"
			);
			$block_arr = Array();

			$curr_page = (int) getRequest('p');
			if($ignore_paging) $curr_page = 0;
			$per_page = ($limit) ? $limit : $this->per_page;

			$sel = new selector('pages');
			$sel->types('hierarchy-type')->name('photoalbum', 'album');
			$sel->where('permissions');

			if($parentElementId) {
				$sel->where('hierarchy')->page($parentElementId)->childs(1);
			}

			if (in_array($order, array('asc', 'desc', 'rand'))) $sel->order('ord')->$order();
			$sel->limit($curr_page, $limit);

			$result = $sel->result;
			$total = $sel->length();

			$lines = Array();
			if($total > 0) {
				foreach($result as $element) {
					$line_arr = Array();

					$element_id = $element->getId();
					$line_arr['attribute:id'] = $element_id;
					$line_arr['attribute:link'] = umiHierarchy::getInstance()->getPathById($element_id);
					$line_arr['xlink:href'] = "upage://" . $element_id;
					$line_arr['node:name'] = $element->getName();

					templater::pushEditable("photoalbum", "album", $element_id);
					$lines[] = self::parseTemplate($template_line, $line_arr, $element_id);
				}
			} else {
				return self::parseTemplate($template_block_empty, $block_arr);
			}

			$block_arr['subnodes:items'] = $block_arr['void:lines'] = $lines;
			$block_arr['total'] = $total;
			$block_arr['per_page'] = $per_page;
			return self::parseTemplate($template_block, $block_arr);
		}


		public function album($path = false, $template = "default", $limit = false, $ignore_paging = false) {
			if($this->breakMe()) return;

			$curr_page = (int) getRequest('p');
			$per_page = ($limit) ? $limit : $this->per_page;

			if($ignore_paging) $curr_page = 0;
			$element_id = $this->analyzeRequiredPath($path);

			if($element_id === false && $path != KEYWORD_GRAB_ALL) {
				throw new publicException(getLabel('error-page-does-not-exist', null, $path));
			}

			list($template_block, $template_block_empty, $template_line) = def_module::loadTemplates("photoalbum/".$template, "album_block", "album_block_empty", "album_block_line");
			$block_arr = Array();

			$hierarchy_type_id = umiHierarchyTypesCollection::getInstance()->getTypeByName("photoalbum", "photo")->getId();
			$type_id = umiHierarchy::getInstance()->getDominantTypeId($element_id);

			if(!$type_id) {
				$type_id = umiObjectTypesCollection::getInstance()->getBaseType("photoalbum", "photo");
			}


			$sel = new umiSelection;
			$sel->addElementType($hierarchy_type_id);

			if($path != KEYWORD_GRAB_ALL) {
				$sel->addHierarchyFilter($element_id);
			}


			if($type_id) {
				$this->autoDetectOrders($sel, $type_id);
				$this->autoDetectFilters($sel, $type_id);
			}

			$sel->addPermissions();
			$sel->addLimit($per_page, $curr_page);

			$result = umiSelectionsParser::runSelection($sel);
			$total = umiSelectionsParser::runSelectionCounts($sel);

			$block_arr['id'] = $block_arr['void:album_id'] = $element_id;

			$lines = Array();
			if($total > 0) {
				foreach($result as $photo_element_id) {
					$line_arr = Array();

					$photo_element = umiHierarchy::getInstance()->getElement($photo_element_id);

					if(!$photo_element) continue;

					$line_arr['attribute:id'] = $photo_element_id;
					$line_arr['attribute:link'] = umiHierarchy::getInstance()->getPathById($photo_element_id);
					$line_arr['xlink:href'] = "upage://" . $photo_element_id;
					$line_arr['node:name'] = $photo_element->getName();

					templater::pushEditable("photoalbum", "photo", $photo_element_id);
					$lines[] = self::parseTemplate($template_line, $line_arr, $photo_element_id);
				}
			} else {
				return self::parseTemplate($template_block_empty, $block_arr, $element_id);
			}

			$block_arr['subnodes:items'] = $block_arr['void:lines'] = $lines;
			$block_arr['total'] = $total;
			$block_arr['per_page'] = $per_page;
			$block_arr['link'] = umiHierarchy::getInstance()->getPathById($element_id);

			return self::parseTemplate($template_block, $block_arr, $element_id);
		}


		public function photo($element_id = false, $template = "default") {
			if($this->breakMe()) return;
			$hierarchy = umiHierarchy::getInstance();
			list($template_block) = def_module::loadTemplates("photoalbum/".$template, "photo_block");

			$element_id = $this->analyzeRequiredPath($path = $element_id);
			$element = $hierarchy->getElement($element_id);
			if($element instanceof iUmiHierarchyElement == false) {
				throw new publicException(getLabel('error-page-does-not-exist', null, $path));
			}

			templater::pushEditable("photoalbum", "photo", $element_id);
			return self::parseTemplate($template_block, array(
				'id' => $element->id,
				'name' => $element->name
			), $element_id);
		}


		public function config () {
			if(class_exists("__photoalbum")) {
				return __photoalbum::config();
			}
		}


		public function getEditLink($element_id, $element_type) {
			$element = umiHierarchy::getInstance()->getElement($element_id);
			$parent_id = $element->getParentId();

			switch($element_type) {
				case "album": {
					$link_add = $this->pre_lang . "/admin/photoalbum/add/{$element_id}/photo/";
					$link_edit = $this->pre_lang . "/admin/photoalbum/edit/{$element_id}/";

					return Array($link_add, $link_edit);
					break;
				}


				case "photo": {
					$link_add = false;
					$link_edit = $this->pre_lang . "/admin/photoalbum/edit/{$element_id}/";

					return Array($link_add, $link_edit);
					break;
				}

				default: {
					return false;
				}
			}
		}

	public function addPhoto() {
		$hierarchy = umiHierarchy::getInstance();
		$hierarchyTypes = umiHierarchyTypesCollection::getInstance();
		$objectTypes = umiObjectTypesCollection::getInstance();
		$cmsController = cmsController::getInstance();

		$parent_id = (int) getRequest('param0');
		$object_type_id = (int) getRequest('param1');
		$title = htmlspecialchars(trim(getRequest('title')));

		$parentElement = $hierarchy->getElement($parent_id);
		$tpl_id		= $parentElement->getTplId();
		$domain_id	= $parentElement->getDomainId();
		$lang_id	= $parentElement->getLangId();

		$hierarchy_type_id = $hierarchyTypes->getTypeByName("photoalbum", "photo")->getId();
		if(!$object_type_id) {
			$object_type_id = $objectTypes->getBaseType("photoalbum", "photo");
		}

		$object_type = $objectTypes->getType($object_type_id);
		if($object_type->getHierarchyTypeId() != $hierarchy_type_id) {
			$this->errorNewMessage("Object type and hierarchy type doesn't match");
			$this->errorPanic();
		}

		$element_id = $hierarchy->addElement($parent_id, $hierarchy_type_id, $title, $title, $object_type_id, $domain_id, $lang_id, $tpl_id);

		$users = $cmsController->getModule("users");
		if($users instanceof def_module) {
			$users->setDefaultPermissions($element_id);
		}

		$element = $hierarchy->getElement($element_id, true);

		$element->setIsActive(true);
		$element->setIsVisible(false);
		$element->setName($title);

		$element->commit();
		$parentElement->setUpdateTime(time());
		$parentElement->commit();

		if($is_xslt) {
			return Array("node:result" => "ok");
		} else {
			$this->redirect($referer_url);
		}
	}

	};
?>