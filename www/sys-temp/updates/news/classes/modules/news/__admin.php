<?php
	abstract class __news extends baseModuleAdmin {

		public function config() {
			$regedit = regedit::getInstance();
			$params = array('config' => array('int:per_page' => NULL, 'int:rss_per_page' => NULL));
			$mode = getRequest("param0");

			if($mode == "do") {
				$params = $this->expectParams($params);
				$regedit->setVar("//modules/news/per_page", (int) $params['config']['int:per_page']);
				$regedit->setVar("//modules/news/rss_per_page", (int) $params['config']['int:rss_per_page']);
				$this->chooseRedirect();
			}

			$params['config']['int:per_page'] = (int) $regedit->getVal("//modules/news/per_page");
			$params['config']['int:rss_per_page'] = (int) $regedit->getVal("//modules/news/rss_per_page");
			$params['config']['int:rss_per_page'] = $params['config']['int:rss_per_page'] > 0 ? $params['config']['int:rss_per_page'] : 10;

			$this->setDataType("settings");
			$this->setActionType("modify");

			$data = $this->prepareData($params, "settings");

			$this->setData($data);
			return $this->doData();
		}


		public function lists() {
			$this->setDataType("list");
			$this->setActionType("view");
	
			if($this->ifNotXmlMode()) return $this->doData();

			$limit = 20;
			$curr_page = getRequest('p');
			$offset = $curr_page * $limit;

			$sel = new selector('pages');
			$sel->types('hierarchy-type')->name('news', 'rubric');
			$sel->types('hierarchy-type')->name('news', 'item');
			$sel->limit($offset, $limit);
			
			selectorHelper::detectFilters($sel);

			$data = $this->prepareData($sel->result, "pages");

			$this->setData($data, $sel->length);
			$this->setDataRangeByPerPage($limit, $curr_page);
			return $this->doData();
		}

		public function add() {
			$parent = $this->expectElement("param0");
			$type = (string) getRequest("param1");
			$mode = (string) getRequest("param2");
			
			$this->setHeaderLabel("header-news-add-" . $type);

			$inputData = Array(	"type" => $type,
						"parent" => $parent,
						'type-id' => getRequest('type-id'),						
						"allowed-element-types" => Array('rubric', 'item'));

			if($mode == "do") {
				$element_id = $this->saveAddedElementData($inputData);
				if($type == "item") {
					umiHierarchy::getInstance()->moveFirst($element_id, ($parent instanceof umiHierarchyElement)?$parent->getId():0);
				}
				$this->chooseRedirect();
			}

			$this->setDataType("form");
			$this->setActionType("create");

			$data = $this->prepareData($inputData, "page");

			$this->setData($data);
			return $this->doData();
		}
		

		public function edit() {
			$element = $this->expectElement('param0', true);
			$mode = (string) getRequest('param1');
			
			$this->setHeaderLabel("header-news-edit-" . $this->getObjectTypeMethod($element->getObject()));
			
			$inputData = array(
				'element'				=> $element,
				'allowed-element-types'	=> array('rubric', 'item')
			);

			if($mode == "do") {
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
				$elements = array($elements);
			}

			foreach($elements as $elementId) {
				$element = $this->expectElement($elementId, false, true);
				
				$params = array(
					"element" => $element,
					"allowed-element-types" => Array('rubric', 'item')
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
				$elements = array($elements);
			}
			$is_active = getRequest('active');
		
			foreach($elements as $elementId) {
				$element = $this->expectElement($elementId, false, true);
				
				$params = array(
					"element" => $element,
					"allowed-element-types" => Array('rubric', 'item'),
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


		public function getDatasetConfiguration($param = '') {
			return array(
					'methods' => array(
						array('title'=>getLabel('smc-load'), 'forload'=>true, 			 'module'=>'news', '#__name'=>'lists'),
						array('title'=>getLabel('smc-delete'), 					     'module'=>'news', '#__name'=>'del', 'aliases' => 'tree_delete_element,delete,del'),
						array('title'=>getLabel('smc-activity'), 		 'module'=>'news', '#__name'=>'activity', 'aliases' => 'tree_set_activity,activity'),
						array('title'=>getLabel('smc-copy'), 'module'=>'content', '#__name'=>'tree_copy_element'),
						array('title'=>getLabel('smc-move'), 					 'module'=>'content', '#__name'=>'tree_move_element'),
						array('title'=>getLabel('smc-change-template'), 						 'module'=>'content', '#__name'=>'change_template'),
						array('title'=>getLabel('smc-change-lang'), 					 'module'=>'content', '#__name'=>'move_to_lang'),
						array('title'=>getLabel('smc-change-lang'), 					 'module'=>'content', '#__name'=>'copy_to_lang_old'),
						),
					'types' => array(
						array('common' => 'true', 'id' => 'item')
					),
					'stoplist' => array('title', 'h1', 'meta_keywords', 'meta_descriptions', 'menu_pic_ua', 'menu_pic_a', 'header_pic', 'more_params', 'robots_deny', 'is_unindexed', 'store_amounts', 'locktime', 'lockuser', 'anons', 'content', 'rate_voters', 'rate_sum', 'begin_time', 'end_time'),
					'default' => 'publish_time[140px]'
				);
		}
	};
?>