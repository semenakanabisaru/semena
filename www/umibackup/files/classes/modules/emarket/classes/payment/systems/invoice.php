<?php
	class invoicePayment extends payment {
		public function validate() {
			return true;
		}

		public function process($template = null) {

			list($tpl_block, $tpl_item) = def_module::loadTemplates("emarket/payment/invoice/".$template,
				'legal_person_block', 'legal_person_item');

			$collection = umiObjectsCollection::getInstance();
			$types  = umiObjectTypesCollection::getInstance();
			$typeId = $types->getBaseType("emarket", "legal_person");

			$customer = customer::get();
			$order = $this->order;

			$mode = getRequest('param2');

			if($mode == 'do') {
				$personId = getRequest('legal-person');
				$isNew = ($personId == null || $personId == 'new');
				if($isNew) {
					$typeId     = $types->getBaseType("emarket", "legal_person");
					$customer   = customer::get();
					$personId  = $collection->addObject("", $typeId);
					$customer->legal_persons = array_merge($customer->legal_persons, array($personId));
				}
				$controller = cmsController::getInstance();
				$data = getRequest('data');

				if($data && $dataModule = $controller->getModule("data")) {
					$key = $isNew ? 'new' : $personId;
					$person = $collection->getObject($personId);
					if ($isNew) {
						$person->setName($data[$key]['name']);
						$dataModule->saveEditedObject($personId, $isNew, true);
					}
				}

				$order->legal_person = $personId;
				$order->order();
				$order->payment_document_num = $order->id;
				$result = $this->printInvoice($order);
				$buffer = outputBuffer::current();
				$buffer->charset('utf-8');
				$buffer->contentType('text/html');
				$buffer->clear();
				$buffer->push($result);
				$buffer->end();
				return true;
			} else if($mode == 'delete') {
				$personId = getRequest('person-id');
				if($collection->isExists($personId)) {
					$customer = customer::get();
					$customer->legal_persons = array_diff($customer->legal_persons, array($personId));
					$collection->delObject($personId);
				}
			}

			$items = array();

			$persons = $customer->legal_persons;
			if(is_array($persons)) foreach($persons as $personId) {
				$person = $collection->getObject($personId);

				$item_arr = array(
					'attribute:id'		=> $personId,
					'attribute:name'	=> $person->name
				);

				$items[] = def_module::parseTemplate($tpl_item, $item_arr, false, $personId);
			}

			$block_arr = array(
				'attribute:type-id'	=> $typeId,
				'xlink:href'		=> 'udata://data/getCreateForm/' . $typeId,
				'subnodes:items'	=> $items
			);

			return def_module::parseTemplate($tpl_block, $block_arr);
		}

		public function poll() {
			$buffer = outputBuffer::current();
			$buffer->clear();
			$buffer->contentType('text/plain');
			$buffer->push('Sorry, but this payment system doesn\'t support server polling.' . getRequest('param0'));
			$buffer->end();
		}

		protected function printInvoice(order $order) {
			$orderId = $order->getId();
			$uri = "uobject://{$orderId}/?transform=sys-tpls/emarket-invoice.xsl";
			return file_get_contents($uri);
		}
	};
?>
