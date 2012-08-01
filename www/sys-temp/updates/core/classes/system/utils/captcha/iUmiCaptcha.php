<?php
	interface iUmiCaptcha {
		public static function generateCaptcha($template="default", $input_id="sys_captcha", $captcha_hash="");
		public static function isNeedCaptha();
		public static function checkCaptcha();
		
		public static function getDrawer();
	}
	
	abstract class captchaDrawer {
		const length = 6;
		const alphabet = '23456789qwertyuipasdfghjkzxcvbnm';
	
		abstract public function draw($randomCode);
		
		public function getRandomCode() {
			$length = 6; $code = ''; $alphas = self::alphabet; $c = strlen($alphas) - 1;
			for($i = 0; $i < $length; $i++) {
				$code .= $alphas{rand(0, $c)};
			}
			return $code;
		}
	};
?>