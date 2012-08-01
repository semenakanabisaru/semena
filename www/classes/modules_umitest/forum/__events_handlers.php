<?php
	abstract class __events_handlers_forum {

		// COMMON MODES (FRONT-END AND ADMIN) EVENT HANDLERS

		public function onElementActivity(iUmiEventPoint $oEventPoint) {
			if ($oEventPoint->getMode() === 'after') {
				if ($oEventPoint->getMode() === 'after') {
					$o_element = $oEventPoint->getRef('element');
					if ($o_element instanceof umiHierarchyElement) {
						$i_element_type = intval($o_element->getTypeId());
						if ($i_element_type && ($i_element_type === $this->getHTypeByName('topic') || $i_element_type === $this->getHTypeByName('message'))) {
							$this->recalcCounts($o_element);
						}
					}
				}
			}
		}

		public function onElementRemove(iUmiEventPoint $oEventPoint) {
			if ($oEventPoint->getMode() === 'after') {
				$o_element = $oEventPoint->getRef('element');
				if ($o_element instanceof umiHierarchyElement) {
					$i_element_type = intval($o_element->getTypeId());
					if ($i_element_type && ($i_element_type === $this->getHTypeByName('topic') || $i_element_type === $this->getHTypeByName('message'))) {
						$this->recalcCounts($o_element);
					}
				}
			}
		}


		public function onElementAppend(iUmiEventPoint $oEventPoint) {
			if ($oEventPoint->getMode() === 'after') {
				$o_element = $oEventPoint->getRef('element');
				if ($o_element instanceof umiHierarchyElement) {
					$i_element_type = intval($o_element->getTypeId());
					if ($i_element_type && ($i_element_type === $this->getHTypeByName('topic') || $i_element_type === $this->getHTypeByName('message'))) {
						// increase
						$this->recalcCounts($o_element);
						// publish_time
						$publish_time = new umiDate(time());
						$o_element->setValue("publish_time", $publish_time);
						$o_element->commit();
					}
				}
			}
		}

		public function recalcCounts(iUmiHierarchyElement $element) {
               
			switch($element->getMethod()) {
				case 'topic':
					$element->messages_count = $this->calculateCount($element, 'message');
					
					$element->last_message = $this->calculateLastMessageId($element);
					$element->commit();
					break;
			} 

			$element = selector::get('page')->id($element->getRel());
			if(!$element) return false;
			
			if(!defined('DISABLE_SEARCH_REINDEX')) {
				define('DISABLE_SEARCH_REINDEX', '1');
			}
			
			switch($element->getMethod()) {
				case 'conf':
					$element->messages_count = $this->calculateCount($element, 'message');
					$element->topics_count = $this->calculateCount($element, 'topic');
					$element->last_message = $this->calculateLastMessageId($element);
					$element->commit();
					break;

				case 'topic':
					$element->messages_count = $this->calculateCount($element, 'message');
					$element->last_message = $this->calculateLastMessageId($element);
					$element->commit();
					$this->recalcCounts($element);
					break;
			} 
		}
		
		public function calculateCount(iUmiHierarchyElement $element, $typeName) {
			$level = ($typeName == 'message' && $element->getMethod() == 'conf') ? 2 : 1;
		
			$sel = new selector('pages');
			$sel->types('hierarchy-type')->name('forum', $typeName);
			$sel->where('hierarchy')->page($element->id)->childs($level);
			$sel->where("is_active")->equals(1);
			return $sel->length;
		}
		
		public function calculateLastMessageId(iUmiHierarchyElement $element) {
			$sel = new selector('pages');
			$sel->types('hierarchy-type')->name('forum', 'message');
			$sel->order('publish_time')->desc();
			$sel->limit(0, 1);
			
			if($element->getMethod() == 'conf') {
				$lastTopics = new selector('pages');
				$lastTopics->types('hierarchy-type')->name('forum', 'topic');
				$lastTopics->where('hierarchy')->page($element->id)->childs(1);
				$lastTopics->order('last_post_time')->desc();
				$lastTopics->limit(0, 1);
				
				if($lastTopics->first) {
					$sel->where('hierarchy')->page($lastTopics->first->id)->childs(1);
				} else {
					return null;
				}
			} else {
				$sel->where('hierarchy')->page($element->id)->childs(1);
			}
			
			return $sel->first;
		}
	}
?>