<?php
	abstract class __catalog extends baseModuleAdmin {
		public function config() {
			$regedit = regedit::getInstance();

			$params = array(
				"config" => array(
					"int:per_page" => NULL
				)
			);

			$mode = getRequest("param0");

			if($mode == "do") {
				$params = $this->expectParams($params);
				$regedit->setVar("//modules/catalog/per_page", $params['config']['int:per_page']);
				$this->chooseRedirect();
			}

			$params['config']['int:per_page'] = (int) $regedit->getVal("//modules/catalog/per_page");

			$this->setDataType("settings");
			$this->setActionType("modify");

			$data = $this->prepareData($params, "settings");

			$this->setData($data);
			return $this->doData();
		}

		public function tree() {
			$this->setDataType("list");
			$this->setActionType("view");
			if($this->ifNotXmlMode()) return $this->doData();

			$limit = 20;
			$curr_page = getRequest('p');
			$offset = $curr_page * $limit;
			
			$sel = new selector('pages');
			$sel->types('hierarchy-type')->name('catalog', 'category');
			$sel->types('hierarchy-type')->name('catalog', 'object');
			$sel->limit($offset, $limit);

			selectorHelper::detectFilters($sel);

			$data = $this->prepareData($sel->result, "pages");

			//Завершаем вывод
			$this->setData($data, $sel->length);
			$this->setDataRangeByPerPage($limit, $curr_page);
			return $this->doData();
		}

		public function add() {
			$parent = $this->expectElement("param0");
			$type = (string) getRequest("param1");
			$mode = (string) getRequest("param2");
			
			$this->setHeaderLabel("header-catalog-add-" . $type);

			$inputData = Array(	"type" => $type,
						"parent" => $parent,
						'type-id' => getRequest('type-id'),
						"allowed-element-types" => Array('category', 'object')
					);

			if($mode == "do") {
				$element_id = $this->saveAddedElementData($inputData);
				$this->chooseRedirect("{$this->pre_lang}/admin/catalog/edit/{$element_id}/");
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
			
			$this->setHeaderLabel("header-catalog-edit-" . $this->getObjectTypeMethod($element->getObject()));
			
			$inputData = Array(	'element' => $element,
						'allowed-element-types' => Array('category', 'object')
					);

			if($mode == "do") {
				$element = $this->saveEditedElementData($inputData);
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
					"allowed-element-types" => Array('category', 'object')
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
					"allowed-element-types" => Array('category', 'object'),
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
						array('title'=>getLabel('smc-load'), 'forload'=>true, 			 'module'=>'catalog', '#__name'=>'tree'),
						array('title'=>getLabel('smc-delete'), 					     'module'=>'catalog', '#__name'=>'del', 'aliases' => 'tree_delete_element,delete,del'),
						array('title' => 'Export csv', '#__name' => 'tree', 'aliases' => 'export'),
						array('title'=>getLabel('smc-activity'), 		 'module'=>'catalog', '#__name'=>'activity', 'aliases' => 'tree_set_activity,activity'),
						array('title'=>getLabel('smc-copy'), 'module'=>'content', '#__name'=>'tree_copy_element'),
						array('title'=>getLabel('smc-move'), 					 'module'=>'content', '#__name'=>'tree_move_element'),
						array('title'=>getLabel('smc-change-template'), 						 'module'=>'content', '#__name'=>'change_template'),
						array('title'=>getLabel('smc-change-lang'), 					 'module'=>'content', '#__name'=>'move_to_lang'),
						array('title'=>getLabel('smc-change-lang'), 					 'module'=>'content', '#__name'=>'copy_to_lang_old')),
					'types' => array(
						array('common' => 'true', 'id' => 'object')
					),
					'stoplist' => array('title', 'h1', 'meta_keywords', 'meta_descriptions', 'menu_pic_ua', 'menu_pic_a', 'header_pic', 'more_params', 'robots_deny', 'is_unindexed', 'store_amounts', 'locktime', 'lockuser', 'rate_voters', 'rate_sum'),
					'default' => 'price[100px]|photo[100px]'
				);
		}
	};
?>