<?php

	require_once("../libs/config.php");
	$regedit = regedit::getInstance();

	if ($regedit->checkSelfKeycode()) exit;

	if (is_file(CURRENT_WORKING_DIR . "/sys-temp/runtime-cache/registry")) {
		unlink(CURRENT_WORKING_DIR . "/sys-temp/runtime-cache/registry");
	}

	if (is_file(CURRENT_WORKING_DIR . "/sys-temp/runtime-cache/trash")) {
		unlink(CURRENT_WORKING_DIR . "/sys-temp/runtime-cache/trash");
	}

	cacheFrontend::getInstance()->flush();

	$ip = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : str_replace("\\", "", $_SERVER['DOCUMENT_ROOT']);
	$domain = getServer('HTTP_HOST');
	$domain_keycode = getRequest('domain_keycode');
	$license_codename = getRequest('license_codename');

	if (strlen(str_replace("-", "", $domain_keycode)) != 33) exit;

	if (!$license_codename) exit;

	$pro = array('commerce', 'business', 'corporate', 'commerce_enc', 'business_enc', 'corporate_enc', 'gov');

	$internalCodeName = in_array($license_codename, $pro) ? 'pro' : $license_codename;

	$checkKey = templater::getSomething($internalCodeName, $domain);
	if ($checkKey != substr($domain_keycode, 12)) exit;

	$primaryDomain = selector::get('domain')->id(1);
	$primaryDomain->setHost($domain);
	$primaryDomain->commit();

	$regedit = regedit::getInstance();
	$regedit->setVar("//settings/keycode", $domain_keycode);
	$regedit->setVar("//settings/system_edition", $license_codename);
	$regedit->setVar("//modules/autoupdate/system_edition", $license_codename);

	include CURRENT_WORKING_DIR . "/classes/modules/autoupdate/ch_m.php";
	ch_remove_m_garbage();

?>