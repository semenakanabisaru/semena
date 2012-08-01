<?php

class system {
	public $thumbs_path = "./images/cms/thumbs/";

	public function __toString() {
		return "umi.__system";
	}

	public function cms_callMethod($method_name, $args) {
		return call_user_func_array(Array($this, $method_name), $args);
	}

	public function isMethodExists($method_name) {
		return method_exists($this, $method_name);
	}

	public function __call($method, $args) {
		throw new publicException("Method " . get_class($this) . "::" . $method . " doesn't exists");
	}

	public function is_int($arg) {
		return is_numeric($arg);
	}

	public function setThumbsPath($sPath) {
		$sOldValue = $this->thumbs_path;
		$this->thumbs_path = $sPath;
		return $sOldValue;
	}

	public function bool2str($arg) {
		return ($arg) ? "true" : "false";
	}

	public function fileExists($arg) {
		return file_exists(ini_get('include_path') . $arg);
	}


	public function isSubscribedOnChanges($elementId) {
		if(!$elementId) return "";
		$checked = " checked";

		$permissions = permissionsCollection::getInstance();
		$objects = umiObjectsCollection::getInstance();

		$userId = $permissions->getUserId();
		$user = $objects->getObject($userId);
		if($user instanceof iUmiObject == false) return false;
		foreach($user->subscribed_pages as $page) {
			if($page instanceof iUmiHierarchyElement) {
				if($page->id == $elementId) return $checked;
			} else {
				if($page == $elementId) return $checked;
			}
		}
	}


	private function getImageProps($filePath, $expect = "width") {
		$filePath = ini_get('include_path') . $filePath;
		if(!file_exists($filePath)) return false;

		list($width, $height) = getimagesize($filePath);

		if($expect == "width")  return $width;
		if($expect == "height") return $height;
	}

	public function getImageWidth($filePath) {
		return $this->getImageProps($filePath, 'width');
	}

	public function getImageHeight($filePath) {
		return $this->getImageProps($filePath, 'height');
	}

	public function getOuterContent($arg, $sourceCharset = "UTF-8") {
		if(!$sourceCharset) $sourceCharset = "UTF-8";

		if(str_replace("http://", "", $arg) != $arg || str_replace("umap://", "", $arg) != $arg) {
			if($res = cacheFrontend::getInstance()->loadSql($arg)) {
				return $res;
			}

			$res = umiRemoteFileGetter::get($arg);

			if($sourceCharset != "UTF-8") {
				$res = iconv($sourceCharset, "UTF-8//IGNORE", $res);
			}
			cacheFrontend::getInstance()->saveSql($arg, $res);
			return $res;
		} else {
			if(substr($arg, -4, 4) == ".tpl") {
				$res = umiRemoteFileGetter::get($arg);

				if($sourceCharset != "UTF-8") {
					$res = iconv($sourceCharset, "UTF-8//IGNORE", $res);
				}
				return def_module::parseTPLMacroses($res, cmsController::getInstance()->getCurrentElementId());
			} else {
				throw new publicException(getLabel('error-resource-not-found', null, $arg));
			}
		}
	}

	public function getSize($relativePath, $unit = "B", $precision = 0) {
		$path = CURRENT_WORKING_DIR . "/" . $relativePath;
		if(is_numeric($relativePath)) {
			$size = $relativePath;
		} else {
			if(!file_exists($path)) {
				throw new publicException(getLabel('error-file-does-not-exist', null, $relativePath));
			}
			$size = filesize($path);
		}
		if(!$precision) $precision = 0;
		$unit = strtoupper($unit);
		switch($unit) {
			case "K": return round($size / 1024, $precision);
			case "M": return round($size / (1024*1024), $precision);
			case "G": return round($size / (1024*1024*1024), $precision);
			case "B":
			default : return round($size, $precision);
		}
	}

	public function convertDate($timestamp, $format = false, $timestring = false) {
		if ($timestamp == 'now') $timestamp = time();

		if ($timestring) {
			$convertedDate = strtotime($timestring);
			if ($convertedDate && $convertedDate != -1) $timestamp = $convertedDate;
		}

		if (!is_numeric($timestamp)) return "";
		if (!$format) $format = DEFAULT_DATE_FORMAT;
		return $timestamp ? date($format, $timestamp) : '';
	}


	public function ifClause($cond, $r1 = "", $r2 = "") {
		return ($cond) ? $r1 : $r2;
	}


	public function parse_price($num = 0) {
		return number_format($num, 0, ',', ' ');
	}


	public function getCurrentURI($toRedirect = false) {
		$from_page = getRequest('from_page');
		return ($from_page && $toRedirect) ? $from_page : getServer('REQUEST_URI');
	}


