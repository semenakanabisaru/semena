<?php

$INFO = Array();

$INFO['name'] = "filemanager";
$INFO['title'] = "Файловый менеджер";
$INFO['description'] = "Управление файловой системой.";
$INFO['filename'] = "modules/filemanager/class.php";
$INFO['config'] = "0";
$INFO['ico'] = "ico_filemanager";
$INFO['default_method'] = "list_files";
$INFO['default_method_admin'] = "shared_files";

$INFO['func_perms'] = "Functions, that should have their own permissions.";

$INFO['func_perms/directory_list'] = "Управление файловой системой";
$INFO['func_perms/directory_list/rename'] = "";
$INFO['func_perms/directory_list/rename'] = "";


$INFO['func_perms/list_files'] = "Просмотр файлов для скачивания";
$INFO['func_perms/list_files/make_directory'] = "";
$INFO['func_perms/list_files/remove'] = "";
$INFO['func_perms/list_files/remove'] = "";

$INFO['func_perms/list_files/shared_files'] = "";
$INFO['func_perms/list_files/add_shared_file'] = "";
$INFO['func_perms/list_files/add_shared_file_do'] = "";
$INFO['func_perms/list_files/edit_shared_file'] = "";
$INFO['func_perms/list_files/edit_shared_file_do'] = "";
$INFO['func_perms/list_files/shared_file_blocking'] = "";
$INFO['func_perms/list_files/del_shared_file'] = "";

$INFO['func_perms/download'] = "Скачивание файлов";

$SQL_INSTALL = Array();

$COMPONENTS = array();

$COMPONENTS[0] = "./classes/modules/filemanager/__admin.php";
$COMPONENTS[1] = "./classes/modules/filemanager/__custom.php";
$COMPONENTS[2] = "./classes/modules/filemanager/__shared_files.php";
$COMPONENTS[3] = "./classes/modules/filemanager/class.php";
$COMPONENTS[4] = "./classes/modules/filemanager/i18n.en.php";
$COMPONENTS[5] = "./classes/modules/filemanager/i18n.php";
$COMPONENTS[6] = "./classes/modules/filemanager/lang.php";
$COMPONENTS[7] = "./classes/modules/filemanager/permissions.php";

?>