<?php
	if(isset($_REQUEST['p'])) {
		$p = $_REQUEST['p'];
		if($p < 0 && $p != 'all') $p = 0;
		if($p != 'all') $p = (int) $p;
		$_REQUEST['p'] = $p;
		unset($p);
	}

	define('MB_ENCODING_SUPPORTED', function_exists('mb_internal_encoding'));
	if(MB_ENCODING_SUPPORTED) {
		define('MB_INTERNAL_ENCODING', mb_internal_encoding());
	} else {
		define('MB_INTERNAL_ENCODING', false);
	}

	function wa_strtolower($str) {
		if (MB_ENCODING_SUPPORTED) {
			mb_internal_encoding("UTF-8");
			return mb_strtolower($str);
	}
		else {
			return strtolower($str);
		}
	}

	function wa_substr($str, $pos, $offset) {
		if (MB_ENCODING_SUPPORTED) {
			mb_internal_encoding("UTF-8");
			return mb_substr($str, $pos, $offset);
	}
		else {
			return substr($str, $pos, $offset);
		}
	}

	function wa_strlen($str) {
		if (MB_ENCODING_SUPPORTED) {
			mb_internal_encoding("UTF-8");
			return mb_strlen($str);
		}
		elseif(is_string($str)&& !preg_match('/[^\x00-\x7F]/S', $str)) {
			return strlen($str);
		}
		return strlen(utf8_decode($str));
	}

	function wa_strpos($str, $seek) {
		if (MB_ENCODING_SUPPORTED) {
			mb_internal_encoding("UTF-8");
			return mb_strpos($str, $seek);
	}
		else {
			return strpos($str, $seek);
		}
	}


	function getArrayKey($array, $key) {
		if(!is_array($array)) {
			return false;
		}

		if($key === false) return NULL;

		if(array_key_exists($key, $array)) {
			return $array[$key];
		} else {
			return NULL;
		}
	}

	function getRequest($key) {
		if ($key == 'p') {
			$answer = prepareRequest($key);
			if ($answer !== false) return $answer;
		}
		return getArrayKey($_REQUEST, $key);
	}

	function getSession($key) {
		if(!isset($_SESSION)) return NULL;
		return getArrayKey($_SESSION, $key);
	}

	function getServer($key) {
		if($key == 'REMOTE_ADDR' && getArrayKey($_SERVER, 'HTTP_X_REAL_IP') !== null) return getArrayKey($_SERVER, 'HTTP_X_REAL_IP');
		return getArrayKey($_SERVER, $key);
	}

	function getCookie($key) {
		return getArrayKey($_COOKIE, $key);
	}

	function getLabel($key, $path = false) {
		$args = func_get_args();
		return ulangStream::getLabel($key, $path, $args);
	}

	function getI18n($key, $pattern = "") {
		return ulangStream::getI18n($key, $pattern);
	}

	function l_mysql_query($sql, $no_cache = false, $className = 'core') {
		static $pool, $i = 0;
		if(is_null($pool)) {
			$pool = ConnectionPool::getInstance();
		}

		$conn = $pool->getConnection($className);
		$result = $conn->query($sql, $no_cache);

		return $result;
	}

	function l_mysql_real_escape_string($inputString, $className = 'core') {
		static $pool = null;
		if(is_null($pool)) {
			$pool = ConnectionPool::getInstance();
		}

		$conn = $pool->getConnection($className);
		if($conn->isOpen()) {
			$info = $conn->getConnectionInfo();
			$link = $info['link'];
			$result = mysql_real_escape_string($inputString, $link);
		} else {
			$result = addslashes($inputString);
		}

		return $result;
	}

	function l_mysql_insert_id($className = 'core') {

		static $pool = null;
		if(is_null($pool)) {
			$pool = ConnectionPool::getInstance();
		}

		$connection = $pool->getConnection($className);
		$info = $connection->getConnectionInfo();
		$link = $info['link'];
		return mysql_insert_id($link);

	}

	function l_mysql_error($className = 'core') {

		static $pool = null;
		if(is_null($pool)) {
			$pool = ConnectionPool::getInstance();
		}

		$connection = $pool->getConnection($className);
		$info = $connection->getConnectionInfo();
		$link = $info['link'];
		return mysql_error($link);

	}

	function l_mysql_affected_rows($className = 'core') {

		static $pool = null;
		if(is_null($pool)) {
			$pool = ConnectionPool::getInstance();
		}

		$connection = $pool->getConnection($className);
		$info = $connection->getConnectionInfo();
		$link = $info['link'];
		return mysql_affected_rows($link);

	}

	function bytes_strlen($string) {
		if(MB_ENCODING_SUPPORTED) {
			mb_internal_encoding("latin1");
			$iResult = strlen($string);
			mb_internal_encoding(MB_INTERNAL_ENCODING);
		} else {
			return strlen($string);
		}
	}

	function bytes_substr($string, $start, $length = false) {
		if(MB_ENCODING_SUPPORTED) {
			mb_internal_encoding("latin1");
			$sResult = '';
			if($length !== false) {
				$sResult = substr($string, $start, $length);
			} else {
				$sResult = substr($string, $start);
			}
			mb_internal_encoding(MB_INTERNAL_ENCODING);
			return $sResult;
		} else {
			if($length !== false)
				return substr($string, $start, $length);
			else
				return substr($string, $start);
		}
	}

	/* Deprecated */
	function natsort2d(&$originalArray, $seekKey = 0) {
		if(is_array($originalArray) == false) {
			return;
		}

		$temp = $resultArray = Array();
		foreach ($originalArray as $key => $value) {
			$temp[$key] = $value[$seekKey];
		}
		natsort($temp);
		foreach ($temp as $key => $value) {
			$resultArray[] = $originalArray[$key];
		}
		$originalArray = $resultArray;
	}

	function removeDirectory($dir) {
		if(!$dh = @opendir($dir)) {
			return false;
		}
		while (($obj = readdir($dh)) !== false) {
			if($obj=='.' || $obj=='..') continue;
				if (!@unlink($dir.'/'.$obj)) {
					removeDirectory($dir.'/'.$obj);
				}
		}
		@rmdir($dir);
		return true;
	}

	function getInterfaceLangs() {
		global $interface_langs;
		return $interface_langs;
	}

	function checkInterfaceLang($prefix) {
		
		return $prefix; // bugfix: 0014317
		
		$config = mainConfiguration::getInstance();
		$langs = $config->get('system', 'interface-langs');

		return in_array($prefix, $langs) ? $prefix : "ru";
	}


	function check_session() {
		$ip = getServer('REMOTE_ADDR');
		if(is_null(getSession('session-owner-ip'))) {
			$_SESSION['session-owner-ip'] = $ip;
			return true;
		}

		if(!session_id()) {
			session_start();
		}

		if(getSession('session-owner-ip') == $ip) {
			return true;
		} else {
			session_destroy();
			session_start();
			return false;
		}
	}

	function enableOutputCompression() {
		if(extension_loaded('zlib') && !defined('DEBUG')) {
			$buffer = ob_get_contents();
			while(@ob_end_clean());
			ob_start('ob_gzhandler');
			ob_start('criticalErrorsBufferHandler');
			echo $buffer;
		}
	}

	function disableOutputCompression() {
		static $called = false;

		if($called) {
			return false;
		}

		while(@ob_end_clean());
		ob_start();

		header("Content-Encoding:");
		header("Content-Length:");
		header("Vary:");


		$called = true;
		return true;
	}

	function array_extract_values($array, &$result = NULL, $ignoreVoidValues = false) {
		if(is_array($array) == false) {
			return Array();
		}

		if(is_array($result) == false) {
			$result = Array();
		}

		foreach($array as $value) {
			if(is_array($value) == false) {
				if($value || $ignoreVoidValues == true) {
					$result[] = $value;
				}
			} else {
				array_extract_values($value, $result, $ignoreVoidValues);
			}
		}
		return $result;
	}

	function array_unique_arrays($array, $key) {
		$result = Array();
		$keys = Array();

		foreach($array as $arr) {
			$currKey = isset($arr[$key]) ? $arr[$key] : NULL;
			if(in_array($currKey, $keys)) {
				continue;
			} else {
				$keys[] = $currKey;
				$result[] = $arr;
			}
		}
		return $result;
	}

	function array_distinct($array) {
		$result = $hashes = array();

		foreach($array as $subArray) {
			$key = sha1(serialize($subArray));

			if(in_array($key, $hashes)) {
				continue;
			}
			$result[] = $subArray;
			$hashes[] = $key;
		}
		return $result;
	}

	function array_positive_values($arr, $recursion = true) {
		if(is_array($arr) == false) {
			return Array();
		}

		$result = Array();
		foreach($arr as $key => $value) {
			if($value) {
				if(is_array($value)) {
					if($recursion) {
						$value = array_positive_values($value, $recursion);
						if(sizeof($value) == 0) {
							continue;
						}
					}
				}
				$result[$key] = $value;
			}
		}
		return $result;
	}

	function set_timebreak($time_end = false) {
		global $time_start;

		if($time_end == false) {
			$time_end = microtime(true);
		}
		$time = $time_end - $time_start;
		return "\r\n<!-- This page generated in {$time} secs -->\r\n";
	}

	// Thanks, Anton Timoshenkov
	function makeThumbnailFullUnsharpMask($img, $amount, $radius, $threshold) {

		if (function_exists('UnsharpMask')){return UnsharpMask($img, $amount, $radius, $threshold);}
			else{

			// Attempt to calibrate the parameters to Photoshop:
			if ($amount > 500) $amount = 500;
			$amount = $amount * 0.016;
			if ($radius > 50) $radius = 50;
			$radius = $radius * 2;
			if ($threshold > 255) $threshold = 255;

			$radius = abs(round($radius)); 	// Only integers make sense.
			if ($radius == 0) {	return $img; imagedestroy($img); break;	}
			$w = imagesx($img); $h = imagesy($img);
			$imgCanvas = $img;
			$imgCanvas2 = $img;
			$imgBlur = imagecreatetruecolor($w, $h);

			// Gaussian blur matrix:
			//	1	2	1
			//	2	4	2
			//	1	2	1

			// Move copies of the image around one pixel at the time and merge them with weight
			// according to the matrix. The same matrix is simply repeated for higher radii.
			for ($i = 0; $i < $radius; $i++)
				{
				imagecopy	  ($imgBlur, $imgCanvas, 0, 0, 1, 1, $w - 1, $h - 1); // up left
				imagecopymerge ($imgBlur, $imgCanvas, 1, 1, 0, 0, $w, $h, 50); // down right
				imagecopymerge ($imgBlur, $imgCanvas, 0, 1, 1, 0, $w - 1, $h, 33.33333); // down left
				imagecopymerge ($imgBlur, $imgCanvas, 1, 0, 0, 1, $w, $h - 1, 25); // up right
				imagecopymerge ($imgBlur, $imgCanvas, 0, 0, 1, 0, $w - 1, $h, 33.33333); // left
				imagecopymerge ($imgBlur, $imgCanvas, 1, 0, 0, 0, $w, $h, 25); // right
				imagecopymerge ($imgBlur, $imgCanvas, 0, 0, 0, 1, $w, $h - 1, 20 ); // up
				imagecopymerge ($imgBlur, $imgCanvas, 0, 1, 0, 0, $w, $h, 16.666667); // down
				imagecopymerge ($imgBlur, $imgCanvas, 0, 0, 0, 0, $w, $h, 50); // center
				}
			$imgCanvas = $imgBlur;

			// Calculate the difference between the blurred pixels and the original
			// and set the pixels
			for ($x = 0; $x < $w; $x++)
				{ // each row
				for ($y = 0; $y < $h; $y++)
					{ // each pixel
					$rgbOrig = ImageColorAt($imgCanvas2, $x, $y);
					$rOrig = (($rgbOrig >> 16) & 0xFF);
					$gOrig = (($rgbOrig >> 8) & 0xFF);
					$bOrig = ($rgbOrig & 0xFF);
					$rgbBlur = ImageColorAt($imgCanvas, $x, $y);
					$rBlur = (($rgbBlur >> 16) & 0xFF);
					$gBlur = (($rgbBlur >> 8) & 0xFF);
					$bBlur = ($rgbBlur & 0xFF);

					// When the masked pixels differ less from the original
					// than the threshold specifies, they are set to their original value.
					$rNew = (abs($rOrig - $rBlur) >= $threshold) ? max(0, min(255, ($amount * ($rOrig - $rBlur)) + $rOrig)) : $rOrig;
					$gNew = (abs($gOrig - $gBlur) >= $threshold) ? max(0, min(255, ($amount * ($gOrig - $gBlur)) + $gOrig)) : $gOrig;
					$bNew = (abs($bOrig - $bBlur) >= $threshold) ? max(0, min(255, ($amount * ($bOrig - $bBlur)) + $bOrig)) : $bOrig;

					if (($rOrig != $rNew) || ($gOrig != $gNew) || ($bOrig != $bNew))
						{
						$pixCol = ImageColorAllocate($img, $rNew, $gNew, $bNew);
						ImageSetPixel($img, $x, $y, $pixCol);
						}
					}
				}
			return $img;
		}

	}

	/** Определение константы для pathinfo */
	if (!defined('PATHINFO_FILENAME')) {
		define('PATHINFO_FILENAME', 8);
	}
	/** Дополнение функции pathinfo,
	 * т.к. константа PATHINFO_FILENAME определена с 5.2
	 * @param (string) $filename - полный путь
	 * @param (string|int) default=null $req_param ('dirname'|'basename'|'extension'|'filename')
	 * @return assoc array or requested value
	 */
	function getPathInfo($filename, $req_param=NULL) {
		$info = pathinfo($filename);
		if (!isset($info['filename'])) {
			/** php_ver <= 5.1.6 */
			$info['filename'] = substr($info['basename'], 0, strpos($info['basename'],'.'));
		}
		if (is_null($req_param)) {
			return $info;
		}

		switch($req_param) {
			case 'dirname':
			case '1':
				return $info['dirname'];
				break;
			case 'basename':
			case '2':
				return $info['basename'];
				break;
			case 'extension':
			case '4':
				return $info['extension'];
				break;
			case 'filename':
			case '8':
				return $info['filename'];
				break;
			default:
				return $info;
		}
	}


	function makeThumbnailFull($path, $thumbs_path, $width, $height, $crop = true, $cropside = 5, $isLogo = false, $quality = 80) {

		$isSharpen=true;

		$image = new umiImageFile($path);
		$file_name = $image->getFileName();
		$file_ext = strtolower($image->getExt());
		$file_ext = ($file_ext=='bmp'?'jpg':$file_ext);

		$allowedExts = Array('gif', 'jpeg', 'jpg', 'png', 'bmp');
		if(!in_array($file_ext, $allowedExts)) return "";

		$file_name = substr($file_name, 0, (strlen($file_name) - (strlen($file_ext) + 1)) );

		$thumbPath = sha1($image->getDirName());

		if (!is_dir($thumbs_path . $thumbPath)) {
			mkdir($thumbs_path . $thumbPath, 0755, true);
		}

		$file_name_new = $file_name . '_' . $width . '_' . $height . '_' . $cropside . '_' . $quality . "." . $file_ext;
		$path_new = $thumbs_path . $thumbPath . '/' . $file_name_new;

		if(!file_exists($path_new) || filemtime($path_new) < filemtime($path)) {
			if(file_exists($path_new)) {
				unlink($path_new);
			}

			$width_src = $image->getWidth();
			$height_src = $image->getHeight();

			if($height == "auto") {
				$real_height = (int) round($height_src * ($width / $width_src));
				//change
				$height=$real_height;
				$real_width = (int) $width;
			} else {
				if($width == "auto") {
						$real_width = (int) round($width_src * ($height / $height_src));
						//change
						$width=$real_width;
				} else {
					$real_width = (int) $width;
				}

				$real_height = (int) $height;
			}

			$offset_h=0;
			$offset_w=0;

			// realloc: devision by zero fix
			if (!intval($width) || !intval($height)) {
				$crop = false;
			}

			if ($crop){
				$width_ratio = $width_src/$width;
				$height_ratio = $height_src/$height;

				if ($width_ratio > $height_ratio){
					$offset_w = round(($width_src-$width*$height_ratio)/2);
					$width_src = round($width*$height_ratio);
				} elseif ($width_ratio < $height_ratio){
					$offset_h = round(($height_src-$height*$width_ratio)/2);
					$height_src = round($height*$width_ratio);
					}


				if($cropside) {
					//defore all it was cropside work like as - 5
					//123
					//456
					//789
					switch ($cropside):
						case 1:
							$offset_w = 0;
							$offset_h = 0;
							break;
						case 2:
							$offset_h = 0;
							break;
						case 3:
							$offset_w += $offset_w;
							$offset_h = 0;
							break;
						case 4:
							$offset_w = 0;
							break;
						case 5:
							break;
						case 6:
							$offset_w += $offset_w;
							break;
						case 7:
							$offset_w = 0;
							$offset_h += $offset_h;
							break;
						case 8:
							$offset_h += $offset_h;
							break;
						case 9:
							$offset_w += $offset_w;
							$offset_h += $offset_h;
							break;
					endswitch;
				}
			}

			$thumb = imagecreatetruecolor($real_width, $real_height);

			$source_array = $image->createImage($path);
			$source = $source_array['im'];

			if ($width*4 < $width_src && $height*4 < $height_src) {
				$_TMP=array();
				$_TMP['width'] = round($width*4);
				$_TMP['height'] = round($height*4);

				$_TMP['image'] = imagecreatetruecolor($_TMP['width'], $_TMP['height']);

				if ($file_ext == 'gif') {
					$_TMP['image_white'] = imagecolorallocate($_TMP['image'], 255, 255, 255);
					imagefill($_TMP['image'], 0, 0, $_TMP['image_white']);
					imagecolortransparent($_TMP['image'], $_TMP['image_white']);
					imagealphablending($source, TRUE);
					imagealphablending($_TMP['image'], TRUE);
				} else {
				    imagealphablending($_TMP['image'], false);
				    imagesavealpha($_TMP['image'], true);
				}
				imagecopyresampled($_TMP['image'], $source, 0, 0, $offset_w, $offset_h, $_TMP['width'], $_TMP['height'], $width_src, $height_src);

				imageDestroy($source);

				$source = $_TMP['image'];
				$width_src = $_TMP['width'];
				$height_src = $_TMP['height'];

				$offset_w = 0;
				$offset_h = 0;
				unset($_TMP);
			}

			if ($file_ext == 'gif') {
				$thumb_white_color = imagecolorallocate($thumb, 255, 255, 255);
				imagefill($thumb, 0, 0, $thumb_white_color);
				imagecolortransparent($thumb, $thumb_white_color);
				imagealphablending($source, TRUE);
				imagealphablending($thumb, TRUE);
			} else {
				imagealphablending($thumb, false);
				imagesavealpha($thumb, true);
			}

			imagecopyresampled($thumb, $source, 0, 0, $offset_w, $offset_h, $width, $height, $width_src, $height_src);
			if($isSharpen) $thumb = makeThumbnailFullUnsharpMask($thumb,80,.5,3);

			switch($file_ext) {
					case 'gif':
						$res = imagegif($thumb, $path_new);
						break;
				case 'png':
						$res = imagepng($thumb, $path_new);
					break;
				default:
						$res = imagejpeg($thumb, $path_new, $quality);
			}
				if(!$res) {
					throw new coreException(getLabel('label-errors-16008'));
				}

			imageDestroy($source);
			imageDestroy($thumb);

			if($isLogo) {
				umiImageFile::addWatermark($path_new);
			}
		}

		$value = new umiImageFile($path_new);

		$arr = Array();
		$arr['size'] = $value->getSize();
		$arr['filename'] = $value->getFileName();
		$arr['filepath'] = $value->getFilePath();
		$arr['src'] = $value->getFilePath(true);
		$arr['ext'] = $value->getExt();

		$arr['width'] = $value->getWidth();
		$arr['height'] = $value->getHeight();

		if(cmsController::getInstance()->getCurrentMode() == "admin") {
			$arr['src'] = str_replace("&", "&amp;", $arr['src']);
		}

		return $arr;
	}

	function dateToString($timestamp) {
		$monthsList = array('января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря');
		$date = date('j.m.Y', $timestamp);
		list($day, $month, $year) = explode('.', $date);
		return $day . " " . $monthsList[(int)$month - 1] . " " . $year;
	}

	function sumToString($i_number, $i_gender = 1, $s_w1 = 'рубль', $s_w2to4 = 'рубля', $s_w5to10 = 'рублей') {
		if (!$i_number) {
			return rtrim("ноль " . $s_w5to10);
		}

		$s_answer = "";
		$v_number = $i_number;

		if (strpos($i_number, '.') !== 0) {
			$i_number = number_format($i_number, 2, '.', '');
			list($v_number, $copecks) = explode('.', $i_number);
			$arr_tmp = SummaStringThree($s_answer, $copecks , 2, "копейка", "копейки", "копеек");
			$s_answer = $arr_tmp['Summa'];
		}

		$arr_tmp = SummaStringThree($s_answer, $v_number, $i_gender, $s_w1, $s_w2to4, $s_w5to10);
		$v_number = $arr_tmp['TempValue'];
		$s_answer = $arr_tmp['Summa'];
		if (!$v_number) return $s_answer;

		$arr_tmp = SummaStringThree($s_answer, $v_number, 2, "тысяча", "тысячи", "тысяч");
		$v_number = $arr_tmp['TempValue'];
		$s_answer = $arr_tmp['Summa'];
		if (!$v_number) return $s_answer;

		$arr_tmp = SummaStringThree($s_answer, $v_number, 1, "миллион", "миллиона", "миллионов");
		$v_number = $arr_tmp['TempValue'];
		$s_answer = $arr_tmp['Summa'];
		if (!$v_number) return $s_answer;

		$arr_tmp = SummaStringThree($s_answer, $v_number, 1, "миллиард", "миллиарда", "миллиардов");
		$v_number = $arr_tmp['TempValue'];
		$s_answer = $arr_tmp['Summa'];
		if (!$v_number) return $s_answer;

		$arr_tmp = SummaStringThree($s_answer, $v_number, 1, "триллион", "триллиона", "триллионов");
		$v_number = $arr_tmp['TempValue'];
		$s_answer = $arr_tmp['Summa'];
		return $s_answer;
	}

	function SummaStringThree($Summa, $TempValue, $Rod, $w1, $w2to4, $w5to10) {
		$Rest = 0; $Rest1 = 0;
		$EndWord = "";
		$s1 = ""; $s10 = ""; $s100 = "";

		$Rest = strlen($TempValue) < 4 ? $TempValue : substr($TempValue, -3);
		$TempValue = floor($TempValue / 1000);
		if ($Rest === 0) {
			if ($Summa === "") $Summa = $w5to10 . " ";
			return array('TempValue' => $TempValue, 'Summa' => $Summa);
		}

		$EndWord = $w5to10;

		$i_i = floor($Rest / 100);
		switch ($i_i) {
			case 0: $s100 = ""; break;
			case 1: $s100 = "сто "; break;
			case 2: $s100 = "двести "; break;
			case 3: $s100 = "триста "; break;
			case 4: $s100 = "четыреста "; break;
			case 5: $s100 = "пятьсот "; break;
			case 6: $s100 = "шестьсот "; break;
			case 7: $s100 = "семьсот "; break;
			case 8: $s100 = "восемьсот "; break;
			case 9: $s100 = "девятьсот "; break;
		}
		$Rest = $Rest % 100;
		$Rest1 = intval(floor($Rest / 10));
		$s1 = "";
		switch ($Rest1) {
			case 0:
				$s10 = ""; break;
			case 1:
				switch ($Rest) {
					case 10: $s10 = "десять "; break;
					case 11: $s10 = "одиннадцать "; break;
					case 12: $s10 = "двенадцать "; break;
					case 13: $s10 = "тринадцать "; break;
					case 14: $s10 = "четырнадцать "; break;
					case 15: $s10 = "пятнадцать "; break;
					case 16: $s10 = "шестнадцать "; break;
					case 17: $s10 = "семнадцать "; break;
					case 18: $s10 = "восемнадцать "; break;
					case 19: $s10 = "девятнадцать "; break;
				}
				break;
			case 2: $s10 = "двадцать "; break;
			case 3: $s10 = "тридцать "; break;
			case 4: $s10 = "сорок "; break;
			case 5: $s10 = "пятьдесят "; break;
			case 6: $s10 = "шестьдесят "; break;
			case 7: $s10 = "семьдесят "; break;
			case 8: $s10 = "восемьдесят "; break;
			case 9: $s10 = "девяносто "; break;
		}

		if ($Rest1 !== 1) {
			$i_j = $Rest % 10;
			switch($i_j) {
				case 1:
					switch($Rod) {
						case 1: $s1 = "один "; break;
						case 2: $s1 = "одна "; break;
						case 3: $s1 = "одно "; break;
					}
					$EndWord = $w1;
					break;
				case 2:
					if ($Rod === 2) {$s1 = "две ";} else {$s1 = "два ";}
					$EndWord = $w2to4;
					break;
				case 3:
					$s1 = "три ";
					$EndWord = $w2to4;
					break;
				case 4:
					$s1 = "четыре ";
					$EndWord = $w2to4;
					break;
				case 5: $s1 = "пять "; break;
				case 6: $s1 = "шесть "; break;
				case 7: $s1 = "семь "; break;
				case 8: $s1 = "восемь "; break;
				case 9:$s1 = "девять "; break;
			}
		}

		$Summa = rtrim(rtrim($s100 . $s10 . $s1 . $EndWord) . " " . $Summa);

		return array('TempValue' => $TempValue, 'Summa' => $Summa);
	}


	function prepareRequest($key) {

		$cmsController = cmsController::getInstance();
		if ($cmsController->getCurrentMode() != 'admin') return false;
		$domains = getRequest('domain_id');
		$langs = getRequest('lang_id');
		$rels = getRequest('rel');
		if (!is_array($domains) || !is_array($langs)) return false;
		$module = $cmsController->getCurrentModule();
		$method = $cmsController->getCurrentMethod();
		if(!$module || !$method) return false;

		if (!isset($_SESSION['paging'])) $_SESSION['paging'] = array();
		$domainId = $domains[0];
		if (!isset($_SESSION['paging'][$domainId])) $_SESSION['paging'][$domainId] = array();
		$langId = $langs[0];
		if (!isset($_SESSION['paging'][$domainId][$langId])) $_SESSION['paging'][$domainId][$langId] = array();
		if (!isset($_SESSION['paging'][$domainId][$langId][$module])) $_SESSION['paging'][$domainId][$langId][$module] = array();
		if (!isset($_SESSION['paging'][$domainId][$langId][$module][$method])) $_SESSION['paging'][$domainId][$langId][$module][$method] = array();

		if (is_array($rels)) $relId = $rels[0];
		else $relId = 0;

		if (!isset($_SESSION['paging'][$domainId][$langId][$module][$method][$relId])) $_SESSION['paging'][$domainId][$langId][$module][$method][$relId] = NULL;

		$currentPage = getArrayKey($_REQUEST, $key);
		if (!is_null($currentPage)) {
			$_SESSION['paging'][$domainId][$langId][$module][$method][$relId] = $currentPage;
		}

		return $_SESSION['paging'][$domainId][$langId][$module][$method][$relId];
	}

	function umi_var_dump($value, $return = false){

		$remoteIp = getServer('HTTP_X_REAL_IP');

		if (!$remoteIp) {
			$remoteIp = getServer('REMOTE_ADDR');
		}
		$config = mainConfiguration::getInstance();
		$allowedIps = $config->get('debug', 'allowed-ip');

		$allowedIps = is_array($allowedIps) ? $allowedIps : array();

		if (in_array($remoteIp, $allowedIps)) {
			var_dump($value);
		} elseif ($return) {
			var_dump($value);
		}
	}

	function elfinder_get_hash($path) {
		$path = str_replace('\\', '/', realpath("./" . trim($path, "./\\")));

        $permissions = permissionsCollection::getInstance();
		$userId = $permissions->getUserId();
		$user = umiObjectsCollection::getInstance()->getObject($userId);

		$source = "";

		if ($filemanagerDirectory = $user->getValue('filemanager_directory')) {
			$i = 1;
			$directories = explode(",", $filemanagerDirectory);
			foreach ($directories as $directory) {
				$directory = trim($directory);
				if (!strlen($directory)) continue;
				$directory = trim($directory, "/");
				$directoryPath = realpath(CURRENT_WORKING_DIR . "/" . $directory);
				if (strpos($directoryPath, CURRENT_WORKING_DIR) === false || !is_dir($directoryPath)) continue;
				if (strpos($path, $directory) !== false) {
					$source = "files" . $i;
					$path = trim(str_replace(CURRENT_WORKING_DIR . "/" . $directory, "", $path), "/");
					break;
				}
				$i++;
			}
		} else {
			$images_path = str_replace('\\', '/', realpath(CURRENT_WORKING_DIR . "/images"));
	        $files_path = str_replace('\\', '/', realpath(CURRENT_WORKING_DIR . "/files"));
	        if (strpos($path, $images_path) === 0) {
        		$path = trim(str_replace($images_path, "", $path), "/");
        		$source = "images";
			} elseif (strpos($path, $images_path) === 0) {
				$path = trim(str_replace($images_path, "", $path), "/");
        		$source = "files";
			}
		}

		$path = str_replace("/", DIRECTORY_SEPARATOR, $path);
		$hash = strtr(base64_encode($path), '+/=', '-_.');
		$hash = rtrim($hash, '.');


		return strlen($hash) ? "umi" . $source . "_" . $hash : "";

	}




?>
