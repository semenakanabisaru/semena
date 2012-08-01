<?php
	abstract class __settings_users {
		
		public function loadUserSettings() {
			$permissions = permissionsCollection::getInstance();
			$objects = umiObjectsCollection::getInstance();
			
			$user_id = $permissions->getUserId();
			$user = $objects->getObject($user_id);
			if($user instanceof umiObject == false) {
				throw new coreException("Can't get current user with id #{$user_id}");
			}
			
			$settings_data = $user->user_settings_data;
			$settings_data_arr = unserialize($settings_data);
			
			if(!is_array($settings_data_arr)) {
				$settings_data_arr = array();
			}
			
			
			$block_arr = array();
			
			$items = array();
			foreach($settings_data_arr as $key => $data) {
				$item_arr = array();
				$item_arr['attribute:key'] = (string) $key;
				
				$values_arr = array();
				foreach($data as $tag => $value) {
					$value_arr = array();
					$value_arr['attribute:tag'] = (string) $tag;
					
					if($key == 'dockItems' && $tag == 'common') {
						$value = $this->filterModulesList($value);
					}
					
					$value_arr['node:value'] = (string) $value;
					$values_arr[] = $value_arr;
				}
				$item_arr['nodes:value'] = $values_arr;
				$items[] = $item_arr;
			}
			$block_arr['items']['nodes:item'] = $items;
			return $block_arr;
		}
		
		public function saveUserSettings() {
			$this->flushAsXML("saveUserSettings");
			
			$permissions = permissionsCollection::getInstance();
			$objects = umiObjectsCollection::getInstance();
			
			$user_id = $permissions->getUserId();
			$user = $objects->getObject($user_id);
			if($user instanceof umiObject == false) {
				throw new coreException("Can't get current user with id #{$user_id}");
			}
			
			$settings_data = $user->getValue("user_settings_data");
			$settings_data = unserialize($settings_data);
			if(!is_array($settings_data)) {
				$settings_data = Array();
			}
			
			$key = getRequest('key');
			$value = getRequest('value');
			$tags = (Array) getRequest('tags');
			
			if(!$key) {
				throw new publicException("You should pass \"key\" parameter to this resourse");
			}
			
			if(sizeof($tags) == 0) {
				$tags[] = 'common';
			}
			
			foreach($tags as $tag) {
				if(!$value) {
					if(isset($settings_data[$key][$tag])) {
						unset($settings_data[$key][$tag]);
						
						if(sizeof($settings_data[$key]) == 0) {
							unset($settings_data[$key]);
						}
					}
				} else {
					$settings_data[$key][$tag] = $value;
				}
			}
			
			$user->setValue("user_settings_data", serialize($settings_data));
			$user->commit();
		}
	
		
		public function filterModulesList($modules) {
			if(!is_string($modules)) {
				return null;
			}
			
			$dockModules = explode(";", $modules);
			$cmsController = cmsController::getInstance();
			$systemModules = $cmsController->getModulesList();
			
			$result = array();
			foreach($dockModules as $moduleName) {
				if (in_array($moduleName, $systemModules) || $moduleName == 'trash') {
					$result[] = $moduleName;
				}
			}
			
			return implode(";", $result);
		}
	};
?>