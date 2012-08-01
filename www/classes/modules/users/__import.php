<?php
	abstract class __imp__users {
		public function permissions($module = "", $method = "", $element_id = false, $parent_id = false) {
			if(!$module && !$method && !$element_id && !$parent_id) {
				return "";
			}

			$perms_users = array();
			$perms_groups = array();
			if($element_id || $parent_id) {
				$typeId = umiObjectTypesCollection::getInstance()->getBaseType("users", "user");
				$objectsCollection = umiObjectsCollection::getInstance();
				$permissions = permissionsCollection::getInstance();
				$records     = $permissions->getRecordedPermissions($element_id ? $element_id : $parent_id);
				foreach($records as $id => $level) {
					$owner = $objectsCollection->getObject($id);
					if(!$owner) continue;
					if($owner->getTypeId() == $typeId) {
						if(is_array($owner->groups)) {
							foreach($owner->groups as $groupId) {
								$groupLevel = $permissions->isAllowedObject($groupId, $element_id ? $element_id : $parent_id);
								foreach($groupLevel as $i => $l) {
									$level |= pow(2, $i) * (int) $l;
								}
							}
						}

						$perms_users[] = array(
							'attribute:id'		=> $owner->getGUID() ? $owner->getGUID() : $owner->id,
							'attribute:login'	=> $owner->login,
							'attribute:access'	=> $level
						);
					} else {
						$perms_groups[] = array(
							'attribute:id'		=> $owner->getGUID() ? $owner->getGUID() : $owner->id,
							'attribute:title'	=> $owner->nazvanie,
							'attribute:access'	=> $level
						);
					}
				}
			} else {
				$objectTypesCollection = umiObjectTypesCollection::getInstance();
				$objectsCollection = umiObjectsCollection::getInstance();
				$cmsController = cmsController::getInstance();
				$permissions = permissionsCollection::getInstance();

				$current_user_id = $permissions->getUserId();
				$current_user = $objectsCollection->getObject($current_user_id);
				$current_owners = $current_user->getValue("groups");
				if(!is_array($current_owners)) {
					$current_owners = Array();
				}
				$current_owners[] = $current_user_id;

				if(!$method) $method = "page";
				$method_view = $method;
				$method_edit = $method . ".edit";

				$owners = $permissions->getPrivileged(array(array($module, $method_view), array($module, $method_edit)));
				foreach($owners as $ownerId) {
					if(in_array($ownerId, array(SV_USER_ID, SV_GROUP_ID))) continue;
					$owner = selector::get('object')->id($ownerId);
					if(!$owner) continue;

					$r = $e = $c = $d = $m = 0;
					if(in_array($ownerId, $current_owners)) {
						$r = permissionsCollection::E_READ_ALLOWED_BIT;
						$e = permissionsCollection::E_EDIT_ALLOWED_BIT;
						$c = permissionsCollection::E_CREATE_ALLOWED_BIT;
						$d = permissionsCollection::E_DELETE_ALLOWED_BIT;
						$m = permissionsCollection::E_MOVE_ALLOWED_BIT;
					} else {
						$r = $this->isAllowedMethod($ownerId, $module, $method_view) ? permissionsCollection::E_READ_ALLOWED_BIT : 0;
						$e = $this->isAllowedMethod($ownerId, $module, $method_edit) ? permissionsCollection::E_EDIT_ALLOWED_BIT : 0;
						if($e) {
							$c = permissionsCollection::E_CREATE_ALLOWED_BIT;
							$d = permissionsCollection::E_DELETE_ALLOWED_BIT;
							$m = permissionsCollection::E_MOVE_ALLOWED_BIT;
						}
					}

					$r = (int)$r & permissionsCollection::E_READ_ALLOWED_BIT;
					$e = (int)$e & permissionsCollection::E_EDIT_ALLOWED_BIT;
					$c = (int)$c & permissionsCollection::E_CREATE_ALLOWED_BIT;
					$d = (int)$d & permissionsCollection::E_DELETE_ALLOWED_BIT;
					$m = (int)$m & permissionsCollection::E_MOVE_ALLOWED_BIT;

					$ownerType = selector::get('object-type')->id($owner->getTypeId())->getMethod();

					if($ownerType == 'user') {
						$perms_users[] = array(
							'attribute:id'		=> $owner->getGUID() ? $owner->getGUID() : $owner->id,
							'attribute:login'	=> $owner->login,
							'attribute:access'	=> ($r + $e + $c + $d + $m)
						);
					} else {
						$perms_groups[] = array(
							'attribute:id'		=> $owner->getGUID() ? $owner->getGUID() : $owner->id,
							'attribute:title'	=> $owner->name,
							'attribute:access'	=> ($r + $e + $c + $d + $m)
						);
					}
				}
			}

			return def_module::parseTemplate('', array(
				'users'		=> array('nodes:user' => $perms_users),
				'groups'	=> array('nodes:group' => $perms_groups)
			));
		}

		public function getUserPermissions($id, $element_id) {
			$allow = permissionsCollection::getInstance()->isAllowedObject($id, $element_id);
			$permission = ((int)$allow[permissionsCollection::E_READ_ALLOWED]   * permissionsCollection::E_READ_ALLOWED_BIT) +
						  ((int)$allow[permissionsCollection::E_EDIT_ALLOWED]   * permissionsCollection::E_EDIT_ALLOWED_BIT) +
						  ((int)$allow[permissionsCollection::E_CREATE_ALLOWED] * permissionsCollection::E_CREATE_ALLOWED_BIT) +
						  ((int)$allow[permissionsCollection::E_DELETE_ALLOWED] * permissionsCollection::E_DELETE_ALLOWED_BIT) +
						  ((int)$allow[permissionsCollection::E_MOVE_ALLOWED]   * permissionsCollection::E_MOVE_ALLOWED_BIT);
			return array('user' => array('attribute:id' => $id, 'node:name' => $permission));
		}

		public function setPerms($element_id) {

			$permissions = permissionsCollection::getInstance();

			if(!getRequest('perms_read') && !getRequest('perms_edit') && !getRequest('perms_create') &&
			   !getRequest('perms_delete') && !getRequest('perms_move') &&
			   /* Note this argument. It's important' */
			   getRequest('default-permissions-set')) {

				$permissions->setDefaultPermissions($element_id);
				return;
			} elseif (!getRequest('perms_read') && !getRequest('perms_edit') && !getRequest('perms_create') &&
			   !getRequest('perms_delete') && !getRequest('permissions-sent')) {
				return;
			}



			$perms_read   = ($t = getRequest('perms_read'))   ? $t : array();
			$perms_edit   = ($t = getRequest('perms_edit'))   ? $t : array();
			$perms_create = ($t = getRequest('perms_create')) ? $t : array();
			$perms_delete = ($t = getRequest('perms_delete')) ? $t : array();
			$perms_move   = ($t = getRequest('perms_move'))	  ? $t : array();

			$permissions->resetElementPermissions($element_id);



			$owners = array_keys($perms_read);
			$owners = array_merge($owners, array_keys($perms_edit));
			$owners = array_merge($owners, array_keys($perms_create));
			$owners = array_merge($owners, array_keys($perms_delete));
			$owners = array_merge($owners, array_keys($perms_move));
			$owners = array_unique($owners);

			foreach($owners as $owner) {
				$level = 0;
				if(isset($perms_read[$owner]))   $level |= 1;
				if(isset($perms_edit[$owner]))   $level |= 2;
				if(isset($perms_create[$owner])) $level |= 4;
				if(isset($perms_delete[$owner])) $level |= 8;
				if(isset($perms_move[$owner]))   $level |= 16;

				if (is_string($owner)) $owner = umiObjectsCollection::getObjectIdByGUID($owner);

				$permissions->setElementPermissions($owner, $element_id, $level);
			}
		}

		public function choose_perms($ownerId = false) {
			$regedit = regedit::getInstance();
			$domainsCollection = domainsCollection::getInstance();
			$permissions = permissionsCollection::getInstance();
			$cmsController = cmsController::getInstance();

			if($ownerId === false) {
				$ownerId = (int) $regedit->getVal("//modules/users/guest_id");
			}

			$restrictedModules = array('autoupdate', 'backup');

			$modules_arr = Array();
			$modules_list = $regedit->getList("//modules");

			foreach($modules_list as $md) {
				list($module_name) = $md;

				if(in_array($module_name, $restrictedModules)) {
					continue;
				}

				$func_list = array_keys($permissions->getStaticPermissions($module_name));
				if(!system_is_allowed($module_name)) continue;

				$module_label = getLabel("module-" . $module_name);
				$is_allowed_module = $permissions->isAllowedModule($ownerId, $module_name);


				$options_arr = Array();
				if(is_array($func_list)) {
					foreach($func_list as $method_name) {

						if(!system_is_allowed($module_name, $method_name)) continue;
						$is_allowed_method = $permissions->isAllowedMethod($ownerId, $module_name, $method_name);

						$option_arr = Array();
						$option_arr['attribute:name'] = $method_name;
						$option_arr['attribute:label'] = getLabel("perms-" . $module_name . "-" . $method_name, $module_name);
						$option_arr['attribute:access'] = (int) $is_allowed_method;
						$options_arr[] = $option_arr;
					}
				}


				$module_arr = Array();
				$module_arr['attribute:name'] = $module_name;
				$module_arr['attribute:label'] = $module_label;
				$module_arr['attribute:access'] = (int) $is_allowed_module;
				$module_arr['nodes:option'] = $options_arr;
				$modules_arr[] = $module_arr;
			}


			$domains_arr = Array();

			$domains = $domainsCollection->getList();
			foreach($domains as $domain) {
				$domain_arr = Array();
				$domain_arr['attribute:id'] = $domain->getId();
				$domain_arr['attribute:host'] = $domain->getHost();
				$domain_arr['attribute:access'] = $permissions->isAllowedDomain($ownerId, $domain->getId());
				$domains_arr[] = $domain_arr;
			}


			$result_arr = Array();
			$result_arr['domains']['nodes:domain'] = $domains_arr;
			$result_arr['nodes:module'] = $modules_arr;
			return $result_arr;
		}


		public function save_perms($owner_id) {
			$owner = $this->getOwnerType($owner_id);
			$guest_id = (int) regedit::getInstance()->getVal("//modules/users/guest_id");
			$def_group_id = (int) regedit::getInstance()->getVal("//modules/users/def_group");

			if(is_array(getRequest('ps_m_perms'))) {
				permissionsCollection::getInstance()->resetModulesPermissions($owner_id, array_keys(getRequest('ps_m_perms')));
			} else {
				permissionsCollection::getInstance()->resetModulesPermissions($owner_id);
			}

			$ownerObj = umiObjectsCollection::getInstance()->getObject($owner_id);
			$groups = $ownerObj->getValue("groups");

			if(is_array($groups)) {
				if(sizeof($groups)) {
					list($nl) = $groups;
				} else {
					$nl = false;
				}
				if(!$nl) {
					$groups = false;
				}
			}

			if(!$groups) {
				$cnt = permissionsCollection::getInstance()->hasUserPermissions($owner_id);

				if(!$cnt) {
					permissionsCollection::getInstance()->copyHierarchyPermissions($guest_id, $owner_id);
				}
			}


			foreach(getRequest('ps_m_perms') as $module => $nl) {
				if(is_array(getRequest('m_perms')) && ($owner_id != $guest_id && $owner_id != $def_group_id)) {
					if(in_array($module, getRequest('m_perms'))) {
						permissionsCollection::getInstance()->setModulesPermissions($owner_id, $module, false, true);
					}
				}

				if(is_array($domains = getRequest('domain'))) {
					foreach($domains as $id => $level) {
						permissionsCollection::getInstance()->setAllowedDomain($owner_id, $id, $level);
					}
				}

				if(!is_array(getRequest($module))) continue;

				foreach(getRequest($module) as $method => $is_allowed) {
					permissionsCollection::getInstance()->setModulesPermissions($owner_id, $module, $method, true);

					$mod_subfuncs = regedit::getInstance()->getList("//modules/{$module}/func_perms/{$method}");

					if(is_array($mod_subfuncs)) {
						foreach($mod_subfuncs as $subfunc) {
							list($sub_method) = $subfunc;

							if(!$sub_method || $sub_method == 'NULL') continue;

							permissionsCollection::getInstance()->setModulesPermissions($owner_id, $module, $sub_method, true);
						}
					}

				}
			}
			permissionsCollection::getInstance()->cleanupBasePermissions();
		}

		public function isAllowedMethod($owner_id, $module, $method) {
			return permissionsCollection::getInstance()->isAllowedMethod($owner_id, $module, $method);
		}

	};

?>