	public function makeThumbnail($path = false, $width = 'auto', $height = 'auto', $template = "default", $returnArrayOnly = false, $flags = 0, $quality = 100) {
		if(!$template) $template = "default";

		$flags = (int)$flags;

		$image = new umiImageFile($path);



		$file_name = $image->getFileName();
		$file_ext = strtolower($image->getExt());
		$file_ext = ($file_ext=='bmp'?'jpg':$file_ext);

		$thumbPath = sha1($image->getDirName());

		if (!is_dir($this->thumbs_path.$thumbPath)) {
			mkdir($this->thumbs_path.$thumbPath, 0755, true);
		}


		$allowedExts = Array('gif', 'jpeg', 'jpg', 'png', 'bmp');
		if(!in_array($file_ext, $allowedExts)) return "";

		$file_name = substr($file_name, 0, (strlen($file_name) - (strlen($file_ext) + 1)) );
		$file_name_new = $file_name . "_" . $width . "_" . $height . "_" .$image->getExt(). "." . $file_ext;
		$path_new = $this->thumbs_path .$thumbPath."/". $file_name_new;

		if(!file_exists($path_new) || filemtime($path_new) < filemtime($path)) {
			if(file_exists($path_new)) {
				unlink($path_new);
			}
			$width_src = $image->getWidth();
			$height_src = $image->getHeight();

			if(!$width_src) return false;

			if($width_src <= $width && $height_src <= $height ) {
				copy($path, $path_new);
				$real_width  = $width;
				$real_height = $height;
			} else {

				if ($width == "auto" && $height == "auto"){
					$real_height = $height_src;
					$real_width = $width_src;
				}elseif ($width == "auto" || $height == "auto"){
					if ($height == "auto"){
						// Flag: Reduce only
						if($flags & 0x2 && $width > $width_src) {
							$real_height = $height_src;
							$real_width  = $width_src;
						} else {
							$real_width = (int) $width;
							$real_height = (int) round($height_src * ($width / $width_src));
						}
					}elseif($width == "auto"){
						// Flag: Reduce only
						if($flags & 0x2 && $height > $height_src) {
							$real_height = $height_src;
							$real_width  = $width_src;
						} else {
							$real_height = (int) $height;
							$real_width = (int) round($width_src * ($height / $height_src));
						}
					}
				}else{
					// Flag: Keep proportions
					if($flags & 0x1) {
						$kwidth  = (float) $width / $width_src;
						$kheight = (float) $height / $height_src;
						$k = max(array($kwidth, $kheight));
						if(($flags & 0x2) && ($k > 1.0)) {
							$k = 1.0;
						}
						$real_width  = (int) round($width_src * $k);
						$real_height = (int) round($height_src * $k);
					} else {
						$real_width  = $width;
						$real_height = $height;
					}
				}

				$thumb = imagecreatetruecolor($real_width, $real_height);
				$thumb_white_color = imagecolorallocate($thumb, 255, 255, 255);

				$source_array = $image->createImage($path);
				$source = $source_array['im'];

				switch($file_ext) {
					case 'gif':
						imagefill($thumb, 0, 0, $thumb_white_color);
						imagecolortransparent($thumb, $thumb_white_color);
						imagealphablending($source, TRUE);
						imagealphablending($thumb, TRUE);
						break;
					case 'png':
						imagefill($thumb, 0, 0, $thumb_white_color);
						imagecolortransparent($thumb, $thumb_white_color);
						imagealphablending($thumb, false);imagesavealpha($thumb, true);
						imagealphablending($source, false);imagesavealpha($source, true);
						break;

					default:
				}

				imagecopyresampled($thumb, $source, 0, 0, 0, 0, $real_width, $real_height, $width_src, $height_src);

				switch($file_ext) {
					case 'gif':
						$res = imagegif($thumb, $path_new);
						break;
					case 'png':
						$res = imagepng($thumb, $path_new);
						break;
					default:
						$res = imagejpeg($thumb, $path_new, $quality);
				}
				if(!$res) {
					throw new coreException(getLabel('label-errors-16008'));
				}
				imagedestroy($source);
				imagedestroy($thumb);
			}
		}

		//Parsing
		$value = new umiImageFile($path_new);

		$arr = Array();
		$arr['size'] = $value->getSize();
		$arr['filename'] = $value->getFileName();
		$arr['filepath'] = $value->getFilePath();
		$arr['src'] = $value->getFilePath(true);
		$arr['ext'] = $value->getExt();

		$arr['width'] = $value->getWidth();
		$arr['height'] = $value->getHeight();

		$arr['void:template'] = $template;

		if(cmsController::getInstance()->getCurrentMode() == "admin") {
			$arr['src'] = str_replace("&", "&amp;", $arr['src']);
		}

		if($returnArrayOnly) {
			return $arr;
		} else {
			list($tpl) = def_module::loadTemplates("thumbs/".$template, "image");
			return def_module::parseTemplate($tpl, $arr);
		}
	}

	public function numpages($total = 0, $per_page = 0, $template = "default", $varName = "p", $max_pages = false) {
		if(!$varName) $varName = "p";
		if(!$max_pages) (int) $max_pages = false;

		return umiPagenum::generateNumPage($total, $per_page, $template, $varName, $max_pages);
	}

	public function order_by($fieldName, $typeId, $template = "default") {
		$from = Array('%5B', '%5D');
		$to = Array('[', ']');

		$result = umiPagenum::generateOrderBy($fieldName, $typeId, $template);
		$result = str_replace($from, $to, $result);
		return $result;
	}

	public function uri_path_pic() {
		list($res) = explode("/", $_REQUEST['path']);

		$allowed_res = Array('about', 'portfolio', 'sites', 'promotion', 'multimedia', 'own_projects', 'contacts');
		if(!in_array($res, $allowed_res)) {
			$res = array_pop($allowed_res);
		}
		return $res;
	}

	public function captcha($template = 'default') {
		$cmsController = cmsController::getInstance();
		$lang_prefix = $cmsController->getCurrentLang()->getPrefix();
		$lang_default = $cmsController->getCurrentLang()->getIsDefault();
		if (!$template) {
			if ($lang_default) {
				$template="default";
			} else {
				$template="default." . $lang_prefix;
				$file = "tpls/captcha/".$template.".tpl";
				if (!file_exists($file)) {
					$template="default";
				}
			}
		}
		$config = mainConfiguration::getInstance();
		if(!$config->get('anti-spam', 'captcha.enabled')) {
			return '';
		}
		return umiCaptcha::generateCaptcha($template);
	}


	public function smartSubstring($string, $max_length = 30) {
		if(!$max_length) $max_length = 30;

		if(strlen($string) > ($max_length - 3)) {
			return substr($string, 0, ($max_length - 3)) . "...";
		} else {
			return $string;
		}
	}


	public function referer_uri() {
		return htmlspecialchars(getServer('HTTP_REFERER'));
	}



	public function getNext($path, $template = "default", $prop_name = "", $order = 0) {
		if(!$template) $template = "default";

		$element_id = def_module::analyzeRequiredPath($path);

		if($element_id === false) {
			throw new publicException(getLabel('error-page-does-not-exist', null, $path));
		}

		$element = umiHierarchy::getInstance()->getElement($element_id);

		if($element instanceof iUmiHierarchyElement == false) {
			throw new publicException('error-require-more-permissions');
		}

		$parent_id = $element->getParentId();

		if($prop_name) {
			$sel = new umiSelection;
			$sel->addHierarchyFilter($parent_id);
			$sel->addActiveFilter(true);
			$sel->addPermissions();

			$object_type_id = $element->getObject()->getTypeId();
			$object_type = umiObjectTypesCollection::getInstance()->getType($object_type_id);

			$order_field_id = $object_type->getFieldId($prop_name);
			if(!$order_field_id) {
				throw new publicException(getLabel('error-prop-not-found', null, $prop_name));
			}
			$sel->setOrderByProperty($order_field_id, $order);

			$sort_array = umiSelectionsParser::runSelection($sel);
		} else {
			$sort_array = umiHierarchy::getInstance()->getChilds($parent_id, false);
			$sort_array = array_keys($sort_array);
		}

		$next_id = false;
		$is_matched = false;

		foreach($sort_array as $id) {
			if($is_matched) {
				$next_id = $id;
				break;
			}

			if($id == $element_id) {
				$is_matched = true;
			}
		}

		list($tpl, $tpl_last) = def_module::loadTemplates("content/slider/".$template, "next", "next_last");

		if($next_id !== false) {
			$block_arr = Array();
			$block_arr['id'] = $next_id;
			$block_arr['link'] = umiHierarchy::getInstance()->getPathById($next_id);
			return def_module::parseTemplate($tpl, $block_arr, $next_id);
		} else {
			return $tpl_last;
		}



		return $element_id;
	}

