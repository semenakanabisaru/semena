<?php
class users extends def_module {
	public $user_login = "%users_anonymous_login%";
	public $user_id;
	public $user_fullname = "%users_anonymous_fullname%";
	public $groups = "";

	public function __construct() {
		parent::__construct();

		define('SV_GROUP_ID', umiObjectsCollection::getInstance()->getObjectIdByGUID('users-users-15'));
		define('SV_USER_ID', umiObjectsCollection::getInstance()->getObjectIdByGUID('system-supervisor'));

		$commonTabs = $this->getCommonTabs();
		if($commonTabs) {
			$commonTabs->add('users_list_all', array('users_list'));
			$commonTabs->add('groups_list');
		}

		$this->__admin();
		$this->is_auth();
	}

	public function __call($a, $b) {
		return parent::__call($a, $b);
	}

	public function __admin() {
		parent::__admin();

		if(cmsController::getInstance()->getCurrentMode() == "admin" && !class_exists("__imp__" . get_class($this))) {
			$this->__loadLib("__config.php");
			$this->__implement("__config_" . get_class($this));

			$this->__loadLib("__messages.php");
			$this->__implement("__messages_users");

			$this->__loadLib("__custom_adm.php");
			$this->__implement("__custom_adm_users");
		} else {
			$this->__loadLib("__register.php");
			$this->__implement("__register_users");

			$this->__loadLib("__forget.php");
			$this->__implement("__forget_users");

			$this->__loadLib("__profile.php");
			$this->__implement("__profile_users");

			$this->__loadLib("__list.php");
			$this->__implement("__list_users");

			$this->__loadLib("__custom.php");
			$this->__implement("__custom_users");
		}
		$this->__loadLib("__loginza.php");
		$this->__implement("__loginza_" . get_class($this));

		$this->__loadLib("__import.php");
		$this->__implement("__imp__" . get_class($this));

		$this->__loadLib("__author.php");
		$this->__implement("__author_users");

		$this->__loadLib("__settings.php");
		$this->__implement("__settings_users");
	}

	public function login($template = "default") {
		if(!$template) $template = "default";

		$from_page = getRequest('from_page');

		if(!$from_page) {
			$from_page = getServer('REQUEST_URI');
		}

		if (defined("CURRENT_VERSION_LINE") && CURRENT_VERSION_LINE=='demo') {
			list($template_login) = self::loadTemplates("users/".$template, "login_demo");
		} else {
			list($template_login) = self::loadTemplates("users/".$template, "login");
		}

		$block_arr = Array();
		$block_arr['from_page'] = self::protectStringVariable($from_page);

		return self::parseTemplate($template_login, $block_arr);
	}

	public function login_do() {
		$res = "";
		$login = getRequest('login');
		$password = getRequest('password');
		$skin_sel = getRequest('skin_sel');

		$from_page = getRequest('from_page');

		if(!$login) return $this->auth();

		$permissions = permissionsCollection::getInstance();
		$cmsController = cmsController::getInstance();

		$user = $permissions->checkLogin($login, $password);

		if($user instanceof iUmiObject) {
			$permissions->loginAsUser($user);

			if ($permissions->isSv($user->id)) {
				$_SESSION['user_is_sv'] = true;
			}

			$login = $user->getValue('login');

			session_commit();

			$oEventPoint = new umiEventPoint("users_login_successfull");
			$oEventPoint->setParam("user_id", $user->id);
			$this->setEventPoint($oEventPoint);

			if($cmsController->getCurrentMode() == "admin") {
				ulangStream::getLangPrefix();
				system_get_skinName();
				$this->chooseRedirect($from_page);
			} else {
				if(!$from_page) {
					$from_page = getServer('HTTP_REFERER');
				}
				$this->redirect($from_page ? $from_page : ($this->pre_lang . '/users/auth/'));
			}

		} else {
			$oEventPoint = new umiEventPoint("users_login_failed");
			$oEventPoint->setParam("login", $login);
			$oEventPoint->setParam("password", $password);
			$this->setEventPoint($oEventPoint);

			$res = '<p><warning>%users_error_loginfailed%</warning></p>%users auth()%';

			if($cmsController->getCurrentMode() == "admin") {
				throw new publicAdminException(getLabel('label-text-error'));
			} else return $this->auth();
		}

		return $res;
	}


