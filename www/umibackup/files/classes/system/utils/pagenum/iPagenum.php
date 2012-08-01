<?php
	interface iPagenum {
		public static function generateNumPage($total, $per_page, $template = "default", $varName = "p");
		public static function generateOrderBy($fieldName, $type_id, $template = "default");
	}
?>