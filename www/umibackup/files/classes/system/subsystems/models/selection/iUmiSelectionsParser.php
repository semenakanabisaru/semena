<?php
	interface iUmiSelectionsParser {
		public static function runSelection(umiSelection $selectionObject);
		public static function runSelectionCounts(umiSelection $selectionObject);
		public static function parseSelection(umiSelection $selectionObject);
	}
?>