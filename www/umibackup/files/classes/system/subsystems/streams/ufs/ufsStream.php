<?php
	class ufsStream extends umiBaseStream {
		protected	$scheme = "ufs",
				$depth = 0,
				$modeAll = false,

				$ignoreNames = Array(	'.', '..', '.svn',
							'.htaccess'
							);

		public function stream_open($path, $mode, $options, $opened_path) {
			$path = $this->parsePath($path);

			if($path !== false) {
				if(file_exists($path)) {
					switch(filetype($path)) {
						case "dir": {
							$data = $this->readDirectory($path, $this->depth);
							break;
						}

						case "file": {
							$this->modeAll = true;
							$data = $this->readFile($path);
							break;
						}

						default: {
							return true;
						}
					}
				} else {
					$data = false;
				}


				if($data !== false) {
				    $data = $this->translateToXml($data);
					$this->setData($data);
					return true;
				}
			}
			return $this->setDataError('not-found');
		}


		protected function parsePath($path) {
			$path = parent::parsePath($path);

			if(array_key_exists("all", $this->params)) {
				$this->modeAll = true;
			}

			if(array_key_exists("depth", $this->params)) {
				$this->depth = $this->params['depth'];
			}
			
			$path = str_replace("..", "", $path);
			if(substr($path, 0, strlen(CURRENT_WORKING_DIR)) == CURRENT_WORKING_DIR) {
				$path = substr($path, strlen(CURRENT_WORKING_DIR) + 1);
			}
			return realpath("./" . trim($path));
		}


		protected function translateToXml() {
			$args = func_get_args();
			return parent::translateToXml($args[0]);
		}


		protected function readDirectory($path, $depth = 0) {
			$result = Array();

			$files = Array();
			$dirs = Array();

			$dirResource = opendir($path);

			$objs = Array();
			while(($obj = readdir($dirResource)) !== false) {
				$objs[] = $obj;
			}

			natsort($objs);

			foreach($objs as $obj) {
				if(in_array($obj, $this->ignoreNames)) continue;

				$objPath = $path . "/" . $obj;

				switch(filetype($objPath)) {
					case "dir": {
						$dir = $this->translateDirectory($objPath);

						if($depth) {
							$sub = $this->readDirectory($objPath, $depth - 1);
							$dir = array_merge($dir, $sub);
						}
						$dirs[] = $dir;
						break;
					}

					case "file": {
						$files[] = $this->translateFile($objPath);
						break;
					}

					default: {
						continue;
					}
				}
			}

			$result['attribute:path'] = $path;
			$result['nodes:directory'] = $dirs;
			$result['nodes:file'] = $files;

			return $result;
		}


		protected function readFile($path) {
			$result = Array();
			$result['file'] = $this->translateFile($path);
			return $result;
		}


		protected function translateDirectory($path) {
			$result = Array();
			$result['attribute:name'] = self::convertCharset(basename($path));

			return $result;
		}


		protected function translateFile($path) {
			$result = Array();

			$path_info = getPathInfo($path);

			$result['attribute:name'] = self::convertCharset($path_info['basename']);
			if (isset($path_info['extension'])) {
			$result['attribute:ext'] = $ext = $path_info['extension'];
			}
			$result['attribute:size'] = filesize($path);

			if(function_exists("mime_content_type")) {
				$result['attribute:mimeType'] = mime_content_type($path);
			}

			$fileStat = stat($path);
			$result['attribute:create-time'] = $fileStat['ctime'];
			$result['attribute:modify-time'] = $fileStat['mtime'];

			if($this->modeAll) {
				switch($ext) {
					case "xml":
					case "xsl":
					case "xsd":
					case "html": {
						$result['xml:source'] = file_get_contents($path);
						break;
					}

					case "txt": {
						$result['source'] = file_get_contents($path);
						break;
					}


					case "gif":
					case "jpg":
					case "jpeg":
					case "png": {
						list($width, $height) = $size = getimagesize($path);
						$result['attribute:mimeType'] = $size['mime'];
						$result['imageWidth'] = $width;
						$result['imageHeight'] = $height;
						break;
					}
				}
			}

			return $result;
		}

		private static function convertCharset($text) {
		/*
		определяет, в какой кодировке фраза на входе
		и возвращает ее в utf-8
		*/
		$textConverted = rawurldecode($text);
		if ($textConverted) $text = $textConverted;
		//
		
		$sCharset = self::detectCharset($text);
		if (function_exists('iconv') && $sCharset !== 'UTF-8') {
			$textConverted = @iconv($sCharset, 'UTF-8', $text);
			if ($textConverted) $text = $textConverted;
		}
		//
		return $text;
	}

		private static function winToLowercase($sStr) {
			for($i=0;$i<strlen($sStr);$i++) {
				$c = ord($sStr[$i]);
				if ($c >= 0xC0 && $c <= 0xDF) { // А-Я
					  $sStr[$i] = chr($c+32);
				} elseif ($sStr[$i] >= 0x41 && $sStr[$i] <= 0x5A) { // A-Z
					  $sStr[$i] = chr($c+32);
				}
			  }
			 return $sStr;
		}

		private static function detectCharset($sStr) {
			if (preg_match("/[\x{0000}-\x{FFFF}]+/u", $sStr)) return 'UTF-8';
			$sAnswer = 'CP1251';
			if (!function_exists('iconv')) return $sAnswer;
			//
			$arrCyrEncodings = array(
				'CP1251',
				'KOI8-R',
				'UTF-8',
				'ISO-8859-5',
				'CP866'
			);
			
			if(function_exists("mb_detect_encoding")) {
				return mb_detect_encoding($sStr, implode(", ",$arrCyrEncodings));
			} else {
				return "UTF-8";
			}
		}
	};
?>
