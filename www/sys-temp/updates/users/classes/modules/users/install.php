<?php
$INFO = Array();

$INFO['version'] = "2.0.0.0";
$INFO['version_line'] = "pro";

$INFO['name'] = "users";
$INFO['filename'] = "modules/users/class.php";
$INFO['config'] = "1";
$INFO['ico'] = "ico_users";
$INFO['default_method'] = "auth";
$INFO['default_method_admin'] = "groups_list";

$INFO['func_perms'] = "Functions, that should have their own permissions.";
$INFO['func_perms/login'] = "Авторизация";
$INFO['func_perms/registrate'] = "Регистрация";
$INFO['func_perms/settings'] = "Редактирование настроек";
$INFO['func_perms/users_list'] = "Управление пользователями";
$INFO['func_perms/profile'] = "Просмотр профиля пользователей";


$SQL_INSTALL = Array();


$SQL_INSTALL['cms_permissions'] = <<<SQL

CREATE TABLE cms_permissions(
id		INT		NOT NULL	PRIMARY KEY	AUTO_INCREMENT,
module		VARCHAR(64)	DEFAULT NULL,
method		VARCHAR(64)	DEFAULT NULL,
owner_id	INT		DEFAULT NULL,
allow		TINYINT		DEFAULT '1',
KEY(module), KEY(method), KEY(owner_id)
)

SQL;

$COMPONENTS = array();

$COMPONENTS[0] = "./classes/modules/users/__admin.php";
$COMPONENTS[1] = "./classes/modules/users/__author.php";
$COMPONENTS[2] = "./classes/modules/users/__config.php";
$COMPONENTS[3] = "./classes/modules/users/__custom.php";
$COMPONENTS[4] = "./classes/modules/users/__custom_adm.php";
$COMPONENTS[5] = "./classes/modules/users/__forget.php";
$COMPONENTS[6] = "./classes/modules/users/__import.php";
$COMPONENTS[7] = "./classes/modules/users/__list.php";
$COMPONENTS[8] = "./classes/modules/users/__loginza.php";
$COMPONENTS[9] = "./classes/modules/users/__openid.php";
$COMPONENTS[10] = "./classes/modules/users/__profile.php";
$COMPONENTS[11] = "./classes/modules/users/__register.php";
$COMPONENTS[12] = "./classes/modules/users/__settings.php";
$COMPONENTS[13] = "./classes/modules/users/__messages.php";
$COMPONENTS[14] = "./classes/modules/users/class.php";
$COMPONENTS[15] = "./classes/modules/users/events.php";
$COMPONENTS[16] = "./classes/modules/users/i18n.en.php";
$COMPONENTS[17] = "./classes/modules/users/i18n.php";
$COMPONENTS[18] = "./classes/modules/users/lang.php";
$COMPONENTS[19] = "./classes/modules/users/permissions.php";


?>