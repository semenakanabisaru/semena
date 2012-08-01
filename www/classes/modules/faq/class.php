<?php

class faq extends def_module {
	public function __construct() {
		parent::__construct();

		if(cmsController::getInstance()->getCurrentMode() == "admin") {
			$this->__loadLib("__admin.php");
			$this->__implement("__faq");
		} else {
			$this->__loadLib("__custom.php");
			$this->__implement("__faq_custom");
		}

		$this->__loadLib("__event_handlers.php");
		$this->__implement("__faq_handlers");

		$regedit = regedit::getInstance();
		$this->per_page = (int) $regedit->getVal("//modules/faq/per_page");
	}

	public function question($template = "default", $element_path = false) {
		list($template_block) = def_module::loadTemplates("faq/".$template, "question");
		$element_id = $this->analyzeRequiredPath($element_path);

		$element = umiHierarchy::getInstance()->getElement($element_id);

		$line_arr = Array();
		if ($element) {
			$line_arr['id'] = $element_id;
			$line_arr['text'] = $element->getName();
			$line_arr['alt_name'] = $element->getAltName();
			$line_arr['link'] = umiHierarchy::getInstance()->getPathById($element_id);
			$line_arr['question'] = nl2br($element->getValue("question"));
			$line_arr['answer'] = ($answer = $element->getValue("answer")) ? nl2br($answer) : nl2br($element->getValue("content"));
		}
		$block_arr = Array();

		$this->pushEditable("faq", "question", $element_id);

		return self::parseTemplate($template_block, $line_arr, $element_id);
	}

	public function project($template = "default", $element_path = false, $limit = false, $ignore_paging = false) {
		list($template_block, $template_block_empty, $template_line) = def_module::loadTemplates("faq/".$template, "categories_block", "categories_block_empty", "categories_block_line");

		$project_id = $this->analyzeRequiredPath($element_path);

		$hierarchy_type_id = umiHierarchyTypesCollection::getInstance()->getTypeByName("faq", "category")->getId();

		$per_page = ($limit) ? $limit : $this->per_page;
		$curr_page = (int) getRequest('p');
		if($ignore_paging) $curr_page = 0;

		$sel = new umiSelection;
		$sel->addElementType($hierarchy_type_id);
		$sel->addHierarchyFilter($project_id);
		$sel->addLimit($per_page, $curr_page);
		$sel->addPermissions();
		$result = umiSelectionsParser::runSelection($sel);
		$total = umiSelectionsParser::runSelectionCounts($sel);


		if(($sz = sizeof($result)) > 0) {
			$block_arr = Array();
			$block_arr['total'] = $total;
			$lines = Array();
			for($i = 0; $i < $sz; $i++) {
				if ($i < $limit || $limit === false) {
					$element_id = $result[$i];
					$element = umiHierarchy::getInstance()->getElement($element_id);

					if(!$element) continue;

					$line_arr = Array();
					$line_arr['attribute:id'] = $element_id;
					$line_arr['attribute:name'] = $line_arr['void:text'] = $element->getName();
					$line_arr['void:alt_name'] = $element->getAltName();
					$line_arr['attribute:link'] = umiHierarchy::getInstance()->getPathById($element_id);
					$line_arr['xlink:href'] = "upage://" . $element_id;

					$this->pushEditable("faq", "category", $element_id);

					$lines[] = self::parseTemplate($template_line, $line_arr, $element_id);
				}
			}

			$block_arr['subnodes:lines'] = $lines;

			$block_arr['per_page'] = $this->per_page;
			$block_arr['total']    = $total;

			$this->generateNumPage ($total, $per_page);

			return self::parseTemplate($template_block, $block_arr, $project_id);
		} else {
			return $template_block_empty;
		}
	}