	public function getPrevious($path, $template = "default", $prop_name = "", $order = 0) {
		if(!$template) $template = "default";

		$element_id = def_module::analyzeRequiredPath($path);

		if($element_id === false) {
			throw new publicException(getLabel('error-page-does-not-exist', null, $path));
		}

		$element = umiHierarchy::getInstance()->getElement($element_id);
		$parent_id = $element->getParentId();

		if($prop_name) {
			$sel = new umiSelection;
			$sel->addHierarchyFilter($parent_id);
			$sel->addActiveFilter(true);
			$sel->addPermissions();

			$object_type_id = $element->getObject()->getTypeId();
			$object_type = umiObjectTypesCollection::getInstance()->getType($object_type_id);

			$order_field_id = $object_type->getFieldId($prop_name);
			if(!$order_field_id) {
				throw new publicException(getLabel('error-prop-not-found', null, $prop_name));
			}
			$sel->setOrderByProperty($order_field_id, $order);

			$sort_array = umiSelectionsParser::runSelection($sel);
		} else {
			$sort_array = umiHierarchy::getInstance()->getChilds($parent_id, false);
			$sort_array = array_keys($sort_array);
		}

		$prev_id = false;

		foreach($sort_array as $id) {

			if($id == $element_id) {
				break;
			} else {
				$prev_id = $id;
			}
		}

		list($tpl, $tpl_first) = def_module::loadTemplates("content/slider/".$template, "previous", "previous_first");

		if($prev_id !== false) {
			$block_arr = Array();
			$block_arr['id'] = $prev_id;
			$block_arr['link'] = umiHierarchy::getInstance()->getPathById($prev_id);
			return def_module::parseTemplate($tpl, $block_arr, $prev_id);
		}
		else {
			return (def_module::isXSLTResultMode()) ? "" : $tpl_first;
		}


		return $element_id;
	}


	public function listErrorMessages($template = "default") {
		if(!$template) $template = "default";

		if($requestId = getRequest('_err')) {
			if($errors = getSession('errors_' . $requestId)) {
				try {
					list($template_block, $template_block_line) = def_module::loadTemplates("errors/".$template, "errors_block", "errors_block_line");
				} catch (publicException $e) {
					$template_block = '<div class="errorsBlock">%items%</div>';
					$template_block_line = '<div class="errorsBlockLine">%message%</div>';
				}
				//$errors = array_unique($errors);
				$block_arr = Array();
				$items = Array();
				foreach($errors as $error) {
					$line_arr = Array();

					if($code = $error['code']) {
						$line_arr['attribute:code'] = $code;
					}

					if($strcode = $error['strcode']) {
						$line_arr['attribute:str-code'] = $strcode;
					}

					$error['message'] = cmsController::getInstance()->getCurrentTemplater()->putLangs($error['message']);

					$line_arr['node:message'] = $error['message'];
					$items[] = def_module::parseTemplate($template_block_line, $line_arr);
				}
				$block_arr['subnodes:items'] = $items;
				return def_module::parseTemplate($template_block, $block_arr);
			} else {
				return "";
			}
		} else {
			return "";
		}
	}


	public function getFilteredPages($type_id, $prop_name, $value, $per_page = 10, $template = "default", $ignore_paging = false, $field_id = false, $asc = true) {
		$curr_page = getRequest('p');
		if($ignore_paging) $curr_page = 0;

		list($template_block, $template_block_line, $template_block_empty) = def_module::loadTemplates("filtered_pages/".$template,
			"pages_block", "pages_block_line", "pages_block_empty");

		$type = umiObjectTypesCollection::getInstance()->getType($type_id);
		if($type instanceof umiObjectType) {
			if($prop_id = $type->getFieldId($prop_name)) {
				$sel = new umiSelection;
				$sel->forceHierarchyTable(true);
				$sel->addObjectType($type_id);

				$field = umiFieldsCollection::getInstance()->getField($prop_id);
				if($guide_id = $field->getGuideId()) {
					if(!is_numeric($value)) {
						$guide_items = umiObjectsCollection::getInstance()->getGuidedItems($guide_id);
						$value = array_search($value, $guide_items);
					}
				}
				$sel->addPropertyFilterEqual($prop_id, $value);
				$sel->addPermissions();
				$sel->addLimit($per_page, $curr_page);

				$sel->addActiveFilter(true);

				if($field_id) {
					$sel->setOrderByProperty($field_id, $asc);
				}
				else {
					$sel ->setOrderByObjectId($asc);
				}

				$result = umiSelectionsParser::runSelection($sel);
				$total = umiSelectionsParser::runSelectionCounts($sel);

				$block_arr = array();

				if($total > 0) {
					$items = array();
					$umiHierarchyInstance = umiHierarchy::getInstance();
					foreach($result as $element_id) {
						$element = $umiHierarchyInstance->getElement($element_id);
						if($element instanceof umiHierarchyElement) {
							$items[] = def_module::parseTemplate($template_block_line, array(
								'attribute:id'		=> $element->id,
								'attribute:link'	=> $element->link,
								'node:name'			=> $element->name
							));
						}
					}
					$block_arr['subnodes:items'] = $items;
					$template = $template_block;
				} else {
					$template = $template_block_empty;
				}

				$block_arr['total'] = $total;
				$block_arr['per_page'] = $per_page;
				return def_module::parseTemplate($template, $block_arr);
			} else {
				throw new publicException("Type \"" . $type->getName() . "\" doesn't have property \"{$prop_name}\"");
			}

		} else {
			throw new publicException("Wrong type id \"{$type_id}\"");
		}
	}