	public function welcome($template = "default") {
		if(!$template) $template = "default";

		if($this->is_auth()) {
			return $this->auth($template);
		} else {
			return "";
		}
	}

	public function auth($template = "default") {
		if(!$template) $template = "default";

		if($this->is_auth()) {
			if(cmsController::getInstance()->getCurrentMode() == "admin") {
				$this->redirect($this->pre_lang . "/admin/");
			} else {
				list($template_logged) = self::loadTemplates("users/".$template, "logged");

				$block_arr = Array();
				$block_arr['xlink:href'] = "uobject://" . $this->user_id;
				$block_arr['user_id'] = $this->user_id;
				$block_arr['user_name'] = $this->user_fullname;
				$block_arr['user_login'] = $this->user_login;

				return self::parseTemplate($template_logged, $block_arr, false, $this->user_id);
			}
		} else {
			$res = $this->login($template);
		}

		return $res;
	}

	public function ping() {

		if (isset($_REQUEST['u-login']) && strlen($_REQUEST['u-login'])>0
			&& isset($_REQUEST['u-password']) && strlen($_REQUEST['u-password'])>0 ) {
				umiAuth::tryPreAuth();
		}

		 $ping = '0';

		//$permissions = permissionsCollection::getInstance();

		if($this->is_auth()) {
			$ping = 'ok';
			$this->updateUserLastRequestTime($this->user_id);
		}
		//session_destroy();

		$buffer = outputBuffer::current();
		$buffer-> contentType('text/html');
		$buffer-> option('generation-time', false);
		$buffer-> clear();
		$buffer-> push($ping);
		$buffer-> end();
	}

	public function is_auth() {
		static $is_auth;

		if($is_auth === false || $is_auth === true) {
			return $is_auth;
		}

		$guest_id = umiObjectsCollection::getInstance()->getObjectIdByGUID('system-guest');

		$user_id = getSession('user_id');
		$login = getSession('cms_login');
		$pass = getSession('cms_pass');

		$this->user_login = "%users_anonymous_login%";
		$this->user_fullname = "%users_anonymous_fullname%";

		if($user_id) {
			if($user_id == $guest_id) {
				$this->user_id = $user_id;
				return $is_auth = false;
			} else {
				$user = umiObjectsCollection::getInstance()->getObject($user_id);
				if($user instanceof umiObject) {
					if($pass != $user->getValue('password')) {
						unset($_SESSION['user_id']);
						$this->user_id = $guest_id;
						return $is_auth = false;
					}

					$login = $user->getValue('login');
					$fname = $user->getValue('fname');
					$lname = $user->getValue('lname');
					$groups = $user->getValue('groups');

					$this->groups = $groups;
					$this->user_id = $user_id;

					$this->user_login = $login;
					$this->user_fullname = "{$fname} {$lname}";

					$this->updateUserLastRequestTime($user_id);

					system_runSession();

					return $is_auth = true;
				}
			}
		}


		if($login && $pass) {
			$objectTypes = umiObjectTypesCollection::getInstance();

			$object_type_id = $objectTypes->getBaseType("users", "user");
			$object_type = $objectTypes->getType($object_type_id);
			$login_field_id = $object_type->getFieldId("login");
			$password_field_id = $object_type->getFieldId("password");

			$sel = new umiSelection;
			$sel->addLimit(1);
			$sel->addObjectType($objectTypes->getChildClasses($object_type_id));
			$sel->addPropertyFilterEqual($login_field_id, $login);
			$sel->addPropertyFilterEqual($password_field_id, $pass);

			$result = umiSelectionsParser::runSelection($sel);
		} else {
			$result = Array();
		}

		if(sizeof($result) == 1) {
			$user_id = $result[0];
			$user_object = umiObjectsCollection::getInstance()->getObject($user_id);

			$login = $user_object->getValue("login");
			$fname = $user_object->getValue("fname");
			$lname = $user_object->getValue("lname");

			$this->updateUserLastRequestTime($user_id);

			$groups = $user_object->getValue("groups");

			$this->groups = $groups;
			$this->user_id = $user_id;

			$this->user_login = $login;
			$this->user_fullname = "{$fname} {$lname}";

			$_SESSION['user_id'] = $user_id;

			system_runSession();

			return $is_auth = true;
		} else {
			$this->user_id = $guest_id;

			$_SESSION['user_id'] = $guest_id;

			return $is_auth = false;
		}
	}


