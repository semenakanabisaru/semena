<?php
	interface iUmiDate {
		public function __construct($timeStamp = false);

		public function getFormattedDate($formatString = false);
		public function getCurrentTimeStamp();
		public function getDateTimeStamp();

		public function setDateByTimeStamp($timeStamp);
		public function setDateByString($dateString);

		public static function getTimeStamp($dateString);
	}
?>