<?php
	abstract class __lib_content extends baseModuleAdmin {

		public function getObjectsByTypeList($type_id) {
			$objectsCollection = umiObjectsCollection::getInstance();
			$objects = $objectsCollection->getGuidedItems($type_id);
		
			$items = array();
			foreach($objects as $item_id => $item_name) {
				$items[] = array(
					'attribute:id'	=> $item_id,
					'node:name'		=> $item_name
				);
			}
			return array('items' => array('nodes:item' => $items));
		}


		public function getObjectsByBaseTypeList($module, $method) {
			$objectTypesCollection = umiObjectTypesCollection::getInstance();
			$objectsCollection = umiObjectsCollection::getInstance();

			$type_id = $objectTypesCollection->getBaseType($module, $method);
		
			$objects = $objectsCollection->getGuidedItems($type_id);
		
			$items = array();
			foreach($objects as $item_id => $item_name) {
				$items[] = array(
					'attribute:id'	=> $item_id,
					'node:name'		=> $item_name
				);
			}
			return array('items' => array('nodes:item' => $items));
		}
	
	
		public function getPagesByBaseTypeList($module, $method) {
			$hierarchyTypesCollection = umiHierarchyTypesCollection::getInstance();
			$hierarchy = umiHierarchy::getInstance();
		
			$type = $hierarchyTypesCollection->getTypeByName($module, $method);
			if($type instanceof iUmiHierarchyType) {
				$type_id = $type->getId();
			} else {
				throw new publicException("Hierarchy type {$module}::{$method} doesn't exists");
			}
		
			$sel = new umiSelection;
			$sel->addElementType($type_id);
			$sel->addPermissions();
		
			$result = umiSelectionsParser::runSelection($sel);
			$pages = array();
			foreach($result as $element_id) {
				$element = $hierarchy->getElement($element_id);
				if($element instanceof umiHierarchyElement) {
					$pages[] = $element;
				}
			}
			return Array("pages" => Array("nodes:page" => $pages));
		}
		
		
		public function widget_create() {
			$cmsController = cmsController::getInstance();
			$hierarchy = umiHierarchy::getInstance();
			$element_id = $this->expectElementId('param0');
			
			$mode = getRequest('do');
			
			$element = $hierarchy->getElement($element_id);

			if($element instanceof umiHierarchyElement) {
				$module_name = $element->getModule();
				$method_name = $element->getMethod();
				
				$module = $cmsController->getModule($module_name);
				if($module instanceof def_module) {
					$links = $module->getEditLink($element_id, $method_name);
					if(isset($links[0])) {
						$link = $links[0];
					}
					
				} else {
					throw new publicException("Module \"{$module_name}\" not found. So I can't get edit link for element #{$element_id}");
				}
				
				if(preg_match("/admin\/([^\/]+)\/([^\/]+)\/([^\/]+)?\/?([^\/]+)?\/?/", $link, $out)) {
					$method = getArrayKey($out, 2);

					$_REQUEST['param0'] = getArrayKey($out, 3);
					$_REQUEST['param1'] = getArrayKey($out, 4) ? getArrayKey($out, 4) : $mode;
					$_REQUEST['param2'] = getArrayKey($out, 5) ? getArrayKey($out, 5) : $mode;
					$_REQUEST['param3'] = $mode;

					return $module->$method();
				} else {
					throw new publicAdminException("Unknown error occured");
				}
			} else {
				$module_name = "content";
				$method_name = "add";
				
				$_REQUEST['param0'] = "0";
				$_REQUEST['param1'] = "page";
				$_REQUEST['param2'] = $mode;
				$_REQUEST['domain'] = $cmsController->getCurrentDomain()->getHost();
				
				$module = $cmsController->getModule($module_name);
				return $module->$method_name();
			}
		}
		
		public function widget_delete() {
			$element = $this->expectElement("param0");
			
			$params = Array(
					"element" => $element
			);
			
			$this->deleteElement($params);
		}
		
		public function domainTemplates() {
			$domains = domainsCollection::getInstance();
			$langs = langsCollection::getInstance();
			$templates = templatesCollection::getInstance();
			
			$data = Array();
			foreach($domains->getList() as $domain) {
				$domainId = $domain->getId();
				
				foreach($langs->getList() as $lang) {
					$langId = $lang->getId();
					
					foreach($templates->getTemplatesList($domainId, $langId) as $template) {
						$data['templates']['nodes:template'][] = $template;
					}
				}
			}
			
			foreach($domains->getList() as $domain) {
			    $data['domains']['nodes:domain'][] = $domain;
			}
			
			foreach($langs->getList() as $lang) {
			    $data['langs']['nodes:lang'][] = $lang;
			}
			
			$this->setDataType("list");
			$this->setActionType("view");
			
			$this->setData($data);
			return $this->doData();
		}

		public function onModifyPageWatchRedirects(umiEventPoint $e) {
			static $links = array();
			$redirects = redirects::getInstance();
			$hierarchy = umiHierarchy::getInstance();
			
			$element = $e->getRef('element');
			if($element instanceof umiHierarchyElement == false) {
				return false;
			}
			
			$elementId = $element->getId();
			$link = $hierarchy->getPathById($elementId, false, false, true);
			
			if($e->getMode() == 'before') {
				$links[$elementId] = $link;
				return true;
			}
			
			if($links[$elementId] != $link) {
				$redirects->add($links[$elementId], $link, 301);
			}
		}
	};
?>