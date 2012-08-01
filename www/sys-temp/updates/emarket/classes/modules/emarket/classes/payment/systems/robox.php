<?php
	class roboxPayment extends payment {
		public function validate() { return true; }

		public function process($template = null) {
			$this->order->order();
			$login = $this->object->login;
			$password = $this->object->password1;
			$amount = number_format($this->order->getActualPrice(), 2, '.', '');
			$sign = md5("{$login}:{$amount}:{$this->order->id}:{$password}:shp_orderId={$this->order->id}");
			$param = array();
			$param['formAction'] = $this->object->test_mode ? "http://test.robokassa.ru/Index.aspx" : "https://merchant.roboxchange.com/Index.aspx";
			$param['MrchLogin']  = $login;
			$param['OutSum']  	 = $amount;
			$param['InvId']  	 = $this->order->id;
			$param['Desc']  	 = "Payment for order {$this->order->id}";
			$param['SignatureValue'] = $sign;
			$param['shp_orderId']    = $this->order->id;
			$param['IncCurrLabel'] = "";
			$param['Culture']  	 = strtolower(cmsController::getInstance()->getCurrentLang()->getPrefix());
			$this->order->setPaymentStatus('initialized');
			list($templateString) = def_module::loadTemplates("emarket/payment/robokassa/".$template, "form_block");
			return def_module::parseTemplate($templateString, $param);
		}

		public function poll() {
			$amount  = getRequest("OutSum");
			$invoice = getRequest("InvId");
			$sign    = getRequest("SignatureValue");
			$orderId = getRequest("shp_orderId");
			$password = $this->object->password2;
			$checkSign = md5("{$amount}:{$invoice}:{$password}:shp_orderId={$orderId}");
			$buffer = outputBuffer::current();
			$buffer->clear();
			$buffer->contentType("text/plain");
			if(strcasecmp($checkSign, $sign) == 0) {
				$this->order->setPaymentStatus("accepted");
				$this->order->payment_document_num = $invoice;
				$buffer->push("OK{$invoice}");
			} else {
				$buffer->push("failed");
			}
			$buffer->end();
		}
	};
?>