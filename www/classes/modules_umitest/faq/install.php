<?php
$INFO = Array();

$INFO['version'] = "2.0.0.0";
$INFO['version_line'] = "pro";

$INFO['name'] = "faq";
$INFO['title'] = "FAQ";
$INFO['filename'] = "modules/faq/class.php";
$INFO['config'] = "1";
$INFO['ico'] = "ico_faq";
$INFO['default_method'] = "project";
$INFO['default_method_admin'] = "projects_list";
$INFO['is_indexed'] = "0";

$INFO['per_page'] = "10";

$INFO['func_perms'] = "";
$INFO['func_perms/projects'] = "Просмотр базы знаний";
$INFO['func_perms/projects/project'] = "";
$INFO['func_perms/projects/category'] = "";
$INFO['func_perms/projects/question'] = "";

$INFO['func_perms/post_question'] = "Возможность задать свой вопрос";
$INFO['func_perms/post_question/addQuestionForm'] = "";

$INFO['func_perms/projects_list'] = "Редактирование базы знаний";
$INFO['func_perms/projects_list/project_add_do'] = "";
$INFO['func_perms/projects_list/project_edit'] = "";
$INFO['func_perms/projects_list/project_edit_do'] = "";
$INFO['func_perms/projects_list/project_blocking'] = "";
$INFO['func_perms/projects_list/project_del'] = "";

$INFO['func_perms/projects_list/categories_list'] = "";
$INFO['func_perms/projects_list/category_add'] = "";
$INFO['func_perms/projects_list/category_add_do'] = "";
$INFO['func_perms/projects_list/category_edit'] = "";
$INFO['func_perms/projects_list/category_edit_do'] = "";
$INFO['func_perms/projects_list/category_blocking'] = "";
$INFO['func_perms/projects_list/category_del'] = "";

$INFO['func_perms/projects_list/questions_list'] = "";
$INFO['func_perms/projects_list/question_add'] = "";
$INFO['func_perms/projects_list/question_add_do'] = "";
$INFO['func_perms/projects_list/question_edit'] = "";
$INFO['func_perms/projects_list/question_edit_do'] = "";
$INFO['func_perms/projects_list/question_blocking'] = "";
$INFO['func_perms/projects_list/question_del'] = "";


$SQL_INSTALL = Array();

$COMPONENTS = array();

$COMPONENTS[0] = "./classes/modules/faq/__admin.php";
$COMPONENTS[1] = "./classes/modules/faq/__custom.php";
$COMPONENTS[2] = "./classes/modules/faq/__event_handlers.php";
$COMPONENTS[3] = "./classes/modules/faq/class.php";
$COMPONENTS[4] = "./classes/modules/faq/events.php";
$COMPONENTS[5] = "./classes/modules/faq/i18n.en.php";
$COMPONENTS[6] = "./classes/modules/faq/i18n.php";
$COMPONENTS[7] = "./classes/modules/faq/lang.php";
$COMPONENTS[8] = "./classes/modules/faq/permissions.php";

?>