	public function hierarchyTypesList() {
		$items = array();
		$hierarchy_types = umiHierarchyTypesCollection::getInstance()->getTypesList();

		foreach($hierarchy_types as $type) {
			$items[] = def_module::parseTemplate("", array(
				'attribute:id'		=> $type->getId(),
				'attribute:module'	=> $type->getName(),
				'attribute:method'	=> $type->getExt(),
				'node:title'		=> $type->getTitle()
			));
		}

		return def_module::parseTemplate("", array(
			'subnodes:items'	=> $items
		));
	}


	public function fieldTypesList() {
		$items = array();
		$field_types_list = umiFieldTypesCollection::getInstance()->getFieldTypesList();
		foreach($field_types_list as $field_type) {
			$line_arr = array(
				'attribute:id'			=> $field_type->getId(),
				'attribute:data-type'	=> $field_type->getDataType()
			);

			if($field_type->getIsMultiple()) {
				$line_arr['attribute:is-multiple'] = true;
			}

			if($field_type->getIsUnsigned()) {
				$line_arr['attribute:is-unsigned'] = true;
			}

			$line_arr['node:name'] = $field_type->getName();
			$items[] = def_module::parseTemplate("", $line_arr);
		}

		return def_module::parseTemplate('', array(
			'subnodes:items'	=> $items
		));
	}


	public function publicGuidesList() {
		$items = array();
		$guides_list = umiObjectTypesCollection::getInstance()->getGuidesList();
		foreach($guides_list as $guide_id => $guide_name) {
			$items[] = def_module::parseTemplate("", array(
				'attribute:id'	=> $guide_id,
				'node:name'		=> $guide_name
			));
		}

		return def_module::parseTemplate('', array(
			'subnodes:items' => $items
		));
	}


	public function getSkinsList() {
		$result = array();
		$config = mainConfiguration::getInstance();

		$skinsList = $config->get('system', 'skins');
		$current_skin = system_get_skinName();

		$skins = array();
		foreach($skinsList as $skinName) {
			$skins[] = array(
				'attribute:id'	=> $skinName,
				'node:name'		=> getLabel('skin-' . $skinName)
			);
		}

		$result['items'] = Array(
			'nodes:item' => $skins,
			'attribute:current' => $current_skin
		);

		return $result;
	}

	public function getSeparator() {
		$result = array();
		$config = mainConfiguration::getInstance();
		$separator = $config->get('seo', 'alt-name-separator');
		if ($separator == "_" || $separator == "-") {
			$result['separator'] = Array(
				'attribute:value' => $separator
			);
			return $result;
		} else {
			$result['separator'] = Array(
				'attribute:value' => "_"
			);
			return $result;
		}
	}

	/* Deprecated: for 2.7.4 -> 2.8 migration */
	protected function includeOldQuickEditJs() {
		$sJS = <<<END
			<script type="text/javascript" src="/scriptaculous/lib/prototype.js" charset="utf-8"></script>
			<script type="text/javascript" src="/js/lLib.js" charset="utf-8"></script>
			<script type="text/javascript" src="/js/client/umiBasket.js" charset="utf-8"></script>

END;

		$userId = permissionsCollection::getInstance()->getUserId();
		$guestId = umiObjectsCollection::getInstance()->getObjectIdByGUID('system-guest');
		if($userId != $guestId) {
			$sJS .= <<<END
			<script type="text/javascript" src="/scriptaculous/src/effects.js" charset="utf-8"></script>
			<script type="text/javascript" src="/scriptaculous/src/dragdrop.js" charset="utf-8"></script>
			<script type="text/javascript" src="/js/easy.php" charset="utf-8"></script>
END;
		}else {
			$sJS.= <<<END
			<script type="text/javascript" src="/js/guest.js" charset="utf-8"></script>
END;
		}

		return $sJS;
	}

