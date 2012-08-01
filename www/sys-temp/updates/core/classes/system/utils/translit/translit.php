<?php
/**
	* Работа с транслитом
*/
	class translit implements iTranslit {
		public static	$fromUpper = Array("Э", "Ч", "Ш", "Ё", "Ё", "Ж", "Ю", "Ю", "Я", "Я", "А", "Б", "В", "Г", "Д", "Е", "З", "И", "Й", "К", "Л", "М", "Н", "О", "П", "Р", "С", "Т", "У", "Ф", "Х", "Ц", "Щ", "Ъ", "Ы", "Ь");
		public static	$fromLower = Array("э", "ч", "ш", "ё", "ё", "ж", "ю", "ю", "я", "я", "а", "б", "в", "г", "д", "е", "з", "и", "й", "к", "л", "м", "н", "о", "п", "р", "с", "т", "у", "ф", "х", "ц", "щ", "ъ", "ы", "ь");
		public static	$toLower   = Array("e", "ch", "sh", "yo", "jo", "zh", "yu", "ju", "ya", "ja", "a", "b", "v", "g", "d", "e", "z", "i", "j", "k", "l", "m", "n", "o", "p", "r", "s",  "t", "u", "f", "h", "c", "w", "~", "y", "\'");

		/**
			* Конвертировать строку в транслит
			* @param String $str входная строка
			* @param String $separator заменитель невалидных символов
			* @return String транслитерированная строка
		*/
		public static function convert($str, $separator = '_') {

			if (!$separator) $separator = '_';

			$str = umiObjectProperty::filterInputString($str);

			$str = str_replace(self::$fromLower, self::$toLower, $str);
			$str = str_replace(self::$fromUpper, self::$toLower, $str);
			$str = strtolower($str);

			$str = preg_replace("/([^A-z^0-9^_^\-]+)/", $separator, $str);
			$str = preg_replace("/[\/\\\',\t`\^\[\]]*/", "", $str);
			$str = str_replace(chr(8470), "", $str);
			$str = preg_replace("/[ \.]+/", $separator, $str);

			$str = preg_replace("/([" . $separator . "]+)/", $separator, $str);

			$str = trim(trim($str), $separator);

			return $str;
		}
	}
?>