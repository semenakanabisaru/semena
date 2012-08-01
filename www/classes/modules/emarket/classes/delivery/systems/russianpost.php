<?php
	class russianpostDelivery extends delivery {
		public function validate(order $order) {
			return true;
		}

		public function getDeliveryPrice(order $order) {
			$objects = umiObjectsCollection::getInstance();

			$orderPrice = $order->getActualPrice();

			$weight = 0;
			$items  = $order->getItems();
			foreach($items as $item) {
				$element    = $item->getItemElement();
				$itemWeight = (int)$element->getValue("weight");
				if($itemWeight != 0) {
					$weight += $itemWeight * $item->getAmount();
				} else {
					return "Невозможно автоматически определить стоимость";
				}
			}

			$deliveryAddress = $objects->getObject( $order->delivery_address );
			if(!$deliveryAddress) {
				return "Невозможно автоматически определить стоимость";
			}


			$viewPost = $objects->getObject( $this->object->viewpost )->getValue("identifier");
			$typePost = $objects->getObject( $this->object->typepost )->getValue("identifier");
			$zip	  = $deliveryAddress->getValue("index");
			$value    = $this->object->setpostvalue ? ceil($order->getActualPrice()) : 0;

			$url = "http://www.russianpost.ru/autotarif/Autotarif.aspx?viewPost={$viewPost}&countryCode=643&typePost={$typePost}&weight={$weight}&value1={$value}&postOfficeId={$zip}";
			$content = umiRemoteFileGetter::get($url);
		
			if (preg_match("/span\s+id=\"TarifValue\">([^<]+)<\/span/i", $content, $match)) {
				$price = floatval(str_replace(",", ".", trim($match[1])));
				if ($price > 0) {
					return $price;
				} elseif (preg_match("/span\s+id=\"lblErrStr\">([^<]+)<\/span/i", $content, $match)) {
					return $match[1];
				}
			}
			
			return "Не определено. Свяжитесь с менеджером для уточнения информации.";
		}
	};
?>