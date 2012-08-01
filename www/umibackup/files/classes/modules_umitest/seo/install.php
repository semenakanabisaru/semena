<?php

$INFO = Array();

$INFO['name'] = "seo";
$INFO['title'] = "SEO";
$INFO['description'] = "SEO";
$INFO['filename'] = "modules/seo/class.php";
$INFO['config'] = "0";
$INFO['ico'] = "ico_seo";
$INFO['default_method'] = "show";
$INFO['default_method_admin'] = "seo";

$INFO['func_perms'] = "Functions, that should have their own permissions.";
$INFO['func_perms/seo'] = "SEO-функции";


$SQL_INSTALL = Array();

$COMPONENTS = array();

$COMPONENTS[0] = "./classes/modules/seo/.htaccess";
$COMPONENTS[1] = "./classes/modules/seo/__admin.php";
$COMPONENTS[2] = "./classes/modules/seo/class.php";
$COMPONENTS[3] = "./classes/modules/seo/classes/org/me/hello/Base64Coder.class";
$COMPONENTS[4] = "./classes/modules/seo/classes/org/me/hello/MyApplet.class";
$COMPONENTS[5] = "./classes/modules/seo/classes/org/me/hello/URLRequester.class";
$COMPONENTS[6] = "./classes/modules/seo/crossdomain.xml";
$COMPONENTS[7] = "./classes/modules/seo/forms.php";
$COMPONENTS[8] = "./classes/modules/seo/i18n.en.php";
$COMPONENTS[9] = "./classes/modules/seo/i18n.php";
$COMPONENTS[10] = "./classes/modules/seo/js/AC_RunActiveContent.js";
$COMPONENTS[11] = "./classes/modules/seo/js/base64_decode.js";
$COMPONENTS[12] = "./classes/modules/seo/js/wrapper.js";
$COMPONENTS[13] = "./classes/modules/seo/lang.php";
$COMPONENTS[14] = "./classes/modules/seo/permissions.php";
$COMPONENTS[15] = "./classes/modules/seo/seo.html";
$COMPONENTS[16] = "./classes/modules/seo/seo.ie.html";
$COMPONENTS[17] = "./classes/modules/seo/seo.opt.html";
$COMPONENTS[18] = "./classes/modules/seo/siteauditor.swf";

?>