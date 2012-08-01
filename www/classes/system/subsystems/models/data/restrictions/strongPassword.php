<?php
	class strongPasswordRestriction extends baseRestriction {
		public function validate($value, $objectId = false) {
			return true;
		}
	};
?>