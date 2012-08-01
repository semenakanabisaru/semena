<?php
	abstract class __users extends baseModuleAdmin {

		public function users_list_all() {
			return $this->users_list(true);
		}


		public function users_list($group_id = false) {
			$this->setDataType("list");
			$this->setActionType("view");
			if($this->ifNotXmlMode()) return $this->doData();

			$limit = 20;
			$curr_page = (int) getRequest('p');
			$offset = $limit * $curr_page;

			$sel = new selector('objects');
			$sel->types('object-type')->name('users', 'user');
			$sel->limit($offset, $limit);

			if(getRequest('param0') == 'outgroup') {
				$sel->where('groups')->isnull(true);
			} else {
				if($groupId = $this->expectObjectId('param0')) {
					$sel->where('groups')->equals($groupId);
				}
			}

			if (!permissionsCollection::getInstance()->isSv()) {
				$sel->where('guid')->notequals('system-supervisor');
			}

			if($loginSearch = getRequest('search')) {
				$sel->where('login')->like('%' . $loginSearch . '%');
			}

			selectorHelper::detectFilters($sel);

			$this->setDataRange($limit, $offset);
			$data = $this->prepareData($sel->result, "objects");

			$this->setData($data, $sel->length);
			return $this->doData();
		}


		public function groups_list() {
			$this->setDataType("list");
			$this->setActionType("view");
			if($this->ifNotXmlMode()) return $this->doData();

			$limit = 20;
			$curr_page = (int) getRequest('p');
			$offset = $limit * $curr_page;

			$sel = new selector('objects');
			$sel->types('object-type')->name('users', 'users');

			if (!permissionsCollection::getInstance()->isSv()) {
				$sel->where('guid')->notequals('users-users-15');
			}

			$sel->limit($offset, $limit);

			selectorHelper::detectFilters($sel);

			$this->setDataRange($limit, $offset);

			$data = $this->prepareData($sel->result, "objects");
			$this->setData($data, $sel->length);
			return $this->doData();
		}


		public function add() {
			$type = (string) getRequest('param0');
			$mode = (string) getRequest('param1');

			$this->setHeaderLabel("header-users-add-" . $type);
			$inputData = array(
				'type'					=> $type,
				'type-id' 				=> getRequest('type-id'),
				'aliases'				=> array('name' => 'login'),
				'allowed-element-types'	=> array('user', 'users')
			);

			if($mode == "do") {
				$object = $this->saveAddedObjectData($inputData);

				$permissions = permissionsCollection::getInstance();
				if(!$permissions->isSv($permissions->getUserId())) {
					$groups = $object->getValue("groups");
					if(in_array(SV_GROUP_ID, $groups)) {
						unset($groups[array_search(SV_GROUP_ID, $groups)]);
						$object->setValue("groups", $groups);
					}
				}

				// fill userdock
				$object->setValue('user_dock', 'seo,content,news,blogs20,forum,comments,vote,webforms,photoalbum,dispatches,catalog,emarket,banners,users,stat,exchange,trash');
				$object->commit();

				$this->save_perms($object->getId());
				$permissions->setAllElementsDefaultPermissions($object->getId());
				$this->chooseRedirect($this->pre_lang . '/admin/users/edit/' . $object->getId() . '/');
			}

			$this->setDataType("form");
			$this->setActionType("create");

			$data = $this->prepareData($inputData, "object");

			$this->setData($data);
			return $this->doData();
		}



		public function edit() {
			$object = $this->expectObject("param0", true);
			$mode = (string) getRequest('param1');
			$objectId = $object->getId();

			$this->setHeaderLabel("header-users-edit-" . $this->getObjectTypeMethod($object));

			$this->checkSv($objectId);

			$inputData = Array(	"object"	=> $object,
						"aliases"	=> Array("name" => "login"),
						"allowed-element-types"	=> Array('users', 'user')
			);

			if($mode == "do") {
				preg_match('|^http:\/\/(?:www\.)?([^/]+)\/|ui', getServer('HTTP_REFERER'), $matches);
				$domainsCollection = domainsCollection::getInstance();
				if ( (!isset($matches[1]) || count($matches[1])!=1)
					 || ($domainsCollection->getDomainId($matches[1])===false && $domainsCollection->getDomainId('www.'.$matches[1])===false) ) {
					$this->errorNewMessage(getLabel("error-users-non-referer"));
					$this->errorPanic();
				}
				
				if(isset($_REQUEST['data'][$objectId]['login'])) {
					if(!$this->checkIsUniqueLogin($_REQUEST['data'][$objectId]['login'], $objectId)) {
						$this->errorNewMessage(getLabel("error-users-non-unique-login"));
						$this->errorPanic();
					}
				}

				$object = $this->saveEditedObjectData($inputData);

				$objectId = $object->getId();

				if(isset($_REQUEST['data'][$objectId]['password'][0])) {
					$password = $_REQUEST['data'][$objectId]['password'][0];
				} else {
					$password = false;
				}

				$permissions = permissionsCollection::getInstance();
				$guestId = $permissions->getGuestId();
				$userId = $permissions->getUserId();

				if($object->getId() == $userId) {
					if($password) {
						$_SESSION['cms_pass'] = $object->password;
					}
				}

				if(in_array($object->getId(), array($userId, $guestId, SV_USER_ID))) {
					if(!$object->is_activated) {
						$object->is_activated = true;
						$object->commit();
					}
				}

				$this->save_perms($objectId);
				$this->chooseRedirect();
			}

			$this->setDataType("form");
			$this->setActionType("modify");

			$data = $this->prepareData($inputData, "object");

			$this->setData($data);
			return $this->doData();
		}


		public function del() {
			$objects = getRequest('element');
			if(!is_array($objects)) {
				$objects = Array($objects);
			}

			foreach($objects as $objectId) {
				$object = $this->expectObject($objectId, false, true);
				if(!$object) continue;
				$this->checkSv($object->getId());

				$object_id = $object->getId();
				if($object_id == SV_GROUP_ID) {
					throw new publicAdminException(getLabel('error-sv-group-delete'));
				}

				if($object_id == SV_USER_ID) {
					throw new publicAdminException(getLabel('error-sv-user-delete'));
				}

				$regedit = regedit::getInstance();
				if($object_id == $regedit->getVal("//modules/users/guest_id")) {
					throw new publicAdminException(getLabel('error-guest-user-delete'));
				}

				if($object_id == $regedit->getVal("//modules/users/def_group")) {
					throw new publicAdminException(getLabel('error-sv-group-delete'));
				}

				if($object_id == permissionsCollection::getInstance()->getUserId()) {
					throw new publicAdminException(getLabel('error-delete-yourself'));
				}

				$params = Array(
					'object'		=> $object,
					'allowed-element-types' => Array('user', 'users')
				);

				$this->deleteObject($params);
			}

			$this->setDataType("list");
			$this->setActionType("view");
			$data = $this->prepareData($objects, "objects");
			$this->setData($data);

			return $this->doData();
		}


		public function activity() {
			$objects = getRequest('object');
			if(!is_array($objects)) {
				$objects = Array($objects);
			}
			$is_active = (bool) getRequest('active');

			foreach($objects as $objectId) {
				$object = $this->expectObject($objectId, false, true);
				$this->checkSv($objectId);

				if(!$is_active) {
					if($objectId == SV_USER_ID) {
						throw new publicAdminException(getLabel('error-sv-user-activity'));
					}

					$regedit = regedit::getInstance();
					if($objectId == $regedit->getVal("//modules/users/guest_id")) {
						throw new publicAdminException(getLabel('error-guest-user-activity'));
					}
				}

				$object->setValue("is_activated", $is_active);
				$object->commit();
			}

			$this->setDataType("list");
			$this->setActionType("view");
			$data = $this->prepareData($objects, "objects");
			$this->setData($data);

			return $this->doData();
		}

		public function getPermissionsOwners() {
			$this->flushAsXML("getPermissionsOwners");

			$buffer = outputBuffer::current();
			$objects = umiObjectsCollection::getInstance();
			$objectTypes = umiObjectTypesCollection::getInstance();
			$groupTypeId = $objectTypes->getBaseType("users", "users");

			$svGroupId = $objects->getObjectIdByGUID('users-users-15');
			$svId = $objects->getObjectIdByGUID('system-supervisor');

			$restrict = array($svId, $svGroupId);

			$sel = new selector('objects');
			$sel->types('object-type')->name('users', 'users');
			$sel->types('object-type')->name('users', 'user');
			$sel->limit(0, 15);
			selectorHelper::detectFilters($sel);

			$items = array();
			foreach($sel as $object) {
				if(in_array($object->id, $restrict)) continue;
				$usersList = array();

				if($object->getTypeId() == $groupTypeId) {
					$users = new selector('objects');
					$users->types('object-type')->name('users', 'user');
					$users->where('groups')->equals($object->id);
					$users->limit(0, 5);
					foreach($users as $user) {
						$usersList[] = array(
							'attribute:id'		=> $user->id,
							'attribute:name'	=> $user->name,
							'xlink:href'		=> $user->xlink
						);
					}

					$type = 'group';
				} else $type = 'user';

				$items[] = array(
					'attribute:id'		=> $object->id,
					'attribute:name'	=> $object->name,
					'attribute:type'	=> $type,
					'xlink:href'		=> $object->xlink,
					'nodes:user'		=> $usersList
				);
			}

			return array(
				'list' => array(
					'nodes:owner' => $items
				)
			);
		}

		public function json_change_dock() {
			$s_dock_panel = getRequest('dock_panel');
			if ($o_users = cmsController::getInstance()->getModule("users")) {
				$i_user_id = $o_users->user_id;
				$o_user = umiObjectsCollection::getInstance()->getObject($i_user_id);
				if ($o_user) {
					$o_user->setValue("user_dock", $s_dock_panel);
					$o_user->commit();
				}
			}
			header('HTTP/1.1 200 OK');
			header("Cache-Control: public, must-revalidate");
			header("Pragma: no-cache");
			header('Date: ' . date("D M j G:i:s T Y"));
			header('Last-Modified: ' . date("D M j G:i:s T Y"));
			header ("Content-type: text/javascript");
			exit();
		}


		public function checkSv ($objectId) {
			$object = $this->expectObject($objectId, true, true);
			$perms = permissionsCollection::getInstance();
			$userId = $perms->getUserId();
			$expectSv = $perms->isSv ($object->getId());
			if ($perms->isSv ($object->getId()) && !$perms->isSv($userId))	{
				throw new publicAdminException (getLabel('error-break-action-with-sv'));
			}
		}


		public function getGroupUsersCount($groupId = false) {
			$objectTypes = umiObjectTypesCollection::getInstance();
			$userObjectTypeId = $objectTypes->getBaseType("users", "user");
			$userObjectType = $objectTypes->getType($userObjectTypeId);

			if($userObjectType instanceof umiObjectType == false) {
				throw new publicException("Can't load user object type");
			}

			$sel = new umiSelection;
			$sel->addObjectType($userObjectTypeId);

			if($groupId !== false) {
				if($groupId != 0) {
					$sel->addPropertyFilterEqual($userObjectType->getFieldId('groups'), $groupId);
				} else {
					$sel->addPropertyFilterIsNull($userObjectType->getFieldId('groups'));
				}
			}

			return umiSelectionsParser::runSelectionCounts($sel);
		}

		public function getDatasetConfiguration($param = '') {
			if($param == 'groups' || $param === "users") {
				$loadMethod = "groups_list";
				$type       = 'users';
				$default    = '';
			} else {
		    	$loadMethod = $param ? ('users_list/' . $param) : 'users_list_all';
		    	$type       = 'user';
		    	$default    = 'fname[99px]|lname[81px]|e-mail[96px]|groups[141px]|is_activated[100px]';
			}

			return array(
					'methods' => array(
						array('title'=>getLabel('smc-load'), 'forload'=>true, 			 'module'=>'users', '#__name'=>$loadMethod),
						array('title'=>getLabel('smc-delete'), 					     'module'=>'users', '#__name'=>'del', 'aliases' => 'tree_delete_element,delete,del'),
						array('title'=>getLabel('smc-activity'), 		 'module'=>'users', '#__name'=>'activity', 'aliases' => 'tree_set_activity,activity'),
						array('title'=>getLabel('smc-copy'), 'module'=>'content', '#__name'=>'tree_copy_element'),
						array('title'=>getLabel('smc-move'), 					 'module'=>'content', '#__name'=>'tree_move_element'),
						array('title'=>getLabel('smc-change-template'), 						 'module'=>'content', '#__name'=>'change_template'),
						array('title'=>getLabel('smc-change-lang'), 					 'module'=>'content', '#__name'=>'move_to_lang')),
					'types' => array(
						array('common' => 'true', 'id' => $type)
					),
					'stoplist' => array('avatar', 'userpic', 'user_settings_data', 'user_dock', 'orders_refs', 'activate_code', 'password', 'last_request_time', 'login', 'is_online', 'delivery_addresses', 'messages_count'),
					'default' => $default
				);
		}

		public function onCreateObject($e) {
			$object = $e->getRef('object');
			$objectType = umiObjectTypesCollection::getInstance()->getType($object->getTypeId());
			if($objectType->getModule() != "users" || $objectType->getMethod() != "user") {
				return;
			}

			if(!isset($_REQUEST['data']['new']['login'])) {
				$_REQUEST['data']['new']['login'] = $_REQUEST['name'];
			}

			if($e->getMode() == "before") {
					$sel = new umiSelection;
					$sel->addLimit(1);
					$sel->addObjectType($objectType->getId());
					$sel->addNameFilterEquals((string) $_REQUEST['data']['new']['login']);

					if(umiSelectionsParser::runSelectionCounts($sel) > 1) {
						if($object instanceof umiObject) {
							umiObjectsCollection::getInstance()->delObject($object->getId());
						}

						$this->errorRegisterFailPage($this->pre_lang . "/admin/users/add/user/");
						$this->errorNewMessage(getLabel('error-login-exists'), true);
					}
			}
		}

		public function onModifyObject(umiEventPoint $e) {
			static $orig_groups = Array();

			$object = $e->getRef('object');
			$objectId = $object->getId();
			$objectType = umiObjectTypesCollection::getInstance()->getType($object->getTypeId());

			if($objectType->getModule() != "users" || $objectType->getMethod() != "user") {
				return;
			}

			if($e->getMode() == "before") {
				$orig_groups[$objectId] = $object->getValue('groups');
			}

			if($e->getMode() == "after") {
				$permissions = permissionsCollection::getInstance();
				$is_sv = $permissions->isSv($permissions->getUserId());

				if($objectId == SV_USER_ID) {
					$object->setValue("groups", Array(SV_GROUP_ID));
				} else {
					$groups = $object->getValue("groups");
					if(!$is_sv) {
						if(in_array(SV_GROUP_ID, $groups) && !in_array(SV_GROUP_ID, $orig_groups[$objectId])) {
							unset($groups[array_search(SV_GROUP_ID, $groups)]);
							$object->setValue("groups", $groups);
						} else if (!in_array(SV_GROUP_ID, $groups) && in_array(SV_GROUP_ID, $orig_groups[$objectId])){
							$groups[] = SV_GROUP_ID;
							$object->setValue("groups", $groups);
						}
					}
				}
				$object->commit();
			}
		}
	};
?>