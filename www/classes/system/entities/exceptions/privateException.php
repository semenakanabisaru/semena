<?php
	class privateException extends baseException {};

	class wrongParamException extends privateException {};
	
	class errorPanicException extends Exception {};
	
	class breakException extends Exception {};
	
	
	abstract class fieldRestrictionException extends privateException {};
	
	class wrongValueException extends fieldRestrictionException {};
	
	class valueRequiredException extends fieldRestrictionException {};
?>