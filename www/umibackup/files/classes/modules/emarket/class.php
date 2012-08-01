<?php
	class emarket extends def_module {
		public $iMaxCompareElements;

		public function __construct() {
			parent::__construct();
			$regedit = regedit::getInstance();
			$config = mainConfiguration::getInstance();
			
			
			$this->iMaxCompareElements = $config->get('modules', 'emarket.compare.max-items');
			
			if(empty($this->iMaxCompareElements)) {
				$this->iMaxCompareElements = 3;
			}
			
			if($this->iMaxCompareElements<=1) {
				$this->iMaxCompareElements = 3;
			}
			

			if(cmsController::getInstance()->getCurrentMode() == "admin") {
				$commonTabs = $this->getCommonTabs();
				$configTabs = $this->getConfigTabs();
				
				$this->__loadLib("__admin.php");
				$this->__implement("__emarket_admin");
				
				$this->__loadLib("__admin_orders.php");
				$this->__implement("__emarket_admin_orders");
				
				
				if($commonTabs) $commonTabs->add("orders", array('order_edit'));
				
				if($configTabs) $configTabs->add("config");
				if($configTabs) $configTabs->add("social_networks");
			
				if($regedit->getVal('//modules/emarket/enable-discounts')) {
					if($commonTabs) $commonTabs->add("discounts", array('discount_add', 'discount_edit'));
					
					$this->__loadLib("__admin_discounts.php");
					$this->__implement("__emarket_admin_discounts");
				}
				
				if($regedit->getVal('//modules/emarket/enable-delivery')) {
					if($commonTabs) $commonTabs->add("delivery", array('delivery_add', 'delivery_edit', 'delivery_address_edit'));
					$this->__loadLib("__admin_delivery.php");
					$this->__implement("__emarket_admin_delivery");
				}
				
				if($regedit->getVal('//modules/emarket/enable-payment')) {
					if($commonTabs) $commonTabs->add("payment", array('payment_add', 'payment_edit'));
					
					$this->__loadLib("__admin_payments.php");
					$this->__implement("__emarket_admin_payment");
				}
				
				if($regedit->getVal('//modules/emarket/enable-currency')) {
					if($commonTabs) $commonTabs->add("currency", array());
					
					$this->__loadLib("__admin_currency.php");
					$this->__implement("__emarket_admin_currency");
				}
				
				if($regedit->getVal('//modules/emarket/enable-stores')) {
					if($commonTabs) $commonTabs->add("stores", array());
					
					$this->__loadLib("__admin_stores.php");
					$this->__implement("__emarket_admin_stores");
				}
				
				$this->__loadLib("__custom_adm.php");
				$this->__implement("__emarket_custom_admin");

			}
			
			$this->__loadLib("__purchasing.php");
			$this->__implement("__emarket_purchasing");
			
			$this->__loadLib("__discounts.php");
			$this->__implement("__emarket_discounts");
			
			$this->__loadLib("__stores.php");
			$this->__implement("__emarket_stores");
			
			$this->__loadLib("__currency.php");
			$this->__implement("__emarket_currency");
			
			$this->__loadLib("__compare.php");
			$this->__implement("__emarket_compare");

			$this->__loadLib("__notification.php");
			$this->__implement("__emarket_notification");
			
			$this->__loadLib("__events.php");
			$this->__implement("__emarket_events");
			
			$this->__loadLib("__custom.php");
			$this->__implement("__emarket_custom");

			$this->__loadLib("__social.php");
			$this->__implement("__emarket_social");
		}
		
		public function personal($template = 'default') {
			list($tpl_block) = def_module::loadTemplates("emarket/".$template, "personal");
			return def_module::parseTemplate($tpl_block, array());
		}


		public function customerDeliveryList($template = 'default') {
			$this->__loadLib("__delivery.php");
			$this->__implement("__emarket_delivery");
			
			$order = $this->getBasketOrder();
			return $this->renderDeliveryAddressesList($order, $template);
		}

		public function getObjectEditLink($objectId, $type = false) {
			switch($type) {
				case 'order':
					return $this->pre_lang . "/admin/emarket/order_edit/{$objectId}/";

				case 'discount':
					return $this->pre_lang . "/admin/emarket/discount_edit/{$objectId}/";

				case 'currency':
					return $this->pre_lang . "/admin/emarket/currency_edit/{$objectId}/";

				case 'delivery':
					return $this->pre_lang . "/admin/emarket/delivery_edit/{$objectId}/";
				
				case 'payment':
					return $this->pre_lang . "/admin/emarket/payment_edit/{$objectId}/";
				
				case 'store':
					return $this->pre_lang . "/admin/emarket/store_edit/{$objectId}/";

				default: {
					return false;
				}
			}
		}
	};
?>