	public function includeQuickEditJs() {
		$config = mainConfiguration::getInstance();
		$old_client_js = (int) $config->get('system', 'use-old-client-js');
		if ($old_client_js) {
			return self::includeOldQuickEditJs();
		}

		$build = regedit::getInstance()->getVal("modules/autoupdate/system_build");

		$sJS = <<<HTML
			<script type="text/javascript" src="/js/client/umiBasket.js?{$build}" charset="utf-8"></script>
HTML;

		$sJS = '';

		$permissions = permissionsCollection::getInstance();
		$userId      = $permissions->getUserId();
		$isAllowed   = $permissions->isAllowedMethod($userId, "content", "sitetree");
		if($isAllowed) {
			$hierarchy = umiHierarchy::getInstance();

			$title = $keywords  = $description = "";

			$elementId =  cmsController::getInstance()->getCurrentElementId();
			$element = $hierarchy->getElement($elementId );

			$sJS .= <<<HTML
				<link rel="stylesheet" href="/js/client/edit-in-place/design.css?{$build}" type="text/css"></link>
				<link rel="stylesheet" href="/js/client/panel/design.css?{$build}" type="text/css"></link>

				<link rel="stylesheet" href="/js/jquery/ui.all.css?{$build}" type="text/css"></link>
				<link rel="stylesheet" href="/styles/common/css/popup.css?{$build}" type="text/css"></link>

				<link type="text/css" rel="stylesheet" href="/styles/skins/mac/design/calendar/calendar.css?{$build}"></link>

				<script type="text/javascript" src="/js/smc/Session.js?{$build}" charset="utf-8"></script>
				<script type="text/javascript" src="/ulang/common.js?{$build}" charset="utf-8"></script>

				<script type="text/javascript" src="/js/jquery/jquery.js?{$build}" charset="utf-8"></script>
				<script type="text/javascript" src="/js/jquery/jquery.cookie.js?{$build}" charset="utf-8"></script>

				<script type="text/javascript" src="/js/jquery/jquery-ui.js?{$build}" charset="utf-8"></script>
				<script type="text/javascript" src="/js/jquery/jquery.umipopups.js?{$build}" charset="utf-8"></script>
				<script type="text/javascript" src="/js/jquery/jquery.jgrowl_minimized.js?{$build}" charset="utf-8"></script>
				<script type="text/javascript" src="/js/jquery/jquery-ui-i18n.js?{$build}" charset="utf-8"></script>

				<script type="text/javascript" src="/js/client/edit-in-place/compiled.js?{$build}" charset="utf-8"></script>



HTML;

			$sJS .= <<<HTML
			<script type="text/javascript">
HTML;
			if($element) {
				$objectId = 0;
				if($object = $element->getObject()) {
					$objectId = $object->getId();
				}

				$sJS .= "
					var EIP_META = {
						element_id: '$elementId',
						object_id: '$objectId',
						title: '".htmlspecialchars($element->getValue("title"),  ENT_QUOTES, 'UTF-8')."',
						meta_descriptions: '".htmlspecialchars($element->getValue("meta_descriptions"),  ENT_QUOTES, 'UTF-8')."',
						meta_keywords: '".htmlspecialchars($element->getValue("meta_keywords"),  ENT_QUOTES, 'UTF-8')."',
						alt_name: '" . $element->getAltName() . "'
					};
";
			}

			$cmsController = cmsController::getInstance();
			$langsCollection = langsCollection::getInstance();
			$langPrefix = '';
			if ($cmsController->getLang()->getId()!=$langsCollection->getDefaultLang()->getId()) {
				$langPrefix = $cmsController->getLang()->getPrefix();
			}
				$sJS .= "
					var langPrefix = \"{$langPrefix}\";
			";

			$sJS .= "
					var SessionLifetime = '".SESSION_LIFETIME."';
				</script>
";

			$eipTheme = $config->get("edit-in-place", "theme");
			if(strlen($eipTheme)) {
				$eipTheme = substr($eipTheme, 1);
				$sJS .= "<link rel=\"stylesheet\" href=\"{$eipTheme}?{$build}\" type=\"text/css\"></link>";
			}
			$eipWYSIWYG = $config->get("edit-in-place", "wysiwyg");
			if(strlen($eipWYSIWYG)) {
				if($eipWYSIWYG == 'tinymce') {
					$sJS .= <<<HTML
					<script src="/js/tinymce/jscripts/tiny_mce/tinymce_defs.js?{$build}" type="text/javascript" charset="utf-8"></script>
					<script src="/js/tinymce/jscripts/tiny_mce/tinymce_custom.js?{$build}" type="text/javascript" charset="utf-8"></script>
					<script src="/js/tinymce/jscripts/tiny_mce/tiny_mce_src.js?{$build}" type="text/javascript" charset="utf-8"></script>
					<script type="text/javascript" charset="utf-8">
						window.mceCommonSettings.toolbar_standart = "fontsettings,tablesettings,|,"
							+"cut,copy,paste,|,pastetext,pasteword,|,selectall,cleanup,|,"
							+ "undo,redo,|,"
							+ "link,unlink,anchor,image,media,|,"
							+ "charmap,code";
						tinyMCE.init(jQuery.extend(window.mceCommonSettings, window.mceCustomSettings));
						uPageEditor.wysiwyg = 'tinymce';
						/**
						 * Encode string to base64
						 * @param str string to encode
						 * @return String base64 encoded string
						 */
						function base64encode(str) {
								var sWinChrs     = 'АБВГДЕЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯабвгдежзийклмнопрстуфхцчшщъыьэюя';
								var sBase64Chrs  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
								var arrBase64    = sBase64Chrs.split('');

								var a = new Array();
								var i = 0;
								for(i = 0; i < str.length; i++) {
									var cch = str.charCodeAt(i);
									if (cch > 127) {
										cch = sWinChrs.indexOf(str.charAt(i)) + 163;
										if(cch < 163) continue;
									}
									a.push(cch);
								}
								var s    = new Array();
								var lPos = a.length - a.length % 3;
								var t = 0;
								for (i=0; i<lPos; i+=3) {
									t = (a[i]<<16) + (a[i+1]<<8) + a[i+2];
									s.push(arrBase64[(t>>18)&0x3f] + arrBase64[(t>>12)&0x3f] + arrBase64[(t>>6)&0x3f] + arrBase64[t&0x3f] );
								}
								switch (a.length-lPos) {
									case 1 :
											t = a[lPos]<<4;
											s.push(arrBase64[(t>>6)&0x3f] + arrBase64[t&0x3f] + '==');
											break;
									case 2 :
											t = (a[lPos]<<10)+(a[lPos+1]<<2);
											s.push(arrBase64[(t>>12)&0x3f] + arrBase64[(t>>6)&0x3f] + arrBase64[t&0x3f] + '=');
											break;
								}
								return s.join('');
						}

					</script>
HTML;
				}
				else {
					$sJS .= <<<HTML
					<script type="text/javascript" charset="utf-8">
						uPageEditor.wysiwyg = 'inline';
					</script>
HTML;
				}
			}
			else {
				$sJS .= <<<HTML
				<script type="text/javascript" charset="utf-8">
					uPageEditor.wysiwyg = 'inline';
				</script>
HTML;
			}
		} else {
			$sJS .= <<<HTML
				<script type="text/javascript" src="/js/jquery/jquery.js?{$build}" charset="utf-8"></script>
				<script type="text/javascript" src="/js/guest.js?{$build}" charset="utf-8"></script>
HTML;
		}

		return $sJS;
	}

 	/* Deprecated: use only for 2.7.4  */
	public function includeEditInPlaceJs() {
		$config = mainConfiguration::getInstance();
		$old_client_js = (int) $config->get('system', 'use-old-client-js');
		if (!$old_client_js) {
			return ""; // return nothing for 2.8
		}

		$permissions = permissionsCollection::getInstance();
		$oUsrsMdl = cmsController::getInstance()->getModule("users");
		$iCurrentElementId = cmsController::getInstance()->getCurrentElementId();

		$is_allowed = $permissions->isAllowedMethod($permissions->getUserId(), "content", "sitetree");
		if(!$is_allowed) {
			return "";
		}

		$editable = (int) getRequest('editable');

		$sJS = <<<END
			<script type="text/javascript">
				var currentElementId = '{$iCurrentElementId}';
				var editable = {$editable};
			</script>

			<script type="text/javascript" src="/tinymce/jscripts/tiny_mce/tinymce_defs.js" charset="utf-8"></script>
			<script type="text/javascript" src="/tinymce/jscripts/tiny_mce/tinymce_custom.js" charset="utf-8"></script>
			<script type="text/javascript" src="/tinymce/jscripts/tiny_mce/tiny_mce_src.js" charset="utf-8"></script>

			<script type="text/javascript" src="/js/client/editInPlace.js" charset="utf-8"></script>

			<script type="text/javascript">
				var commStgs = Object.extend(window.mceCommonSettings, window.mceCustomSettings);

				tinyMCE.init(Object.extend(commStgs, {
					theme : "editinplace",
					plugins: "",
					width: '100%'
				}));
			</script>

END;
		return $sJS;
	}



