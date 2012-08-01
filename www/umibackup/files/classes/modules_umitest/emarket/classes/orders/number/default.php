<?php
	class defaultOrderNumber implements iOrderNumber {
		protected $order;
		
		public function __construct(order $order) {
			$this->order = $order;
		}
		
		public function number() {
			$sel = new selector('objects');
			$sel->types('object-type')->name('emarket', 'order');
			$sel->order('number')->desc();
			$sel->limit(0, 1);
			$number = $sel->first ? ($sel->first->number + 1) : 1;
			
			$order = $this->order;
			$order->name = getLabel('order-name-prefix', 'emarket', $number);
			$order->number = $number;
			$order->commit();
			
			return $number;
		}
	};
?>