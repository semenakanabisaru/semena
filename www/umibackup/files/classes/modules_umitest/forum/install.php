<?php

$INFO = Array();

$INFO['name'] = "forum";
$INFO['title'] = "Конференции";
$INFO['description'] = "Модуль конференций";
$INFO['filename'] = "modules/forum/class.php";
$INFO['config'] = "1";
$INFO['ico'] = "ico_forum";
$INFO['default_method'] = "show";
$INFO['default_method_admin'] = "lists";

$INFO['def_group'] = "0";
$INFO['need_moder'] = "0";
$INFO['antimat'] = "0";
$INFO['antidouble'] = "0";
$INFO['autounion'] = "0";
$INFO['allow_guest'] = "0";
$INFO['per_page'] = "20";

$INFO['func_perms'] = "Functions, that should have their own permissions.";
$INFO['func_perms/view'] = "Доступ к форуму";
$INFO['func_perms/last_messages'] = "Административный доступ";

$COMPONENTS = array();

$COMPONENTS[0] = "./classes/modules/forum/__admin.php";
$COMPONENTS[1] = "./classes/modules/forum/__custom.php";
$COMPONENTS[2] = "./classes/modules/forum/__events_handlers.php";
$COMPONENTS[3] = "./classes/modules/forum/__sysevents.php";
$COMPONENTS[4] = "./classes/modules/forum/class.php";
$COMPONENTS[5] = "./classes/modules/forum/events.php";
$COMPONENTS[6] = "./classes/modules/forum/i18n.en.php";
$COMPONENTS[7] = "./classes/modules/forum/i18n.php";
$COMPONENTS[8] = "./classes/modules/forum/lang.php";
$COMPONENTS[9] = "./classes/modules/forum/list.txt";
$COMPONENTS[10] = "./classes/modules/forum/permissions.php";


$SQL_INSTALL = Array();
?>