	public function getObjectTypesList($module, $method = false) {
		$typesCollection = umiObjectTypesCollection::getInstance();
		$hierarchyTypesCollection = umiHierarchyTypesCollection::getInstance();


		if(is_numeric($module) && $method === false) {
			$type = $typesCollection->getType($module);
			if($type instanceof umiObjectType) {
				$hierarchy_type_id = $type->getHierarchyTypeId();
				$hierarchy_type = $hierarchyTypesCollection->getType($hierarchy_type_id);
			} else {
				throw new publicException("Object type #{$module} doesn't exists");
			}
		} else {
			$hierarchy_type = $hierarchyTypesCollection->getTypeByName($module, $method);
		}

		if($hierarchy_type) {
			$types = $typesCollection->getTypesByHierarchyTypeId($hierarchy_type->getId());

			$result = Array();
			foreach($types as $id => $type_name) {
				$item_arr = Array();
				$item_arr['attribute:id'] = $id;
				$item_arr['node:name'] = $type_name;

				$result[] = $item_arr;
			}
			return Array("items" => Array("nodes:item" => $result));
		} else {
			throw new publicException("Hierarchy type for {$module}/{$method} not found");
		}
	}


	public function getChildObjectTypesList($type_id) {
		$typesCollection = umiObjectTypesCollection::getInstance();

		$types = $typesCollection->getChildClasses($type_id);

		$result = Array();
		foreach($types as $id) {
			$item_arr = Array();
			$item_arr['attribute:id'] = $id;
			$item_arr['node:name'] = $typesCollection->getType($id)->getName();

			$result[] = $item_arr;
		}
		return Array("items" => Array("nodes:item" => $result));
	}

	/**
	* Returns udata array with guide items count
	*
	* @param mixed $guideId
	* @return array
	*/
	public function getGuideItemsCount($guideId) {
		$guideId = is_numeric($guideId) ? $guideId : umiObjectTypesCollection::getInstance()->getTypeIdByGUID($guideId);
		$count = umiObjectsCollection::getInstance()->getCountByTypeId($guideId);
		return array("items" => array("attribute:total" => $count));
	}

	public function getLangsList() {
		$langsCollection = langsCollection::getInstance();
		$langs = $langsCollection->getList();

		$block_arr = array();
		$block_arr['items'] = Array('nodes:item' => $langs);
		return $block_arr;
	}


	public function getInterfaceLangsList() {
		$config = mainConfiguration::getInstance();
		$interface_langs = $config->get('system', 'interface-langs');

		$items_arr = array();
		foreach($interface_langs as $lang_prefix) {
			$items_arr[] = array(
				"attribute:prefix"	=> $lang_prefix,
				"node:title"		=> getLabel("interface-lang-" . $lang_prefix)
			);
		}


		$block_arr = array();
		$block_arr['items'] = array(
			'attribute:current' => uLangStream::getLangPrefix(),
			'nodes:item' => $items_arr
		);
		return $block_arr;
	}


	public function getTemplatesList($domain_host = false) {
		$templatesCollection = templatesCollection::getInstance();
		$cmsController = cmsController::getInstance();

		$lang_id = $cmsController->getCurrentLang()->getId();
		$domain_id = false;

		if($domain_host) {
			$domain_id = domainsCollection::getInstance()->getDomainId($domain_host);
		}

		if(!$domain_id) {
			$domain_id = $cmsController->getCurrentDomain()->getId();
		}

		$templates = $templatesCollection->getTemplatesList($domain_id, $lang_id);

		$items = Array();
		foreach($templates as $template) {
			$item_arr = Array();
			$item_arr['attribute:id'] = $template->getId();
			$item_arr['node:name'] = $template->getTitle();
			$items[] = $item_arr;
		}

		return Array("items" => Array('nodes:item' => $items));
	}


	public function getFieldTypesList() {
		$field_types = umiFieldTypesCollection::getInstance()->getFieldTypesList();

		$items = Array();
		foreach($field_types as $field_type_id => $field_type) {
			$item_arr = Array();
			$item_arr['attribute:id'] = $field_type_id;
			$item_arr['node:name'] = $field_type->getName();
			$items[] = $item_arr;
		}

		$block_arr = Array();
		$block_arr['nodes:item'] = $items;
		return $block_arr;
	}


	public function base64($mode, $str) {
		switch($mode) {
			case "encode": {
				return base64_encode($str);
			}

			case "decode": {
				return base64_decode($str);
			}

			default: {
				throw new publicException("Don't know, how to do base64 \"{$mode}\". Type \"encode\" or \"decode\".");
			}
		}
	}

	public function fs($mode, $file) {
		switch($mode) {
			case "dirname": {
				return dirname($file);
			}

			default: {
				throw new publicException("Don't know, how to do fs \"{$mode}\".");
			}
		}
	}


	public function getSubNavibar($element_id = false) {
		$cmsController = cmsController::getInstance();
		$hierarchy = umiHierarchy::getInstance();
		$module = $cmsController->getCurrentModule();
		$method = $cmsController->getCurrentMethod();

		$result = Array();
		$result['module'] = Array(
			"attribute:label"	=> getLabel("module-" . $module),
			"node:name"		=> $module
		);

		if($element_id != false) {
			$parents = $hierarchy->getAllParents($element_id, true);

			$pages_arr = Array();
			foreach($parents as $element_id) {
				if($element_id == 0) {
					continue;
				}
				$element = $hierarchy->getElement($element_id);
				if($element instanceof umiHierarchyElement) {
					$page_arr = Array();
					$page_arr['attribute:id'] = $element_id;
					$page_arr['xlink:href'] = "upage://" . $element_id;
					$page_arr['attribute:name'] = $element->getName();
					$page_arr['attribute:edit-link'] = $this->getEditLink($element_id);
					$pages_arr[] = $page_arr;
				}
			}
			if(sizeof($pages_arr)) {
				$result['parents'] = Array('nodes:page' => $pages_arr);
			}
		}


		if($method) {
		    $label = "header-" . $module . "-" . $method;

			if($cmsController->headerLabel) {
				$label = $cmsController->headerLabel;
			}

			$result['method'] = Array(
				"attribute:label"	=> getLabel($label),
				"node:name"		=> $method
			);
		}

		return $result;
	}


