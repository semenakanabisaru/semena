<?php
/**
	* Класс для работы с файлами изображений, наследуется от класса umiFile
*/
	class umiImageFile extends umiFile implements iUmiImageFile {
		private static $aSupportedTypes = null;
		private static $useWatermark = false;
		private static $CurrentBit = 0;

		/**
			* Конструктор, принимает в качестве аргумента путь до файла в локальной файловой системе.
			* @param String $filepath путь до файла в локальной файловой системе
		*/
		public function __construct($filepath) {
			parent::__construct($filepath);
			if (!$this->is_broken) {
				$this->is_broken = ! self::getIsImage($this->name);
			}

		}

		/**
			* Получить список поддерживаемых расширений файлов
			* @return Array массив, стостоящий из допустимых расширений файлов изображений
		*/
		public static function getSupportedImageTypes() {
			if (is_null(self::$aSupportedTypes)) {
				self::$aSupportedTypes = array();
				self::$aSupportedTypes[] = "GIF";
				self::$aSupportedTypes[] = "JPG";
				self::$aSupportedTypes[] = "JPEG";
				self::$aSupportedTypes[] = "PNG";
				self::$aSupportedTypes[] = "WBMP";
				self::$aSupportedTypes[] = "BMP";
				self::$aSupportedTypes[] = "SWF";
			}

			return self::$aSupportedTypes;
		}
		
		/**
			* Указывает на необходимость добавления водного знака к следующей загружаемой картинке
		*/
		public static function setWatermarkOn () {
			self::$useWatermark = true;
		}
		/**
			* Отключает водный знак
		*/
		public static function setWatermarkOff () {
			self::$useWatermark = false;
		}

		/**
			* Загрузить файл из запроса и сохранить локально. Информация о файле берется из массива $_FILES[$group_name]["size"][$var_name]
			* @param String $group_name
			* @param String $var_name
			* @param String $target_folder локальная папка, в которую необходимо сохранить файл
			* @return Boolean true в случае успеха
		*/
		public static function upload($group_name, $var_name, $target_folder, $id = false) {
			self::$class_name = __CLASS__;		
			$filepath = parent::upload($group_name, $var_name, $target_folder, $id);
			
			$regedit = regedit::getInstance();
			$max_img_filesize = (int) $regedit->getVal("//settings/max_img_filesize");
			$upload_max_filesize = (int) ini_get("upload_max_filesize");
			$max_img_filesize = ($max_img_filesize < $upload_max_filesize) ? $max_img_filesize : $upload_max_filesize;

			$filesize = (int) filesize("." . $filepath);
			$max_img_filesize = (int) $max_img_filesize*1024*1024;
			
			if($max_img_filesize > 0) {
				if($max_img_filesize < $filesize) {
					unlink("." . $filepath);
					return false;
				}
			}
			
			$info = @getimagesize(".".$filepath);
			if(!is_array($info)) {
				@unlink("." . $filepath);
				return false;				
			}
			
			//Пропуск через GD, чтобы избавиться от EXIF
			$jpgThroughGD = (bool) mainConfiguration::getInstance()->get("kernel", "jpg-through-gd");
			if ($jpgThroughGD) {

				list(,, $extension) = array_values(getPathInfo("." . $filepath));
				$extension = strtolower($extension);
				if ($extension == 'jpg' || $extension == 'jpeg'){
					$res = imagecreatefromjpeg("." . $filepath);
					if ($res) {
						imagejpeg($res, "." . $filepath, 100);
						imagedestroy($res);
					} else {
						return false;
					}
				}
			}

			// Если нужно добавляем водяной знак и отключаем его для следующих изображений
			if (self::$useWatermark) {
				self::addWatermark ("./".$filepath);
			}
			self::setWatermarkOff ();
			
			return $filepath;
		}
		
		/**
			* Проверить, является ли файл допустимым изображением
			* @param String $sFilePath путь до файла, который необходимо проверить
			* @return Boolean true, если файл является изображением
		*/
		public static function getIsImage($sFilePath) {
			$arrFParts = explode(".", $sFilePath);
			$sFileExt = strtoupper(array_pop($arrFParts));
			return in_array($sFileExt, self::getSupportedImageTypes());
		}

		public function getWidth() {			
			list($width, $height) = getimagesize($this->filepath);
			return $width;
		}

		public function getHeight() {
			list($width, $height) = getimagesize($this->filepath);
			return $height;
		}
		
		/**
			* Добавляет водный знак на изображение
			* @param string $filePath путь до изображения
			* @return boolean
		*/
		public static function addWatermark ($filePath) {
			if (!empty($_REQUEST['disable_watermark'])) return false;
			
			$regedit = regedit::getInstance ();
		
			$srcWaterImage = $regedit->getVal ("//settings/watermark/image");
			$alphaWaterImage = $regedit->getVal ("//settings/watermark/alpha");
			$valignWaterImage = $regedit->getVal ("//settings/watermark/valign");
			$halignWaterImage = $regedit->getVal ("//settings/watermark/halign");
			
			if (!file_exists ($srcWaterImage)) {
				return false;
			}
			if (!$alphaWaterImage) { 
				$alphaWaterImage = 100;
			}
			if (!$valignWaterImage) { 
				$valignWaterImage = "bottom";
			}
			if (!$halignWaterImage) {
				$halignWaterImage = "right";
			}

			$waterImgParam = self::createImage ($srcWaterImage);
			$srcImgParam = self::createImage ($filePath);
			$imageFileInfo = getPathInfo ($filePath);
			
			if (!$waterImgParam || !$srcImgParam) {
				return false;
			}
			
			$x_ins = 0;
			$y_ins = 0;
			
			switch ($halignWaterImage){
				case "center" : {
					$x_ins = floor (($srcImgParam["width"] - $waterImgParam["width"]) / 2);
					break;
				}
				case "right" : {
					$x_ins = $srcImgParam["width"] - $waterImgParam["width"];
				}
			}
			switch ($valignWaterImage) {
				case "center" : {
					$y_ins = floor (($srcImgParam["height"] - $waterImgParam["height"]) / 2);
					break;
				}
				case "bottom" : {
					$y_ins = $srcImgParam["height"] - $waterImgParam["height"];
				}
			}

			$tmp = $waterImgParam['im'];
			
			$cut = imagecreatetruecolor($waterImgParam["width"], $waterImgParam["height"]);
			imagecopy($cut, $srcImgParam['im'], 0, 0, $x_ins , $y_ins, $waterImgParam["width"], $waterImgParam["height"]);
			imagecopy($cut, $tmp, 0, 0, 0, 0, $waterImgParam["width"], $waterImgParam["height"]);
			
			imagecopymerge($srcImgParam['im'], $cut, $x_ins , $y_ins, 0, 0, $waterImgParam["width"], $waterImgParam["height"], $alphaWaterImage);

			switch ($imageFileInfo['extension']) {
				case "jpeg" :
				case "jpg"  :
				case "JPEG" :
				case "JPG"  : {
					imagejpeg ($srcImgParam['im'], $filePath, 90);
					break;
				}
				case "png" :
				case "PNG" : {
					imagepng ($srcImgParam['im'], $filePath);
				}
				case "gif" :
				case "GIF" : {
					imagegif ($srcImgParam['im'], $filePath);
					break;
				}
				case "bmp" :
				case "BMP" :
					imagewbmp($srcImgParam['im'], $filePath);
					break;
			}
			
			imagedestroy ($srcImgParam["im"]);
			imagedestroy ($waterImgParam["im"]);
			
			return;

		}
		
		/**
			* Создает и возвращает индентификатор изображения
			* @param string $imageFilePath путь до изображения
			* @return array массив: индентификатор (im), ширина (width), высота (height)
		*/
		public static function createImage ($imageFilePath) {
			$imageFilePath = str_replace(CURRENT_WORKING_DIR, '', $imageFilePath);

			$image_identifier = 0;
			$pathinfo = parse_url ($imageFilePath);

			$imageFilePath = (substr ($pathinfo["path"], 0, 1) == "/")
								? substr ($pathinfo["path"], 1)
								: $pathinfo["path"];
								
			list ($width, $height, $type, $attr) = getimagesize ($imageFilePath);

			$types = array (
				"GIF" => "1",
				"JPG" => "2",
				"PNG" => "3",
				"BMP" => "6",
				"WBMP"=> "15",
				"XBM" => "16"
			);
			
			switch($type){
				case $types["GIF"] : {
					$image_identifier = imagecreatefromgif ($imageFilePath);
					break;
				}				
				case $types["JPG"] : {
					$image_identifier = imagecreatefromjpeg ($imageFilePath);
					break;
				}
				case $types["PNG"] : {
					$image_identifier = imagecreatefrompng ($imageFilePath);
					break;
				}
				case $types["BMP"] : {
					$image_identifier = self::imagecreatefrombmp($imageFilePath);
					break;
				}
				case $types["WBMP"] : {
					$image_identifier = imagecreatefromwbmp ($imageFilePath);
					break;
				}
				case $types["XBM"]: {
					$image_identifier = imagecreatefromxbm ($imageFilePath);
				}	
			}
			
			if (!$image_identifier) {
				return false;
			}
				
			return array (
				"im"     => $image_identifier,
				"width"  => $width,
				"height" => $height
			);
		}
		
		
		/** Создает изображение из bmp, т.к. встроенная поддержка в php отсутствует
			* Нарыто на просторах интернета
			* @param string $file путь до изображения
			* @return resource type image идентификатор изображения
		*/
		private static function imagecreatefrombmp($file) {
			$f=fopen($file,"r");
			$Header=fread($f,2);

			if($Header=="BM") {
				$Size=self::freaddword($f);
				$Reserved1=self::freadword($f);
				$Reserved2=self::freadword($f);
				$FirstByteOfImage=self::freaddword($f);

				$SizeBITMAPINFOHEADER=self::freaddword($f);
				$Width=self::freaddword($f);
				$Height=self::freaddword($f);
				$biPlanes=self::freadword($f);
				$biBitCount=self::freadword($f);
				$RLECompression=self::freaddword($f);
				$WidthxHeight=self::freaddword($f);
				$biXPelsPerMeter=self::freaddword($f);
				$biYPelsPerMeter=self::freaddword($f);
				$NumberOfPalettesUsed=self::freaddword($f);
				$NumberOfImportantColors=self::freaddword($f);
	
				if($biBitCount<24) {
					$img=imagecreate($Width,$Height);
					$Colors=pow(2,$biBitCount);
					for($p=0;$p<$Colors;$p++) {
						$B=self::freadbyte($f);
						$G=self::freadbyte($f);
						$R=self::freadbyte($f);
						$Reserved=self::freadbyte($f);
						$Palette[]=imagecolorallocate($img,$R,$G,$B);
					}
         
					if($RLECompression==0) {
						$Zbytek=(4-ceil(($Width/(8/$biBitCount)))%4)%4;
	
						for($y=$Height-1;$y>=0;$y--) {
							$CurrentBit=0;
							for($x=0;$x<$Width;$x++) {
								$C=self::freadbits($f,$biBitCount);
								imagesetpixel($img,$x,$y,$Palette[$C]);
							}
							if($CurrentBit!=0) {
								self::freadbyte($f);
							}
							for($g=0;$g<$Zbytek;$g++) {
								self::freadbyte($f);
							}
						}
					}
				}

		      if($RLECompression==1) {
					$y=$Height;
					$pocetb=0;
				
					while(true) {
						$y--;
						$prefix=self::freadbyte($f);
						$suffix=self::freadbyte($f);
						$pocetb+=2;
						$echoit=false;

						if ($echoit) {
							//echo "Prefix: $prefix Suffix: $suffix<BR>";
							if ( ($prefix==0) && ($suffix==1) ) break;
							if ( feof($f) ) break;

							while(!(($prefix==0)and($suffix==0))) {
								if($prefix==0) {
									$pocet=$suffix;
									$Data.=fread($f,$pocet);
									$pocetb+=$pocet;
									if($pocetb%2==1) {
										self::freadbyte($f); $pocetb++;
									}                     
								}
                  
								if($prefix>0) {
									$pocet=$prefix;
									for($r=0;$r<$pocet;$r++) {
										$Data.=chr($suffix);                     
									}
								}
								
							}
		            
							$prefix=self::freadbyte($f);
							$suffix=self::freadbyte($f);
							$pocetb+=2;
							//if($echoit) echo "Prefix: $prefix Suffix: $suffix<BR>";
						}

						for($x=0;$x<strlen($Data);$x++) {
							imagesetpixel($img,$x,$y,$Palette[ord($Data[$x])]);
						}
		         
						$Data="";
	
					}
				}


				if($RLECompression==2) {
					$y=$Height;
					$pocetb=0;
		      
					while(true) {                
						$y--;
						$prefix=self::freadbyte($f);
						$suffix=self::freadbyte($f);
						$pocetb+=2;
						$echoit=false;

						//if ($echoit) echo "Prefix: $prefix Suffix: $suffix<BR>";
						if ( ($prefix==0) and ($suffix==1) ) break;
						if ( feof($f) ) break;
	
						while(!(($prefix==0)and($suffix==0))) {
							if($prefix==0) {
								$pocet=$suffix;
								$CurrentBit=0;
								for($h=0;$h<$pocet;$h++) {
									$Data.=chr(self::freadbits($f,4));
								}
		                     
								if ($CurrentBit!=0) self::freadbits($f,4);
		               
								$pocetb+=ceil(($pocet/2));
		               
								if($pocetb%2==1) {
								   self::freadbyte($f);
								   $pocetb++;
								}
		                 
							}
		            
							if($prefix>0) {
								$pocet=$prefix;
								$i=0;
		               
								for($r=0;$r<$pocet;$r++) {
								   if($i%2==0) {
								      $Data.=chr($suffix%16);
								   }
								   else {
								      $Data.=chr(floor($suffix/16));
								   }
		                  $i++;
								}
							}
               
							$prefix=self::freadbyte($f);
							$suffix=self::freadbyte($f);
							$pocetb+=2;
							//if ($echoit) echo "Prefix: $prefix Suffix: $suffix<BR>";
						}
	
						for($x=0;$x<strlen($Data);$x++) {
						   imagesetpixel($img,$x,$y,$Palette[ord($Data[$x])]);
						}
		             
						$Data="";
					}
				}

				if($biBitCount==24) {
					$img=imagecreatetruecolor($Width,$Height);
					$Zbytek=$Width%4;
	
					for($y=$Height-1;$y>=0;$y--) {
					   for($x=0;$x<$Width;$x++) {
					      $B=self::freadbyte($f);
						   $G=self::freadbyte($f);
					      $R=self::freadbyte($f);
					      $color=imagecolorexact($img,$R,$G,$B);
					      
					      if($color==-1) $color=imagecolorallocate($img,$R,$G,$B);
					      
					      imagesetpixel($img,$x,$y,$color);
					   }
			      
					   for($z=0;$z<$Zbytek;$z++) {
					      self::freadbyte($f);
					   }
					}
				}
			
				fclose($f);   
				return $img;
			}
			fclose($f);		
		}
		
		private static function freaddword($f) {
			$b1=self::freadword($f);
		   $b2=self::freadword($f);
		   return $b2*65536+$b1;
		}

		private static function freadword($f) {
		   $b1=self::freadbyte($f);
		   $b2=self::freadbyte($f);
		   return $b2*256+$b1;
		}

		private static function freadbyte($f) {
		    return ord(fread($f,1));
		}		
		
		private static function freadbits($f,$count) {
			$Byte=freadbyte($f);
			$LastCBit = self::$CurrentBit;
			
			self::$CurrentBit += $count;
			if (self::$CurrentBit==8) {
				self::$CurrentBit=0;
			}
			else {
				fseek($f,ftell($f)-1);
			}
			return RetBits($Byte,$LastCBit,$count);
		}

		private static function RetBits($byte,$start,$len) {
			$bin=decbin8($byte);
			$r=bindec(substr($bin,$start,$len));
			return $r;
		}
		
	}
	
	
?>
