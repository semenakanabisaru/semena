<?php

$INFO = Array();

$INFO['verison'] = "2.0.0.0";
$INFO['version_line'] = "pro";

$INFO['name'] = "news";
$INFO['filename'] = "modules/news/class.php";
$INFO['config'] = "1";
$INFO['ico'] = "ico_news";
$INFO['default_method'] = "archive";
$INFO['default_method_admin'] = "lists";
$INFO['is_indexed'] = "1";
$INFO['per_page'] = "10";
$INFO['rss_per_page'] = "10";

$INFO['func_perms'] = "Functions, that should have their own permissions.";

$INFO['func_perms/view'] = "Просмотр новостей";
$INFO['func_perms/view/lastlist'] = "";
$INFO['func_perms/view/listlents'] = "";
$INFO['func_perms/view/rubric'] = "";
$INFO['func_perms/view/related_links'] = "";
$INFO['func_perms/view/rss'] = "";
$INFO['func_perms/view/item'] = "";


$INFO['func_perms/lists'] = "Управление новостями";
$INFO['func_prems/lists/add_item_do'] = "";
$INFO['func_prems/lists/del_item'] = "";
$INFO['func_prems/lists/edit_list'] = "";
$INFO['func_prems/lists/edit_list_do'] = "";
$INFO['func_prems/lists/del_list'] = "";
$INFO['func_prems/lists/subjects'] = "";
$INFO['func_prems/lists/subjects_do'] = "";
$INFO['func_prems/lists/add_item'] = "";
$INFO['func_prems/lists/add_list'] = "";
$INFO['func_prems/lists/add_list_do'] = "";
$INFO['func_prems/lists/edit_item'] = "";
$INFO['func_prems/lists/edit_item_do'] = "";

$COMPONENTS = array();

$COMPONENTS[0] = "./classes/modules/news/__admin.php";
$COMPONENTS[1] = "./classes/modules/news/__custom.php";
$COMPONENTS[2] = "./classes/modules/news/__custom_adm.php";
$COMPONENTS[3] = "./classes/modules/news/__rss_import.php";
$COMPONENTS[4] = "./classes/modules/news/__subjects.php";
$COMPONENTS[5] = "./classes/modules/news/calendar.php";
$COMPONENTS[6] = "./classes/modules/news/class.php";
$COMPONENTS[7] = "./classes/modules/news/events.php";
$COMPONENTS[8] = "./classes/modules/news/i18n.en.php";
$COMPONENTS[9] = "./classes/modules/news/i18n.php";
$COMPONENTS[10] = "./classes/modules/news/lang.php";
$COMPONENTS[11] = "./classes/modules/news/permissions.php";

?>