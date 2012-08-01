<?php
	class payonlinePayment extends payment {
		public function validate() { return true; }

		public function process($template = null) {
			$this->order->order();
			$currency    = strtoupper( mainConfiguration::getInstance()->get('system', 'default-currency') );
			// NB! Possible values for PayOnline are RUB (not RUR!), EUR and USD
			if(!in_array($currency, array('RUB', 'EUR', 'USD'))) {
				$currency = 'RUB';
			}
			$merchantId  = $this->object->merchant_id;
			$privateKey  = $this->object->private_key;
			$orderId     = $this->order->getId();
			$amount      = number_format($this->order->getActualPrice(), 2, '.', '');			
			$keyString   = "MerchantId={$merchantId}&OrderId={$orderId}&Amount={$amount}&Currency={$currency}&PrivateSecurityKey={$privateKey}";
			$securityKey = md5($keyString);			
			$formAction  = "?MerchantId={$merchantId}&OrderId={$orderId}&Amount={$amount}&Currency={$currency}&SecurityKey={$securityKey}&order-id={$orderId}";
			$formAction  = "https://secure.payonlinesystem.com/ru/payment/" . $formAction;
			$param = array();
			$param['formAction'] 	= $formAction;
			$param['MerchantId'] 	= $merchantId;
			$param['OrderId'] 	 	= $orderId;
			$param['Amount'] 	 	= $amount;
			$param['Currency'] 	 	= $currency;
			$param['SecurityKey'] 	= $securityKey;
			$param['orderId'] 	 	= $orderId;
			$param['ReturnUrl']		= 'http://' . cmsController::getInstance()->getCurrentDomain()->getHost();
			$this->order->setPaymentStatus('initialized');
			list($templateString) = def_module::loadTemplates("emarket/payment/payonline/".$template, "form_block");
			return def_module::parseTemplate($templateString, $param);
		}

		public function poll() {
			$dateTime      = getRequest('DateTime');
			$transactionId = getRequest('TransactionID');
			$orderId       = getRequest('OrderId');
			$amount        = getRequest('Amount');
			$currency      = getRequest('Currency');
			$securityKey   = getRequest('SecurityKey');
			$privateKey    = $this->object->private_key;
			$keyString     = "DateTime={$dateTime}&TransactionID={$transactionId}&OrderId={$orderId}&Amount={$amount}&Currency={$currency}&PrivateSecurityKey={$privateKey}";
			$checkKey      = md5($keyString);
			if(strcasecmp($checkKey, $securityKey) == 0) {				
				$this->order->setPaymentStatus('accepted');
			}
			$this->order->payment_document_num = $transactionId;
			$cmsController = cmsController::getInstance();
			if($emarket = $cmsController->getModule("emarket")) {
				$host = "http://".$cmsController->getCurrentDomain()->getHost();
				
				if ( umiHierarchy::getInstance()->getIdByPath("resultpayonline") ) {
					$host  .= "/resultpayonline/";
				}
				
				$emarket->redirect($host);
			}
		}
	};
?>