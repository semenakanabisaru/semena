<?php

$INFO = Array();

$INFO['version'] = "2.8.0.0";
$INFO['version_line'] = "pro";

$INFO['name'] = "emarket";
$INFO['title'] = "Интернет-магазин";
$INFO['filename'] = "modules/emarket/class.php";
$INFO['config'] = "1";
$INFO['ico'] = "ico_eshop";
$INFO['default_method_admin'] = "orders";

$INFO['is_indexed'] = "0";

$INFO['func_perms'] = "";

$INFO['enable-discounts'] = "1";
$INFO['enable-currency'] = "1";
$INFO['enable-stores'] = "1";
$INFO['enable-payment'] = "1";
$INFO['enable-delivery'] = "1";
$INFO['delivery-with-address'] = "0";

$COMPONENTS = array();

$COMPONENTS[0] = "./classes/modules/emarket/__admin.php";
$COMPONENTS[1] = "./classes/modules/emarket/__admin_currency.php";
$COMPONENTS[2] = "./classes/modules/emarket/__admin_delivery.php";
$COMPONENTS[3] = "./classes/modules/emarket/__admin_discounts.php";
$COMPONENTS[4] = "./classes/modules/emarket/__admin_orders.php";
$COMPONENTS[5] = "./classes/modules/emarket/__admin_payments.php";
$COMPONENTS[6] = "./classes/modules/emarket/__admin_stores.php";
$COMPONENTS[7] = "./classes/modules/emarket/__compare.php";
$COMPONENTS[8] = "./classes/modules/emarket/__currency.php";
$COMPONENTS[9] = "./classes/modules/emarket/__custom.php";
$COMPONENTS[10] = "./classes/modules/emarket/__custom_adm.php";
$COMPONENTS[11] = "./classes/modules/emarket/__delivery.php";
$COMPONENTS[12] = "./classes/modules/emarket/__discounts.php";
$COMPONENTS[13] = "./classes/modules/emarket/__events.php";
$COMPONENTS[14] = "./classes/modules/emarket/__notification.php";
$COMPONENTS[15] = "./classes/modules/emarket/__payments.php";
$COMPONENTS[16] = "./classes/modules/emarket/__purchasing.php";
$COMPONENTS[17] = "./classes/modules/emarket/__required.php";
$COMPONENTS[18] = "./classes/modules/emarket/__stores.php";
$COMPONENTS[19] = "./classes/modules/emarket/class.php";
$COMPONENTS[20] = "./classes/modules/emarket/classes/currency/currencyUpdater.php";
$COMPONENTS[21] = "./classes/modules/emarket/classes/currency/updaters/rur.php";
$COMPONENTS[22] = "./classes/modules/emarket/classes/currency/updaters/usd.php";
$COMPONENTS[23] = "./classes/modules/emarket/classes/customer/customer.php";
$COMPONENTS[24] = "./classes/modules/emarket/classes/delivery/delivery.php";
$COMPONENTS[25] = "./classes/modules/emarket/classes/delivery/systems/courier.php";
$COMPONENTS[26] = "./classes/modules/emarket/classes/delivery/systems/russianpost.php";
$COMPONENTS[27] = "./classes/modules/emarket/classes/delivery/systems/self.php";
$COMPONENTS[28] = "./classes/modules/emarket/classes/discounts/discount.php";
$COMPONENTS[29] = "./classes/modules/emarket/classes/discounts/discountModificator.php";
$COMPONENTS[30] = "./classes/modules/emarket/classes/discounts/discountRule.php";
$COMPONENTS[31] = "./classes/modules/emarket/classes/discounts/discounts/itemDiscount.php";
$COMPONENTS[32] = "./classes/modules/emarket/classes/discounts/discounts/orderDiscount.php";
$COMPONENTS[33] = "./classes/modules/emarket/classes/discounts/modificators/absolute.php";
$COMPONENTS[34] = "./classes/modules/emarket/classes/discounts/modificators/proc.php";
$COMPONENTS[35] = "./classes/modules/emarket/classes/discounts/rules/allOrdersPrices.php";
$COMPONENTS[36] = "./classes/modules/emarket/classes/discounts/rules/dateRange.php";
$COMPONENTS[37] = "./classes/modules/emarket/classes/discounts/rules/items.php";
$COMPONENTS[38] = "./classes/modules/emarket/classes/discounts/rules/orderPrice.php";
$COMPONENTS[39] = "./classes/modules/emarket/classes/discounts/rules/relatedItems.php";
$COMPONENTS[40] = "./classes/modules/emarket/classes/discounts/rules/userGroups.php";
$COMPONENTS[41] = "./classes/modules/emarket/classes/discounts/rules/users.php";
$COMPONENTS[42] = "./classes/modules/emarket/classes/orders/items/custom.php";
$COMPONENTS[43] = "./classes/modules/emarket/classes/orders/items/digital.php";
$COMPONENTS[44] = "./classes/modules/emarket/classes/orders/items/optioned.php";
$COMPONENTS[45] = "./classes/modules/emarket/classes/orders/number/default.php";
$COMPONENTS[46] = "./classes/modules/emarket/classes/orders/order.php";
$COMPONENTS[47] = "./classes/modules/emarket/classes/orders/orderItem.php";
$COMPONENTS[48] = "./classes/modules/emarket/classes/payment/payment.php";
$COMPONENTS[49] = "./classes/modules/emarket/classes/payment/systems/chronopay.php";
$COMPONENTS[50] = "./classes/modules/emarket/classes/payment/systems/courier.php";
$COMPONENTS[51] = "./classes/modules/emarket/classes/payment/systems/invoice.php";
$COMPONENTS[52] = "./classes/modules/emarket/classes/payment/systems/payonline.php";
$COMPONENTS[53] = "./classes/modules/emarket/classes/payment/systems/rbk.php";
$COMPONENTS[54] = "./classes/modules/emarket/classes/payment/systems/receipt.php";
$COMPONENTS[55] = "./classes/modules/emarket/classes/payment/systems/robox.php";
$COMPONENTS[56] = "./classes/modules/emarket/classes/payment/systems/yandex.php";
$COMPONENTS[57] = "./classes/modules/emarket/classes/stores/stores.php";
$COMPONENTS[58] = "./classes/modules/emarket/events.php";
$COMPONENTS[59] = "./classes/modules/emarket/i18n.en.php";
$COMPONENTS[60] = "./classes/modules/emarket/i18n.php";
$COMPONENTS[61] = "./classes/modules/emarket/includes.php";
$COMPONENTS[62] = "./classes/modules/emarket/lang.php";
$COMPONENTS[63] = "./classes/modules/emarket/permissions.php";





?>
