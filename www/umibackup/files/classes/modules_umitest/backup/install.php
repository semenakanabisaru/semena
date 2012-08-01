<?php
$INFO = Array();

$INFO['version'] = "2.0.0.0";
$INFO['version_line'] = "pro";

$INFO['name'] = "backup";
$INFO['title'] = "Backups";
$INFO['description'] = "Module for backuping all updates on site.";
$INFO['filename'] = "modules/backup/class.php";
$INFO['config'] = "0";
$INFO['ico'] = "ico_backup";
$INFO['default_method'] = "temp_method";
$INFO['default_method_admin'] = "config";


$INFO['max_timelimit'] = "30";
$INFO['max_save_actions'] = "10";
$INFO['enabled'] = "1";
$INFO['enabled_sys'] = "1";
$INFO['max_timelimit_sys'] = "787";
$INFO['max_save_arch_sys'] = "45";
$INFO['enabled_files'] = "0";
$INFO['max_timelimit_files'] = "0";
$INFO['max_save_arch_files'] = "0";
$INFO['enabled_img'] = "1";
$INFO['max_timelimit_img'] = "0";
$INFO['max_save_arch_img'] = "0";
$INFO['enabled_tpl'] = "0";
$INFO['max_timelimit_tpl'] = "0";
$INFO['max_save_arch_tpl'] = "0";

$INFO['func_perms'] = "Functions, that should have their own permissions.";
$INFO['func_perms/config'] = "Настройка";

$SQL_INSTALL['cms_backup_drop'] = "DROP TABLE cms_backup";
$SQL_INSTALL['cms_backup'] = "
CREATE TABLE cms_backup(
id DOUBLE NOT NULL PRIMARY KEY AUTO_INCREMENT,
ctime INT,
changed_module VARCHAR(128),
changed_method VARCHAR(128),
param TEXT,
param0 TEXT,
user_id INT,
is_active INT DEFAULT '0'
) TYPE=InnoDB;
";

$COMPONENTS = array();

$COMPONENTS[0] = "./classes/modules/backup/__admin.php";
$COMPONENTS[1] = "./classes/modules/backup/class.php";
$COMPONENTS[2] = "./classes/modules/backup/i18n.en.php";
$COMPONENTS[3] = "./classes/modules/backup/i18n.php";
$COMPONENTS[4] = "./classes/modules/backup/lang.php";
$COMPONENTS[5] = "./classes/modules/backup/permissions.php";


?>