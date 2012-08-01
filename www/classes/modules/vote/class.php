<?php

class vote extends def_module {
	public function __construct() {
				parent::__construct();

		if(cmsController::getInstance()->getCurrentMode() == "admin") {
			$this->__loadLib("__admin.php");
			$this->__implement("__vote");
		} else {
			$this->__loadLib("__rate.php");
			$this->__implement("__rate_vote");

			$this->__loadLib("__custom.php");
			$this->__implement("__custom_vote");
		}
		
		$this->__loadLib("__events_handlers.php");
		$this->__implement("__eventsHandlers");

		$this->is_private = intval(regedit::getInstance()->getVal("//modules/vote/is_private"));
	}


	public function poll($path = "", $template = "default") {
		$element_id = $this->analyzeRequiredPath($path);

		$element = umiHierarchy::getInstance()->getElement($element_id);

		if(!$element) return "";

		if($this->checkIsVoted($element->getObject()->getId())||$element->getValue('is_closed')) {
			return $this->results($element_id, $template);
		} else {
			return $this->insertvote($element_id, $template);
		}
	}


	public function insertvote($path = "", $template = "default") {
		list($template_block, $template_line, $template_submit) = def_module::loadTemplates("vote/".$template, "vote_block", "vote_block_line", "vote_block_submit");
		$hierarchy = umiHierarchy::getInstance();
		$objects = umiObjectsCollection::getInstance();

		$elementId = $this->analyzeRequiredPath($path);

		$element = $hierarchy->getElement($elementId);

		if(!$element) {
			throw new publicException(getLabel('error-page-does-not-exist', null, $path));
		}

		$block_arr = array(
			'id'	=> $elementId,
			'text'	=> $element->question
		);
		
		$result = $element->answers;
		if(!is_array($result)) $result = array();
		
		$lines = array();
		foreach($result as $item_id) {
			$item = $objects->getObject($item_id);
			
			$line_arr = array();
			$line_arr['attribute:id'] = $line_arr['void:item_id'] = $item_id;
			$line_arr['node:item_name'] = $item->name;

			$lines[] = self::parseTemplate($template_line, $line_arr, false, $item_id);
		}


		$is_closed = (bool) $element->getValue("is_closed");

		$block_arr['submit'] = ($is_closed) ? "" : $template_submit;
		$block_arr['subnodes:items'] = $block_arr['void:lines'] = $lines;
		$block_arr['link'] = $element->link;
		return self::parseTemplate($template_block, $block_arr, $elementId);
	}


	public function results($path, $template = "default") {
		if(!$template) $template = "default";
		list($template_block, $template_line) = def_module::loadTemplates("vote/".$template, "result_block", "result_block_line");

		$element_id = $this->analyzeRequiredPath($path);

		$element = umiHierarchy::getInstance()->getElement($element_id);
		if(!$element) return false;

		$block_arr = Array();
		
		$block_arr['id']          = $element_id;
		$block_arr['text']        = $element->getValue("question");
		$block_arr['vote_header'] = $element->getValue("h1");
		$block_arr['alt_name']    = $element->getAltName();
			$result                   = $element->getValue('answers');
		/*
		$item_type_id = umiObjectTypesCollection::getInstance()->getBaseType("vote", "poll_item");
		$item_type = umiObjectTypesCollection::getInstance()->getType($item_type_id);
		$rel_field_id = $item_type->getFieldId("poll_rel");

		$hierarchy_type_id = umiHierarchyTypesCollection::getInstance()->getTypeByName("vote", "poll_item")->getId();
		$sel = new umiSelection();
		
		$sel->setOrderFilter();
		$sel->setOrderByObjectId();

		$sel->setObjectTypeFilter();
		$sel->addObjectType($item_type_id);

		$sel->setPropertyFilter();
		$sel->addPropertyFilterEqual($rel_field_id, $element->getObject()->getId());

		$count_field_id = $item_type->getFieldId("count");

		$sel->setOrderByProperty($count_field_id);

		$result = umiSelectionsParser::runSelection($sel);
		*/
		$items = Array();
		$total = 0;
		foreach($result as $item_id) {
			$item = umiObjectsCollection::getInstance()->getObject($item_id);
			$total += (int) $item->getPropByName("count")->getValue();
			$items[] = $item;
		}

		$lines = Array();
		foreach($items as $item) {
			$line_arr = Array();

			$line_arr['node:item_name'] = $item->getName();
			$line_arr['attribute:score'] = $line_arr['void:item_result'] = $c = (int) $item->getValue("count");

			$curr_procs = ($total > 0) ? round((100 * $c) / $total) : 0;
			$line_arr['attribute:score-rel'] = $line_arr['void:item_result_proc'] = $curr_procs;
			$line_arr['void:item_result_proc_reverce'] = 100 - $curr_procs;

			$lines[] = self::parseTemplate($template_line, $line_arr, false, $item->getId());
		}
		

		$block_arr['subnodes:items'] = $block_arr['void:lines'] = $lines;
		$block_arr['total_posts'] = $total;
		$block_arr['link'] = umiHierarchy::getInstance()->getPathById($element_id);
		return self::parseTemplate($template_block, $block_arr, $element_id);
	}


