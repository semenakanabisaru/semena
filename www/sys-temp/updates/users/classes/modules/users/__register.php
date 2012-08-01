<?php
	abstract class __register_users {

		public function settings($template = "default") {
			if(!$template) $template = "default";

			list($template_block) = def_module::loadTemplates("users/register/".$template, "settings_block");
			$block_arr = Array();
			$block_arr['xlink:href'] = "udata://data/getEditForm/" . $this->user_id;
			$block_arr['user_id'] = $this->user_id;


			return def_module::parseTemplate($template_block, $block_arr, false, $this->user_id);
		}

		public function settings_do($template = "default") {
			$object_id = $this->user_id;

			$password = (string) getRequest('password');

			$oEventPoint = new umiEventPoint("users_settings_do");
			$oEventPoint->setMode("before");
			$oEventPoint->setParam("user_id", $object_id);
			$this->setEventPoint($oEventPoint);

			$object = umiObjectsCollection::getInstance()->getObject($object_id);
			$login = $object->getValue('login');
			if($password) {
				if(strlen($password) < 3) {
					$this->errorNewMessage("%errors_too_short_password%");
					$this->errorPanic();
				}

				if($login == $password) {
					$this->errorNewMessage("%errors_login_equals_password%");
					$this->errorPanic();
				}

				if(!is_null($password_confirm =	getRequest('password_confirm'))) {
					if($password !=	$password_confirm) {
						$this->errorNewMessage("%errors_wrong_password_confirm%");
						$this->errorPanic();
					}
				}

				$object->setValue("password", (($password) ? md5($password) : ""));
				$_SESSION['cms_pass'] = md5($password);
			}

			if (isset($_REQUEST['email'])) {
				if(!preg_match("/.+@.+\..+/", getRequest('email'))) {
					$this->errorNewMessage("%errors_wrong_email_format%");
					$this->errorPanic();
				}

				if(!$this->checkIsUniqueEmail(getRequest('email'), $object_id)) {
					$this->errorNewMessage("%error_users_non_unique_email%");
					$this->errorPanic();
				}

				$object->setValue("e-mail", $_REQUEST['email']);
			}

			$data_module = cmsController::getInstance()->getModule('data');
			$data_module->saveEditedObject($object_id);

			$object->commit();

			if ($eshop_module = cmsController::getInstance()->getModule('eshop')) {
				$eshop_module->discountCardSave($object_id);
			}

			$oEventPoint = new umiEventPoint("users_settings_do");
			$oEventPoint->setMode("after");
			$oEventPoint->setParam("user_id", $object_id);
			$this->setEventPoint($oEventPoint);


			$url = getRequest("from_page");
			if (!$url) {
				$url = ($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $this->pre_lang . "/users/settings/";
			}

			$this->redirect($url);
		}

		public function registrate($template = "default") {
			if(!$template) $template = "default";

			$cmsController = cmsController::getInstance();
			$users = $cmsController->getModule("users");

			if($users instanceof def_module) {
				if($users->is_auth()) {
					$this->redirect($this->pre_lang . "/users/settings/");
				}
			}

			$object_type_id = umiObjectTypesCollection::getInstance()->getBaseType("users", "user");

			list($template_block) = def_module::loadTemplates("users/register/".$template, "registrate_block");

			$block_arr = Array();
			$block_arr['xlink:href'] = "udata://data/getCreateForm/" . $object_type_id;
			$block_arr['type_id'] = $object_type_id;

			return def_module::parseTemplate($template_block, $block_arr);
		}

		public function registrate_do($template = "default") {
			if ($this->is_auth()) {
				$this->redirect($this->pre_lang . "/");
			}
			if(!($template = getRequest('template'))) $template	= 'default';
			$objectTypes = umiObjectTypesCollection::getInstance();
			$regedit = regedit::getInstance();

			$referer_url = getServer('HTTP_REFERER');
			$without_act = (bool) $regedit->getVal("//modules/users/without_act");

			$this->errorRegisterFailPage($referer_url);

			$objectTypeId	= $objectTypes->getBaseType("users",	"user");
			if($customObjectTypeId = getRequest('type-id')) {
				$childClasses = $objectTypes->getChildClasses($objectTypeId);
				if(in_array($customObjectTypeId, $childClasses)) {
					$objectTypeId = $customObjectTypeId;
				}
			}

			$objectType = $objectTypes->getType($objectTypeId);

			$login = (string) getRequest('login');
			$password =	(string) getRequest('password');
			$email = (string) getRequest('email');

			if(strlen($login) < 3) {
				$this->errorNewMessage("%errors_too_short_login%");
				$this->errorPanic();
			}

			if(strlen($login) > 40) {
				$this->errorNewMessage("%errors_too_long_login%");
				$this->errorPanic();
			}

			if(strlen($password) < 3) {
				$this->errorNewMessage("%errors_too_short_password%");
				$this->errorPanic();
			}

			if($login == $password) {
				$this->errorNewMessage("%errors_login_equals_password%");
				$this->errorPanic();
			}

			if(!is_null($password_confirm =	getRequest('password_confirm'))) {
				if($password !=	$password_confirm) {
					$this->errorNewMessage("%errors_wrong_password_confirm%");
					$this->errorPanic();
				}
			}

			if(!preg_match("/.+@.+\..+/", $email)) {
				$this->errorNewMessage("%errors_wrong_email_format%");
				$this->errorPanic();
			}

			$oEventPoint = new umiEventPoint("users_registrate");
			$oEventPoint->setMode("before");
			$oEventPoint->setParam("login",	$login);
			$oEventPoint->addRef("password", $password);
			$oEventPoint->addRef("email", $email);
			$this->setEventPoint($oEventPoint);

			if(!$this->checkIsUniqueEmail($email)) {
				$this->errorNewMessage("%error_users_non_unique_email%");
				$this->errorPanic();
			}

			if(!$without_act &&	($email===false	|| !strlen($email))) {
				$this->redirect($this->pre_lang	. "/users/registrate_done/?result=error");
			}

			$login_field_id = $objectType->getFieldId("login");


			// check captcha
			if (isset($_REQUEST['captcha'])) {
				$_SESSION['user_captcha'] = md5((int) getRequest('captcha'));
			}


			if (!umiCaptcha::checkCaptcha()) {
				$this->errorNewMessage("%errors_wrong_captcha%");
				$this->errorPanic();
			}

			$sel = new umiSelection;
			$sel->addLimit(1);
			$sel->addObjectType($objectTypeId);
			$sel->addPropertyFilterEqual($login_field_id, $login);

			$is_exists = (bool) umiSelectionsParser::runSelectionCounts($sel);

			if($is_exists) {
				$this->errorNewMessage("%err_users_user_exists%");
				$this->errorPanic();
			}

			//Creating user...
			$object_id = umiObjectsCollection::getInstance()->addObject($login, $objectTypeId);
			$activate_code = md5($login . time());

			$object = umiObjectsCollection::getInstance()->getObject($object_id);

			$object->setValue("login", $login);
			$object->setValue("password", md5($password));
			$object->setValue("e-mail", $email);

			$object->setValue("is_activated", $without_act);
			$object->setValue("activate_code", $activate_code);
			$object->setValue("referer", getSession("http_referer"));
			$object->setValue("target", getSession("http_target"));
			$object->setValue("register_date", umiDate::getCurrentTimeStamp());


			if ($without_act) {
				$_SESSION['cms_login'] = $login;
				$_SESSION['cms_pass'] = md5($password);
				$_SESSION['user_id'] = $object_id;

				session_commit();
			}

			$group_id = regedit::getInstance()->getVal("//modules/users/def_group");
			$object->setValue("groups", Array($group_id));

			cmsController::getInstance()->getModule('data');
			$data_module = cmsController::getInstance()->getModule('data');
			$data_module->saveEditedObject($object_id, true);

			$object->commit();

			if ($eshop_module = cmsController::getInstance()->getModule('eshop')) {
				$eshop_module->discountCardSave($object_id);
			}

			//Forming mail...
			list(
				$template_mail, $template_mail_subject, $template_mail_noactivation, $template_mail_subject_noactivation
			) = def_module::loadTemplatesForMail("users/register/".$template,
				"mail_registrated", "mail_registrated_subject", "mail_registrated_noactivation", "mail_registrated_subject_noactivation"
			);

			if($without_act && $template_mail_noactivation && $template_mail_subject_noactivation) {
				$template_mail = $template_mail_noactivation;
				$template_mail_subject = $template_mail_subject_noactivation;
			}

			$mail_arr = Array();
			$mail_arr['user_id'] = $object_id;
			$mail_arr['domain'] = $domain = $_SERVER['HTTP_HOST'];
			$mail_arr['activate_link'] = "http://" . $domain . $this->pre_lang . "/users/activate/" . $activate_code . "/";
			$mail_arr['login'] = $login;
			$mail_arr['password'] = $password;
			$mail_arr['lname'] = $object->getValue("lname");
			$mail_arr['fname'] = $object->getValue("fname");
			$mail_arr['father_name'] = $object->getValue("father_name");

			$mail_content = def_module::parseTemplateForMail($template_mail, $mail_arr, false, $object_id);

			$template_mail_subject = def_module::parseTemplateForMail($template_mail_subject, $mail_arr, false, $object_id);

			$fio = $object->getValue("lname") . " " . $object->getValue("fname") . " " . $object->getValue("father_name");

			$email_from = regedit::getInstance()->getVal("//settings/email_from");
			$fio_from = regedit::getInstance()->getVal("//settings/fio_from");


			$someMail = new umiMail();
			$someMail->addRecipient($email, $fio);
			$someMail->setFrom($email_from, $fio_from);
			$someMail->setSubject($template_mail_subject);
			$someMail->setContent($mail_content);
			$someMail->commit();
			$someMail->send();

			$oEventPoint = new umiEventPoint("users_registrate");
			$oEventPoint->setMode("after");
			$oEventPoint->setParam("user_id", $object_id);
			$this->setEventPoint($oEventPoint);

			if ($without_act) {
				$this->redirect($this->pre_lang . "/users/registrate_done/?result=without_activation");
			} else {
				$this->redirect($this->pre_lang . "/users/registrate_done/");
			}
		}

		public function registrate_done($template = "default") {
			if(!$template) $template = "default";
			$suffix = '';
			switch(getRequest('result')) {
				case 'without_activation': $suffix='_without_activation'; break;
				case 'error'             : $suffix='_error'; break;
				case 'error_user_exists' : $suffix='_user_exists'; break;
			}

			list($template_block) = def_module::loadTemplates("users/register/".$template, "registrate_done_block".$suffix);
			$block_arr = Array();

			return def_module::parseTemplate($template_block, $block_arr);
		}


		public function activate($template = "default") {
			if ($this->is_auth()) $this->redirect("/");

			if(!$template) $template = "default";

			list($template_block, $template_block_failed) = def_module::loadTemplates("users/register/".$template, "activate_block", "activate_block_failed");

			$block_arr = Array();

			$activate_code = (string) getRequest('param0');

			if(!$activate_code || strlen($activate_code) != 32) {
				throw new publicException("%error_activation_code_failed%");
				$template = $template_block_failed;
			}

			$typesCollection = umiObjectTypesCollection::getInstance();

			$object_type_id = $typesCollection->getBaseType("users", "user");
			$object_type = $typesCollection->getType($object_type_id);
			$childTypes = $typesCollection->getChildClasses($object_type_id);


			$activate_code_field_id = $object_type->getFieldId("activate_code");

			$sel = new umiSelection;
			$sel->addLimit(1);
			$sel->addObjectType($object_type_id);
			$sel->addObjectType($childTypes);
			$sel->addPropertyFilterEqual($activate_code_field_id, $activate_code);

			$result = umiSelectionsParser::runSelection($sel);

			if($result) {
				list($user_id) = $result;

				$user = umiObjectsCollection::getInstance()->getObject($user_id);
				$user->setValue("is_activated", 1);
				$user->setValue("activate_code", md5(uniqid(rand(), true)));
				$user->commit();

				permissionsCollection::getInstance()->loginAsUser($result);

				$oEventPoint = new umiEventPoint("users_activate");
				$oEventPoint->setMode("after");
				$oEventPoint->setParam("user_id", $user_id);
				$this->setEventPoint($oEventPoint);

				$template = $template_block;
			} else {
				throw new publicException("%error_activation_code_failed%");
				$template = $template_block_failed;
			}

			return def_module::parseTemplate($template, $block_arr);
		}

		public function onAutoCreateAvatar(iUmiEventPoint $oEventPoint) {
			$user_id = $oEventPoint->getParam("user_id");
			$avatar_type_id = $object_type_id = umiObjectTypesCollection::getInstance()->getBaseType("users", "avatar");
			if($oEventPoint->getMode() != "after") {
				return;
			}

			if($image = umiImageFile::upload("avatar", "user_avatar_file", "./images/cms/data/picture/")) {
				$avatar_id = umiObjectsCollection::getInstance()->addObject("Avatar for user {$user_id}", $avatar_type_id);

				$avatar = umiObjectsCollection::getInstance()->getObject($avatar_id);
				$avatar->setValue("picture", $image);
				$avatar->setValue("is_hidden", true);
				$avatar->commit();

				$user = umiObjectsCollection::getInstance()->getObject($user_id);
				$user->setValue("avatar", $avatar_id);
				$user->commit();

				return true;
			} else {
				return false;
			}
		}


		public function onRegisterAdminMail(iUmiEventPoint $oEventPoint) {
			$regedit = regedit::getInstance();
			$template = "default";

			if($oEventPoint->getMode() == "after") {
				list($template_mail, $template_mail_subject) = def_module::loadTemplatesForMail("users/register/".$template, "mail_admin_registrated", "mail_admin_registrated_subject");

				$email_to = $regedit->getVal("//settings/admin_email");
				$email_from = $regedit->getVal("//settings/email_from");
				$fio_from = $regedit->getVal("//settings/fio_from");

				$object_id = $oEventPoint->getParam('user_id');
				$login = $oEventPoint->getParam('login');

				$mail_arr = Array();
				$mail_arr['user_id'] = $object_id;
				if($login) {
					$mail_arr['login'] = $login;
				}
				$mail_subject = def_module::parseTemplateForMail($template_mail_subject, $mail_arr, false, $object_id);
				$mail_content = def_module::parseTemplateForMail($template_mail, $mail_arr, false, $object_id);

				$someMail = new umiMail();
				$someMail->addRecipient($email_to, $fio_from);
				$someMail->setFrom($email_from, $fio_from);
				$someMail->setSubject($mail_subject);
				$someMail->setContent($mail_content);
				$someMail->commit();
				$someMail->send();
			}
		}
	};
?>