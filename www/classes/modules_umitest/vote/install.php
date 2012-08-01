<?php

$INFO = Array();

$INFO['name'] = "vote";
$INFO['filename'] = "vote/class.php";
$INFO['config'] = "0";
$INFO['default_method'] = "insertvote";
$INFO['default_method_admin'] = "lists";


$INFO['func_perms/lists'] = "Управление опросами";
$INFO['func_perms/poll'] = "Просмотр опросов";
$INFO['func_perms/post'] = "Разрешить голосовать";

$COMPONENTS = array();

$COMPONENTS[0] = "./classes/modules/vote/__admin.php";
$COMPONENTS[1] = "./classes/modules/vote/__custom.php";
$COMPONENTS[2] = "./classes/modules/vote/__events_handlers.php";
$COMPONENTS[3] = "./classes/modules/vote/__rate.php";
$COMPONENTS[4] = "./classes/modules/vote/class.php";
$COMPONENTS[5] = "./classes/modules/vote/events.php";
$COMPONENTS[6] = "./classes/modules/vote/forms.php";
$COMPONENTS[7] = "./classes/modules/vote/i18n.en.php";
$COMPONENTS[8] = "./classes/modules/vote/i18n.php";
$COMPONENTS[9] = "./classes/modules/vote/lang.php";
$COMPONENTS[10] = "./classes/modules/vote/permissions.php";
$COMPONENTS[11] = "./classes/modules/vote/update.php";

?>
