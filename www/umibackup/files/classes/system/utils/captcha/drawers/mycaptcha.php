<?php

	class mycaptchaCaptchaDrawer extends captchaDrawer {

		public function getRandomCode() {
			$length = 4; $code = ''; $alphas = '123456789'; $c = strlen($alphas) - 1;
			for($i = 0; $i < $length; $i++) {
				$code .= $alphas{rand(0, $c)};
			}
			return $code;
		}

		public function draw($randomCode) {
			header('Content-type: image/jpeg');
			header('Etag: ' . sha1($randomCode));
			$this->render($randomCode);
			exit;
		}

		private function render($str) {
			$length = 4;
			$charWidth = 14;
			$imagewidth = $imagelength = $length * $charWidth + 24;
			$imageheight = 24;

			$image = imagecreate($imagelength, $imageheight);
			$bgcolor = imagecolorallocate($image, 255, 255, 255);
			$stringcolor = imagecolorallocate($image, 0, 0, 0);
			$linecolor   = imagecolorallocate($image, 0, 0, 0);

			$chars = str_split($str);
			$sz = sizeof($chars);

			$left = 15;
			for($i = 0; $i < $sz; $i++) {
				$rx = (rand() % 6) - 3;
				$ry = (rand() % 6) - 3;
				imagestring($image, 25, ($left + $rx), (5 + $ry), $chars[$i], $stringcolor);
				$left += $charWidth;
			}

			$linecolor = imagecolorallocate($image, 204, 204, 204);

			$c = 10 + (rand() % 10);
			for($i = 0; $i < $c; $i++) {
				$y1 = 0;
				$y2 = $imageheight;
				$x1 = rand() % ($imagewidth + $imagewidth * 0.2);
				$x2 = $x1 - $imageheight;
				imageline($image, $x1, $y1, $x2, $y2, $linecolor);
			}
			imagejpeg($image, '', 65);
		}

	};

?>