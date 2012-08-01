<?php

$INFO = Array();

$INFO['version'] = "2.0.0.0";
$INFO['version_line'] = "pro";

$INFO['name'] = "search";
$INFO['filename'] = "modules/search/class.php";
$INFO['config'] = "0";
$INFO['ico'] = "ico_search";
$INFO['default_method'] = "search_do";
$INFO['default_method_admin'] = "index_control";

$INFO['hightlight_color'] = "";
$INFO['weight_name'] = "0";
$INFO['weight_title'] = "0";
$INFO['weight_h1'] = "0";
$INFO['weight_content'] = "0";
$INFO['autoindex'] = "1";
$INFO['per_page'] = "10";
$INFO['search_deep'] = "999999";


$INFO['func_perms'] = "Functions, that should have their own permissions.";

$INFO['func_perms/index'] = "Индексация";
$INFO['func_perms/index/index_all'] = "";
$INFO['func_perms/index/index_item'] = "";
$INFO['func_perms/index/elementisreindexed'] = "";
$INFO['func_perms/index/index_control'] = "";

$INFO['func_perms/search'] = "Поиск по сайту";
$INFO['func_perms/search/runsearch'] = "";
$INFO['func_perms/search/search_do'] = "";
$INFO['func_perms/search/insert_form'] = "";


$SQL_INSTALL['cms_search_index_drop'] = "DROP TABLE cms_search_index";

$SQL_INSTALL['cms_search_index_all'] = "

CREATE TABLE cms_search_index (
word		VARCHAR(128)	NOT NULL	DEFAULT '',
module		VARCHAR(48)	NOT NULL	DEFAULT '',
method		VARCHAR(48)	NOT NULL	DEFAULT '',
param		VARCHAR(48)	NOT NULL	DEFAULT '',
lang		VARCHAR(24)	NOT NULL	DEFAULT '',
domain		VARCHAR(128)	NOT NULL	DEFAULT '',
range		INT		NOT NULL	DEFAULT 0,
indextime	INT DEFAULT 0,

KEY (word), KEY (module), KEY (method), KEY (lang), KEY (domain)
) TYPE=InnoDB;

";


$COMPONENTS = array();

$COMPONENTS[0] = "./classes/modules/search/__admin.php";
$COMPONENTS[1] = "./classes/modules/search/__custom.php";
$COMPONENTS[2] = "./classes/modules/search/class.php";
$COMPONENTS[3] = "./classes/modules/search/i18n.en.php";
$COMPONENTS[4] = "./classes/modules/search/i18n.php";
$COMPONENTS[5] = "./classes/modules/search/lang.en.php";
$COMPONENTS[6] = "./classes/modules/search/lang.php";
$COMPONENTS[7] = "./classes/modules/search/permissions.php";
$COMPONENTS[8] = "./classes/modules/search/update.php";

?>