<?php
	interface iLanguageMorph {
		public function __construct();
		public static function get_word_base($word);
		public static function get_word_morph($word, $type = 'noun', $count = 0);
	};
?>