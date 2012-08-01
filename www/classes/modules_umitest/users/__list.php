<?php
	abstract class __list_users {

		public function list_users($template = "default", $per_page = 10) {
			list($template_block, $template_block_item) = $this->loadTemplates("users/list_users/".$template, "block", "block_item");
			$block_arr = Array();

			$curr_page = (int) getRequest('p');

	        $type_id = umiObjectTypesCollection::getInstance()->getBaseType("users", "user");
			$type = umiObjectTypesCollection::getInstance()->getType($type_id);
			$is_active_field_id = $type->getFieldId('is_activated');

        	$sel = new umiSelection;
	        $sel->addLimit($per_page, $curr_page);

            $sel->addObjectType($type_id);

			$sel->addPropertyFilterEqual($is_active_field_id, true);
			$this->autoDetectOrders($sel, $type_id);
			$this->autoDetectFilters($sel, $type_id);

        	$result = umiSelectionsParser::runSelection($sel);
	        $total = umiSelectionsParser::runSelectionCounts($sel);
			
			
			$items = Array();
			
			foreach($result as $user_id) {
				$item_arr = Array();
				$item_arr['void:user_id'] = $user_id;
				$item_arr['attribute:id'] = $user_id;
				$item_arr['xlink:href'] = "uobject://" . $user_id;
				
				$items[] = def_module::parseTemplate($template_block_item, $item_arr, false, $user_id);
			}

			$block_arr['subnodes:items'] = $items;
			$block_arr['per_page'] = $per_page;
			$block_arr['total'] = $total;
			return def_module::parseTemplate($template_block, $block_arr);
		}
		
		
		public function count_users() {
			$typeId = umiObjectTypesCollection::getInstance()->getBaseType("users", "user");
			$type = umiObjectTypesCollection::getInstance()->getType($typeId);
			$isActiveField = $type->getFieldId('is_activated');
			
			$sel = new umiSelection;
			$sel->addObjectType($typeId);
			$sel->addPropertyFilterEqual($isActiveField, true);
			return umiSelectionsParser::runSelectionCounts($sel);$total;
		}
		
		
		public function isUserOnline($userId = false, $onlineTimeout = 900) {
			if($userId === false) {
				throw new publicException("This macros need user id given.");
			}

			if($user = umiObjectsCollection::getInstance()->getObject($userId)) {
				$last_request_time = $user->getValue("last_request_time");
				
				$is_online = (bool) (($last_request_time + $onlineTimeout) >= time());

				$user->setValue("is_online", $is_online);
				$user->commit();

				return $is_online;
			} else {
				throw new publicException("User #{$userId} doesn't exists.");
			}
		}
		
		
		public function recountUserMessages() {
/*			$message_type_id = umiObjectTypesCollection::getInstance()->getBaseType("forum", "message");

		
	                $type_id = umiObjectTypesCollection::getInstance()->getBaseType("users", "user");

        	        $sel = new umiSelection;

        	        $sel->setObjectTypeFilter();
                	$sel->addObjectType($type_id);
			
        	        $result = umiSelectionsParser::runSelection($sel);

			foreach($result as $user_id) {
				$user = umiObjectsCollection::getInstance()->getObject($user_id);
				
				$sel = new umiSelection;
				$sel->setLimitFilter();
				$sel->addLimit(1);

				$sel->setObjectTypeFilter();
				$sel->addObjectType($message_type_id);
				
				$sel->setPermissionsFilter();
				$sel->addPermissions();
				
				$sel->setPropertyFilter();
				$sel->addPropertyFilterEquals($field_id, $user_id);
				
				$total = umiSelectionsParser::runSelectionCounts($sel);
				
				return $total;
				
				$user->commit();
				umiObjectsCollection::getInstance()->unloadObject($user_id);
			}
*/		}

	};
?>