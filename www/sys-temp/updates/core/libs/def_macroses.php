<?php

	/**
	 * @deprecated
	 * Больше не используется в системе
	 */
	function macros_content() {
		static $res;
		if(!is_null($res)) return $res;

		$cmsController = cmsController::getInstance();

		$current_module = $cmsController->getCurrentModule();
		$current_method = $cmsController->getCurrentMethod();


		$previousValue = $cmsController->isContentMode;
		$cmsController->isContentMode = true;

		if($module = $cmsController->getModule($current_module)) {
			$pid = $cmsController->getCurrentElementId();
			$permissions = permissionsCollection::getInstance();
			$templater = $cmsController->getCurrentTemplater();
			$isAdmin = $permissions->isAdmin();

			if($pid) {
				list($r, $w) = $permissions->isAllowedObject($permissions->getUserId(), $pid);
				if($r) {
					$is_element_allowed = true;
				} else {
					$is_element_allowed = false;
				}
			} else {
				$is_element_allowed = true;
			}

			if(system_is_allowed($current_module, $current_method) && ($is_element_allowed)) {
				if($parsedContent = $cmsController->parsedContent) {
					return $parsedContent;
				}
				if($cmsController->getCurrentMode() == "admin") {
					try {
						if (!$templater->getIsInited()) {
							$res = $module->cms_callMethod($current_method, NULL);
						}
					} catch (publicException $e) {
						$templater->setDataSet($e);
						return $res = false;
					}
				} else {
					try {
						$res = $module->cms_callMethod($current_method, NULL);
					} catch (publicException $e) {
						$res = $e->getMessage();
					}
					$res = system_parse_short_calls($res);
					$res = str_replace("%content%", "%content ", $res);

					$res = templater::getInstance()->parseInput($res);

					$res = system_parse_short_calls($res);
				}

				if($res !== false && is_array($res) == false) {
					if($cmsController->getCurrentMode() != "admin" && stripos($res, "%cut%") !== false) {
						if(array_key_exists("cut", $_REQUEST)) {
							if($_REQUEST['cut'] == "all") {
								$_REQUEST['cut_pages'] = 0;
								return str_ireplace("%cut%", "", $res);
							}
							$cut = (int) $_REQUEST['cut'];
						} else $cut = 0;

						$res_arr = spliti("%cut%", $res);

						if($cut > (sizeof($res_arr) - 1))
							$cut = sizeof($res_arr) - 1;
						if($cut < 0)
							$cut = 0;

						$_REQUEST['cut_pages'] = sizeof($res_arr);
						$_REQUEST['cut_curr_page'] = $cut;

						$res = $res_arr[$cut];
					}

					$cmsControllerInstance = $cmsController;
					$cmsControllerInstance->parsedContent = $res;
					$cmsController->isContentMode = $previousValue;
					return $res;
				}
				else {
					$cmsController->isContentMode = $previousValue;
					return $res = '<notice>%core_templater% %core_error_nullvalue%</notice>';
				}
			} else {
				if($cmsController->getCurrentMode() == "admin" && $isAdmin) {
					if($current_module == "content" && $current_method == "sitetree") {
						$regedit = regedit::getInstance();

						$modules = $regedit->getList("//modules");
						foreach($modules as $item) {
							list($module) = $item;
							if(system_is_allowed($module) && $module != 'content') {
								$module_inst = $cmsController->getModule($module);
								$url = $module_inst->pre_lang . "/admin/" . $module . "/";
								$module_inst->redirect($url);
							}
						}
					}
				}


				if($module = $cmsController->getModule("users")) {
					header("Status: 401 Unauthorized");

					$cmsController->setCurrentModule("users");
					$cmsController->setCurrentMethod("login");
					$cmsController->isContentMode = $previousValue;

					if($isAdmin) {
						$module = $cmsController->getModule($current_module);
						if($module->isMethodExists($current_method) == false) {
							$url = $module_inst->pre_lang . "/admin/content/sitetree/";
							$module->redirect($url);
						}
						if($cmsController->getCurrentMode() == "admin") {
							$e = new requreMoreAdminPermissionsException(getLabel("error-require-more-permissions"));
							$templater->setDataSet($e);
						}

						return $res = "<p><warning>%core_error_nopermission%</warning></p>" . $module->login();
					}
					else {
						if ($templater instanceof xslAdminTemplater && $templater->getParsed()) {
							throw new requireAdminPermissionsException("No permissions");
						}
						return $res = $module->login();
					}
				}

				return $res = '<warning>%core_templater% %core_error_nullvalue% %core_error_nopermission%</warning>';
			}

		}
		$cmsController->isContentMode = $previousValue;
		return $res = '%core_templater% %core_error_unknown%';
	}


	function macros_title() {
		$cmsController = cmsController::getInstance();
		$hierarchy = umiHierarchy::getInstance();
		$regedit = regedit::getInstance();

		if($cmsController->getCurrentMode() == "") {
			if($elementId = $cmsController->getCurrentElementId()) {
				if($element = $hierarchy->getElement($elementId)) {
					if($title = $element->getValue("title")) {
						return $title;
					}
				}
			}
		}

		if($cmsController->currentTitle) {
			return $cmsController->currentTitle;
		}

		$domainId = $cmsController->getCurrentDomain()->getId();
		$langId = $cmsController->getCurrentLang()->getId();
		$titlePrefix = $regedit->getVal("//settings/title_prefix/" . $langId . "/" . $domainId);

		if (strpos($titlePrefix, "%title_string%") !== false) {
			return str_replace("%title_string%", macros_header(), $titlePrefix);
		}

		return $titlePrefix . ' ' . macros_header();
	}


	function macros_header() {
		$cmsController = cmsController::getInstance();
		$hierarchy = umiHierarchy::getInstance();

		if($cmsController->currentHeader) {
			return $cmsController->currentHeader;
		}

		if($elementId = $cmsController->getCurrentElementId()) {
			if($element = $hierarchy->getElement($elementId)) {
				$header = ($tmp = $element->getValue("h1")) ? $tmp : "";
				return str_replace("%", "&#37;", $header);
			}
		}

		$currentModule = $cmsController->getCurrentModule();
		$currentMethod = $cmsController->getCurrentMethod();

		if(isset($cmsController->langs[$currentModule][$currentMethod])) {
			return $cmsController->langs[$currentModule][$currentMethod];
		} else {
			return false;
		}
	}

	function macros_systemBuild() {
		return regedit::getInstance()->getVal('//modules/autoupdate/system_build');;
	}

	function macros_menu() {
		$cmsController = cmsController::getInstance();
		$contentModule = $cmsController->getModule('content');

		return ($contentModule instanceof def_module) ? $contentModule->menu() : "";
	}

	function macros_describtion() {
		$cmsController = cmsController::getInstance();
		$regedit = regedit::getInstance();
		$hierarchy = umiHierarchy::getInstance();

		$domainId = $cmsController->getCurrentDomain()->getId();
		$langId = $cmsController->getCurrentLang()->getId();

		$description = "";
		if($elementId = $cmsController->getCurrentElementId()) {
			if($element = $hierarchy->getElement($elementId)) {
				$description = $element->getValue("meta_descriptions");
			}
		}
		if(!$description) {
			$description = $regedit->getVal("//settings/meta_description/" . $langId . "/" . $domainId);
		}

		return $description;
	}


	function macros_keywords() {
		$cmsController = cmsController::getInstance();
		$regedit = regedit::getInstance();
		$hierarchy = umiHierarchy::getInstance();

		$domainId = $cmsController->getCurrentDomain()->getId();
		$langId = $cmsController->getCurrentLang()->getId();

		$keywords = "";
		if($elementId = $cmsController->getCurrentElementId()) {
			if($element = $hierarchy->getElement($elementId)) {
				$keywords = $element->getValue("meta_keywords");
			}
		}
		if(!$keywords) {
			$keywords = $regedit->getVal("//settings/meta_keywords/" . $langId . "/" . $domainId);
		}

		return $keywords;
	}

	function macros_returnPid() {
		return cmsController::getInstance()->getCurrentElementId();
	}

	function macros_returnPreLang() {
		return cmsController::getInstance()->pre_lang;
	}

	function macros_returnDomain() {
		return getServer('HTTP_HOST');
	}

	function macros_returnDomainFloated() {
		$cmsController = cmsController::getInstance();

		if($cmsController->getCurrentMode() == "") {
			return getServer('HTTP_HOST');
		} else {
			$arr = Array();
			if(is_numeric(getRequest('param0'))) {
				$arr[] = getRequest('param0');
			}

			if(is_numeric(getRequest('param1'))) {
				$arr[] = getRequest('param1');
			}

			if(getRequest('parent')) {
				$arr[] = getRequest('parent');
			}

			foreach($arr as $c) {
				if(is_numeric($c)) {
					try {
						if($element = umiHierarchy::getInstance()->getElement($c)) {
							$domain_id = $element->getDomainId();
							if($domain = domainsCollection::getInstance()->getDomain($domain_id)) {
								return $domain->getHost();
							}
						}
					} catch (baseException $e) {
						//Do nothing
					}
				}

				if(is_string($c)) {
					if($domain_id = domainsCollection::getInstance()->getDomainId($c)) {
						if($domain = domainsCollection::getInstance()->getDomain($domain_id)) {
							return $domain->getHost();
						}
					}
				}
			}

			return getServer('HTTP_HOST');
		}

	}

	function macros_curr_time() {
		return time();
	}

	function macros_skin_path() {
		if(getRequest('skin_sel')) return getRequest('skin_sel');
		return (getCookie('skin')) ? getCookie('skin') : regedit::getInstance()->getVal("//skins");
	}

	function macros_current_user_id() {
		return permissionsCollection::getInstance()->getUserId();
	}


	function macros_current_version_line() {
		if(defined("CURRENT_VERSION_LINE")) {
			return CURRENT_VERSION_LINE;
		} else {
			return "pro";
		}
	}

	function macros_catched_errors() {
		$res = "";
		foreach(baseException::$catchedExceptions as $exception) {
			$res .= "<p>" . $exception->getMessage() . "</p>";
		}
		return $res;
	}

	function macros_current_alt_name() {
		$cmsController = cmsController::getInstance();

		if($element_id = $cmsController->getCurrentElementId()) {
			if($element = umiHierarchy::getInstance()->getElement($element_id)) {
				return $element->getAltName();
			} else {
				return "";
			}
		} else {
			return "";
		}
	}


	function macros_returnParentId() {
		$cmsController = cmsController::getInstance();

		if($element_id = $cmsController->getCurrentElementId()) {
			if($element = umiHierarchy::getInstance()->getElement($element_id)) {
				return $element->getParentId();
			} else {
				return "";
			}
		} else {
			return "";
		}
	}

?>
