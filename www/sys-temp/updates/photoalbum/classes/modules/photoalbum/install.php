<?php

$INFO = Array();

$INFO['name'] = "photoalbum";
$INFO['title'] = "Фотоальбомы";
$INFO['description'] = "Модуль фотогалерей.";
$INFO['filename'] = "modules/photoalbum/class.php";
$INFO['config'] = "1";
$INFO['ico'] = "ico_photoalbum";
$INFO['default_method'] = "albums";
$INFO['default_method_admin'] = "albums_list";

$INFO['func_perms'] = "Functions, that should have their own permissions.";

$INFO['func_perms/albums'] = "Просмотр фотогалерей";
$INFO['func_perms/albums/album'] = "";
$INFO['func_perms/albums/photo'] = "";
$INFO['func_perms/albums/view'] = "";

$INFO['func_perms/albums_list'] = "Управление фотогалереями";
$INFO['func_perms/albums_list/albums_list'] = "";
$INFO['func_perms/albums_list/album_add'] = "";
$INFO['func_perms/albums_list/album_add_do'] = "";
$INFO['func_perms/albums_list/album_blocking'] = "";
$INFO['func_perms/albums_list/album_del'] = "";
$INFO['func_perms/albums_list/album_edit'] = "";
$INFO['func_perms/albums_list/album_edit_do'] = "";
$INFO['func_perms/albums_list/photos_list'] = "";
$INFO['func_perms/albums_list/photo_add'] = "";
$INFO['func_perms/albums_list/photo_add_do'] = "";
$INFO['func_perms/albums_list/photo_blocking'] = "";
$INFO['func_perms/albums_list/photo_del'] = "";
$INFO['func_perms/albums_list/photo_edit'] = "";
$INFO['func_perms/albums_list/photo_edit_do'] = "";

$SQL_INSTALL = Array();

$COMPONENTS = array();

$COMPONENTS[0] = "./classes/modules/photoalbum/__admin.php";
$COMPONENTS[1] = "./classes/modules/photoalbum/__custom.php";
$COMPONENTS[2] = "./classes/modules/photoalbum/__picasa.php";
$COMPONENTS[3] = "./classes/modules/photoalbum/class.php";
$COMPONENTS[4] = "./classes/modules/photoalbum/i18n.en.php";
$COMPONENTS[5] = "./classes/modules/photoalbum/i18n.php";
$COMPONENTS[6] = "./classes/modules/photoalbum/lang.php";
$COMPONENTS[7] = "./classes/modules/photoalbum/permissions.php";
$COMPONENTS[8] = "./classes/modules/photoalbum/picasa-button/.htaccess";
$COMPONENTS[9] = "./classes/modules/photoalbum/picasa-button/icon.psd";
$COMPONENTS[10] = "./classes/modules/photoalbum/picasa-button/pbf.orign";

?>