	public function logout() {
		$_SESSION['cms_login'] = "";
		$_SESSION['cms_pass'] = "";
		$_SESSION['user_id'] = false;

		if (isset($_SESSION['u-login'])) unset($_SESSION['u-login']);
		if (isset($_SESSION['u-password'])) unset($_SESSION['u-password']);
		if (isset($_SESSION['u-password-md5'])) unset($_SESSION['u-password-md5']);
		if (isset($_SESSION['u-session-id'])) unset($_SESSION['u-session-id']);

		$b_succ = setcookie('u-login', "", time() - 3600, "/");
		$b_succ = setcookie('u-password', "", time() - 3600, "/");
		$b_succ = setcookie('u-password-md5', "", time() - 3600, "/");

		$b_succ = setcookie(ini_get('session.name'), "", time() - 3600, "/");
		$b_succ = setcookie('umicms_session', "", time() - 3600, "/");

		//ostapenko delete cookie eip from file: /js/client/edit-in-place/compiled.js
		$b_succ = setcookie ('eip-panel-state-first', "", time() - 3600, "/");
		//


		session_destroy();

		$redirect_url = getRequest('redirect_url');
		$this->redirect($redirect_url);
	}


	public static function protectStringVariable($stringVariable = "") {
		$stringVariable = htmlspecialchars($stringVariable);
		return $stringVariable;
	}

	public function getFavourites($userId = false) {
		if(!$userId) {
			$userId = getRequest('param0');
		}
		$objects = umiObjectsCollection::getInstance();
		$permissions = permissionsCollection::getInstance();
		$regedit = regedit::getInstance();
		$currentLangPrefix = ulangStream::getLangPrefix();

		$user = $objects->getObject($userId);
		if($user instanceof iUmiObject == false) return;

		$isTrashAllowed = $permissions->isAllowedMethod($userId, 'data', 'trash');
		$userDockModules = explode(',', $user->user_dock);
		$items = array();
		foreach($userDockModules as $moduleName) {
			if($regedit->getVal('/modules/' . $moduleName) == false && $moduleName != 'trash') continue;

			if($permissions->isAllowedModule(false,	$moduleName) == false)	{
				if($moduleName == 'trash') {
					if($isTrashAllowed == false) continue;
				} else continue;
			}

			$items[] = self::parseTemplate("", array(
				'attribute:id'	=> $moduleName,
				'attribute:label' => getLabel('module-' . $moduleName)
			));
		}

		return self::parseTemplate("", array(
			'subnodes:items'	=> $items
		));
	}

	public function updateUserLastRequestTime($user_id) {
		if($user_id == umiObjectsCollection::getInstance()->getObjectIdByGUID('system-guest')) {
			return false;
		}

		$user_object = umiObjectsCollection::getInstance()->getObject($user_id);

		if($user_object instanceof iUmiObject == false) {
			return false;
		}

		if(cmsController::getInstance()->getCurrentMode() != "admin") {
			$time = time();

			if(($user_object->last_request_time + 60) < $time) {
				$user_object->last_request_time = $time;
				$user_object->commit();
			}
		}
	}

	public function getObjectEditLink($objectId, $type = false) {
		if ($type == 'author') return $this->pre_lang . "/admin/data/guide_item_edit/" . $objectId . "/";
		return $this->pre_lang . "/admin/users/edit/" . $objectId . "/";
	}

	public function checkIsUniqueEmail($email, $userId = false) {
		if(!$email) return false;

		$sel = new selector('objects');
		$sel->types('object-type')->name('users', 'user');
		$sel->where('e-mail')->equals($email);
		$sel->limit(0, 1);

		if($sel->first) {
			return ($userId !== false) ? ($sel->first->id == $userId) : false;
		} else return true;
	}

	public function checkIsUniqueLogin($login, $userId = false) {
		if(!$login) return false;

		$sel = new selector('objects');
		$sel->types('object-type')->name('users', 'user');
		$sel->where('login')->equals($login);
		$sel->limit(0, 1);

		if($sel->first) {
			return ($userId !== false) ? $sel->first->id : false;
		} else return true;
	}
};

?>
