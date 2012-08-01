<?php
$INFO = Array();

$INFO['version'] = "2.0.0.0";
$INFO['version_line'] = "pro";

$INFO['name'] = "content";
$INFO['filename'] = "modules/content/class.php";
$INFO['config'] = "1";
$INFO['ico'] = "ico_content";
$INFO['default_method'] = "content";
$INFO['default_method_admin'] = "sitetree";
$INFO['is_indexed'] = "1";

$INFO['func_perms'] = "Functions, that should have their own permissions.";

$INFO['func_perms/content'] = "Просмотр страниц";
$INFO['func_perms/content/title'] = "";
$INFO['func_perms/content/menu'] = "";
$INFO['func_perms/content/sitemap'] = "";
$INFO['func_perms/content/get_page_url'] = "";
$INFO['func_perms/content/get_page_id'] = "";
$INFO['func_perms/content/redirect'] = "";
$INFO['func_perms/content/get_describtion'] = "";
$INFO['func_perms/content/get_keywords'] = "";
$INFO['func_perms/content/insert'] = "";
$INFO['func_perms/content/header'] = "";
$INFO['func_perms/content/gen404'] = "";
$INFO['func_perms/content/json_get_tickets'] = "";

$INFO['func_perms/sitetree'] = "Управление контентом";
$INFO['func_perms/sitetree/rec_tree'] = "";
$INFO['func_perms/sitetree/add_page'] = "";
$INFO['func_perms/sitetree/add_page_do'] = "";
$INFO['func_perms/sitetree/del_page'] = "";
$INFO['func_perms/sitetree/edit_page'] = "";
$INFO['func_perms/sitetree/edit_page_do'] = "";
$INFO['func_perms/sitetree/move_page'] = "";
$INFO['func_perms/sitetree/treelink_parse'] = "";
$INFO['func_perms/sitetree/treelink'] = "";
$INFO['func_perms/sitetree/edit_domain'] = "";
$INFO['func_perms/sitetree/edit_domain_do'] = "";
$INFO['func_perms/sitetree/insertimage'] = "";
$INFO['func_perms/sitetree/insertmacros'] = "";
$INFO['func_perms/sitetree/replace'] = "";
$INFO['func_perms/sitetree/json_load'] = "";
$INFO['func_perms/sitetree/json_move'] = "";
$INFO['func_perms/sitetree/json_copy'] = "";
$INFO['func_perms/sitetree/json_del'] = "";
$INFO['func_perms/sitetree/json_load_hierarchy'] = "";

// imanager
$INFO['func_perms/sitetree/json_remove_imanager_object'] = "";
$INFO['func_perms/sitetree/json_create_imanager_object'] = "";
$INFO['func_perms/sitetree/json_get_images_panel'] = "";


$INFO['func_perms/sitetree/templates_do'] = "";


$INFO['func_perms/tickets'] = "Работа с заметками";
$INFO['func_perms/tickets/json_add_ticket'] = "";
$INFO['func_perms/tickets/json_del_ticket'] = "";
$INFO['func_perms/tickets/json_update_ticket'] = "";


$SQL_INSTALL = Array();

$COMPONENTS = array();

$COMPONENTS[0] = "./classes/modules/content/__admin.php";
$COMPONENTS[1] = "./classes/modules/content/__antispam.php";
$COMPONENTS[2] = "./classes/modules/content/__custom.php";
$COMPONENTS[3] = "./classes/modules/content/__editor.php";
$COMPONENTS[4] = "./classes/modules/content/__events.php";
$COMPONENTS[5] = "./classes/modules/content/__imanager.php";
$COMPONENTS[6] = "./classes/modules/content/__json.php";
$COMPONENTS[7] = "./classes/modules/content/__lib.php";
$COMPONENTS[8] = "./classes/modules/content/__tickets.php";
$COMPONENTS[9] = "./classes/modules/content/class.php";
$COMPONENTS[10] = "./classes/modules/content/events.php";
$COMPONENTS[11] = "./classes/modules/content/i18n.en.php";
$COMPONENTS[12] = "./classes/modules/content/i18n.php";
$COMPONENTS[13] = "./classes/modules/content/lang.en.php";
$COMPONENTS[14] = "./classes/modules/content/lang.php";
$COMPONENTS[15] = "./classes/modules/content/methods/pages/__pagesByAccountTags.lib.php";
$COMPONENTS[16] = "./classes/modules/content/methods/pages/__pagesByDomainTags.lib.php";
$COMPONENTS[17] = "./classes/modules/content/methods/pages/__pages_mklist_by_tags.lib.php";
$COMPONENTS[18] = "./classes/modules/content/methods/tags/__tagsAccountCloud.lib.php";
$COMPONENTS[19] = "./classes/modules/content/methods/tags/__tagsAccountEfficiencyCloud.lib.php";
$COMPONENTS[20] = "./classes/modules/content/methods/tags/__tagsAccountUsageCloud.lib.php";
$COMPONENTS[21] = "./classes/modules/content/methods/tags/__tagsDomainCloud.lib.php";
$COMPONENTS[22] = "./classes/modules/content/methods/tags/__tagsDomainEfficiencyCloud.lib.php";
$COMPONENTS[23] = "./classes/modules/content/methods/tags/__tagsDomainUsageCloud.lib.php";
$COMPONENTS[24] = "./classes/modules/content/methods/tags/__tags_mk_cloud.lib.php";
$COMPONENTS[25] = "./classes/modules/content/methods/tags/__tags_mk_eff_cloud.lib.php";
$COMPONENTS[26] = "./classes/modules/content/permissions.php";
$COMPONENTS[27] = "./classes/modules/content/update.php";


?>