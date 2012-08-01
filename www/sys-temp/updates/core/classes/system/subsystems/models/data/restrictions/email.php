<?php
	class emailRestriction extends baseRestriction {
		protected $errorMessage = 'restriction-error-email';

		public function validate($value, $objectId = false) {
			return (bool) ($value ? preg_match("/.+\@.+\..+/", $value) : true);
		}
	};
?>