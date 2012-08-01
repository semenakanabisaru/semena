<?php
	abstract class __emarket_social {
		
		public function callback() {
			
			//$this->_emulate_cost_delivery();//
			
			$network = '';
			if(getRequest('merchant_id')) {
				$network = 'vkontakte';
			}
			
			
			
			$handler = socialCallbackHandler::get($network);
			
			if($handler) {
				$handler->response();
			}	
			
			else {
				$buffer = outputBuffer::current();
				$buffer->contentType('text/xml');
				$buffer->charset('utf-8');
				$buffer->clear();
				//social TODO
				$buffer->push("<?xml version=\"1.0\" encoding=\"utf-8\"?>\n".
						"<failure>
							<error-code>100</error-code>
							<error-description>Неизвестный тип уведомлений</error-description>
							<critical>true</critical>
						</failure>");
				$buffer->end();
			}
		}
		
		
		public function _emulate_order() {
			$_POST = array(
				'new_state'=>'chargeable',
				'merchant_id' => 		22724,
				'item_id_1'			=> 42,
				'item_price_1'		=> 325.00,
				"item_quantity_1"	=> 1, "item_currency_1"=> 643,
				'notification_type'=>'calculate-shipping-cost-test',
				'shipping_country'=>'RU', 'order_id'=>94782,
				
				'shipping_code'=>'3',
				'shipping_flat'=>'2',
				'shipping_house'=>'3',
				'shipping_street'=>'3',
				'shipping_country'=>'RU',
				'shipping_city'=>'city',
				'user_name'=>'user_name',
    'order_comment' => 5,
    'order_date' => '2011-03-15T19:10:21+03:00',
    'order_id' => 947821,
    'recipient_name' => 4,
    'recipient_phone' => 12345678900,
    'shipping_country_str' => 'Россия',
    'shipping_email' => 'id1853202@vk.com',
    'shipping_method' => 27234,
   'shipping_phone' => 12345678900,
    'shipping_street' => '40 лет Октября пр.',
    'user_id' => 1853202,

	);
		
			$_REQUEST = $_POST;			
			
		}

		public  function _emulate_cost_delivery() {
			$_POST = array(
				'new_state'=>'chargeable',
				'merchant_id' => 		22724,
				'item_id_1'			=> 42,
				'item_price_1'		=> 325.00,
				"item_quantity_1"	=> 1, "item_currency_1"=> 643,
				'notification_type'=>'calculate-shipping-cost-test',
				'shipping_country'=>'RU', 'order_id'=>94782,
				
				'shipping_code'=>'3',
				'shipping_flat'=>'2',
				'shipping_house'=>'3',
				'shipping_street'=>'3',
				'shipping_country'=>'RU',
				'shipping_city'=>'city',
				'user_name'=>'user_name',
				'order_comment' => 5,
				'order_date' => '2011-03-15T19:10:21+03:00',
				'order_id' => 947821,
				'recipient_name' => 4,
				'recipient_phone' => 12345678900,
				'shipping_country_str' => 'Россия',
				'shipping_email' => 'id1853202@vk.com',
				'shipping_method' => 27234,
			   'shipping_phone' => 12345678900,
				'shipping_street' => '40 лет Октября пр.',
				'user_id' => 1853202,

				);
		
			$_REQUEST = $_POST;			
			
		}

	};
?>
