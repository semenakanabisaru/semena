<?php

    $INFO = Array();

    $INFO['name'] = "exchange";
    $INFO['filename'] = "modules/exchange/class.php";
    $INFO['config'] = "0";
    $INFO['ico'] = "exchange";
    $INFO['default_method'] = "import";
    $INFO['default_method_admin'] = "import";

    $INFO['func_perms'] = "";


    $COMPONENTS = array();

    $COMPONENTS[0] = "./classes/modules/exchange/__admin.php";
	$COMPONENTS[1] = "./classes/modules/exchange/__auto.php";
	$COMPONENTS[2] = "./classes/modules/exchange/__custom.php";
	$COMPONENTS[3] = "./classes/modules/exchange/__export.php";
	$COMPONENTS[4] = "./classes/modules/exchange/__import.php";
	$COMPONENTS[5] = "./classes/modules/exchange/class.php";
	$COMPONENTS[6] = "./classes/modules/exchange/i18n.en.php";
	$COMPONENTS[7] = "./classes/modules/exchange/i18n.php";
	$COMPONENTS[8] = "./classes/modules/exchange/permissions.php";
?>