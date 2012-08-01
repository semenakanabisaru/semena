<?php

	abstract class __emarket_admin_orders extends baseModuleAdmin {
		public function orders () {
			$this->setDataType("list");
			$this->setActionType("view");
			if($this->ifNotXmlMode()) return $this->doData();

			$limit = 20;
			$curr_page = (int) getRequest('p');
			$offset = $limit * $curr_page;

			$sel = new selector('objects');
			$sel->types('object-type')->name('emarket', 'order');
			$sel->where('name')->isNull(false);
            $sel->where('name')->notequals('dummy');
			$sel->limit($offset, $limit);
			if(!getRequest('order_filter')) $sel->order('order_date')->desc();
			selectorHelper::detectFilters($sel);

			$domains = getRequest('domain_id');
			if(is_array($domains) && sizeof($domains)) {
				$domainsCollection = domainsCollection::getInstance();
				if(sizeof($domainsCollection->getList()) > 1) {
					$sel->where('domain_id')->equals($domains[0]);
				}
			}

			$this->setDataRange($limit, $offset);
			$data = $this->prepareData($sel->result, "objects");
			$this->setData($data, $sel->length);
			return $this->doData();
		}


		public function order_edit() {
			$object = $this->expectObject("param0", true);
			$mode = (string) getRequest('param1');
			$objectId = $object->getId();

			$this->setHeaderLabel("header-users-edit-" . $this->getObjectTypeMethod($object));

			$this->checkSv($objectId);

			$inputData = Array(	"object"	=> $object,
						"allowed-element-types"	=> Array('emarket', 'order')
			);

			if($mode == "do") {

				$oldDeliveryPrice = $object->getValue('delivery_price');

				$object = $this->saveEditedObjectData($inputData);

				$newDeliveryPrice = $object->getValue('delivery_price');

				$order = order::get($object->id);

				$amounts = getRequest('order-amount-item');
				$dels = getRequest('order-del-item');

				$isChanged = false;
				if(is_array($amounts)) foreach($amounts as $itemId => $amount) {
					$item = $order->getItem($itemId);
					if($item instanceof orderItem) {
						if($item->getAmount() != $amount) {
							$item->setAmount($amount);
							$item->commit();
							$isChanged = true;
						}
					}
				}

				if(is_array($dels)) foreach($dels as $itemId) {
					$item = orderItem::get($itemId);
					if($item instanceof orderItem) {
						$order->removeItem($item);
						$isChanged = true;
					}
				}

				if($isChanged) {
					$order->refresh();
					$order->commit();
				}

				if ($oldDeliveryPrice != $newDeliveryPrice && !$isChanged) {
					$originalPrice = $object->getValue('total_original_price');
					$totalPrice = $originalPrice;

					$discount = $order->getDiscount();
					if($discount instanceof discount) {
						$totalPrice = $discount->recalcPrice($originalPrice);
					}
					$totalPrice += $newDeliveryPrice;
					$object->setValue('total_price', $totalPrice);
					$object->commit();
				}

				$this->chooseRedirect();
			}

			$this->setDataType("form");
			$this->setActionType("modify");

			$data = $this->prepareData($inputData, "object");

			$this->setData($data);
			return $this->doData();
		}

		public function order_printable() {
			$object = $this->expectObject("param0", true);
			$typeId = umiObjectTypesCollection::getInstance()->getBaseType('emarket', 'order');
			if($object->getTypeId() != $typeId) {
				throw new wrongElementTypeAdminException(getLabel("error-unexpected-element-type"));
			}
			$orderId = $object->getId();
			$uri = "uobject://{$orderId}/?transform=sys-tpls/emarket-order-printable.xsl";
			//$uri = "uobject://{$orderId}/";
			$result = file_get_contents($uri);
			$buffer = outputBuffer::current();
			$buffer->charset('utf-8');
			$buffer->contentType('text/html');
			//$buffer->contentType('text/xml');
			$buffer->clear();
			$buffer->push($result);
			$buffer->end();
			return;
		}

	};
?>