	public function getEditLink($element_id) {
		$hierarchy = umiHierarchy::getInstance();
		$cmsController = cmsController::getInstance();

		$element = $hierarchy->getElement($element_id);
		if($element instanceof umiHierarchyElement) {
			$module_name = $element->getModule();
			$method_name = $element->getMethod();

			$module = $cmsController->getModule($module_name);
			if($module instanceof def_module) {
				$links = $module->getEditLink($element_id, $method_name);
				if(isset($links[1])) {
					return $links[1];
				}
			} else {
				throw new publicException("Module \"{$module_name}\" not found. So I can't get edit link for element #{$element_id}");
			}
		} else {
			throw new publicException("Element #{$element_id} doesn't exists.");
		}
	}


	public function getObjectName($object_id) {
		$objects = umiObjectsCollection::getInstance();
		$object = $objects->getObject($object_id);
		if($object instanceof umiObject) {
			return $object->getName();
		} else {
			throw new publicException(getLabel('error-object-does-not-exist', null, $object_id));
		}
	}




	// Thanks, Anton Timoshenkov.
	// See realisation in lib.php
	public function makeThumbnailFull($path, $width, $height, $template = "default", $returnArrayOnly = false, $crop = true, $cropside = 5, $isLogo = false, $quality = 80) {

		$arr = makeThumbnailFull($path, $this->thumbs_path, $width, $height, $crop, $cropside, $isLogo, $quality);

		if(cmsController::getInstance()->getCurrentMode() == "admin") {
			$arr['src'] = str_replace("&", "&amp;", $arr['src']);
		}

		$arr['void:template'] = $template;

		if($returnArrayOnly) {
			return $arr;
		} else {
			list($tpl) = def_module::loadTemplates("thumbs/".$template, "image");
			return def_module::parseTemplate($tpl, $arr);
		}
	}

	public function getModuleSetting($moduleName, $settingName) {
		$cmsController = cmsController::getInstance();
		$regedit = regedit::getInstance();

		if($cmsController->getCurrentMode() != 'admin') {
			throw new publicException("Sorry, but you are not allowed to use this method here");
		}

		if($module = $cmsController->getModule($moduleName)) {
			$settingValue = $regedit->getVal("//modules/" . get_class($module) . "/" . $settingName);
			return $settingValue ? $settingValue : null;
		} else return false;
	}

	public function googleAnalyticsCode() {
		$regedit = regedit::getInstance();
		$domain = cmsController::getInstance()->getCurrentDomain();
		$googleAnalyticsId = $regedit->getVal("//settings/ga-id/{$domain->getId()}");

		if($googleAnalyticsId) {
			return <<<END
<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', '{$googleAnalyticsId}']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
END;
		} else {
			return "";
		}
	}

	public function get_module_tabs($module_name, $method_name) {
		$cmsController = cmsController::getInstance();
		$module = $cmsController->getModule($module_name);
		if (!$module instanceof def_module) return "";
		$commonTabs = $module->getCommonTabs();
		$configTabs = $module->getConfigTabs();

		$affectedTabs = null;
		$current_tab = false;

		if ($commonTabs && $current_tab = $commonTabs->getTabNameByAlias($method_name)) {
			$affectedTabs = $commonTabs;
		} elseif ($configTabs && $current_tab = $configTabs->getTabNameByAlias($method_name)) {
			$affectedTabs = $configTabs;
		}

		if (!$affectedTabs instanceof adminModuleTabs) return "";

		$pre_lang = $cmsController->pre_lang;

		$items = array();
		foreach ($affectedTabs->getAll() as $tab_name => $method_aliases) {
			$labelSuffix = $module_name. "-" . $tab_name;
			$label = getLabel("tabs-" . $labelSuffix) != "tabs-" . $labelSuffix ? getLabel("tabs-" . $labelSuffix) : getLabel("header-" . $labelSuffix);

			$item_arr = array();
			$item_arr['attribute:name'] = $tab_name;
			$item_arr['attribute:label'] = $label;
			$item_arr['attribute:link'] = $pre_lang . "/admin/" . $module_name . "/" . $tab_name . "/";
			$aliases = array();
			foreach($method_aliases as $alias) {
				$aliases[] = def_module::parseTemplate("", array("attribute:name" => $alias));
			}
			if ($tab_name == $current_tab) {
				$item_arr['attribute:active'] = 1;
			}
			$item_arr['subnodes:aliases'] = $aliases;
			$items[] = def_module::parseTemplate("", $item_arr);
		}

		return def_module::parseTemplate("", array(
			"subnodes:items" => $items
		));
	}


	public function alphabeticalIndex($elementId = false, $depth = 1, $template = 'default', $pattern = 'а-яa-z0-9') {
		list($tpl_block, $tpl_letter, $tpl_letter_a) = def_module::loadTemplates("alphabetical-index/".$template, "block", "block_item", "block_item_a");

		$pages = new selector('pages');

		$elementId = def_module::analyzeRequiredPath($elementId);
		if($elementId) {
			$pages->where('hierarchy')->page($elementId)->childs($depth);
		}

		$index = new alphabeticalIndex($pages);
		$letters = $index->index($pattern);

		$baseLink = null;
		$element = selector::get('page')->id($elementId);
		$baseLink = $element->link;

		$items_arr = array();
		foreach($letters as $letter => $count) {
			$template_item = ($count ? $tpl_letter_a : $tpl_letter);

			$link = $count ? ($baseLink . '?fields_filter[name]=' . $letter . '%') : null;
			$items_arr[] = def_module::parseTemplate($template_item, array(
				'@count'	=> $count,
				'@link'		=> $link,
				'#letter'	=> (string) $letter
			));
		}

		$block_arr = array(
			'nodes:letter' => $items_arr
		);
		return def_module::parseTemplate($tpl_block, $block_arr);
	}


