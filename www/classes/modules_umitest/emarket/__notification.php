<?php
	abstract class __emarket_notification {
		public function notifyOrderStatusChange(order $order, $changedProperty) {
			$order->need_export = true;
			if($changedProperty == "status_id") {
				$order->status_change_date = new umiDate();
			}
			if(order::getCodeByStatus($order->getPaymentStatus()) == "accepted" && !$order->delivery_allow_date) {
				$sel = new selector('objects');
				$sel->types('object-type')->name('emarket', 'delivery');
				if($sel->length) {
					$order->delivery_allow_date = new umiDate();
				}
			}
			$statusId = $order->getValue($changedProperty);
			$codeName = order::getCodeByStatus($statusId);
			if($changedProperty == 'status_id' && (!$statusId || $codeName == 'payment')) return;
			$this->sendCustomerNotification($order, $changedProperty, $codeName);
			if($changedProperty == 'status_id' && $codeName == 'waiting') {
				$this->sendManagerNotification($order);
			}
		}
		public function sendCustomerNotification(order $order, $changedStatus, $codeName) {
			$customer = umiObjectsCollection::getInstance()->getObject($order->getCustomerId());
			$email    = $customer->email ? $customer->email : $customer->getValue("e-mail");
			if($email) {
				$name  = $customer->lname . " " .$customer->fname . " " . $customer->father_name;
				$langs = cmsController::getInstance()->langs;
				$statusString = "";
				switch($changedStatus) {
					case 'status_id' : {
							if($codeName == 'waiting') {
								$paymentStatusCodeName = order::getCodeByStatus($order->getPaymentStatus());
								$pkey = 'notification-status-payment-' . $paymentStatusCodeName;
								$okey = 'notification-status-' . $codeName;
								$statusString = ($paymentStatusCodeName == 'initialized') ?
													 ( (isset($langs[$okey]) ? ($langs[$okey] . " " . $langs['notification-and']) : "") . (isset($langs[$pkey]) ? (" ".$langs[$pkey]) : "" ) ) :
												( (isset($langs[$pkey]) ? ($langs[$pkey] . " " . $langs['notification-and']) : "") . (isset($langs[$okey]) ? (" ".$langs[$okey]) : "" ) );
							} else {
								$key = 'notification-status-' . $codeName;
								$statusString = isset($langs[$key]) ? $langs[$key] : "_";
							}
							break;
					}
					case 'payment_status_id': {
							$key = 'notification-status-payment-' . $codeName;
							$statusString = isset($langs[$key]) ? $langs[$key] : "_";
							break;
					}
					case 'delivery_status_id': {
							$key = 'notification-status-delivery-' . $codeName;
							$statusString = isset($langs[$key]) ? $langs[$key] : "_";
							break;
					}
				}
				$collection = umiObjectsCollection::getInstance();
				$paymentObject = $collection->getObject($order->payment_id);
				if($paymentObject) {
					$paymentType   = $collection->getObject($paymentObject->payment_type_id);
					$paymentClassName = $paymentType->class_name;
				} else {
					$paymentClassName = null;
				}
				$templateName  = ($paymentClassName == "receipt") ? "status_notification_receipt" : "status_notification";
				list($template) = def_module::loadTemplates("emarket/mail/default", $templateName);
				$param = array();
				$param["order_id"]   = $order->id;
				$param["order_name"] = $order->name;
				$param["order_number"] = $order->number;
				$param["status"]     = $statusString;
				$param["domain"]     = cmsController::getInstance()->getCurrentDomain()->getHost();
				if($paymentClassName == "receipt") {
					$param["receipt_signature"] = sha1("{$customer->id}:{$customer->email}:{$order->order_date}");
				}
				$content = def_module::parseContent($template, $param);
				$regedit  = regedit::getInstance();
				$letter   = new umiMail();
				$letter->addRecipient($email, $name);
				$letter->setFrom($regedit->getVal('//modules/emarket/from-email'),
								 $regedit->getVal('//modules/emarket/from-name'));
				$letter->setSubject($langs['notification-status-subject']);
				$letter->setContent($content);
				$letter->commit();
				$letter->send();
			}
		}
		public function sendManagerNotification(order $order) {
			$regedit  = regedit::getInstance();
			$emails	  = $regedit->getVal('//modules/emarket/manager-email');

			$letter = new umiMail();

			$recpCount = 0;
			foreach (explode(',' , $emails) as $recipient) {
				$recipient = trim($recipient);
				if (strlen($recipient)) {
					$letter->addRecipient($recipient);
					$recpCount++;
				}
			}
			if(!$recpCount) return;

			list($template) = def_module::loadTemplates("emarket/mail/default", "neworder_notification");
			try {
				$payment = payment::get($order->payment_id);
				$paymentName 	= $payment->name;
				$paymentStatus  = order::getCodeByStatus($order->getPaymentStatus());
			} catch(coreException $e) {
				$paymentName 	= "";
				$paymentStatus 	= "";
			}
			$param = array();
			$param["order_id"]       = $order->id;
			$param["order_name"]     = $order->name;
			$param["order_number"]   = $order->number;
			$param["payment_type"]   = $paymentName;
			$param["payment_status"] = $paymentStatus;
			$param["price"]          = $order->getActualPrice();
			$param["domain"]         = cmsController::getInstance()->getCurrentDomain()->getHost();
			$content = def_module::parseContent($template, $param);
			$langs = cmsController::getInstance()->langs;

			$letter->setFrom($regedit->getVal('//modules/emarket/from-email'),
							 $regedit->getVal('//modules/emarket/from-name'));
			$letter->setSubject($langs['notification-neworder-subject'] . " (#{$order->number})");
			$letter->setContent($content);
			$letter->commit();
			$letter->send();
		}
	};
?>