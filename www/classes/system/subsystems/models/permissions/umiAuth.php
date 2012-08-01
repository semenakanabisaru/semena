<?php
class umiAuth extends singleton implements iSingleton, iUmiAuth {
	/*
	*/

	// ==== constants : ====================================================

	const PREAUTH_INVALID = 0;
	const PREAUTH_SUCCESS_NEW = 1;
	const PREAUTH_SUCCESS_RESTORE = 2;
	const PREAUTH_ALREADY = 3;
	const PREAUTH_NEEDNOT = 4;

	// ==== singleton : ====================================================

	public function __construct() {
	}

	public static function getInstance($c = NULL) {
		return parent::getInstance(__CLASS__);
	}

	// ==== methods : ======================================================

	public function tryPreAuth() {
		/*
		*/

		// ==== init variables : ===================
		$s_login = "";
		$s_password_md5 = "";
		$s_session_expected = "";
		//
		$s_field_login = 'u-login';
		$s_field_password = 'u-password';
		$s_field_password_md5 = 'u-password-md5';
		$s_field_session_id = 'u-session-id';
		$s_field_session_name = ini_get("session.name");

		// ==== process cookies : ==================
		if($s_login = getCookie($s_field_login)) {
			if($s_password_md5 = getCookie($s_field_password)) {
				$s_password_md5 = md5($s_password_md5);
			} else {
				$s_password_md5 = getCookie($s_field_password_md5);
			}
		}

		// ==== process headers : ==================
		if (function_exists('apache_request_headers')) {
			$arr_headers = apache_request_headers();
			if (isset($arr_headers[$s_field_login])) {
				$s_login = umiObjectProperty::filterInputString(str_replace(chr(0), "", $arr_headers[$s_field_login]));
			}
			if (isset($arr_headers[$s_field_password_md5])) {
				$s_password_md5 = umiObjectProperty::filterInputString(str_replace(chr(0), "", $arr_headers[$s_field_password_md5]));
			} elseif (isset($arr_headers[$s_field_password])) {
				$s_password_md5 = md5(umiObjectProperty::filterInputString(str_replace(chr(0), "", $arr_headers[$s_field_password])));
			}
			if (isset($arr_headers[$s_field_session_id])) {
				$s_session_expected = umiObjectProperty::filterInputString(str_replace(chr(0), "", $arr_headers[$s_field_session_id]));
			}
		}

		// ==== process request params : ===========

		// post :
		if (isset($_POST[$s_field_login])) {
			$s_login = umiObjectProperty::filterInputString(str_replace(chr(0), "", $_POST[$s_field_login]));
		}
		if (isset($_POST[$s_field_password_md5])) {
			$s_password_md5 = umiObjectProperty::filterInputString(str_replace(chr(0), "", $_POST[$s_field_password_md5]));
		} elseif (isset($_POST[$s_field_password])) {
			$s_password_md5 = md5(umiObjectProperty::filterInputString(str_replace(chr(0), "", $_POST[$s_field_password])));
		}
		if (isset($_POST[$s_field_session_id])) {
			$s_session_expected = umiObjectProperty::filterInputString(str_replace(chr(0), "", $_POST[$s_field_session_id]));
		}
		// get :
		if (isset($_GET[$s_field_login])) {
			$s_login = umiObjectProperty::filterInputString(str_replace(chr(0), "", $_GET[$s_field_login]));
		}
		if (isset($_GET[$s_field_password_md5])) {
			$s_password_md5 = umiObjectProperty::filterInputString(str_replace(chr(0), "", $_GET[$s_field_password_md5]));
		} elseif (isset($_GET[$s_field_password])) {
			$s_password_md5 = md5(umiObjectProperty::filterInputString(str_replace(chr(0), "", $_GET[$s_field_password])));
		}
		if (isset($_GET[$s_field_session_id])) {
			$s_session_expected = umiObjectProperty::filterInputString(str_replace(chr(0), "", $_GET[$s_field_session_id]));
		}

		// ==== try to authorize : =================
		if (strlen($s_login) && strlen($s_password_md5)) {
			$i_object_type_id = umiObjectTypesCollection::getInstance()->getBaseType("users", "user");
			$o_object_type = umiObjectTypesCollection::getInstance()->getType($i_object_type_id);

			$i_login_field_id = $o_object_type->getFieldId("login");
			$i_password_field_id = $o_object_type->getFieldId("password");
			$i_is_active_id = $o_object_type->getFieldId("is_activated");

			$sel = new umiSelection;

			$sel->setLimitFilter();
			$sel->addLimit(1);

			$sel->setObjectTypeFilter();
			$sel->addObjectType($i_object_type_id);

			$sel->setPropertyFilter();

			$sel->addPropertyFilterEqual($i_login_field_id, $s_login);
			$sel->addPropertyFilterEqual($i_password_field_id, $s_password_md5);
			$sel->addPropertyFilterEqual($i_is_active_id, 1);

			$result = umiSelectionsParser::runSelection($sel);

			if(sizeof($result) === 1) {

				$i_user_id = intval($result[0]);

				if (!session_id())
				{
                         session_start();
                    }

				$s_curr_session = session_id();


				system_runSession();



				// maybe already authorized :
				if (strlen($s_curr_session) && isset($_SESSION) && isset($_SESSION['cms_login']) && isset($_SESSION['cms_pass']) && isset($_SESSION['user_id']) && $_SESSION['cms_login'] === $s_login && $_SESSION['cms_pass'] === $s_password_md5 && $_SESSION['user_id'] === $i_user_id) {
                         $_SESSION['starttime']=time();
					return self::PREAUTH_ALREADY; // RETURN
				}

				// try to restore
				if (strlen($s_session_expected)) {

					// stop current session :
					if (strlen($s_curr_session)) {
						session_destroy();
					}

					// restore expected :
					session_id($s_session_expected);
					session_start(); $_SESSION['starttime']=time();


					// control restored (mr Walles likes ms Walles only) :
					if (!(isset($_SESSION['cms_login']) && isset($_SESSION['cms_pass']) && isset($_SESSION['user_id']) && $_SESSION['cms_login'] === $s_login && $_SESSION['cms_pass'] === $s_password_md5 && $_SESSION['user_id'] === $i_user_id)) {
						session_destroy();
					} else {
						$o_event_point = new umiEventPoint("users_prelogin_successfull");
						$o_event_point->setParam("prelogin_mode", self::PREAUTH_SUCCESS_RESTORE);
						$o_event_point->setParam("user_id", $i_user_id);
						umiEventsController::getInstance()->callEvent($o_event_point);

						return self::PREAUTH_SUCCESS_RESTORE; // RETURN
					}
				} else {
					@session_start(); // эта строчка должна выглядеть именно так (вопрос объединения корзин при авторизации)

					$_SESSION['cms_login'] = $s_login;
					$_SESSION['cms_pass'] = $s_password_md5;
					$_SESSION['user_id'] = $i_user_id;

                    $permissions = permissionsCollection::getInstance();
                    if($permissions->isSv($i_user_id)) {
						$_SESSION['user_is_sv'] = true;
                    }

					session_commit();
					session_start();

					$_SESSION['starttime']=time();

					$o_event_point = new umiEventPoint("users_prelogin_successfull");
					$o_event_point->setParam("prelogin_mode", self::PREAUTH_SUCCESS_NEW);
					$o_event_point->setParam("user_id", $i_user_id);
					umiEventsController::getInstance()->callEvent($o_event_point);

					// ==== memorize me : ====
					if (isset($_REQUEST['u-login-store']) && (intval($_REQUEST['u-login-store']) || strtoupper($_REQUEST['u-login-store']) === 'ON')) {
						setcookie($s_field_login, $s_login, (time() + (60 * 60 * 24 * 31)), "/");
						setcookie($s_field_password_md5, $s_password_md5, (time() + (60 * 60 * 24 * 31)), "/");
					}
					return self::PREAUTH_SUCCESS_NEW; // RETURN
				}
			}
		}

		// default return :
		return self::PREAUTH_INVALID; // RETURN
	}
}
?>