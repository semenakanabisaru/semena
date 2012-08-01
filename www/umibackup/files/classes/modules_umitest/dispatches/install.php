<?php

$INFO = Array();

$INFO['name'] = "dispatches";
$INFO['filename'] = "dispatches/class.php";
$INFO['config'] = "0";
$INFO['default_method'] = "subscribe";
$INFO['default_method_admin'] = "lists";

$INFO['func_perms'] = "Functions, that should have their own permissions.";


$INFO['func_perms/lists'] = "Управление рассылками";
$INFO['func_perms/subscribe'] = "Разрешить подписку и отписку";


// import types


$s_dt_xml_path = dirname(__FILE__)."/types.xml";
if (is_file($s_dt_xml_path)) {
	$o_dt_importer = new umiModuleDataImporter();
	$b_succ = $o_dt_importer->loadXmlFile($s_dt_xml_path);
	if ($b_succ) $o_dt_importer->import();
}


$COMPONENTS = array();

$COMPONENTS[0] = "./classes/modules/dispatches/__admin.php";
$COMPONENTS[1] = "./classes/modules/dispatches/__custom.php";
$COMPONENTS[2] = "./classes/modules/dispatches/__dispatches.php";
$COMPONENTS[3] = "./classes/modules/dispatches/__messages.php";
$COMPONENTS[4] = "./classes/modules/dispatches/__releasees.php";
$COMPONENTS[5] = "./classes/modules/dispatches/__subscribers.php";
$COMPONENTS[6] = "./classes/modules/dispatches/__subscribers_import.php";
$COMPONENTS[7] = "./classes/modules/dispatches/class.php";
$COMPONENTS[8] = "./classes/modules/dispatches/events.php";
$COMPONENTS[9] = "./classes/modules/dispatches/i18n.en.php";
$COMPONENTS[10] = "./classes/modules/dispatches/i18n.php";
$COMPONENTS[11] = "./classes/modules/dispatches/lang.php";
$COMPONENTS[12] = "./classes/modules/dispatches/permissions.php";


?>
