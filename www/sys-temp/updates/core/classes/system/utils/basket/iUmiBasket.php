<?php

	interface iUmiBasket {
		public function addToBasket($iElementId, $iCount = 1);
		public function removeFromBasket($iElementId);
		public function recalcBasket();
		public function getBasketItemCount($iElementId);
		public function changeBasketItem($iElementId, $arrItemInfo);

		public function order();
		public function checkIsEmpty();

		public function renderBasket($sTemplate = "default");
		public function render4JSON($iRequestId);
		public function render4Mail($sTemplate = "default");

		public function renderUserOrders($sTemplate = "default");
		
		public function setItemPropertyValue($element_id, $field_name, $value);

		public static function getStatusBySId($sId);
		
	}

?>