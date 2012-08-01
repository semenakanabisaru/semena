<?php

$INFO = Array();

$INFO['name'] = "banners";
$INFO['filename'] = "banners/class.php";
$INFO['config'] = "1";
$INFO['default_method'] = "insert_banner";
$INFO['default_method_admin'] = "lists";

$INFO['func_perms'] = "Functions, that should have their own permissions.";
$INFO['func_perms/places'] = "Редактирование мест показа";
$INFO['func_perms/lists'] = "Редактирование баннеров";
$INFO['func_perms/insert'] = "Просмотр баннеров";


$COMPONENTS = array();

$COMPONENTS[0] = "./classes/modules/banners/__admin.php";
$COMPONENTS[1] = "./classes/modules/banners/__banners.php";
$COMPONENTS[2] = "./classes/modules/banners/__custom.php";
$COMPONENTS[3] = "./classes/modules/banners/__places.php";
$COMPONENTS[4] = "./classes/modules/banners/class.php";
$COMPONENTS[5] = "./classes/modules/banners/forms.php";
$COMPONENTS[6] = "./classes/modules/banners/i18n.en.php";
$COMPONENTS[7] = "./classes/modules/banners/i18n.php";
$COMPONENTS[8] = "./classes/modules/banners/lang.php";
$COMPONENTS[9] = "./classes/modules/banners/permissions.php";

?>