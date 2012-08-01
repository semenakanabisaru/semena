<?php
	class language_morph implements iLanguageMorph {	//TODO Write interface
		private $lang;

		public function __construct() {}

		public static function get_word_base($word) {
			$conv = umiConversion::getInstance();
			return $conv->stemmerRu($word);
		}

		public static function get_word_morph($word, $type = 'noun', $count = 0) {}
	};
?>