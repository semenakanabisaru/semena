<?php

	abstract class __content extends baseModuleAdmin {

		/* tickets */

		public function tickets () {
			$this->setDataType("list");
			$this->setActionType("view");
			if($this->ifNotXmlMode()) return $this->doData();

			$limit = 20;
			$curr_page = getRequest('p');
			$offset = $curr_page * $limit;

			$sel = new selector('objects');
			$sel->types('object-type')->name('content', 'ticket');
			$sel->limit($offset, $limit);

			selectorHelper::detectFilters($sel);

			if(isset($_REQUEST['order_filter']['name'])) {
				$_REQUEST['order_filter']['message'] = $_REQUEST['order_filter']['name'];
				unset($_REQUEST['order_filter']['name']);
			}

			$data = $this->prepareData($sel->result, "objects");

			$this->setData($data, $sel->length);
			$this->setDataRangeByPerPage($limit, $curr_page);
			return $this->doData();
		}


		public function del_ticket() {
			$objects = getRequest('element');
			if(!is_array($objects)) {
				$objects = Array($objects);
			}

			foreach($objects as $objectId) {
				$object = $this->expectObject($objectId, false, true);

				$params = Array(
					'object'		=> $object,
					'allowed-element-types' => Array('ticket')
				);

				$this->deleteObject($params);
			}

			$this->setDataType("list");
			$this->setActionType("view");
			$data = $this->prepareData($objects, "objects");
			$this->setData($data);

			return $this->doData();
		}

		/* /tickets */


		public function sitetree() {
			$domains = domainsCollection::getInstance()->getList();
			$permissions = permissionsCollection::getInstance();
			$user_id = $permissions->getUserId();

			$this->setDataType("list");
			$this->setActionType("view");

			foreach($domains as $i => $domain) {
				$domain_id = $domain->getId();

				if(!$permissions->isAllowedDomain($user_id, $domain_id)) {
					unset($domains[$i]);
				}
			}

			$data = $this->prepareData($domains, "domains");

			$this->setData($data, sizeof($domains));
			return $this->doData();
		}


		public function add() {
			$parent = $this->expectElement("param0");
			$type = (string) getRequest("param1");
			$mode = (string) getRequest("param2");

			$inputData = array(	"type" => $type,
						"parent" => $parent,
						"allowed-element-types" => array('page', ''));

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

			$inputData = array(	"element" => $element,
								"allowed-element-types" => array('page', '')
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


		public function config() {
			$domains = domainsCollection::getInstance()->getList();
			$lang_id = cmsController::getInstance()->getCurrentLang()->getId();

			$mode = (string) getRequest('param0');


			$result = Array();

			foreach($domains as $domain) {
				$host = $domain->getHost();
				$domain_id = $domain->getId();

				$result[$host] = Array();

				$templates = templatesCollection::getInstance()->getTemplatesList($domain_id, $lang_id);
				foreach($templates as $template) {
					$result[$host][] = $template;
				}
			}

			if($mode == "do") {
				$this->saveEditedList("templates", $result);
				$this->chooseRedirect();
			}


			$this->setDataType("list");
			$this->setActionType("modify");

			$data = $this->prepareData($result, "templates");

			$this->setData($data);
			return $this->doData();
		}


		public function del() {
			$element = $this->expectElement('param0');

			$params = Array(
				"element" => $element,
				"allowed-element-types" => Array('page', '')
			);

			$this->deleteElement($params);
			$this->chooseRedirect();
		}


		public function tpl_edit() {
			$tpl_id = (int) getRequest('param0');
			$template = templatesCollection::getInstance()->getTemplate($tpl_id);

			$mode = (string) getRequest('param1');

			if($mode == "do") {
				$this->saveEditedTemplateData($template);
				$this->chooseRedirect();
			}

			$this->setDataType('form');
			$this->setActionType('modify');

			$data = $this->prepareData($template, 'template');

			$this->setData($data);
			return $this->doData();
		}

		//Events
		public function systemLockPage($eEvent){
			if ($ePage = $eEvent->getRef("element")){
				$userId = $eEvent->getParam("user_id");
				$lockTime = $eEvent->getParam("lock_time");
				$oPage = &$ePage->getObject();
				$oPage->setValue("locktime", $lockTime);
				$oPage->setValue("lockuser", $userId);
				$oPage->commit();
			}
		}
		public function systemUnlockPage($eEvent){
			if ($ePage = $eEvent->getRef("element")){
				$userId = $eEvent->getParam("user_id");
				$oPage = $ePage->getObject();
				$oPage->setValue("locktime", null);
				$oPage->setValue("lockuser", null);
				$oPage->commit();
			}
		}
		//Lock control methods
		public function systemIsLockedById($element_id, $user_id){
			$ePage = umiHierarchy::getElement($element_id);
			$oPage = $ePage->getObject();
			$lockTime = $oPage->getValue("locktime");
			if ($lockTime == null){
				return false;
			}
			$lockUser = $oPage->getValue("lockuser");
			$lockDuration = regedit::getInstance()->getVal("//settings/lock_duration");
			if (($lockTime->timestamp + $lockDuration) > time() && $lockUser!=$user_id){
				return true;
			}else{
				return false;
			}
		}
		public function systemWhoLocked($element_id){
			$ePage = umiHierarchy::getElement($element_id);
			$oPage = $ePage->getObject();
			return $oPage->getValue("lock_user");
		}
		public function systemUnlockAll() {
			$oUsersMdl = cmsController::getInstance()->getModule("users");
			if (!$oUsersMdl->isSv()){
				throw new publicAdminException(getLabel('error-can-unlock-not-sv'));
			}
			$sel = new umiSelection();
			$sel->forceHierarchyTable(true);
			$result = umiSelectionsParser::runSelection($sel);
			foreach ($result as $page_id){
				$ePage = umiHierarchy::getInstance()->getElement($page_id);
				$oPage = $ePage->getObject();
				$oPage->setValue("locktime", null);
				$oPage->setValue("lockuser", null);
				$oPage->commit();
				$ePage->commit();
			}
		}
		public function unlockAll () {
			$this->systemUnlockAll();
			$this->chooseRedirect();
		}
		public function unlockPage($pageId) {
			$element = umiHierarchy::getInstance()->getElement($pageId);
			if($element instanceof umiHierarchyElement) {
				$pageObject = $element->getObject();
				$pageObject->setValue("locktime", 0);
				$pageObject->setValue("lockuser", 0);
				$pageObject->commit();
			}
		}
		public function unlock_page() {
			$pageId = getRequest("param0");
			if (cmsController::getInstance()->getModule("users")->isSv) {
				throw new publicAdminException(getLabel('error-can-unlock-not-sv'));
			}
			$this->unlockPage($pageId);
		}

		public function content_control() {
			$mode = getRequest("param0");
			$regedit = regedit::getInstance();

			$params = Array (
				"content_config" => Array (
						"bool:lock_pages" => false,
						"int:lock_duration" => 0,
						"bool:expiration_control" => false
					)
			);

			if ($mode == "do") {
				$params = $this->expectParams($params);
				$regedit->setVar("//settings/lock_pages", $params['content_config']['bool:lock_pages']);
				$regedit->setVar("//settings/lock_duration", $params['content_config']['int:lock_duration']);
				$regedit->setVar("//settings/expiration_control", $params['content_config']['bool:expiration_control']);

				$this->switchGroupsActivity('svojstva_publikacii', (bool) $params['content_config']['bool:expiration_control']);

				$this->chooseRedirect();
			}
			$params['content_config']['bool:lock_pages'] = $regedit->getVal("//settings/lock_pages");
			$params['content_config']['int:lock_duration'] = $regedit->getVal("//settings/lock_duration");
			$params['content_config']['bool:expiration_control'] = $regedit->getVal("//settings/expiration_control");

			$this->setDataType("settings");
			$this->setActionType("modify");

			$data = $this->prepareData($params, "settings");
			$this->setData($data);
			return $this->doData();
		}



		public function getDatasetConfiguration($param = '') {
		    $loadMethod = 'load_tree_node';
		    $deleteMethod = 'tree_delete_element';
		    $activityMethod = 'tree_set_activity';

		    if($param == "tickets") {
		    	$loadMethod = 'tickets';
		    	$deleteMethod = 'del_ticket';
		    	$activityMethod = 'none';
		    }

			$result = array(
					'methods' => array(
						array('title'=>getLabel('smc-load'), 'forload'=>true, 			 'module'=>'content', '#__name'=>$loadMethod),
						array('title'=>getLabel('smc-delete'), 					     'module'=>'content', '#__name'=>$deleteMethod, 'aliases' => 'tree_delete_element,delete,del'),
						array('title'=>getLabel('smc-activity'), 		 'module'=>'content', '#__name'=>$activityMethod, 'aliases' => 'tree_set_activity,activity'),
						array('title'=>getLabel('smc-copy'), 'module'=>'content', '#__name'=>'tree_copy_element'),
						array('title'=>getLabel('smc-move'), 					 'module'=>'content', '#__name'=>'tree_move_element'),
						array('title'=>getLabel('smc-change-template'), 						 'module'=>'content', '#__name'=>'change_template'),
						array('title'=>getLabel('smc-change-lang'), 					 'module'=>'content', '#__name'=>'move_to_lang'),
						array('title'=>getLabel('smc-change-lang'), 					 'module'=>'content', '#__name'=>'copy_to_lang_old'))
			);

			if($param == "tickets") {
				$result['types'] = array(
					array('common' => 'true', 'id' => 'ticket')
				);

				$result['stoplist'] = array('title', 'h1', 'meta_keywords', 'meta_descriptions', 'menu_pic_ua', 'menu_pic_a', 'header_pic', 'more_params', 'robots_deny', 'is_unindexed', 'store_amounts', 'locktime', 'lockuser', 'avatar', 'userpic', 'user_settings_data', 'user_dock', 'orders_refs', 'activate_code', 'password', 'message');
				$result['default'] = 'url[312px]|user_id[147px]';
			}

			return $result;
		}

		public function getObjectEditLink($objectId, $type = false) {
			return false;
		}
	};

?>
