<?php
	/**
	* @desc Класс-обертка для внутреннего представления типа данных "Дата"
	*/
	class umiDate implements iUmiDate {
		public $timestamp;
		public static $defaultFormatString = DEFAULT_DATE_FORMAT;
        /**
        * @desc Публичный конструктор
        * @param Int $timestamp Количество секунд с начала эпохи Unix (TimeStamp)
        */
		public function __construct($timestamp = false) {
			if($timestamp === false) {
				$timestamp = self::getCurrentTimeStamp();
			}
			$this->setDateByTimeStamp($timestamp);
		}

		/**
		* @desc Возвращяет текущий Time Stamp
		* @return Int Time Stamp
		*/
		public function getCurrentTimeStamp() {
			if (isset($_SERVER['REQUEST_TIME'])) {
				return $_SERVER['REQUEST_TIME'];
			}
			else {
			return time();
		}
		}
        /**
        * @desc Возвращает Time Stamp для сохраненной даты
        * @return Int Time Stamp
        */
		public function getDateTimeStamp() {
			return intval($this->timestamp);
		}
        /**
        * @desc Возвращает сохраненную дату в отформатированом виде
        * @param String $formtString Форматная строка (см. описание функции date на php.net)
        * @return String отформатированная дата 
        */
		public function getFormattedDate($formatString = false) {
			if($formatString === false) {
				$formatString = self::$defaultFormatString;
			}
			return date($formatString, $this->timestamp);
		}
        /**
        * @desc Устанавливает дату по Time Stamp
        * @param Int $timestamp Time Stamp желаемой даты
        * @return Boolean false - если $timestamp не число, true - в противном случае
        */
		public function setDateByTimeStamp($timestamp) {
			if(!is_numeric($timestamp)) {
				return false;
			}
			$this->timestamp = $timestamp;
			return true;
		}
		/**
		* @desc Устанавливает дату по переданой строке
		* @param String $dateString Строка с датой
		* @return Boolean true - если переданная строка может быть интерпретирована, как дата, false - в противном случае
		*/
		public function setDateByString($dateString) {
			$dateString = umiObjectProperty::filterInputString($dateString);
			$timestamp  = strlen($dateString) ? self::getTimeStamp($dateString) : 0;
			return $this->setDateByTimeStamp($timestamp);
		}
		/**
		* @desc Преобразует строку с датой в Time Stamp
		* @param String $dateString Строка с датой
		* @return Int Time Stamp
		*/
		public static function getTimeStamp($dateString) {
			return toTimeStamp($dateString);
		}
		
		public function __toString() {
			return $this->getFormattedDate();
		}
	}
?>