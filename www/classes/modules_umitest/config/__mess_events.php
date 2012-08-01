<?php
	abstract class __events_config {
		
		public function systemEventsNotify(iUmiEventPoint $event) {
			$eventId = $event->getEventId();
			
			$titleLabel = $titleLabel = 'event-' . $eventId . '-title';
			$contentLabel = 'event-' . $eventId . '-content';
			
			$title = getLabel($titleLabel, 'common/content/config');
			$content = getLabel($contentLabel, 'common/content/config');
			
			if($titleLabel == $title) {
				return;
			}
			
			if($element = $event->getRef('element')) {
				$hierarchy = umiHierarchy::getInstance();
				$oldbForce = $hierarchy->forceAbsolutePath(true);
				
				$params = array(
					'%page-name%' => $element->name,
					'%page-link%' => $element->link
				);
				
				$hierarchy->forceAbsolutePath($oldbForce);
			} else $params = array();
			
			if($object = $event->getRef('object')) {
				$params['%object-name%'] = $object->name;
				
				$objectTypes = umiObjectTypesCollection::getInstance();
				$objectType = $objectTypes->getType($object->getTypeId());
				if($hierarchyTypeId = $objectType->getHierarchyTypeId()) {
					$hierarchyTypes = umiHierarchyTypesCollection::getInstance();
					$hierarchyType = $hierarchyTypes->getType($hierarchyTypeId);
					
					$params['%object-type%'] = $hierarchyType->getTitle();
				}
			}
			
			$title = str_replace(array_keys($params), array_values($params), $title);
			$content = str_replace(array_keys($params), array_values($params), $content);
			
			$this->dispatchSystemEvent($title, $content);
		}
		
		public function dispatchSystemEvent($title, $content) {
			$recipients = $this->getSystemEventRecipients();
			
			if(sizeof($recipients)) {
				$messages = umiMessages::getInstance();
				$message = $messages->create();
				$message->setTitle($title);
				$message->setContent($content);
				$message->setType("sys-log");
				$message->commit();
				
				$message->send($recipients);
			}
		}
		
		public function getSystemEventRecipients() {
			$permissions = permissionsCollection::getInstance();
			$currentUserId = $permissions->getUserId();
			
			$sel = new selector('objects');
			$sel->types('object-type')->name('users', 'user');
			$sel->where('groups')->equals(SV_GROUP_ID);
			
			$result = array(SV_GROUP_ID);
			foreach($sel as $user) {
				if($user->id != $currentUserId) {
					$result[] = $user->id;
				}
			}
			return $result;
		}
	};
?>