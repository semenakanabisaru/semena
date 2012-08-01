<?php
	class emailRestriction extends baseRestriction {
		protected $errorMessage = 'restriction-error-email';
		
		public function validate($value) {
			return (bool) ($value ? preg_match("/.+\@.+\..+/", $value) : true);
		}
	};
?>