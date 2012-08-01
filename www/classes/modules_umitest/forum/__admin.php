<?php
	
	abstract class __forum extends baseModuleAdmin {
	
		public function lists() {
			$this->setDataType("list");
			$this->setActionType("view");
			if($this->ifNotXmlMode()) return $this->doData();
			
			$limit = 20;
			$curr_page = getRequest('p');
			$offset = $curr_page * $limit;

			$sel = new selector('pages');
			$sel->types('hierarchy-type')->name('forum', 'conf');
			$sel->types('hierarchy-type')->name('forum', 'topic');
			$sel->types('hierarchy-type')->name('forum', 'message');
			$sel->limit($offset, $limit);
			
			selectorHelper::detectFilters($sel);

			$data = $this->prepareData($sel->result, "pages");

			$this->setData($data, $sel->length);
			$this->setDataRangeByPerPage($limit, $curr_page);
			return $this->doData();
		}
	
		public function last_messages() {
			$this->setDataType("list");
			$this->setActionType("view");
			if($this->ifNotXmlMode()) return $this->doData();
			
			$limit = 20;
			$curr_page = getRequest('p');
			$offset = $curr_page * $limit;

			$sel = new selector('pages');
			$sel->types('hierarchy-type')->name('forum', 'message');
			$sel->order('publish_time')->desc();
			$sel->limit($offset, $limit);
			
			selectorHelper::detectFilters($sel);

			$data = $this->prepareData($sel->result, "pages");

			$this->setData($data, $sel->length);
			$this->setDataRangeByPerPage($limit, $curr_page);
			return $this->doData();
		}
	
		public function add() {
			$parent = $this->expectElement("param0");
	
			//Получаем название типа. Id типа данных будет
			//автоматически определен в ф-ях saveEditedElementData или prepareData
			$type = (string) getRequest("param1");
			$mode = (string) getRequest("param2");
	
			$this->setHeaderLabel("header-forum-add-" . $type);
	
			//Подготавливаем список параметров
			$inputData = Array(	"type" => $type,
						"parent" => $parent,
						'type-id' => getRequest('type-id'),
						"allowed-element-types" => Array('conf', 'topic', 'message'));
	
			if ($mode == "do") {
                    
                    $elementId  = $this->saveAddedElementData($inputData);
				$element = $this->expectElement($elementId, false, true); 
				
				
                    $event = new umiEventPoint("systemCreateElementAfter");
                    $event->addRef("element", $element);
                    $event->setMode("after");
                    $event->call();

				$this->chooseRedirect();
			}
	
			// Вывод данных
			return $this->makeAdminOutputForm("create", "page", $inputData);
		}
	
		public function edit() {
			// Указываем, что в первом параметре мы ожидаем id страницы
			// Если все хорошо, то нам вернется umiHierarchyElement, если нет,
			// то будет выброшен соответствующий exception
			$element = $this->expectElement("param0");
			$mode = (string)getRequest('param1');
	
			$this->setHeaderLabel("header-forum-edit-" . $this->getObjectTypeMethod($element->getObject()));
	
			$inputData = Array(
				"element" => $element,
				"allowed-element-types" => Array('conf', 'topic', 'message')
			);
	
			if ($mode == "do") {
				$this->saveEditedElementData($inputData);

                    $event = new umiEventPoint("systemSwitchElementActivity");
                    $event->addRef("element", $element);
                    $event->setMode("after");
                    $event->call();


				$this->chooseRedirect();
			}
	
			// Вывод данных
			return $this->makeAdminOutputForm("modify", "page", $inputData);
	
		}
		
		public function del() {
			$elements = getRequest('element');
			if(!is_array($elements)) {
				$elements = Array($elements);
			}
			
			$hierarchy = umiHierarchy::getInstance();
			
			foreach($elements as $elementId) {
				$element = $this->expectElement($elementId, false, true);
				
				$parentElementId = $element->getRel();
				$parentElement = $hierarchy->getElement($parentElementId);
				if($parentElement instanceof umiHierarchyElement) {
					$parentMethod = $parentElement->getMethod();
					
					if($parentMethod == "conf") {
						$topicsCount = $parentElement->getValue('topics_count');
						$messagesCount = $parentElement->getValue('messages_count');
						
						$messagesDiff  = $element->getValue('messages_count');
						
						$parentElement->setValue('topics_count', $topicsCount - 1);
						$parentElement->setValue('messages_count', $messagesCount - $messagesDiff);
						
						$parentElement->commit();
					}
					
					if($parentMethod == "topic") {
						$messagesCount = $parentElement->getValue('messages_count');
						if($messagesCount == 1) {
							$params = Array(
										"element" => $parentElement,
										"allowed-element-types" => Array('conf', 'topic', 'message')
									);
							$this->deleteElement($params);
						}
					}
				}
				
				$params = Array(
					"element" => $element,
					"allowed-element-types" => Array('conf', 'topic', 'message')
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
					"allowed-element-types" => Array('conf', 'topic', 'message'),
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
	
		public function config() {
			$regedit = regedit::getInstance();
	
			//Подготавливаем параметры и забиваем в него NULL'ы
			$params = Array(
				'config' => Array(
					'int:per_page' => NULL,
					'boolean:need_moder' => NULL,
					'boolean:allow_guest' => NULL,
					'boolean:sort_by_last_message' => NULL
				)
			);
			$i_otype_dispatch = umiObjectTypesCollection::getInstance()->getBaseType('dispatches', "dispatch");
			if ($i_otype_dispatch) {
				$params['config']['weak_guide:dispatch_id'] = Array('type-id' => $i_otype_dispatch,'value' => NULL);
			}
	
			$mode = (string) getRequest('param0');
	
			if($mode == 'do') {
				//Говорим, что мы ожидаем в POST'е параметры, описанные в $params
				//Ф-я expectParams заполнит его значениями или выбросит exception
				$params = $this->expectParams($params);
	
				//Смело обращаемся к массиву и получаем нужные значения
				$regedit->setVar('//modules/forum/per_page', $params['config']['int:per_page']);
				$regedit->setVar('//modules/forum/need_moder', $params['config']['boolean:need_moder']);
				$regedit->setVar('//modules/forum/allow_guest', $params['config']['boolean:allow_guest']);
				$regedit->setVar('//modules/forum/sort_by_last_message', $params['config']['boolean:sort_by_last_message']);
				if ($i_otype_dispatch) {
					$regedit->setVar('//modules/forum/dispatch_id', $params['config']['weak_guide:dispatch_id']);
				}
	
				$this->chooseRedirect();
			}
	
			//Заполняем массив значениями
			$params['config']['int:per_page'] = (int) $regedit->getVal('//modules/forum/per_page');
			$params['config']['boolean:need_moder'] = (int) $regedit->getVal('//modules/forum/need_moder');
			$params['config']['boolean:allow_guest'] = (int) $regedit->getVal('//modules/forum/allow_guest');
			$params['config']['boolean:sort_by_last_message'] = (int) $regedit->getVal('//modules/forum/sort_by_last_message');
			if ($i_otype_dispatch) {
				$params['config']['weak_guide:dispatch_id']['value'] = (int) $regedit->getVal('//modules/forum/dispatch_id');
			}
	
			$this->setDataType('settings');
			$this->setActionType('modify');
	
			$data = $this->prepareData($params, 'settings');
	
			$this->setData($data);
			return $this->doData();
		}
		
		public function getDatasetConfiguration($param = '') {
		    $loadMethod = ($param == 'last_messages') ? 'last_messages' : 'lists';
		    
			return array(
					'methods' => array(
						array('title'=>getLabel('smc-load'), 'forload'=>true, 			 'module'=>'forum', '#__name'=>$loadMethod),
						array('title'=>getLabel('smc-delete'), 					     'module'=>'forum', '#__name'=>'del', 'aliases' => 'tree_delete_element,delete,del'),
						array('title'=>getLabel('smc-activity'), 		 'module'=>'forum', '#__name'=>'activity', 'aliases' => 'tree_set_activity,activity'),
						array('title'=>getLabel('smc-copy'), 'module'=>'content', '#__name'=>'tree_copy_element'),
						array('title'=>getLabel('smc-move'), 					 'module'=>'content', '#__name'=>'tree_move_element'),
						array('title'=>getLabel('smc-change-template'), 						 'module'=>'content', '#__name'=>'change_template'),
						array('title'=>getLabel('smc-change-lang'), 					 'module'=>'content', '#__name'=>'move_to_lang'),
						array('title'=>getLabel('smc-change-lang'), 					 'module'=>'content', '#__name'=>'copy_to_lang_old'),						
						),
					'types' => array(
						array('common' => 'true', 'id' => 'message')
					),
					'stoplist' => array('title', 'h1', 'meta_keywords', 'meta_descriptions', 'menu_pic_ua', 'menu_pic_a', 'header_pic', 'more_params', 'robots_deny', 'is_unindexed', 'store_amounts', 'locktime', 'lockuser', 'rate_voters', 'rate_sum'),
					'default' => 'publish_time[156px]|author_id[100px]'
				);
			}
	
	};
?>