<?php
	abstract class __profile_users {
		public function profile($template = "default", $user_id = false) {
			if(!$template) $template = "default";

			list($template_block, $template_bad_user_block) = def_module::loadTemplates("users/profile/".$template, "profile_block", "bad_user_block");
			$block_arr = Array();

			if(!$user_id) {
				$user_id = (int) getRequest('param0');
			}
			
			if(!$user_id) {
				$permissions = permissionsCollection::getInstance();
				if($permissions->isAuth()) {
					$user_id = $permissions->getUserId();
				}
			}
			
			if($user = selector::get('object')->id($user_id)) {
				$this->validateEntityByTypes($user, array('module' => 'users', 'method' => 'user'));
			
				$block_arr['xlink:href'] = "uobject://" . $user_id;

				$userTypeId = $user->getTypeId();

				if($userType = umiObjectTypesCollection::getInstance()->getType($userTypeId)) {
					$userHierarchyTypeId = $userType->getHierarchyTypeId();

					if($userHierarchyType = umiHierarchyTypesCollection::getInstance()->getType($userHierarchyTypeId)) {

						if($userHierarchyType->getName() == "users" && $userHierarchyType->getExt() == "user") {
							$block_arr['id'] = $user_id;

							return def_module::parseTemplate($template_block, $block_arr, false, $user_id);
						}
					}
				}
			} else {
				throw new publicException(getLabel('error-object-does-not-exist', null, $user_id));
			}
			
			return def_module::parseTemplate($template_bad_user_block, $block_arr);
		}
		
		
		public function onSubscribeChanges(umiEventPoint $e) {
			static $is_called;
			
			if($is_called === true) {
				return true;
			}
			
			$mode = (bool) getRequest('subscribe_changes');

			$users_module = cmsController::getInstance()->getModule("users");
			
			if($user_id = $users_module->user_id) {
				$user = umiObjectsCollection::getInstance()->getObject($user_id);
				if($user instanceof umiObject) {
					$topic_id = $e->getParam("topic_id");
					$subscribed_pages = $user->getValue("subscribed_pages");
					
					if($mode) {
						$topic = umiHierarchy::getInstance()->getElement($topic_id);
						if($topic instanceof umiHierarchyElement) {
							if(!in_array($topic, $subscribed_pages)) {
								$subscribed_pages[] = $topic_id;
							}
						}
					} else {
						$tmp = Array();
						
						if(!is_array($subscribed_pages)) {
							$subscribed_pages = Array();
						}
						
						foreach($subscribed_pages as $page) {
							if($page->getId() != $topic_id) {
								$tmp[] = $page;
							}
						}
						$subscribed_pages = $tmp;
						unset($tmp);
					}

					$user->setValue("subscribed_pages", $subscribed_pages);
					$user->commit();

					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
		}
	};
?>