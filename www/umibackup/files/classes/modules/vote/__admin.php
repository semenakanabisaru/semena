<?php
	abstract class __vote extends baseModuleAdmin {

		public function polls() {
			//Deprecated method
			regedit::getInstance()->setVar("//modules/vote/default_method_admin", "lists");
			$this->redirect($this->pre_lang . "/admin/vote/lists/");
		}


		public function lists() {
			$this->setDataType("list");
			$this->setActionType("view");
			if($this->ifNotXmlMode()) return $this->doData();

			$limit = 20;
			$curr_page = (int) getRequest('p');
			$offset = $limit * $curr_page;

			$sel = new selector('pages');
			$sel->types('hierarchy-type')->name('vote', 'poll');
			$sel->limit($offset, $limit);
			selectorHelper::detectFilters($sel);

			$this->setDataRange($limit, $offset);
			$data = $this->prepareData($sel->result, "pages");
			$this->setData($data, $sel->length);
			return $this->doData();
		}

		public function add() {            
			$parent = (int)    getRequest("param0");
			$mode   = (string) getRequest("param1");

			//Подготавливаем список параметров
			$inputData = Array("type"   => 'poll',
							   "parent"  => null,
							   'type-id' => getRequest('type-id'), 
							   "allowed-element-types" => Array('poll'));

			if($mode == "do") {
				$this->saveAddedElementData($inputData);
				$this->chooseRedirect();
			}

			$this->setDataType("form");
			$this->setActionType("create");

			$data = $this->prepareData($inputData, "page");

			$this->setData($data);
			return $this->doData();
		}

		public function edit() {
			$element = $this->expectElement("param0");
			$mode = (string) getRequest('param1');

			$inputData = Array("element" => $element,                               
							   "allowed-element-types" => Array('poll'));

			if($mode == "do") {
				if(isset($_REQUEST['data']['new'])) {
					unset($_REQUEST['data']['new']);
				}
				$this->saveEditedElementData($inputData);
				$this->chooseRedirect();
			}

			$this->setDataType("form");
			$this->setActionType("modify");

			$data = $this->prepareData($inputData, "page");

			$this->setData($data);
			return $this->doData();
		}

		public function del() {
			$elements = getRequest('element');
			if(!is_array($elements)) {
				$elements = Array($elements);
			}
			
			foreach($elements as $elementId) {
				$element = $this->expectElement($elementId, false, true);
				$params = Array(
					"element" => $element,
					"allowed-element-types" => Array('poll')
				);
				
				$this->deleteElement($params);
			}
			
			$this->setDataType("list");
			$this->setActionType("view");
			$data = $this->prepareData($elements, "pages");
			$this->setData($data);

			return $this->doData();
		}


		public function activity() {
			$elements = getRequest('element');
			if(!is_array($elements)) {
				$elements = Array($elements);
			}
			$is_active = getRequest('active');

			foreach($elements as $elementId) {
				$element = $this->expectElement($elementId, false, true);
				
				$params = Array(
					"element" => $element,
					"allowed-element-types" => Array('poll'),
					"activity" => $is_active
				);
	
				$this->switchActivity($params);
				$element->commit();
			}
			
			$this->setDataType("list");
			$this->setActionType("view");
			$data = $this->prepareData($elements, "pages");
			$this->setData($data);

			return $this->doData();
		}
		
		public function answers_list() { 
			$element = $this->expectElement('param0');            
			$mode    = (string) getRequest('param1'); 
			if(!($element instanceof umiHierarchyElement)) {
				$sError = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n".
						  "<error>".getLabel('error_save_page_first')."</error>";
				die($sError);
			}
			$aAIDs   = $element->getValue('answers');
			$object  = $element->getObject()->getId();
			$type_id = umiObjectTypesCollection::getInstance()->getBaseType("vote", "poll_item");
			if($mode == "do") {
				$params = array("type_id" => $type_id);
				$iLastInsertID = $this->saveEditedList("objects", $params); 
				if($iLastInsertID !== false) {
					$aAIDs[] = $iLastInsertID;
					$element->setValue('answers',$aAIDs);
				}
				$dels = getRequest("dels");
				if(is_array($dels)) {
					foreach($dels as $id) {
						$key = array_search($id, $aAIDs);
						unset($aAIDs[$key]);
					}
					$aAIDs = array_values($aAIDs);
					$element->setValue('answers',$aAIDs);
				}
				$element->commit();				
				//$this->chooseRedirect();
			}            
			$this->setDataType("list");            
			$this->setActionType("modify"); 
			$data = $this->prepareData($aAIDs, "objects");
			$data['attribute:object_id'] = $object;
			$this->setData($data);
			return $this->doData();            
		}

		public function getEditLink($element_id, $element_type) {
			$element = umiHierarchy::getInstance()->getElement($element_id);
			$parent_id = $element->getParentId();

			switch($element_type) {
				case "poll": {
					$link_edit = $this->pre_lang . "/admin/vote/edit/{$element_id}/";

					return Array(false, $link_edit);
					break;
				}

				default: {
					return false;
				}
			}
		}

		public function resetUserRatedPages($user_id = null) {
			if (is_null($user_id)) $user_id = getRequest("param0");
			$user = umiObjectsCollection::getInstance()->getObject($user_id);
			if ($user instanceof umiObject) {
				$user->setValue("rated_pages", array());
				$user->commit();
			}
			$this->chooseRedirect();
		}

		public function config() {
			$regedit = regedit::getInstance();
			$params = Array ( 
				"config" => Array (	"bool:is_private" => false,
									"bool:is_graded"  => false	
								)
			);
			$mode = getRequest("param0");
			if ($mode == "do"){			
				$params = $this->expectParams($params);
				$regedit->setVar("//modules/vote/is_private", (int) $params["config"]["bool:is_private"]);
				$regedit->setVar("//modules/vote/is_graded", (int) $params["config"]["bool:is_graded"]);
				$this->chooseRedirect();	
			} 
			$params["config"]["bool:is_private"] = (bool) $regedit->getVal("//modules/vote/is_private");
			$params["config"]["bool:is_graded"] = (bool) $regedit->getVal("//modules/vote/is_graded");
			
			$this->setDataType("settings");
			$this->setActionType("modify");
			
			$data = $this->prepareData($params, "settings");
			$this->setData($data);
			return $this->doData();
		}
		
		public function getDatasetConfiguration($param = '') {
			return array(
					'methods' => array(
						array('title'=>getLabel('smc-load'), 'forload'=>true, 			 'module'=>'vote', '#__name'=>'lists'),
						array('title'=>getLabel('smc-delete'), 					     'module'=>'vote', '#__name'=>'del', 'aliases' => 'tree_delete_element,delete,del'),
						array('title'=>getLabel('smc-activity'), 		 'module'=>'vote', '#__name'=>'activity', 'aliases' => 'tree_set_activity,activity'),
						array('title'=>getLabel('smc-copy'), 'module'=>'content', '#__name'=>'tree_copy_element'),
						array('title'=>getLabel('smc-move'), 					 'module'=>'content', '#__name'=>'tree_move_element'),
						array('title'=>getLabel('smc-change-template'), 						 'module'=>'content', '#__name'=>'change_template'),
						array('title'=>getLabel('smc-change-lang'), 					 'module'=>'content', '#__name'=>'move_to_lang')),
					'types' => array(
						array('common' => 'true', 'id' => 'poll')
					),
					'stoplist' => array('title', 'h1', 'meta_keywords', 'meta_descriptions', 'menu_pic_ua', 'menu_pic_a', 'header_pic', 'more_params', 'robots_deny', 'is_unindexed', 'store_amounts', 'locktime', 'lockuser', 'rate_voters', 'rate_sum', 'total_count'),
					'default' => 'question[170px]'
				);
		}
	};
?>