	public function category($template = "default", $element_path = false, $limit = false, $ignore_paging = false, $order = true) {
		if(!$template) $template = "default";
		list($template_block, $template_block_empty, $template_line) = def_module::loadTemplates("faq/".$template, "questions_block", "questions_block_empty", "questions_block_line");

		$category_id = $this->analyzeRequiredPath($element_path);

		$hierarchy_type_id = umiHierarchyTypesCollection::getInstance()->getTypeByName("faq", "question")->getId();

		$per_page = ($limit) ? $limit : $this->per_page;
		$curr_page = (int) getRequest('p');
		if($ignore_paging) $curr_page = 0;


		$sel = new umiSelection;
		$sel->addElementType($hierarchy_type_id);
		$sel->addHierarchyFilter($category_id);
		$sel->addLimit($per_page, $curr_page);
		$sel->addPermissions();
		$sel->setOrderByOrd((bool) $order);
		$result = umiSelectionsParser::runSelection($sel);
		$total = umiSelectionsParser::runSelectionCounts($sel);

		if(($sz = sizeof($result)) > 0) {
			$block_arr = Array();
			$block_arr['total'] = $total;
			$block_arr['per_page'] = $per_page;
			$lines = Array();
			for($i = 0; $i < $sz; $i++) {
				if ($i < $limit || $limit === false) {
					$element_id = $result[$i];
					$element = umiHierarchy::getInstance()->getElement($element_id);

					if(!$element) continue;

					$line_arr = Array();
					$line_arr['attribute:id'] = $element_id;
					$line_arr['attribute:name'] = $line_arr['void:text'] = $element->getName();
					$line_arr['void:alt_name'] = $element->getAltName();
					$line_arr['attribute:link'] = umiHierarchy::getInstance()->getPathById($element_id);
					$line_arr['xlink:href'] = "upage://" . $element_id;
					$line_arr['question'] = nl2br($element->getValue("question"));
					$line_arr['answer'] = ($answer = $element->getValue("answer")) ? nl2br($answer) : nl2br($element->getValue("content"));
					$this->pushEditable("faq", "question", $element_id);

					$lines []= self::parseTemplate($template_line, $line_arr, $element_id);
				}
			}

			$block_arr['subnodes:items'] = $block_arr['void:lines'] = $lines;

			return self::parseTemplate($template_block, $block_arr, $category_id);
		} else {
			return $template_block_empty;
		}
	}

	public function projects($template = "default", $limit = false, $ignore_paging = false) {
		list($template_block, $template_block_empty, $template_line) = def_module::loadTemplates("faq/".$template, "projects_block", "projects_block_empty", "projects_block_line");

		$hierarchy_type_id = umiHierarchyTypesCollection::getInstance()->getTypeByName("faq", "project")->getId();

		$per_page = ($limit) ? $limit : $this->per_page;
		$curr_page = (int) getRequest('p');
		if($ignore_paging) $curr_page = 0;

		$sel = new umiSelection;
		$sel->addElementType($hierarchy_type_id);
		$sel->addLimit($per_page, $curr_page);
		$sel->addPermissions();
		$result = umiSelectionsParser::runSelection($sel);
		$total = umiSelectionsParser::runSelectionCounts($sel);

		if(($sz = sizeof($result)) > 0) {
			$block_arr = Array();

			$lines = Array();
			for($i = 0; $i < $sz; $i++) {
				if ($i < $limit || $limit === false) {
					$element_id = $result[$i];
					$element = umiHierarchy::getInstance()->getElement($element_id);

					if(!$element) continue;

					$line_arr = Array();
					$line_arr['attribute:id'] = $element_id;
					$line_arr['attribute:name'] = $line_arr['void:text'] = $element->getName();
					$line_arr['void:alt_name'] = $element->getAltName();
					$line_arr['attribute:link'] = $element->link;
					$line_arr['xlink:href'] = "upage://" . $element_id;

					$this->pushEditable("faq", "project", $element_id);
					$lines[] = self::parseTemplate($template_line, $line_arr, $element_id);
				}
			}

			$block_arr['subnodes:lines'] = $lines;
			$block_arr['total'] = $total;
			$block_arr['per_page'] = $per_page;

			return self::parseTemplate($template_block, $block_arr);
		} else {
			return $template_block_empty;
		}
	}

