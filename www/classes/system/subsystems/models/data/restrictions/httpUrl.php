<?php
	class httpUrlRestriction extends baseRestriction implements iNormalizeInRestriction {
		public function validate($value, $objectId = false) {
			return !strlen($value) || preg_match("/^(https?:\/\/)?([A-z\.]+)/", $value);
		}

		public function normalizeIn($value, $objectId = false) {
			if(strlen($value) && preg_match("/^https?:\/\//", $value) == false) {
				$value = "http://" . $value;
			}

			return $value;
		}
	};
?>