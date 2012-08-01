<?php
	interface iRegedit {
		public function getKey($keyPath, $rightOffset = 0);

		public function getVal($keyPath);
		public function setVar($keyPath, $value);
		public function setVal($keyPath, $value);

		public function delVar($keyPath);

		public function getList($keyPath);
	};
?>