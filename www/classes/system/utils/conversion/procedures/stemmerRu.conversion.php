<?php
	require SYS_KERNEL_PATH . "utils/conversion/dkStemmer.php";

	class stemmerRu implements IGenericConversion {
		public function convert($args) {
			if(isset($args[0])) {
				$word = $args[0];
				$word = iconv("UTF-8", "CP1251", $word);
				
				$stem = new Lingua_Stem_Ru;
				$result = $stem->stem_word($word);
				return iconv("CP1251", "UTF-8", $result);
			} else {
				return $args;
			}
		}
	};
?>