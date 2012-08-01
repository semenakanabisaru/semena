<?php
$INFO = Array();

$INFO['name'] = "updatesrv";
$INFO['title'] = "Сервер обновления";
$INFO['description'] = "Модуль сервера обновлений.";
$INFO['filename'] = "modules/updatesrv/class.php";
$INFO['config'] = "0";
$INFO['ico'] = "ico_updatesrv";
$INFO['default_method'] = "status";
$INFO['default_method_admin'] = "licenses";

$INFO['func_perms'] = "Functions, that should have their own permissions.";
$INFO['func_perms/service'] = "Автоматический бот";

/*
$SQL_INSTALL['cms_updatesrv_lines'] = <<<END

CREATE TABLE cms_updatesrv_lines (
id		INT		NOT NULL			PRIMARY KEY	AUTO_INCREMENT,
title		VARCHAR(255) 			DEFAULT '',
keyname		VARCHAR(16)			DEFAULT ''
)

END;

$SQL_INSTALL['cms_updatesrv_modules'] = <<<END

CREATE TABLE cms_updatesrv_modules (
id		INT		NOT NULL			PRIMARY KEY	AUTO_INCREMENT,
rel_line	INT		NOT NULL,
module_name	VARCHAR(48)			DEFAULT ''
)

END;

$SQL_INSTALL['cms_updatesrv_versions'] = <<<END

CREATE TABLE cms_updatesrv_versions (
id		INT		NOT NULL			PRIMARY KEY	AUTO_INCREMENT,
rel_module	INT		NOT NULL,
version		VARCHAR(24)	NOT NULL	DEFAULT '',
cr_time		INT		NOT NULL	DEFAULT 0,
obj_path	VARCHAR(255)	NOT NULL	DEFAULT ''
)


END;

$SQL_INSTALL['cms_updatesrv_licenses'] = <<<END

CREATE TABLE cms_updatesrv_licenses (
id		INT		NOT NULL			PRIMARY KEY	AUTO_INCREMENT,
domain		VARCHAR(255)	NOT NULL	DEFAULT '',
ip		VARCHAR(48)	NOT NULL	DEFAULT '',
keycode		VARCHAR(255)	NOT NULL	DEFAULT '',
fio		VARCHAR(255) 	NOT NULL	DEFAULT '',
email		VARCHAR(64)	NOT NULL	DEFAULT '',
phone		VARCHAR(48)	NOT NULL	DEFAULT '',
posttime	INT		NOT NULL	DEFAULT '',
is_free		INT				DEFAULT 0
)

END;

$SQL_INSTALL['cms_updatesrv_licenses_modules'] = <<<END

CREATE TABLE cms_updatesrv_licenses_modules (
mid		INT		NOT NULL,
lid		INT		NOT NULL
)

END;

//$SQL_INSTALL['cms_news_drop'] = "DROP ";
//$SQL_INSTALL['cms_news'] = "";
//$SQL_INSTALL['perms1'] = "INSERT INTO cms_permissions (module, method, user_id) VALUES('news','lastlist','')";
*/


$COMPONENTS = array();

$COMPONENTS[0] = "./classes/modules/updatesrv/__admin.php";
$COMPONENTS[1] = "./classes/modules/updatesrv/__licenses.php";
$COMPONENTS[2] = "./classes/modules/updatesrv/__lines.php";
$COMPONENTS[3] = "./classes/modules/updatesrv/__server.php";
$COMPONENTS[4] = "./classes/modules/updatesrv/__updates.php";
$COMPONENTS[5] = "./classes/modules/updatesrv/class.php";
$COMPONENTS[6] = "./classes/modules/updatesrv/forms.php";
$COMPONENTS[7] = "./classes/modules/updatesrv/i18n.php";
$COMPONENTS[8] = "./classes/modules/updatesrv/interface.php";
$COMPONENTS[9] = "./classes/modules/updatesrv/lang.php";
$COMPONENTS[10] = "./classes/modules/updatesrv/permissions.php";

?>
