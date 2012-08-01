<?php
class __admin extends baseModuleAdmin {
	public function config() {
		$regedit = regedit::getInstance();
		$params = Array('paging' => array(
							'int:blogs_per_page'    => null,
							'int:posts_per_page'    => null,
							'int:comments_per_page' => null),
						'user'  => array(
							'string:autocreate_path'   => null,
							'int:blogs_per_user'       => null,
							'boolean:allow_guest_comments' => null,
							'boolean:moderate_comments' => null),
						'notifications' => array(
							'boolean:on_comment_add' => null
						));
		if(getRequest('param0') == 'do') {
			try {
				$params = $this->expectParams($params);
				$regedit->setVar('//modules/blogs20/paging/blogs',    	   $params['paging']['int:blogs_per_page']);
				$regedit->setVar('//modules/blogs20/paging/posts',    	   $params['paging']['int:posts_per_page']);
				$regedit->setVar('//modules/blogs20/paging/comments', 	   $params['paging']['int:comments_per_page']);
				$regedit->setVar('//modules/blogs20/autocreate_path',  	   $params['user']['string:autocreate_path']);
				$regedit->setVar('//modules/blogs20/blogs_per_user',  	   $params['user']['int:blogs_per_user']);
				$regedit->setVar('//modules/blogs20/allow_guest_comments', $params['user']['boolean:allow_guest_comments'] ? 1 : 0);
				$regedit->setVar('//modules/blogs20/moderate_comments', $params['user']['boolean:moderate_comments'] ? 1 : 0);
				$regedit->setVar('//modules/blogs20/notifications/on_comment_add', $params['notifications']['boolean:on_comment_add'] ? 1 : 0);
			} catch(Exception $e) {}
			$this->chooseRedirect();
		}
		$params['paging']['int:blogs_per_page']     = $regedit->getVal("//modules/blogs20/paging/blogs");
		$params['paging']['int:posts_per_page']     = $regedit->getVal("//modules/blogs20/paging/posts");
		$params['paging']['int:comments_per_page']  = $regedit->getVal("//modules/blogs20/paging/comments");
		$params['user']['string:autocreate_path']   = $regedit->getVal("//modules/blogs20/autocreate_path");
		$params['user']['int:blogs_per_user'] 	    = $regedit->getVal("//modules/blogs20/blogs_per_user");
		$params['user']['boolean:allow_guest_comments'] = $regedit->getVal("//modules/blogs20/allow_guest_comments") ? true : false;
		$params['user']['boolean:moderate_comments'] = $regedit->getVal("//modules/blogs20/moderate_comments") ? true : false;
		$params['notifications']['boolean:on_comment_add'] = $regedit->getVal("//modules/blogs20/notifications/on_comment_add") ? true : false;
		if(!$regedit->getVal("//modules/blogs20/import/old")) {
			$params['import'] = array();			
		}
		$this->setDataType('settings');
		$this->setActionType('modify'); 
		$data = $this->prepareData($params, 'settings'); 
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
				"allowed-element-types" => Array('post', 'blog', 'comment')
			);
			$this->deleteElement($params);
		}
		
		$this->setDataType("list");
		$this->setActionType("view");
		$data = $this->prepareData($elements, "pages");
		$this->setData($data);

		return $this->doData();
	}
	
	public function edit() {
		$element = $this->expectElement("param0");
		$mode = (String) getRequest('param1');
		$this->setHeaderLabel('header-blogs20-edit-'.$this->getObjectTypeMethod($element->getObject()));
		$inputData = Array(	"element" => $element,
							"allowed-element-types" => Array('post', 'blog', 'comment'));
		if($mode == "do") {
			$this->saveEditedElementData($inputData);
			if($element->getTypeId() == umiHierarchyTypesCollection::getInstance()->getTypeByName('blogs20', 'blog')->getId() ) {				
				permissionsCollection::getInstance()->setElementPermissions($element->getObject()->getOwnerId(), $element->getId(), 31);
			}			
			$this->chooseRedirect();
		}
		$this->setDataType("form");
		$this->setActionType("modify");
		$data = $this->prepareData($inputData, "page");
		$this->setData($data);
		return $this->doData();
	}
	public function add() {
		$parent = $this->expectElement("param0");		
		$mode   = (string) getRequest("param2");
		$type   = (string) getRequest("param1");
		$this->setHeaderLabel('header-blogs20-add-'.$type);
		$inputData = Array( "type"   => $type,
							"parent" => $parent,
							"allowed-element-types" => Array('post', 'blog'));
		if($mode == "do") {
			$element_id = $this->saveAddedElementData($inputData);			
			$this->chooseRedirect();
		}
		$this->setDataType("form");
		$this->setActionType("create");
		$data = $this->prepareData($inputData, "page");
		$this->setData($data);
		return $this->doData();
	}
	public function blogs() {
		return $this->listItems('blog');
	}
	public function lists() {
		return $this->redirect($this->pre_lang . '/admin/blogs20/blogs/');
	}		
	public function posts() {
		return $this->listItems('post');		
	}
	public function comments() {
		return $this->listItems('comment');
	}	
	public function activity() {
		$elements = getRequest('element');
		if(!is_array($elements)) {
			$elements = array($elements);
		}
		$is_active = getRequest('active');
		
		foreach($elements as $elementId) {
			$element = $this->expectElement($elementId, false, true);
			
			$params = Array(
				"element" => $element,
				"allowed-element-types" => Array('post', 'blog', 'comment'),
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
	public function listItems($itemType) {
		$this->setDataType("list");
		$this->setActionType("view");
		
		if($this->ifNotXmlMode()) {
			$data['nodes:blogs'] = array( array('nodes:blog' => $this->getAllBlogs() ) ); 
			$this->setData($data, 0);
			return $this->doData();
		}

		$limit = 20;
		$curr_page = getRequest('p');
		$offset = $limit * $curr_page;
		
		$sel = new selector('pages');
		$sel->limit($offset, $limit);
		
		switch($itemType) {
			case 'comment':
				$sel->types('hierarchy-type')->name('blogs20', 'comment');
				break;

			case 'post':
				if(!is_null(getRequest('rel'))) {
					$sel->types('hierarchy-type')->name('blogs20', 'comment');
				}
				$sel->types('hierarchy-type')->name('blogs20', 'post');
				break;

			default:
				$sel->types('hierarchy-type')->name('blogs20', 'blog');
				$sel->types('hierarchy-type')->name('blogs20', 'comment');
				$sel->types('hierarchy-type')->name('blogs20', 'post');
		}
		selectorHelper::detectFilters($sel);
		
		$this->setDataRange($limit, $offset);
		$data = $this->prepareData($sel->result, "pages");
		$this->setData($data, $sel->length);
		return $this->doData();
	}
	public function getAllBlogs() {
		$sel = new selector('pages');
		$sel->where('permissions');
		$sel->types('hierarchy-type')->name('blogs20', 'blog');
		
		$result = array();
		foreach($sel as $blog) {
			$result[] = array(
				'attribute:id' => $blog->id,
				'node:name' => $blog->name
			);
		}
		
		return $result;
	}

	public function getDatasetConfiguration($param = '') {
		switch($param) {
			case 'comments':
					$loadMethod = 'comments';
				break;
			case 'posts': 
					$loadMethod = 'posts';
				break;
			default: 
					$loadMethod = 'blogs';			
		}		
		return array(
				'methods' => array(
					array('title'=>getLabel('smc-load'), 'forload'=>true, 'module'=>'blogs20', '#__name'=>$loadMethod),
					array('title'=>getLabel('smc-delete'), 				  'module'=>'blogs20', '#__name'=>'del', 'aliases' => 'tree_delete_element,delete,del'),
					array('title'=>getLabel('smc-activity'), 		      'module'=>'blogs20', '#__name'=>'activity', 'aliases' => 'tree_set_activity,activity'),
					array('title'=>getLabel('smc-copy'), 'module'=>'content', '#__name'=>'tree_copy_element'),
					array('title'=>getLabel('smc-move'), 					 'module'=>'content', '#__name'=>'tree_move_element'),
					array('title'=>getLabel('smc-change-template'), 						 'module'=>'content', '#__name'=>'change_template'),
					array('title'=>getLabel('smc-change-lang'), 					 'module'=>'content', '#__name'=>'move_to_lang'),
					array('title'=>getLabel('smc-change-lang'), 					 'module'=>'content', '#__name'=>'copy_to_lang_old'),
					),
				'types' => array(
					array('common' => 'true', 'id' => umiObjectTypesCollection::getInstance()->getBaseType('blogs20', 'post'))
				),
				'stoplist' => array('title', 'h1', 'meta_keywords', 'meta_descriptions', 'menu_pic_ua', 'menu_pic_a', 'header_pic', 'more_params', 'robots_deny', 'is_unindexed', 'store_amounts', 'locktime', 'lockuser', 'content', 'rate_voters', 'rate_sum'),
				'default' => 'publish_time[156px]'
			);
		}
};
?>
