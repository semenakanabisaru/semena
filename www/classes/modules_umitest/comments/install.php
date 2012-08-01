<?php
$INFO = Array();

$INFO['version'] = "2.0.0.0";
$INFO['version_line'] = "pro";

$INFO['name'] = "comments";
$INFO['title'] = "Комментарии";
$INFO['filename'] = "modules/comments/class.php";
$INFO['config'] = "1";
$INFO['ico'] = "ico_comments";
$INFO['default_method'] = "void_func";
$INFO['default_method_admin'] = "view_comments";
$INFO['is_indexed'] = "0";

$INFO['per_page'] = "10";
$INFO['moderated'] = "0";
$INFO['guest_posting'] = "0";
$INFO['allow_guest'] = "1";

$INFO['func_perms'] = "";
	$INFO['func_perms/insert'] = "Просмотр комментариев";
	$INFO['func_perms/view_comments'] = "Редактирование комментариев";


$SQL_INSTALL = Array();

$COMPONENTS = array();

$COMPONENTS[0] = "./classes/modules/comments/__admin.php";
$COMPONENTS[1] = "./classes/modules/comments/__custom.php";
$COMPONENTS[2] = "./classes/modules/comments/class.php";
$COMPONENTS[3] = "./classes/modules/comments/events.php";
$COMPONENTS[4] = "./classes/modules/comments/i18n.en.php";
$COMPONENTS[5] = "./classes/modules/comments/i18n.php";
$COMPONENTS[6] = "./classes/modules/comments/lang.php";
$COMPONENTS[7] = "./classes/modules/comments/permissions.php";

?>
