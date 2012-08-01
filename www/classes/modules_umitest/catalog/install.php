<?php

$INFO = Array();

$INFO['version'] = "2.0.0.0";
$INFO['version_line'] = "lite";

$INFO['name'] = "catalog";
$INFO['title'] = "Каталог";
$INFO['filename'] = "modules/catalog/class.php";
$INFO['config'] = "1";
$INFO['ico'] = "ico_catalog";
$INFO['default_method'] = "category";
$INFO['default_method_admin'] = "tree";
$INFO['is_indexed'] = "0";
$INFO['per_page'] = 10;


$INFO['func_perms'] = "";

$INFO['func_perms/tree'] = "Управление каталогом";
$INFO['func_perms/tree/edit_object'] = "";
$INFO['func_perms/tree/edit_section'] = "";
$INFO['func_perms/tree/section_add'] = "";
$INFO['func_perms/tree/tree_section_add'] = "";
$INFO['func_perms/tree/tree_section_add_do'] = "";
$INFO['func_perms/tree/section_edit'] = "";
$INFO['func_perms/tree/tree_section_edit'] = "";
$INFO['func_perms/tree/tree_section_edit_do'] = "";
$INFO['func_perms/tree/tree_blocking'] = "";
$INFO['func_perms/tree/tree_del'] = "";
$INFO['func_perms/tree/matrix'] = "";
$INFO['func_perms/tree/matrix_add_do'] = "";
$INFO['func_perms/tree/matrix_edit'] = "";
$INFO['func_perms/tree/matrix_edit_do'] = "";
$INFO['func_perms/tree/matrix_edit_answ'] = "";
$INFO['func_perms/tree/matrix_edit_answ_do'] = "";
$INFO['func_perms/tree/matrix_matrix'] = "";
$INFO['func_perms/tree/matrix_matrix_do'] = "";
$INFO['func_perms/tree/matrix_del'] = "";
$INFO['func_perms/tree/object_add'] = "";
$INFO['func_perms/tree/tree_object_add'] = "";
$INFO['func_perms/tree/tree_object_add_do'] = "";
$INFO['func_perms/tree/object_edit'] = "";
$INFO['func_perms/tree/tree_object_edit'] = "";
$INFO['func_perms/tree/tree_object_edit_do'] = "";

$INFO['func_perms/view'] = "Просмотр каталога";
$INFO['func_perms/view/parsesearchrelation'] = "";
$INFO['func_perms/view/parsesearchtext'] = "";
$INFO['func_perms/view/parsesearchprice'] = "";
$INFO['func_perms/view/parsesearchboolean'] = "";
$INFO['func_perms/view/applyfiltertext'] = "";
$INFO['func_perms/view/applyfilterint'] = "";
$INFO['func_perms/view/applyfilterrelation'] = "";
$INFO['func_perms/view/applyfilterprice'] = "";
$INFO['func_perms/view/applyfilterboolean'] = "";
$INFO['func_perms/view/category'] = "";
$INFO['func_perms/view/getcategorylist'] = "";
$INFO['func_perms/view/getobjectslist'] = "";
$INFO['func_perms/view/object'] = "";
$INFO['func_perms/view/viewobject'] = "";
$INFO['func_perms/view/search'] = "";
$INFO['func_perms/view/geteditlink'] = "";


$SQL_INSTALL = Array();

$COMPONENTS = array();

$COMPONENTS[0] = "./classes/modules/catalog/__admin.php";
$COMPONENTS[1] = "./classes/modules/catalog/__custom.php";
$COMPONENTS[2] = "./classes/modules/catalog/__custom_adm.php";
$COMPONENTS[3] = "./classes/modules/catalog/__search.php";
$COMPONENTS[4] = "./classes/modules/catalog/class.php";
$COMPONENTS[5] = "./classes/modules/catalog/i18n.en.php";
$COMPONENTS[6] = "./classes/modules/catalog/i18n.php";
$COMPONENTS[7] = "./classes/modules/catalog/lang.php";
$COMPONENTS[8] = "./classes/modules/catalog/permissions.php";



?>