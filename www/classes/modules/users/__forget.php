<?php
	abstract class __forget_users {
		public function forget($template = "default") {
			list($template_block) = def_module::loadTemplates("users/forget/".$template, "forget_block");
			$block_arr = Array();
			return def_module::parseTemplate($template_block, $block_arr);
		}


		public function forget_do($template = "default") {
			static $macrosResult;
			if($macrosResult) return $macrosResult;

			$forget_login = (string) getRequest('forget_login');
			$forget_email = (string) getRequest('forget_email');

			$hasLogin = strlen($forget_login) != 0;
			$hasEmail = strlen($forget_email) != 0;

			$user_id = false;

			list(
				$template_wrong_login_block, $template_forget_sended, $template_mail_verification, $template_mail_verification_subject
			) = def_module::loadTemplatesForMail("users/forget/".$template,
				"wrong_login_block", "forget_sended", "mail_verification", "mail_verification_subject"
			);

			if ($hasLogin || $hasEmail) {
				$sel = new selector('objects');
				$sel->types('object-type')->name('users', 'user');
				if($hasLogin) $sel->where('login')->equals($forget_login);
				if($hasEmail) $sel->where('e-mail')->equals($forget_email);
				$sel->limit(0, 1);

				$user_id = ($sel->first) ? $sel->first->id : false;
			}
			else $user_id = false;

			if ($user_id) {
				$activate_code = md5(self::getRandomPassword());

				$object = umiObjectsCollection::getInstance()->getObject($user_id);

				$regedit = regedit::getInstance();
				$without_act = (bool) $regedit->getVal("//modules/users/without_act");
				if ($without_act || intval($object->getValue('is_activated'))) {
					$object->setValue("activate_code", $activate_code);
					$object->commit();

					$email = $object->getValue("e-mail");
					$fio = $object->getValue("lname") . " " . $object->getValue("fname") . " " . $object->getValue("father_name");

					$email_from = regedit::getInstance()->getVal("//settings/email_from");
					$fio_from = regedit::getInstance()->getVal("//settings/fio_from");

					$mail_arr = Array();
					$mail_arr['domain'] = $domain = $_SERVER['HTTP_HOST'];
					$mail_arr['restore_link'] = "http://" . $domain . $this->pre_lang . "/users/restore/" . $activate_code . "/";
					$mail_arr['login'] = $object->getValue('login');
					$mail_arr['email'] = $object->getValue('e-mail');

					$mail_subject = def_module::parseTemplateForMail($template_mail_verification_subject, $mail_arr, false, $user_id);
					$mail_content = def_module::parseTemplateForMail($template_mail_verification, $mail_arr, false, $user_id);

					$someMail = new umiMail();
					$someMail->addRecipient($email, $fio);
					$someMail->setFrom($email_from, $fio_from);
					$someMail->setSubject($mail_subject);
					$someMail->setPriorityLevel("highest");
					$someMail->setContent($mail_content);
					$someMail->commit();
					$someMail->send();

					$oEventPoint = new umiEventPoint("users_restore_password");
					$oEventPoint->setParam("user_id", $user_id);
					$this->setEventPoint($oEventPoint);

					$block_arr = Array();
					$block_arr['attribute:status'] = "success";
					return $macrosResult = def_module::parseTemplate($template_forget_sended, $block_arr);
				} else {
					$referer_url = getServer('HTTP_REFERER');
					if (!strlen($referer_url)) $referer_url = $this->pre_lang . "/users/forget/";
					$this->errorRegisterFailPage($referer_url);
					$this->errorNewMessage("%errors_forget_nonactivated_login%");
					$this->errorPanic();

					$block_arr = Array();
					$block_arr['attribute:status'] = "fail";
					$block_arr['forget_login'] = $forget_login;
					$block_arr['forget_email'] = $forget_email;
					return $macrosResult = def_module::parseTemplate($template_wrong_login_block, $block_arr);
				}
			} else {
				$referer_url = getServer('HTTP_REFERER');
				if (!strlen($referer_url)) $referer_url = $this->pre_lang . "/users/forget/";
				$this->errorRegisterFailPage($referer_url);
				if ($hasLogin && !$hasEmail) $this->errorNewMessage("%errors_forget_wrong_login%");
				if ($hasEmail && !$hasLogin) $this->errorNewMessage("%errors_forget_wrong_email%");
				if (($hasEmail && $hasLogin) || (!$hasEmail && !$hasLogin)) $this->errorNewMessage("%errors_forget_wrong_person%");
				$this->errorPanic();

				$block_arr = Array();
				$block_arr['attribute:status'] = "fail";
				$block_arr['forget_login'] = $forget_login;
				$block_arr['forget_email'] = $forget_email;
				return $macrosResult = def_module::parseTemplate($template_wrong_login_block, $block_arr);
			}
		}


		public function restore($activate_code = false, $template = "default") {
			static $result = Array();

			if(isset($result[$template])) {
				return $result[$template];
			}

			list(
				$template_restore_failed_block, $template_restore_ok_block, $template_mail_password, $template_mail_password_subject
			) = def_module::loadTemplatesForMail("users/forget/".$template,
				"restore_failed_block", "restore_ok_block", "mail_password", "mail_password_subject"
			);

			if(!$activate_code) {
				$activate_code = (string) getRequest('param0');
				$activate_code = trim($activate_code);
			}
			
			$object_type_id = umiObjectTypesCollection::getInstance()->getBaseType("users", "user");
			$object_type = umiObjectTypesCollection::getInstance()->getType($object_type_id);
			$activate_code_field_id = $object_type->getFieldId("activate_code");

			$sel = new selector('objects');
			$sel->types('object-type')->name('users', 'user');
			$sel->where('activate_code')->equals($activate_code);
			$sel->limit(0, 1);


			if($sel->first) {
				$object = $sel->first;
				$user_id = $object->id;
			} else {
				$object = false;
				$user_id = false;
			}

			$block_arr = Array();

			if($user_id && $activate_code) {
				$password = self::getRandomPassword();

				$login = $object->getValue("login");
				$email = $object->getValue("e-mail");
				$fio  = $object->getValue("lname") . " " . $object->getValue("fname") . " " . $object->getValue("father_name");

				$object->setValue("password", md5($password));
				$object->setValue("activate_code", "");
				$object->commit();

				$email_from = regedit::getInstance()->getVal("//settings/email_from");
				$fio_from = regedit::getInstance()->getVal("//settings/fio_from");

				$mail_arr = Array();
				$mail_arr['domain'] = $domain = $_SERVER['HTTP_HOST'];
				$mail_arr['password'] = $password;
				$mail_arr['login'] = $login;

				$mail_subject = def_module::parseTemplateForMail($template_mail_password_subject, $mail_arr, false, $user_id);
				$mail_content = def_module::parseTemplateForMail($template_mail_password, $mail_arr, false, $user_id);

				$someMail = new umiMail();
				$someMail->addRecipient($email, $fio);
				$someMail->setFrom($email_from, $fio_from);
				$someMail->setSubject($mail_subject);
				$someMail->setContent($mail_content);
				$someMail->commit();
				$someMail->send();

				$block_arr['attribute:status'] = "success";
				$block_arr['login'] = $login;
				$block_arr['password'] = $password;
				return $result[$template] = def_module::parseTemplate($template_restore_ok_block, $block_arr, false, $user_id);
			} else {
				$block_arr['attribute:status'] = "fail";
				return $result[$template] = def_module::parseTemplate($template_restore_failed_block, $block_arr);
			}
		}


		public static function getRandomPassword ($length = 12) {
			$avLetters = "$#@^&!1234567890qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM";
			$size = strlen($avLetters);


			$npass = "";
			for($i = 0; $i < $length; $i++) {
				$c = rand(0, $size - 1);
				$npass .= $avLetters[$c];
			}
			return $npass;
		}
	};

?>