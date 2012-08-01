<?php
	//TODO: Include subclasses here...
	$config = mainConfiguration::getInstance();
	
	$includePath = CURRENT_WORKING_DIR . '/classes/modules/emarket/classes';
	require $includePath . '/discounts/discount.php';
	require $includePath . '/discounts/discountModificator.php';
	require $includePath . '/discounts/discountRule.php';
	discount::init();
	
	require $includePath . '/orders/order.php';
	require $includePath . '/orders/orderItem.php';
	require $includePath . '/orders/number/' . $config->get('modules', 'emarket.numbers') . '.php';
	
	require $includePath . '/currency/currencyUpdater.php';
	
	require $includePath . '/delivery/delivery.php';
	require $includePath . '/payment/payment.php';
	
	require $includePath . '/customer/customer.php';

	require $includePath . '/stores/stores.php';

	require $includePath . '/social/social_callbacks_handler.php';
	
	interface iOrderStep {
		public function renderStep();
		public function executeStep();
	};
	
	interface iOrderNumber {
		public function __construct(order $order);
		public function number();
	};
?>