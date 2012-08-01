<?php
	abstract class __comments extends baseModuleAdmin {

		public function view_comments() {
			$this->setDataType("list");
			$this->setActionType("view");
			if($this->ifNotXmlMode()) return $this->doData();

			//Получение id родительской страницы. Если передан неверный id, будет выброшен exception
			$parent_id = $this->expectElementId('param0');

			$limit = 20;
			$curr_page = (int) getRequest('p');
			$offset = $limit * $curr_page;

			if(!is_null($rel = getRequest('rel'))) {
				$rel = array_extract_values($rel);
				if(empty($rel)) {
					unset($_REQUEST['rel']);
				}
			}

			$sel = new selector('pages');
			$sel->types('hierarchy-type')->name('comments', 'comment');
			$sel->order('publish_time')->desc();
			$sel->limit($offset, $limit);
			selectorHelper::detectFilters($sel);

			$this->setDataRange($limit, $offset);
			$data = $this->prepareData($sel->result, "pages");
			$this->setData($data, $sel->length);
			return $this->doData();
		}

		public function view_noactive_comments() {
			$this->setDataType("list");
			$this->setActionType("view");
			if($this->ifNotXmlMode()) return $this->doData();

			//Получение id родительской страницы. Если передан неверный id, будет выброшен exception
			$parent_id = $this->expectElementId('param0');

			$limit = 20;
			$curr_page = (int) getRequest('p');
			$offset = $limit * $curr_page;

			$sel = new selector('pages');
			$sel->types('hierarchy-type')->name('comments', 'comment');
			$sel->where('is_active')->equals(false);
			$sel->limit($offset, $limit);
			selectorHelper::detectFilters($sel);

			$this->setDataRange($limit, $offset);
			$data = $this->prepareData($sel->result, "pages");
			$this->setData($data, $sel->total);
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
					"allowed-element-types" => Array('comment')
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
			$is_active = getRequest('activity');

			foreach($elements as $elementId) {
				$element = $this->expectElement($elementId, false, true);

				$params = Array(
					"element" => $element,
					"allowed-element-types" => Array('comment'),
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

		public function edit() {
			//Указываем, что в первом параметре мы ожидаем id страницы
			//Если все хорошо, то нам вернется umiHierarchyElement, если нет,
			//то будет выброшен соответствующий exception
			$element = $this->expectElement("param0");
			$mode = (String) getRequest('param1');

			$inputData = Array(	"element" => $element,
						"allowed-element-types" => Array('comment'));

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

		public function config() {
			$regedit = regedit::getInstance();
			$vkontakte = $regedit->getVal('//modules/comments/vkontakte');
			$facebook = $regedit->getVal('//modules/comments/facebook');
			//Подготавливаем параметры и забиваем в него NULL'ы
			$params = Array(
				'config' => Array(
					'boolean:default_comments'		=> NULL,
					'int:per_page'			=> NULL,
					'boolean:moderated'		=> NULL,
					'boolean:allow_guest'	=> NULL,
							),
				'vkontakte' => Array(
					'boolean:vkontakte'		=> NULL,
					'boolean:vk_extend'		=> NULL,
							'int:vk_per_page' => NULL,
							'int:vk_width' => NULL,
							'string:vk_api' => NULL,
							),
				'facebook' => Array(
					'boolean:facebook'		=> NULL,
							'int:fb_per_page' => NULL,
							'int:fb_width' => NULL,
							'select:fb_colorscheme'		=> Array(
								'light'		=> getLabel('option-colorscheme-light'),
								'dark'		=> getLabel('option-colorscheme-dark')
							)
						)
					);

			$mode = (string) getRequest('param0');

			if($mode == 'do') {
				//Говорим, что мы ожидаем в POST'е параметры, описанные в $params
				//Ф-я expectParams заполнит его значениями или выбросит exception
				$params = $this->expectParams($params);

				//Смело обращаемся к массиву и получаем нужные значения
				$regedit->setVar('//modules/comments/default_comments', $params['config']['boolean:default_comments']);
				$regedit->setVar('//modules/comments/per_page', $params['config']['int:per_page']);
				$regedit->setVar('//modules/comments/moderated', $params['config']['boolean:moderated']);
				$regedit->setVar('//modules/comments/allow_guest', $params['config']['boolean:allow_guest']);

				$regedit->setVar('//modules/comments/vkontakte', $params['vkontakte']['boolean:vkontakte']);
				$regedit->setVar('//modules/comments/vk_per_page', $params['vkontakte']['int:vk_per_page']);
				$regedit->setVar('//modules/comments/vk_width', $params['vkontakte']['int:vk_width']);
				$regedit->setVar('//modules/comments/vk_api', $params['vkontakte']['string:vk_api']);
				$regedit->setVar('//modules/comments/vk_extend', $params['vkontakte']['boolean:vk_extend']);
				
				$regedit->setVar('//modules/comments/facebook', $params['facebook']['boolean:facebook']);
				$regedit->setVar('//modules/comments/fb_per_page', $params['facebook']['int:fb_per_page']);
				$regedit->setVar('//modules/comments/fb_width', $params['facebook']['int:fb_width']);
				$regedit->setVar('//modules/comments/fb_colorscheme', $params['facebook']['select:fb_colorscheme']);

				$this->chooseRedirect();
			}

			//Заполняем массив значениями
			$params['config']['boolean:default_comments'] = (bool) $regedit->getVal('//modules/comments/default_comments');
			$params['config']['int:per_page'] = (int) $regedit->getVal('//modules/comments/per_page');
			$params['config']['boolean:moderated'] = (bool) $regedit->getVal('//modules/comments/moderated');
			$params['config']['boolean:allow_guest'] = (bool) $regedit->getVal('//modules/comments/allow_guest');

			$params['vkontakte']['boolean:vkontakte'] = (bool) $regedit->getVal('//modules/comments/vkontakte');
			$params['vkontakte']['int:vk_per_page'] = (int) $regedit->getVal('//modules/comments/vk_per_page');
			$params['vkontakte']['int:vk_width'] = (int) $regedit->getVal('//modules/comments/vk_width');
			$params['vkontakte']['string:vk_api'] = (string) $regedit->getVal('//modules/comments/vk_api');
			$params['vkontakte']['boolean:vk_extend'] = (bool) $regedit->getVal('//modules/comments/vk_extend');
			
			$params['facebook']['boolean:facebook'] = (bool) $regedit->getVal('//modules/comments/facebook');
			$params['facebook']['int:fb_per_page'] = (int) $regedit->getVal('//modules/comments/fb_per_page');
			$params['facebook']['int:fb_width'] = (int) $regedit->getVal('//modules/comments/fb_width');
			$params['facebook']['select:fb_colorscheme']['value'] = (string) $regedit->getVal('//modules/comments/fb_colorscheme');
			
			$this->setDataType('settings');
			$this->setActionType('modify');

			$data = $this->prepareData($params, 'settings');

			$this->setData($data);
			return $this->doData();
		}

		public function getDatasetConfiguration($param = '') {
			$load = ($param == "noactive") ? "view_noactive_comments" : "view_comments";

			return array(
					'methods' => array(
						array('title'=>getLabel('smc-load'), 'forload'=>true, 			 'module'=>'comments', '#__name'=>$load),
						array('title'=>getLabel('smc-delete'), 					     'module'=>'comments', '#__name'=>'del', 'aliases' => 'tree_delete_element,delete,del'),
						array('title'=>getLabel('smc-activity'), 		 'module'=>'comments', '#__name'=>'activity', 'aliases' => 'tree_set_activity,activity'),
						array('title'=>getLabel('smc-copy'), 'module'=>'content', '#__name'=>'tree_copy_element'),
						array('title'=>getLabel('smc-move'), 					 'module'=>'content', '#__name'=>'tree_move_element'),
						array('title'=>getLabel('smc-change-template'), 						 'module'=>'content', '#__name'=>'change_template'),
						array('title'=>getLabel('smc-change-lang'), 					 'module'=>'content', '#__name'=>'move_to_lang'),
						array('title'=>getLabel('smc-change-lang'), 					 'module'=>'content', '#__name'=>'copy_to_lang_old'),
						),
					'types' => array(
						array('common' => 'true', 'id' => 'comment')
					),
					'stoplist' => array('title', 'h1', 'meta_keywords', 'meta_descriptions', 'menu_pic_ua', 'menu_pic_a', 'header_pic', 'more_params', 'robots_deny', 'is_unindexed', 'store_amounts', 'locktime', 'lockuser', 'anons', 'content', 'rate_voters', 'rate_sum'),
					'default' => 'publish_time[140px]'
				);
		}

	};
?>