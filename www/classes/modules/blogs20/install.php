<?php

$INFO = Array();
$INFO['verison']      = "2.0.0.0";

$INFO['name']     = "blogs20";
$INFO['filename'] = "modules/blogs20/class.php";
$INFO['config']   = "1";
$INFO['ico']      = "ico_blogs20";
$INFO['default_method']       = "blogsList";
$INFO['default_method_admin'] = "posts";

$INFO['func_perms'] 	   = "";
$INFO['func_perms/common'] = "Основные права на использование модуля";
$INFO['func_perms/add']    = "Добавление контента";
$INFO['func_perms/admin']  = "Основные права на управление модулем";

$INFO['paging'] 		 = "";
$INFO['paging/blogs']    = "10";
$INFO['paging/posts']    = "10";
$INFO['paging/comments'] = "50";

$INFO['autocreate_path'] = "/";
$INFO['blogs_per_user']  = "5";
$INFO['allow_guest_comments'] = "0";

$INFO['notifications'] = '';
$INFO['notifications/on_comment_add'] = '1';

$COMPONENTS = array();

$COMPONENTS[0] = "./classes/modules/blogs20/__admin.php";
$COMPONENTS[1] = "./classes/modules/blogs20/__custom.php";
$COMPONENTS[2] = "./classes/modules/blogs20/__events_handlers.php";
$COMPONENTS[3] = "./classes/modules/blogs20/__import.php";
$COMPONENTS[4] = "./classes/modules/blogs20/class.php";
$COMPONENTS[5] = "./classes/modules/blogs20/events.php";
$COMPONENTS[6] = "./classes/modules/blogs20/i18n.en.php";
$COMPONENTS[7] = "./classes/modules/blogs20/i18n.php";
$COMPONENTS[8] = "./classes/modules/blogs20/lang.php";
$COMPONENTS[9] = "./classes/modules/blogs20/permissions.php";


?>
