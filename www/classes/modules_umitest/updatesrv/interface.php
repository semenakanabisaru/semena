<?php
	interface iUpdatesrv {
		public static function addLicense($licenseLevel, $domainName, $ipAddr = false);
		public static function generateReport($licenseId);
	}
?>