	public function post($template = "default") {
		if(!$template) $template = getRequest('template');
		if(!$template) $template = "default";

		list($template_block, $template_block_voted, $template_block_closed, $template_block_ok) = def_module::loadTemplates("vote/".$template, "js_block", "js_block_voted", "js_block_closed", "js_block_ok");
		
		$item_id = (int) getRequest('param0');
		$item = umiObjectsCollection::getInstance()->getObject($item_id);
		
		if (!$item instanceof umiObject) {
			throw new publicException(getLabel('error-page-does-not-exist', null, ''));
		}

		$poll_rel = $item->getPropByName("poll_rel")->getValue();

		$object_id = $poll_rel;
		$object = umiObjectsCollection::getInstance()->getObject($object_id);
		if($this->checkIsVoted($object_id)) {
			$res = ($template_block_voted) ? $template_block_voted : "Вы уже проголосовали";
		} else {

			if($object->getValue("is_closed")) {
				$res = ($template_block_closed) ? $template_block_closed : "Ошибка. Голосование не активно, либо закрыто.";
			} else {

				$count = $item->getValue("count");
				$item->setValue("count", ++$count);
				$item->setValue("poll_rel", $poll_rel);
				$item->commit();

				if ($this->is_private) {
					$oUsersMdl = cmsController::getInstance()->getModule("users");
					if ($oUsersMdl) {
						$oUser = umiObjectsCollection::getInstance()->getObject($oUsersMdl->user_id);
					if ($oUser instanceof umiObject) {
						$arrRatedPages = $oUser->getValue("rated_pages");
						$arrRatedPagesIds = array();
						foreach ($arrRatedPages as $vVal) {
							if ($vVal instanceof umiHierarchyElement) {
								$arrRatedPagesIds[] = intval($vVal->getId());
							} else {
								$arrRatedPagesIds[] = intval($vVal);
							}
						}

						$arrVotePages = umiHierarchy::getInstance()->getObjectInstances($object_id);
						$arrVotePages = array_map("intval", $arrVotePages);
						$arrRated = array_merge($arrRatedPagesIds, $arrVotePages);
						$oUser->setValue("rated_pages", array_unique($arrRated));
						$oUser->commit();
					}
			}
		}

				$res = ($template_block_ok) ? $template_block_ok : "Ваше мнение учтено";
			}

			if(!isset($_SESSION['vote_polled']) || !is_array($_SESSION['vote_polled'])) {
				$_SESSION['vote_polled'] = Array();
			}
		}
		$_SESSION['vote_polled'][] = $object_id;

		$res = def_module::parseTPLMacroses($res);

		if($template_block) {
			$block_arr = Array();
			$block_arr['res'] = $res;
			$r = $this->parseTemplate($template_block, $block_arr);
			$this->flush($r, "text/javascript");
		} else {
			$this->flush("alert('{$res}');", "text/javascript");
		}
	}


	private function checkIsVoted($object_id) {
		$vote_polled = getSession('vote_polled');
		if ($this->is_private) {
			$oUsersMdl = cmsController::getInstance()->getModule("users");
			if ($oUsersMdl) {
				$oUser = umiObjectsCollection::getInstance()->getObject($oUsersMdl->user_id);
				if ($oUser instanceof umiObject) {
					$arrRatedPages = $oUser->getValue("rated_pages");
					$arrRatedPagesIds = array();
					foreach ($arrRatedPages as $vVal) {
						if ($vVal instanceof umiHierarchyElement) {
							$arrRatedPagesIds[] = intval($vVal->getId());
						} else {
							$arrRatedPagesIds[] = intval($vVal);
						}
					}

					$arrVotePages = umiHierarchy::getInstance()->getObjectInstances($object_id);
					
					$rpages = array();
					foreach ($arrRatedPages as $page) {
						if ($page instanceof umiHierarchyElement) {
							$rpages[] = $page->id;
						}
					}
					
					$arrRatedPages = array_map("intval", $rpages);
					$arrVotePages = array_map("intval", $arrVotePages);
					
					$arrVoted = array_intersect($arrVotePages, $arrRatedPagesIds);

					return (bool) count($arrVoted);
				}
			}
		}

		if(is_array($vote_polled)) {
			return in_array($object_id, $vote_polled);
		} else {
			return false;
		}
	}



	public function insertlast($template = "default") {
		if(!$template) $template = "default";

		$type_id = umiObjectTypesCollection::getInstance()->getBaseType("vote", "poll");
		$type = umiObjectTypesCollection::getInstance()->getType($type_id);
		$time_field_id = $type->getFieldId("publish_time");

		$hierarchy_type_id = umiHierarchyTypesCollection::getInstance()->getTypeByName("vote", "poll")->getId();

		$sel = new umiSelection();
		$sel->setHierarchyFilter();
		$sel->addElementType($hierarchy_type_id);

		$sel->setLimitFilter();
		$sel->addLimit(1);

		$sel->setOrderFilter();
		$sel->setOrderByProperty($time_field_id, false);
		
		$sel->addPermissions();

		$sel->forceHierarchyTable();

		$result = umiSelectionsParser::runSelection($sel);

		if(sizeof($result)) {
			list($element_id) = $result;
		} else {
			$element_id = false;
		}

		if($element_id) {
			return $this->poll($element_id, $template);
		}
	}

	public function config() {
		return __vote::config();
	}

};
?>
