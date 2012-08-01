<?php

    $INFO = Array();

    $INFO['name'] = "doc_generator";
    $INFO['title'] = "Генератор документов";
    $INFO['filename'] = "modules/doc_generator/class.php";
    $INFO['config'] = "0";
    $INFO['ico'] = "ico_doc_generator";
    $INFO['default_method_admin'] = "order_list";

    $INFO['func_perms'] = "";

    $COMPONENTS = array();

    $COMPONENTS[0] = "./classes/modules/doc_generator/class.php";
    $COMPONENTS[1] = "./classes/modules/doc_generator/__admin.php";
    $COMPONENTS[2] = "./classes/modules/doc_generator/lang.php";
    $COMPONENTS[3] = "./classes/modules/doc_generator/i18n.php";
    $COMPONENTS[4] = "./classes/modules/doc_generator/permissions.php";
    $COMPONENTS[5] = "./classes/modules/doc_generator/package_labels.php";
    $COMPONENTS[6] = "./classes/modules/doc_generator/shipment_blank.php";
    $COMPONENTS[7] = "./classes/modules/doc_generator/cash_on_delivery_blank.php";
    $COMPONENTS[7] = "./classes/modules/doc_generator/functions.php";

?>