	public function calendarIndex($elementId, $fieldName, $year = false, $month = false, $depth = 1, $template = 'default') {
		list($tpl_block, $tpl_week, $tpl_day, $tpl_day_a, $tpl_day_null) = def_module::loadTemplates(
		"./tpls/calendar/".$template, 'calendar', 'week', 'day','day_a', 'day_null');

		$pages = new selector('pages');

		$elementId = def_module::analyzeRequiredPath($elementId);
		if(!$elementId) {
			throw new publicException("Page #{$elementId} not found");
		}

		$hierarchy = umiHierarchy::getInstance();
		$objectTypeId = $hierarchy->getDominantTypeId($elementId);
		if(!$objectTypeId) return;

		$pages->types('object-type')->id($objectTypeId);
		$pages->where('hierarchy')->page($elementId)->childs($depth);

		try {
			$index = new calendarIndex($pages);
			$calendar = $index->index($fieldName, $year, $month);
		} catch (baseException $e) {
			throw new publicException($e->getMessage());
		}

		$weeks = array();

		$weeksCount = ceil((sizeof($calendar['days']) + $calendar['first-day']) / 7);
		$daysCount = $weeksCount * 7;

		$baseLink = null;
		$element = selector::get('page')->id($elementId);
		if($element) $baseLink = $element->link;
		$fromTs = $index->timeStart;
		for($i = 0; $i < $weeksCount; $i++) {
			$days = array();
			for($j = 0; $j < 7; $j++) {
				$number = $i * 7 + $j - $calendar['first-day'] + 1;
				if($number > sizeof($calendar['days']) || $number <= 0) {
					$number = false;
					$tpl = $tpl_day_null;
					$count = 0;
				} else {
					$count = (int) $calendar['days'][$number];
					$tpl = $count ? $tpl_day_a : $tpl_day;
				}

				$link = null;
				if($count) {
					$t1 = $fromTs + 3600 * 24 * ($number - 1);
					$t2 = $t1 + 3600 * 24;
					$link = $baseLink . "?fields_filter[{$fieldName}][]={$t1}&fields_filter[{$fieldName}][]=" . $t2;
				}

				$days[] = def_module::parseTemplate($tpl, array(
					'@count'	=> $count,
					'@link'		=> $link,
					'#day'		=> $number

				));
			}
			$week = array(
				'void:days' => $days,
				'nodes:day'	=> $days
			);
			$weeks[] = def_module::parseTemplate($tpl_week, $week);
		}

		return def_module::parseTemplate($tpl_block, array(
			'date'				=> $index->timeStart,
			'year'				=> $calendar['year'],
			'month'				=> $calendar['month'],
			'void:weeks'		=> $weeks,
			'nodes:week'		=> $weeks
		));
	}

	public function getDominantTypeId($elementId = 0) {
		$hierarchy = umiHierarchy::getInstance();
		return $hierarchy->getDominantTypeId((int) $elementId);
	}


	public function getVideoPlayer() {
		$width  = 640;
		$height = 360;
		$template = "default";
		$arguments = func_get_args();
		if(!count($arguments)) {
			throw new publicException("No video specified");
			return null;
		}
		if(count($arguments) > 1 && strval(intval($arguments[0])) === $arguments[0]) {
			$entityId  = (int)$arguments[0];
			$fieldName = $arguments[1];
			$indexBase = 2;
			$entity = umiHierarchy::getInstance()->getElement($entityId);
			if(!$entity) {
				$entity = umiObjectsCollection::getInstance()->getObject($entityId);
				if($entity) {
					throw new publicException("Entity {$entityId} doesn't exists");
					return null;
				}
			}
			$path = (string)$entity->getValue($fieldName);
		} else {
			$path = $arguments[0];
			$indexBase = 1;
		}
		if(count($arguments) > $indexBase) {
			$width = (int)$arguments[$indexBase];
		}
		if(count($arguments) > $indexBase+1) {
			$height = (int)$arguments[$indexBase+1];
		}
		if(count($arguments) > $indexBase+2) {
			$template = (int)$arguments[$indexBase+2];
		}
		if(count($arguments) > $indexBase+3) {
			$autoload = (string)$arguments[$indexBase+3];
		}
		else {
			$autoload  = 'false';
		}

		list($template) = def_module::loadTemplates("video/".$template, 'video_player');
		return def_module::parseTemplate($template, array("path"=>$path, "width"=>$width, "height"=>$height, 'autoload'=>$autoload) );
	}
	
	public function checkLicenseKey() {
		
		$permissions = permissionsCollection::getInstance();
		if (!$permissions->isAdmin()) return false;
	
		$keycode = regedit::getInstance()->getVal("//settings/keycode");
		
		$response = umiRemoteFileGetter::get("http://www.umi-cms.ru/udata/updatesrv/checkLicenseKey/{$keycode}/");
			
		$dom = new DOMDocument;
		$block_arr = array();

		if(@$dom->loadXML($response) === false) {
			$block_arr['error'] = getLabel('error-invalid_answer');
		} else {
			$udata = $dom->getElementsByTagName("udata")->item(0);
			$childs = $udata->childNodes;
			
			$error = false;
			
			for ($i = 0; $i < $childs->length; $i++) {
				$child = $childs->item($i);
				$nodeName = $child->nodeName;
				if ($nodeName == "error") $error = true;
				$block_arr[$nodeName] = html_entity_decode($child->nodeValue);
			}
			
			if (!$error) {
				
				$userId = $permissions->getUserId();
				$user = umiObjectsCollection::getInstance()->getObject($userId);
				
				$block_arr['user'] = array(
					'attribute:domain'		=> cmsController::getInstance()->getCurrentDomain()->getHost(),
					'attribute:name'		=> $user->getValue('fname'),
					'attribute:email'		=> $user->getValue('e-mail')
				);
				
				$block_arr += system_buildin_load('core')->getDomainsList();
				
			}
			
		}
		
		return def_module::parseTemplate('', $block_arr);
		
	}
	
	public function sendSupportRequest() {
		if (defined('CURRENT_VERSION_LINE') && CURRENT_VERSION_LINE=='demo') {
			$block_arr = array();
			$block_arr['success'] = getLabel('no-ask-support-in-demo-mode');
			return def_module::parseTemplate('', $block_arr);
		}

		$headers = array ("Content-type" => 'application/x-www-form-urlencoded; charset=utf-8');
		
		$response = umiRemoteFileGetter::get("http://www.umi-cms.ru/webforms/post_support/?ajax=1", false, $headers, $_POST);
		
		$block_arr = array();	
		$dom = new DOMDocument;
	
		if(@$dom->loadXML($response) === false) {
			$block_arr['error'] = getLabel('error-invalid_answer');
		} else {
			$block_arr[$dom->documentElement->nodeName] = html_entity_decode($dom->documentElement->nodeValue);
		}

		return def_module::parseTemplate('', $block_arr);
		
	}

};


?>