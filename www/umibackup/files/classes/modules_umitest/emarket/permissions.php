<?php
	$permissions = array(
		'purchasing' => array(
			'price', 'stores', 'ordersList', 'basketAddLink', 'applyPriceCurrency',
			'order', 'basket', 'cart',
			'purchase', 'gateway', 'callback', 'receipt', 'removeDeliveryAddress',
			'currencySelector', 'getCustomerInfo', 'selectCurrency', 'discountInfo'
		),

		'personal' => array(
			'ordersList', 'customerDeliveryList'
		),

		'compare' => array(
			'getCompareList', 'getCompareLink',
			'addToCompare', 'removeFromCompare', 'resetCompareList',
			'jsonAddToCompareList', 'jsonRemoveFromCompare', 'jsonResetCompareList'
		),

		'control' => array(
			'orders', 'ordersList', 'del', 'order_edit', 'order_printable',  'order.edit',
			'currency', 'currency_add', 'currency_edit', 'currency.edit',
			'delivery', 'delivery_add', 'delivery_edit', 'delivery_address_edit', 'delivery.edit',
			'discounts', 'discount_add', 'discount_edit', 'getModificators', 'getRules', 'discount', 'discount.edit',
			'payment', 'payment_add', 'payment_edit', 'payment.edit',
			'stores', 'store_add', 'store_edit', 'store', 'store.edit'
		)
	);
?>
