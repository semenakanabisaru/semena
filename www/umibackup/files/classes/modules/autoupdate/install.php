<?php

$INFO = Array();

$INFO['verison'] = "2.0.0.0";
$INFO['version_line'] = "pro";

$INFO['name'] = "autoupdate";
$INFO['filename'] = "modules/autoupdate/class.php";
$INFO['config'] = "0";
$INFO['ico'] = "ico_autoupdate";
$INFO['default_method'] = "updateall";
$INFO['default_method_admin'] = "versions";
$INFO['is_indexed'] = "0";
$INFO['autoupdates_disabled'] = "0";
$INFO['system_version'] = "2.0.0.0";
$INFO['system_edition'] = "business";
$INFO['last_updated'] = "1172828100";

$INFO['func_perms'] = "Functions, that should have their own permissions.";
$INFO['func_perms/service'] = "Автоматический бот";
$INFO['func_perms/service/updateall'] = "";
$INFO['func_perms/versions'] = "Управление обновлениями";
$INFO['func_perms/versions/updatemodule'] = "";


$COMPONENTS = array();

$COMPONENTS[0] = "./classes/modules/autoupdate/__admin.php";
$COMPONENTS[1] = "./classes/modules/autoupdate/__json.php";
$COMPONENTS[2] = "./classes/modules/autoupdate/ch_m.php";
$COMPONENTS[3] = "./classes/modules/autoupdate/class.php";
$COMPONENTS[4] = "./classes/modules/autoupdate/i18n.en.php";
$COMPONENTS[5] = "./classes/modules/autoupdate/i18n.php";
$COMPONENTS[6] = "./classes/modules/autoupdate/lang.php";
$COMPONENTS[7] = "./classes/modules/autoupdate/permissions.php";
$COMPONENTS[8] = "./classes/modules/autoupdate/update.php";

?>