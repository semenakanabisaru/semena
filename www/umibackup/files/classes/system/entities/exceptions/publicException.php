<?php
	class publicException extends baseException {};

	class publicAdminException extends publicException {};

	class expectElementException extends publicAdminException {};
	class expectObjectException extends publicAdminException {};
	class expectObjectTypeException extends publicAdminException {};
	
	class requireAdminPermissionsException extends publicAdminException {};
	class requreMoreAdminPermissionsException extends publicAdminException {};
	class requireAdminParamException extends publicAdminException {};
	class wrongElementTypeAdminException extends publicAdminException {};
	class publicAdminPageLimitException extends publicAdminException {};
	class publicAdminLicenseLimitException extends publicAdminException {};
	
	class maxIterationsExeededException extends publicException {};
	
	class umiRemoteFileGetterException extends publicException {};
	
	class xsltOnlyException extends publicException {
		public function __construct ($message = "", $code = 0, $strcode = "") {
			parent::__construct(getLabel('error-only-xslt-method'));
		}
	};
	
	class tplOnlyException extends publicException {
		public function __construct ($message = "", $code = 0, $strcode = "") {
			parent::__construct(getLabel('error-only-tpl-method'));
		}
	};
?>