	public function addQuestionForm($template="default", $category_path=false) {
		list($template_add_user, $template_add_guest) = def_module::loadTemplates("faq/".$template, "question_add_user", "question_add_guest");

		$category_id = $this->analyzeRequiredPath($category_path);

		if(cmsController::getInstance()->getModule("users")->is_auth()) {
			$template_add = $template_add_user;
		} else {
			$template_add = $template_add_guest;
		}
		$block_arr['action'] = $this->pre_lang . "/faq/post_question/" . $category_id . "/";

		return self::parseTemplate($template_add, $block_arr, $category_id);
	}

	public function post_question() {

		$iPosted= getRequest('posted');
		if(is_null($tickets = getSession('tickets'))) {
			$tickets = $_SESSION['tickets'] = Array();
		}

		if ($iPosted) {
			$sPosted = getArrayKey($tickets, $iPosted);
			return $sPosted;
		}

		$referer_url = getServer('HTTP_REFERER');
		$this->errorRegisterFailPage($referer_url);

		$parent_element_id = (int) getRequest('param0');
		// input
		$email = htmlspecialchars(getRequest('email'));
		$nick = htmlspecialchars(getRequest('nick'));
		$title = htmlspecialchars(getRequest('title'));
		$question = htmlspecialchars(getRequest('question'));
		$ip = $_SERVER['REMOTE_ADDR'];

		if(!strlen($title)) {
			$this->errorNewMessage("%error_faq_required_title%");
			$this->errorPanic();
		}

		if(!strlen($question)) {
			$this->errorNewMessage("%error_faq_required_question%");
			$this->errorPanic();
		}

		if(!strlen($email)) {
			$users = cmsController::getInstance()->getModule("users");
			if($users instanceof def_module) {
				$user_id = $users->user_id;
				if($user = umiObjectsCollection::getInstance()->getObject($user_id)) {
					$email = $user->getValue('e-mail');
				}
			}
		}

		$referer_url = (string) $_SERVER['HTTP_REFERER'];
		$posttime = time();
		$ip = $_SERVER['REMOTE_ADDR'];

		if (isset($_REQUEST['captcha'])) {
			$_SESSION['user_captcha'] = md5((int) getRequest('captcha'));
		}

		if (!umiCaptcha::checkCaptcha()) {
			$this->errorNewMessage("%errors_wrong_captcha%");
			$this->errorPanic();
		}

		// before add event point
		$oEventPoint = new umiEventPoint("faq_post_question");
		$oEventPoint->setMode("before");
		$oEventPoint->setParam("parent_element_id", $parent_element_id);
		$oEventPoint->setParam("test_captcha", umiCaptcha::checkCaptcha());

		$this->setEventPoint($oEventPoint);

		// check captcha
		if (!umiCaptcha::checkCaptcha() || !$parent_element_id) {
			$this->redirect($referer_url);
		}

		$is_active = 0;

		if($oUsers = cmsController::getInstance()->getModule("users")) {
			if($oUsers->is_auth()) {
				$user_id = cmsController::getInstance()->getModule('users')->user_id;
				$iAuthorId = $oUsers->createAuthorUser($user_id);
				$is_active = $oUsers->isSv($user_id);
			} else {
				$iAuthorId = $oUsers->createAuthorGuest($nick, $email, $ip);
			}
		}

		$object_type_id = umiObjectTypesCollection::getInstance()->getBaseType("faq", "question");
		$hierarchy_type_id = umiHierarchyTypesCollection::getInstance()->getTypeByName("faq", "question")->getId();

		$parentElement = umiHierarchy::getInstance()->getElement($parent_element_id);
		$tpl_id		= $parentElement->getTplId();
		$domain_id	= $parentElement->getDomainId();
		$lang_id	= $parentElement->getLangId();

		$element_id = umiHierarchy::getInstance()->addElement($parent_element_id, $hierarchy_type_id, $title, $title, $object_type_id, $domain_id, $lang_id, $tpl_id);

		permissionsCollection::getInstance()->setDefaultPermissions($element_id);

		$element = umiHierarchy::getInstance()->getElement($element_id);

		$element->setIsActive(false);
		$element->setIsVisible(false);

		$element->setValue("question", $question);
		$element->setValue("publish_time", $posttime);

		$element->getObject()->setName($title);
		$element->setValue("h1", $title);

		$element->setValue("author_id", $iAuthorId);
		$element->commit();

		// send mails

		$from = regedit::getInstance()->getVal("//settings/fio_from");
		$from_email = regedit::getInstance()->getVal("//settings/email_from");
		$admin_email = regedit::getInstance()->getVal("//settings/admin_email");

		list(
			$confirm_mail_subj_user, $confirm_mail_user, $confirm_mail_subj_admin, $confirm_mail_admin
		) = def_module::loadTemplatesForMail("faq/default",
			"confirm_mail_subj_user", "confirm_mail_user", "confirm_mail_subj_admin", "confirm_mail_admin"
		);

		// for admin
		$mail_arr = Array();
		$mail_arr['domain'] = $domain = $_SERVER['HTTP_HOST'];
		$mail_arr['question'] = $question;
		$mail_arr['question_link'] = "http://" . $domain . $this->pre_lang. "/admin/faq/edit/" . $element_id . "/";
		$mail_adm_subj = def_module::parseTemplateForMail($confirm_mail_subj_admin, $mail_arr);
		$mail_adm_content = def_module::parseTemplateForMail($confirm_mail_admin, $mail_arr);

		$confirmAdminMail = new umiMail();
		$confirmAdminMail->addRecipient($admin_email);
		$confirmAdminMail->setFrom($email, $nick);
		$confirmAdminMail->setSubject($mail_adm_subj);
		$confirmAdminMail->setContent($mail_adm_content);
		$confirmAdminMail->commit();
		$confirmAdminMail->send();

		// for user
		$user_mail = Array();
		$user_mail['domain'] = $domain = $_SERVER['HTTP_HOST'];
		$user_mail['question'] = $question;
		$user_mail['ticket'] = $element_id;
		$mail_usr_subj = def_module::parseTemplateForMail($confirm_mail_subj_user, $user_mail);
		$mail_usr_content = def_module::parseTemplateForMail($confirm_mail_user, $user_mail);

		$confirmMail = new umiMail();
		$confirmMail->addRecipient($email);
		$confirmMail->setFrom($from_email, $from);
		$confirmMail->setSubject($mail_usr_subj);
		$confirmMail->setContent($mail_usr_content);
		$confirmMail->commit();
		$confirmMail->send();

		// after add event point
		$oEventPoint = new umiEventPoint("faq_post_question");
		$oEventPoint->setMode("after");
		$oEventPoint->setParam("element_id", $element_id);
		$this->setEventPoint($oEventPoint);

		$_SESSION['tickets'][$element_id] = $mail_usr_content;
		$this->redirect($this->pre_lang . '/faq/post_question/?posted=' . $element_id);
	}

	public function onQuestionPost(iUmiEventPoint $event) {
		if($event->getMode() == 'after') {
			$questionId = $event->getParam('element_id');
			antiSpamHelper::checkForSpam($questionId, 'question');
		}
	}

	public function getEditLink($element_id, $element_type) {
		$element = umiHierarchy::getInstance()->getElement($element_id);
		$parent_id = $element->getParentId();

		switch($element_type) {
			case "project": {
				$link_add = $this->pre_lang . "/admin/faq/add/{$element_id}/category/";
				$link_edit = $this->pre_lang . "/admin/faq/edit/{$element_id}/";

				return Array($link_add, $link_edit);
				break;
			}

			case "category": {
				$link_add = $this->pre_lang . "/admin/faq/add/{$element_id}/question/";
				$link_edit = $this->pre_lang . "/admin/faq/edit/{$element_id}/";

				return Array($link_add, $link_edit);
				break;
			}

			case "question": {
				$link_edit = $this->pre_lang . "/admin/faq/edit/{$element_id}/";

				return Array(false, $link_edit);
				break;
			}

			default: {
				return false;
			}
		}
	}
}

?>