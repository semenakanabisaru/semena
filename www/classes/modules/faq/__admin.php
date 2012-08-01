<?php
	abstract class __faq extends baseModuleAdmin {

		public function config() {
			$regedit = regedit::getInstance();

			$params = Array(
				"config" => Array(
					"int:per_page" => NULL,
					"boolean:confirm_user_answer" => NULL
				)
			);

			$mode = getRequest("param0");

			if($mode == "do") {
				$params = $this->expectParams($params);
				$regedit->setVar("//modules/faq/per_page", $params['config']['int:per_page']);
				$regedit->setVar("//modules/faq/confirm_user_answer", $params['config']['boolean:confirm_user_answer']);
				$this->chooseRedirect();
			}

			$params['config']['int:per_page'] = (int) $regedit->getVal("//modules/faq/per_page");
			$params['config']['boolean:confirm_user_answer'] = (int) $regedit->getVal("//modules/faq/confirm_user_answer");

			$this->setDataType("settings");
			$this->setActionType("modify");

			$data = $this->prepareData($params, "settings");

			$this->setData($data);
			return $this->doData();
		}
		
		public function projects_list() {
			$this->lists();
		}
		
		public function categories_list() {
			$this->lists();
		}
		
		public function questions_list() {
			$this->lists();
		}


		public function lists() {
			$this->setDataType("list");
			$this->setActionType("view");
	
			if($this->ifNotXmlMode()) return $this->doData();
			
			$limit = 20;
			$curr_page = getRequest('p');
			$offset = $curr_page * $limit;

			$sel = new selector('pages');
			$sel->types('hierarchy-type')->name('faq', 'project');
			$sel->types('hierarchy-type')->name('faq', 'category');
			$sel->types('hierarchy-type')->name('faq', 'question');
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
			
			$this->setHeaderLabel("header-faq-add-" . $type);

			$inputData = Array(	"type" => $type,
						"parent" => $parent,
						'type-id' => getRequest('type-id'),
						"allowed-element-types" => Array('project', 'category', 'question')
					);

			if($mode == "do") {
				$element_id = $this->saveAddedElementData($inputData);
				$this->chooseRedirect("{$this->pre_lang}/admin/faq/edit/{$element_id}/");
			}

			$this->setDataType("form");
			$this->setActionType("create");

			$data = $this->prepareData($inputData, "page");

			$this->setData($data);
			return $this->doData();
			
		}


		public function edit() {
			$element = $this->expectElement('param0');
			$mode = (string) getRequest('param1');
			
			$this->setHeaderLabel("header-faq-edit-" . $this->getObjectTypeMethod($element->getObject()));
			
			$inputData = Array(	'element' => $element,
						'allowed-element-types' => Array('project', 'category', 'question')
					);

			if($mode == "do") {
				$this->saveEditedElementData($inputData);
				$this->chooseRedirect();
			}

			$this->setDataType("form");
			$this->setActionType("modify");

			$data = $this->prepareData($inputData, 'page');

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
					"allowed-element-types" => Array('project', 'category', 'question')
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
					"allowed-element-types" => Array('project', 'category', 'question'),
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
						array('title'=>getLabel('smc-load'), 'forload'=>true, 			 'module'=>'faq', '#__name'=>'projects_list'),
						array('title'=>getLabel('smc-delete'), 					     'module'=>'faq', 'aliases'=>'tree_delete_element', '#__name'=>'del'),
						array('title'=>getLabel('smc-activity'), 		 'module'=>'faq', 'aliases' => 'tree_set_activity', '#__name'=>'activity'),
						array('title'=>getLabel('smc-copy'), 'module'=>'content', '#__name'=>'tree_copy_element'),
						array('title'=>getLabel('smc-move'), 					 'module'=>'content', '#__name'=>'tree_move_element'),
						array('title'=>getLabel('smc-change-template'), 						 'module'=>'content', '#__name'=>'change_template'),
						array('title'=>getLabel('smc-change-lang'), 					 'module'=>'content', '#__name'=>'move_to_lang'),
						array('title'=>getLabel('smc-change-lang'), 					 'module'=>'content', '#__name'=>'copy_to_lang_old'),
						),
					'types' => array(
						array('common' => 'true', 'id' => 'question')
					),
					'stoplist' => array('title', 'h1', 'meta_keywords', 'meta_descriptions', 'menu_pic_ua', 'menu_pic_a', 'header_pic', 'more_params', 'robots_deny', 'is_unindexed', 'store_amounts', 'locktime', 'lockuser', 'anons', 'content', 'answer', 'rate_voters', 'rate_sum'),
					'default' => ''
				);
		}
	};
?>