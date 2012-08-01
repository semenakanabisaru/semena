<?php

$INFO = Array();

$INFO['verison'] = "2.0.0.0";
$INFO['version_line'] = "dev";

$INFO['name'] = "data";
$INFO['filename'] = "modules/data/class.php";
$INFO['config'] = "1";
$INFO['ico'] = "ico_data";
$INFO['default_method'] = "test";
$INFO['default_method_admin'] = "types";

$INFO['func_perms'] = "Functions, that should have their own permissions.";

$INFO['func_perms/main'] = "Просмотр объектов";
$INFO['func_perms/main/geteditlink'] = "";
$INFO['func_perms/main/geteditform'] = "";
$INFO['func_perms/main/getcreateform'] = "";
$INFO['func_perms/main/gettypefieldgroups'] = "";
$INFO['func_perms/main/rendereditfield'] = "";
$INFO['func_perms/main/rendereditfieldstring'] = "";
$INFO['func_perms/main/endereditfieldint'] = "";
$INFO['func_perms/main/rendereditfieldpassword'] = "";
$INFO['func_perms/main/rendereditfieldrelation'] = "";
$INFO['func_perms/main/rendereditfieldimagefile'] = "";
$INFO['func_perms/main/saveeditedobject'] = "";
$INFO['func_perms/main/getproperty'] = "";
$INFO['func_perms/main/getpropertygroup'] = "";
$INFO['func_perms/main/getallgroups'] = "";
$INFO['func_perms/main/getpropertyofobject'] = "";
$INFO['func_perms/main/getpropertygroupofobject'] = "";
$INFO['func_perms/main/getallgroupsofobject'] = "";
$INFO['func_perms/main/rendereditablegroups'] = "";
$INFO['func_perms/main/rendereditablefield'] = "";
$INFO['func_perms/main/renderstringinput'] = "";
$INFO['func_perms/main/renderintegerinput'] = "";
$INFO['func_perms/main/renderbooleaninput'] = "";
$INFO['func_perms/main/rendertextinput'] = "";
$INFO['func_perms/main/renderwysiwyginput'] = "";
$INFO['func_perms/main/renderimagefileinput'] = "";
$INFO['func_perms/main/renderrelationinput'] = "";
$INFO['func_perms/main/renderdateinput'] = "";
$INFO['func_perms/main/rendertagsinput'] = "";
$INFO['func_perms/main/rendersymlinkinput'] = "";
$INFO['func_perms/main/saveeditedgroups'] = "";
$INFO['func_perms/main/rss'] = "";
$INFO['func_perms/main/atom'] = "";
$INFO['func_perms/main/generateFeed'] = "";
$INFO['func_perms/main/getRssMeta'] = "";
$INFO['func_perms/main/getRssMetaByPath'] = "";
$INFO['func_perms/main/getAtomMeta'] = "";
$INFO['func_perms/main/getAtomMetaByPath'] = "";
$INFO['func_perms/main/checkIfFeedable'] = "";

$INFO['func_perms/guides'] = "Управление справочниками";
$INFO['func_perms/guides/guide_items'] = "";
$INFO['func_perms/guides/guide_items_do'] = "";
$INFO['func_perms/guides/guide_item_edit'] = "";
$INFO['func_perms/guides/guide_item_edit_do'] = "";

$INFO['func_perms/trash'] = "Мусорная корзина";
$INFO['func_perms/trash/trash_del'] = "";
$INFO['func_perms/trash/trash_restore'] = "";
$INFO['func_perms/trash/trash_empty'] = "";


$INFO['func_perms/types'] = "Управление шаблонами данных";
$INFO['func_perms/types/fill_navibar'] = "";
$INFO['func_perms/types/type_add'] = "";
$INFO['func_perms/types/type_edit'] = "";
$INFO['func_perms/types/type_edit_do'] = "";
$INFO['func_perms/types/type_del'] = "";
$INFO['func_perms/types/type_field_add'] = "";
$INFO['func_perms/types/type_field_add_do'] = "";
$INFO['func_perms/types/type_field_edit'] = "";
$INFO['func_perms/types/type_field_edit_do'] = "";
$INFO['func_perms/types/type_group_add'] = "";
$INFO['func_perms/types/type_group_add_do'] = "";
$INFO['func_perms/types/type_group_edit'] = "";
$INFO['func_perms/types/type_group_edit_do'] = "";
$INFO['func_perms/types/json_move_field_after'] = "";
$INFO['func_perms/types/json_move_group_after'] = "";
$INFO['func_perms/types/json_delete_field'] = "";
$INFO['func_perms/types/json_delete_group'] = "";
$INFO['func_perms/types/json_load_hierarchy_level'] = "";

$COMPONENTS = array();

$COMPONENTS[0] = "./classes/modules/data/__admin.php";
$COMPONENTS[1] = "./classes/modules/data/__client_reflection.php";
$COMPONENTS[2] = "./classes/modules/data/__custom.php";
$COMPONENTS[3] = "./classes/modules/data/__files.php";
$COMPONENTS[4] = "./classes/modules/data/__guides.php";
$COMPONENTS[5] = "./classes/modules/data/__json.php";
$COMPONENTS[6] = "./classes/modules/data/__rss.php";
$COMPONENTS[7] = "./classes/modules/data/__search.php";
$COMPONENTS[8] = "./classes/modules/data/__trash.php";
$COMPONENTS[9] = "./classes/modules/data/class.php";
$COMPONENTS[10] = "./classes/modules/data/i18n.en.php";
$COMPONENTS[11] = "./classes/modules/data/i18n.php";
$COMPONENTS[12] = "./classes/modules/data/lang.php";
$COMPONENTS[13] = "./classes/modules/data/permissions.php";
$COMPONENTS[14] = "./classes/modules/data/update.php";

?>