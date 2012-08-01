<?php

$INFO = Array();

$INFO['name']			= "geoip";
$INFO['filename']		= "modules/geoip/class.php";
$INFO['ico']			= "ico_geoip";
$INFO['default_method']		= "void";
$INFO['default_method_admin']	= "info";

$INFO['func_perms'] = "Void";
$INFO['func_perms/config'] = "Настройка";

$COMPONENTS = array();

$COMPONENTS[0] = "./classes/modules/geoip/.htaccess";
$COMPONENTS[1] = "./classes/modules/geoip/__admin.php";
$COMPONENTS[2] = "./classes/modules/geoip/class.php";
$COMPONENTS[3] = "./classes/modules/geoip/cngeoip.dat";
$COMPONENTS[4] = "./classes/modules/geoip/cngeoip.php";
$COMPONENTS[5] = "./classes/modules/geoip/example_utf8.php";
$COMPONENTS[6] = "./classes/modules/geoip/i18n.en.php";
$COMPONENTS[7] = "./classes/modules/geoip/i18n.php";
$COMPONENTS[8] = "./classes/modules/geoip/lang.php";
$COMPONENTS[9] = "./classes/modules/geoip/newcngeoip.php";
$COMPONENTS[10] = "./classes/modules/geoip/permissions.php";

?>