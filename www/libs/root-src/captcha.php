<?php
	session_start();
	ob_start();
	require CURRENT_WORKING_DIR . "/libs/root-src/standalone.php";
	ob_end_clean();
	
	$code = getSession('umi_captcha_plain');
	$drawer = umiCaptcha::getDrawer();

	if(!$code || isset($_REQUEST['reset'])) $code = $drawer->getRandomCode();

	$_SESSION['umi_captcha'] =  md5($code);
	$_SESSION['umi_captcha_plain'] = $code;
	setcookie("umi_captcha", md5($code));

	$drawer->draw($code);
?>