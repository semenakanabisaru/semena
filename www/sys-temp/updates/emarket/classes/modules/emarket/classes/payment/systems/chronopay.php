<?php
	class chronopayPayment extends payment {
		public function validate() { return true; }
		
		public function process($template = null) {
			$productId = $this->object->product_id;
			$siteId    = $this->object->site_id;

			$productName = $this->order->getId();

			$cmsController = cmsController::getInstance();

			$language = strtolower($cmsController->getCurrentLang()->getPrefix());

			switch ($language) {
				case 'ru':
					$language = 'ru';
					break;				
				default:
					$language = 'en';
					break;
			}

			$this->order->order();

			$productPrice = $this->order->getActualPrice();
			$secretCode   = $this->object->secret;
			$priceString  = number_format($productPrice, 2, '.', '');
			$sign = md5($productId . '-' . $priceString . '-' . $secretCode);
			
			$answerUrl = $cmsController->getCurrentDomain()->getHost() .
						 "/emarket/gateway/" . $this->order->getId() . "/";

			$param = array();
			$param["formAction"]    = "https://payments.chronopay.com/index.php";
			$param["product_id"]	= $productId;			
			$param["product_price"]	= $productPrice;
			$param["language"]		= $language;
			$param["order_id"]		= $this->order->getId();			
			$param["cb_type"]		= "P";
			$param["cb_url"]		= $answerUrl;
			$param["decline_url"]	= $cmsController->getCurrentDomain()->getHost();
			$param["sign"]			= $sign;
			$this->order->setPaymentStatus('initialized');
			list($templateString) = def_module::loadTemplates("emarket/payment/chronopay/".$template, "form_block");
			return def_module::parseTemplate($templateString, $param);
		}
		
		public function poll() {
			if (!isset($_SERVER['REMOTE_ADDR']) || $_SERVER['REMOTE_ADDR'] !== '207.97.254.211') {
				return false;
			}
			$secretCode = $this->object->secret;
			$customerId = getRequest('customer_id');
			$transactionId   = getRequest('transaction_id');
			$transactionType = getRequest('transaction_type');
			$total      = getRequest('total');
			$hashString = md5($secretCode . $customerId . $transactionId . $transactionType . $total);
			if(strcasecmp($hashString, getRequest('sign') ) != 0) {
				return false;
			}
			
			$this->order->setPaymentStatus('accepted');
			
			$this->order->payment_document_num = $transactionId;
		}
	};
?>