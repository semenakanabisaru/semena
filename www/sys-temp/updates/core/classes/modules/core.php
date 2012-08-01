<?php
	class core {
		public function cms_callMethod($method_name, $args) {
			return call_user_func_array(array($this, $method_name), $args);
		}

		public function isMethodExists($method_name) {
			return method_exists($this, $method_name);
		}

		public function __call($method, $args) {
			throw new publicException("Method " . get_class($this) . "::" . $method . " doesn't exists");
		}


		public function navibar($template = 'default', $isFull = true, $offsetLeft = 0, $offsetRight = 0) {
			if(!$template) $template = 'default';
			$cmsController = cmsController::getInstance();
			$hierarchy = umiHierarchy::getInstance();
			$currentElementId = $cmsController->getCurrentElementId();
			
			list(
				$tpl_block, $tpl_block_empty, $tpl_item, $tpl_item_a, $tpl_quant
			) = def_module::loadTemplates("content/navibar/".$template,
				'navibar', 'navibar_empty', 'element', 'element_active', 'quantificator'
			);

			$parents = $hierarchy->getAllParents($currentElementId);
			$parents[] = $currentElementId;
			
			$items = array();
			foreach($parents as $elementId) {
				if(!$elementId) continue;
				
				$element = $hierarchy->getElement($elementId);
				if($element instanceof iUmiHierarchyElement) {
					$items[] = $element;
				}
			}
			
			$sz = sizeof($items) - $offsetRight;
			$items_arr = array();
			for($i = (int) $offsetLeft; $i < $sz; $i++) {
				$element = $items[$i];
				$tpl_item_current = (!$isFull && $i == ($sz - 1)) ? $tpl_item_a : $tpl_item;

				$item_arr = def_module::parseTemplate($tpl_item_current, array(
					'attribute:id'		=> $element->id,
					'attribute:link'	=> $element->link,
					'xlink:href'		=> 'upage://' . $element->id,
					'node:text'			=> $element->name
				), $element->id);

				if (is_string($item_arr) && ($i != ($sz - 1))) {
					$item_arr .= $tpl_quant;
				}

				$items_arr[] = $item_arr;
			}
			
			if($sz == 0) $tpl_block = $tpl_block_empty;
			return def_module::parseTemplate($tpl_block, array(
				'items'			=> array('nodes:item'	=> $items_arr),
				'void:elements' => $items_arr
			));
		}

	
		public function insertCut($template = "default") {
			if(!$template) $template = "default";
	
			$pages = getRequest('cut_pages');
			$curr_page = ((int) getRequest('cut_curr_page')) + 1;
	
			if($pages > 1) {
				return "%system numpages('{$pages}', '1', '{$template}', 'cut')%";
			}
		}


		public function curr_module() {
			$cmsController = cmsController::getInstance();
			$module = $cmsController->getCurrentModule();
			$method = $cmsController->getCurrentMethod();
			
			if($module == "config" && $method == "mainpage") return "";
			if($module == "data" && $method == "trash") return "trash";
	
			return $module;
		}


		public function insertPopup($text = "", $src = "") {
			$res = $text;
	
			$path = (substr($src, 0, 1) == "/") ? "." . $src : $src;
			if(file_exists($path)) {
				$isz = getimagesize($path);
				if(is_array($isz)) {
					list($width, $height) = $isz;
					$res = "<a href=\"$src\" onclick=\"javascript: return gen_popup('$src', '$width', '$height');\" class=\"umi_popup\">" . $text . "</a>";
				}
			}
			return $res;
		}


		public function insertThumb($src1 = "", $src2 = "", $alt = "") {
			$path2 = (substr($src2, 0, 1) == "/") ? "." . $src2 : $src2;
	
			$thumb = "<img src=\"$src1\" border=\"0\" class=\"umi_thumb\" alt=\"{$alt}\" title=\"{$alt}\" />";
	
			if(file_exists($path2)) {
				$isz = getimagesize($path2);
				if(is_array($isz)) {
					list($width, $height) = $isz;
					$res = "<a href=\"$src2\" onclick=\"javascript: return gen_popup('$src2', '$width', '$height');\">" . $thumb . "</a>";
				}
			}
			return $res;
		}


		public function getTypeEditLinkXml($typeId) {
			if(system_is_allowed("data", "type_edit")) {
				$objectTypes = umiObjectTypesCollection::getInstance();
				
				if($type = $objectTypes->getType($typeId)) {
					return array(
						'type'	=> array(
							'node:name'			=> $type->getName(),
							'attribute:link'	=> $this->pre_lang . "/admin/data/type_edit/{$typeId}/"
						)
					);
				}
			}
		}


		public function importSkinXsl($filename = false) {
			static $emptyResult = false;
			$cmsController = cmsController::getInstance();

			$skin = system_get_skinName();
			$moduleName = $cmsController->getCurrentModule();
			
			if(!$filename) {
				$module = $cmsController->getModule($moduleName);
				$dataType = $module->dataType;
				$actionType = $module->actionType;
				
				if($actionType == 'create') {
					$actionType = 'modify';
				}
				
				$path = CURRENT_WORKING_DIR . '/styles/skins/' . $skin . '/data/' . $dataType . '.' . $actionType . '.xsl';
				
			} else {
				$path = CURRENT_WORKING_DIR . '/styles/skins/' . $skin . '/data/modules/' . $moduleName . '/' . $filename;
			}
			
			$path_empty = CURRENT_WORKING_DIR . '/styles/skins/' . $skin . '/empty.xsl';
	
			if(!$emptyResult) {
				if(file_exists($path_empty) == false) {
					throw new coreException("Empty template is required. Not found in '{$path_empty}'");
				}
				$emptyResult = array('plain:result' => file_get_contents($path_empty));
			}
	
			if(checkFileForReading($path, array('xsl'))) {
				$result = file_get_contents($path);
				return array('plain:result' => $result);
			} else {
				return $emptyResult;
			}
		}


		public function header() {
			$controller = cmsController::getInstance();
	
			if($controller->headerLabel) {
				$label = $controller->headerLabel;
			} else {
				$module = $controller->getCurrentModule();
				$method = $controller->getCurrentMethod();
				
				$label = "header-{$module}-{$method}";
			}
	
			return getLabel($label);
		}


		public function cacheIsEnabled() {
			$isStaticCache = (file_exists("./cache.config")) ? "1" : "0";
			$isDynamicCache = cacheFrontend::getInstance()->getIsConnected();
			return (int) ($isStaticCache || $isDynamicCache);
		}


		public function contextManualUrl() {
			$cmsController = cmsController::getInstance();
			$moduleName = $cmsController->getCurrentModule();
			$methodName = $cmsController->getCurrentMethod();
			$langPrefix = uLangStream::getLangPrefix();

			$subMethod = false;
			
			$module = $cmsController->getModule($moduleName);
			if($module instanceof def_module) {
				if(isset($module->data['object']['attribute:id'])) {
					$objectId = $module->data['object']['attribute:id'];
					$object = umiObjectsCollection::getInstance()->getObject($objectId);
					if($object instanceof umiObject) {
						
						$objectTypeId = $object->getTypeId();
						$objectType = umiObjectTypesCollection::getInstance()->getType($objectTypeId);
						$hierarchyTypeId = $objectType->getHierarchyTypeId();
						if($hierarchyTypeId) {
							$hierarchyType = umiHierarchyTypesCollection::getInstance()->getType($hierarchyTypeId);
							$subMethod = $hierarchyType->getExt();
						}
					}
				}
				
				if(!$subMethod && isset($module->data['page']['attribute:id'])) {
					$elementId = $module->data['page']['attribute:id'];
					$element = umiHierarchy::getInstance()->getElement($elementId);
					if($element instanceof umiHierarchyElement) {
						$subMethod = $element->getMethod();
					}
				}
				
				if(!$subMethod && isset($module->data['page']['attribute:type-id'])) {
					$objectTypeId = $module->data['page']['attribute:type-id'];
					$objectType = umiObjectTypesCollection::getInstance()->getType($objectTypeId);
					if($objectType instanceof umiObjectType) {
						$hierarchyTypeId = $objectType->getHierarchyTypeId();
						$hierarchyType = umiHierarchyTypesCollection::getInstance()->getType($hierarchyTypeId);
						if($hierarchyType instanceof umiHierarchyType) {
							$subMethod = $hierarchyType->getExt();
						}
					}
				}
	
				if(!$subMethod && isset($module->data['object']['attribute:type-id'])) {
					$objectTypeId = $module->data['object']['attribute:type-id'];
					$objectType = umiObjectTypesCollection::getInstance()->getType($objectTypeId);
					if($objectType instanceof umiObjectType) {
						$hierarchyTypeId = $objectType->getHierarchyTypeId();
						$hierarchyType = umiHierarchyTypesCollection::getInstance()->getType($hierarchyTypeId);
						if($hierarchyType instanceof umiHierarchyType) {
							$subMethod = $hierarchyType->getExt();
						}
					}
				}
			}
			
			$path = false;
			if($subMethod) {
			
				$tryPath = "./man/" . $langPrefix . "/" . $moduleName . "/" . $methodName . "." . $subMethod . ".html";
				if(is_file($tryPath)) {
					$path = $tryPath;
				}
			}
			
			if(!$path) {
				$tryPath = "./man/" . $langPrefix . "/" . $moduleName . "/" . $methodName . ".html";
				if(is_file($tryPath)) {
					$path = $tryPath;
				} else {
					$path = "./man/ru/" . $moduleName . "/" . $methodName . ".html";
				}
			}

			if(!$path) $path = "";
			
			return $path;
		}


		public function getDomainsList() {
			$domains = domainsCollection::getInstance();
			$permissions = permissionsCollection::getInstance();
			$userId = $permissions->getUserId();
			$result = $domains->getList();
			
			foreach ($result as $id => $domain) {
				if(!$permissions->isAllowedDomain($userId, $domain->getId())) unset($result[$id]);
			}
			
			return array('domains' => array('nodes:domain' => $result));
		}

		public function contextManual() {
			$path = $this->contextManualUrl();
			return ($path) ? array('plain:result' => file_get_contents($path)) : false;
		}
	};
?>