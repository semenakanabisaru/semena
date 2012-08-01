<?php
	if(!defined("CURRENT_VERSION_LINE")) define("CURRENT_VERSION_LINE", "");
	if(!defined("CURRENT_WORKING_DIR")) define("CURRENT_WORKING_DIR", "/tmp/non-existing-directory");
	
	$interface_langs = Array('ru');


	if(isset($_REQUEST['p'])) {
		$p = (int) $_REQUEST['p'];
		if($p < 0) $p = 0;
		$_REQUEST['p'] = $p;
		unset($p);
	}

	function wa_strtolower($str) {
		$strtolower_func = function_exists("mb_strtolower") ? "mb_strtolower" : "strtolower";
		return $strtolower_func($str);
	}
	
	
	function wa_substr($str, $pos, $offset) {
		$substr_func = function_exists("mb_strtolower") ? "mb_substr" : "substr";
		return $substr_func($str, $pos, $offset);
	}
	
	function wa_strlen($str) {
		$strlen_func = function_exists("mb_strlen") ? "mb_strlen" : "strlen";
		return $strlen_func($str);
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
		return getArrayKey($_REQUEST, $key);
	}
	
	function getSession($key) {
		if(!isset($_SESSION)) return NULL;
		return getArrayKey($_SESSION, $key);
	}
	
	function getServer($key) {
		return getArrayKey($_SERVER, $key);
	}
	
	function getCookie($key) {
		return getArrayKey($_COOKIE, $key);
	}

	function getLabel($key, $path = false) {
		return ulangStream::getLabel($key, $path);
	}

	function getI18n($key, $pattern = "") {
		return ulangStream::getI18n($key, $pattern);
	}
	
	function getMaxValue($arr) {
		$res = false;
		$sz = sizeof($arr);

		for($i = 0; $i < $sz; $i++) {
			$tmp = $arr[$i];
			if($tmp > $res) {
				$res = $tmp;
			}
		}
		
		return $res;
	}
	
	function l_mysql_query($sql, $no_cache = false) {
		static $cache = Array(), $i = 0;
		
		if(substr($sql, 0, 1) == "\t") {
			$sql = trim($sql, " \t\n");
		}
		
		if(strtoupper(substr($sql, 0, 6)) != "SELECT" || defined('MYSQL_DISABLE_CACHE')) return mysql_query($sql);
		
		$hash = md5($sql);
		
		if(isset($cache[$hash]) && $no_cache == false) {
			$result = $cache[$hash][0];
			if($cache[$hash][1]) {
				mysql_data_seek($result, 0);
			}
		} else {
			//echo ++$i, "\t{$sql}\n";
			
			$result = mysql_query($sql);
			if(mysql_error()) {
				$cache[$hash] = false;
			} else {
				$cache[$hash] = Array($result, mysql_num_rows($result));
			}
		}
		
		return $result;
	}
	
	function bytes_strlen($string) {
		if(function_exists('mb_internal_encoding')) {
			$oldEnc  = mb_internal_encoding(); 
			mb_internal_encoding("latin1");
			$iResult = strlen($string);
			mb_internal_encoding($oldEnc);
		} else {
			return strlen($string);
		}		
	}
	
	function bytes_substr($string, $start, $length = false) {
		if(function_exists('mb_internal_encoding')) {
			$oldEnc = mb_internal_encoding(); 
			mb_internal_encoding("latin1");
			$sResult = '';
			if($length !== false) {
				$sResult = substr($string, $start, $length);
			} else {
				$sResult = substr($string, $start);
			}
			mb_internal_encoding($oldEnc);
			return $sResult;
		} else {
			if($length !== false)
				return substr($string, $start, $length);
			else
				return substr($string, $start);				
		}
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
		$langs = getInterfaceLangs();
		if(in_array($prefix, $langs)) {
			return $prefix;
		} else {
			return "ru";
		}
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


	function makeThumbnailFullPlaceLogo($image) {
		$logo_full_file_name="./images/cms/thumbs_logo.png";  // файл логотип
		$type_placment=3; // тип размещения (1 - левый верхни, 2 - правый верхнй, 3 - правый нижний, 4 - левый нижний, 5 - центр)
		$x=5; // координата размещения - Х
		$y=5; // координата размещения - Y

		$image_width=imagesx($image);
		$image_height=imagesy($image);


		if (file_exists($logo_full_file_name)) {

			$logoFile = new umiImageFile($logo_full_file_name);
			if(($image_width < ($logoFile->getWidth()+$x)) || ($image_height < ($logoFile->getHeight()+$y)) ){return $image;}

			$logo=@imagecreatefrompng($logo_full_file_name);
			if ($logo) {
				$logo_width=imagesx($logo);
				$logo_height=imagesy($logo);
				switch ($type_placment) {
					case 2:
						$x=$image_width-$x-$logo_width;
						break;
					case 3:
						$x=$image_width-$x-$logo_width;
						$y=$image_height-$y-$logo_height;
						break;
					case 4:
						$y=$image_height-$y-$logo_height;
						break;
					case 5:
						$x=floor($image_width/2-$logo_width/2);
						$y=floor($image_height/2-$logo_height/2);
						break;
					default:
				}
				imagecopy($image,$logo,$x,$y,0,0,$logo_width,$logo_height);
			}
		}

		return $image;
		}


	function makeThumbnailFull($path, $thumbs_path, $width, $height, $crop = true, $cropside = 5, $isLogo = false) {

		$quality = 80;
		$isSharpen=true;

		$image = new umiImageFile($path);
		$file_name = $image->getFileName();
		$file_ext = $image->getExt();

		$file_ext = strtolower($file_ext);
		$allowedExts = Array('gif', 'jpeg', 'jpg', 'png', 'bmp');
		if(!in_array($file_ext, $allowedExts)) return "";

		$file_modified 	= filemtime($path);
		$file_name_new = md5($path.$width.$height.$crop.$cropside.$isLogo)."." . $file_ext;
		$path_new = $thumbs_path . $file_name_new;


		if(!is_file($path_new)) {
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

				if($crop)
					{
					$width_ratio = $width_src/$width;
					$height_ratio = $height_src/$height;

					if ($width_ratio > $height_ratio)
						{
						$offset_w = round(($width_src-$width*$height_ratio)/2);
						$width_src = round($width*$height_ratio);
						}
					elseif ($width_ratio < $height_ratio)
						{
						$offset_h = round(($height_src-$height*$width_ratio)/2);
						$height_src = round($height*$width_ratio);
						}


				if($cropside)
					{
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

				if ($image->getExt() == "gif")	{ $source = imagecreatefromgif($path);}
				else if ($image->getExt() == "png")	{ $source = imagecreatefrompng($path);}
				else { 	$source = imagecreatefromjpeg($path); }

				if ($width*4 < $width_src AND $height*4 < $height_src) {
					$_TMP=array();
					$_TMP['width'] = round($width*4);
					$_TMP['height'] = round($height*4);

					$_TMP['image'] = imagecreatetruecolor($_TMP['width'], $_TMP['height']);
					imagecopyresized($_TMP['image'], $source, 0, 0, $offset_w, $offset_h, $_TMP['width'], $_TMP['height'], $width_src, $height_src);
					$source = $_TMP['image'];
					$width_src = $_TMP['width'];
					$height_src = $_TMP['height'];

					$offset_w = 0;
					$offset_h = 0;
					unset($_TMP);
				}

				imagecopyresampled($thumb, $source, 0, 0, $offset_w, $offset_h, $width, $height, $width_src, $height_src);

				if($isLogo) {$thumb = makeThumbnailFullPlaceLogo($thumb);}
				if($isSharpen) $thumb = makeThumbnailFullUnsharpMask($thumb,80,.5,3);


				if($image->getExt() == "png") {
					imagepng($thumb, $path_new);
				} else if($image->getExt() == "gif") {
					imagegif($thumb, $path_new);
				} else {
					imagejpeg($thumb, $path_new, $quality);
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
	
	function enableOutputCompression() {
		if(extension_loaded('zlib')) {	// && defined('DEBUG') == false
			ob_start('ob_gzhandler');
		}
	}
	
	function disableOutputCompression() {
		static $called = false;
		
		if($called) {
			return false;
		}
		
		ob_end_clean();
		ob_start();
		
		header("Content-Encoding:");
		header("Content-Length:");
		header("Vary:");
		
		
		$called = true;
		return true;
	}
	
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


	abstract class baseException extends Exception {
		protected $strcode;

		public static $catchedExceptions = Array();
		
		public function __construct ($message, $code = 0, $strcode = "") {
			$message = templater::putLangs($message);
		
			baseException::$catchedExceptions[] = $this;
			$this->strcode = $strcode;
			parent::__construct($message, $code);
		}
		
		
		public function getStrCode() {
			return (string) $this->strcode;
		}
	};


	class coreException extends baseException {};
	
	class coreBreakEventsException extends coreException {};


	class privateException extends baseException {};

	class wrongParamException extends privateException {};
	
	class errorPanicException extends Exception {};


	class publicException extends baseException {};

	class publicAdminException extends publicException {};

	class expectElementException extends publicAdminException {};
	class expectObjectException extends publicAdminException {};
	class expectObjectTypeException extends publicAdminException {};
	
	class requireAdminPermissionsException extends publicAdminException {};
	class requreMoreAdminPermissionsException extends publicAdminException {};
	class requireAdminParamException extends publicAdminException {};
	class wrongElementTypeAdminException extends publicAdminException {};
	class publicAdminPageLimitException extends publicAdminException {};
	class publicAdminLicenseLimitException extends publicAdminException {};
	
	class maxIterationsExeededException extends publicException {};
	
	class umiRemoteFileGetterException extends publicException {};


/**
 * @desc Simle factory for automation of file handling
 * @author Leeb <ignat@umi-cms.ru>
 * @version 1.0
 */
class XMLFactory {   
    /**
    * @desc Path to data store
    */
    const DATA_STORE = "/xmldb/";
    /**
    * @var XMLFactory singletone instance
    */
    private static $oInstance   = NULL;
    /**
    * @var Array of XMLProxy already opened 
    */
    private $aOpenedList = array();
    /**
    * @desc Private constructor
    */
    private function __construct() {
    	return;        
    }
    /**
    * @desc Destructor
    */
    public function __destruct() {
        // Not need enough to save all proxies because any proxy can do it by itself
        /*
        foreach($this->aOpenedList as $oProxy) {
            $oProxy->saveFile();
        }
        */
    }
    /**
    * @desc Return instance of the class 
    * @return XMLFactory
    */
    public static function getInstance($c = NULL) {
        if (!isset(self::$oInstance)) {
            $sClassName       = __CLASS__;
            self::$oInstance  = new $sClassName;
        }
        return self::$oInstance;
    }
    /**
    * @desc Return proxy object for a specified xml-file
    * @param String $_sFileName
    * @param Boolean $_bReopen indicates if we should reopen the proxy
    * @return XMLProxy     
    */
    public function getProxy($_sFileName, $_bReopen=false) {        
        if($_bReopen || !array_key_exists($_sFileName, $this->aOpenedList)) {
            if($_bReopen)
                unset($this->aOpenedList[$_sFileName]);
            $this->aOpenedList[$_sFileName] = new XMLProxy($_SERVER['DOCUMENT_ROOT'] . XMLFactory::DATA_STORE . $_sFileName);            
        }
        return $this->aOpenedList[$_sFileName];
    }
    /**
    * @desc Creates a new xml-file in the store
    * @param String $_sFileName
    * @param String $_sContent
    * @return bool if file was not exists and created successfuly
    */
    public function createFile($_sFileName, $_sContent) {
        $sFullFileName = $_SERVER['DOCUMENT_ROOT'] . XMLFactory::DATA_STORE . $_sFileName;
        if( file_exists($sFullFileName) ) return false;
        return (file_put_contents($sFullFileName, $_sContent) == strlen($_sContent));
    }
    /**
    * @desc Deletes file in a store
    * @param String $_sFileName    
    * @return bool if file had successfuly deleted
    */
    public function removeFile($_sFileName) {
        $sFullFileName = $_SERVER['DOCUMENT_ROOT'] . XMLFactory::DATA_STORE . $_sFileName;
        if( file_exists($sFullFileName) ) return unlink($sFullFileName);
        return false;
    }
    /**
    * @desc Copies file in the store
    * @param String $_sSource Source file
    * @param String $_sDest Destination file
    * @return bool true if successfully copied
    */
    public function copyFile($_sSource, $_sDest) {
        if( !file_exists($_SERVER['DOCUMENT_ROOT'] . XMLFactory::DATA_STORE . $_sSource) ) return false;
        return copy($_SERVER['DOCUMENT_ROOT'] . XMLFactory::DATA_STORE . $_sSource,
                    $_SERVER['DOCUMENT_ROOT'] . XMLFactory::DATA_STORE . $_sDest);        
    }
    /**
    * @desc Lists all xml files in the specified folder of the store
    * @param String $_sFolder
    * @return array
    */
    public function listFiles($_sFolder) {
        return glob($_SERVER['DOCUMENT_ROOT'] . XMLFactory::DATA_STORE . $_sFolder . '*.xml', GLOB_NOSORT);
    }
}


/**
 * Simle proxy class for easing using XML files
 * @author Leeb <ignat@umi-cms.ru>
 * @version 3.0
 */
class XMLProxy {
	/**
	 * @var DOM Object, containes current XML
	 */
	private $oXMLDocument    = NULL;
	/**
	 * @var XPath object, for XPath handling
	 */
	private $oXPath          = NULL;
	/**
	* @var String path to source file
	*/
	private $sSourcePath     = NULL;
	/**
	* @var Array pool to the active elements
	*/
	private $aElementPool    = array();
	/**
	* @var Boolean
	*/
	protected static $bCanLock = true;
	/**
	* @var $filesCache cache xml files properties to avoid double-read after __wakeup
	*/    
	protected static $filesCache = Array();
	/**
	* @var Boolean
	*/
	private $bFileLocked     = false;
	/**
	* @var String
	*/
	private $sLockPath       = NULL;
	/**
	* @var Int
	*/
	private $iModifyTime     = 0;
	/**
	* @var Array
	*/
	private $aCacheStore     = array();
	/**
	* @var Boolean    
	*/
	private $bDisableLocks   = false;
	/**
	 * Constructor
	 * @param String $_sFileName contains path to the XML file
	 */
	public function __construct($_sFileName = NULL) {
		$this->oXMLDocument = new DOMDocument;
		$this->oXPath       = new DOMXPath($this->oXMLDocument);
		$this->loadFile($_sFileName);
		$this->bDisableLocks = defined('XMLDRV_DISABLE_LOCKS') && XMLDRV_DISABLE_LOCKS;
	}
	/**
	* Destructor
	*/
	public function __destruct() {
		self::$bCanLock = false;
		$this->saveFile();        
		$this->aElementPool = array();
	}

	  public function __wakeup() {
			$this->loadFile($this->sSourcePath);
	  }


	/**
	* @desc     
	*/
	public function __toString() {
		return $this->toString();
	}
	/**
	 * Load XML from a file
	 * @param String $_sFileName contains path to the XML file
	 * @return Boolean true if successed
	 */
	public function loadFile($_sFileName) {
		if(isset(self::$filesCache[$_sFileName])) {
			$fileParams = self::$filesCache[$_sFileName];
		} else {
			if(!file_exists($_sFileName)) {
				if(file_exists($_sFileName.'.lock')) {
					if(!copy($_sFileName.'.lock', $_sFileName)) 
						return false;
				} else {
					// Hack for always keep ready content store
					if(strstr($_sFileName, 'objectcontent') !== false) {
						file_put_contents($_sFileName, "<"."?xml version=\"1.0\" encoding=\"utf-8\"?".">\n<umi>\n<values />\n</umi>");
					} else {
						return false;
					}
				}
			}
			
			$fileParams = Array(
				'mtime' => filemtime($_sFileName),
				'sourcePath' => $_sFileName,
				'dom' => DOMDocument::load($_sFileName)
			);
			$fileParams['xpath'] = new DOMXPath($fileParams['dom']);
			self::$filesCache[$_sFileName] = $fileParams;
		}
		
		$this->iModifyTime = $fileParams['mtime'];
		$this->sSourcePath = $fileParams['sourcePath'];
		$this->oXMLDocument = $fileParams['dom'];
		$this->oXPath      = $fileParams['xpath'];
		$this->aCacheStore = array();
		return true;        
	}
	/**
	 * Load XML from a string
	 * @param String $_sXML contains the XML code
	 * @return Boolean true if successed
	 */
	public function loadString($_sXML = NULL) {
		if($_sXML) {
			$this->sSourcePath = NULL;
			@$this->oXMLDocument->loadXML($_sXML);            
			$this->oXPath      = new DOMXPath($this->oXMLDocument);
			$this->aCacheStore = array();
			return true;
		} else {
			return false;
		}
	}    
	/**
	 * Saves XML to file
	 * @param String $_sFileName contains path to the file
	 * @return Boolean true if successed
	 */
	public function saveFile($_sFileName = NULL) {	//TODO: Check here for extra saving for same files
		if($_sFileName) {
			$this->sSourcePath = $_sFileName;
		} else {
			if(!$this->bFileLocked) return false;
		}
		if(!$this->sSourcePath) return false;

		$this->oXMLDocument->save($this->sSourcePath);
		$this->releaseLock();

		return true;        
	}
	/**
	 * Dumps containing XML to string
	 * @return Boolean true if successed
	 */
	public function toString() {
		return $this->oXMLDocument->saveXML();
	}
	/**
	  * Locks current file for safe changing
	  * @return Boolean always true
	  */
	  public function getLock() { 
	  	  if($this->bDisableLocks) return $this->bFileLocked = true;          
		  if($this->bFileLocked)   return true;
		  if(!self::$bCanLock)     return true;
		  $sLockName     = $this->sSourcePath . '.lock';
		  $iLockDeadLine = time() + 10;
		  $bNeedReload   = false;
		  while(file_exists($sLockName) && (time() < $iLockDeadLine)) {
			  $bNeedReload = true;
		  }
		  if(!$bNeedReload && (filemtime($this->sSourcePath) != $this->iModifyTime)) {
			  $bNeedReload = true;
		  }
		  file_put_contents($sLockName, '');
		  chmod($sLockName, 0777);
		  copy($this->sSourcePath, $sLockName);
		  clearstatcache();
		  if(filesize($sLockName) != 0) {          
			$this->sLockPath   = $this->sSourcePath;
			$this->sSourcePath = $sLockName;
			$this->bFileLocked = true;
		  } else {
			unlink($sLockName);
		  }
		  if($bNeedReload) {              
			  $this->aCacheStore = array();
			  $this->loadFile($this->sLockPath);
			  foreach($this->aElementPool as $oElement)
				$oElement->restore($this->oXMLDocument);
		  }
		  return true;
	  }
	  /**
	  * Releases the lock of the current file
	  * @return Boolean always true 
	  */
	  public function releaseLock() { //return true;         
		  if(!$this->bFileLocked) return true;
		  if(substr($this->sSourcePath,strrpos($this->sSourcePath, '.')) != '.lock') return true;
		  
		  if(!is_writable($this->sLockPath)) {
				trigger_error("Can't release lock on \"{$this->sLockPath}\"", E_USER_ERROR);
		  }
		  
		  copy($this->sSourcePath, $this->sLockPath);
		  unlink($this->sSourcePath);
		  $this->sSourcePath = $this->sLockPath;
		  $this->sLockPath   = NULL;
		  $this->bFileLocked = false;
		  return true;
	  }
	  /**
	  * Gets count of elements
	  * @param String $_sXPath
	  * @return Integer
	  */
	  public function getCount($_sXPath) {
		 $oNodeList = $this->oXPath->evaluate($_sXPath);
		 return $oNodeList->length;
	  }
	  /**
	  * Gets child count
	  * @param String $_sXPath
	  * @return Integer
	  */
	  public function getChildCount($_sXPath) {
		 $oChildren = $this->oXPath->evaluate($_sXPath.'/*');
		 return $oChildren->length;
	  }
	  /**
	  * Gets attributes count
	  * @param String $_sXPath
	  * @return Integer
	  */
	  public function getAttributeCount($_sXPath) {
		  $oNode = $this->getNode($_sXPath);
		 if(!$oNode->hasAttributes()) return 0;
		 return $oNode->attributes->length;
	  }
	  /**
	   * Gets XML-tree parsed to array
	   * @param String  $_sXPath tree root element XPath
	   * $param Boolean $_bFlat  only top-level children
	   * @return Array
	   */
	  public function getNodeTree($_sXPath, $_bFlat = false) {
		$aNodes = $this->getNode($_sXPath, true);
		$aTree  = array();
		foreach($aNodes as $oNode) {
			$aTmp                = $this->getTree($oNode, true, $_bFlat);
			$aTmp['@attributes'] = $this->getAttributeList($oNode);
			$aTmp['@value']      = $oNode->nodeValue;            
			$aTree[$oNode->nodeName][] = $aTmp;
		}
		  return $aTree;
	  }
	  /**
	  * Gets value of the element
	  * @param String $_sXPath
	  * @param Boolean $_bMultiple
	  * @return String value of the specified element
	  */
	  public function getElementValue($_sXPath, $_bMultiple=false) {
		  //if($this->getChildCount($_sXPath)) return "";
		  $oNode = $this->getNode($_sXPath, $_bMultiple);
		  if($_bMultiple) {
			  $aValues = array();
			  foreach($oNode as $o)
				if(is_object($o) && 
			       $o->childNodes->length == 1 && 
			       in_array($o->firstChild->nodeType, array(XML_TEXT_NODE, XML_CDATA_SECTION_NODE))) $aValues[] = $o->nodeValue;
			  return $aValues;
		  } else {
			return ( is_object($oNode) && 
			         $oNode->childNodes->length == 1 && 
			         in_array($oNode->firstChild->nodeType, array(XML_TEXT_NODE, XML_CDATA_SECTION_NODE))  ) ? $oNode->nodeValue : "";
		  }
	  }
	  /**
	  * Sets value of the element
	  * @param String $_sXPath
	  * @param String $_sValue new value of the element
	  * @return Boolean
	  */
	  public function setElementValue($_sXPath, $_sValue) {
		  $this->getLock();		  
		  $oNode = $this->getNode($_sXPath);
		  for($i = 0; $i < $oNode->childNodes->length; $i++) {
		  	  if(!in_array($oNode->childNodes->item($i)->nodeType, array(XML_TEXT_NODE, XML_CDATA_SECTION_NODE))) return false;
		  }
		  $oData = $this->oXMLDocument->createCDATASection($_sValue);		  
		  while($oNode->childNodes->length) {
			  $oNode->removeChild( $oNode->firstChild );                
		  }
		  $oNode->appendChild($oData);
		  return true;
	  }
	  /**
	  * Gets attribute value of the specified element
	  * @param String $_sXPath element
	  * @param String $_sName  name of the attribute
	  * @param Boolean $_bMultiple
	  * @return String value of the attribute
	  */
	  public function getAttributeValue($_sXPath, $_sName, $_bMultiple=false) {
		  $oNode     = $this->getNode($_sXPath, $_bMultiple);
		  if($_bMultiple) {
			$Attributes = array();
			if(!empty($oNode))
			foreach($oNode as $o) {
				$oAttrNode = $o->attributes->getNamedItem($_sName);
				if(is_object($oAttrNode)) $Attributes[] = $oAttrNode->nodeValue;
			}
			return $Attributes;
		  } else {
			if(!is_object($oNode)) return false;        
			$oAttrNode = $oNode->attributes->getNamedItem($_sName);        
			return (is_object($oAttrNode))?$oAttrNode->nodeValue:false;
		  }
	  }
	  /**
	  * Sets attribute value of the specified element
	  * @param String $_sXPath
	  * @param String|Array $_sName  name of the attribute
	  * @param String $_sValue new value of the attribute
	  * @return Boolean
	  */
	  public function setAttributeValue($_sXPath, $_sName, $_sValue = '') {
		  $this->getLock();
		  $aNodes = $this->getNode($_sXPath, true);
		  if(is_array($_sName)&&empty($_sName)) return false;
		  foreach($aNodes as $oNode) {
			if(is_array($_sName)) {
				foreach($_sName as $sAName=>$sAValue)
					$oNode->attributes->getNamedItem($sAName)->nodeValue = $sAValue;                    
			} else {
				  $oNode->attributes->getNamedItem($_sName)->nodeValue = $_sValue;
			}
		  }
		  return true;
	  }
	  /**
	  * Returns proxy object for specified element
	  * @param String $_sXPath
	  * @return ElementProxy
	  */
	  public function getElement($_sXPath) {
		  return new ElementProxy($this->oXMLDocument, $this->getNode($_sXPath), $this);
	  }
	  /**
	  * Returns array of proxy objects
	  * @param String $_sXPath
	  * @param String $_sConvAttr
	  * @return Array of Element Proxy
	  */
	  public function getElementsArray($_sXPath, $_sConvAttr = false) {
		  $aNodes   = $this->getNode($_sXPath, true);
		  $aProxies = array();
		  if(!empty($aNodes))
		  foreach($aNodes as $oNode)
			$aProxies[] = new ElementProxy($this->oXMLDocument, $oNode, $this, $_sConvAttr);
		  return $aProxies;
	  }
	  /**
	  * Add new element
	  * @param String $_sXPath parent element
	  * @param String $_sName  name of the new element
	  * @param String $_sValue new value of the new element (optional)
	  * @return ElementProxy new element to work with
	  */
	  public function addElement($_sXPath, $_sName, $_sValue="") {
		  $this->getLock();
		  $oNode          = $this->getNode($_sXPath, false, true);
		  if(!is_object($oNode)) return new ElementProxy(null, null);
		  $oNewElement = $this->oXMLDocument->createElement($_sName, "");
		  $oData       = $this->oXMLDocument->createCDATASection($_sValue);
		  $oNewElement->appendChild($oData);
		  $oNode->appendChild($oNewElement);
		  return new ElementProxy($this->oXMLDocument, $oNewElement, $this);
	  }
	  /**
	  * Remove the element
	  * @param String $_sXPath
	  * @return Boolean
	  */
	  public function removeElement($_sXPath) {
		  $this->getLock();
		  $aNodeList    = $this->getNode($_sXPath, true, true);
		  foreach($aNodeList as $oNode) {
			$oParent = $oNode->parentNode;
			  $oParent->removeChild($oNode);
		  }
		  return true;
	  }
	  /**
	  * Add new attribute to the specified element
	  * @param String $_sXPath element
	  * @param String $_sName  new attribute name
	  * @param String $_sValue new attribute value (optional)
	  * @return Boolean
	  */
	  public function addAttribute($_sXPath, $_sName, $_sValue="") {
		  $this->getLock();
		  $oNode          = $this->getNode($_sXPath, false, true);
		  $oNewElement = $this->oXMLDocument->createAttribute($_sName);
		  $oNewElement->nodeValue = $_sValue;
		  $oNode->appendChild($oNewElement);        
		  return true;
	  }
	  /**
	  * Remove the attribute from the specified element
	  * @param String $_sXPath
	  * @param String $_sName  name of the deleting attribute
	  * @return Boolean
	  */
	  public function removeAttribute($_sXPath, $_sName) {
		  $this->getLock();
		  $oNode = $this->getNode($_sXPath, false, true);
		  $oNode->removeAttribute($_sName);
		  return true;
	  }
	  /**
	  * Registers ElementProxy in the recover-pool for feature recoverind if need 
	  */
	  public function registerElement(ElementProxy &$_Element) { 
		  // Temporary disabled. Cyclic links crashes php
		  return true;
		  //----------------------------------------------
		  if($this->bFileLocked) return false;
		  if(!($_Element->exists() && $_Element->valid())) return false;          
		  $this->aElementPool[] = $_Element;          
		  $aDescriptors    = array_keys($this->aElementPool);
		  $aRevDescriptors = array_reverse($aDescriptors);
		  return $aRevDescriptors[0];
	  }
	  /**
	  * Unregisters ElementProxy from the recover-pool 
	  */
	  public function unregisterElement($_iElementDescriptor) { 
		  // Temporary disabled. Cyclic links crashes php
		  return true;
		  //---------------------------------------------
		  if(!self::$bCanLock) return;
		  if(array_key_exists($_iElementDescriptor, $this->aElementPool))
			unset($this->aElementPool[$_iElementDescriptor]);          
	  }
	  
	  /**
	  * Get node or node list according to the specified XPath
	  * @param String  $_sXPath
	  * @param Boolean $_bReturnList return one or more nodes in array (optional)
	  * @param Boolean $_bClearCache if true method clears node cache
	  * @return DOMNode|Array(DOMNode)
	  */
	  private function getNode($_sXPath, $_bReturnList = false, $_bClearCache = false) {      	  
		  $bCached  = false;               
		  if($_bClearCache) $this->aCacheStore = array();
		  if(isset($this->aCacheStore[$_sXPath]) ) { 
			  $oChildList = $this->aCacheStore[$_sXPath]; $bCached = true;
		  } else {
			  $oChildList = $this->oXPath->evaluate($_sXPath);
			  $this->aCacheStore[$_sXPath] = $oChildList;
		  }          
		  if(!is_object($oChildList)) return ($_bReturnList) ? array() : NULL;
		  if($oChildList->length == 0) return ($_bReturnList) ? array() : NULL;
		  if($_bReturnList) {
			 $aReturn = array();
			 $iLength = $oChildList->length;
			 for($i=0; $i<$iLength; $i++)
				  $aReturn[] = $oChildList->item($i);
			 return $aReturn;
		  } else {
			 return $oChildList->item(0);
		  }
	  }
	  /**
	  * Makes a tree of childs
	  * @param DOMNode $_oRoot root DOMNode
	  * @param Boolean $_bParseAttrib indicates wether we should place attributes to the elements
	  * @param Boolean $_bFlat if true top-level children will be placed only
	  * @return Array  list of the 
	  */
	  private function getTree($_oRoot, $_bParseAttrib = true, $_bFlat = false) {
		  $aTreeLevel = array();
		  $oChildren  = $_oRoot->childNodes;
		  
		  if(!is_object($oChildren)) {
			return Array();
		  }

		  if($oChildren->length)
		  for($i=0; $i<$oChildren->length; $i++) {
			  $oChild   = $oChildren->item($i);
			  if($oChild->nodeName == "#text") continue;
			  $aContent = array();
			  if(!$_bFlat)
					 $aContent = $this->getTree($oChild, $_bParseAttrib, $_bFlat);
			  if($_bParseAttrib)
					$aContent['@attributes'] = $this->getAttributeList($oChild);
			  $aContent['@value'] = $oChild->nodeValue;
			  $aTreeLevel[$oChild->nodeName][] = $aContent;
		  }
		  return $aTreeLevel;
	  }
	  /**
	  * Returns array of the element attributes
	  * @param DOMNode $_oNode
	  * @return Array (Name=>Value)
	  */
	  private function getAttributeList($_oNode) {
		  $aAttrList = array();
		  
		  if(!is_object($_oNode->attributes)) {
			return Array();
		  }
		  
		  if($_oNode->attributes->length > 0)
		  foreach($_oNode->attributes as $oAttr)
			$aAttrList[$oAttr->nodeName] = $oAttr->nodeValue;
		  return $aAttrList;
	  }      
}


/**
* Simle proxy class for easing using DOM
* @author Leeb <ignat@umi-cms.ru>
* @version 1.0
*/
class ElementProxy {
    /**
    * Pool Descriptor
    * @var Int
    */
    private $iDescriptor  = false;
    /**
    * Current Document
    * @var DOMDocument
    */
    private $oDocument    = NULL; 
    /**
    * Working DOM element
    * @var DOMNode
    */
    private $oElementNode = NULL;
    /**
    * Parent proxy object reference
    * @var XMLProxy 
    */
    private $oParentProxy = NULL;
    /**
    * Valid flag
    * @var Boolean
    */
    private $isValid      = false;
    /**
    * Attribute that will be converted to a string
    * @var Boolean|String
    */
    private $sConvAttrib  = false;
    private $node_type = NULL;
    
    /**
    * @desc Constructor
    * @param DOMDocument    $_oDocument    Document object for new entitys creation
    * @param DOMNode        $_oElementNode Element we want to work with
    * @param XMLProxy       $_oParentProxy Parent proxy object
    * @param String|Boolean $_sConvAttr    Attribute name for __toString() conversion
    */
    public function __construct($_oDocument, $_oElementNode, $_oParentProxy = NULL, $_sConvAttr = false) {
        if(is_object($_oDocument)&&is_object($_oElementNode)) $this->isValid = true;
        $this->oDocument    = $_oDocument;
        $this->oElementNode = $_oElementNode;
        $this->oParentProxy = $_oParentProxy;
        $this->sConvAttrib  = $_sConvAttr;
        if($this->oParentProxy && $this->iDescriptor === false)
            $this->iDescriptor  = $this->oParentProxy->registerElement( $this );
    }
    /**
    * @desc Public destructor
    */
    public function __destruct() {
        if($this->oParentProxy && $this->iDescriptor !== false)
            $this->oParentProxy->unregisterElement($this->iDescriptor);
    }
    /**
    * @desc Overloaded getter for working with element attributes 
    */
    public function __get($_sAttibuteName) {
        return $this->getAttribute($_sAttibuteName);
    }
    /**
    * @desc  Returns value of the specified attribute
    * @param String $_sAttributeName name of the attribute
    * @return Mixed attribute value
    */
    public function getAttribute($_sAttibuteName) {
        if(!$this->isValid) return false;
        $oAttribute = $this->oElementNode->attributes->getNamedItem($_sAttibuteName);
        if($oAttribute)
            return $oAttribute->nodeValue;
        else
            return "";
    }
    /**
    * @desc Overloaded setter for working with element attributes
    */
    public function __set($_sAttributeName, $_AttributeValue) {
        $this->setAttribute($_sAttributeName, $_AttributeValue);
    }
    /**
    * @desc  Sets the specified attribute value
    * @param String $_sAttributeName name of the attribute
    * @param Mixed attribute value
    */
    public function setAttribute($_sAttributeName, $_AttributeValue) {
        if(!$this->isValid) return false;
        if($this->oParentProxy) $this->oParentProxy->getLock();
        if($this->oElementNode->hasAttribute($_sAttributeName)) {
            $this->oElementNode->attributes->getNamedItem($_sAttributeName)->nodeValue = $_AttributeValue;
        } else {
            $oAttribute = $this->oDocument->createAttribute($_sAttributeName);
            $oAttribute->nodeValue = $_AttributeValue;
            $this->oElementNode->appendChild($oAttribute);
        }
    }
    /**
    * @desc Returns name of the element
    * @return String
    */
    public function getName() {
        if(!$this->isValid) return false;
        return $this->oElementNode->nodeName;
    }
    /**
    * @desc Returns text value of the node
    * @return String
    */
    public function getValue() {
        if(!$this->isValid) return false;
        return $this->oElementNode->nodeValue;
    }
    /**
    * @desc Sets new text value to the node
    * @param String $_NewValue
    */
    public function setValue($_NewValue) {
        if(!$this->isValid) return false;
        if($this->oParentProxy) $this->oParentProxy->getLock();
        while($this->oElementNode->childNodes->length) {
            $this->oElementNode->removeChild( $this->oElementNode->childNodes->item(0) );
        }        
        //$oData = $this->oDocument->createCDATASection($_NewValue);
        $oData = $this->oElementNode->ownerDocument->createCDATASection($_NewValue);
        $this->oElementNode->appendChild($oData);
    }
    /**
    * @desc Return count of attributes
    * @return Integer
    */
    public function getAttributesCount() {
        if(!$this->isValid) return false;
        return $this->oElementNode->attributes->length;
    }
    /**
    * @desc Return list of the attributes
    * @return Array (Name=>Value)
    */    
    public function getAttributesList() {
        if(!$this->isValid) return false;
        $aList  = array();
        $length = $this->oElementNode->attributes->length;
        for($i=0; $i<$length; $i++) {
            $oAttribute = $this->oElementNode->attributes->item($i);
            $aList[$oAttribute->nodeName] = $oAttribute->nodeValue;
        }
        return $aList;
    }
    /**
    * @desc Removes current element
    */
    public function Remove() {
        if(!$this->isValid) return false;
        if($this->oParentProxy) $this->oParentProxy->getLock();
        $oParent = $this->oElementNode->parentNode;
        $oParent->removeChild($this->oElementNode);
    }    
    /**
    * @desc   Return count of the child elements
    * @return Integer
    */
    public function getChildCount() {
        if(!$this->isValid) return false;
        $oXPath = new DOMXPath($this->oDocument);
        return $oXPath->evaluate("descendant::*", $this->oElementNode)->length;
    }
    /**
    * @desc  Returns specified child    
    * @param Integer $_iNumber number of child n the list
    * @param String $_sName name of the element (optional)
    * @return ElementProxy
    */
    public function getChild($_iNumber, $_sName=""){
        if(!$this->isValid) return false;
        if(($_sName == "")||($_sName==NULL)) $_sName = "*";
        $oXPath = new DOMXPath($this->oDocument);
        return new ElementProxy($this->oDocument, 
                                $oXPath->evaluate("descendant::".$_sName."[".$_iNumber."]", $this->oElementNode)->item(0),
                                $this->oParentProxy);
    }
    /**
    * @desc   Returns proxy object for the parent node
    * @return ElementProxy    
    */
    public function getParent() {
        return new ElementProxy($this->oDocument, $this->oElementNode->parentNode, $this->oParentProxy);
    }
    /**
    * @desc Creates new child element and returns it proxy
    * @param String $_sName name of the new element
    * @return ElementProxy
    */
    public function addChild($_sName) {
        if(!$this->isValid) return false;
        if($this->oParentProxy) $this->oParentProxy->getLock();
        $oNewElement = $this->oDocument->createElement($_sName);
        $this->oElementNode->appendChild($oNewElement);
        return new ElementProxy($this->oDocument, $oNewElement, $this->oParentProxy);
    }
    /**
    * @desc Clones self
    * @param bool $_bDeep Indicates whether to copy all descendant nodes
    * @return ElementProxy
    */
    public function cloneSelf($_bDeep = false) {
        if(!$this->isValid) return false;
        if($this->oParentProxy) $this->oParentProxy->getLock();
        $oNewElement = $this->oElementNode->cloneNode($_bDeep);
        $this->oElementNode->parentNode->appendChild($oNewElement);
        return new ElementProxy($this->oDocument, $oNewElement, $this->oParentProxy);
    }
    /**
    * @desc Sets attribute that will be used by __toString method
    * @param String $_sName name of the attribute (additionally may be <#text>, empty or <false>)
    * @return none 
    */
    public function setToStringAttribute($_sName) {
        $this->sConvAttrib = $_sName;
    }
    /**
    * @desc Converts object to a string (standard overload)
    * @return String representation of the object
    */
    public function __toString() {
        if($this->sConvAttrib === false) {
            $sResult   = '<' . $this->getName();
            $aAttrList = $this->getAttributesList();
            if(!empty($aAttrList))
            foreach($aAttrList as $sName => $sValue)
                $sResult .= ' ' . $sName . '="' . $sValue . '"';
            $sValue    = $this->getValue();
            if(strlen($sValue))
                $sResult .= '>' . $sValue . '</' . $this->getName() . '>';
            else
                $sResult .= ' />';
            return $sResult;
        }
        if($this->sConvAttrib == '#text' || strlen($this->sConvAttrib) == 0) {
            return $this->getValue();
        } else {
            return $this->getAttribute($this->sConvAttrib);
        }
    }
    /**
    * @desc Indicates existence of the element
    * @return Boolean
    */
    public function exists() {
        return $this->isValid;
    }
    /**
    * @desc Indicates if the element is valid
    * @return Boolean
    */
    public function valid() {
        return $this->isValid;
    }
    /**
    * @desc  Restores the object in new context
    * @param DOMDocument $_oDocument Document in new context
    * 
    */
    public function restore(DOMDocument $_oDocument) {
        $sXPath             = self::getXPath( $this->oElementNode );
        $this->oDocument    = $_oDocument;
        $oXPathObject       = new DOMXPath( $this->oDocument );
        $oNodeList          = $oXPathObject->evaluate( $sXPath );
        if($oNodeList->length == 0) return $this->isValid = false;
        $this->oElementNode = $oNodeList->item(0);
        if(is_object($_oDocument)&&is_object($_oElementNode)) {
            $this->isValid = true;
        } else {
            $this->isValid = false; 
        }
        return $this->isValid;
    }
    /**
    * @desc   Creates xpath of the given node
    * @param  DOMNode $_oNode
    * @return String XPath
    */
    private static function getXPath(DOMNode $_oNode) {
        switch($_oNode->nodeType) {
            case XML_ELEMENT_NODE:
                $sResult = (($_oNode->parentNode) ? self::getXPath($_oNode->parentNode) : '' ) . '/' . $_oNode->nodeName;
                $iLength = $_oNode->attributes->length;
                if($iLength) $sResult .= '[';
                for($i=0; $i<$iLength; $i++) {
                    $oAttribute = $_oNode->attributes->item($i);
                    $sResult .= (($i>0)?' and ':'') . $oAttribute->nodeName . '="' . $oAttribute->nodeValue . '"';
                }
                if($iLength) $sResult .= ']';
                return $sResult;
                break;
            default:
                return '';
        }
        return '';
    }
}


	interface iSingleton {
		public static function getInstance($c = NULL);
/*		public function isExists($elementId);	*/	//TODO: Move to collection pattern
	};


/**
	* Базовый класс синглетон
*/
	abstract class singleton {
		private static $instances = Array();

		/**
			* Конструктор, который необходимо перегрузить в дочернем классе
		*/
		abstract protected function __construct();

		/**
			* Получить экземпляр класса, необходимо перегрузить в дочернем классе:
			* parent::getInstance(__CLASS__)
			* @param String имя класса
			* @return singleton экземпляр класса
		*/
		public static function getInstance($c) {
			if (!isset(singleton::$instances[$c])) {
				singleton::$instances[$c] = new $c;
			}
			return singleton::$instances[$c];
		}

		/**
			* Запрещаем копирование
		*/
		public function __clone() {
			throw new coreException('Singletone clonning is not permitted. Just becase it\'s non-sense.');
		}
		
		/**
			* Отключить кеширование повторных sql-запросов
		*/
		protected function disableCache() {
			if(!defined('MYSQL_DISABLE_CACHE')) {
				define('MYSQL_DISABLE_CACHE', '1');
			}
		}

		/**
			* Получить языкозависимую строку по ее ключу
			* @param String $label ключ строки
			* @return String значение строки в текущей языковой версии
		*/
		protected function translateLabel($label) {
			$prefix = "i18n::";
			if(substr($label, 0, strlen($prefix)) == $prefix) {
				$str = getLabel(substr($label, strlen($prefix)));
			} else {
				$str = getLabel($label);
			}
			return (is_null($str)) ? $label : $str;
		}

	};


	interface iUmiEntinty {
		public function getId();
		public function commit();
		public function update();

		public static function filterInputString($string);
	};


/**
	* Базовый класс для классов, которые реализуют ключевые сущности ядра системы.
	* Реализует основные интерфейсы, которые должна поддерживать любая сущность.
*/
	abstract class umiEntinty {
		protected $id, $is_updated = false;

		protected $bNeedUpdateCache = false;

		/**
			* Конструктор сущности, должен вызываться из коллекций
			* @param Integer $id id сущности
			* @param Array $row=false массив значений, который теоретически может быть передан в конструктор для оптимизации
		*/
		public function __construct($id, $row = false) {
			$this->setId($id);
			$this->is_updated = false;
			if($this->loadInfo($row) === false) {
				throw new privateException("Failed to load info for {$this->store_type} with id {$id}");
			}
		}

		/**
			* Запрещаем копирование
		*/
		public function __clone() {
				throw new coreException('umiEntinty must not be cloned');
    	}

		/**
			* Деструктор сущности проверят, были ли внесены изменения. Если да, то они сохраняются
		*/
		public function __destruct() {
			if ($this->is_updated) {
				$this->save();
				$this->setIsUpdated(false);
				$this->updateCache();
			} elseif ($this->bNeedUpdateCache) {
				// В memcached кидаем только при деструкте и только если были какие-то изменения
				$this->updateCache();
			}
		}

		/**
			* Вернуть id сущности
			* @return Integer $id
		*/
		public function getId() {
			return $this->id;
		}

		/**
			* Изменить id сущности
			* @param Integer $id новый id сущности
		*/
		protected function setId($id) {
			$this->id = (int) $id;
		}

		/**
			* Узнать, есть ли несохраненные модификации
			* @return Boolean true если есть несохраненные изменения
		*/
		public function getIsUpdated() {
			return $this->is_updated;
		}

		/**
			* Установить флаг "изменен"
			* @param Boolean $is_updated=true значение флага "изменен"
		*/
		public function setIsUpdated($is_updated = true) {
			$this->is_updated 	    = (bool) $is_updated;
			$this->bNeedUpdateCache = $this->is_updated;
		}

		/**
			* Загрузить необходимую информацию о сущности из БД. Требует реализации в дочернем классе.
		*/
		abstract protected function loadInfo();

		/**
			* Сохранить в БД информацию о сущности. Требует реализации в дочернем классе.
		*/
		abstract protected function save();

		/**
			* Применить совершенные изменения, если они есть. Если нет, вернет false
			* @return Boolean true если изменения примененые и при этом не возникло ошибок
		*/
		public function commit() {
			if ($this->is_updated) {
				$this->disableCache();
				$res = $this->save();

				if (cacheFrontend::getInstance()->getIsConnected()) {
					// обновляем инфу об объекте из базы для корректного сохранения не применившихся свойств в memcached
					$this->update();
					$this->updateCache();
				} else {
					$this->setIsUpdated(false);
				}

				return $res;
			} else {
				return false;
			}
		}

		/**
			* Заново прочитать все данные сущности из БД. Внесенные изменения скорее всего будут утеряны
			* @return Boolean результат операции зависит от реализации loadInfo() в дочернем классе
		*/
		public function update() {
			$res = $this->loadInfo();
			$this->setIsUpdated(false);
			return $res;
		}

		/**
			* Отфильтровать значения, попадающие в БД
			* @param String $string значение
			* @return String отфильтрованное значение
		*/
		public static function filterInputString($string) {
			$string = mysql_real_escape_string($string);
			return $string;

		}
		
		/**
			* Обновить версию сущности, которая находится в кеше
		*/
		protected function updateCache() {
			cacheFrontend::getInstance()->save($this, $this->store_type);
		}
		
		/**
			* Отключить каширование повторных sql-запросов
		*/
		protected function disableCache() {
			if(!defined('MYSQL_DISABLE_CACHE')) {
				if(get_class($this) === "umiObjectProperty") {
					return;
				}
				define('MYSQL_DISABLE_CACHE', '1');
			}
		}

		/**
			* Перевести строковую константу по ее ключу
			* @param String $label ключ строковой константы
			* @return String значение константы в текущей локали
		*/
		protected function translateLabel($label) {
			$prefix = "i18n::";
			if(substr($label, 0, strlen($prefix)) == $prefix) {
				$str = getLabel(substr($label, strlen($prefix)));
			} else {
				$str = getLabel($label);
			}
			return (is_null($str)) ? $label : $str;
		}

		/**
			* Получить ключ строковой константы, если она определена, либо вернуть саму строку
			* @param String $str строка, для которых нужно определить ключ
			* @param String $pattern="" префикс ключа, используется внутри системы
			* @return String ключ константы, либо параметр $str, если такого значение нет в списке констант
		*/
		protected function translateI18n($str, $pattern = "") {
			$label = getI18n($str, $pattern);
			return (is_null($label)) ? $str : $label;
		}
	};


	interface iRegedit {
		public function getKey($keyPath, $rightOffset = 0);

		public function getVal($keyPath);
		public function setVar($keyPath, $value);
		public function setVal($keyPath, $value);

		public function delVar($keyPath);

		public function getList($keyPath);
	};



class regedit extends singleton implements iRegedit {
	public $cacheFolder = "cache/";

	private $useFileCache;
	private $needUpdate = false;
	private $is_updated = false;

	//��� ������ � ��������
	private $cache = Array();

	private function serPath($kPath) {
		$kPath = strtolower($kPath);
		if(substr($kPath, 0, 2) != "//")
			$kPath = "//" . $kPath;
	}

	protected function __construct($useFileCache = true) {
		$this->useFileCache = $useFileCache;

		$this->cacheFolder = CURRENT_WORKING_DIR . '/' . $this->cacheFolder;

		$this->readCache();
		
		//Fix mistakes :(
		$fn = "./files/umisubscribers.csv";
		if(file_exists($fn)) {
			if(is_writable($fn)) {
				unlink($fn);
			}
		}
		
		$this->removeStatModule();
	}

	public static function getInstance($c = NULL) {
		return parent::getInstance(__CLASS__);
	}

	public function __destruct() {
		$this->saveCache();
	}

	//... ���� �����
	public function __toString() {
		return "umi.__regedit";
	}

	//���������� �-� getKey
	//���������� id ����� � ������� cms_reg ��
	//����������� ���� (modules/content/func_perms/...)
	//����� ��������� ����� ���� //modules/... !!!
	public function getKey($kPath, $roffset = 0) {
		$this->serPath($kPath);

		if(substr($kPath, 0, 2) == "//")	// for "//.../......"
			$kPath = substr($kPath, 2, strlen($kPath) - 2);

		if(isset($this->cache['key://' . $kPath]))
			return $this->cache['key://' . $kPath];

		$sp = split("/", $kPath);

		$key = 0;
		$i = 0;
		foreach($sp as $cp) {
			if(sizeof($sp) < ++$i + $roffset) {
				$this->need_update = true;
				return $this->cache['key://' . $kPath] = $key;
			} 
			
			$key = XMLFactory::getInstance()->getProxy('registry.xml')->getAttributeValue('//key[@rel='.$key.' and @var="'.($cp).'"]', 'id');

			if($key === false) {
				$this->need_update = true;
				return false;
			}
		}

		$this->need_update = true;
		
		if($key) {
			$this->cache['key://' . $kPath] = $key;
		}

		return $key;
	}

	//���������� �������� ����� ������� � ������� � ���
	public function getVal($kPath) {
		$this->serPath($kPath);
		
		if(isset($this->cache[$kPath])) {
			return $cached = $this->cache[$kPath];
		}
		$key_id = (int) $this->getKey($kPath);
		if($key_id == 0)
			return false;

		$this->needUpdate = true;

//		echo "������� �������� ���� � ����� ����: \"$kPath\"<br />\r\n";
        $val = XMLFactory::getInstance()->getProxy('registry.xml')->getAttributeValue('//key[@id='.$key_id.']','val');
        if($val === false) return false;
        if(!$val) {
                $val = "";
        }
        return $this->cache[$kPath] = $val;
	}

	//���������� ������ �������� ������� ������� � ������� �� � ���
	public function getList($kPath) {
		$this->serPath($kPath);

		if(isset($this->cache['list:' . $kPath])) {
			return $this->cache['list:' . $kPath];
		}

		if($key_id = (int) $this->getKey($kPath)) {
			$res = Array();
            $cache = $this->cache;
            $aKey = XMLFactory::getInstance()->getProxy('registry.xml')->getElementsArray('//key[@rel='.$key_id.']');
            foreach($aKey as $Key) {
                $var = $Key->getAttribute('var');
                $val = $Key->getAttribute('val');
                $cache[$kPath . "/" . $var] = $val;
                $res[] = Array($var, $val);                
            }	

			$this->cache['list:' . $kPath] = $res;

			$this->needUpdate = true;
			return $res;
		} else
			return false;
	}

	//��������� ���� � ���������� �������� � ���
	public function setVar($kPath, $val) {
		$this->serPath($kPath);
		
		$this->is_updated = true;
		$this->needUpdate = true;
		
		$this->cache = Array();

		unset($this->cache[$kPath]);
		unset($this->cache['list:' . $kPath]);
		$nPath = preg_replace("/(.*)\/[A-z0-9]*/i", "\\1", $kPath);
		unset($this->cache['list:' . $nPath]);
        $oProxy = XMLFactory::getInstance()->getProxy('registry.xml');
		if($key_id = (int) $this->getKey($kPath)) {
            $oProxy->setAttributeValue('/umi/registry/key[@id='.$key_id.']', 'val', str_replace("'", "\\'", $val) );
			return true;			
		} else {
			$key_id = (int) $this->getKey($kPath, 1);
			$sp = split("/", $kPath);
//			$var = $sp[sizeof($sp)-1];
			$var = array_pop($sp);            
            $new_key_id = (int)$oProxy->getAttributeValue('/umi/registry/key[not(@id <= preceding-sibling::key/@id) and not(@id <=following-sibling::key/@id)]','id') + 1;
            $NewKey     = $oProxy->addElement('/umi/registry', 'key', '');
            
            $NewKey->setAttribute('id', $new_key_id);
            $NewKey->setAttribute('var', ($var) );
            $NewKey->setAttribute('val', str_replace("'", "\\'", ($val)));
            $NewKey->setAttribute('rel', $key_id);
            return true;
		}
	}

	//������� ���� �� �������
	//!!N1!! ���������� ������������� �� ���������. �� ������� ���� ����.
	//!!N2!! � ��� ����� ������������� false. ������� �� ����� ������������ �-�� in_array()
	public function delVar($kPath) {
		$this->serPath($kPath);

//		unset($this->cache[$kPath]);
		unset($this->cache[$kPath]);
		unset($this->cache['key:' . $kPath]);
		unset($this->cache['list:' . $kPath]);
		$nPath = preg_replace("/(.*)\/[A-z0-9]*/i", "\\1", $kPath);
//		unset($this->cache['key:' . $nPath]);
		unset($this->cache['list:' . $nPath]);

		$this->needUpdate = true;

		if($key_id = (int) $this->getKey($kPath)) {
			$oProxy = XMLFactory::getInstance()->getProxy('registry.xml');
            $oProxy->removeElement('//key[@id='.$key_id.']');
            $oProxy->removeElement('//key[@rel='.$key_id.']');
            
			return true;
		} else
			return false;
	}

	public function saveCache() {
		if($this->is_updated) {
			$filepath = $this->cacheFolder . "reg";
			if(file_exists($filepath)) {
				unlink($filepath);
			}
			return false;
		}

		if((!$this->useFileCache || !$this->needUpdate) && file_exists($this->cacheFolder . "reg"))
			return false;

		@file_put_contents($this->cacheFolder . "reg", serialize($this->cache));
		@chmod($this->cacheFolder . "reg", 0777);
	}

	public function readCache() {
		if(!$this->useFileCache)
			return false;

		if(!file_exists($this->cacheFolder . "reg"))
			return false;

		$this->cache = unserialize(file_get_contents($this->cacheFolder . "reg"));
	}


	final public static function checkSomething($a, $b) {

        $trial_lifetime = 3600*24*45;

        if(($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == 'subdomain.localhost') && $_SERVER['SERVER_ADDR'] == '127.0.0.1') {
            return true;
        }
        
        if(substr($_SERVER['HTTP_HOST'], strlen($_SERVER['HTTP_HOST']) - 4, 4) == "cvs5") {
            return true;
        }
        
        foreach($b as $version_line => $c3) {
            $is_valid = (bool) (substr($a, 12, strlen($a) - 12) == $c3);
            if($is_valid === true) {
                define("CURRENT_VERSION_LINE", $version_line);
                
                if($version_line == "trial") {
                    $create_time = filectime(__FILE__);
                    $current_time = time();
                    
                    if(($current_time - $create_time) > $trial_lifetime){
                        include "./errors/trial_expired.html";
                        exit();
                    }
                }
                return true;
            }
        }
    }
    
    
    public function setVal($var, $val) {
	    return $this->setVar($var, $val);
    }
    
    
    protected function removeStatModule() {
    	if($this->getVal("//modules/stat")) {
    		$this->delVar("//modules/stat");
      	}
    }
};



	interface iSearchModel {
		public function runSearch($searchString, $searchTypesArray = NULL);
		public function getContext($elementId, $searchString);
		public function getIndexPages();
		public function getIndexWords();
		public function getIndexWordsUniq();
		public function getIndexLast();
		public function truncate_index();
		public function index_all($limit = false);
		public function index_item($elementId);

		public function index_items($elementId);
		public function unindex_items($elementId);
	};


/**
* @desc Dummy implmentation. XML Driver does not support searching
*/
	class searchModel extends singleton implements iSingleton, iSearchModel {

		public function __construct() {
		}		
		
		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		} 

		public function index_all($limit = false) {
			$total = 0;
			
            $aElements = XMLFactory::getInstance()->getProxy('hierarchy.xml')->getElementsArray('/umi/elements/element');
			foreach($aElement as $Element) {			
                $element_id = $Element->id;
                $updatetime = $Element->updatetime;
				if(!$this->elementIsReindexed($element_id, $updatetime)) {
					$this->index_item($element_id, true);
					
					++$total;
					if(($limit !== false) && (--$limit == 0)) {
						break;
					}
				}
			}
			return $total;
		}

		public function index_item($element_id, $is_manual = false) {
			if(!$is_manual) {
				if(getServer("HTTP_HOST") == "localhost") {
					return false;
				}
			}
			if(defined("UMICMS_CLI_MODE")) {
				return false;
			}
			$index_data = $this->parseItem($element_id);
		}

		public function elementIsReindexed($element_id, $updatetime) {
			return true;
		}

		public function parseItem($element_id) {             
            return true;
		}

		public function buildIndexImage($index_fields) {			
			return array();
		}

		public static function splitString($str) {
			return null;
		}

		public function updateSearchIndex($element_id, $index_image) {
			return true;
		}

		public static function getWordId($word) {
			return 0;
		}


		public function getIndexPages() {
			return 0;
		}


		public function getIndexWords() {
			return 0;
		}


		public function getIndexWordsUniq() {
			return 0;
		}


		public function getIndexLast() {
			return 0;
		}


		public function truncate_index () {
			return true;
		}


		public function runSearch($str, $search_types = NULL, $hierarchy_rels = NULL) {			
			return array();
		}

		public function buildQueries($words, $search_types = NULL, $hierarchy_rels = NULL) {
			return null;
		}

		public function prepareContext($element_id) {			
			return null;
		}


		public function getContext($element_id, $search_string) {			
			return null;
		}
        
        public function unindex_items($element_id) {
            return true;
        }
    
        public function index_items($element_id) {
            return true;        
        }

        private function expandArray($arr, &$result) {
            return true;
        }
	};


	interface iPermissionsCollection {

		public function getOwnerType($ownerId);
		public function makeSqlWhere($ownerId);

		public function isAllowedModule($ownerId, $module);
		public function isAllowedMethod($ownerId, $module, $method);
		public function isAllowedObject($ownerId, $objectId);
		public function isSv($userId = false);
		public function isAdmin($userId = false);
		public function isOwnerOfObject($objectId, $userId);

		public function resetElementPermissions($elementId, $ownerId = false);
		public function resetModulesPermissions($ownerId);
		
		public function setElementPermissions($ownerId, $elementId, $level);
		public function setModulesPermissions($ownerId, $module, $method = false);

		public function setDefaultPermissions($elementId);

		public function hasUserPermissions($ownerId);
		
		public function copyHierarchyPermissions($fromOwnerId, $toOwnerId);
		
		public function getUserId();
		
		public function setAllElementsDefaultPermissions($ownerId);
		
		public function getUsersByElementPermissions($elementId, $level = 1);
		
		public function pushElementPermissions($elementId, $level = 1);
	};


	class permissionsCollection	extends	singleton implements iSingleton, iPermissionsCollection	{
		protected $methodsPermissions =	Array(), $tempElementPermissions = Array();
			private	  $user_id = 0;

		public function	__construct() {
			$users = cmsController::getInstance()->getModule("users");
			if($users instanceof def_module) {
				$this->user_id = $users->user_id;
			}
			
		}


		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}


		public function	getOwnerType($owner_id)	{
			if($owner_object = umiObjectsCollection::getInstance()->getObject($owner_id)) {
				if($groups = $owner_object->getPropByName("groups")) {
					return $groups->getValue();
				} else {
					return $owner_id;
				}
			} else {
				return false;
			}
		}


		public function	makeSqlWhere($owner_id)	{
			static $cache =	Array();
			if(isset($cache[$owner_id])) return	$cache[$owner_id];

			$owner = $this->getOwnerType($owner_id);
			
			if(is_numeric($owner)) {
				$owner = Array($owner);
			}
			if(!in_array(2373, $owner)) {
				$owner[] = 2373;
			}

			if(is_numeric($owner)) {
				$sql = "(@owner=".$owner_id.")";
			} else {
				$owner[] = $owner_id;

				$sql = "";
				$sz	= sizeof($owner);
				for($i = 0;	$i < $sz; $i++)	{
					$sql .=	"@owner=".$owner[$i];
					if($i <	($sz - 1)) {
						$sql .=	" or ";
					}
				}
				$sql = "({$sql})";
			}
			return $cache[$owner_id] = $sql;
		}



		public function	isAllowedModule($owner_id, $module)	{
			//$sql_where = $this->makeSqlWhere($owner_id);

			if($owner_id == false) {
				$owner_id = $this->getUserId();
			}

			if($this->isSv($owner_id)) return true;

			$module	   = ($module);
			if(substr($module, 0, 7) ==	"macros_") return false;
			$allow = XMLFactory::getInstance()->getProxy('mperms/'.$owner_id.'.xml')->getCount("/umi/mperms/perm[@module='".$module."' and @method='']");

			return (bool)($allow > 0);
		}


		public function	isAllowedMethod($owner_id, $module,	$method) {
			if($module == "content" && $method == "") return 1;
			if($module == "config" && $method == "menu") return 1;
			//if($module == "users" && $method == "permissions") return 1;
			if($module == "backup" && $method == "backup_panel") return 1;
			
			if($this->isSv($owner_id)) {
				return true;
			}
			
			$method = $this->getBaseMethodName($module, $method);

			$methodsPermissions	= &$this->methodsPermissions;
			if(!isset($methodsPermissions[$owner_id]) || !is_array($methodsPermissions[$owner_id]))	{
				$methodsPermissions[$owner_id] = Array();
			}
			$cache = &$methodsPermissions[$owner_id];

			//$sql_where = $this->makeSqlWhere($owner_id);

			if($module == "backup" && $method == "rollback") return	true;
			if($module == "config" && ($method == "lang_list" || $method ==	"lang_phrases")) return	true;
			if($module == "users" && ($method == "auth"	|| $method == "login_do" ||	$method	== "login")) return	true;

			$cache_key = $module;

			if(!array_key_exists($cache_key, $cache)) {
				$aElements = XMLFactory::getInstance()->getProxy('mperms/'.$owner_id.'.xml')->getElementsArray("/umi/mperms/perm[@module='".$module."']");				
				foreach($aElements as $Element)	{				 
					$cache[$cache_key][] = $Element->method;
				}
				
				if($owner_id != 2373) {
					$aElements = XMLFactory::getInstance()->getProxy('mperms/2373.xml')->getElementsArray("/umi/mperms/perm[@module='".$module."']");				
					foreach($aElements as $Element)	{				 
						$cache[$cache_key][] = $Element->method;
					}
				}
			}
			
			if(!isset($cache[$cache_key])) {
				return false;
			}
			
			if (in_array($method, $cache[$cache_key]) || in_array(strtolower($method), $cache[$cache_key]))	{
				return true;
			} else {
				return false;
			}
		}


		public function	isAllowedObject($owner_id, $object_id) {
			$object_id = (int) $object_id;
			if($object_id == 0)	return Array(0,	0);
	
			if($this->isSv($owner_id)) {
				return Array(true, true);
			}
		
			static $cache;
			if(!is_array($cache)) {
				$cache = Array();
			}
		
		
			$cache_key = $owner_id . "." . $object_id;
		
			if(isset($cache[$cache_key])) {
				if(is_array($cache[$cache_key])) {
					return $cache[$cache_key];
				}
			}

//			if($res	= cacheFrontend::getInstance()->loadSql($cache_key)) {
//				return $res;
//			}

			$sql_where = $this->makeSqlWhere($owner_id);
			$level = XMLFactory::getInstance()->getProxy('eperms/'.$object_id.'.xml')->getElementValue('/umi/eperms/perm['.$sql_where.']', true);			            
            $level = is_array($level)&&!empty($level) ? max( array_map('intval', $level) ) : 0;
            
			$r = false;	$e = false;
			if($level >= 1)	{
				$r = true;
			}

			if($level >= 2)	{
				$e = true;
			}
		
			$res = Array($r, $e);
		
			$cache[$cache_key] = $res;
		
//			cacheFrontend::getInstance()->saveSql($cache_key,	$res);
			return $res;
		}

		public function isAllowedDomain($owner_id, $domain_id) {

			$owner_id = (int) $owner_id;
			$domain_id = (int) $domain_id;
			
			if($this->isSv($owner_id)) {
				return 1;
			}
			
	
			$aElements = XMLFactory::getInstance()->getProxy('mperms/'.$owner_id.'.xml')->getElementsArray("/umi/mperms/perm[@module='domain' and @method=".$domain_id."]");

			return count($aElements) > 0;
		}



		public function	isSv($user_id = false) {
			static $is_sv;
		
			if($user = umiObjectsCollection::getInstance()->getObject($user_id)) {
				if($groups = $user->getPropByName("groups")) {
					if(in_array(15,	$groups->getValue())) {
						$is_sv = true;
						return true;
					} else {
						return false;
					}
				} else {
					return false;
				}
			} else {
				return false;
			}
		}
		
		public function isAdmin($user_id = false) {
			static $is_admin = Array();
		    
		    if($user_id === false) {
		        $user_id = $this->getUserId();
		    }
		    
		    if(isset($is_admin[$user_id])) {
		        return $is_admin[$user_id];
		    }
		    
		    if($this->isSv($user_id)) {
		        return $is_admin[$user_id] = true;
		    }
		    $cnt = XMLFactory::getInstance()->getProxy('mperms/'.$user_id.'.xml')->getCount("/umi/mperms/perm[@method='']");            
            return $is_admin[$user_id] = (bool) $cnt;			
		}

		public function	isOwnerOfObject($object_id,	$user_id) {
			if($user_id	== $object_id) {	//Objects == User, that's ok
				return true;
			} else {
				$object	= umiObjectsCollection::getInstance()->getObject($object_id);
				$owner_id =	$object->getOwnerId();

				if($owner_id ==	0 || $owner_id == $user_id)	{
					return true;
				} else {
					return false;
				}
			}
		}
		
		public function	setDefaultPermissions($element_id) {
			if(!umiHierarchy::getInstance()->isExists($element_id))	{
				return false;
			}					

			XMLFactory::createFile('eperms/'.$element_id.'.xml', '<?xml	version="1.0" encoding="utf-8" ?><umi><eperms /></umi>');
			$oProxy	= XMLFactory::getInstance()->getProxy('eperms/'.$element_id.'.xml', true);
			$oProxy->removeElement('/umi/eperms/perm');

			$element = umiHierarchy::getInstance()->getElement($element_id,	true, true);
			$hierarchy_type_id = $element->getTypeId();
			$hierarchy_type	= umiHierarchyTypesCollection::getInstance()->getType($hierarchy_type_id);

			$module	= $hierarchy_type->getName();
			$method	= $hierarchy_type->getExt();


			//Getting outgroup users
			$type_id = umiObjectTypesCollection::getInstance()->getBaseType("users", "user");

			$sel = new umiSelection;
			$sel->setObjectTypeFilter();
			$sel->addObjectType($type_id);

			$group_field_id	= umiObjectTypesCollection::getInstance()->getType($type_id)->getFieldId("groups");
			$sel->setPropertyFilter();
			$sel->addPropertyFilterIsNull($group_field_id);

			$users = umiSelectionsParser::runSelection($sel);


			//Getting groups list
			$object_type_id	= umiObjectTypesCollection::getInstance()->getBaseType("users",	"users");

			$sel = new umiSelection;

			$sel->setObjectTypeFilter();
			$sel->addObjectType($object_type_id);
			$groups	= umiSelectionsParser::runSelection($sel);

			$objects = array_merge($users, $groups);


			//Let's	get	element's ownerId and his groups (if user)
			$owner_id =	$element->getObject()->getOwnerId();
			if($owner =	umiObjectsCollection::getInstance()->getObject($owner_id)) {
				if($owner_groups = $owner->getValue("groups")) {
					$owner_arr = $owner_groups;
				} else {
					$owner_arr = Array($owner_id);
				}
			} else {
				$owner_arr = Array();
			}


			foreach($objects as	$ugid) {
				if($module == "content") $method ==	"page";
				if($this->isAllowedMethod($ugid, $module, $method))	{
					if(in_array($ugid, $owner_arr) || $ugid	== SV_GROUP_ID)	{
						$level = 2;
					} else {
						$level = 1;
					}					 
					$oPerm		  =	$oProxy->addElement('/umi/eperms', 'perm', $level);
					$oPerm->owner =	$ugid;					
				}
			}
			$cache_key = $this->user_id	. "." .	$element_id;
			cacheFrontend::getInstance()->saveSql($cache_key,	Array(true,	true));
		}


		public function setDefaultElementPermissions(iUmiHierarchyElement $element, $owner_id) {
			$module = $element->getModule();
			$method = $element->getMethod();

			$level = 0;
			if($this->isAllowedMethod($owner_id, $module, $method)) {
				$level = 1;
			}
			if($this->isAllowedMethod($owner_id, $module, $method . ".edit")) {
				$level = 2;
			}
			
			$this->setElementPermissions($owner_id, $element->getId(), $level);

			return $level;
		}


		public function	resetElementPermissions($elementId,	$ownerId = false) {
			$elementId = (int) $elementId;		
			
			if($ownerId	===	false) {				
				XMLFactory::getInstance()->getProxy('eperms/'.$elementId.'.xml')->removeElement('/umi/eperms/perm');
			} else {
				$ownerId = (int) $ownerId;
				XMLFactory::getInstance()->getProxy('eperms/'.$elementId.'.xml')->removeElement('/umi/eperms/perm[@owner='.$ownerId.']');				
			}		
		}	
		
		public function	resetModulesPermissions($ownerId) {
			$ownerId = (int) $ownerId;			
			XMLFactory::getInstance()->getProxy('mperms/'.$ownerId.'.xml')->removeElement('/umi/mperms/perm');
			return true;
		}		
		
		public function	setElementPermissions($ownerId,	$elementId,	$level)	{
			$ownerId	= (int)	$ownerId;
			$elementId	= (int)	$elementId;
			$level		= (int)	$level;
			$oPerm = XMLFactory::getInstance()->getProxy('eperms/'.$elementId.'.xml')->addElement('/umi/eperms', 'perm',	$level);
			$oPerm->owner =	$ownerId;
			return true;			
		}


		public function setAllowedDomain($owner_id, $domain_id, $allow = 1) {
			$owner_id = (int) $owner_id;
			$domain_id = (int) $domain_id;
			$allow = (int) $allow;
		

			if ($allow) {
				$oPerm = XMLFactory::getInstance()->getProxy('mperms/'.$owner_id.'.xml')->addElement('/umi/mperms', 'perm');
				$oPerm->module = 'domain';
				$oPerm->method = $domain_id;
			} else {
				XMLFactory::getInstance()->getProxy('mperms/'.$owner_id.'.xml')->removeElement('/umi/mperms/perm[@module="domain" and method="'.$domain_id.'"]');
			}
			
			return true;
		}

		public function	setModulesPermissions($ownerId,	$module, $method = false) {
			$ownerId = (int) $ownerId;
			$module	= ($module);
			
			if($method !== false) {
				return $this->setMethodPermissions($ownerId, $module, $method);
			} else {
                $sProxyName = 'mperms/'.$ownerId.'.xml';
                XMLFactory::createFile($sProxyName, '<?xml version="1.0" encoding="utf-8"?><umi><mperms /></umi>');
				$oPerm = XMLFactory::getInstance()->getProxy($sProxyName)->addElement('/umi/mperms', 'perm');
				$oPerm->module = $module;
				$oPerm->method = "";
				return true;
			}
		}	
		
		protected function setMethodPermissions($ownerId, $module, $method)	{
			$method	= ($method);
            $sProxyName = 'mperms/'.$ownerId.'.xml';
            XMLFactory::createFile($sProxyName, '<?xml version="1.0" encoding="utf-8"?><umi><mperms /></umi>');
			$oPerm = XMLFactory::getInstance()->getProxy($sProxyName)->addElement('/umi/mperms', 'perm');
			$oPerm->module = $module;
			$oPerm->method = $method;
			return true;
		}		
				
		public function	hasUserPermissions($ownerId) {
			$cnt = 0;
			$oXMLFInstance = XMLFactory::getInstance();
			$aFiles		   = XMLFactory::listFiles('eperms/');			  
			foreach($aFiles	as $sFileName) {
				$cnt +=	$oXMLFInstance->getProxy('eperms/'.$sFileName)->getCount('/umi/eperms/perm[@owner='.$ownerId.']');
			}			 
			return $cnt;
		}		
		
		public function	copyHierarchyPermissions($fromUserId, $toUserId) {
			$fromUserId	= (int)	$fromUserId;
			$toUserId	= (int)	$toUserId;
			
			$oXMLFInstance = XMLFactory::getInstance();			   
			$aFiles		   = XMLFactory::listFiles('eperms/');			  
			foreach($aFiles	as $sFileName) {
			   $aPerms = $oXMLFInstance->getProxy('eperms/'.$sFileName)->getElementsArray('/umi/eperms/perm[@owner='.$fromUserId.']');
			   foreach($aPerms as $Perm) { 
				   $NewPerm	= $Perm->cloneSelf(true);
				   $NewPerm->owner = $toUserId;
			   }
			}
			return true;
		}
		
		
		public function	getUserId()	{
			return $this->user_id;
		}

		public function	setAllElementsDefaultPermissions($owner_id)	{
			$hierarchy = umiHierarchy::getInstance();
		    $aEIDs = XMLFactory::getInstance()->getProxy('hierarchy.xml')->getAttributeValue('/umi/elements/element', 'id', true);
			foreach($aEIDs as $element_id) {			
				$element = $hierarchy->getElement($element_id, true, true);
				if($element	instanceof umiHierarchyElement) {
					$this->setDefaultElementPermissions($element, $owner_id);

					$hierarchy->unloadElement($element_id, $owner_id);
				}
			}
		}
		
		protected function getBaseMethodName($module, $method) {
			$methods = $this->getStaticPermissions($module);
			
			if(is_array($methods)) {
				if(array_key_exists($method, $methods)) {
					return $method;
				} else {
					foreach($methods as $base_method => $sub_methods) {
						if(in_array($method, $sub_methods) || in_array(strtolower($method), $sub_methods)) {
							return $base_method;
						}
					}
					return $method;
				}
			} else {
				return $method;
			}
		}
		
		protected function getStaticPermissions($module) {
			static $cache = Array();
			
			$static_file = "./classes/modules/" . $module . "/permissions.php";
			if(file_exists($static_file)) {
				include $static_file;
				if(isset($permissions)) {
					$static_permissions = $permissions;
				
					$static_file_custom = "./classes/modules/" . $module . "/permissions.custom.php";
					if(file_exists($static_file_custom)) {
						unset($permissions);
						include $static_file_custom;
						if(isset($permissions)) {
							$static_permissions = array_merge($static_permissions, $permissions);
						}
					}

					$cache[$module] = $static_permissions;
					unset($static_permissions);
					unset($permissions);
				} else {
					$cache[$module] = false;
				}
			} else {
				$cache[$module] = false;
			}
			return $cache[$module];
		}
		
		public function getUsersByElementPermissions($elementId, $level = 1) {
			return XMLFactory::getInstance()->getProxy('eperms/'.$elementId.'.xml')->getAttributeValue('number(/umi/eperms/perm/text())>='.$level, 'owner', true);			
		}
		
		public function pushElementPermissions($elementId, $level = 1) {
			if(array_key_exists($elementId, $this->tempElementPermissions) == false) {
				$this->tempElementPermissions[$elementId] = (int) $level;
			}
		}

	};


	interface iCifi {
		public function __construct($cifiName, $sourceDir, $imagesOnly = true);
		public function read_files();
		public function make_element($defaultValue = "");
		public function make_div();
		public function make_upload();
		public function getUpdatedValue($useHTTP_POST_FILES = false);
	};


	class cifi implements iCifi {
		private $name, $dir;
		private $exts = Array('gif', 'jpg', 'jpeg', 'bmp', 'png', 'swf', 'GIF', 'JPG', 'JPEG', 'BMP', 'PNG', 'SWF');
		private $image_only;
		public $maxFilesPerFolder = 150;

		public function __construct($name, $dir, $image_only = true) {
			$this->name = $name;
			$this->dir = $dir;
			$this->image_only = $image_only;
		}
		
		// for file select
		public function old_read_files() {
			$res = Array();

			$dir = $this->dir;

			$o_dir = new umiDirectory($dir);
			$arr_files = $o_dir->getFiles("(?<!\.htaccess|\.svn)\s?$");

			$i = 0;
			foreach ($arr_files as $s_file_name => $s_file_path) {
				if((++$i > $this->maxFilesPerFolder) && $this->maxFilesPerFolder) {
					return true;
				}
				
				$name_arr = split("\.", $s_file_name);
				$ext = $name_arr[sizeof($name_arr)-1];
				if(!in_array($ext, $this->exts) && $this->image_only) {
					continue;
				}
				
				$s_file_name = str_replace("&", "&amp;", $s_file_name);
				$tmp = iconv("CP1251", "UTF-8//IGNORE", $s_file_name);
				if($tmp === false) {
					$tmp = iconv("CP1251", "UTF-8//TRANSLIT", $s_file_name);
				}
				$res[] = $tmp;
			}
			//sort($res);

			return $res;
		}
		
		// for new images select
		public function read_files() {
			$res = Array();

			$dir = $this->dir;

			$o_dir = new umiDirectory($dir);
			$arr_files = $o_dir->getFiles("(?<!\.htaccess|\.svn)\s?$");

			$i = 0;
			foreach ($arr_files as $s_file_name => $s_file_path) {
				if((++$i > $this->maxFilesPerFolder) && $this->maxFilesPerFolder) {
					return true;
				}
				
				$name_arr = split("\.", $s_file_name);
				$ext = $name_arr[sizeof($name_arr)-1];
				if(!in_array($ext, $this->exts) && $this->image_only) {
					continue;
				}
				
				$s_file_name = str_replace("&", "&amp;", $s_file_name);
				
				$tmp1 = iconv("CP1251", "UTF-8//IGNORE", $this->dir.'/'.$s_file_name);
				$tmp2 = iconv("CP1251", "UTF-8//IGNORE", $s_file_name);
				
				/*if($tmp === false) {
					$tmp1 = iconv("CP1251", "UTF-8//TRANSLIT", $this->dir.'/'.$s_file_name);
					$tmp2 = iconv("CP1251", "UTF-8//TRANSLIT", $s_file_name);
				}*/
				
				$res[] = array($tmp1, $tmp2);
			}
			//sort($res);

			return $res;
		}


		public function make_element($def = "", $arr_extfiles = array()) {
			$files_arr = $this->read_files();
			//$files_arr = array_merge($arr_extfiles, $files_arr);

			if($files_arr === true) {
				if($def) {
					$files_arr = Array();
					$files_arr[] = $def;
				} else {
					$files_arr = Array();
				}
				$is_parted = true;
			} else {
				$is_parted = false;
			}

			if(!is_array($files_arr)) {
				return false;
			}

			$res = <<<JS
<script type="text/javascript">
	cifi_images_arr_{$this->name} = Array();

JS;


			$sz = sizeof($files_arr);
			for($i = 0; $i < $sz; $i++) {
				$res .= <<<JS
	cifi_images_arr_{$this->name}[{$i}] = "{$files_arr[$i]}";

JS;
			}
			
			$def = ($def) ? "'" . str_replace("&", "&amp;", $def) . "'" : "''";
			
			if($is_parted) {
				$dir = "'" . mysql_escape_string(basename($this->dir)) . "'";
			} else {
				$dir = "''";
			}

			$res .= <<<JS
	cifi_generate('{$this->name}', cifi_images_arr_{$this->name}, {$def}, {$dir});
</script>

JS;

			return $res;
		}


		public function make_div() {
			return <<<END
<div id="cifi_mdiv_{$this->name}" style="text-align: left; border: #FFF 1px solid;"></div>
END;
		}

		public function make_upload() {
			$selected = $_REQUEST['select_' . $this->name];
			$uploaded = $_FILES['f_' . $this->name];

			if($uploaded['name'] == $selected) {
				system_upload_file($uploaded['tmp_name'], $this->dir, $uploaded['name']);
				return $uploaded['name'];
			} else {
				return false; 
			}
		}



		public function getUpdatedValue($mode = false) {
			$name = $this->name;
			$folder = $this->dir;

			$select_value = getRequest('select_' . $name);

			$files_arr = ($mode) ? $HTTP_POST_FILES : $_FILES;

			if($files_arr['pics']['size'][$name] != 0) {
				if($select_value == $files_arr['pics']['name'][$name]) {
					system_upload_file($files_arr['pics']['tmp_name'][$name], $folder, $files_arr['pics']['name'][$name]);
					$res = $files_arr['pics']['name'][$name];
				} else {
					$res = $select_value;
				}
			} else {
				$res = $select_value;
			}
			
			return $res;
		}
	};


class ranges {
    public function __construct() {
        $this->days = Array(
            'понедельник'    => 0,
            'вторник'    => 1,
            'среда'        => 2,
            'четверг'    => 3,
            'пятница'    => 4,
            'суббота'    => 5,
            'восскресенье'    => 6,
            'пн'        => 0,
            'пон'        => 0,
            'вт'        => 1,
            'ср'        => 2,
            'срд'        => 2,
            'чт'        => 3,
            'чет'        => 3,
            'пт'        => 4,
            'птн'        => 4,
            'сб'        => 5,
            'вс'        => 6);

        $this->months = Array(
            'январь'    => 0,
            'февраль'    => 1,
            'март'        => 2,
            'апрель'    => 3,
            'май'        => 4,
            'июнь'        => 5,
            'июль'        => 6,
            'август'    => 7,
            'сентябрь'    => 8,
            'октябрь'    => 9,
            'ноябрь'    => 10,
            'декабрь'    => 11,

            'янв'    => 0,
            'ян'    => 0,
            'фев'    => 1,
            'фв'    => 1,
            'мар'    => 2,
            'апр'    => 3,
            'ап'    => 3,
            'май'    => 4,
            'июнь'        => 5,
            'ин'        => 5,
            'июль'        => 6,
            'ил'        => 6,
            'август'    => 7,
            'авг'    => 7,
            'сент'    => 8,
            'сен'    => 8,
            'окт'    => 9,
            'ок'    => 9,
            'нбр'    => 10,
            'дек'    => 11);
    }

    public function get($str = "", $mode = 0) {
        $str = $this->prepareStr($str, $mode);
        return $this->str2range($str);
    }

    private function prepareStr($str = "", $mode = 0) {
        switch($mode) {
            case 0: {
                return system_assoc_replace($str, $this->days);
                break;
            }

            case 1: {
                return system_assoc_replace($str, $this->months);
                break;
            }
        }
    }

    private function str2range($s) {
        $s = preg_replace("/ +/", " ", $s);
        $s = preg_replace("/ - /", "-", $s);
        $s = preg_replace("/! /", "!", $s);

        if(preg_match_all("/(?!!)(\d+)(?!\-)/", $s, $nums)) {
            $nums = $nums[1];
        }

        if(preg_match_all("/!(\d+)(?!\-)/", $s, $unnums)) {
            $unnums = $unnums[1];
        }

        if(preg_match_all("/(?!!)(\d+\-\d+)/", $s, $range)) {
            $range = $range[0];
        }

        if(preg_match_all("/!(\d+\-\d+)/", $s, $urange)) {
            $urange = $urange[1];
        }

        $res = Array();

        
        $sz = sizeof($urange);
        for($i = 0; $i  < $sz; $i++) {
            if(is_array($urange[$i])) continue;
            list($from, $to) = split("-", $urange[$i]);
            if($from <= $to) {
                for($n = $from; $n <= $to; $n++) {                
                    $unnums[] = $n;
                }
            } else {
                for($n = $from; $n <= 31; $n++) {                
                    $unnums[] = $n;
                }
                for($n = 1; $n <= $to; $n++) {                
                    $unnums[] = $n;
                }
            }
        }        
                
        $sz = sizeof($range);
        for($i = 0; $i < $sz; $i++) {
            if(is_array($range[$i])) continue; 
            list($from, $to) = split("-", $range[$i]);            
            if($from <= $to) {
                for($n = $from; $n <= $to; $n++) {                
                    if(!in_array((int) $n, $unnums))
                        $res[] = (int) $n;
                }
            } else {
                for($n = $from; $n <= 31; $n++) {                
                    if(!in_array((int) $n, $unnums))
                        $res[] = (int) $n;
                }
                for($n = 1; $n <= $to; $n++) {                
                    if(!in_array((int) $n, $unnums))
                        $res[] = (int) $n;
                }
            }
        }      

        $sz = sizeof($nums);
        for($i = 0; $i < $sz; $i++) {
            if(!in_array((int) $nums[$i], $unnums) && !in_array((int) $nums[$i], $res) && !empty($nums[$i]))
                $res[] = (int) $nums[$i];
        }
        return $res;
    }
}



	interface iTranslit {
		public static function convert($string);
	}


/**
	* Работа с транслитом
*/
	class translit implements iTranslit {
		public static	$fromUpper = Array("Э/g", "Ч", "Ш", "Ё", "Ё", "Ж", "Ю", "Ю", "Я", "Я", "А", "Б", "В", "Г", "Д", "Е", "З", "И", "Й", "К", "Л", "М", "Н", "О", "П", "Р", "С", "Т", "У", "Ф", "Х", "Ц", "Щ", "Ъ", "Ы", "Ь");
		public static	$fromLower = Array("э", "ч", "ш", "ё", "ё", "ж", "ю", "ю", "я", "я", "а", "б", "в", "г", "д", "е", "з", "и", "й", "к", "л", "м", "н", "о", "п", "р", "с", "т", "у", "ф", "х", "ц", "щ", "ъ", "ы", "ь");
		public static	$toLower   = Array("e\'", "ch", "sh", "yo", "jo", "zh", "yu", "ju", "ya", "ja", "a", "b", "v", "g", "d", "e", "z", "i", "j", "k", "l", "m", "n", "o", "p", "r", "s",  "t", "u", "f", "h", "c", "w", "~", "y", "\'");
		
		/**
			* Конвертировать строку в транслит
			* @param String $str входная строка
			* @return String транслитерированная строка
		*/
		public static function convert($str) {

			$str = umiObjectProperty::filterInputString($str);

			$str = str_replace(self::$fromLower, self::$toLower, $str);
			$str = str_replace(self::$fromUpper, self::$toLower, $str);
			$str = strtolower($str);

			$str = preg_replace("/([^A-z^0-9^_^\-]+)/", "_", $str);
			$str = preg_replace("/[\/\\\',\t`\^\[\]]*/", "", $str);
			$str = str_replace(chr(8470), "", $str);
			$str = preg_replace("/[ \.]+/", "_", $str);

			$str = preg_replace("/([_]+)/", "_", $str);
			$str = trim(trim($str), "_");

			return $str;
		}
	}


	interface iTemplater {
		public function init($input);

		public function loadLangs();
		public function putLangs($input);

		public function parseInput($input);

		public function parseMacros($macrosStr);
		public function executeMacros($macrosArr);

		public static function pushEditable($module, $method, $id);
		
		public function cleanUpResult($input);
	}



class templater extends singleton implements iTemplater {
	public $defaultMacroses = Array(Array("%content%", "macros_content"),
		Array("%menu%", "macros_menu"),
		Array("%header%", "macros_header"),
		Array("%pid%", "macros_returnPid"),
		Array("%parent_id%", "macros_returnParentId"),
		Array("%pre_lang%", "macros_returnPreLang"),
		Array("%curr_time%", "macros_curr_time"),
		Array("%domain%", "macros_returnDomain"),
		Array("%domain_floated%", "macros_returnDomainFloated"),
	
		Array("%title%", "macros_title"),
		Array("%keywords%", "macros_keywords"),
		Array("%describtion%", "macros_describtion"),
		Array("%description%", "macros_describtion"),
		Array("%adm_menu%", "macros_adm_menu"),
		Array("%adm_navibar%", "macros_adm_navibar"),
		Array("%skin_path%", "macros_skin_path"),
		Array("%ico_ext%", "macros_ico_ext"),
	
		Array("%current_user_id%", "macros_current_user_id"),
		Array("%current_version_line%", "macros_current_version_line"),
		Array("%context_help%", "macros_help"),
		Array("%current_alt_name%", "macros_current_alt_name"));
	public $cacheMacroses = Array();
    public $processingCache = array(); // For macrosess in process (infinite recursion preventing)
	public $cachePermitted = false;
	public $LANGS = Array();
	public $cacheEnabled = 0;

	protected function __construct() {
	}

	public static function getInstance($c = NULL) {
		return parent::getInstance(__CLASS__);
	}



	public function init($input) {
		$this->loadLangs();
		

		$this->cacheMacroses["%content%"] = $this->parseInput(cmsController::getInstance()->parsedContent);

		$res = $this->parseInput($input);
		$res = $this->putLangs($res);

		$this->output = system_parse_short_calls($res);
	}

	public function loadLangs() {
		$try_path = CURRENT_WORKING_DIR . "/classes/modules/lang." . cmsController::getInstance()->getLang()->getPrefix() . ".php";
		if(!file_exists($try_path)) {
			$try_path = CURRENT_WORKING_DIR . "/classes/modules/lang.php";
		}

		include_once $try_path;

		if(isset($LANG_EXPORT)) {
			$cmsControllerInstance = cmsController::getInstance();
			$cmsControllerInstance->langs = array_merge($cmsControllerInstance->langs, $LANG_EXPORT);
			unset($LANG_EXPORT);
		}
		return true;
	}

	public function putLangs($input) {
		$res = $input;
		
		if(($p = strpos($res, "%")) === false) return $res;

		$langs = cmsController::getInstance()->langs;

		foreach($langs as $cv => $cvv) {
			if(is_array($cvv)) continue;
			
			$m = "%" . $cv . "%";
			
			if(($mp = strpos($res, $m, $p)) !== false) {
				$res = str_replace($m, $cvv, $res, $mp);
			}
		}

		return $res;
	}

	public function parseInput($input) {
		$res = $input;

		if(is_array($res)) {
			return $res;
		}

		$pid = cmsController::getInstance()->getCurrentElementId();
		$input = str_replace("%pid%", $pid, $input);
		
		if(strrpos($res, "%") === false) {
			return $res;
		}

		$input = str_replace("%%", "%\r\n%", $input);

		if(preg_match_all("/%([A-z_]{3,})%/m", $input, $temp)) {
			$temp = $temp[0];

			$sz = sizeof($temp);
			
			
			for($i = 0; $i < $sz; $i++) {
				try {
					$r = $this->parseMacros($temp[$i]);
				} catch (publicException $e) {
				}
			}
		}

		if(preg_match_all("/%([A-zА-Яа-я0-9]+\s+[A-zА-Яа-я0-9_]+\([A-zА-Яа-я \/\._\-\(\)0-9%:<>,!@\|'&=;\?\+#]*\))%/mu", $input, $temp)) {
			$temp = $temp[0];

			$sz = sizeof($temp);
			
			for($i = 0; $i < $sz; $i++) {
				try {
					$r = $this->parseMacros($temp[$i]);
				} catch (publicException $e) {
				}
			}
		}
        if(is_array($res)) implode('',$res);
		$cache = $this->cacheMacroses;
		$cache = array_reverse($cache);
		foreach($cache as $ms => $mr) {
			if(($p = strpos($res, $ms)) !== false) {                
				$res = str_replace($ms, $mr, $res);
			}
		}

		return $this->cleanUpResult( $this->putLangs($res) );
	}

	public function parseMacros($macrosStr) {	//????????? ????????? ?????? ?? ????????????: ???????? ??????, ??????        
		$macrosArr = Array();			// ? ?????? ??????????
//echo $macrosStr, "\n";
		if(strrpos($macrosStr, "%") === false)
			return $macrosArr;
            
        // Set up processing cache
        if(isset($this->processingCache[$macrosStr])) return $macrosStr;
        $this->processingCache[$macrosStr] = true;
        //--------------------------------------
			
		$preg_pattern = "/%([A-z0-9]+)\s+([A-z0-9]+)\((.*)\)%/m";
		if(defined("TPL_MODE")) {
			if(TPL_MODE == "SIMPLE") {
				$preg_pattern = "/%([A-z0-9]+)\s+([A-z0-9]+)\((.*)\)%/Um";
			}
		}
		

		if(preg_match($preg_pattern, $macrosStr, $pregArr)) {
			$macrosArr['str']    = $pregArr[0];
			$macrosArr['module'] = $pregArr[1];
			$macrosArr['method'] = $pregArr[2];
			$macrosArr['args']   = $pregArr[3];

			if(array_key_exists($macrosArr['str'], $this->cacheMacroses)) {
                unset($this->processingCache[$macrosStr]);
				return $this->cacheMacroses[$macrosArr['str']];
            }

			//????????? ?????? ?????????? ?? ??????
			$params = split(",", $macrosArr['args']);

			$sz = sizeof($params);
			for($i = 0; $i < $sz; $i++) {
				$cparam = $params[$i];

				if(strpos($cparam, "%") !== false) {
					$cparam = $this->parseInput($cparam);
				}
				$params[$i] = trim($cparam, "'\" ");
			}
			$macrosArr['args'] = $params;

			$res = $macrosArr['result'] = $this->executeMacros($macrosArr);
			$this->cacheMacroses[$macrosArr['str']] = $macrosArr['result'];	//? ???
            unset($this->processingCache[$macrosStr]);
			return $res;

		} else {

			//????????. ????? ????, ??? ?????-?? ?????????? ??????...
			$defMs = $this->defaultMacroses;

			$sz = sizeof($defMs);
			for($i = 0; $i < $sz; $i++)
				if(stripos($macrosStr, $defMs[$i][0]) !== false) {
						if(array_key_exists($defMs[$i][0], $this->cacheMacroses)) {
                            unset($this->processingCache[$macrosStr]);
							return $this->cacheMacroses[$defMs[$i][0]];
                        }
							
						if(!isset($defMs[$i][2])) {
							$defMs[$i][2] = NULL;
						}

						$res = $this->executeMacros(
										Array(
											"module" => $defMs[$i][1],
											"method" => $defMs[$i][2],
											"args"   => Array()
											)
									);
						$res = $this->parseInput($res);
						$this->cacheMacroses[$defMs[$i][0]] = $res;	//? ???
                        unset($this->processingCache[$macrosStr]);
						return $res;
					}

			$this->cacheMacroses[$macrosStr] = $macrosStr;
            unset($this->processingCache[$macrosStr]);
			return $macrosStr;
		}
	}

	public function executeMacros($macrosArr) {
		$module = $macrosArr['module'];
		$method = $macrosArr['method'];

		if($module == "current_module")
			$module = cmsController::getInstance()->getCurrentModule();
		$res = "";

		if(!$method) {
			$cArgs = $macrosArr['args'];
			$res = call_user_func_array($macrosArr['module'], $cArgs);
		}

		if($module == "core" || $module == "system" || $module == "custom") {
			$pk = &system_buildin_load($module);

			if($pk) {
				$res = $pk->cms_callMethod($method, $macrosArr['args']);
			}
		}

		if($module != "core" && $module != "system") {
			if(system_is_allowed($module, $method)) {
				if($module_inst = cmsController::getInstance()->getModule($module)) {
					$res = $module_inst->cms_callMethod($method, $macrosArr['args']);
				}
			}
		}

        if(is_array($res)) {
        	$tmp = "";
        	foreach($res as $s) {
        		if(!is_array($s)) {
        			$tmp .= $s;
        		}
        	}
        	$res = $tmp;
        }
		if(strpos($res, "%") !== false) {
			$res = $this->parseInput($res);
		}

		return $res;
	}


	public function __destruct() {
	}

	public $blocks = Array();

	public static function pushEditable($module, $method, $id) {
		if($module === false && $method === false) {

			if($element = umiHierarchy::getInstance()->getElement($id)) {
				$elementTypeId = $element->getTypeId();

				if($elementType = umiObjectTypesCollection::getInstance()->getType($elementTypeId)) {
					$elementHierarchyTypeId = $elementType->getHierarchyTypeId();

					if($elementHierarchyType = umiHierarchyTypesCollection::getInstance()->getType($elementHierarchyTypeId)) {
						$module = $elementHierarchyType->getName();
						$method = $elementHierarchyType->getExt();
					} else {
						return false;
					}
				}
			}
		}

		$templater = templater::getInstance();
		$templater->blocks[] = Array($module, $method, $id);
	}

	public function prepareQuickEdit() {
		$toFlush = $this->blocks;
		
		if(sizeof($toFlush) == 0) return;
		
		$key = md5("http://" . getServer('HTTP_HOST') . getServer('REQUEST_URI'));
		$_SESSION[$key] = $toFlush;
	}


	final public static function getSomething($version_line = "pro") {
		$default_domain = domainsCollection::getInstance()->getDefaultDomain();

		$cs2 = md5($_SERVER['SERVER_ADDR']);
		
		switch($version_line) {
			case "pro":
				$cs3 = md5(md5(md5(md5(md5(md5(md5(md5(md5(md5($default_domain->getHost()))))))))));
				break;

			case "free":
				$cs3 = md5(md5(md5($default_domain->getHost())));
				break;

			case "lite":
				$cs3 = md5(md5(md5(md5(md5($default_domain->getHost())))));
				break;

			case "freelance":
				$cs3 = md5(md5(md5(md5(md5(md5(md5($default_domain->getHost())))))));
				break;
				
			case "trial": {
				$cs3 = md5(md5(md5(md5(md5(md5($default_domain->getHost()))))));
			}
		}

		$licenseKeyCode = strtoupper(substr($cs2, 0, 11) . "-" . substr($cs3, 0, 11));
		return $licenseKeyCode;
	}
	
	
	public function cleanUpResult($input) {
		return $input;
		
		$input = str_replace("%pid%", cmsController::getInstance()->getCurrentElementId(), $input);
		
	    if(!regedit::getInstance()->getVal("//settings/show_macros_onerror")) {
    		$input = preg_replace("/%([A-z?-?А-я \/\._\-\(\)0-9%:<>,!@\|'&=;\?\+#]*)%/m", "", $input);
	    }

		return $input;
	}
};


	interface iCmsController {

		public function loadBuildInModule($moduleName);

//		public function loadModule($moduleName);

		public function getModule($moduleName);

		public function installModule($moduleName);

		public function getSkinPath();


		public function getCurrentModule();
		public function getCurrentMethod();
		public function getCurrentElementId();
		public function getCurrentMode();
		public function getCurrentDomain();
		public function getCurrentLang();

		public function getLang();

		public function setCurrentModule($moduleName);
		public function setCurrentMethod($methodName);
		
		public function getRequestId();
		
		public function getPreLang();
		
		public function calculateRefererUri();
		public function getCalculatedRefererUri();
	}


	class cmsController extends singleton implements iSingleton, iCmsController {
		private	$modules = Array(),
				$current_module = false,
				$current_method = false,
				$current_mode = false,
				$current_element_id = false,
				$current_lang = false,
				$current_domain = false,
				$calculated_referer_uri = false;

		public		$parsedContent = false,
				$currentTitle = false,
				$currentHeader = false,
				$currentMetaKeywords = false,
				$currentMetaDescription = false,

				$langs = Array(),
				$langs_export = Array(),

				$nav_arr = Array(),
				$pre_lang = "",
				$errorUrl, $headerLabel = false;
				
		public		$isContentMode = false;


		protected function __construct() {
			$this->init();
		}

		/**
		* @desc
		* @return cmsController
		*/
		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}


		private function loadModule($module_name) {
			$xpath = "//modules/" . $module_name;
			
			if(!defined("CURRENT_VERSION_LINE")) {
				define("CURRENT_VERSION_LINE", "");
			}
			
			if(CURRENT_VERSION_LINE == "free" || CURRENT_VERSION_LINE == "lite") {
				if($module_name == "forum" || $module_name == "vote" || $module_name == "webforms") {
					return false;
				}
			}
			
			if(ulangStream::getLangPrefix() != "ru" && $module_name == "seo") {
				return false;
			}

			if(regedit::getInstance()->getVal($xpath) == $module_name) {
				$module_path = CURRENT_WORKING_DIR . "/classes/modules/" . $module_name . "/class.php";
				$imodule_path = CURRENT_WORKING_DIR . "/classes/modules/" . $module_name . "/interface.php";

				if(file_exists($module_path)) {
					if(file_exists($imodule_path)) {
					
						include_once $imodule_path;
					}
					include_once $module_path;

					if(class_exists($module_name)) {
						$new_module = new $module_name();
						$new_module->cms_init();
						$new_module->pre_lang = $this->pre_lang;
						$this->modules[$module_name] = $new_module;

						return $new_module;
					}
				} else {
					return false;
				}
			} else {
				return false;
			}
		}


		public function loadBuildInModule($moduleName) {
			//TODO
		}

		public function getModule($module_name) {
			if(!$module_name) return false;

			if(array_key_exists($module_name, $this->modules)) {
				return $this->modules[$module_name];
			} else {
				return $this->loadModule($module_name);
			}
		}

		public function installModule($moduleName) {
			//TODO
		}

		public function getSkinPath() {
			//TODO
		}


		public function getCurrentModule() {
			return $this->current_module;
		}

		public function getCurrentMethod() {
			return $this->current_method;
		}

		public function getCurrentElementId() {
			return $this->current_element_id;
		}

		public function getLang() {
			return $this->current_lang;
		}

		public function getCurrentLang() {
			return $this->getLang();
		}

		public function getCurrentMode() {
			return $this->current_mode;
		}

		public function getCurrentDomain() {
			return $this->current_domain;
		}



		private function init() {
			$this->detectDomain();
			$this->detectLang();
			$this->detectMode();
			$this->loadLangs();


			$LANG_EXPORT = array();
			$lang_file = CURRENT_WORKING_DIR . "/classes/modules/lang.php";
			if (file_exists($lang_file)) {
				include $lang_file;
			}
			$this->langs = array_merge($this->langs, $LANG_EXPORT);
			//$this->langs = $this->langs + $LANG_EXPORT;


			$ext_lang = CURRENT_WORKING_DIR . "/classes/modules/lang." . $this->getCurrentLang()->getPrefix() . ".php";
			if(file_exists($ext_lang)) {
				include $ext_lang;
				$this->langs = array_merge($this->langs, $LANG_EXPORT);
				//$this->langs = $this->langs + $LANG_EXPORT;
			}

			$this->errorUrl = getServer('HTTP_REFERER');
			$this->doSomething();
			$this->calculateRefererUri();
		}

		private function detectDomain() {
			$host = getServer('HTTP_HOST');
			if($domain_id = domainsCollection::getInstance()->getDomainId($host)) {
				$domain = domainsCollection::getInstance()->getDomain($domain_id);
			} else {
				$domain = domainsCollection::getInstance()->getDefaultDomain()->getId();
			}

			if(is_object($domain)) {
				$this->current_domain = $domain;
				return true;
			} else {
//				trigger_error("Can't detect domain \"{$host}\" in domains list", E_USER_WARNING);
				$this->current_domain = domainsCollection::getInstance()->getDefaultDomain();
				return false;
			}
		}

		private function detectLang() {
			$LangIDs = getRequest('lang_id');

			$lang_id = false;
			if($LangIDs != null) {
				if(is_array($LangIDs)) list($LangIDs) = $LangIDs;
				$lang_id = intval($LangIDs);
			} else if (!is_null(getRequest('links')) && is_array($rel = getRequest('rel'))) {
				if(sizeof($rel) && ($elementId = array_pop($rel))) {
					$element = umiHierarchy::getInstance()->getElement($elementId, true);
					if($element instanceof umiHierarchyElement) {
						$lang_id = $element->getLangId();
					}
				}
			} else {
				list($sub_path) = $this->getPathArray();
				$lang_id = langsCollection::getInstance()->getLangId($sub_path);				
			}
			
			if(($this->current_lang = langsCollection::getInstance()->getLang($lang_id)) === false ) {
				if($this->current_domain) {
					if($lang_id = $this->current_domain->getDefaultLangId()) {
						$this->current_lang = langsCollection::getInstance()->getLang($lang_id);
					} else {
						$this->current_lang = langsCollection::getInstance()->getDefaultLang();
					}
				} else {
					$this->current_lang = langsCollection::getInstance()->getDefaultLang();
				}
			}

			if($this->current_lang->getId() != $this->current_domain->getDefaultLangId()) {
				$this->pre_lang = "/" . $this->current_lang->getPrefix();
				$_REQUEST['pre_lang'] = $this->pre_lang;
			}
		}

		private function getPathArray() {
			$path = getRequest('path');
			$path = trim($path, "/");

			return explode("/", $path);
		}

		private function detectMode() {
			$path_arr = $this->getPathArray();
			
			if(sizeof($path_arr) < 2) {
				$path_arr[1] = NULL;
			}
			
			list($sub_path1, $sub_path2) = $path_arr;

			if($sub_path1 == "admin" || $sub_path2 == "admin") {
				$this->current_mode = "admin";
			} else {
				$this->current_mode = "";
				cacheFrontend::$cacheMode = true;
			}
		}


		private function getSubPathType($sub_path) {
			$regedit = regedit::getInstance();

			if(!$this->current_module) {
				if($sub_path == "seo") {
					if(ulangStream::getLangPrefix() != "ru") {
						return "UNKNOWN";
					}
				}
				
				if($sub_path == "trash") {
					def_module::redirect($this->pre_lang . "/admin/data/trash/");
				}
				
				if($regedit->getVal("//modules/" . $sub_path)) {
					$this->setCurrentModule($sub_path);
					return "MODULE";
				}
			}

			if($this->current_module && !$this->current_method) {
				$this->setCurrentMethod($sub_path);
				return "METHOD";
			}

			if($this->current_module && $this->current_method) {
				return "PARAM";
			}

			return "UNKNOWN";
		}


		public function analyzePath() {		//TODO: Add in interface
			$regedit = regedit::getInstance();
			$hierarchy = umiHierarchy::getInstance();
			
			$path_arr = $this->getPathArray();

			$path = getRequest('path');
			$path = trim($path, "/");


			$sz = sizeof($path_arr);
			$url_arr = Array();
			$p = 0;
			for($i = 0; $i < $sz; $i++) {
				$sub_path = $path_arr[$i];

				if($i <= 1) {
					if(($sub_path == $this->current_mode) || ($sub_path == $this->current_lang->getPrefix())) {
						continue;
					}
				}
				$url_arr[] = $sub_path;

				$sub_path_type = $this->getSubPathType($sub_path);

				if($sub_path_type == "PARAM") {
					$_REQUEST['param' . $p++] = $sub_path;
				}
			}

			if(!$this->current_module) {
				if($this->current_mode == "admin") {
					$module_name = $regedit->getVal("//settings/default_module_admin");
					$this->autoRedirectToMethod($module_name);
				} else {
					$module_name = $regedit->getVal("//settings/default_module");
				}
				$this->setCurrentModule($module_name);
			}

			if(!$this->current_method) {
				if($this->current_mode == "admin") {
					$this->autoRedirectToMethod($this->current_module);
				} else {
					$method_name = $regedit->getVal("//modules/" . $this->current_module . "/default_method");
				}
				$this->setCurrentMethod($method_name);
			}


			if($this->getCurrentMode() == "admin") {
				return;
			}

			$element_id = false;
			$sz = sizeof($url_arr);
			$sub_path = "";
			for($i = 0; $i < $sz; $i++) {
				$sub_path .= "/" . $url_arr[$i];

				if(!($tmp = $hierarchy->getIdByPath($sub_path, false, $errors_count))) {
					$element_id = false;
					break;
				} else {
					$element_id = $tmp;
				}
			}
			
			if($element_id) {
				if($errors_count > 0 && !defined("DISABLE_AUTOCORRECTION_REDIRECT")) {
					$path = $hierarchy->getPathById($element_id);
					
					if($i == 0) {
						if($this->isModule($url_arr[0])) {
							$element_id = false;
							break;
						}
					}
					
					header("HTTP/1.1 301 Moved Permanently");
					header("Location: {$path}");
					exit();
				}
				
				$element = $hierarchy->getElement($element_id);
				if($element instanceof umiHierarchyElement) {
				    if($element->getIsDefault()) {
				        $path = $hierarchy->getPathById($element_id);
				        header("HTTP/1.1 301 Moved Permanently");
    					header("Location: {$path}");
    					exit();
				    }
				}
			}

			if(($path == "" || $path == $this->current_lang->getPrefix()) && $this->current_mode != "admin") {
				if($element_id = $hierarchy->getDefaultElementId($this->getCurrentLang()->getId(), $this->getCurrentDomain()->getId())) {
					$this->current_element_id = $element_id;
				}
			}


			if($element = $hierarchy->getElement($element_id, true)) {
				$type = umiHierarchyTypesCollection::getInstance()->getType($element->getTypeId());
				
				if(!$type) return false;

				$this->current_module = $type->getName();
				
				if($ext = $type->getExt()) {
					$this->setCurrentMethod($ext);
				} else {
					$this->setCurrentMethod("content");	//Fixme: content "constructor". Maybe, fix in future?
				}

				$this->current_element_id = $element_id;
			}
		}


		public function setCurrentModule($module_name) {
			$this->current_module = $module_name;
		}


		public function setCurrentMethod($method_name) {
			if(defined("CURRENT_VERSION_LINE")) {
				if(CURRENT_VERSION_LINE == "free" || CURRENT_VERSION_LINE == "lite" || CURRENT_VERSION_LINE == "freelance") {
					if(cmsController::getInstance()->getCurrentMode() == "admin") {
						if($this->current_module == "data" && substr($method_name, 0, strlen("trash")) != "trash" && $method_name != "json_load_hierarchy_level") {
							$this->current_module = "content";
							$this->current_method = "sitetree";
							return false;
						}
					}
				}
			}

			$this->current_method = $method_name;
		}


		public function loadLangs() {
			$modules = regedit::getInstance()->getList("//modules");
			foreach($modules as $module) {
				$module_name = $module[0];

				$lang_path = CURRENT_WORKING_DIR . '/classes/modules/' . $module_name . '/';
				$lang_path .= "lang.php";

				if (file_exists($lang_path)) {
					include $lang_path;
				}

				if(isset($C_LANG)) {
					if(is_array($C_LANG)) {
						$this->langs[$module_name] = $C_LANG;
						unset($C_LANG);
					}
				}
				
				if(isset($LANG_EXPORT)) {
					if(is_array($LANG_EXPORT)) {
						$this->langs = array_merge($this->langs, $LANG_EXPORT);
						//$this->langs = $this->langs + $LANG_EXPORT;
						unset($LANG_EXPORT);
					}
				}

				$lang_path = CURRENT_WORKING_DIR . '/classes/modules/' . $module_name . '/';
				$lang_path .= "lang." . $this->getCurrentLang()->getPrefix() .".php";

				if(file_exists($lang_path)) {
					include $lang_path;

					if(is_array($C_LANG)) {
						$this->langs[$module_name] = $C_LANG;
						unset($C_LANG);
					}

					if(is_array($LANG_EXPORT)) {
						$this->langs = array_merge($this->langs, $LANG_EXPORT);
						//$this->langs = $this->langs + $LANG_EXPORT;
						unset($LANG_EXPORT);
					}
				}
			}
		}


		final private function doSomething () { return false; 
	                          if(defined("CURRENT_VERSION_LINE")) {
                          				if(CURRENT_VERSION_LINE != "demo") {
                          					include CURRENT_WORKING_DIR . "/errors/invalid_license.html";
                          					exit();
                          				} else {
                          					return true;
                          				}
                          			}

                          			$keycode = regedit::getInstance()->getVal("//settings/keycode");

                          			if($this->doStrangeThings($keycode)) {
                          				return true;
                          			}


                          			$comp_keycode = Array();
                          			$comp_keycode['pro'] = templater::getSomething("pro");
                          			$comp_keycode['free'] = templater::getSomething("free");
                          			$comp_keycode['lite'] = templater::getSomething("lite");
                          			$comp_keycode['freelance'] = templater::getSomething("freelance");
                          			$comp_keycode['trial'] = templater::getSomething("trial");

                          			if(regedit::checkSomething($keycode, $comp_keycode)) {
                          				return true;
                          			} else {
                          				include CURRENT_WORKING_DIR . "/errors/invalid_license.html";
                          				exit();
                          			}
                          		}


		
		
		
		final private function doStrangeThings($keycode) {
			$license_file = CURRENT_WORKING_DIR . "/cache/trash";
			$cmp_keycode = false;
			$expire = 604800;
			
			if(!file_exists(CURRENT_WORKING_DIR . "/cache")) {
				mkdir(CURRENT_WORKING_DIR . "/cache", 0777);
			}

			if(file_exists($license_file)) {
				if((time() - filemtime($license_file)) > $expire) {
					$cmp_keycode = base64_decode(file_get_contents($license_file));
				}
			} else {
				file_put_contents($license_file, base64_encode($keycode));
			}
			
			if($cmp_keycode !== false && $keycode) {
				if($keycode === $cmp_keycode) {
					return true;
				}
			}
			return false;
		}
		
		
		public function getRequestId() {
			static $requestId = false;
			if($requestId === false) $requestId = time();
			return $requestId;
		}
		
		public function getPreLang() {
			return $this->pre_lang;
		}
		

		protected function autoRedirectToMethod($module) {
			$pre_lang = $this->pre_lang;
			$method = regedit::getInstance()->getVal("//modules/" . $module . "/default_method_admin");
			
			$url = $pre_lang . "/admin/" . $module . "/" . $method . "/";
			
			header("Location: {$url}");
			exit();
		}
		
		
		public function calculateRefererUri() {
			if($referer = getRequest('referer')) {
				$_SESSION['referer'] = $referer;
			} else {
				if($referer = getSession('referer')) {
					unset($_SESSION['referer']);
				} else {
					$referer = getServer('HTTP_REFERER');
				}
			}
			$this->calculated_referer_uri = $referer;
		}
		
		
		public function getCalculatedRefererUri() {
			if($this->calculated_referer_uri === false) {
				$this->calculateRefererUri();
			}
			return $this->calculated_referer_uri;
		}
		
		
		public function isModule($module_name) {
			$regedit = regedit::getInstance();
			
			if($regedit->getVal('//modules/' . $module_name)) {
				return true;
			} else {
				return false;
			}
			
		}
	};


	interface iLanguageMorph {
		public function __construct();
		public static function get_word_base($word);
		public static function get_word_morph($word, $type = 'noun', $count = 0);
	};


	class language_morph implements iLanguageMorph {	//TODO Write interface
		private $lang;

		public function __construct() {}

		public static function get_word_base($word) {
			$conv = umiConversion::getInstance();
			return $conv->stemmerRu($word);
		}

		public static function get_word_morph($word, $type = 'noun', $count = 0) {}
	};


	interface iUmiDate {
		public function __construct($timeStamp = false);

		public function getFormattedDate($formatString = false);
		public function getCurrentTimeStamp();
		public function getDateTimeStamp();

		public function setDateByTimeStamp($timeStamp);
		public function setDateByString($dateString);

		public static function getTimeStamp($dateString);
	}


	/**
	* @desc Класс-обертка для внутреннего представления типа данных "Дата"
	*/
	class umiDate implements iUmiDate {
		public $timestamp;
		public $defaultFormatString = "Y-m-d H:i";
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
			return time();
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
				$formatString = $this->defaultFormatString;
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
			$timestamp  = strlen($dateString) ? self::getTimeStamp($dateString) : time();
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
	}


	interface iUmiFile {
		public function __construct($filePath);
		public function delete();

		public static function upload($variableGroupName, $variableName, $targetFolder);

		public function getSize();
		public function getExt();
		public function getFileName();
		public function getDirName();
		public function getModifyTime();
		public function getFilePath($webMode = false);

		public function getIsBroken();

		public function __toString();
		
		public static function getUnconflictPath($path);
		
		public function download($deleteAfterDownload = false);
	}


/**
	* Класс для работы с файлами в системе
*/
	class umiFile implements iUmiFile {
		protected	$filepath,
				$size, $ext, $name, $dirname, $modify_time,
				$is_broken = false;
		public static $mask = 0777;

		protected static $class_name = 'umiFile';

		/**
			* Конструктор
			* @param String $filepath путь до файла
		*/
		public function __construct($filepath) {
			$filepath = str_replace("//", "/", $filepath);
			$filepath = str_replace("\\\\", "\\", $filepath);
			
			if(!@is_file($filepath)) {
				$this->is_broken = true;
				return false;
			}

			$this->filepath = $filepath;
			$this->filepath = str_replace("\\", "/", $filepath);
			$this->loadInfo();
		}

		/**
			* Удалить файл из файловой системы
		*/
		public function delete() {
			if(is_writable($this->filepath)) {
				return unlink($this->filepath);
			} else {
				return false;
			}
		}
		
		
		/**
			* Послать HTTP заголовки для того, чтобы браузер начал скачивать файл
			* @param Boolean $deleteAfterDownload = false удалить файл после скачивания
		*/
		public function download($deleteAfterDownload = false) {
			ob_end_clean();
		
			header('HTTP/1.1 200 OK');
			header("Cache-Control: public, must-revalidate");
			header("Pragma: no-cache");
			header("Content-type: application/force-download");
			header("Content-Length: " . $this->getSize());
			header('Accept-Ranges: bytes');
			header("Content-Encoding: None");
			header("Vary:");
			header('Content-Transfer-Encoding: Binary');
			header("Content-Disposition: attachment; filename=" . $this->getFileName());
			
			readfile(realpath($this->getFilePath()));
			
			if($deleteAfterDownload) {
				$this->delete();
			}
			exit();
		}
		
		public static function manualUpload($name, $temp_path, $size, $target_folder) {
		    if(!$size || !$name || $name == ".htaccess" || !is_uploaded_file($temp_path)) return 1;
		    $aForbiddenTypes = array("php", "php3", "php4", "php5", "phtml");
		    if( in_array(substr($name, strrpos($name, '.')), $aForbiddenTypes) ) return 2;
		    
		    list(,, $extension) = array_values(pathinfo($name));
			$name = substr($name, 0, strlen($name) - strlen($extension));
			$name = translit::convert($name);
			$name .= "." . strtolower($extension);

			$new_path = $target_folder . "/" . $name;

			if($name == ".htaccess") {
				return 3;
			}

			$extension = strtolower($extension);

			if( in_array( $extension, $aForbiddenTypes ) ) {
				return 4;
			}
			
			if(is_uploaded_file($temp_path)) {
				$new_path = umiFile::getUnconflictPath($new_path);
				
				if(move_uploaded_file($temp_path, $new_path)) {
					chmod($new_path, self::$mask);

					$new_path = self::getRelPath($new_path);
					return new self::$class_name($new_path);
				} else {
					return 5;
				}
			} else {
				return 6;
			}
		}


		public static function upload($group_name, $var_name, $target_folder, $id = false) {
			global $_FILES;
			$files_array = &$_FILES;

			$target_folder_input = $target_folder;
			if(substr($target_folder_input, strlen($target_folder_input) - 1, 1) != "/") $target_folder_input .= "/";

			$target_folder = realpath($target_folder);

			if(!is_dir($target_folder)) {
				return false;
			}

			if(!is_writable($target_folder)) {
				return false;
			}

			if(!is_array($files_array)) {
				return false;
			}
			
			if(!isset($files_array[$group_name]) && isset($files_array['pics'])) {
				$files_array[$group_name] = $files_array['pics'];
				$group_name = "pics";
			}

			if(array_key_exists($group_name, $files_array)) {
				$file_info = $files_array[$group_name];
				
				if(isset($file_info['size'][$var_name])) {
					$id = false;
				}

				$size = ($id === false) ? 
                                    (isset($file_info['size'][$var_name])? $file_info['size'][$var_name] : 0)
                                        : 
                                    (isset($file_info['size'][$id][$var_name])? $file_info['size'][$id][$var_name] : 0);

				if($size == 0) {
					return false;
				} else {
					$temp_path = ($id === false) ? $file_info['tmp_name'][$var_name] : $file_info['tmp_name'][$id][$var_name];
					$name = ($id === false) ? $file_info['name'][$var_name] : $file_info['name'][$id][$var_name];	//TODO: make cyrilic to translit conversion

					$aForbiddenTypes = array("php", "php3", "php4", "php5", "phtml");

					if( in_array(substr($name, strrpos($name, '.')), $aForbiddenTypes) ) return false;

					list(,, $extension) = array_values(pathinfo($name));
					$name = substr($name, 0, strlen($name) - strlen($extension));
					$name = translit::convert($name);
					$name .= "." . strtolower($extension);

					$new_path = $target_folder . "/" . $name;

					if($name == ".htaccess") {
						return false;
					}

					$extension = strtolower($extension);

					if( in_array( $extension, $aForbiddenTypes ) ) {
						return false;
					}


					if(is_uploaded_file($temp_path)) {
						$new_path = umiFile::getUnconflictPath($new_path);
						if(move_uploaded_file($temp_path, $new_path)) {
							chmod($new_path, self::$mask);

							$new_path = self::getRelPath($new_path);
							return new self::$class_name($new_path);
						} else {
							return false;
						}
					} else {
						return false;
					}
				}
			} else {
				return false;
			}
		}
		
		// Ф-я распаковки zip-архива
		public static function upload_zip ($var_name, $file = "", $folder = "./images/cms/data/")  {
			
			if ($file == "") {
				$temp_path = $var_name['tmp_name'];
				$name = $var_name['name'];
				
				list(,, $extension) = array_values(pathinfo($name));
				$name = substr($name, 0, strlen($name) - strlen($extension));
				$name = translit::convert($name);
				$name .= "." . $extension;

				$new_path = $folder.$name;
				$new_zip_path = './'.$name;
				
				if ($var_name['size'] == 0) {
					return false;
				}
				
				if(is_uploaded_file($temp_path)) {
						
						$new_path = umiFile::getUnconflictPath($new_path);
						if(move_uploaded_file($temp_path, $new_zip_path)) {
							chmod($new_zip_path, self::$mask);
						} else {
							return false;
						}
				} else {
					return false;
				}
			
			} else {
				
				$file = "./".$file;
				
				if (!file_exists ($file) || !is_writable($file)) return "File not exists!";
				
				$path_parts = pathinfo ($file);
				
				if ($path_parts['extension'] != "zip") {
					return "It's not zip-file!";
				}
				
				$new_path = $file;
				$new_zip_path = $file;
			}
			
			$archive = new PclZip($new_zip_path);
		
			if (($list = $archive->extract(PCLZIP_OPT_PATH, $folder,
				PCLZIP_CB_PRE_EXTRACT, "callbackPreExtract",
				PCLZIP_CB_POST_EXTRACT, "callbackPostExtract",
				PCLZIP_OPT_REMOVE_ALL_PATH)) == 0 || !is_array ($list)) {
				
				// unlink zip
				if(is_writable($new_path)) {
					unlink($new_path);
				}
				throw new coreException ("Zip extracting error: ".$archive->errorInfo(true));

			} 
				
			// unlink zip
			if(is_writable($new_path)) {
				unlink($new_path);
			}

			return $list;

		}

		/**
			* Получить название файла
			* @return String название файла
		*/
		public function getFileName() {
			return $this->name;
		}

		/**
			* Получить путь директорию, в которой лежит файл
			* @return String адрес директории, в которой лежит файл относительно UNIX TIMESTAMP
		*/
		public function getDirName() {
			return $this->dirname;
		}

		/**
			* Получить время последней модификации файла
			* @return Integer время последней модификации файла в UNIX TIMESTAMP
		*/
		public function getModifyTime() {
			return $this->modify_time;
		}

		/**
			* Получить расширение файла
			* @return String расширение файла
		*/
		public function getExt() {
			return $this->ext;
		}

		/**
			* Получить размер файла
			* @return Integer размер файла в байтах
		*/
		public function getSize() {
			return $this->size;
		}

		/**
			* Получить путь до файла в файловой системе
			* @param Boolean $web_mode если true, то путь будет указан относительно DOCUMENT_ROOT'а
			* @return String путь до файла
		*/
		public function getFilePath($web_mode = false) {
			if($web_mode) {
				$sIncludePath = ini_get("include_path");
				if (substr($this->filepath, 0, strlen($sIncludePath)) === $sIncludePath) {
					return "/" . substr($this->filepath, strlen($sIncludePath));
				}
				return (substr($this->filepath, 0, 2) == "./") ? ("/" . substr($this->filepath, 2, strlen($this->filepath) - 2)) : $this->filepath;
			} else {
				return $this->filepath;
			}
		}

		private function loadInfo() {
			if(!is_file($this->filepath)) {
				$this->is_broken = true;
				return false;
			}

			if(!is_readable($this->filepath)) {
				$this->is_broken = true;
				return false;
			}
			
			$pathinfo = pathinfo($this->filepath);

			$this->modify_time = filemtime($this->filepath);
			$this->size = filesize($this->filepath);
			$this->dirname = $pathinfo['dirname'];
			$this->name = $pathinfo['basename'];
			$this->ext = strtolower(getArrayKey($pathinfo, 'extension'));

			if($this->ext == "php" || $this->ext == "php5" || $this->ext == "phtml") {
				$this->is_broken = true;
			}

			if($this->name == ".htaccess") {
				$this->is_broken = true;
			}
		}

		public function __toString() {
			$filepath = $this->getFilePath(true);
			return is_null($filepath) ? "" : $filepath;
		}

		/**
			* Узнать, все ли в порядке с файлом, на который ссылается объект umiFile
			* @return Boolean true, если нет ошибок
		*/
		public function getIsBroken() {
			return (bool) $this->is_broken;
		}

		
		public static function getUnconflictPath($new_path) {
			if(!file_exists($new_path)) {
				return $new_path;
			} else {
				$info = pathinfo($new_path);
				$dirname = $info['dirname'];
				$filename = $info['filename'];
				$ext = $info['extension'];

				for($i = 1; $i < 257; $i++) {
					$new_path = $dirname . "/" . $filename . $i . "." . $ext;
					if(!file_exists($new_path)) {
						return $new_path;
					}
				}
				throw new coreException("This is really hard to happen");
			}
		}


		protected static function getRelPath($path) {
			$cwd = realpath(getcwd());
			return "." . substr(realpath($path), strlen($cwd));
		}
	};
	
	
// Контроль извлекаемых из zip-архива файлов
function callbackPreExtract ($p_event, &$p_header) {
	
	$info = pathinfo($p_header['filename']);
	$basename = substr($info['basename'], 0, (strlen($info['basename']) - strlen($info['extension']))-1);
	$basename = translit::convert($basename);
	$p_header['filename'] = $info['dirname']."/".$basename.".".$info['extension'];
	
	$p_header['filename'] = umiFile::getUnconflictPath($p_header['filename']);
	
	return 1;

}

function callbackPostExtract ($p_event, &$p_header) {
	
	$info = pathinfo($p_header['stored_filename']);
	
	$allowedTypes = array ("jpg","jpeg","gif","bmp","png");
	
	if ( !in_array ($info['extension'], $allowedTypes)) {
		unlink ($p_header['filename']);
	}
	
	return 1;

}


	interface iUmiImageFile {
		public function getWidth();
		public function getHeight();
	}


/**
	* Класс для работы с файлами изображений, наследуется от класса umiFile
*/
	class umiImageFile extends umiFile implements iUmiImageFile {
		private static $aSupportedTypes = null;
		private static $useWatermark = false;

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
		private static function addWatermark ($filePath) {
		
			$regedit = regedit::getInstance ();
		
			$srcWaterImage = $regedit->getVal ("//settings/watermark/image");
			$scaleWaterImage = 80;//$regedit->getVal ("//settings/watermark/scale");
			$alphaWaterImage = $regedit->getVal ("//settings/watermark/alpha");
			$valignWaterImage = $regedit->getVal ("//settings/watermark/valign");
			$halignWaterImage = $regedit->getVal ("//settings/watermark/halign");
			
			if (!file_exists ($srcWaterImage)) {
				return false;
			}
			if (!$alphaWaterImage) {
				$alphaWaterImage = 70;
			}
			if (!$valignWaterImage) {
				$valignWaterImage = "bottom";
			}
			if (!$halignWaterImage) {
				$halignWaterImage = "right";
			}

			$waterImgParam = self::createImage ($srcWaterImage);
			$srcImgParam = self::createImage ($filePath);
			$imageFileInfo = pathinfo ($filePath);
			
			if (!$waterImgParam || !$srcImgParam) {
				return false;
			}

			$hscale = $waterImgParam["height"] / $srcImgParam["height"];
			$wscale = $waterImgParam["width"] / $srcImgParam["width"];

			if (($hscale > $scaleWaterImage/100) || ($wscale > $scaleWaterImage/100)) {
				$scale = ($scaleWaterImage / 100) / (($hscale > $wscale) ? $hscale : $wscale);
				$newheight = floor($waterImgParam["height"] * $scale);
				$newwidth = floor($waterImgParam["width"] * $scale);
			} else {
				$newheight = $waterImgParam["height"];
				$newwidth = $waterImgParam["width"];
			}

			$tmpImg = imagecreatetruecolor ($newwidth, $newheight);
			$whiteColor = imagecolorallocate ($tmpImg, 255, 255, 255);

			imagefilledrectangle ($tmpImg, 0, 0, $newwidth, $newheight, $whiteColor);

			imagecopyresized ($tmpImg, $waterImgParam["im"], 0, 0, 0, 0, $newwidth, $newheight, $waterImgParam["width"], $waterImgParam["height"]);
			imagedestroy ($waterImgParam["im"]);

			$waterImgParam = array (
				"im" => $tmpImg,
				"width" => $newwidth,
				"height" => $newheight
			);

			$tmpImg = imagecreatetruecolor ($srcImgParam["width"], $srcImgParam["height"]);
			$whiteColor = imagecolorallocate ($tmpImg, 255, 255, 255);

			imagecopy ($tmpImg, $srcImgParam["im"], 0,0,0,0, $srcImgParam["width"], $srcImgParam["height"]);

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
			
			imagecopymerge ($tmpImg, $waterImgParam["im"], $x_ins, $y_ins, 0, 0, $waterImgParam["width"], $waterImgParam["height"], $alphaWaterImage);

			switch ($imageFileInfo['extension']) {
				case "jpeg" :
				case "jpg"  :
				case "JPEG" :
				case "JPG"  : {
					imagejpeg ($tmpImg, $filePath, 90);
					break;
				}
				case "png" :
				case "PNG" : {
					imagepng ($tmpImg, $filePath);
				}
				case "gif" :
				case "GIF" : {
					imagegif ($tmpImg, $filePath);
					break;
				}
				case "bmp" :
				case "BMP" : { /* TODO */ }
			}

			imagedestroy ($srcImgParam["im"]);
			imagedestroy ($waterImgParam["im"]);
			imagedestroy ($tmpImg);
			
			return true;

		}
		
		/**
			* Создает и возвращает индентификатор изображения
			* @param string $imageFilePath путь до изображения
			* @return array массив: индентификатор (im), ширина (width), высота (height)
		*/
		private static function createImage ($imageFilePath) {
			
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
	}


	interface iLang {
		public function getTitle();
		public function setTitle($title);

		public function getPrefix();
		public function setPrefix($prefix);

		public function getIsDefault();
		public function setIsDefault($isDefault);
	}


	class lang extends umiEntinty implements iUmiEntinty, iLang {
		private $prefix, $is_default, $title;
		protected $store_type = "lang";

		protected function loadInfo($row = false) {
			if($row === false) {
                $oXMLLang = XMLFactory::getInstance()->getProxy("langs.xml")->getElement("/umi/langs/lang[@id=".$this->id."]");
                $this->prefix     = $oXMLLang->prefix;
                $this->title      = $oXMLLang->getValue();
                $this->is_default = (bool) $oXMLLang->is_default;				
                return true;
			}
			if(list($id, $prefix, $is_default, $title) = $row) {
				$this->prefix = $prefix;
				$this->title = $title;
				$this->is_default = (bool) $is_default;
				return true;
			} else {
				return false;
			}
		}


		public function getTitle() {
			return $this->title;
		}

		public function getPrefix() {
			return $this->prefix;
		}

		public function getIsDefault() {
			return $this->is_default;
		}


		public function setTitle($title) {
			$this->title = $title;
			$this->setIsUpdated();
		}

		public function setPrefix($prefix) {
			$this->prefix = $prefix;
			$this->setIsUpdated();
		}

		public function setIsDefault($is_default) {
			$this->is_default = (bool) $is_default;
			$this->setIsUpdated();
		}

		protected function save() {
			$title = $this->title;
			$prefix = $this->filterPrefix($this->prefix);
			$is_default = (int) $this->is_default;
			$oXMLLang = XMLFactory::getInstance()->getProxy("langs.xml")->getElement("/umi/langs/lang[@id=".$this->id."]");
            $oXMLLang->setValue($title);
            $oXMLLang->prefix     = $prefix;
            $oXMLLang->is_default = $is_default;            
		}
		
		protected function filterPrefix($prefix) {
			return preg_replace("/[^A-z0-9_\-]+/", "", $prefix);
		}
	}


	interface iLangsCollection {
		public function addLang($prefix, $title, $isDefault = false);
		public function delLang($langId);

		public function getDefaultLang();
		public function setDefault($langId);

		public function getLangId($prefix);
		public function getLang($langId);

		public function getList();

		public function getAssocArray();
	}


	class langsCollection extends singleton implements iSingleton, iLangsCollection {
		private $langs = Array(),
			$def_lang;

		protected function __construct() {
			$this->loadLangs();
		}


		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}

		private function loadLangs() {            
            $aLangList = XMLFactory::getInstance()->getProxy("langs.xml")->getNodeTree("/umi/langs", true);                        
            foreach($aLangList["langs"][0]["lang"] as $aLang) {
                $aAttrib   = $aLang["@attributes"];
                $lang_id   = $aAttrib["id"];
                $row       = array($aAttrib["id"], $aAttrib["prefix"], $aAttrib["is_default"], $aLang["@value"]);			
				if($lang = cacheFrontend::getInstance()->load($lang_id, "lang")) {
				} else {
					try {
						$lang = new lang($lang_id, $row);
					} catch (privateException $e) {
						continue;
					}

					cacheFrontend::getInstance()->save($lang, "lang");
				}
				
				$this->langs[$lang_id] = $lang;
				if($this->langs[$lang_id]->getIsDefault()) {
					$this->def_lang = $this->langs[$lang_id];
				}
			}
		}

		public function getLangId($prefix) {
			foreach($this->langs as $lang) {
				if($lang->getPrefix() == $prefix) {
					return $lang->getId();
				}
			}
			return false;
		}

		public function addLang($prefix, $title, $is_default = false) {
			if($lang_id = $this->getLangId($prefix)) {
				return $lang_id;
			}           
            $oProxy  = XMLFactory::getInstance()->getProxy("langs.xml");
            $lang_id = (int)$oProxy->getAttributeValue("/umi/langs/lang[last()]", "id") + 1;            
            $oXMLLang = $oProxy->addElement("/umi/langs", "lang", "");
            $oXMLLang->id = $lang_id;
            $lang = new lang($lang_id);
            $lang->setPrefix($prefix);
            $lang->setTitle($title);
            $lang->setIsDefault($is_default);
            $lang->commit();
            $this->langs[$lang_id] = &$lang;
            return $lang_id;			
		}

		public function delLang($lang_id) {
			$lang_id = (int) $lang_id;
			if(!$this->isExists($lang_id)) return false;            
            XMLFactory::getInstance()->getProxy("langs.xml")->removeElement("/umi/langs/lang[@id=".$lang_id."]");
            unset($this->langs[$lang_id]);
            return true;
		}

		public function getLang($lang_id) {
			$lang_id = (int) $lang_id;
			return ($this->isExists($lang_id)) ? $this->langs[$lang_id] : false;
		}

		public function isExists($lang_id) {
			return (bool) array_key_exists($lang_id, $this->langs);
		}

		public function getList() {
			return (Array) $this->langs;
		}

		public function setDefault($lang_id) {
			if(!$this->isExists($lang_id)) {
				return false;
			}

			if($this->def_lang) {
				$this->def_lang->setIsDefault(false);
				$this->def_lang->commit();
			}

			$this->def_lang = $this->getLang($lang_id);
			$this->def_lang->setIsDefault(true);
			$this->def_lang->commit();
		}

		public function getDefaultLang() {
			return ($this->def_lang) ? $this->def_lang : false;
		}


		public function getAssocArray() {
			$res = Array();

			foreach($this->langs as $lang) {
				$res[$lang->getId()] = $lang->getTitle();
			}

			return $res;
		}
	}


	interface iDomainMirrow {
		public function getHost();
		public function setHost($host);
	}


	class domainMirrow extends umiEntinty implements iUmiEntinty, iDomainMirrow {
		private $host;

		public function setHost($host) {
			$this->host = trim($host);
			$this->setIsUpdated();
		}
                 
		public function getHost() {
			return $this->host;
		}

		protected function loadInfo($row = false) {
			if($row === false) {
                $oXMLMirror = XMLFactory::getInstance()->getProxy("domains.xml")->getElement("/umi/mirrors/mirror[@id=".$this->id."]");								
                $this->host = $oXMLMirror->getValue();
                return true;
			}
			if(list($id, $host) = $row) {
				$this->host = $host;
				return true;
			} else {
				return false;
			}
		}

		protected function save() {
			$host = $this->host;
            XMLFactory::getInstance()->getProxy("domains.xml")->setElementValue("/umi/mirrors/mirror[@id=".$this->id."]", $host);			
		}
	}


	interface iDomain {
//REM: Inheritance from iDomainMirrow interface for a while
//		public function getHost();
//		public function setHost(string $host);

		public function getIsDefault();
		public function setIsDefault($isDefault);

		public function addMirrow($mirrowHost);
		public function delMirrow($mirrowId);

		public function getMirrowId($mirrowHost);
		public function getMirrow($mirrowId);

		public function getMirrowsList();
		public function delAllMirrows();


		public function isMirrowExists($mirrowId);

		public function getDefaultLangId();
		public function setDefaultLangId($langId);
	}


	class domain extends umiEntinty implements iUmiEntinty, iDomainMirrow, iDomain {
		private	$host, $default_lang_id, $mirrows = Array();
		protected $store_type = "domain";

		protected function loadInfo($row = false) {			
            $oXMLDomain = XMLFactory::getInstance()->getProxy("domains.xml")->getElement("/umi/domains/domain[@id=".$this->id."]");
            $this->host            = $oXMLDomain->getValue();
            $this->is_default      = (bool) $oXMLDomain->is_default;
            $this->default_lang_id = (int)  $oXMLDomain->default_lang_id;
            return $this->loadMirrows();
		}

		public function getHost() {
			return $this->host;
		}

		public function getIsDefault() {
			return $this->is_default;
		}

		public function setHost($host) {
			$this->host = trim($host);
			$this->setIsUpdated();
		}

		public function setIsDefault($is_default) {
			$this->is_default = (bool) $is_default;
			$this->setIsUpdated();
		}


		public function getDefaultLangId() {
			return $this->default_lang_id;
		}

		public function setDefaultLangId($lang_id) {
			if(langsCollection::getInstance()->isExists($lang_id)) {
				$this->default_lang_id = $lang_id;
				$this->setIsUpdated();

				return true;
			} else {
				trigger_error("Language #{$lang_id} doesn't exists", E_USER_WARNING);
				return false;
			}
		}


		public function addMirrow($mirrow_host) {
			if($mirrow_id = $this->getMirrowId($mirrow_host)) {
				return $mirrow_id;
			} else {
                $oProxy     = XMLFactory::getInstance()->getProxy("domains.xml");
                $iMirrowId  = (int)$oProxy->getAttributeValue("/umi/mirrors/mirror[last()]", "id") + 1;
                $oNewMirror = $oProxy->addElement("/umi/mirrors", "mirror", "");
                $oNewMirror->id  = $iMirrowId;
                $oNewMirror->rel = $this->id;
                $mirrow = new domainMirrow($iMirrowId);
				$mirrow->setHost($mirrow_host);
				$mirrow->commit();
				$this->mirrows[$iMirrowId] = $mirrow;
				return $mirrow_id;
			}
		}

		public function delMirrow($mirrow_id) {
			if($this->isMirrowExists($mirrow_id)) {
                XMLFactory::getInstance()->getProxy("domains.xml")->removeElement("/umi/mirrors/mirror[@id=".$mirrow_id."]");
                unset($this->mirrows[$mirrow_id]);
                return true;				
			} else {
				return false;
			}
		}

		public function delAllMirrows() {
            XMLFactory::getInstance()->getProxy("domains.xml")->removeElement("/umi/mirrors/mirror[@rel=".$this->id."]");			
		}

		public function getMirrowId($mirrow_host) {
			foreach($this->mirrows as $mirrow) {
				if($mirrow->getHost() == $mirrow_host) {
					return $mirrow->getId();
				}
			}
			return false;
		}

		public function getMirrow($mirrow_id) {
			if($this->isMirrowExists($mirrow_id)) {
				return $this->mirrows[$mirrow_id];
			} else {
				return false;
			}
		}

		public function isMirrowExists($mirrow_id) {
			return (bool) array_key_exists($mirrow_id, $this->mirrows);
		}

		public function getMirrowsList() {
			return $this->mirrows;
		}

		private function loadMirrows() {			
            $aMirrors = XMLFactory::getInstance()->getProxy("domains.xml")->getNodeTree("/umi/mirrors/mirror[@rel=".$this->id."]", false, true);
            if(is_array($aMirrors)&&!empty($aMirrors))
            foreach($aMirrors["mirror"] as $Mirror) {
                try {
                    $aRow = array($Mirror['@attributes']['id'], $Mirror['@value']);
                    $this->mirrows[$Mirror['@attributes']['id']] = new domainMirrow($aRow[0], $aRow);
                } catch(privateException $e) {
                    continue;
                }                
            }
			return true;
		}

		protected function save() {
			$host = $this->host;
			$is_default = (int) $this->is_default;
			$default_lang_id = (int) $this->default_lang_id;            
            $oXMLDomain = XMLFactory::getInstance()->getProxy("domains.xml")->getElement("/umi/domains/domain[@id=".$this->id."]");
            $oXMLDomain->setValue($host);
            $oXMLDomain->is_default      = $is_default;
            $oXMLDomain->default_lang_id =  $default_lang_id;			
		}
	}


	interface iDomainsCollection {
		public function addDomain($host, $defaultLangId, $isDefault = false);
		public function delDomain($domainId);
		public function getDomain($domainId);

		public function getDefaultDomain();
		public function setDefaultDomain($domainId);

		public function getDomainId($host, $useMirrows = true);

		public function getList();
	}


	class domainsCollection extends singleton implements iSingleton, iDomainsCollection {
		private $domains = Array(), $def_domain;

		protected function __construct() {
			$this->loadDomains();
		}

		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}

		public function addDomain($host, $lang_id, $is_default = false) {
			if($domain_id = $this->getDomainId($host)) {
				return $domain_id;
			} else {
                $oProxy     = XMLFactory::getInstance()->getProxy("domains.xml");
                $domain_id  = (int)$oProxy->getAttributeValue("/umi/domains/domain[last()]", "id") + 1;
                $oXMLDomain = $oProxy->addElement("/umi/domains", "domain", "");
                $oXMLDomain->id = $domain_id;			

				$this->domains[$domain_id] = $domain = new domain($domain_id);
				$domain->setHost($host);
				$domain->setIsDefault($is_default);
				$domain->setDefaultLangId($lang_id);
				if($is_default) $this->setDefaultDomain($domain_id);
				$domain->commit();

				return $domain_id;
			}
		}

		public function setDefaultDomain($domain_id) {
			if($this->isExists($domain_id)) {
                XMLFactory::getInstance()->getProxy("domains.xml")->setAttributeValue("/umi/domains/domain[@is_default=1]", "is_default", "0");				    
                if($def_domain = $this->getDefaultDomain()) {
                    $def_domain->setIsDefault(false);
                    $def_domain->commit();
                }
                $this->def_domain = $domain_id;
                $this->def_domain->setIsDefault(true);
                $this->def_domain->commit();
			} else {
				return false;
			}
		}

		public function delDomain($domain_id) {
			if($this->isExists($domain_id)) {
				$domain = $this->getDomain($domain_id);
				$domain->delAllMirrows();
				if($domain->getIsDefault()) {
					$this->def_domain = false;
				}
				unset($domain);
				unset($this->domains[$domain_id]);                
                XMLFactory::getInstance()->getProxy("hierarchy.xml")->removeElement("/umi/elements/element[@domain_id=".$domain_id."]");
                XMLFactory::getInstance()->getProxy("domains.xml")->removeElement("/umi/domains/domain[@id=".$domain_id."]");
				return true;
			} else {
				trigger_error("Domain #{$domain_id} doesn't exists.", E_USER_WARNING);
				return false;
			}
		}

		public function getDomain($domain_id) {
			if($this->isExists($domain_id)) {
				return $this->domains[$domain_id];
			} else {
				return false;
			}
		}

		public function getDefaultDomain() {
			return ($this->def_domain) ? $this->def_domain : false;
		}

		public function getList() {
			return $this->domains;
		}

		public function isExists($domain_id) {
			return (bool) array_key_exists($domain_id, $this->domains);
		}

		public function getDomainId($host, $use_mirrows = true) {
			foreach($this->domains as $domain) {
				if($domain->getHost() == $host) {
					return $domain->getId();
				} else {
					if($use_mirrows) {
						$mirrows = $domain->getMirrowsList();
						foreach($mirrows as $domainMirrow) {
							if($domainMirrow->getHost() == $host) {
								return $domain->getId();
							}
						}
					}
				}
			}
			return false;
		}

		private function loadDomains() {
            $aDomainList = XMLFactory::getInstance()->getProxy("domains.xml")->getNodeTree("/umi/domains", true);						
            foreach($aDomainList["domains"][0]["domain"] as $aDomain) {
                $aAttrib   = $aDomain["@attributes"];
                $domain_id = $aAttrib["id"];
                $row       = array($aAttrib["id"], $aDomain["@value"], $aAttrib["is_default"], $aAttrib["default_lang_id"]);
				if($domain = cacheFrontend::getInstance()->load($domain_id, "domain")) {
				} else {
					try {
						$domain = new domain($domain_id, $row);
					} catch(privateException $e) {
						continue;
					}

					cacheFrontend::getInstance()->save($domain, "domain");
				}
				$this->domains[$domain_id] = $domain;

				if($domain->getIsDefault()) {
					$this->def_domain = $domain;
				}
			}

			return true;
		}
	}


	interface iTemplate {
		public function getFilename();
		public function setFilename($filename);

		public function getTitle();
		public function setTitle($title);

		public function getDomainId();
		public function setDomainId($domainId);

		public function getLangId();
		public function setLangId($langId);

		public function getIsDefault();
		public function setIsDefault($isDefault);
		
		public function getUsedPages();
		public function setUsedPages($elementIdArray);
	}


	class template extends umiEntinty implements iUmiEntinty, iTemplate {
		private $filename, $title, $domain_id, $lang_id, $is_default;
		protected $store_type = "template";


		public function getFilename() {
			return $this->filename;
		}

		public function getTitle() {
			return $this->title;
		}

		public function getDomainId() {
			return $this->domain_id;
		}

		public function getLangId() {
			return $this->lang_id;
		}

		public function getIsDefault() {
			return $this->is_default;
		}

		public function setFilename($filename) {
			$this->filename = $filename;
			$this->setIsUpdated();
		}

		public function setTitle($title) {
			$this->title = $title;
			$this->setIsUpdated();
		}

		public function setDomainId($domain_id) {
			$domains = domainsCollection::getInstance();
			if($domains->isExists($domain_id)) {
				$this->domain_id = (int) $domain_id;
				$this->setIsUpdated();

				return true;
			} else {
				return false;
			}
		}

		public function setLangId($lang_id) {
			$langs = langsCollection::getInstance();
			if($langs->isExists($lang_id)) {
				$this->lang_id = (int) $lang_id;
				$this->setIsUpdated();

				return true;
			} else {
				return false;
			}
		}

		public function setIsDefault($is_default) {
			$this->is_default = (bool) $is_default;
			$this->setIsUpdated();
		}
		
		
		public function getUsedPages() {			            
            $aHE = XMLFactory::getInstance()->getProxy('hierarchy.xml')->getNodeTree('/umi/elements/element[@tpl_id='.$this->id.' and @is_deleted=0 and @domain_id='.$this->domain_id.']');
            $oProxy = XMLFactory::getInstance()->getProxy('objects.xml');			
            $res = Array();
			foreach($aHE['element'] as $Element) {
				$res[] = Array($Element['@attributes']['id'], 
                                $oProxy->getAttributeValue('/umi/objects/object[@id='.$Element['@attributes']['obj_id'].']', 'name'));
			}
			return $res;
		}
		
		
		public function setUsedPages($pages) {
			$default_tpl_id = templatesCollection::getInstance()->getDefaultTemplate($this->domain_id, $this->lang_id)->getId();
            
            $oProxy = XMLFactory::getInstance()->getProxy('hierarchy.xml'); 
            $oProxy->setAttributeValue('/umi/elements/element[@tpl_id='.$this->id.' and @is_deleted=0 and @domain_id='.$this->domain_id.']',
                                                                                    'tpl_id', $default_tpl_id);            

			if(!is_array($pages)) {
				return false;
			}

            if(is_array($pages)&&!empty($pages)) {
                $sXPath = '/umi/elements/element[';
                $i      = 0;
                foreach($pages as $page)
                    $sXPath = (($i++)?' or ':'').'@id='.$page;
                $sXPath = ']';
                $oProxy->setAttributeValue($sXPath, 'tpl_id', $this->id);
            }
		}


		protected function loadInfo($row = false) {
			if($row === false) {
                $Template = XMLFactory::getInstance()->getProxy('templates.xml')->getElement('/umi/templates/tempalte['.$this->id.']');
                $this->filename     = $Template->filename;
                $this->title        = $Template->getValue();
                $this->domain_id    = (int)  $Template->domain_id;
                $this->lang_id      = (int)  $Template->lang_id;
                $this->is_default   = (bool) $Template->is_default;
                return true;				
			}

			if(list($id, $filename, $title, $domain_id, $lang_id, $is_default) = $row) {
				$this->filename = $filename;
				$this->title = $title;
				$this->domain_id = (int) $domain_id;
				$this->lang_id = (int) $lang_id;
				$this->is_default = (bool) $is_default;

				return true;
			} else {
				return false;
			}
		}

		protected function save() { 
			$filename = $this->filename;
			$title = $this->title;
			$domain_id = (int) $this->domain_id;
			$lang_id =  (int) $this->lang_id;
			$is_default = (int) $this->is_default;            
            $Template = XMLFactory::getInstance()->getProxy('templates.xml')->getElement('/umi/templates/template[@id='.$this->id.']');
            $Template->setValue($title);
            $Template->filename   = $filename;
            $Template->domain_id  = $domain_id;
            $Template->lang_id    = $lang_id;
            $Template->is_default = $is_default;
			return true;
		}
	}


	interface iTemplatesCollection {
		public function addTemplate($filename, $title, $domainId = false, $langId = false, $isDefault = false);
		public function delTemplate($templateId);


		public function getDefaultTemplate($domain_id = false, $lang_id = false);
		public function setDefaultTemplate($template_id, $domain_id = false, $lang_id = false);

		public function getTemplatesList($domainId, $langId);

		public function getTemplate($templateId);
	}


    class templatesCollection extends singleton implements iSingleton, iTemplatesCollection {
        private $templates = Array(), $def_template;

        protected function __construct() {
            $this->loadTemplates();
        }

        public static function getInstance($c = NULL) {
            return parent::getInstance(__CLASS__);
        }


        public function addTemplate($filename, $title, $domain_id = false, $lang_id = false, $is_default = false) {
            $domains = domainsCollection::getInstance();
            $langs = langsCollection::getInstance();

            if(!$domains->isExists($domain_id)) {
                if($domains->getDefaultDomain()) {
                    $domain_id = $domains->getDefaultDomain()->getId();
                } else {
                    return false;
                }
            }

            if(!$langs->isExists($lang_id)) {
                if($langs->getDefaultLang()) {
                    $lang_id = $langs->getDefaultLang()->getId();
                } else {
                    return false;
                }
            }           
            $oProxy      = XMLFactory::getInstance()->getProxy("templates.xml");
            $template_id = (int)$oProxy->getAttributeValue("/umi/templates/template[last()]", "id") + 1;            
            $oXMLTPL     = $oProxy->addElement("/umi/templates", "template", "");
            $oXMLTPL->id = $template_id;

            $template = new template($template_id);
            $template->setFilename($filename);
            $template->setTitle($title);
            $template->setDomainId($domain_id);
            $template->setLangId($lang_id);
            $template->setIsDefault($is_default);

            if($is_default) {
                $this->setDefaultTemplate($template_id);
            }
            $template->commit();


            $this->templates[$template_id] = $template;

            return $template_id;
        }

        public function setDefaultTemplate($template_id, $domain_id = false, $lang_id = false) {
            if($domain_id == false) $domain_id = domainsCollection::getInstance()->getDefaultDomain()->getId();    
            if($lang_id ==false) $lang_id = cmsController::getInstance()->getCurrentLang()->getId();
            
            // $sql = "UPDATE cms3_templates SET is_default = 0 WHERE domain_id = {$domain_id} AND lang_id = {$lang_id}";
            // $sql = "UPDATE cms3_templates SET is_default = 1 WHERE id = {$templaet_id} AND domain_id = {$domain_id} AND lang_id = {$lang_id}";
            
            if(!$this->isExists($template_id)) {
                return false;
            }
            
            $templates = $this->getTemplatesList($domain_id,$lang_id);
            foreach ($templates as $template) {
                if($template_id == $template->getId()) {
                    $template->setIsDefault(true);                    
                }
                else {
                    $template->setIsDefault(false);
                }
                $template->commit();
            }
            return true;
// ������� ������� ����
            if(!($template = $this->getTemplate($templateId))) {
                return false;
            }

            if($this->def_template) {
                $this->def_template->setIsDefault(false);
                $this->def_template->commit();
            }

            $this->def_template = $template;
            $this->def_template->setIsDefault(true);
            $this->def_template->commit();

            //return true;
        }

        public function delTemplate($template_id) {
            if($this->isExists($template_id)) {
                if($this->templates[$template_id]->getIsDefault()) {
                    unset($this->def_template);
                }
                unset($this->templates[$template_id]);
                $o_deftpl = $this->getDefaultTemplate();
                if (!$o_deftpl || $o_deftpl->getId() == $template_id) return false;
                XMLFactory::getInstance()->getProxy('hierarchy.xml')->setAttributeValue('/umi/elementss/element[@tpl_id='.$template_id.']', 'tpl_id', $o_deftpl->getId());
                XMLFactory::getInstance()->getProxy('templates.xml')->removeElement('/umi/templates/template[@id='.$template_id.']');                
                return true;

            } else return false;
        }

        public function getTemplatesList($domain_id, $lang_id) {
            $res = Array();

            foreach($this->templates as $template) {
                if($template->getDomainId() == $domain_id && $template->getLangId() == $lang_id) {
                    $res[] = $template;
                }
            }

            return $res;
        }

        public function getDefaultTemplate($domain_id = false, $lang_id = false) {
            if($domain_id == false) $domain_id = cmsController::getInstance()->getCurrentDomain()->getId();    
            if($lang_id ==false) $lang_id = cmsController::getInstance()->getCurrentLang()->getId();

            $templates = $this->getTemplatesList($domain_id, $lang_id);
            foreach($templates as $template) {
                if($template->getIsDefault() == true) {
                    return $template;
                }
            }
            return false;
        }

        public function getTemplate($template_id) {
            return ($this->isExists($template_id)) ? $this->templates[$template_id] : false;
        }

        public function isExists($template_id) {
            return (bool) array_key_exists($template_id, $this->templates);
        }


        private function loadTemplates() {            
            $aTemplates = XMLFactory::getInstance()->getProxy('templates.xml')->getNodeTree('/umi/templates');            
            foreach($aTemplates['templates'][0]['template'] as $Template) {
                $attr        = $Template['@attributes'];
                $template_id = $attr['id'];
                $row         = array($attr['id'], $attr['filename'], $Template['@value'], $attr['domain_id'], $attr['lang_id'], $attr['is_default']);
                if($template = cacheFrontend::getInstance()->load($template_id, "template")) {
                } else {
                    try {
                        $template = new template($template_id, $row);
                    } catch (privateException $e) {
                        continue;
                    }
                    cacheFrontend::getInstance()->save($template, "template");
                }
                $this->templates[$template_id] = $template;

                if($template->getIsDefault()) {
                    $this->def_template = $template;
                }
            }
            return true;
        }
    }


	interface iUmiHierarchyType {
		public function getName();
		public function setName($name);

		public function getTitle();
		public function setTitle($title);

		public function getExt();
		public function setExt($ext);
	}


	class umiHierarchyType extends umiEntinty implements iUmiEntinty, iUmiHierarchyType {
		private $name, $title, $ext;
		protected $store_type = "element_type";

		public function getName() {
			return $this->name;
		}

		public function getTitle() {
			return $this->translateLabel($this->title);
		}

		public function getExt() {
			return $this->ext;
		}

		public function setName($name) {
			$this->name = $name;
			$this->setIsUpdated();
		}

		public function setTitle($title) {
            $title = $this->translateI18n($title, "hierarchy-type-");
			$this->title = $title;
			$this->setIsUpdated();
		}

		public function setExt($ext) {
			$this->ext = $ext;
			$this->setIsUpdated();
		}


		protected function loadInfo($row = false) {
			if($row === false) {
                $oXMLType    = XMLFactory::getInstance()->getProxy("hierarchy.xml")->getElement("/umi/types/type[@id=".$this->id."]");
                $this->name  = $oXMLType->name;
                $this->title = $oXMLType->getValue();
                $this->ext   = $oXMLType->ext;
                return true;
			}

			if(list($id, $name, $title, $ext) = $row) {
				$this->name = $name;
				$this->title = $title;
				$this->ext = $ext;

				return true;
			} else {
				return false;
			}
		}

		protected function save() {
			$name = $this->name;
			$title = $this->title;
			$ext = $this->ext;            
            $oXMLType       = XMLFactory::getInstance()->getProxy("hierarchy.xml")->getElement("/umi/types/type[@id=".$this->id."]");
            $oXMLType->name = $name;
            $oXMLType->ext  = $ext;
            $oXMLType->setValue($title);
			return true;
		}
	}


	interface iUmiHierarchyTypesCollection {
		public function addType($name, $title, $ext = "");
		public function getType($typeId);
		public function delType($typeId);
		public function getTypeByName($typeName, $extName = false);

		public function getTypesList();
	}


	class umiHierarchyTypesCollection extends singleton implements iSingleton, iUmiHierarchyTypesCollection {
		private $types;

		protected function __construct() {
			$this->loadTypes();
		}


		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}

		public function getType($type_id) {
			if($this->isExists($type_id)) {
				return $this->types[$type_id];
			} else {
				return false;
			}
		}


		public function getTypeByName($name, $ext = false) {
			foreach($this->types as $type) {
				if($type->getName() == $name && !$ext) return $type;
				if($type->getName() == $name && $type->getExt() == $ext && $ext) return $type;
			}
			return false;
		}


		public function addType($name, $title, $ext = "") {
			if($hierarchy_type  = $this->getTypeByName($name, $ext)) {
				$hierarchy_type->setTitle($title);
				return $hierarchy_type->getId();
			}
            
            $oProxy  = XMLFactory::getInstance()->getProxy("hierarchy.xml");
            $type_id = (int)$oProxy->getAttributeValue("/umi/types/type[last()]", "id") + 1;            
            $oXMLType = $oProxy->addElement("/umi/types", "type", "");
            $oXMLType->id = $type_id;			

			$type = new umiHierarchyType($type_id);
			$type->setName($name);
			$type->setTitle($title);
			$type->setExt($ext);
			$type->commit();

			$this->types[$type_id] = $type;


			return $type_id;
		}


		public function delType($type_id) {
			if($this->isExists($type_id)) {
				unset($this->types[$type_id]);
				$type_id = (int) $type_id;
                XMLFactory::getInstance()->getProxy('hierarchy.xml')->removeElement('/umi/types/type[@id='.$type_id.']');
				return true;
			} else {
				return false;
			}
		}


		public function isExists($type_id) {
			return (bool) array_key_exists($type_id, $this->types);
		}


		private function loadTypes() {           
            $aList = XMLFactory::getInstance()->getProxy('hierarchy.xml')->getNodeTree('/umi/types');

			
            foreach($aList['types'][0]['type'] as $aType) {
                $id  = $aType['@attributes']['id'];
                $row = array($aType['@attributes']['id'], $aType['@attributes']['name'], $aType['@value'], $aType['@attributes']['ext']);
				if($type = cacheFrontend::getInstance()->load($id, "element_type")) {
				} else {
					try {
						$type = new umiHierarchyType($id, $row);
					} catch (privateException $e) {
						continue;
					}

					cacheFrontend::getInstance()->save($type, "element_type");
				}
				$this->types[$id] = $type;
			}
			return true;
		}

		public function getTypesList() {
			return $this->types;
		}
	}


	interface iUmiHierarchyElement {
		public function getIsDeleted();
		public function setIsDeleted($isDeleted = false);

		public function getIsActive();
		public function setIsActive($isActive = true);

		public function getIsVisible();
		public function setIsVisible($isVisible = true);

		public function getTypeId();
		public function setTypeId($typeId);

		public function getLangId();
		public function setLangId($langId);

		public function getTplId();
		public function setTplId($tplId);

		public function getDomainId();
		public function setDomainId($domainId);

		public function getUpdateTime();
		public function setUpdateTime($timeStamp = 0);

		public function getOrd();
		public function setOrd($ord);

		public function getRel();
		public function setRel($rel_id);

		public function getObject();
		public function setObject(umiObject $object);

		public function setAltName($altName, $autoConvert = true);
		public function getAltName();

		public function setIsDefault($isDefault = true);
		public function getIsDefault();

		public function getParentId();

		public function getValue($propName);
		public function setValue($propName, $propValue);

		public function getFieldId($FieldName);

		public function getName();
		public function setName($name);
		
		public function getObjectTypeId();
		
		public function getHierarchyType();
		
		public function getObjectId();
		
		
		public function getModule();
		public function getMethod();
	}


	class umiHierarchyElement extends umiEntinty implements iUmiEntinty, iUmiHierarchyElement {
		private    $rel, $alt_name, $ord, $object_id,
			$type_id, $domain_id, $lang_id, $tpl_id,
			$is_deleted = false, $is_active = true, $is_visible = true, $is_default = false, $name,
			$update_time,
			$object,
			$is_broken = false;

		protected $store_type = "element";

		public static function filterInputString($string) {
			return $string;
		}

		public function getIsDeleted() {
			return $this->is_deleted;
		}

		public function getIsActive() {
			return $this->is_active;
		}

		public function getIsVisible() {
			return $this->is_visible;
		}

		public function getLangId() {
			return $this->lang_id;
		}

		public function getDomainId() {
			return $this->domain_id;
		}

		public function getTplId() {
			return $this->tpl_id;
		}

		public function getTypeId() {
			return $this->type_id;
		}

		public function getUpdateTime() {
			return $this->update_time;
		}

		public function getOrd() {
			return $this->ord;
		}

		public function getRel() {
			return $this->rel;
		}

		public function getAltName() {
			return $this->alt_name;
		}

		public function getIsDefault() {
			return $this->is_default;
		}


		public function getObject() {
			if($this->object) {
				return $this->object;
			} else {
				$this->object = umiObjectsCollection::getInstance()->getObject($this->object_id);
				return $this->object;
			}
		}

		public function getParentId() {
			return $this->rel;
		}

		public function getName() {
			return $this->translateLabel($this->name);     //read-only
		}


		public function getValue($prop_name) {
			return $this->getObject()->getValue($prop_name);
		}

		public function setValue($prop_name, $prop_value) {
			$res = $this->getObject()->setValue($prop_name, $prop_value);
			$this->setIsUpdated(true);
			return $res;
		}



		public function setIsVisible($is_visible = true) {
			if ($this->is_visible !== ((bool)$is_visible)) {
				$this->is_visible = (bool) $is_visible;
				$this->setIsUpdated();
			}
		}

		public function setIsActive($is_active = true) {
			if ($this->is_active !== ((bool)$is_active)) {
				$this->is_active = (bool) $is_active;
				$this->setIsUpdated();
			}
		}

		public function setIsDeleted($is_deleted = false) {
			if ($this->is_deleted !== ((bool)$is_deleted)) {
				$this->is_deleted = (bool) $is_deleted;
				$this->setIsUpdated();
			}
		}

		public function setTypeId($type_id) {
			if ($this->type_id !== ((int)$type_id)) {
				$this->type_id = (int) $type_id;
				$this->setIsUpdated();
			}
		}

		public function setLangId($lang_id) {
			if ($this->lang_id !== ((int)$lang_id)) {
				$this->lang_id = (int) $lang_id;
				$this->setIsUpdated();
			}
		}

		public function setTplId($tpl_id) {
			if ($this->tpl_id !== ((int)$tpl_id)) {
				$this->tpl_id = (int) $tpl_id;
				$this->setIsUpdated();
			}
		}

		public function setDomainId($domain_id) {
			$childs = umiHierarchy::getInstance()->getChilds($this->id, true, true);

			foreach($childs as $child_id => $nl) {
				$child = umiHierarchy::getInstance()->getElement($child_id);
				$child->setDomainId($domain_id);
				umiHierarchy::getInstance()->unloadElement($child_id);
				unset($child);
			}

			if ($this->domain_id !== ((int)$domain_id)) {
				$this->domain_id = (int) $domain_id;
				$this->setIsUpdated();
			}
		}

		public function setUpdateTime($update_time = 0) {
			if($update_time == 0) {
				$update_time = umiHierarchy::getTimeStamp();
			}
			if ($this->update_time !== ((int)$update_time)) {
				$this->update_time = (int) $update_time;
				$this->setIsUpdated();
			}
		}

		public function setOrd($ord) {
			if ($this->ord !== ((int)$ord)) {
				$this->ord = (int) $ord;
				$this->setIsUpdated();
			}
		}

		public function setRel($rel) {
			if ($this->rel !== ((int)$rel)) {
				$this->rel = (int) $rel;
				$this->setIsUpdated();
			}
		}

		public function setObject(umiObject $object, $bNeedSetUpdated = true) {
			$this->object = $object;
			$this->object_id = $object->getId();
			if ($bNeedSetUpdated) $this->setIsUpdated();
		}

		public function setAltName($alt_name, $auto_convert = true) {
			if (!strlen($alt_name)) $alt_name = $this->getName();
			$alt_name = $this->getRightAltName($alt_name);
			
			$sPrevAltname = $this->alt_name;

			$this->alt_name = $this->getRightAltName(umiObjectProperty::filterInputString($alt_name));
			if(!$this->alt_name) {
				$this->alt_name = $alt_name;
			}

			$sNewAltname = $this->alt_name;
			if ($sNewAltname !== $sPrevAltname) $this->setIsUpdated();
		}

		private function getRightAltName($alt_name) {
			if(!strlen($alt_name)) $alt_name = '1';

			$alt_name = translit::convert($alt_name);

			$exists_alt_names =  array();
			if($this->getRel() == 0) {
				$modules_keys = regedit::getInstance()->getList("//modules");
				foreach($modules_keys as $module_name) {
					if($alt_name == $module_name[0]) { 
						$alt_name .= '1';
						break;
					}
				}
			}

			$alt_digit = "";
			$alt_string = $alt_name;
			if (preg_match  ("/^([a-z0-9_.-]*\D)(\d*)$/", $alt_name, $regs)) {
				$alt_digit = $regs[2];
				$alt_string = $regs[1];
			}
			
			$lang_id = $this->getLangId();
			$domain_id = $this->getDomainId();
			
			$AltNames = XMLFactory::getInstance()->getProxy('hierarchy.xml')->getNodeTree('/umi/elements/element[@rel='.$this->getRel().' and not(@id='.$this->getId().')'.
																						  ' and @is_deleted=0 and @lang_id='.$lang_id.' and @domain_id='.$domain_id.' and starts-with(text(), "'.$alt_string.'")]');
			if(!empty($AltNames))
				foreach($AltNames['element'] as $an)
					$exists_alt_names[] = $an['@value'];            
					
			
			if(!empty($exists_alt_names) and in_array($alt_name,$exists_alt_names)){

			foreach($exists_alt_names as $next_alt_name){
					preg_match  ("/(\D*)(\d*)/", $next_alt_name, $regs);
					if (!empty($regs[2])) $alt_digit = max($alt_digit,$regs[2]);
			}
			++$alt_digit;
		}

			return $alt_string. $alt_digit;
		}


		public function setIsDefault($is_default = true) {
			if ($this->is_default !== ((int)$is_default)) {
				$this->is_default = (int) $is_default;
				$this->setIsUpdated();
			}
		}


		public function getFieldId($field_name) {
			return umiObjectTypesCollection::getInstance()->getType($this->getObject()->getTypeId())->getFieldId($field_name);
		}

		protected function loadInfo() {            
			$Element = XMLFactory::getInstance()->getProxy('hierarchy.xml')->getElement('/umi/elements/element[@id='.$this->id.']');             
			
			if(!$Element->exists()) return false;
			
			if(!$Element->obj_id && !$this->is_broken) {    
				$this->is_broken = true;                
				umiHierarchy::getInstance()->delElement($this->id);                
				return false;
			}
							   
			$this->rel        = (int) $Element->rel;
			$this->type_id    = (int) $Element->type_id;
			$this->lang_id    = (int) $Element->lang_id;
			$this->domain_id  = (int) $Element->domain_id;
			$this->tpl_id     = (int) $Element->tpl_id;
			$this->object_id  = (int) $Element->obj_id;
			$this->ord        = (int) $Element->ord;
			$this->alt_name   = $Element->getValue();
			$this->is_active  = (bool) $Element->is_active;
			$this->is_visible = (bool) $Element->is_visible;
			$this->is_deleted = (bool) $Element->is_deleted;
			$this->is_default = (bool) $Element->is_default;           
			
			$name    = XMLFactory::getInstance()->getProxy('objects.xml')->getElementValue('/umi/objects/object[@id='.$Element->obj_id.']');

			$this->name = $name;    //read-only

			$updatetime = (int)$Element->updatetime;		//TODO: Ignat, тут должен быть нормальный updatetime
			if (!$updatetime) {                             //TODO: При чем тут нормальный updatetime? Я дословно переводил
				$updatetime = umiHierarchy::getTimeStamp();
			}
			$this->update_time = (int) $updatetime;

			return true;           
		}        

		protected function save() {
			$oProxy = XMLFactory::getInstance()->getProxy('hierarchy.xml');
			if($this->is_default) {
				$oProxy->setAttributeValue('/umi/elements/element[@is_default=1 and @lang_id='.(int)$this->lang_id.' and @domain_id='.(int)$this->domain_id.']',
											'is_defalut', '0');                
			}            
			$Element = $oProxy->getElement('/umi/elements/element[@id='.$this->id.']');
			$Element->setValue($this->alt_name);
			$Element->rel = (int) $this->rel;
			$Element->type_id = (int) $this->type_id;
			$Element->lang_id = (int) $this->lang_id;
			$Element->domain_id = (int) $this->domain_id;
			$Element->tpl_id = (int) $this->tpl_id;
			$Element->obj_id = (int) $this->object_id;
			$Element->ord = (int) $this->ord;            
			$Element->is_active = (int) $this->is_active;
			$Element->is_visible = (int) $this->is_visible;
			$Element->is_deleted = (int) $this->is_deleted;
			$Element->update_time = (int) $this->update_time;
			$Element->is_default = (int) $this->is_default;            
			//if ($this->is_updated && cmsController::getInstance()->getModule('search')) {
			//    searchModel::getInstance()->index_item($this->id);
			//}
			return true;            
		}

		public function setIsUpdated($is_updated = true) {
			parent::setIsUpdated($is_updated);
			$this->update_time = time();
			umiHierarchy::getInstance()->addUpdatedElementId($this->id);
			if($this->rel) {
				umiHierarchy::getInstance()->addUpdatedElementId($this->rel);
			}
		}
		
		
		public function getIsBroken() {
			return $this->is_broken;
		}
		
		public function commit() {
			$object = $this->getObject();
			if($object instanceof umiObject) {
				$object->commit();
				
				$objectId = $object->getId();
				$hierarchy = umiHierarchy::getInstance();
				cacheFrontend::getInstance()->del($objectId, "object");
				
				$virtuals = $hierarchy->getObjectInstances($objectId);
				foreach($virtuals as $virtualElementId) {
					cacheFrontend::getInstance()->del($virtualElementId, "element");
				}
			}
			
			parent::commit();
		}
		
		
		public function getObjectTypeId() {
			return $this->getObject()->getTypeId();
		}
	
		public function setName($name) {
		$res = $this->getObject()->setName($name);
		$this->setIsUpdated(true);
		return $res;
	}
	
		public function getHierarchyType() {
		return umiHierarchyTypesCollection::getInstance()->getType($this->type_id);
	}
	
		public function getObjectId() {
			return $this->object_id;
		}
		
		
		protected function getType() {
			$hierarchyTypesCollection = umiHierarchyTypesCollection::getInstance();
			return $hierarchyTypesCollection->getType($this->getTypeId());
		}
		
		
		public function getModule() {
			return $this->getType()->getName();
		}

		public function getMethod() {
			return $this->getType()->getExt();
		}

		
		public function __wakeup() {
			$this->object = NULL;
		}
		
		public function __sleep() {
			$vars = get_class_vars(get_class($this));
			$vars['object'] = NULL;
			return array_keys($vars);
		}
	};


	interface iUmiHierarchy {
		public function addElement($relId, $hierarchyTypeId, $name, $alt_name, $objectTypeId = false, $domainId = false, $langId = false, $templateId = false);
		public function getElement($elementId, $ignorePermissions = false, $ignoreDeleted = false);
		public function delElement($elementId);

		public function copyElement($elementId, $newRelId, $copySubPages = false);
		public function cloneElement($elementId, $newRelId, $copySubPages = false);


		public function getDeletedList();

		public function restoreElement($elementId);
		public function removeDeletedElement($elementId);
		public function removeDeletedAll();


		public function getParent($elementId);
		public function getAllParents($elementsId, $selfInclude = false);

		public function getChilds($elementId, $allowUnactive = true, $allowUnvisible = true, $depth = 0, $hierarchyTypeId = false, $domainId = false);
		public function getChildsCount($elementId, $allowUnactive = true, $allowUnvisible = true, $depth = 0, $hierarchyTypeId = false, $domainId = false);

		public function getPathById($elementId, $ignoreLang = false, $ignoreIsDefaultStatus = false);
		public function getIdByPath($elementPath, $showDisabled = false, &$errorsCount = 0);

		public static function compareStrings($string1, $string2);
		public static function convertAltName($alt_name);
		public static function getTimeStamp();

		public function getDefaultElementId($langId = false, $domainId = false);

		public function moveBefore($elementId, $relId, $beforeId = false);
		public function moveFirst($elementId, $relId);

		public function getDominantTypeId($elementId);

		//public function applyFilter(umiHierarchyFilter);
		
		public function addUpdatedElementId($elementId);
		public function getUpdatedElements();
		
		public function unloadElement($elementId);
		
		public function getElementsCount($module, $method = "");

		public function forceAbsolutePath($bIsForced = true);
		
		public function getObjectInstances($objectId, $bIgnoreDomain = false, $bIgnoreLang = false);
		
		public function getLastUpdatedElements($limit, $updateTimeStamp = 0);
		
		public function checkIsVirtual($elementIds);
	}



	class umiHierarchy extends singleton implements iSingleton, iUmiHierarchy {
		private $elements = Array(),
			$objects, $langs, $domains, $templates;
			
		private $updatedElements = Array();
		private $autocorrectionDisabled = false;
		private $elementsLastUpdateTime = 0;
		private $bForceAbsolutePath = false;
		private $symlinks = Array();


		protected function __construct() {
			$this->objects        =    &umiObjectsCollection::getInstance();
			$this->langs        =    &langsCollection::getInstance();
			$this->domains        =    &domainsCollection::getInstance();
			$this->templates    =    &templatesCollection::getInstance();
			
			if(regedit::getInstance()->getVal("//settings/disable_url_autocorrection")) {
				$this->autocorrectionDisabled = true;
			}

		}

		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}

		public function isExists($element_id) {
			if($this->isLoaded($element_id)) {
				return true;
			} else {
				$element_id = (int) $element_id;     
				return (bool) XMLFactory::getInstance()->getProxy('hierarchy.xml')->getCount('/umi/elements/element[@id='.$element_id.']');
			}
		}

		public function isLoaded($element_id) {
			if($element_id === false) {
				return false;
			}

			if(is_array($element_id)) {
				$is_loaded = true;
				
				foreach($element_id as $celement_id) {
					if(!array_key_exists($celement_id, $this->elements)) {
						$is_loaded = false;
						break;
					}
				}
				
				return $is_loaded;
			} else {
				return (bool) array_key_exists($element_id, $this->elements);
			}
		}


		public function getElement($element_id, $ignorePermissions = false, $ignoreDeleted = false) {
			if(!$ignorePermissions) {
				if(!$this->isAllowed($element_id)) return false;
			}

			if($this->isLoaded($element_id)) {
				return $this->elements[$element_id];
			} else {
				if($element = cacheFrontend::getInstance()->load($element_id, "element")) {
				} else {
					try {
					$element = new umiHierarchyElement($element_id);
					} catch (privateException $e) {
						return false;
					}
					
					cacheFrontend::getInstance()->save($element, "element");
				}
				
				if(is_object($element)) {
					if($element->getIsBroken()) {
						return false;
					}

					if($element->getIsDeleted() && !$ignoreDeleted) return false;

					$this->pushElementsLastUpdateTime($element->getUpdateTime());
					$this->elements[$element_id] = $element;
					return $this->elements[$element_id];
				} else {
					return false;
				}
			}
		}

		public function delElement($element_id) {
			//Inline checking permissions
			if($users_module = cmsController::getInstance()->getModule("users")) {
				if(!$users_module->isAllowedObject($users_module->user_id, $element_id)) {
					return false;
				}
			}
			if(array_key_exists($element_id, $this->elements)) {
				if($element = $this->getElement($element_id)) {
					$aElements = XMLFactory::getInstance()->getProxy('hierarchy.xml')->getElementsArray('/umi/elements/element[@rel='.$element_id.']');
					foreach($aElements as $Element) {
						$child_element = $this->getElement($Element->id, true, true);
						cacheFrontend::getInstance()->del($Element->id, "element");
						$this->delElement($Element->id);
					}
					$element->setIsDeleted(true);
					$element->commit();
					unset($this->elements[$element_id]);
					$this->addUpdatedElementId($element_id);
					return true;
				} 
				return false;
			} else {
				$oProxy       = XMLFactory::getInstance()->getProxy('hierarchy.xml');
				$oThisElement = $oProxy->getElement('/umi/elements/element[@id='.$element_id.']');
				$oThisElement->is_deleted = true;
				$aChildElements = $oProxy->getElementsArray('/umi/elements/element[@rel='.$element_id.']');
				foreach($aChildElements as $oElement) $this->delElement( $oElement->id );
				$this->addUpdatedElementId($element_id);
				cacheFrontend::getInstance()->del($element_id, "element");
			}
		}

		public function copyElement($element_id, $rel_id, $copySubPages = false) {
			if($this->isExists($element_id) && ($this->isExists($rel_id) || $rel_id === 0)) {
				cacheFrontend::getInstance()->flush();
				
				$rel_id = (int) $rel_id;
				$timestamp = self::getTimeStamp();

				if($element = $this->getElement($element_id)) {
					$ord = (int) $element->getOrd();
					unset($element);
				}
				$old_element_id = $element_id;     
				$oProxy         = XMLFactory::getInstance()->getProxy('hierarchy.xml');
				$element_id     = (int) $oProxy->getAttributeValue('/umi/elements/element[not(@id <= preceding-sibling::element/@id) and not(@id <=following-sibling::element/@id)]','id') + 1;
				$ElemCopy       = $oProxy->getElement('/umi/elements/element[@id='.$old_element_id.']')->cloneSelf(true);
				$ElemCopy->id   = $element_id;
				$ElemCopy->rel  = $rel_id;
				$ElemCopy->ord  = $this->getMaxOrd($rel_id) + 10;
				$ElemCopy->updatetime = $timestamp;
				
				
				
				XMLFactory::copyFile('eperms/'.$old_element_id.'.xml', 'eperms/'.$element_id.'.xml'); 
								
				if($element = $this->getElement($element_id)) {
					$element->setAltName($element->getAltName());
					$element->commit();

					if($copySubPages) {
						$domain_id = $element->getDomainId();

						$childs = $this->getChilds($old_element_id, true, true, 0, false, $domain_id);
						foreach($childs as $child_id => $nl) {
							$this->copyElement($child_id, $element_id, true);
						}
					}

					return $element_id;
				} else {
					return false;
				}
			} else {
				return false;
			}
		}

		public function cloneElement($element_id, $rel_id, $copySubPages = false) {
			if($this->isExists($element_id) && ($this->isExists($rel_id) || $rel_id === 0)) {
				if($element = $this->getElement($element_id)) {
					$ord = (int) $element->getOrd();
				}

				$object_id = $element->getObject()->getId();     
				
				$oProxy = XMLFactory::getInstance()->getProxy('objects.xml');
				$new_object_id = (int)$oProxy->getAttributeValue('/umi/objects/object[not(@id <= preceding-sibling::object/@id) and not(@id <=following-sibling::object/@id)]','id') + 1;
				$NewObj        = $oProxy->getElement('/umi/objects/object[@id='.$object_id.']')->cloneSelf(true);
				$NewObj->id    = $new_object_id;
				
				$oProxy  = XMLFactory::getInstance()->getProxy('objectcontent/'.$NewObj->type_id.'.xml');
				$aValues = $oProxy->getElementsArray('/umi/values/value[@obj_id='.$object_id.']');
				foreach($aValues as $Value) $Value->cloneSelf(true)->setAttribute('obj_id', $new_object_id);    
				
				$timestamp = self::getTimeStamp();
				$old_element_id = $element_id;    
				$oProxy         = XMLFactory::getInstance()->getProxy('hierarchy.xml');
				$element_id     = (int)$oProxy->getAttributeValue('/umi/elements/element[not(@id <= preceding-sibling::element/@id) and not(@id <=following-sibling::element/@id)]','id') + 1;
				$ElemCopy       = $oProxy->getElement('/umi/elements/element[@id='.$old_element_id.']')->cloneSelf(true);
				$ElemCopy->id   = $element_id;
				$ElemCopy->rel  = $rel_id;
				$ElemCopy->ord  = $this->getMaxOrd($rel_id) + 10;
				$ElemCopy->updatetime = $timestamp;
				$ElemCopy->obj_id = $new_object_id;
				
				XMLFactory::copyFile('eperms/'.$old_element_id.'.xml', 'eperms/'.$element_id.'.xml');     

				if($element = $this->getElement($element_id)) {
					$element->setAltName($element->getAltName());
					$element->commit();

					if($copySubPages) {
						$domain_id = $element->getDomainId();

						$childs = $this->getChilds($old_element_id, true, true, 0, false, $domain_id);
						foreach($childs as $child_id => $nl) {
							$this->cloneElement($child_id, $element_id, true);
						}
					}

					return $element_id;
				}
				 else {
					return false;
				}
			}
		}

		public function getDeletedList() {
			$tmp      = array();
			$res      = array();
			$oProxy   = XMLFactory::getInstance()->getProxy('hierarchy.xml');
			$aDeleted = $oProxy->getElementsArray('/umi/elements/element[@is_deleted=1]');
			foreach($aDeleted as $Element) {
				$rel = $Element->rel;
				$id  = $Element->id;
				if($rel != 0) 
					if($oProxy->getElement('/umi/elements/element[@id='.$rel.']')->getAttribute('is_deleted') != 0) continue;
				if(array_key_exists($rel, $tmp)) continue;
				if(array_key_exists($id, $res))  unset($res[$tmp[$id]]);
				$res[$id] = $id;
				$tmp[$id] = $rel;
			}
			return array_values($res);
		}
		
		public function restoreElement($element_id) {
			if($element = $this->getElement($element_id, false, true)) {
				$element->setIsDeleted(false);
				$element->commit();
				$aChildren = XMLFactory::getInstance()->getProxy('hierarchy.xml')->getElementsArray('/umi/elements/element[@rel='.$element_id.']');
				foreach($aChildren as $Child) {
					$child_id = $Child->id;
					$child_element = $this->getElement($child_id, true, true);
					$this->restoreElement($child_id);
				}
				return true;
			} else {
				return false;
			}
		}

		public function removeDeletedElement($element_id) {
			if($element = $this->getElement($element_id, true, true)) {
				if($element->getIsDeleted()) {
					$element_id = (int) $element_id;
					
					$oProxy     = XMLFactory::getInstance()->getProxy('hierarchy.xml');
					$aChildren  = $oProxy->getElementsArray('/umi/elements/element[@rel='.$element_id.']');
					foreach($aChildren as $Child) {
						$child_id      = $Child->id;
						$child_element = $this->getElement($child_id, true, true);
						$child_element->setIsDeleted(true);
						$this->removeDeletedElement($child_id);
					}
					$oProxy->removeElement('/umi/elements/element[@id='.$element_id.']');
					unset($this->elements[$element_id]);
					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
		}

		public function removeDeletedAll() {
			$aElements = XMLFactory::getInstance()->getProxy('hierarchy.xml')->getElementsArray('/umi/elements/element[@is_deleted=1]');
			foreach($aElements as $Element) {
				$this->removeDeletedElement($Element->id);
			}
			return true;
		}

		public function getParent($element_id) { 
			return (int)XMLFactory::getInstance()->getProxy('hierarchy.xml')->getAttributeValue('/umi/elements/element[@id='.((int)$element_id).']', 'rel');   
		}

		public function getAllParents($element_id, $include_self = false) {
			$res = Array();

			$self_id = $element_id;

			if($include_self) $res[] = $self_id;
			while($element_id > 0) {
				$element_id = $this->getParent($element_id);
				if($element_id === false || in_array($element_id, $res)) return false;
				$res[] = $element_id;
			}
			return array_reverse($res);
		}

		public function getChilds($element_id, $allow_unactive = true, $allow_unvisible = true, $depth = 0, $hierarchy_type_id = false, $domainId = false) {        	
			$res = Array();
			$element_id = (int) $element_id;
			$allow_unactive = (int) $allow_unactive;
			$allow_unvisible = (int) $allow_unvisible;
			$hierarchy_type_id = (int) $hierarchy_type_id;

			$lang_id = cmsController::getInstance()->getCurrentLang()->getId();

			$domain_id   = ($domainId) ? $domainId : cmsController::getInstance()->getCurrentDomain()->getId();
			
			$XPath = '/umi/elements/element[@is_deleted=0 and @lang_id='.$lang_id.(($element_id > 0) ? "" : " and @domain_id=".$domain_id);

			if(!$allow_unactive)    $XPath .= " and @is_active=1";
			if(!$allow_unvisible)   $XPath .= " and @is_visible=1";
			if($hierarchy_type_id)  $XPath .= " and @type_id=".$hierarchy_type_id;
			
			
			$oProxy = XMLFactory::getInstance()->getProxy('hierarchy.xml');
			
			
			$aIDs = array($element_id);
			$aWrk = array($element_id => &$res);
			while($depth >= 0) {
				if(empty($aIDs)) {
					break;
				}
				$aChildren = $oProxy->getElementsArray($XPath.' and (@rel='.implode(' or @rel=', $aIDs).')]');
				@usort($aChildren, create_function('$a, $b', 'if($a->ord>$b->ord)return 1; else if($a->ord<$b->ord) return -1; else return 0;'));
				$aIDs      = array();
				$aTmp      = array();
				foreach($aChildren as $Child) {
					$iChildId  = $Child->id;
					$iChildRel = $Child->rel;
					$aIDs[]    = $iChildId;
					$aWrk[$iChildRel][$iChildId] = array();
					$aTmp[$iChildId] = &$aWrk[$iChildRel][$iChildId];
				}
				$aWrk = $aTmp;
				$depth--;
			}
			return $res;
		}
		
		
		private static function countRecursive($a) {
			$c = 0;
			if(is_array($a))
			foreach($a as $item) {
				$c += 1 + self::countRecursive($item);
			}
			return $c;
		}
		
		public function getChildsCount($elementId, $allowUnactive = true, $allowUnvisible = true, $depth = 0, $hierarchyTypeId = false, $domainId = false) {
			$aChilds = $this->getChilds($elementId, $allowUnactive, $allowUnvisible, $depth, $hierarchyTypeId, $domainId);        	
			return self::countRecursive($aChilds);
		}

		public function forceAbsolutePath($bIsForced = true) {
			$bOldValue = $this->bForceAbsolutePath;
			$this->bForceAbsolutePath = (bool) $bIsForced;
			return $bOldValue;
		}

		public function getPathById($element_id, $ignoreLang = false, $ignoreIsDefaultStatus = false) {
			static $cache = Array();
			$element_id = (int) $element_id;
			
			if(isset($cache[$element_id . $ignoreLang])) return $cache[$element_id . $ignoreLang];

			$pre_lang = cmsController::getInstance()->pre_lang;

			if($element = umiHierarchy::getInstance()->getElement($element_id, true)) {
				$current_domain = cmsController::getInstance()->getCurrentDomain();
				$element_domain_id = $element->getDomainId();

				if(!$this->bForceAbsolutePath && $current_domain->getId() == $element_domain_id) {
					$domain_str = "";
				} else {
					$domain_str = "http://" . domainsCollection::getInstance()->getDomain($element_domain_id)->getHost();
				}
				
				$element_lang_id = intval($element->getLangId());
				$element_lang = langsCollection::getInstance()->getLang($element_lang_id);

				$b_lang_default = ($element_lang_id === intval(cmsController::getInstance()->getCurrentDomain()->getDefaultLangId()));

				if(!$element_lang || $b_lang_default || $ignoreLang == true) {
					$lang_str = "";
				} else {
					$lang_str = "/" . $element_lang->getPrefix();
				}
			
				if($element->getIsDefault() && !$ignoreIsDefaultStatus) {
					return $cache[$element_id . $ignoreLang] = $domain_str . $lang_str . "/";
				}
			} else {
				return $cache[$element_id . $ignoreLang] = "";
			}
			
			if($parents = $this->getAllParents($element_id)) {
				$path = $domain_str . $lang_str;
				$parents[] = $element_id;
				
				$aElements = XMLFactory::getInstance()->getProxy('hierarchy.xml')->getElementsArray('/umi/elements/element[@id='.implode(' or @id=',$parents).']');
				// Sort elements in proper order
				$iRel = 0;
				$aOrderedElements = array();
				while(true) {
					$idx = 0;
					foreach($aElements as $iIndex=>$e) {
						if($e->rel == $iRel) {
							$idx  = $iIndex;
							$iRel = $e->id;
							break;
						}
					}
					$aOrderedElements[] = $aElements[$idx];
					unset($aElements[$idx]);
					if(!count($aElements)) break;
				}
				foreach($aOrderedElements as $Element) {
					$alt_name = $Element->getValue();
					if($alt_name) $path .= "/" . $alt_name;
				}     
				$path .= "/";
				return $cache[$element_id] = $path;
			} else {
				return $cache[$element_id] = false;
			}
		}

		public function getIdByPath($element_path, $show_disabled = false, &$errors_count = 0) {
			static $cache = Array();
			
			if(isset($cache[$element_path])) return $cache[$element_path];
			
			$oProxy = XMLFactory::getInstance()->getProxy('hierarchy.xml');

			if($element_path == "/") {
				return $cache[$element_path] = $this->getDefaultElementId();
			}
			
			$element_path = trim($element_path, "\/ \n");
			
			if($id = cacheFrontend::getInstance()->loadSql($element_path . "_path")) {
				return $id;
			}
			
			$paths = split("/", $element_path);

			$lang_id = cmsController::getInstance()->getCurrentLang()->getId();
			$domain_id = cmsController::getInstance()->getCurrentDomain()->getId();

			$sz = sizeof($paths);
			$id = 0;
			for($i = 0; $i < $sz; $i++) {
				$alt_name = $paths[$i];     
				
				if($i == 0) {
					if($element_domain_id = domainsCollection::getInstance()->getDomainId($alt_name)) {
						$domain_id = $element_domain_id;
						continue;
					}
				}
				
				$aElements = $oProxy->getElementsArray('/umi/elements/element[text()="'.$alt_name.'" and @rel='.$id.' and @lang_id='.$lang_id.' and @domain_id='.$domain_id.(($show_disabled)?'':' and @is_deleted=0 and @is_active=1').']');     

				if(empty($aElements)) {   
					$max       = 0;
					$temp_id   = 0;
					$res_id    = 0;
					$aElements = $oProxy->getElementsArray('/umi/elements/element[@rel='.$id.' and @lang_id='.$lang_id.' and @domain_id='.$domain_id.(($show_disabled)?'':' and @is_deleted=0 and @is_active=1').']');     
					foreach($aElements as $Element) {
						$temp_id = $Element->id;
						$cstr    = $Element->getValue();
						if($this->autocorrectionDisabled) {
							if($alt_name == $cstr) {
								$res_id = $temp_id;
								$max = 80;
							}
						} else {
							$temp = umiHierarchy::compareStrings($alt_name, $cstr);
							if($temp > $max) {
								$max = $temp;
								$res_id = $temp_id; 
								++$errors_count;
							}
						}
					}
					if($max > 75) {
						$id = $res_id;
					} else {
						return $cache[$element_path] = false;
					}
				} else {
					$E = $aElements[0];
					if(!$E->id) {
						return $cache[$element_path] = false;
					} else {
						$id = $E->id;
					}
				}
			}
			
			cacheFrontend::getInstance()->saveSql($element_path . "_path", $id, 3600);

			return $cache[$element_path] = $id;
		}

		public function addElement($rel_id, $hierarchy_type_id, $name, $alt_name, $type_id = false, $domain_id = false, $lang_id = false, $tpl_id = false) {
			if($type_id === false) {
				if($hierarchy_type = umiHierarchyTypesCollection::getInstance()->getType($hierarchy_type_id)) {
					$type_id = umiObjectTypesCollection::getInstance()->getBaseType($hierarchy_type->getName(), $hierarchy_type->getExt());
					
					if(!$type_id) {
						throw new coreException("There is no base object type for hierarchy type #{$hierarchy_type_id}");
						return false;
					}
				} else {
					throw new coreException("Wrong hierarchy type id given");
					return false;
				}
			}
			
			if($domain_id === false) {
				$domain_id = cmsController::getInstance()->getCurrentDomain()->getId();
			}
			
			if($lang_id === false) {
				$lang_id = cmsController::getInstance()->getCurrentLang()->getId();
			}
			
			if($tpl_id === false) {
				$tpl_id = templatesCollection::getInstance()->getDefaultTemplate($domain_id, $lang_id)->getId();
			}
			
			if($rel_id) {
				$this->addUpdatedElementId($rel_id);
			} else {
				$this->addUpdatedElementId($this->getDefaultElementId());
			}

			if($object_id = $this->objects->addObject($name, $type_id)) {     
				$oProxy     = XMLFactory::getInstance()->getProxy('hierarchy.xml');
				$element_id = (int)$oProxy->getAttributeValue('/umi/elements/element[not(@id <= preceding-sibling::element/@id) and not(@id <=following-sibling::element/@id)]','id') + 1;
				XMLFactory::createFile('eperms/'.$element_id.'.xml', '<?xml version="1.0" encoding="utf-8"?><umi><eperms /></umi>');
				$NewElement = $oProxy->addElement('/umi/elements', 'element', '');
				$NewElement->id        = $element_id;
				$NewElement->rel       = $rel_id;
				$NewElement->type_id   = $hierarchy_type_id;
				$NewElement->domain_id = $domain_id;
				$NewElement->lang_id   = $lang_id;
				$NewElement->tpl_id    = $tpl_id;
				$NewElement->obj_id    = $object_id;    
				$element = $this->getElement($element_id, true);
				$element->setAltName($alt_name);
				$ord = (int)$oProxy->getAttributeValue('/umi/elements/element[not(@ord <= preceding-sibling::element/@ord) and not(@ord <=following-sibling::element/@ord)][@rel='.$rel_id.']','ord');
				$element->setOrd( ($ord + 1) );
				$element->commit();
				$this->elements[$element_id] = $element;
				$this->addUpdatedElementId($rel_id);
				$this->addUpdatedElementId($element_id);     
				if($rel_id) {
					$parent_element = umiHierarchy::getInstance()->getElement($rel_id);
					if($parent_element instanceof umiHierarchyElement) {
						$object_instances = umiHierarchy::getInstance()->getObjectInstances($parent_element->getObject()->getId());
						    
						if(sizeof($object_instances) > 1) {
							foreach($object_instances as $symlink_element_id) {
								if($symlink_element_id == $rel_id) continue;
								$this->symlinks[] = Array($element_id, $symlink_element_id);
							}
						}
					}
				}
				return $element_id;
			} else {
				throw new coreException("Failed to create new object for hierarchy element");
				return false;
			}
		}


		public function getDefaultElementId($lang_id = false, $domain_id = false) {
			static $cache = Array();
			
			if($lang_id === false) {
				$lang_id = cmsController::getInstance()->getCurrentLang()->getId();
			}
			if($domain_id === false) {
				$domain_id = cmsController::getInstance()->getCurrentDomain()->getId();
			}
			
			if(isset($cache[$lang_id][$domain_id])) {
				return $cache[$lang_id][$domain_id];
			}
			
			return $cache[$lang_id][$domain_id] = XMLFactory::getInstance()->getProxy('hierarchy.xml')->getAttributeValue('/umi/elements/element[@is_default=1 and @is_deleted=0 and @is_active=1 and @lang_id='.$lang_id.' and @domain_id='.$domain_id.']', 'id'); 
		}


		public static function compareStrings($str1, $str2) {
			return    100 * (
				similar_text($str1, $str2) / (
					(strlen($str1) + strlen($str2))
				/ 2)
			);
		}

		public static function convertAltName($alt_name) {
			//$alt_name = translit::convert($alt_name);
			$alt_name = preg_replace("/[\?\\\\\-&=]+/", "_", $alt_name);
			$alt_name = preg_replace("/[_\/]+/", "_", $alt_name);
			return $alt_name;
		}

		public static function getTimeStamp() {
			return time();
		}


		public function moveBefore($element_id, $rel_id, $before_id = false) {
			if(!$this->isExists($element_id)) return false;
			
			$oProxy = XMLFactory::getInstance()->getProxy('hierarchy.xml');
			
			$element = umiHierarchy::getInstance()->getElement($element_id);

			$lang_id = $element->getLangId();
			$domain_id = $element->getDomainId();

			$element_id = (int) $element_id;
			$rel_id = (int) $rel_id;

			// apply default template if need for all descendants
			$iCurrTplId = $element->getTplId();
			$arrTpls = templatesCollection::getInstance()->getTemplatesList($domain_id, $lang_id);
			$bNeedChangeTpl = true;
			foreach($arrTpls as $oTpl) {
				if ($oTpl->getId() == $iCurrTplId) {
					$bNeedChangeTpl = false; break;
				}

			}

			if ($bNeedChangeTpl) {
				$oDefaultTpl = templatesCollection::getInstance()->getDefaultTemplate($domain_id, $lang_id);
				if ($oDefaultTpl) {
					$iDefaultTplId = $oDefaultTpl->getId();

					// get all descendants id's
					$oSel = new umiSelection;
					
					$oSel->setHierarchyFilter();
					$oSel->addHierarchyFilter($element_id, 100);

					$arrDescendantsIds = umiSelectionsParser::runSelection($oSel);
					$arrDescendantsIds[] = $element_id;
					$sDIds = implode(",", $arrDescendantsIds);
					
					$XPath = '/umi/elements/element[@id='.implode(']|/umi/elements/element[@id=', $arrDescendantsIds).']';
					$oProxy->setAttributeValue($XPath, 'tpl_id', $iDefaultTplId);
				}
			}

			if($before_id) {
				$before_id = (int) $before_id;
				
				$ord = $oProxy->getAttributeValue('/umi/elements/element[@id='.$before_id.']', 'ord');

				if($ord != null) {
					$ord = (int) $ord;
					$aElements = $oProxy->getElementsArray('/umi/elements/element[@rel='.$rel_id.' and @lang_id='.$lang_id.' and @domain_id='.$domain_id.' and @ord>='.$ord.']');
					foreach($aElements as $oElement) 
						$oElement->setAttribute('ord', $oElement->getAttribute('ord') + 1 );
					$oProxy->setAttributeValue('/umi/elements/element[@id='.$element_id.']', array('ord'=>$ord, 'rel'=>$rel_id));
					return true;
				} else {
					return false;
				}
			} else {
				$ord = $this->getMaxOrd($rel_id, $oProxy);
				
				if($ord != null) {
					++$ord;
				} else {
					$ord = 1;
				}
				$oProxy->setAttributeValue('/umi/elements/element[@id='.$element_id.']', array('ord'=>$ord, 'rel'=>$rel_id));
				return true;
			}
		}
		

		public function moveFirst($element_id, $rel_id) {
			$element_id = (int) $element_id;
			$rel_id = (int) $rel_id;
			$oProxy = XMLFactory::getInstance()->getProxy('hierarchy.xml');
			$ords   = $oProxy->getAttributeValue('/umi/elements/element[@rel='.$rel_id.']', 'ord', true); 
			$before_id = $oProxy->getAttributeValue('/umi/elements/element[(@rel='.$rel_id.') and (@ord = '.min($ords).')]', 'id');
			if($before_id === false) {
				throw new coreException($err);
				return false;
			} else {
				return $this->moveBefore($element_id, $rel_id, $before_id);
			}
		}


		protected function isAllowed($element_id) {
			if($users_ext = cmsController::getInstance()->getModule('users')) {
				list($r, $e) = $users_ext->isAllowedObject($users_ext->user_id, $element_id);
				return $r;
			} else {
				return true;
			}
		}

		public function getDominantTypeId($element_id) {
			if($this->isExists($element_id)) {
				$lang_id = cmsController::getInstance()->getCurrentLang()->getId();
				$domain_id = cmsController::getInstance()->getCurrentDomain()->getId();
				$element_id = (int) $element_id;
				$aObjCache = array();
				$aCount    = array();
				$iMaxCount = array(0, 0);
				$oProxy    = XMLFactory::getInstance()->getProxy('objects.xml');
				$aElements = XMLFactory::getInstance()->getProxy('hierarchy.xml')->getElementsArray('/umi/elements/element[@rel='.$element_id.' and @is_deleted=0 and @lang_id='.$lang_id.' and @domain_id='.$domain_id.']');
				if(empty($aElements)) return null;
				$sCondition = '';
				$i = 0;
				foreach($aElements as $Element)
					$sCondition .= (($i++)?' or ':'').'@id='.$Element->obj_id;
				$aObjects  = XMLFactory::getInstance()->getProxy('objects.xml')->getElementsArray('/umi/objects/object['.$sCondition.']');
				if(empty($aObjects)) return null;     
				foreach($aObjects as $Object) {
					$iObjId = $Object->id;
					if(array_key_exists($iObjId, $aObjCache)) {
						$iTypeId = $aObjCache[$iObjId];
					} else {
						$iTypeId = $Object->type_id;
						$aObjCache[$iObjId] = $iTypeId;
					}
					if(array_key_exists($iTypeId, $aCount)) $aCount[$iTypeId]++;
					else $aCount[$iTypeId] = 1;
					if($aCount[$iTypeId] > $iMaxCount[1]) {
						$iMaxCount[0] = $iTypeId;
						$iMaxCount[1] = $aCount[$iTypeId];
					}
				}
				return $iMaxCount[0];     
			} else {
				return false;
			}
		}
		
		
		public function addUpdatedElementId($element_id) {
			if(!in_array($element_id, $this->updatedElements)) {
				$this->updatedElements[] = $element_id;
			}
		}
		
		
		public function getUpdatedElements() {
			return $this->updatedElements;
		}
		
		
		public function __destruct() {
			if(sizeof($this->updatedElements)) {
				if(function_exists("deleteElementsRelatedPages")) {
					deleteElementsRelatedPages();
				}
			}
			
			if(sizeof($this->symlinks)) {
				foreach($this->symlinks as $arr) {
					list($element_id, $symlink_id) = $arr;
					$this->copyElement($element_id, $symlink_id);
				}
				$this->symlinks = Array();
			}
		}
		
		public function getCollectedElements() {
			return array_keys($this->elements);
		}
		
		
		public function unloadElement($element_id) {
			static $pid;

			if($pid === NULL) {
				$pid = cmsController::getInstance()->getCurrentElementId();
			}
			
			if($pid == $element_id) return false;

			if(array_key_exists($element_id, $this->elements)) {
				unset($this->elements[$element_id]);
			} else {
				return false;
			}
		}
		
		
		public function getElementsCount($module, $method = "") {
			$hierarchy_type_id = umiHierarchyTypesCollection::getInstance()->getTypeByName($module, $method)->getId();
			return XMLFactory::getInstance()->getProxy('hierarchy.xml')->getCount('/umi/elements/element[@type_id='.$hierarchy_type_id.']'); 
		}
		
		
		private function pushElementsLastUpdateTime($update_time = 0) {
			if($update_time > $this->elementsLastUpdateTime) {
				$this->elementsLastUpdateTime = $update_time;
			}
		}
		
		public function getElementsLastUpdateTime() {
			return $this->elementsLastUpdateTime;
		}
		
		
		public function getObjectInstances($object_id, $bIgnoreDomain = false, $bIgnoreLang = false) {
			$res       = array();
			$object_id = (int) $object_id;
			$lang_id   = cmsController::getInstance()->getCurrentLang()->getId();
			$domain_id = cmsController::getInstance()->getCurrentDomain()->getId();
			$aElements = XMLFactory::getInstance()->getProxy('hierarchy.xml')->getElementsArray('/umi/elements/element[@obj_id='.$object_id.' and @domain_id='.$domain_id.' and @lang_id='.$lang_id.']');
			if(!empty($aElements))
			foreach($aElements as $Element) {
				$res[] = $Element->id;
			}
			return $res;
		}
		
		
		public function getDominantTplId($elementId) {
			$elementId = (int) $elementId;
			$aElements = XMLFactory::getInstance()->getProxy('hierarchy.xml')->getElementsArray('/umi/elements/element[@rel='.$elementId.' and @is_deleted=0]');
			$aTplCount = array();
			$iMaxCount = array(0, 0);
			if(!empty($aElements)) {
				foreach($aElements as $Element) {
					$iTpl = $Element->tpl_id;
					if(array_key_exists($iTpl, $aTplCount)) $aTplCount[$iTpl]++;
					else $aTplCount[$iTpl] = 1;
					if($iMaxCount[1] < $aTplCount[$iTpl]) { 
						$iMaxCount[0] = $iTpl;
						$iMaxCount[1] = $aTplCount[$iTpl];
					}
					return $iMaxCount[0];
				}
			} else {
				$element = $this->getElement($elementId);
				if($element instanceof umiHierarchyElement) {
					return $element->getTplId();
				}
			} 
		}
		
		
		public function getLastUpdatedElements($limit, $timestamp = 0) { 
			$res       = array();
			$limit     = (int) $limit;
			$timestamp = (int) $timestamp;
			
			$aElements = XMLFactory::getInstance()->getProxy('hierarchy.xml')->getElementsArray('/umi/elements/element[@updatetime>='.$timestamp.']');
			$i = 0;
			if(!empty($aElements))
			foreach($aElements as $Element) {
				$res[] = $Element->id;
				$i++;
				if($i>=$limit) break;
			}
			return $res;
		}
		
		public function checkIsVirtual($arr) {
			if(sizeof($arr) == 0) return $arr;
			
			foreach($arr as $element_id => $nl) {
				$element = $this->getElement($element_id);
				$arr[$element_id] = (string) $element->getObjectId();
			}
			
			$temp_arr = Array();
			$oProxy = XMLFactory::getInstance()->getProxy('hierarchy.xml');
			foreach($arr as $element_id => $object_id) {
				if(isset($temp_arr[$object_id])) {
					$elements = $temp_arr[$object_id];
				} else {
					$temp_arr[$object_id] = $elements = $oProxy->getElementsArray('/umi/elements/element[@obj_id = ' . $object_id . ' and @is_deleted = 0]');
				}
				$arr[$element_id] = (bool) (sizeof($elements) > 1);
			}
			
			return $arr;
		}
		
		protected function rewriteElementAltName($element_id) {
			$this->unloadElement($element_id);
			
			$element = $this->getElement($element_id, true, true);
			if($element instanceof umiHierarchyElement) {
				$element->setAltName($element->getAltName());
				$element->commit();

				return true;
			} else {
				return false;
			}
		}
		
		
		protected function getMaxOrd($rel_id, $oProxy = NULL) {
			if(!$oProxy) {
				$oProxy = XMLFactory::getInstance()->getProxy('hierarchy.xml');
			}
			return (int) $oProxy->getAttributeValue("/umi/elements/element[@rel = '{$rel_id}' and (not(@ord <= preceding-sibling::element/@ord) and not(@ord <=following-sibling::element/@ord))]",'ord');
		}
	};


	interface iUmiSelection {
		public function setObjectTypeFilter($isEnabled = true);
		public function setElementTypeFilter($isEnabled = true);
		public function setPropertyFilter($isEnabled = true);
		public function setLimitFilter($isEnabled = true);
		public function setHierarchyFilter($isEnabled = true);
		public function setOrderFilter($isEnabled = true);
		public function setPermissionsFilter($isEnabled = true);
		public function setNamesFilter($isEnabled = true);
		public function setActiveFilter($isEnabled = true);
		public function setOwnerFilter($isEnabled = true);
		public function setObjectsFilter($isEnabled = true);
		public function setElementsFilter($isEnabled = true);

		public function forceHierarchyTable($isForced = true);

		public function addObjectType($objectTypeId);
		public function addElementType($elementTypeId);

		public function addLimit($resultsPerQueryPage, $resultsPage = 0);

		public function setOrderByProperty($fieldId, $asc = true);
		public function setOrderByOrd();
		public function setOrderByRand();
		public function setOrderByName($asc = true);
		public function setOrderByObjectId($asc = true);

		public function addHierarchyFilter($elementId, $depth = 0, $ignoreIsDefault = false);

		public function addPropertyFilterBetween($fieldId, $minValue, $maxValue);
		public function addPropertyFilterEqual($fieldId, $exactValue, $caseInsencetive = true);
		public function addPropertyFilterNotEqual($fieldId, $exactValue, $caseInsencetive = true);
		public function addPropertyFilterLike($fieldId, $likeValue, $caseInsencetive = true);
		public function addPropertyFilterMore($fieldId, $val);
		public function addPropertyFilterLess($fieldId, $val);
		public function addPropertyFilterIsNull($fieldId);
		public function addActiveFilter($active);
		public function addOwnerFilter($owner);
		public function addObjectsFilter($vOids);
		public function addElementsFilter($vEids);

		public function addNameFilterEquals($exactValue);
		public function addNameFilterLike($likeValue);

		public function addPermissions($userId = false);
		public function setPermissionsLevel($level = 1);
		
		public function setDomainId($domainId = false);
		public function setLangId($langId = false);

		public function getOrderConds();
		public function getLimitConds();
		public function getPropertyConds();
		public function getObjectTypeConds();
		public function getElementTypeConds();
		public function getHierarchyConds();
		public function getPermissionsConds();
		public function getForceCond();
		public function getActiveConds();
		public function getOwnerConds();
		public function getObjectsConds();
		public function getElementsConds();
		public function getDomainId();
		public function getLangId();
		public function getRequiredPermissionsLevel();

		public function getNameConds();
		
		
		public function setConditionModeOR();
		
		public function setIsDomainIgnored($isDomainIgnored = false);
		public function setIsLangIgnored($isLangIgnored = false);
	}


/**
	* Класс, который предоставляет средства для создания шаблонов выборок данных из базы данных.
*/
	class umiSelection implements iUmiSelection {
		private	$order = Array(),
			$limit = Array(),
			$object_type = Array(),
			$element_type = Array(),
			$props = Array(),
			$hierarchy = Array(),
			$perms = Array(),
			$names = Array(),
			$active = Array(),
			$owner = Array(),
			$objects_ids = Array(),
			$elements_ids = Array(),

			$is_order = false,  $is_limit = false, $is_object_type = false, $is_element_type = false, $is_props = false, $is_hierarchy = false, $is_permissions = false, $is_forced = false, $is_names = false, $is_active = false,
			$condition_mode_or = false, $is_owner = false,
			$is_objects_ids = false, $is_elements_ids = false,
			$is_domain_ignored = false, $isDomainIgnored = false, $isLangIgnored = false, $langId = false, $domainId = false,
			$permissionsLevel = 1,
			$searchStrings = Array();
			
		public	$result = false, $count = false;

		// ========

		public $optimize_root_search_query = false;
		public $sql_part__hierarchy = "";
		public $sql_part__element_type = "";
		public $sql_part__owner = "";
		public $sql_part__objects = "";
		public $sql_part__elements = "";
		public $sql_part__perms = "";
		public $sql_part__perms_tables = "";
		public $sql_part__content_tables = "";
		public $sql_part__object_type = "";
		public $sql_part__props_and_names = "";
		public $sql_part__lang_cond = "";
		public $sql_part__domain_cond = "";
		public $sql_part__unactive_cond = "";

		public $sql_cond__total_joins = 0;
		public $sql_cond__content_tables_loaded = 0;
		public $sql_cond__need_content = false;
		public $sql_cond__need_hierarchy = false;
		public $sql_cond__domain_ignored = false;
		public $sql_cond_auto_domain = false;

		public $sql_arr_for_mark_used_fields = array();
		public $sql_arr_for_and_or_part = array();

		// ==

		public $sql_kwd_distinct = "";
		public $sql_kwd_distinct_count = "";
		public $sql_kwd_straight_join = "";

		public $sql_select_expr = "";
		public $sql_table_references = "";
		public $sql_where_condition_required = "";
		public $sql_where_condition_common = "";
		public $sql_where_condition_additional = "";

		public $sql_order_by = "";
		public $sql_limit = "";
		
		public $objectTableIsRequired = false;
		public $excludeNestedPages = false;
		
		public $usedContentTables = Array();

		// ========

		/**
		* @deprecated
		* @desc Включает/выключает фильтрацию по типу объектов
		* @param Boolean $is_enabled Разрешить фильтрацию (true) или запретить (false)
		*/
		public function setObjectTypeFilter($is_enabled = true) {
			$this->is_object_type = (bool) $is_enabled;
			if (!$is_enabled) $this->object_type = Array();
		}

		/**
		* @deprecated
		* @desc Включает/выключает фильтрацию по типу елементов иерархии
		* @param Boolean $is_enabled Разрешить фильтрацию (true) или запретить (false)
		*/
		public function setElementTypeFilter($is_enabled = true) {
			$this->is_element_type = (bool) $is_enabled;
			if (!$is_enabled) $this->element_type = Array();
		}

		/**
		* @deprecated
		* @desc Включает/выключает фильтрацию по свойствам объектов
		* @param Boolean $is_enabled Разрешить фильтрацию (true) или запретить (false)
		*/
		public function setPropertyFilter($is_enabled = true) {
			$this->is_props = (bool) $is_enabled;
			if (!$is_enabled) $this->props = Array();
		}

		/**
		* @deprecated
		* @desc Включает/выключает ограничение по количество элементов
		* @param Boolean $is_enabled Разрешить ограничение (true) или запретить (false)
		*/
		public function setLimitFilter($is_enabled = true) {
			$this->is_limit = (bool) $is_enabled;
			if (!$is_enabled) $this->limit = Array();
		}

		/**
		* @deprecated
		* @desc Включает/выключает фильтрацию по id элементов иерархии
		* @param Boolean $is_enabled  Разрешить фильтрацию (true) или запретить (false) 
		*/
		public function setHierarchyFilter($is_enabled = true) {
			$this->is_hierarchy = (bool) $is_enabled;
			if (!$is_enabled) $this->hierarchy = Array();
		}

		/**
		* @deprecated
		* @desc Включает/выключает сортировку
		* @param Boolean $is_enabled Разрешить сортировку (true) или запретить (false)
		*/
		public function setOrderFilter($is_enabled = true) {
			$this->is_order = (bool) $is_enabled;
			if (!$is_enabled) $this->order = Array();
		}

		/**
		* @deprecated
		* @desc Включает/выключает фильтрацию по правам
		* @param $is_enabled Разрешить фильтрацию (true) или запретить (false)
		*/ 
		public function setPermissionsFilter($is_enabled = true) {
			

			$this->is_permissions = $is_enabled;

			$user_id = $this->getCurrentUserId();
			if(cmsController::getInstance()->getModule("users")->isSv($user_id)) {
				$this->is_permissions = false;
			}
			if (!$is_enabled) $this->perms = Array();
		}

		/**
		* @deprecated
		* @desc Включает/выключает фильтрацию по активности элемента
		* @param $is_enabled Разрешить фильтрацию (true) или запретить (false)
		*/
		public function setActiveFilter($is_enabled = true) {
			$this->is_active = (bool) $is_enabled;
			if (!$is_enabled) $this->is_active = Array();
		}

		/**
		* @deprecated
		* @desc Включает/выключает фильтрацию по владельцу
		* @param $is_enabled Разрешить фильтрацию (true) или запретить (false)
		*/
		public function setOwnerFilter($is_enabled = true) {
			$this->is_owner = (bool) $is_enabled;
			if (!$is_enabled) $this->is_owner = Array();
		}

		/**
		* @deprecated
		* @desc Включает/выключает фильтрацию по id объектов
		* @param $is_enabled Разрешить фильтрацию (true) или запретить (false)
		*/
		public function setObjectsFilter($is_enabled = true) {
			$this->is_objects_ids = (bool) $is_enabled;
			if (!$is_enabled) $this->is_objects_ids = Array();
		}
		
		/**
		* @deprecated
		* @desc 
		*/
		public function setElementsFilter($is_enabled = true) {
			$this->is_elements_ids = (bool) $is_enabled;
			if (!$is_enabled) $this->is_elements_ids = Array();
		}

		/**
		* @deprecated
		* @desc Включает/выключает фильтрацию по имени объекта
		* @param $is_enabled Разрешить фильтрацию (true) или запретить (false)
		*/
		public function setNamesFilter($is_enabled = true) {
			$this->is_names = (bool) $is_enabled;
			if (!$is_enabled) $this->names = Array();
		}

		public function forceHierarchyTable($isForced = true) {
			$this->is_forced = (bool) $isForced;
		}

		/**
		* @desc Добавляет тип объекта к критерию фильтрации
		* @param Int $object_type Id типа объекта
		*/
		public function addObjectType($object_type_id) {
			$this->setObjectTypeFilter();

			if(is_array($object_type_id)) {
				foreach($object_type_id as $sub_object_type_id) {
					if(!$this->addObjectType($sub_object_type_id)) {
						return false;
					}
				}
				return true;
			}

			if(umiObjectTypesCollection::getInstance()->isExists($object_type_id)) {
				if(in_array($object_type_id, $this->object_type) === false) {
					$this->object_type[] = $object_type_id;
					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
		}

		/**
		* @desc Добавляет тип элемента к критерию фильтрации
		* @param Int $object_type Id типа элемента
		*/
		public function addElementType($element_type_id) {
			/*
			Не принимает массив !!! вызывайте несколько раз (TODO: переписать)
			*/
			$this->setElementTypeFilter();
		
			if(umiHierarchyTypesCollection::getInstance()->isExists($element_type_id)) {
				if(in_array($element_type_id, $this->element_type) === false) {
					$this->element_type[] = $element_type_id;
					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
		}

		/**
		* @desc Устанавливает количественные ограничения на выборку
		* @param Int $per_page	Количество объектов на странице
		* @param Int $page 		Номер выбираемой страницы
		*/
		public function addLimit($per_page, $page = 0) {
			$this->setLimitFilter();
		
			$per_page = (int) $per_page;
			$page = (int) $page;

			if($page < 0) {
				$page = 0;
			}
			
			$this->limit = Array($per_page, $page);
		}

		/**
		* @desc Устанавливает признак активности елемента
		* @param Boolean $active True - выбрать активные алементы, False - выбрать неактивные элементы
		*/
		public function addActiveFilter($active) {
			$this->setActiveFilter();
			$this->active = Array($active);
		}

		/**
		* @desc Устанавливает владельцев объекта/элемента
		* @param Array $vOwners Возможные id владельцев
		*/
		public function addOwnerFilter($vOwners) {
			$this->setOwnerFilter();
			$this->owner = $this->toIntsArray($vOwners);
		}

		/**
		* @desc Устанавливает возможные id объектов
		* @param Array $vOids возможные id объектов
		*/
		public function addObjectsFilter($vOids) {
			$this->setObjectsFilter();
			$this->objects_ids = $this->toIntsArray($vOids);
		}

		/**
		* @desc Устанавливает возможные id елементов иерархии
		* @param Array $vOids возможные id елементов иерархии
		*/
		public function addElementsFilter($vEids) {
			$this->setElementsFilter();
			$this->elements_ids = $this->toIntsArray($vEids);
		}

		/**
		* @desc Устанавливает поле и вид сортировки
		* @param Int 		$field_id 	id поля, по которому будет произведена сортировка
		* @param Boolean 	$asc 		порядок сортировки: true - прямой, false - обратный
		*/
		public function setOrderByProperty($field_id, $asc = true) {
			if(!$field_id) return false;
			$this->setOrderFilter();
		
			$data_type = $this->getDataByFieldId($field_id);

			$filter = Array("field_id" => $field_id, "asc" => $asc, "type" => $data_type, "native_field" => false);

			if(in_array($filter, $this->order) === false) {
				$this->order[] = $filter;
				return true;
			} else {
				return false;
			}
		}

		/**
		* @desc Устанавливает сортировку по расположению в иерархии
		* @param Boolean $asc порядок сортировки: true - прямой, false - обратный
		*/
		public function setOrderByOrd($asc = true) {
			$this->setOrderFilter();

			$filter = Array("type" => "native", "native_field" => "ord", "asc" => $asc);

			if(in_array($filter, $this->order) === false) {
				$this->order[] = $filter;
				return true;
			} else {
				return false;
			}
		}
		
		/**
		* @desc Устанавливает выборку случайных ID
		*/
		public function setOrderByRand() {
			$this->setOrderFilter();
		
			$filter = Array("type" => "native", "native_field" => "rand", "asc" => true);

			if(in_array($filter, $this->order) === false) {
				$this->order[] = $filter;
				return true;
			} else {
				return false;
			}
		}

		/**
		* @desc Устанавливает сортировку по имени
		* @param Boolean $asc порядок сортировки: true - прямой, false - обратный
		*/
		public function setOrderByName($asc = true) {
			$this->setOrderFilter();
		
			$filter = Array("type" => "native", "native_field" => "name", "asc" => $asc);

			if(in_array($filter, $this->order) === false) {
				$this->order[] = $filter;
				return true;
			} else {
				return false;
			}
		}
		
		/**
		* @desc Устанавливает сортировку по id объекта
		* @param Boolean $asc порядок сортировки: true - прямой, false - обратный
		*/
		public function setOrderByObjectId($asc = true) {
			$this->setOrderFilter();

			$filter = Array("type" => "native", "native_field" => "object_id", "asc" => $asc);
			
			if(in_array($filter, $this->order) === false) {
				$this->order[] = $filter;
				return true;
			} else {
				return false;
			}
		}

        /**
        * @desc Устанавливает параметры выбора элементов иерархии
        * @param Int 	 	$element_id 		Id корня выборки
        * @param Int 	 	$depth				Глубина выборки элементов от корня
        * @param Boolean	$ignoreIsDefault	игнорировать элемент по-умолчанию
        */
		public function addHierarchyFilter($element_id, $depth = 0, $ignoreIsDefault = true) {
			$this->setHierarchyFilter();
			
			if(is_array($element_id)) {
				foreach($element_id as $id) {
					$this->addHierarchyFilter($id, $depth);
				}
				return;
			}

			if(umiHierarchy::getInstance()->isExists($element_id) || (is_numeric($element_id) && $element_id == 0)) {
				if($element_id == umiHierarchy::getInstance()->getDefaultElementId() && $ignoreIsDefault == false) {
					$element_id = Array(0, 0);
				}
			
				if(in_array($element_id, $this->hierarchy) === false || $element_id == 0) {
					$this->hierarchy[] = Array((int) $element_id, $depth);
				}

				if($depth > 0) {
					$this->hierarchy[] = Array($element_id, $depth);
				}
			} else {
				return false;
			}
		}

        /**
        * @desc Устанавливает проверку попадания значения поля в интервал
        * @param Int 	$field_id 	Id поля
        * @param Mixed 	$min 		Минимальное значение
        * @param Mixed	$max		Максимальное значение
        */
		public function addPropertyFilterBetween($field_id, $min, $max) {
			if(!$field_id) return false;
			$this->setPropertyFilter();
			
			$data_type = $this->getDataByFieldId($field_id);

			$filter = Array("type" => $data_type, "field_id" => $field_id, "filter_type" => "between", "min" => $min, "max" => $max);
			$this->props[] = $filter;
		}

		/**
		* @desc Устанавливает проверку значения поля на равенство
		* @param Int		$field_id			Id поля
		* @param Mixed		$value				Значение для сравнения
		* @param Boolean 	$case_insencetive   True - не учитывать регистр, false - учитывать регистр
		*/
		public function addPropertyFilterEqual($field_id, $value, $case_insencetive = true) {
			if(!$field_id) return false;
			$this->setPropertyFilter();
		
			$data_type = $this->getDataByFieldId($field_id);

			$filter = Array("type" => $data_type, "field_id" => $field_id, "filter_type" => "equal", "value" => $value, "case_insencetive" => $case_insencetive);
			$this->props[] = $filter;
		}

		/**
		* @desc Устанавливает проверку значения поля на неравенство
		* @param Int		$field_id			Id поля
		* @param Mixed		$value				Значение для сравнения
		* @param Boolean 	$case_insencetive   True - не учитывать регистр, false - учитывать регистр 
		*/
		public function addPropertyFilterNotEqual($field_id, $value, $case_insencetive = true) {
			if(!$field_id) return false;
			$this->setPropertyFilter();
		
			$data_type = $this->getDataByFieldId($field_id);

			$filter = Array("type" => $data_type, "field_id" => $field_id, "filter_type" => "not_equal", "value" => $value, "case_insencetive" => $case_insencetive);
			$this->props[] = $filter;
		}

        /**
		* @desc Устанавливает проверку значения поля на включение поисковой строки
		* @param Int		$field_id			Id поля
		* @param Mixed		$value				Значение для поиска
		* @param Boolean 	$case_insencetive   True - не учитывать регистр, false - учитывать регистр 
		*/
		public function addPropertyFilterLike($field_id, $value, $case_insencetive = true) {
			if(!$field_id) return false;
			$this->setPropertyFilter();
		
			$data_type = $this->getDataByFieldId($field_id);

			$filter = Array("type" => $data_type, "field_id" => $field_id, "filter_type" => "like", "value" => $value, "case_insencetive" => $case_insencetive);
			$this->props[] = $filter;
		}

		/**
		* @desc Устанавливает проверку значения поля на "больше"
		* @param Int		$field_id			Id поля
		* @param Mixed		$value				Значение для сравнения		
		*/
		public function addPropertyFilterMore($field_id, $value) {
			if(!$field_id) return false;
			$this->setPropertyFilter();
		
			$data_type = $this->getDataByFieldId($field_id);

			$filter = Array("type" => $data_type, "field_id" => $field_id, "filter_type" => "more", "value" => $value);
			$this->props[] = $filter;
		}

		/**
		* @desc Устанавливает проверку значения поля на "меньше"
		* @param Int		$field_id			Id поля
		* @param Mixed		$value				Значение для сравнения		
		*/
		public function addPropertyFilterLess($field_id, $value) {
			if(!$field_id) return false;
			$this->setPropertyFilter();
		
			$data_type = $this->getDataByFieldId($field_id);

			$filter = Array("type" => $data_type, "field_id" => $field_id, "filter_type" => "less", "value" => $value);
			$this->props[] = $filter;
		}

		/**
		* @desc Устанавливает проверку значения поля на отсутствие значения
		* @param Int		$field_id			Id поля		
		*/
		public function addPropertyFilterIsNull($field_id) {
			if(!$field_id) return false;
			$this->setPropertyFilter();
		
			$data_type = $this->getDataByFieldId($field_id);

			$filter = Array("type" => $data_type, "field_id" => $field_id, "filter_type" => "null");
			$this->props[] = $filter;
		}

		/**
		* @desc Устанавливает проверку значения поля на отсутствие значения
		* @param Int		$field_id			Id поля		
		*/
		public function addPropertyFilterIsNotNull($field_id) {
			if(!$field_id) return false;
			$this->setPropertyFilter();
		
			$data_type = $this->getDataByFieldId($field_id);

			$filter = Array("type" => $data_type, "field_id" => $field_id, "filter_type" => "notnull");
			$this->props[] = $filter;
		}

        /**
        * @desc Устанавливает пользователя или группу для проверки прав на элемент
        * @param Int $user_id ID пользователя или группы
        */
		public function addPermissions($user_id = false) {
			$this->setPermissionsFilter();
		
			if($user_id === false) $user_id = $this->getCurrentUserId();
			$owners = $this->getOwnersByUser($user_id);
			$this->perms = $owners;
		}
		
		/**
			* Устанавливает уровень прав, который должен быть у искомых страниц
		*/
		public function setPermissionsLevel($level = 1) {
			$this->permissionsLevel = (int) $level;
		}

		/**
		* @desc Устанавливает значение для проверки имени поля на равенство
		* @param Mixed $value Значение для проверки
		*/
		public function addNameFilterEquals($value) {
			$this->setNamesFilter();
		
			$value = Array("value" => $value, "type" => "exact");

			if(!in_array($value, $this->names)) {
				$this->names[] = $value;
			}
		}
		
		/**
		* @desc Устанавливает значение для поиска в имени
		* @param Mixed $value значение для поиска
		*/
		public function addNameFilterLike($value) {
			$this->setNamesFilter();
		
			$value = Array("value" => $value, "type" => "like");

			if(!in_array($value, $this->names)) {
				$this->names[] = $value;
			}
		}

		/**
		* @desc Возвращает параметры сортировки
		* @return Array | Boolean(False) 
		*/
		public function getOrderConds() {
			return ($this->is_order) ? $this->order : false;
		}

		/**
		* @desc Возвращает количественные ограничения на выборку
		* @return Array | Boolean(False) 
		*/
		public function getLimitConds() {
			return ($this->is_limit) ? $this->limit : false;
		}

		/**
		* @desc Возвращает признак активности
		* @return Boolean 
		*/
		public function getActiveConds() {
			return ($this->is_active) ? $this->active : false;
		}

		/**
		* @desc Возвращает список возможных владельцев
		* @return Array | Boolean(False) 
		*/
		public function getOwnerConds() {
			$arrAnswer = array();
			if (is_array($this->owner) && count($this->owner)) {
				$arrAnswer = array_map('intval', $this->owner);
			}
			return ($this->is_owner) ? $arrAnswer : false;
		}
		
		/**
		* @desc Возвращает список возможных id объектов
		* @return Array | Boolean(False) 
		*/
		public function getObjectsConds() {
			$arrAnswer = array();
			if (is_array($this->objects_ids) && count($this->objects_ids)) {
				$arrAnswer = array_map('intval', $this->objects_ids);
			}
			return ($this->is_objects_ids) ? $arrAnswer : false;
		}
		
		/**
		* @desc Возвращает список возможных id элементов иерархии
		* @return Array | Boolean(False) 
		*/
		public function getElementsConds() {
			$arrAnswer = array();
			if (is_array($this->elements_ids) && count($this->elements_ids)) {
				$arrAnswer = array_map('intval', $this->elements_ids);
			}
			return ($this->is_elements_ids) ? $arrAnswer : false;
		}

		/**
		* @desc Возвращает список условий на выборку по значению полей
		* @return Array | Boolean(False)
		*/
		public function getPropertyConds() {
			return ($this->is_props) ? $this->props : false;
		}

		/**
		* @desc Возвращает список возможных id типов объектов
		* @return Array | Boolean(False) 
		*/
		public function getObjectTypeConds() {
			return ($this->is_object_type) ? $this->object_type : false;
		}

		/**
		* @desc Возвращает список возможных id типов элементов иерархии
		* @return Array  | Boolean(False)
		*/
		public function getElementTypeConds() {
			if($this->getObjectTypeConds() !== false) {
				return false;
			}
			
			if($this->optimize_root_search_query) {
				if(is_array($this->element_type)) {
					if(sizeof($this->element_type) > 1) {
						reset($this->element_type);
						$this->element_type = Array(current($this->element_type));
					}
				}
			}

			return ($this->is_element_type) ? $this->element_type : false;
		}

		public function getHierarchyConds() {
			$this->hierarchy = array_unique_arrays($this->hierarchy, 0);
			return ($this->is_hierarchy && !$this->optimize_root_search_query) ? $this->hierarchy : false;
		}

		/**
		* @desc Возвращает список пользователей и/или групп с правами на элемент иерархии
		* @return Array | Boolean(False) 
		*/
		public function getPermissionsConds() {
			return ($this->is_permissions) ? $this->perms : false;
		}

		public function getForceCond() {
			return $this->is_forced;
		}

		/**
		* @desc Возвращает условия проверки имени
		* @return Array | Boolean(False)
		*/
		public function getNameConds() {
			return ($this->is_names) ? $this->names : false;
		}
		
		private function getDataByFieldId($field_id) {
			if($field = umiFieldsCollection::getInstance()->getField($field_id)) {
				$field_type_id = $field->getFieldTypeId();

				if($field_type = umiFieldTypesCollection::getInstance()->getFieldType($field_type_id)) {
					if($data_type = $field_type->getDataType()) {
						return umiFieldType::getDataTypeDB($data_type);
					} else {
						return false;
					}
				} else {
					return false;
				}
			} else {
				return false;
			}
		}

		private function getCurrentUserId() {
			if($users = cmsController::getInstance()->getModule("users")) {
				return $users->user_id;
			} else {
				return false;
			}
		}

		private function getOwnersByUser($user_id) {
			if($user = umiObjectsCollection::getInstance()->getObject($user_id)) {
				$groups = $user->getValue("groups");
				$groups[] = $user_id;
				return $groups;
			} else {
				return false;
			}
		}
		
		/**
		* @desc Устанавливает флаг "ИЛИ" группировки результатов выборки по значению полей.
		* 		Если этот флаг установлен, то выбираются объекты/элементы иерархии,
		* 		удовлетворяющие хотя бы одному условию, из указаных. В противном случае
		* 		требуется соблюдение всех указаных условий.
		*/
		public function setConditionModeOr() {
			$this->condition_mode_or = true;
		}
		
		/**
		* @desc Возвращает значение флага группировки результатов выборки по значению полей
		* @return Boolean
		*/
		public function getConditionModeOr() {
			return $this->condition_mode_or;
		}
		
		
		/**
		* @desc Устанавливает значение флага игнорирования текущего домена
		* @param Boolean $isDomainIgnored True - домен игнорируется, false - не игнорируется
		*/
		public function setIsDomainIgnored($isDomainIgnored = false) {
			$this->isDomainIgnored = (bool) $isDomainIgnored;
		}
		
		/**
		* @desc Устанавливает значение флага игнорирования текущей языковой версии
		* @param Boolean $isLangIgnored True - домен игнорируется, false - не игнорируется
		*/
		public function setIsLangIgnored($isLangIgnored = false) {
			$this->isLangIgnored = (bool) $isLangIgnored;
		}
		
		/**
		* @desc Возвращает значение  флага игнорирования текущего домена
		* @return Boolean
		*/
		public function getIsDomainIgnored() {
			return $this->isDomainIgnored;
		}

		/**
		* @desc Возвращает значение  флага игнорирования текущей языковой версии
		* @return Boolean
		*/
		public function getIsLangIgnored() {
			return $this->isLangIgnored;
		}
		
		/**
			* Искать только по указанному домену
			* @param Integer $domainId = false id домена, либо false, если поиск будет по всем доменам
		*/
		public function setDomainId($domainId = false) {
			$this->domainId = ($domainId === false) ? false : (int) $domainId;
		}
		
		/**
			* Искать только в указанной языковой версии
			* @param Integer $langId = false id языка, либо false
		*/
		public function setLangId($langId = false) {
			$this->langId = ($langId === false) ? false : (int) $langId;
		}
		
		/**
			* Поиск по строке в любом тектовом поле
			* @param String $searchString строка поиска
		*/
		public function searchText($searchString) {
			if(is_string($searchString)) {
				if(strlen($searchString) > 0 && !in_array($searchString, $this->searchStrings)) {
					$this->searchStrings[] = $searchString;
					return true;
				}
			}
			return false;
		}
		
		public function getDomainId() {
			return $this->domainId;
		}
		
		public function getLangId() {
			return $this->langId;
		}
		
		public function getRequiredPermissionsLevel() {
			return $this->permissionsLevel;
		}
		
		public function getSearchStrings() {
			return $this->searchStrings;
		}

		//

		private function toIntsArray($vValue) {
			$arrAnswer = Array();
			if (is_string($vValue)) {
				$arrAnswer = preg_split("/[^\d]/is", $vValue);
			} elseif (is_numeric($vValue)) {
				$arrAnswer = array(intval($vValue));
			} elseif (!is_array($vValue)) {
				$arrAnswer = array();
			} else {
			    $arrAnswer = $vValue;
			}
			return array_map('intval', $arrAnswer);
		}
	};


	interface iUmiSelectionsParser {
		public static function runSelection(umiSelection $selectionObject);
		public static function runSelectionCounts(umiSelection $selectionObject);
		public static function parseSelection(umiSelection $selectionObject);
	}


/**
* @desc Utility to select entitys by specified criteria (XML Database version) 
*/
class umiSelectionsParser implements iUmiSelectionsParser {
	const UPCASE_FROM = 'abcdefghijklmnopqrstuvwxyzабвгдеёжзийклмнопрстуфхцчшщъыьэюя';
	const UPCASE_TO   = 'ABCDEFGHIJKLMNOPQRSTUVWXYZАБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ';
    /**
    * @desc Prepare for selecting objects
    * @param umiSelection $selectionObject Selection criteria
    * @return Array
    */
    public static function parseSelection(umiSelection $selectionObject) {
        $aResultQueryList                = array();
        $aResultQueryList['Limitation']  = umiSelectionsParser::makeLimitations($selectionObject);
        $aResultQueryList['Ordering']    = umiSelectionsParser::makeOrdering($selectionObject);
        $aResultQueryList['Objects']     = umiSelectionsParser::makeObjectQuery($selectionObject);
        $aResultQueryList['Content']     = umiSelectionsParser::makeContentQuery($selectionObject);
        $aResultQueryList['Permissions'] = umiSelectionsParser::makePermissionQuery($selectionObject);
        $aResultQueryList['Hierarchy']   = umiSelectionsParser::makeHierarchyQuery($selectionObject);        
        return $aResultQueryList;
    }
    /**
    * @desc Select objects for prepared criteria
    * @param umiSelection $selectionObject Selection criteria 
    * @return Array of hierarchy elements ids
    */
    public static function runSelection(umiSelection $selectionObject) {    	
        $aQuery     = umiSelectionsParser::parseSelection($selectionObject);
        $aOIDs      = array();
        $aEIDs      = array();
        $aResultIDs = array();
        $XMLFactory = XMLFactory::getInstance();
        if($aQuery['Objects']) {
            if($aQuery['Ordering']['object']) {
                $aElements = $XMLFactory->getProxy('objects.xml')->getElementsArray($aQuery['Objects'][0]);
                $aOIDs     = umiSelectionsParser::sortItems($aElements, $aQuery['Ordering']['object']);
            } else {
                $aOIDs = $XMLFactory->getProxy('objects.xml')->getAttributeValue($aQuery['Objects'][0], 'id', true);
            }
            $aResultIDs = $aOIDs;
            if(empty($aResultIDs) && !$aQuery['Objects'][1]) {
        		$selectionObject->count  = 0;
        		$selectionObject->result = array();
        		return array();
			}
        }        
        if($aQuery['Content']) {            
            $sQueryOID = (( !empty($aOIDs) && !$aQuery['Objects'][1] )?'[@obj_id='.implode(' or @obj_id=',$aOIDs).']':'');
            $aOldOIDs  = $aOIDs;
            if($aQuery['Ordering']['content']) {
                $aElements = array();
                $aElementsExclude = array();
                foreach($aQuery['Content'][0] as $iTypeID) {
                	$aCurrElements = array();
                    $sProxy = 'objectcontent/'.$iTypeID.'.xml';
                    $oProxy = $XMLFactory->getProxy($sProxy);
                    $iItCnt = 0;
                    foreach($aQuery['Content'][2] as $sPath) {                
                        $sQuery = '/umi/values/value['.$sPath[0].']'.$sQueryOID;
                        $aTmp   = $oProxy->getElementsArray($sQuery, 'obj_id');                    
                        if($sPath[1]) {
                            $aElementsExclude = array_merge($aTmp, $aElementsExclude);
                        } else {
                            if(!$iItCnt) { 
                            	$aCurrElements = $aTmp;
							} else {
                                if($aQuery['Content'][1])
                                    $aCurrElements = array_intersect($aCurrElements, $aTmp);
                                else
                                    $aCurrElements = array_merge($aCurrElements, $aTmp);
							}
                        }
                        $iItCnt++;
                    }
                    $aElements = array_merge($aElements, $aCurrElements);
                }
                $aOIDs     = umiSelectionsParser::sortItems($aElements, $aQuery['Ordering']['content']);
                $aOIDsExc  = array();
                if(!empty($aElementsExclude)) foreach($aElementsExclude as $e) $aOIDsExc[] = $e->id;                
                $aOIDs     = array_diff($aOIDs, $aOIDsExc);                
            } else {
                $aOIDs = array();
                foreach($aQuery['Content'][0] as $iTypeID) {
                	$aCurrOIDs = array();
                    $sProxy = 'objectcontent/'.$iTypeID.'.xml';
                    $oProxy = $XMLFactory->getProxy($sProxy);
                    $iItCnt = 0;
                    foreach($aQuery['Content'][2] as $sPath) {                
                        $sQuery = '/umi/values/value['.$sPath[0].']'.$sQueryOID;
                        $aTmp   = $oProxy->getAttributeValue($sQuery, 'obj_id', true);
                        if($sPath[1]) $aTmp = array_diff($aOldOIDs, $aTmp);
                        if(!$iItCnt) {
                        	$aCurrOIDs = $aTmp;
						} else {
                            if($aQuery['Content'][1])
                                $aCurrOIDs = array_intersect($aCurrOIDs, $aTmp);
                            else
                                $aCurrOIDs = array_merge($aCurrOIDs, $aTmp);
						}
						$iItCnt++;
                    }
                    $aOIDs = array_merge($aOIDs, $aCurrOIDs);
                }                
            }
            if($aQuery['Objects'][1]) {
            	$aResultIDs = array_unique(array_merge($aOIDs, $aResultIDs));
            	$aOIDs      = $aResultIDs;
			} else {
            	$aResultIDs = $aOIDs;
			}
            if(empty($aResultIDs)) {
        		$selectionObject->count  = 0;
        		$selectionObject->result = array();
        		return array();
			}
        }
        if($aQuery['Hierarchy']) {
            $sQuery     = $aQuery['Hierarchy'] . (( !empty($aOIDs) ) ? '[@obj_id=' . implode(' or @obj_id=', $aOIDs) . ']' : '' );
            if($aQuery['Ordering']['hierarchy']) {
                $aElements = $XMLFactory->getProxy('hierarchy.xml')->getElementsArray($sQuery);
                $aEIDs     = umiSelectionsParser::sortItems($aElements, $aQuery['Ordering']['hierarchy']);
            } else if(!empty($aOIDs)) {
            	$aElements = $XMLFactory->getProxy('hierarchy.xml')->getElementsArray($sQuery);
                $aEIDs     = umiSelectionsParser::arrangeElements($aElements, $aOIDs, 'obj_id');
            } else {
                $aEIDs = $XMLFactory->getProxy('hierarchy.xml')->getAttributeValue($sQuery, 'id', true);            
            }
            if($selectionObject->excludeNestedPages) {
            	$proxy = $XMLFactory->getProxy('hierarchy.xml');
            	$tmp   = array();
            	foreach($aEIDs as $elementID) {
            		$rel = $proxy->getAttributeValue('/umi/elements/element[@id='.$elementID.']', 'rel');
            		if(!in_array($rel, $aEIDs)) $tmp[] = $elementID;
            	}
            	$aEIDs = $tmp;
            }
            $aResultIDs = $aEIDs;
            if(empty($aResultIDs)) {
        		$selectionObject->count  = 0;
        		$selectionObject->result = array();
        		return array();
			}
        }
        if($aQuery['Permissions'] && !empty($aEIDs)) {
            $sQuery = $aQuery['Permissions'];
            $aEIDs  = array();
            foreach($aResultIDs as $iEID) {
                if($XMLFactory->getProxy('eperms/'.$iEID.'.xml')->getCount($sQuery))
                    $aEIDs[] = $iEID;
            }            
            $aResultIDs  = $aEIDs;
        }
        $selectionObject->count  = count($aResultIDs);
        if($aQuery['Limitation'] !== false) {
            $aResultIDs = array_slice($aResultIDs, $aQuery['Limitation']['start'], $aQuery['Limitation']['count']);
        }
        $selectionObject->result = $aResultIDs;                
        return $aResultIDs;
    }
    /**
    * @desc Counts selected objects
    * @param umiSelection $selectionObject Selection criteria
    * @return Int count of selected hierarchy elements
    */
    public static function runSelectionCounts(umiSelection $selectionObject) {
        if($selectionObject->count === false) umiSelectionsParser::runSelection($selectionObject);
        return $selectionObject->count;        
    }
    /**
    * @desc Makes XPath condition from array of values
    * @param String
    * @param Array
    * @param String
    * @return String
    */
    private static function makeXPathFromArray($_sField, $_aValues, $_sFunction=false) {
        $sResultXPath = '';
        if(is_array($_aValues) && !empty($_aValues)) {
            if($_sFunction)
                array_map($_sFunction, $_aValues);
            $sResultXPath = '['.$_sField.'=' . implode(' or '.$_sField.'=', $_aValues) . ']';
        }
        return $sResultXPath;
    }
    /**
    * @desc Makes limitations
    * @param umiSelection $selectionObject Selection criteria 
    * @return Array 
    */
    private static function makeLimitations(umiSelection $selectionObject) {
        $aResultLimit = false;
        $Conditions = $selectionObject->getLimitConds();
        if($Conditions !== false) {            
            if (is_array($Conditions) && count($Conditions) > 1 && is_numeric($Conditions[0]) && is_numeric($Conditions[1])) {
                $aResultLimit          = array();
                $aResultLimit['start'] = intval($Conditions[0] * $Conditions[1]);
                $aResultLimit['count'] = intval($Conditions[0]);
            }
        }
        return $aResultLimit;
    }
    /**
    * @desc Makes order conditions
    * @param umiSelection $selectionObject Selection criteria 
    * @return Array 
    */
    private static function makeOrdering(umiSelection $selectionObject) {
        $aResult    = array('hierarchy'=>false, 'object'=>false, 'content'=>false);
        $Conditions = $selectionObject->getOrderConds();
        if(is_array($Conditions) && !empty($Conditions)) {
            foreach($Conditions as $Cond) {                
                if($Field = $Cond['native_field']) {                    
                    $aOrder = array(false, false, false);
                    switch($Field) {
                        case 'name':      $aOrder[1] = 'name'; break;
                        case 'object_id': $aOrder[1] = 'id';   break;
                        case 'rand':      $aOrder[1] = false;  break;
                    }
                    $aOrder[2] = (bool)$Cond['asc'];
                    $aResult['object'][] = $aOrder;
                } else {
                    $aResult['content'][] = array($Cond['field_id'], $Cond['type'], (bool)$Cond['asc']);
                }
            }
        }
        if($aResult['object'] === false && $aResult['content'] === false) {
            $aResult['hierarchy'] = array( array(false, 'ord', true) );
        }
        return $aResult;
    }    
    /**
    * @desc Sorts the elements and return array of ids
    * @param Array $_aElements array of element proxy
    * @param Array $_aSortingCriteria
    * @return Array 
    */    
    private static function sortItems($_aElements, $_aSortingCriteria) {
        if(!is_array($_aElements) || empty($_aElements) || !is_array($_aSortingCriteria) || empty($_aSortingCriteria)) return array();
        $aTable = array();        
        foreach($_aElements as $oElement) {
        	$tmp = $oElement->getAttributesList();
        	$tmp['#_v'] = $oElement->getValue();      	
            $aTable[]   = $tmp;
        }
        $DefaultIdField = true;
        foreach($_aSortingCriteria as $Criteria) {
            $DefaultIdField = $DefaultIdField & ($Criteria[0] === false);
            $sDesisionPath = "if(\$a['field_id']==".$Criteria[0]." && \$b['field_id']!=".$Criteria[0].") return -1;".
                             "if(\$a['field_id']!=".$Criteria[0]." && \$b['field_id']==".$Criteria[0].") return 1;".
                             "if(\$a['field_id']!=".$Criteria[0]." && \$b['field_id']!=".$Criteria[0].") return 0;".
                             "if(\$a['field_id']==".$Criteria[0]." && \$b['field_id']==".$Criteria[0].")";
            if(!in_array($Criteria[1], array('rand', 'ord', 'obj_id')))
            	$sFunctionCode =  (($Criteria[0] !== false)?$sDesisionPath:'').
                              "return (".($Criteria[2]?'':'-')."strnatcasecmp(\$a['#_v'], \$b['#_v']) );";
            else
            	$sFunctionCode =  (($Criteria[0] !== false)?$sDesisionPath:'').
                              "return (".($Criteria[2]?'':'-')."strnatcasecmp(\$a['".$Criteria[1]."'], \$b['".$Criteria[1]."']) );";
            usort($aTable, create_function('$a, $b', $sFunctionCode));
        }
        $aReturn = array();
        foreach($aTable as $aRow) $aReturn[] = $DefaultIdField ? $aRow['id'] : $aRow['obj_id'];
        return $aReturn;
    }
    /**
    * @desc Arranges Elements by array    
    * @param Array of ElementProxy
    * @param Array of Int
    * @param String
    * @return Array
    */
    private static function arrangeElements($_aElements, $_aArrangeArray, $_sArrangeField) {
    	if(!is_array($_aElements) || empty($_aElements) || !is_array($_aArrangeArray) || empty($_aArrangeArray)) return array();
    	$Result = array();
    	foreach($_aArrangeArray as $ArrangeValue) {
    		if(!empty($_aElements))
    		foreach($_aElements as $i=>$Element) {
    			if($Element->getAttribute($_sArrangeField) == $ArrangeValue) {
    				$Result[] = $Element->id;
    				unset($_aElements[$i]);
    				break;    				
    			}    			
    		}
    	}
    	return $Result;
    }
    /**
    * @desc Makes object ids selection query
    * @param umiSelection $selectionObject Selection criteria
    * @return String
    */
    private static function makeObjectQuery(umiSelection $selectionObject) {
    	$bMergeIDs     = false;
        $sResultQuery  = '';
        $sResultQuery .= umiSelectionsParser::makeXPathFromArray('@id',       $selectionObject->getObjectsConds(),    'intval');
        $sResultQuery .= umiSelectionsParser::makeXPathFromArray('@owner_id', $selectionObject->getOwnerConds(),      'intval');
        $sResultQuery .= umiSelectionsParser::makeXPathFromArray('@type_id',  $selectionObject->getObjectTypeConds(), 'intval');
        $Conditions    = $selectionObject->getNameConds();
        $strings       = $selectionObject->getSearchStrings();
        if(!empty($strings)) {
        	foreach($strings as $str) $Conditions[] = array('type'=>'like', 'value'=>$str);
        	$bMergeIDs = true;
		}
        if(is_array($Conditions) && !empty($Conditions)) {
            $i = 0;
            //if(strlen($sResultQuery)) $sResultQuery .= ' and ';
            $sResultQuery .= '[';
            foreach($Conditions as $Cond) {
                if($Cond['type'] == 'exact')
                    $sResultQuery .= (($i++)?' or ':'') . "text()='". $Cond['value'] . "'";
                else
                    $sResultQuery .= (($i++)?' or ':'') . "contains( translate(text(), '".umiSelectionsParser::UPCASE_FROM."', '".umiSelectionsParser::UPCASE_TO."'), translate('". ($Cond['value']) . "', '".umiSelectionsParser::UPCASE_FROM."', '".umiSelectionsParser::UPCASE_TO."'))";
            }
            $sResultQuery .= ']';
        }
        return (strlen($sResultQuery)) ? array('/umi/objects/object'.$sResultQuery, $bMergeIDs) : false;
    }
    /**
    * @desc Makes hierarchy elements ids selection query
    * @param umiSelection $selectionObject Selection criteria
    * @return String
    */
    private static function makeHierarchyQuery(umiSelection $selectionObject) {
    	$rel = $selectionObject->getHierarchyConds();
        $sResultQuery = '';
        $sResultQuery .= umiSelectionsParser::makeXPathFromArray('@id',      $selectionObject->getElementsConds(),    'intval');
        //$sResultQuery .= umiSelectionsParser::makeXPathFromArray('@rel',     isset($rel[0]) ? $rel[0] : array(),      'intval');
        $sResultQuery .= umiSelectionsParser::makeXPathFromArray('@type_id', $selectionObject->getElementTypeConds(), 'intval');
        if(is_array($rel) && !empty($rel)) {
        	$sResultQuery .= '[';
        	$c = false;
	        foreach($rel as $pid) {
	        	$sResultQuery .= ( $c ? ' or ' : '' ).'@rel='.$pid[0];
	        	$c = true;        		
	        }
	        $sResultQuery .= ']';
		}
        if(strlen($sResultQuery) || $selectionObject->getForceCond()) {
            $sResultQuery .= '[@lang_id='.cmsController::getInstance()->getCurrentLang()->getId().']';
            if(!$selectionObject->getIsDomainIgnored()) {
            	if($domainId = $selectionObject->getDomainId())
            		$sResultQuery .= '[@domain_id='.$domainId.']';
            	else
                	$sResultQuery .= '[@domain_id='.cmsController::getInstance()->getCurrentDomain()->getId().']';
			}
            if ($ActiveCond = $selectionObject->getActiveConds()) {
                $isActive      = (isset($ActiveCond[0]) && (bool) $ActiveCond[0])? 1 : 0;
                $sResultQuery .= '[@is_active='.$isActive.']';
            } else {
                $sResultQuery .= ( !strlen( cmsController::getInstance()->getCurrentMode() ) ) ? '[@is_active=1]' : '';
            }                                    
        }        
        return (strlen($sResultQuery)) ? '/umi/elements/element'.$sResultQuery.'[@is_deleted=0]' : false;
    }
    /**
    * @desc Makes hierarchy elements ids selection by permissions query
    * @param umiSelection $selectionObject Selection criteria
    * @return String
    */
    private static function makePermissionQuery(umiSelection $selectionObject) {
        $sResultQuery = '';
        $Conditions   = $selectionObject->getPermissionsConds();
        if(is_array($Conditions) && !empty($Conditions)) {
             array_map('intval', $Conditions);
             $Conditions[] = 2373;
             $sResultQuery = " and (@owner=" . implode(" or @owner=", $Conditions) . ")";
        }
        return (strlen($sResultQuery)) ? 
                '/umi/eperms/perm[text()>=1'.$sResultQuery.']' : false;
    }
    /**
    * @desc Makes objects content filter query
    * @param umiSelection $selectionObject Selection criteria
    * @return Array
    */
    private static function makeContentQuery(umiSelection $selectionObject) {        
        $aResult    = array();
        $iTypeID    = false;
        $sQueryGlue = true;
        $Conditions = $selectionObject->getPropertyConds();
        $strings = $selectionObject->getSearchStrings();
        if(!empty($strings))
        	$Conditions[] = array('type'=>true,'filter_type'=>'', 'value'=>$strings);
        $Orders  = $selectionObject->getOrderConds();
        if(is_array($Orders) && !empty($Orders)) {
            foreach($Orders as $Ord) {                
                if(!$Ord['native_field']) {                
                    $Conditions[] = array('field_id'=>$Ord['field_id'], 'filter_type'=>'exists', 'type'=>$Ord['type']);
                }
            }
        }
        if(is_array($Conditions) &&  !empty($Conditions)) {
            if($selectionObject->getConditionModeOr()) $sQueryGlue = false;
            foreach($Conditions as $Cond) {
                if($Cond['type'] === false) continue;
                $Query = array();
                switch($Cond['filter_type']) {
                    case 'equal':
                            if(!is_array($Cond['value'])) $Cond['value'] = array($Cond['value']);
                            array_map(NULL, $Cond['value']);
                            $Query[0] = "(@field_id=" . $Cond['field_id'] . " and (text()='" . implode("' or text()='" , $Cond['value']) . "') )";
                            $Query[1] = false;
                        break;
                    case 'not_equal':
                            if(!is_array($Cond['value'])) $Cond['value'] = array($Cond['value']);
                            array_map(NULL, $Cond['value']);
                            $Query[0] = "(@field_id=" . $Cond['field_id'] . " and not(text()='" . implode("') and not(text()='" , $Cond['value']) . "') )";
                            $Query[1] = false;
                        break;
                    case 'like':
                            $Query[0] = '(@field_id=' . $Cond['field_id'] . " and contains(text(), '" . ($Cond['value']) . "') )";
                            $Query[1] = false;
                        break;
                    case 'between':
                            $Query[0] = '(@field_id='.$Cond['field_id'].' and text()>='.((float)$Cond['min']).' and text()<='.((float)$Cond['max']) . ')';
                            $Query[1] = false;
                        break;
                    case 'more':
                            $Query[0] = '(@field_id=' . $Cond['field_id'] . ' and text()>' . ((float)$Cond['value']) . ')';
                            $Query[1] = false;
                        break;
                    case 'less':
                            $Query[0] = '(@field_id=' . $Cond['field_id'] . ' and text()<' . ((float)$Cond['value']) . ')';
                            $Query[1] = false;
                        break;
                    case 'null':
                            $Query[0] = '(@field_id=' . $Cond['field_id'] . ')';
                            $Query[1] = true;
                        break;
                    case 'exists':
                            $Query[0] = '(@field_id=' . $Cond['field_id'] . ')';
                            $Query[1] = false;
                        break;
                    default:                    		
                    		$sQueryGlue = false;
                    		$Query[0]   = "contains(text(), '".implode("') or contains(text(), '", $Cond['value'])."')";
                    		$Query[1]   = false;
                    	break;
                } // switch($Cond...
                if($iTypeID === false) {
                    $iTypeID  = array();
                    if($aHTypes = $selectionObject->getElementTypeConds()) {                        
                    	$oProxy = XMLFactory::getInstance()->getProxy('objectsdef.xml');
                        foreach($aHTypes as $iHType)
                            $iTypeID = array_merge($iTypeID, $oProxy->getAttributeValue('/umi/objecttypes/objecttype[@hierarchy_type_id='.$iHType.']', 'id', true));
                    } else if($aTypes = $selectionObject->getObjectTypeConds()){
                        $iTypeID = $aTypes;                            
                    } else {
                        $oProxy    = XMLFactory::getInstance()->getProxy('objectsdef.xml');
                        $aGroupIds = $oProxy->getAttributeValue('/umi/fieldcontroller/conn[@field_id=' . (isset($Cond['field_id']) ? $Cond['field_id'] : '22') . ']', 'group_id', true);
                        foreach($aGroupIds as $groupId)
                        	$iTypeID[] = $oProxy->getAttributeValue('/umi/fieldgroups/fieldgroup[@id='.$groupId.']', 'type_id');
                    }
                }
                if(strlen($Query[0])) $aResult[] = $Query;
            } // foreach($Conditions...
        } // if(is_array...
        return (!empty($aResult)) ? array($iTypeID, $sQueryGlue, $aResult) : false;
    }
}


	interface iUmiFieldType {
		public function getName();
		public function setName($name);

		public function getIsMultiple();
		public function setIsMultiple($isMultiple);

		public function getIsUnsigned();
		public function setIsUnsigned($isUnsigned);

		public function getDataType();
		public function setDataType($dataTypeStr);

		public static function getDataTypes();
		public static function getDataTypeDB($dataType);
		public static function isValidDataType($dataTypeStr);
	}


	class umiFieldType extends umiEntinty implements iUmiEntinty, iUmiFieldType {
		private $name, $data_type, $is_multiple = false, $is_unsigned = false;
		protected $store_type = "field_type";

		public function getName() {
			return $this->translateLabel($this->name);
		}

		public function getIsMultiple() {
			return $this->is_multiple;
		}

		public function getIsUnsigned() {
			return $this->is_multiple;
		}

		public function getDataType() {
			return $this->data_type;
		}


		public function setName($name) {
            $name = $this->translateI18n($name, "field-type-");
			$this->name = $name;
			$this->setIsUpdated();
		}

		public function setIsMultiple($is_multiple) {
			$this->is_multiple = (bool) $is_multiple;
			$this->setIsUpdated();
		}

		public function setIsUnsigned($is_unsigned) {
			$this->is_unsigned = (bool) $is_unsigned;
			$this->setIsUpdated();
		}

		public function setDataType($data_type) {
			if(self::isValidDataType($data_type)) {
				$this->data_type = $data_type;
				$this->setIsUpdated();
				return true;
			} else {
				return false;
			}
		}

		public static function getDataTypes() {
			return

			Array	(
				"int",
				"string",
				"text",
				"relation",
				"file",
				"img_file",
				"swf_file",
				"date",
				"boolean",
				"wysiwyg",
				"password",
				"tags",
				"symlink",
				"price",
				"formula",
				"float"
				);
		}

		public static function getDataTypeDB($data_type) {
			$rels = Array	(
				"int"		=> "int_val",
				"string"	=> "varchar_val",
				"text"		=> "text_val",
				"relation"	=> "rel_val",
				"file"		=> "varchar_val",
				"img_file"	=> "varchar_val",
				"swf_file"	=> "varchar_val",
				"date"		=> "int_val",
				"boolean"	=> "int_val",
				"wysiwyg"	=> "text_val",
				"password"	=> "varchar_val",
				"tags"		=> "varchar_val",
				"symlink"	=> "tree_val",
				"price"		=> "float_val",
				"formula"	=> "varchar_val",
				"float"		=> "float_val"
				);

			if(array_key_exists($data_type, $rels) === false) {
				return false;
			} else {
				return $rels[$data_type];
			}
		}

		public static function isValidDataType($data_type) {
			return in_array($data_type, self::getDataTypes());
		}


		protected function loadInfo($row = false) {
			if($row === false) {
                $oType = XMLFactory::getInstance()->getProxy('objectsdef.xml')->getElement('/umi/fieldtypes/fieldtype[@id='.$this->id.']');
                $this->name        = $oType->getValue();
                $this->data_type   = $oType->data_type;
                $this->is_multiple = (bool) $oType->is_multiple;
                $this->is_unsigned = (bool) $oType->is_unsigned;
				return true;
			}
			if(list($id, $name, $data_type, $is_multiple, $is_unsigned) = $row) {
				if(!self::isValidDataType($data_type)) {
					throw new coreException("Wrong data type given for filed type #{$this->id}");
					return false;
				}

				$this->name = $name;
				$this->data_type = $data_type;
				$this->is_multiple= (bool) $is_multiple;
				$this->is_unsigned = (bool) $is_unsigned;

				return true;
			} else {
				return false;
			}
		}

		protected function save() {
			$name = ($this->name);
			$data_type = ($this->data_type);
			$is_multiple = (int) $this->is_multiple;
			$is_unsigned = (int) $this->is_unsigned;            
            $oType = XMLFactory::getInstance()->getProxy('objectsdef.xml')->getElement('/umi/fieldtypes/fieldtype[@id='.$this->id.']');
            $oType->setValue($name);
            $oType->data_type   = $data_type;
            $oType->is_multiple = $is_multiple;
            $oType->is_unsigned = $is_unsigned;			
		}
	}


	interface iUmiField {
		public function getName();
		public function setName($name);

		public function getTitle();
		public function setTitle($title);

		public function getIsLocked();
		public function setIsLocked($isLocked);

		public function getIsInheritable();
		public function setIsInheritable($isInheritable);

		public function getIsVisible();
		public function setIsVisible($isVisible);

		public function getFieldTypeId();
		public function setFieldTypeId($fieldTypeId);

		public function getFieldType();

		public function getGuideId();
		public function setGuideId($guideId);

		public function getIsInSearch();
		public function setIsInSearch($isInSearch);

		public function getIsInFilter();
		public function setIsInFilter($isInFilter);

		public function getTip();
		public function setTip($tip);
		
		public function getIsRequired();
		public function setIsRequired($isRequired = false);
	}


	class umiField extends umiEntinty implements iUmiEntinty, iUmiField {
		private	$name, $title, $is_locked = false, $is_inheritable = false, $is_visible = true, $field_type_id, $guide_id;
		private $is_in_search = true, $is_in_filter = true, $tip = NULL;
		protected $store_type = "field";


		public function getName() {
			return $this->name;
		}

		public function getTitle() {
			return $this->translateLabel($this->title);
		}

		public function getIsLocked() {
			return $this->is_locked;
		}

		public function getIsInheritable() {
			return $this->is_inheritable;
		}

		public function getIsVisible() {
			return $this->is_visible;
		}

		public function getFieldTypeId() {
			return $this->field_type_id;
		}

		public function getFieldType() {
			return umiFieldTypesCollection::getInstance()->getFieldType($this->field_type_id);
		}

		public function getGuideId() {
			return $this->guide_id;
		}

		public function getIsInSearch() {
			return $this->is_in_search;
		}

		public function getIsInFilter() {
			return $this->is_in_filter;
		}

		public function getTip() {
			return $this->tip;
		}


		public function setName($name) {
			$name = str_replace("-", "_", $name);
			$name = umiHierarchy::convertAltName($name);
			$this->name = umiObjectProperty::filterInputString($name);
			$this->setIsUpdated();
		}

		public function setTitle($title) {
			$title = $this->translateI18n($title, "field-");
			$this->title = umiObjectProperty::filterInputString($title);
			$this->setIsUpdated();
		}

		public function setIsLocked($is_locked) {
			$this->is_locked = (bool) $is_locked;
			$this->setIsUpdated();
		}

		public function setIsInheritable($is_inheritable) {
			$this->is_inheritable = (bool) $is_inheritable;
			$this->setIsUpdated();
		}

		public function setIsVisible($is_visible) {
			$this->is_visible = (bool) $is_visible;
			$this->setIsUpdated();
		}

		public function setFieldTypeId($field_type_id) {
			$this->field_type_id = (int) $field_type_id;
			$this->setIsUpdated();
			return true;
		}

		public function setGuideId($guide_id) {
			$this->guide_id = (int) $guide_id;
			$this->setIsUpdated();
		}

		public function setIsInSearch($is_in_search) {
			$this->is_in_search = (bool) $is_in_search;
			$this->setIsUpdated();
		}

		public function setIsInFilter($is_in_filter) {
			$this->is_in_filter = (bool) $is_in_filter;
			$this->setIsUpdated();
		}

		public function setTip($tip) {
			$this->tip = umiObjectProperty::filterInputString($tip);
			$this->setIsUpdated();
		}


		protected function loadInfo($row = false) {
			if($row === false) {
				$oXMLField = XMLFactory::getInstance()->getProxy("objectsdef.xml")->getElement("/umi/fields/field[@id=".$this->id."]");
				$this->name           = $oXMLField->name;
				$this->title          = $oXMLField->getValue();
				$this->is_locked      = (bool)   $oXMLField->is_locked;
				$this->is_inheritable = (bool)   $oXMLField->is_inheritable;
				$this->is_visible     = (bool)   $oXMLField->is_visible;
				$this->field_type_id  = (int)    $oXMLField->field_type_id;
				$this->guide_id       = (int)    $oXMLField->guide_id;
				$this->is_in_search   = (bool)   $oXMLField->in_search;
				$this->is_in_filter   = (bool)   $oXMLField->in_filter;
				$this->tip            = (string) $oXMLField->tip;                
				return true;                				
			}

			if(list($id, $name, $title, $is_locked, $is_inheritable, $is_visible, $field_type_id, $guide_id, $in_search, $in_filter, $tip) = $row) {
				$this->name = $name;
				$this->title = $title;
				$this->is_locked = (bool) $is_locked;
				$this->is_inheritable = (bool) $is_inheritable;
				$this->is_visible = (bool) $is_visible;
				$this->field_type_id = (int) $field_type_id;
				$this->guide_id = (int) $guide_id;
				$this->is_in_search = (bool) $in_search;
				$this->is_in_filter = (bool) $in_filter;
				$this->tip = (string) $tip;
			} else {
				return false;
			}
		}

		protected function save() {
			$name = ($this->name);
			$title = ($this->title);
			$is_locked = (int) $this->is_locked;
			$is_inheritable = (int) $this->is_inheritable;
			$is_visible = (int) $this->is_visible;
			$field_type_id = (int) $this->field_type_id;
			$guide_id = (int) $this->guide_id;
			$in_search = (int) $this->is_in_search;
			$in_filter = (int) $this->is_in_filter;
			$tip = (string) $this->tip;

			$Field = XMLFactory::getInstance()->getProxy("objectsdef.xml")->getElement("/umi/fields/field[@id=".$this->id."]");
			$Field->setValue($title);
			$Field->name           = $name;
			$Field->is_locked      = $is_locked;
			$Field->is_inheritable = $is_inheritable;
			$Field->is_visible     = $is_visible;
			$Field->field_type_id  = $field_type_id;
			$Field->guide_id       = $guide_id;
			$Field->in_search      = $in_search;
			$Field->in_filter      = $in_filter;
			$Field->tip            = $tip;
			return true;
		}		
		public function getIsRequired() {
			// ToDo: implement
			return false;
		}
		public function setIsRequired($isRequired = false) {
			// ToDo: implement
			return;
		}
	}


	interface iUmiFieldsGroup {
		public function getName();
		public function setName($name);

		public function getTitle();
		public function setTitle($title);

		public function getTypeId();
		public function setTypeId($typeId);

		public function getOrd();
		public function setOrd($ord);

		public function getIsActive();
		public function setIsActive($isActive);

		public function getIsVisible();
		public function setIsVisible($isVisible);

		public function getIsLocked();
		public function setIsLocked($isLocked);

		public function getFields();

		public function attachField($fieldId);
		public function detachField($fieldId);

		public function moveFieldAfter($fieldId, $beforeFieldId, $group_id, $is_last);
	}


	class umiFieldsGroup extends umiEntinty implements iUmiEntinty, iUmiFieldsGroup {
		private	$name, $title,
			$type_id, $ord,
			$is_active = true, $is_visible = true, $is_locked = false,

			$autoload_fields = false,

			$fields = Array();
			
		protected $store_type = "fields_group";


		public function getName() {
			return $this->name;
		}

		public function getTitle() {
			return $this->translateLabel($this->title);
		}

		public function getTypeId() {
			return $this->type_id;
		}

		public function getOrd() {
			return $this->ord;
		}

		public function getIsActive() {
			return $this->is_active;
		}

		public function getIsVisible() {
			return $this->is_visible;
		}

		public function getIsLocked() {
			return $this->is_locked;
		}


		public function setName($name) {
			$this->name = umiObjectProperty::filterInputString($name);
			$this->setIsUpdated();
		}

		public function setTitle($title) {
			$title = $this->translateI18n($title, "fields-group");
			$this->title = umiObjectProperty::filterInputString($title);
			$this->setIsUpdated();
		}

		public function setTypeId($type_id) {
			$types = umiObjectTypesCollection::getInstance();
			if($types->isExists($type_id)) {
				$this->type_id = $type_id;
				return true;
			} else {
				return false;
			}
		}

		public function setOrd($ord) {
			$this->ord = $ord;
			$this->setIsUpdated();
		}

		public function setIsActive($is_active) {
			$this->is_active = (bool) $is_active;
			$this->setIsUpdated();
		}

		public function setIsVisible($is_visible) {
			$this->is_visible = (bool) $is_visible;
			$this->setIsUpdated();
		}

		public function setIsLocked($is_locked) {
			$this->is_locked = (bool) $is_locked;
			$this->setIsUpdated();
		}


		protected function loadInfo($row = false) {
			if($row === false) {				
				$Group = XMLFactory::getInstance()->getProxy('objectsdef.xml')->getElement('/umi/fieldgroups/fieldgroup[@id='.$this->id.']');
				$this->name       = $Group->name;
				$this->title      = $Group->getValue();
				$this->type_id    = $Group->type_id;
				$this->is_active  = (bool) $Group->is_active;
				$this->is_visible = (bool) $Group->is_visible;
				$this->is_locked  = (bool) $Group->is_locked;
				$this->ord        = (int)  $Group->ord;
				return true;                
			}

			if(list($id, $name, $title, $type_id, $is_active, $is_visible, $is_locked, $ord) = $row) {
				if(!umiObjectTypesCollection::getInstance()->isExists($type_id)) {
					return false;
				}

				$this->name = $name;
				$this->title = $title;
				$this->type_id = $type_id;
				$this->is_active = (bool) $is_active;
				$this->is_visible = (bool) $is_visible;
				$this->is_locked = (bool) $is_locked;
				$this->ord = (int) $ord;

				if($this->autoload_fields) {
					return $this->loadFields();
				} else {
					return true;
				}
			} else {
				return false;
			}
		}


		protected function save() {
			$Group = XMLFactory::getInstance()->getProxy('objectsdef.xml')->getElement('/umi/fieldgroups/fieldgroup[@id='.$this->id.']'); 
			$Group->setValue(($this->title));            
			$Group->name        = ($this->name);			
			$Group->type_id     = (int) $this->type_id;
			$Group->is_active   = (int) $this->is_active;
			$Group->is_visible  = (int) $this->is_visible;
			$Group->ord         = (int) $this->ord;
			$Group->is_locked   = (int) $this->is_locked;
			return true;
		}

		public function loadFields($rows = false) {
			$fields = umiFieldsCollection::getInstance();
//$rows = false;
			if($rows === false) {
				$oProxy = XMLFactory::getInstance()->getProxy('objectsdef.xml');
				$aConn  = $oProxy->getElementsArray('/umi/fieldcontroller/conn[@group_id='.$this->id.']');
				$sXPath = '/umi/fields/field[@id=';
				$i = 0;
				foreach($aConn as $Connection) {
					$sXPath .= (($i>0)?' or @id=':'').$Connection->getAttribute('field_id');
					$i++;
				}
				$sXPath  .= ']';
				$aFields = $oProxy->getElementsArray($sXPath);
				foreach($aFields as $oField) {                    
					$row = array($oField->id, $oField->name, $oField->getValue(), $oField->is_locked, $oField->is_inheritable, $oField->is_visible, $oField->field_type_id, $oField->guide_id, $oField->in_search, $oField->in_filter, $oField->tip);
					if($field = $fields->getField($oField->id, $row)) {
						$this->fields[$oField->id] = $field;
					}                    
				}
			} else {
				foreach($rows as $row) {
					list($field_id) = $row;
					if($field = $fields->getField($field_id, $row)) {
						$this->fields[$field_id] = $field;
					}
				}
			}
		}

		public function getFields() {
			return $this->fields;
		}

		private function isLoaded($field_id) {
			return (bool) array_key_exists($field_id, $this->fields);
		}

		public function attachField($field_id) {
			if($this->isLoaded($field_id)) {
				return true;
			} else {
				$field_id = (int) $field_id;
				
				$oProxy = XMLFactory::getInstance()->getProxy('objectsdef.xml');
				$ord    = $oProxy->getAttributeValue('/umi/fieldcontroller/conn[not(@ord <= preceding-sibling::conn/@ord) and not(@ord <=following-sibling::conn/@ord)][@group_id='.$this->id.']','ord');                
				$ord   += 5;
				$Conn   = $oProxy->addElement('/umi/fieldcontroller', 'conn', '');
				$Conn->ord      = $ord;
				$Conn->field_id = $field_id;
				$Conn->group_id = $this->id;                

				$fields = umiFieldsCollection::getInstance();
				$field = $fields->getField($field_id);
				$this->fields[$field_id] = $field;
				
				$this->fillInContentTable($field_id);
			}
		}
		
		
		protected function fillInContentTable($field_id) {
			$type_id = $this->type_id;		
			$aObjects = XMLFactory::getInstance()->getProxy('objects.xml')->getElementsArray('/umi/objects/object[@type_id='.$type_id.']');
			$oProxy   = XMLFactory::getInstance()->getProxy('objectcontent/'.$type_id.'.xml');
			foreach($aObjects as $Object) {
				$Element = $oProxy->addElement('/umi/values', 'value', '');
				$Element->obj_id   = $Object->id;
				$Element->field_id = $field_id;
			}		
		}

		public function detachField($field_id) {
			if($this->isLoaded($field_id)) {
				$field_id = (int) $field_id;
				$oProxy = XMLFactory::getInstance()->getProxy('objectsdef.xml');
				$oProxy->removeElement('/umi/fieldscontroller/conn[@field_id='.$field_id.' and @group_id='.$this->id.']');
				unset($this->fields[$field_id]);                
				$c = $oProxy->getCount('/umi/fieldscontroller/conn[@field_id='.$field_id.']');
				return ($c == 0) ? umiFieldsCollection::getInstance()->delField($field_id) : true;				
			} else {
				return false;
			}
		}

		public function moveFieldAfter($field_id, $after_field_id, $group_id, $is_last) {
			$oProxy = XMLFactory::getInstance()->getProxy('objectsdef.xml'); 
			if($after_field_id == 0) {
				$neword = 0;
			} else {
				$neword = $oProxy->getAttributeValue('/umi/fieldscontroller/conn[@field_id='.$after_field_id.' and @group_id='.$group_id.']','ord');		
			}

			if($is_last) {
				$aConn = $oProxy->getElementsArray('/umi/fieldscontroller/conn[@group_id='.$this->id.' and @ord>='.$neword.']');
				foreach($aConn as $Connection) $Connection->ord = $Connection->ord + 1;				
			} else {				
				$neword = $oProxy->getAttributeValue('/umi/fieldcontroller/conn[not(@ord <= preceding-sibling::conn/@ord) and not(@ord <=following-sibling::conn/@ord)][@group_id='.$group_id.']','ord');
				++$neword;
			}
			
			$Conn = $oProxy->getElement('/umi/fieldcontroller/conn[@field_id='.$field_id.' and @group_id='.$this->id.']');
			$Conn->ord      = $neword;
			$Conn->group_id = $group_id;
			return true;            
		}
		

		public function commit() {
			parent::commit();
			cacheFrontend::getInstance()->flush();
		}
	};



	interface iUmiObjectType {
		public function addFieldsGroup($name, $title, $isActive = true, $isVisible = true);
		public function delFieldsGroup($fieldGroupId);

		public function getFieldsGroupByName($fieldGroupName);

		public function getFieldsGroup($fieldGroupId);
		public function getFieldsGroupsList();

		public function getName();
		public function setName($name);

		public function setIsLocked($isLocked);
		public function getIsLocked();

		public function setIsGuidable($isGuidable);
		public function getIsGuidable();

		public function setIsPublic($isPublic);
		public function getIsPublic();

		public function setHierarchyTypeId($hierarchyTypeId);
		public function getHierarchyTypeId();

		public function getParentId();

		public function setFieldGroupOrd($groupId, $newOrd, $isLast);


		public function getFieldId($fieldName);

		public function getAllFields($returnOnlyVisibleFields = false);
		
		public function getModule();
		public function getMethod();
	}



	class umiObjectType extends umiEntinty implements iUmiEntinty, iUmiObjectType {
		private $name, $parent_id, $is_locked = false,
			$field_groups = Array(), $is_guidable = false, $is_public = false, $hierarchy_type_id;
		protected $store_type = "object_type";


		public function getName() {
			return $this->translateLabel($this->name);
		}

		public function setName($name) {
            $name = $this->translateI18n($name, "object-type-");
			$this->name = $name;
			$this->setIsUpdated();
		}

		public function getIsLocked() {
			return $this->is_locked;
		}


		public function setIsLocked($is_locked) {
			$this->is_locked = (bool) $is_locked;
			$this->setIsUpdated();
		}

		public function getParentId() {
			return $this->parent_id;
		}


		public function getIsGuidable() {
			return $this->is_guidable;
		}

		public function setIsGuidable($is_guidable) {
			$this->is_guidable = (bool) $is_guidable;
			$this->setIsUpdated();
		}

		public function getIsPublic() {
			return $this->is_public;
		}

		public function setIsPublic($is_public) {
			$this->is_public = (bool) $is_public;
			$this->setIsUpdated();
		}

		public function getHierarchyTypeId() {
			return $this->hierarchy_type_id;
		}

		public function setHierarchyTypeId($hierarchy_type_id) {
			$this->hierarchy_type_id = (int) $hierarchy_type_id;
			$this->setIsUpdated();
		}


		public function addFieldsGroup($name, $title, $is_active = true, $is_visible = true) {
			if($group = $this->getFieldsGroupByName($name)) {
				return $group->getId();
			}
			
            XMLFactory::createFile('objectcontent/'.$this->id.'.xml' , "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<umi>\n <values />\n</umi>");
				$oProxy = XMLFactory::getInstance()->getProxy('objectsdef.xml');
				$aNodes = $oProxy->getElementsArray('/umi/fieldgroups/fieldgroup[@type_id='.$this->id.']');
				$ord    = 1;            
				foreach($aNodes as $Node) {
					 if($ord < $Node->ord) $ord = $Node->ord;                 
				}
				if($ord > 1) $ord += 5;        
				
				$field_group_id = (int)$oProxy->getAttributeValue('/umi/fieldgroups/fieldgroup[not(@id <= preceding-sibling::field/@id) and not(@id <=following-sibling::fieldgroup/@id)]','id') + 1;
			$Element        = $oProxy->addElement('/umi/fieldgroups', 'fieldgroup', '');
				$Element->id    = $field_group_id;
				$Element->type_id = $this->id;
				$element->ord   = $ord;			

			$field_group = new umiFieldsGroup($field_group_id);
			$field_group->setName($name);
			$field_group->setTitle($title);
			$field_group->setIsActive($is_active);
			$field_group->setIsVisible($is_visible);
			$field_group->commit();

			$this->field_groups[$field_group_id] = $field_group;


			$child_types = umiObjectTypesCollection::getInstance()->getSubTypesList($this->id);
			$sz = sizeof($child_types);
			for($i = 0; $i < $sz; $i++) {
				$child_type_id = $child_types[$i];
					
				if($type = umiObjectTypesCollection::getInstance()->getType($child_type_id)) {
					$type->addFieldsGroup($name, $title, $is_active, $is_visible);
				} else {
					throw new coreException("Can't load object type #{$child_type_id}");
				}
			}


			return $field_group_id;
		}

		public function delFieldsGroup($field_group_id) {
			if($this->isFieldsGroupExists($field_group_id)) {
				$field_group_id = (int) $field_group_id;               
					 XMLFactory::getInstance()->getProxy('objectsdef.xml')->removeElement('/umi/fieldgroups/fieldgroup[@id='.$field_group_id.']');
				unset($this->field_groups[$field_group_id]);
				return true;
			} else {
				return false;
			}
		}

		public function getFieldsGroupByName($field_group_name) {
			$groups = $this->getFieldsGroupsList();
			foreach($groups as $group_id => $group) {
				if($group->getName()  == $field_group_name) {
					return $group;
				}
			}
			return false;
		}

		public function getFieldsGroup($field_group_id) {
			if($this->isFieldsGroupExists($field_group_id)) {
				return $this->field_groups[$field_group_id];
			} else {
				return false;
			}
		}

		public function getFieldsGroupsList() {
			return $this->field_groups;
		}


		private function isFieldsGroupExists($field_group_id) {
			if(!$field_group_id) {
				return false;
			} else {
				return (bool) array_key_exists($field_group_id, $this->field_groups);
			}
		}

		protected function loadInfo() {
				$Element = XMLFactory::getInstance()->getProxy('objectsdef.xml')->getElement('/umi/objecttypes/objecttype[@id='.$this->id.']');
				$this->name = $Element->name;
				$this->parent_id = (int) $Element->parent_id;
				$this->is_locked = (bool) $Element->is_locked;
				$this->is_guidable = (bool) $Element->is_guidable;
				$this->is_public = (bool) $Element->is_public;
				$this->hierarchy_type_id = (int) $Element->hierarchy_type_id;
				return $this->loadFieldsGroups();		
		}

		private function loadFieldsGroups() {
				$oProxy  = XMLFactory::getInstance()->getProxy('objectsdef.xml');
				$aGroups = $oProxy->getElementsArray('/umi/fieldgroups/fieldgroup[@type_id='.$this->id.']');
                @usort($aGroups, create_function('$a, $b', 'if($a->ord>$b->ord)return 1; else if($a->ord<$b->ord) return -1; else return 0;'));
				$sFieldsCondition = '';
				$i = 0;
				foreach($aGroups as $Group) {
					 $sFieldsCondition .= (($i++)?' or ':'').'@group_id='.$Group->getAttribute('id');
					 $group_id = $Group->id;
					 $row = array($group_id, $Group->name, $Group->getValue(), $Group->type_id, 
									  $Group->is_active, $Group->is_visible, $Group->is_locked, $Group->ord);
					 $field_group = new umiFieldsGroup($group_id, $row);                
					 $this->field_groups[$group_id] = $field_group;
				}
				
				if(!$sFieldsCondition) {
					return;
				}
				
				$aConn   = $oProxy->getElementsArray('/umi/fieldcontroller/conn['.$sFieldsCondition.']');
				$aFGConnetctions  = array();
				$sFieldsCondition = '';
				$i = 0;
				foreach($aConn as $Connection) {
					 $iFID = $Connection->getAttribute('field_id');
					 $aFGConnetctions[ $iFID ] = $Connection->getAttribute('group_id');
					 $sFieldsCondition .= (($i++)?' or ':'').'@id='.$iFID;
				}            
				$aFields = $oProxy->getElementsArray('/umi/fields/field['.$sFieldsCondition.']');
				$fields  = Array();
				foreach($aFields as $oField) {
					 $iFID     = $oField->id;
					 $group_id = $aFGConnetctions[ $iFID ];
					 
					 if(isset($fields[$group_id])) {
						 if(!is_array($fields[$group_id])) {
							  $fields[$group_id] = Array();
						 }
					}
					 $fields[$group_id][] = Array($iFID, $oField->name, $oField->getValue(), $oField->is_locked, $oField->is_inheritable, $oField->is_visible, 
															$oField->field_type_id, $oField->guide_id, $oField->in_search, $oField->in_filter, $oField->tip);                                            
				}
				foreach($this->field_groups as $group_id => $oGroup) {
					if (isset($fields[$group_id])) {
						$oGroup->loadFields($fields[$group_id]);
					}
				}
			return true;
		}

		protected function save() {
			$Element = XMLFactory::getInstance()->getProxy('objectsdef.xml')->getElement('/umi/objecttypes/objecttype[@id='.$this->id.']');
			if(!$Element->valid()) {
				$Element = XMLFactory::getInstance()->getProxy('objectsdef.xml')->addElement('/umi/objecttypes', 'objecttype', '');
				$Element->id = $this->id;				
			}
			$Element->name = umiObjectProperty::filterInputString($this->name);
			$Element->parent_id = (int) $this->parent_id;
			$Element->is_locked = (int) $this->is_locked;
			$Element->is_guidable = (int) $this->is_guidable;
			$Element->is_public = (int) $this->is_public;
			$Element->hierarchy_type_id = (int) $this->hierarchy_type_id;
            XMLFactory::createFile('objectcontent/'.$this->id.'.xml' , "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<umi>\n <values />\n</umi>");			
		}

		public function setFieldGroupOrd($group_id, $neword, $is_last) {            
			$neword = (int) $neword;
			$group_id = (int) $group_id;            
				$oProxy = XMLFactory::getInstance()->getProxy('objectsdef.xml');
			if(!$is_last) {               
					 $type_id = $oProxy->getAttributeValue('/umi/fieldgroups/fieldgroup[@id='.$group_id.']');                
					 $aElements = $oProxy->getElementsArray('/umi/fieldgroups/fieldgroup[@type_id='.$type_id.' and @ord>='.$neword.']');                                
					 foreach($aElements as $Element) $Element->ord = $Element->ord + 1;			
			}
			$oProxy->setAttributeValue('/umi/fieldgroups/fieldgorup[@id='.$group_id.']', 'ord', $neword);            
			return true;
		}


		public function getAllFields($returnOnlyVisibleFields = false) {
			$fields = Array();

			$groups = $this->getFieldsGroupsList();
			foreach($groups as $group) {
				if($returnOnlyVisibleFields) {
					if(!$group->getIsVisible()) {
						continue;
					}
				}

				$fields = array_merge($fields, $group->getFields());
			}

			return $fields;
		}

		public function getFieldId($field_name) {
			$groups = $this->getFieldsGroupsList();
			foreach($groups as $group_id => $group) {
				if(!$group->getIsActive()) continue;

				$fields = $group->getFields();

				foreach($fields as $field_id => $field) {
					if($field->getName() == $field_name) {
						return $field->getId();
					}
				}
			}
			return false;
		}
		
		public function getModule() {
			$hierarchyTypeId = $this->getHierarchyTypeId();
			$hierarchyType = umiHierarchyTypesCollection::getInstance()->getType($hierarchyTypeId);
			if($hierarchyType instanceof umiHierarchyType) {
				return $hierarchyType->getName();
			} else {
				return false;
			}
		}
		
		public function getMethod() {
			$hierarchyTypeId = $this->getHierarchyTypeId();
			$hierarchyType = umiHierarchyTypesCollection::getInstance()->getType($hierarchyTypeId);
			if($hierarchyType instanceof umiHierarchyType) {
				return $hierarchyType->getExt();
			} else {
				return false;
			}
		}
	}


	interface iUmiObjectProperty {
		public function getValue();
		public function setValue($value);
		public function resetValue();

		public function getName();
		public function getTitle();

		public function getIsMultiple();
		public function getIsUnsigned();
		public function getDataType();
		public function getIsLocked();
		public function getIsInheritable();
		public function getIsVisible();

		public static function filterOutputString($string);
		public static function filterCDATA($string);
		
		public function getObject();
		public function getField();
	}


	abstract class umiObjectProperty extends umiEntinty implements iUmiEntinty, iUmiObjectProperty {
		protected $object_id, $field_id, $field, $field_type, $store, 
		/**
		* @var XMLProxy
		*/
			$StoreProxy,
			$value = Array();

		public    $store_type = "property";
		public static $USE_FORCE_OBJECTS_CREATION = false;
		public static $IGNORE_FILTER_INPUT_STRING = false;

		public function __construct($id, $field_id) {
			$this->setId($id);
			$this->object_id = (int) $id;
			//$type_id     = XMLFactory::getInstance()->getProxy('objectcontent/objectmap.xml')->getAttributeValue('/umi/objectmap/entry[@oid='.$this->object_id.']', 'tid');            
			$type_id     = XMLFactory::getInstance()->getProxy('objects.xml')->getAttributeValue('/umi/objects/object[@id='.$this->object_id.']', 'type_id');
			$this->store = 'objectcontent/'.$type_id.'.xml';
			$this->StoreProxy  = XMLFactory::getInstance()->getProxy($this->store);            
			$this->field = umiFieldsCollection::getInstance()->getField($field_id);
			$this->field_id = $field_id;

			$this->is_updated = false;
			//
			$this->loadInfo();
		}
		
		public static function getProperty($id, $field_id, $type_id) {
			$className = self::getClassNameByFieldId($field_id);
			return new $className($id, $field_id, $type_id);
		}
	
		public function getId() {
			return $this->id . "." . $this->field_id;
		}

		public function getValue() {
			if($this->getIsMultiple() === false) {
				if(sizeof($this->value) > 0) {
					list($value) = $this->value;
				} else {
					return NULL;
				}
			} else {
				$value = $this->value;
			}
			return $value;
		}

		public function setValue($value) {
			if(!is_array($value)) {
				$value = Array($value);
			}
			// patch for 'date' datatype :
			$data_type = $this->getDataType();
			if ($data_type === 'date') {
				foreach ($value as $vKey=>$vVal) {
					if (!($vVal instanceof umiDate)) {
						$value[$vKey] = new umiDate(intval($vVal));
					}
				}
			}
			//
			$this->value = $value;
			$this->setIsUpdated();
			$this->setObjectIsUpdated();
		}

		public function resetValue() {
			$this->value = Array();
			$this->setIsUpdated();
		}

		public function getName() {
			return $this->field->getName();
		}

		public function getTitle() {
			return $this->field->getTitle();
		}


		protected function loadInfo() {
			$field = $this->field;
			$field_types = umiFieldTypesCollection::getInstance();

			$field_type_id = $field->getFieldTypeId();

			$field_type = $field_types->getFieldType($field_type_id);
			$this->field_type = $field_type;

			$this->value = $this->loadValue();
		}

		public function getIsMultiple() {
			return $this->field_type->getIsMultiple();
		}

		public function getIsUnsigned() {
			return $this->field_type->getIsUnsigned();
		}

		public function getDataType() {
			return $this->field_type->getDataType();
		}

		public function getIsLocked() {
			return $this->field->getIsLocked();
		}

		public function getIsInheritable() {
			return $this->field->getIsInheritable();
		}

		public function getIsVisible() {
			return $this->field->getIsVisible();
		}
        	

		protected function loadValue() {			
			$this->value = array();
		}
		
		protected function loadValueByTypeName($_sTypeName = '') {
			$aFields   = XMLFactory::getInstance()->getProxy($this->store)->getNodeTree('/umi/values/value[@obj_id='.$this->object_id.' and @field_id='.$this->field_id.']');
			$aValues   = array();
			if($this->getIsMultiple()) {
				if(!empty($aFields['value']))
				foreach($aFields['value'] as $aValue) {
					$Tmp = $aValue['@value'];
					if($Tmp == null) continue;
					$aValues[] = $Tmp;
				}
			} else {
				if(isset($aFields['value'])) {
					$aValues[] = $aFields['value'][0]['@value'];
				}
			}
			return $aValues;
		}
                
		protected function save() {
			return $this->saveValue();
		}
		
		protected function saveValue() {
			$this->deleteCurrentRows();
			foreach($this->value as $val) {				
				$this->saveValueByTypeName('', $val);                
			}
			$this->fillNull();
		}	
        				
        		
		protected function deleteCurrentRows() {
			if($this->StoreProxy instanceof XMLProxy)
				$this->StoreProxy->removeElement('/umi/values/value[@obj_id='.$this->object_id.' and @field_id='.$this->field_id.']');            
		}

		protected function saveValueByTypeName($_TypeName, $_Val) {            
			$Value = $this->StoreProxy->addElement('/umi/values','value','');
			$Value->setAttribute(  'obj_id',   $this->object_id);
			$Value->setAttribute('field_id',   $this->field_id);
			$Value->setValue($_Val);
			$this->StoreProxy->saveFile();
		}	        
        		        
        protected function fillNull($sDebugInfo = NULL) {
			return true;           
			$c = $this->StoreProxy->getCount('/umi/values/value[@obj_id='.$this->object_id.' and @field_id='.$this->field_id.']');
			if($c == 0) {
				$Value = $this->StoreProxy->addElement('/umi/values', 'value');
				$Value->obj_id   = $this->object_id;
				$Value->field_id = $this->field_id;                
			}
			return true;
		}
        
		public static function filterInputString($string) {
			$string = ($string);
			$string = umiObjectProperty::filterCDATA($string);
			
			if(cmsController::getInstance()->getCurrentMode() != "admin"  && !self::$IGNORE_FILTER_INPUT_STRING) {
				$string = str_replace("%", "&#37;", $string);
			}
			
			return $string;
		}

		public static function filterOutputString($string) {
			if(cmsController::getInstance()->getCurrentMode() == "admin") {
				$string = str_replace("%", "&#037;", $string);
			} else {
				$string = str_replace("&#037;", "%", $string);
			}
			return $string;
		}
		
		public static function filterCDATA($string) {
			$string = str_replace("]]>", "]]&gt;", $string);
			return $string;
		}
		
		protected function setObjectIsUpdated() {
			if($object = $this->getObject()) {
				$object->setIsUpdated();
				return true;
			} else {
				return false;
			}
		}
		
		public function getObject() {
			return umiObjectsCollection::getInstance()->getObject($this->object_id);
		}
		
		public function getObjectId() {
			return $this->object_id;
		}
		
		public function getField() {
			return $this->field;
		}
		
		protected static function getClassNameByFieldId($field_id) {
			static $cache = Array();
			if(isset($cache[$field_id])) {
				return $cache[$field_id];
			}
			
			$field = umiFieldsCollection::getInstance()->getField($field_id);
			$fieldTypeId = $field->getFieldTypeId();
			$fieldType = umiFieldTypesCollection::getInstance()->getFieldType($fieldTypeId);
			$fieldDataType = $fieldType->getDataType();
			
			$propertyClasses = Array(
				'relation' => 'umiObjectPropertyRelation',
				'wysiwyg' => 'umiObjectPropertyWYSIWYG',
				'string' => 'umiObjectPropertyString',
				'file' => 'umiObjectPropertyFile',
				'img_file' => 'umiObjectPropertyImgFile',
				'swf_file' => 'umiObjectPropertyImgFile',
				'boolean' => 'umiObjectPropertyBoolean',
				'int' => 'umiObjectPropertyInt',
				'text' => 'umiObjectPropertyText',
				'date' => 'umiObjectPropertyDate',
				'symlink' => 'umiObjectPropertySymlink',
				'price' => 'umiObjectPropertyPrice',
				'float' => 'umiObjectPropertyFloat',
				'tags' => 'umiObjectPropertyTags',
				'password' => 'umiObjectPropertyPassword'
			);
			
			if(isset($propertyClasses[$fieldDataType])) {
				return $cache[$field_id] = $propertyClasses[$fieldDataType];
			} else {
				throw new coreException("Unhandled field of type \"{$fieldDataType}\"");
			}
		}

		// ==== xxx ============================================================
		protected static function data_type_by_field_id($i_field_id) {
			$v_answer = 0;
			//
			$o_field = umiFieldsCollection::getInstance()->getField($i_field_id);
			// EXCEPTION coreException
			if (!($o_field instanceof umiField)) {
				$v_answer = -1;
			} else {
				$i_field_type_id = $o_field->getFieldTypeId();
				$o_field_type = umiFieldTypesCollection::getInstance()->getFieldType($i_field_type_id);
				// EXCEPTION coreException
				if (!($o_field_type instanceof umiFieldType)) {
					$v_answer = -2;
				} else {
					$v_answer = $o_field_type->getDataType();
				}
			}
			//
			return $v_answer;
		}
		protected static function col_name_by_data_type($s_data_type) {
			$s_col_name = '';
			//
			switch ($s_data_type) {
				// int_val :
				case 'int': // int
				case 'boolean': // int
				case 'date': // date
					$s_col_name = 'int_val';
					break;
				// float_val :
				case 'price': // float
				case 'float': // float
					$s_col_name = 'float_val';
					break;
				// varchar_val :
				case 'string': // string
				case 'password': // password
				case 'tags': // tags
					$s_col_name = 'varchar_val';
					break;
				// text_val :
				case 'text': // text
				case 'wysiwyg': // text
				case 'img_file': // imgfile
				case 'swf_file': // imgfile
				case 'file': // file
					$s_col_name = 'text_val';
					break;
				// rel_val :
				case 'relation': // relation
					$s_col_name = 'rel_val';
					break;
				// tree_val :
				case 'symlink': // symlink
					$s_col_name = 'tree_val';
					break;
				default:
					break;
			}
			//
			return $s_col_name;
		}

		public static function objectsByValue($i_field_id, $arr_value = NULL, $b_elements = false, $b_stat = true, $arr_domains = NULL) {
			throw new coreException("Not implemented yet");
		}

	}


/**
	* Этот класс служит для управления свойством объекта
*/
	class umiObjectPropertyBoolean extends umiObjectProperty {
		/**
			* Загружает значение свойства из БД, если тип свойства целое число
		*/
		protected function loadValue() {
			$values = $this->loadValueByTypeName('int_val');
			$res    = array();
			foreach($values as $val) $res[] = ($val)?true:false;
			return $res;
		}
		
		/**
			* Сохраняет значение свойства в БД, если тип свойства "Число"
		*/
		protected function saveValue() {
			$this->deleteCurrentRows();
			foreach($this->value as $val) {
				if($val === false || $val === "") continue;
				$this->saveValueByTypeName('int_val', (int)$val);
			}
			$this->fillNull();
		}
	};


/**
	* Этот класс служит для управления свойством объекта
*/
	class umiObjectPropertyImgFile extends umiObjectProperty {
		/**
			* Загружает значение свойства из БД, если тип свойства "Изображение"
		*/
		protected function loadValue() {
			$res = array();                        
			$values = $this->loadValueByTypeName('text_val');
			foreach($values as $val) {                
				if(strlen($val) == 0) continue;
				$img = new umiImageFile(self::filterOutputString($val));
				if($img->getIsBroken()) continue;
				$res[] = $img;                
			}            
			return $res;
		}

		/**
			* Сохраняет значение свойства в БД, если тип свойства "Изображение"
		*/
		protected function saveValue() {
			$this->deleteCurrentRows();
			if(is_null($this->value)) {
				return;
			}
			foreach($this->value as $val) {
				if(!$val) continue;				
				if(is_object($val)) {
					$val = ($val->getFilePath());
				} else {
					$val = ($val);
				}
				$this->saveValueByTypeName('text_val', $val);                 
			}
			$this->fillNull();
		}
	};


/**
	* Этот класс служит для управления свойством объекта
*/
	class umiObjectPropertyRelation extends umiObjectProperty {
		/**
			* Загружает значение свойства из БД, если тип свойства "Ссылка на объект"
		*/
		protected function loadValue() {
			return $this->loadValueByTypeName('rel_val');
		}

		/**
			* Сохраняет значение свойства в БД, если тип свойства "Ссылка на объект"
		*/
		protected function saveValue() {
			$this->deleteCurrentRows();

			if(is_null($this->value)) {
				return;
			}

			$tmp = Array();
			foreach($this->value as $val) {
				if(is_string($val) && strpos($val, "|") !== false) {
					$tmp1 = split("\|", $val);
					foreach($tmp1 as $v) {
						$v = trim($v);
						if($v) $tmp[] = $v;
						unset($v);
					}
					unset($tmp1);
					$this->getField()->setFieldTypeId(7);    //Check, if we can use it without fieldTypeId

				} else {
					$tmp[] = $val;
				}
			}
			$this->value = $tmp;
			unset($tmp);
			
			$forceObjectsCreation = self::$USE_FORCE_OBJECTS_CREATION;
			$oProxy = XMLFactory::getInstance()->getProxy('objects.xml');
			foreach($this->value as $val) {
				if(!$val) continue;

				if(is_object($val)) {
					$val = $val->getId();
				} else {
					if(is_numeric($val) && umiObjectsCollection::getInstance()->isExists($val) && !$forceObjectsCreation) {
						$val = (int) $val;
					} else {
						if($guide_id = $this->field->getGuideId()) {
							$val_name = self::filterInputString($val);
							$val = $oProxy->getAttributeValue('/umi/objects/object[@type_id='.$guide_id." and text()='".$val_name."']", 'id');
							if(!$val) {
								if($val = umiObjectsCollection::getInstance()->addObject($val_name, $guide_id)) {
									$val = (int) $val;
								} else {
									throw new coreException("Can't create guide item");
									return false;
								}
							}                            
						} else {
							continue;

						}
					}
				}
				if(!$val) continue;
				$this->saveValueByTypeName('rel_val', $val);                
			}
			$this->fillNull();
		}
	};


/**
	* Этот класс служит для управления свойством объекта
*/
	class umiObjectPropertyTags extends umiObjectProperty {
		/**
			* Загружает значение свойства из БД, если тип свойства "Тэги"
		*/
		protected function loadValue() {
			$res = $this->loadValueByTypeName('varchar_val');           
			foreach($res as &$str) $str = self::filterOutputString((string)$str);
			return $res;
		}

		/**
			* Сохраняет значение свойства в БД, если тип свойства "Тэги"
		*/
		protected function saveValue() {
			$this->deleteCurrentRows();

			if(sizeof($this->value) == 1) {
				$value =  split(",", preg_replace("[^\w, ]", "", trim($this->value[0], ",")));
			} else {
				$value = array_map( create_function('$a', " return preg_replace(\"[^\\w, ]\", \"\", \$a); ") , $this->value);
			}

			foreach($value as $val) {
				$val = trim($val);
				if(strlen($val) == 0) continue;

				$val = self::filterInputString($val);
				$this->saveValueByTypeName('varchar_val', $val);                 
			}
			$this->fillNull();
		}
	};


/**
	* Этот класс служит для управления свойством объекта
*/
	class umiObjectPropertyDate extends umiObjectProperty {
		/**
			* Загружает значение свойства из БД, если тип свойства "Дата"
		*/
		protected function loadValue() {
			$res = Array();            
			$values = $this->loadValueByTypeName('int_val');
			foreach($values as $val) {
				$res[] = new umiDate((int)$val);
			}
			return $res;
		}
		
		/**
			* Сохраняет значение свойства в БД, если тип свойства "Дата"
		*/
		protected function saveValue() {
			$this->deleteCurrentRows();
			foreach($this->value as $val) {
				if($val === false || $val === "") continue;
				$val = (is_object($val)) ? (int) $val->timestamp : (int) $val;
				$this->saveValueByTypeName('int_val', $val);                
			}
			//
			$sDebugData = (is_array($this->value) ? count($this->value) : -1);
			//
			$this->fillNull();
		}
	};


/**
	* Этот класс служит для управления свойством объекта
*/
	class umiObjectPropertyInt extends umiObjectProperty {
		/**
			* Загружает значение свойства из БД, если тип свойства целое число
		*/
		protected function loadValue() {
			return $this->loadValueByTypeName('int_val');
		}

		/**
			* Сохраняет значение свойства в БД, если тип свойства "Число"
		*/
		protected function saveValue() {
			$this->deleteCurrentRows();
			foreach($this->value as $val) {
				if($val === false || $val === "") continue;				
				$this->saveValueByTypeName('int_val', (int)$val);                
			}
			$this->fillNull();
		}
	};


/**
	* Этот класс служит для управления свойством объекта
*/
	class umiObjectPropertyString extends umiObjectProperty {
		/**
			* Загружает значение свойства из БД, если тип свойства "Строка"
		*/
		protected function loadValue() {
			$res = $this->loadValueByTypeName('varchar_val');           
			foreach($res as &$str) $str = self::filterOutputString((string)$str);
			return $res;
		}
		
		/**
			* Сохраняет значение свойства в БД, если тип свойства "Строка"
		*/
		protected function saveValue() {
			$this->deleteCurrentRows();

			foreach($this->value as $val) {
				if(strlen($val) == 0) continue;
				$val = self::filterInputString($val);
				$this->saveValueByTypeName('varchar_val', $val);                
			}
			$this->fillNull();
		}
	}


/**
	* Этот класс служит для управления свойством объекта
*/
	class umiObjectPropertyText extends umiObjectProperty {
		/**
			* Загружает значение свойства из БД, если тип свойства "Текст"
		*/
		protected function loadValue() {
			$res = $this->loadValueByTypeName('text_val');           
			foreach($res as &$str) $str = self::filterOutputString((string)$str);
			return $res;
		}

		/**
			* Сохраняет значение свойства в БД, если тип свойства "Текст"
		*/
		protected function saveValue() {
			$this->deleteCurrentRows();

			foreach($this->value as $val) {
				if($val == "<p />" || $val == "&nbsp;") $val = "";
				$val = self::filterInputString($val);
				$this->saveValueByTypeName('text_val', $val);                
			}
			$this->fillNull();
		}
		
		public function __wakeup() {
			foreach($this->value as $i => $v) {
				if(is_string($v)) {
					$this->value[$i] = str_replace("&#037;", "%", $v);
				}
			}
		}
	};


/**
	* Этот класс служит для управления свойством объекта
*/
	class umiObjectPropertyFile extends umiObjectProperty {
		/**
			* Загружает значение свойства из БД, если тип свойства "Файл"
		*/
		protected function loadValue() {
			$res = Array();            
			$values = $this->loadValueByTypeName('text_val');
			foreach($values as $val) {
				$file = new umiFile($val);
				if($file->getIsBroken()) continue;
				$res[] = $file;
			}
			
			return $res;
		}

		/**
			* Сохраняет значение свойства в БД, если тип свойства "Файл"
		*/
		protected function saveValue() {
			$this->deleteCurrentRows();

			if(is_null($this->value)) {
				return;
			}

			foreach($this->value as $val) {
				if(!$val) continue;
				
				if(is_object($val)) {
					$val = ($val->getFilePath());
				} else {
					$val = ($val);
				}
				$this->saveValueByTypeName('text_val', $val);                
			}
			$this->fillNull();
		}
	};


/**
	* Этот класс служит для управления свойством объекта
*/
	class umiObjectPropertyPassword extends umiObjectProperty {
		/**
			* Загружает значение свойства из БД, если тип свойства целое число
		*/
		protected function loadValue() {
			$res = $this->loadValueByTypeName('varchar_val');           
			foreach($res as &$str) $str = self::filterOutputString((string)$str);
			return $res;
		}


		/**
			* Сохраняет значение свойства в БД, если тип свойства "Пароль"
		*/
		protected function saveValue() {
			foreach($this->value as $val) {
				if(strlen($val) == 0) continue;

				$this->deleteCurrentRows();

				$val = self::filterInputString($val);
				$this->saveValueByTypeName('varchar_val', $val);                
			}
			$this->fillNull();
		}
	};


/**
	* Этот класс служит для управления свойством объекта
*/
	class umiObjectPropertyWYSIWYG extends umiObjectPropertyText {
		/**
			* Загружает значение свойства из БД, если тип свойства "HTML-текст"
		*/
		protected function loadValue() {
			$res = $this->loadValueByTypeName('text_val');           
			foreach($res as &$str) {
				if(str_replace("&nbsp;", "", trim($str)) == "") continue; 
				$str = self::filterOutputString((string)$str);
			}
			return $res;
		}
	};


/**
	* Этот класс служит для управления свойством объекта
*/
	class umiObjectPropertyFloat extends umiObjectProperty {
		/**
			* Загружает значение свойства из БД, если тип свойства "число с точкой"
		*/
		protected function loadValue() {
			return $this->loadValueByTypeName('float_val'); 
		}
		
		/**
			* Сохраняет значение свойства в БД, если тип свойства "Число с точкой"
		*/
		protected function saveValue() {
			$this->deleteCurrentRows();
			foreach($this->value as $val) {
				if($val === false || $val === "") continue;
				if(strpos(".", $val) === false) $val = str_replace(",", ".", $val);
				$this->saveValueByTypeName('float_val', (float)$val);                
			}
			$this->fillNull();
		}
	};


/**
	* Этот класс служит для управления свойством объекта
*/
	class umiObjectPropertyPrice extends umiObjectPropertyFloat {
		/**
			* Загружает значение свойства из БД, если тип свойства "Цена"
		*/
		protected function loadValue() {
			$res = $this->loadValueByTypeName('float_val');			
			if($eshop_inst = cmsController::getInstance()->getModule("eshop")) {
				$price = is_array($res) && count($res) ? $res[0] : $res;
				$price = $eshop_inst->calculateDiscount($this->object_id, $price);
				$res = Array($price);
			}			
			list($price) = $res;
			$oEventPoint = new umiEventPoint("umiObjectProperty_loadPriceValue");
			$oEventPoint->setParam("object_id", $this->object_id);
			$oEventPoint->addRef("price", $price);
			$oEventPoint->call();
			$res = Array($price);			
			return $res;
		}

		/**
			* Сохраняет значение свойства в БД, если тип свойства "Цена"
		*/
		protected function saveValue() {
			$this->deleteCurrentRows();
			foreach($this->value as $val) {
				if($val === false || $val === "") continue;
				if(strpos(".", $val) === false) $val = str_replace(",", ".", $val);
				$val = abs((float)$val);
				if($val > 9999999.99) $val = 9999999.99;
				$this->saveValueByTypeName('float_val', $val);                
			}
			$this->fillNull();
		}
		
		public function __wakeup() {
			$this->value = $this->loadValue();
		}
	};


/**
	* Этот класс служит для управления свойством объекта
*/
	class umiObjectPropertySymlink extends umiObjectProperty {
		/**
			* Загружает значение свойства из БД, если тип свойства "Ссылка на дерево"
		*/
		protected function loadValue() {
			$res = Array();            
			$values = $this->loadValueByTypeName('tree_val');
			foreach($values as $val) {
				$element = umiHierarchy::getInstance()->getElement( (int) $val );
				if($element === false) continue;
				if($element->getIsActive() == false) continue;
				$res[] = $element;
			}
			return $res;
		}

		/**
			* Сохраняет значение свойства в БД, если тип свойства "Ссылка на дерево"
		*/
		protected function saveValue() {
			$this->deleteCurrentRows();

			foreach($this->value as $val) {
				if($val === false || $val === "") continue;

				if(is_object($val)) {
					$val = (int) $val->getId();
				}

				if(is_numeric($val)) {
					$val = (int) $val;
				}

				if(!$val) continue;
				$this->saveValueByTypeName('tree_val', $val);                
			}
			$this->fillNull();
		}
	};


	interface iUmiObject {
		public function getName();
		public function setName($name);

		public function getIsLocked();
		public function setIsLocked($isLocked);

		public function getTypeId();
		public function setTypeId($typeId);

		public function getPropGroupId($groupName);
		public function getPropGroupByName($groupName);
		public function getPropGroupById($groupId);

		public function getPropByName($propName);
		public function getPropById($propId);

		public function isPropertyExists($id);


		public function getValue($propName);
		public function setValue($propName, $propValue);

		public function setOwnerId($ownerId);
		public function getOwnerId();
	}


	class umiObject extends umiEntinty implements iUmiEntinty, iUmiObject {
		private $name, $type_id, $is_locked, $owner_id = false,
			$type, $properties = Array(), $prop_groups = Array();
		protected $store_type = "object";

		public function getName() {
			return $this->translateLabel($this->name);
		}

		public function getTypeId() {
			return $this->type_id;
		}

		public function getIsLocked() {
			return $this->is_locked;
		}


		public function setName($name) {
			if ($this->name !== $name) {
				if(($this->translateLabel($this->name) != $this->name)) {
					$name = $this->translateI18n($name);
				}
				$this->name = $name;
				$this->setIsUpdated();
			}
		}

		public function setTypeId($type_id) {
			if ($this->type_id !== $type_id) {
				$this->type_id = $type_id;
				$this->setIsUpdated();
			}
			return true;
		}

		public function setIsLocked($is_locked) {
			if ($this->is_locked !== ((bool) $is_locked)) {
				$this->is_locked = (bool) $is_locked;
				$this->setIsUpdated();
			}
		}

		public function setOwnerId($ownerId) {
			if(!is_null($ownerId) and umiObjectsCollection::getInstance()->isExists($ownerId)) {
				if ($this->owner_id !== $ownerId) {
					$this->owner_id = $ownerId;
					$this->setIsUpdated();
				}
				return true;
			}
			else {
				if (!is_null($this->owner_id)) {
					$this->owner_id = NULL;
					$this->setIsUpdated();
				}
				return false;
			}
		}

		public function getOwnerId() {
			return $this->owner_id;
		}

		protected function save() {
			if ($this->is_updated) {

				$name      = umiObjectProperty::filterInputString($this->name);
				$type_id   = (int) $this->type_id;
				$is_locked = (int) $this->is_locked;
				$owner_id  = (int) $this->owner_id;
				$oObject   = XMLFactory::getInstance()->getProxy('objects.xml')->getElement('/umi/objects/object[@id='.$this->id.']');
				$oObject->setValue($name);
				$oObject->type_id   = (int) $this->type_id;
				$oObject->is_locked = (int) $this->is_locked;
				$oObject->owner_id  = (int) $this->owner_id;
				foreach($this->properties as $prop) {
					if(is_object($prop)) $prop->commit();
				}
				$this->setIsUpdated(false);
			}
			return true;
		}

		protected function loadInfo() {
			$oObject = XMLFactory::getInstance()->getProxy('objects.xml')->getElement('/umi/objects/object[@id='.$this->id.']');
			if(!$oObject->type_id) {    //Foregin keys check failed, or manual queries made. Delete this object.
				umiObjectsCollection::getInstance()->delObject($this->id);
				return false;
			}        
			$this->name      = $oObject->getValue();
			$this->type_id   = (int)  $oObject->type_id;
			$this->is_locked = (bool) $oObject->is_locked;
			$this->owner_id  = (int)  $oObject->owner_id;
			return $this->loadType();
		}

		private function loadType() {
			$type = umiObjectTypesCollection::getInstance()->getType($this->type_id);

			if(!$type) {
				throw new coreException("Can't load type in object's init");
				return false;
			}

			$this->type = $type;
			return $this->loadProperties();
		}

		private function loadProperties() {
			$type = $this->type;
			$groups_list = $type->getFieldsGroupsList();
			foreach($groups_list as $group) {
				if($group->getIsActive() == false) continue;

				$fields = $group->getFields();

				$this->prop_groups[$group->getId()] = Array();

				foreach($fields as $field) {
					$this->properties[$field->getId()] = $field->getName();
					$this->prop_groups[$group->getId()][] = $field->getId();
				}
			}
		}

		public function getPropByName($prop_name) {
			$prop_name = strtolower($prop_name);

			foreach($this->properties as $field_id => $prop) {
				if(is_object($prop)) {
					if($prop->getName() == $prop_name) {
						return $prop;
					}
				} else {
					if($prop == $prop_name) {
						$prop = cacheFrontend::getInstance()->load($this->id . "." . $field_id, "property");
						if($prop instanceof umiObjectProperty == false) {
							$prop = umiObjectProperty::getProperty($this->id, $field_id, $this->type_id);
							cacheFrontend::getInstance()->save($prop, "property");
						}
						$this->properties[$field_id] = $prop;
						return $prop;
					}
				}
			}
			return NULL;
		}

		public function getPropById($field_id) {
			if(!$this->isPropertyExists($field_id)) {
				return NULL;
			} else {
				if(!is_object($this->properties)) {
					if($prop = cacheFrontend::getInstance()->load($this->id . "." . $field_id, "property")) {
					} else {
						$prop = umiObjectProperty::getProperty($this->id, $field_id, $this->type_id);
						cacheFrontend::getInstance()->save($prop, "property");
					}
					$this->properties[$field_id] = $prop;
				}
				return $this->properties[$field_id];
			}
		}

		public function isPropertyExists($field_id) {
			return (bool) array_key_exists($field_id, $this->properties);
		}

		public function isPropGroupExists($prop_group_id) {
			return (bool) array_key_exists($prop_group_id, $this->prop_groups);
		}

		public function getPropGroupId($prop_group_name) {
			$groups_list = $this->type->getFieldsGroupsList();
			foreach($groups_list as $group) {
				if($group->getName() == $prop_group_name) {
					return $group->getId();
				}
			}
			return false;
		}

		public function getPropGroupByName($prop_group_name) {
			$groups_list = $this->type->getFieldsGroupsList();

			if($group_id = $this->getPropGroupId($prop_group_name)) {
				return $this->getPropGroupById($group_id);
			} else {
				return false;
			}
		}

		public function getPropGroupById($prop_group_id) {
			if($this->isPropGroupExists($prop_group_id)) {
				return $this->prop_groups[$prop_group_id];
			} else {
				return false;
			}
		}


		public function getValue($prop_name) {
			if($prop = $this->getPropByName($prop_name)) {
				//echo $this->name . '.' . $prop_name . ' = ' . ((!is_object($prop->getValue()))?$prop->getValue():print_r($prop->getValue(),true)) . "<br />\n"; flush();
				return $prop->getValue();
			} else {
				return false;
			}
		}

		public function setValue($prop_name, $prop_value) {
			if($prop = $this->getPropByName($prop_name)) {
				$this->setIsUpdated();
				return $prop->setValue($prop_value);
			} else {
				return false;
			}
		}
		
		
		public function commit() {
			foreach($this->properties as $prop) {
				if(is_object($prop)) {
					$prop->commit();
				}
			}
			// umiEntinty :
			parent::commit();
		}
		
		
		public function setIsUpdated($isUpdated = true) {
			umiObjectsCollection::getInstance()->addUpdatedObjectId($this->id);
			return parent::setIsUpdated($isUpdated);
		}
	}


	interface iUmiFieldTypesCollection {
		public function addFieldType($name, $dataType = "string", $isMultiple = false, $isUnsigned = false);
		public function delFieldType($fieldTypeId);
		public function getFieldType($fieldTypeId);

		public function getFieldTypesList();
	}


	class umiFieldTypesCollection extends singleton implements iSingleton, iUmiFieldTypesCollection {
		private $field_types = Array();

		protected function __construct() {
			$this->loadFieldTypes();
		}

		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}


		public function addFieldType($name, $data_type = "string", $is_multiple = false, $is_unsigned = false) {
			if(!umiFieldType::isValidDataType($data_type)) {
				throw new coreException("Not valid data type given");
				return false;
			}           
            $oProxy         = XMLFactory::getInstance()->getProxy('objectsdef.xml');
            $field_type_id  = (int)$oProxy->getAttributeValue('/umi/fieldtypes/fieldtype[last()]', 'id') + 1;
            $oXMLType       = $oProxy->addElement('/umi/fieldtypes', 'fieldtype', '');
            $oXMLType->id   = $field_type_id;

			$field_type = new umiFieldType($field_type_id);

			$field_type->setName($name);
			$field_type->setDataType($data_type);
			$field_type->setIsMultiple($is_multiple);
			$field_type->setIsUnsigned($is_unsigned);
			$field_type->commit();

			$this->field_types[$field_type_id] = $field_type;

			return $field_type_id;
		}

		public function delFieldType($field_type_id) {
			if($this->isExists($field_type_id)) {
				$field_type_id = (int) $field_type_id;
                XMLFactory::getInstance()->getProxy('objectsdef.xml')->removeElement('/umi/fieldtypes/fieldtype[@id='.$field_type_id.']');
				unset($this->field_types[$field_type_id]);
				return true;
			} else {
				return false;
			}
		}

		public function getFieldType($field_type_id) {
			if($this->isExists($field_type_id)) {
				return $this->field_types[$field_type_id];
			} else {
				return false;
			}
		}

		public function isExists($field_type_id) {
			return (bool) array_key_exists($field_type_id, $this->field_types);
		}


		private function loadFieldTypes() {			
            $aTypeList = XMLFactory::getInstance()->getProxy('objectsdef.xml')->getNodeTree("/umi/fieldtypes");            
            if(!empty($aTypeList))			
            foreach($aTypeList['fieldtypes'][0]['fieldtype'] as $aType) {                
                $field_type_id = $aType['@attributes']['id'];
                $row = array($aType['@attributes']['id'], 
                             $aType['@value'], 
                             $aType['@attributes']['data_type'], 
                             $aType['@attributes']['is_multiple'], 
                             $aType['@attributes']['is_unsigned']);
				if($field_type = cacheFrontend::getInstance()->load($field_type_id, "field_type")) {
				} else {
					try {
						$field_type = new umiFieldType($field_type_id, $row);
					} catch(privateException $e) {
						continue;
					}
					cacheFrontend::getInstance()->save($field_type, "field_type");
				}
				$this->field_types[$field_type_id] = $field_type;
			}

			return true;
		}

		public function getFieldTypesList() {
			return $this->field_types;
		}
	}


	interface iUmiFieldsCollection {
		public function addField($name, $title, $fieldTypeId, $isVisible = true, $isLocked = false, $isInheritable = false);
		public function delField($field_id);
		public function getField($fieldId);
	}


	class umiFieldsCollection extends singleton implements iSingleton, iUmiFieldsCollection {
		private	$fields = Array();

		protected function __construct() {
		}

		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}


		public function getField($field_id, $row = false) {
			if($this->isExists($field_id)) {
				return $this->fields[$field_id];
			} else {
				return $this->loadField($field_id, $row);
			}
		}

		public function delField($field_id) {
			if($this->isExists($field_id)) {
                XMLFactory::getInstance()->getProxy('objectsdef.xml')->removeElement('/umi/fields/field[@id='.$field_id.']');
				unset($this->fields[$field_id]);
				return true;
			} else {
				return false;
			}
		}

		public function addField($name, $title, $field_type_id, $is_visible = true, $is_locked = false, $is_inheritable = false) {            
            $oProxy    = XMLFactory::getInstance()->getProxy("objectsdef.xml");
            $field_id  = (int)$oProxy->getAttributeValue("/umi/fields/field[last()]", "id") + 1;            
            $oXMLField = $oProxy->addElement("/umi/fields", "field", "");
            $oXMLField->id = $field_id;

			$field = new umiField($field_id);

			$field->setName($name);
			$field->setTitle($title);
			if(!$field->setFieldTypeId($field_type_id)) return false;
			$field->setIsVisible($is_visible);
			$field->setIsLocked($is_locked);
			$field->setIsInheritable($is_inheritable);

			if(!$field->commit()) return false;

			$this->fields[$field_id] = $field;

			return $field_id;
		}

		public function isExists($field_id) {
			return (bool) array_key_exists((string)$field_id, $this->fields);
		}

		private function loadField($field_id, $row) {
			if($field = cacheFrontend::getInstance()->load($field_id, "field")) {
			} else {
				try {
					$field = new umiField($field_id, $row);
				} catch(privateException $e) {
					return false;
				}

				cacheFrontend::getInstance()->save($field, "field");
			}
			
			if($field instanceof iUmiField) {			
				$this->fields[$field_id] = $field;
				return $this->fields[$field_id];
			} else {
				return false;
			}
		}
	}


	interface iUmiObjectTypesCollection {
		public function addType($parentId, $name, $isLocked = false);
		public function delType($typeId);

		public function getType($typeId);
		public function getSubTypesList($typeId);

		public function getParentClassId($typeId);
		public function getChildClasses($typeId);

		public function getGuidesList($publicOnly = false);

		public function getTypesByHierarchyTypeId($hierarchyTypeId, $ignoreMicroCache = false);
		public function getTypeByHierarchyTypeId($hierarchyTypeId, $ignoreMicroCache = false);

		public function getBaseType($typeName, $typeExt = "");
	}


	class umiObjectTypesCollection extends singleton implements iSingleton, iUmiObjectTypesCollection {
		private $types = Array();

		protected function __construct() {
		}

		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}


		public function getType($type_id) {
			if($this->isLoaded($type_id)) {
				return $this->types[$type_id];
			} else {
				if(true) { //if($this->isExists($type_id)) {	//doesn't matter any more
					$this->loadType($type_id);
					return $this->types[$type_id];
				} else {
					return false;
				}
			}
			throw new coreException("Unknow error");
		}

		public function addType($parent_id, $name, $is_locked = false) {
			$parent_id = (int) $parent_id;
            $oProxy = XMLFactory::getInstance()->getProxy('objectsdef.xml');           

			$type_id = (int)$oProxy->getAttributeValue('/umi/objecttypes/objecttype[not(@id <= preceding-sibling::objecttype/@id) and not(@id <=following-sibling::objecttype/@id)]','id') + 1;
            $Element = $oProxy->addElement('/umi/fieldgroups', 'fieldgroup', '');
            $Element->id = $type_id;
            $Element->parent_id = $parent_id;			
            
            $aGroups = $oProxy->getElementsArray('/umi/fieldgroups/fieldgroup[@type_id='.$parent_id.']');
            $new_group_id = (int)$oProxy->getAttributeValue('/umi/fieldgroups/fieldgroup[not(@id <= preceding-sibling::fieldgroup/@id) and not(@id <=following-sibling::fieldgroup/@id)]','id');
            foreach($aGroups as $Group) {
                $new_group_id++;
                $NewGroup = $Group->cloneSelf();
                $NewGroup->id = $new_group_id;
                $aConns = $oProxy->getElementsArray('/umi/fieldcontroller/conn[@group_id='.$Group->id.']');
                foreach($aConns as $Conn) $Conn->cloneSelf()->group_id = $new_group_id;
            }
            
            XMLFactory::getInstance()->createFile('objectcontent/'.$type_id.'.xml' , "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<umi>\n <values />\n</umi>");
            
            $parent_hierarchy_type_id = false;
			if($parent_id) {
				$parent_type = $this->getType($parent_id);
				if($parent_type) {
					$parent_hierarchy_type_id = $parent_type->getHierarchyTypeId();
				}
			}
			
			$Element 	 		= XMLFactory::getInstance()->getProxy('objectsdef.xml')->addElement('/umi/objecttypes', 'objecttype', '');
			$Element->id 		= (int) $type_id;
			$Element->parent_id = (int) $parent_id; 

			$type = new umiObjectType($type_id);
			$type->setName($name);
			$type->setIsLocked($is_locked);			
			if($parent_hierarchy_type_id) {
				$type->setHierarchyTypeId($parent_hierarchy_type_id);
			}
			$type->commit();

			$this->types[$type_id] = $type;

			return $type_id;
		}

		public function delType($type_id) {
			if($this->isExists($type_id)) {
				$childs = $this->getChildClasses($type_id);
                $oObjProxy  = XMLFactory::getInstance()->getProxy('objects.xml');
                $oTypeProxy = XMLFactory::getInstance()->getProxy('objectsdef.xml');               

				$sz = sizeof($childs);
				for($i = 0; $i < $sz; $i++) {
					$child_type_id = $childs[$i];

					if($this->isExists($child_type_id)) {
                        $oObjProxy->removeElement('/umi/objects/object[@type_id='.$child_type_id.']');
                        $oTypeProxy->removeElement('/umi/objecttypes/objecttype[@id='.$child_type_id.']');                        
						unset($this->types[$child_type_id]);
					}
				}

				$type_id = (int) $type_id;
                $oObjProxy->removeElement('/umi/objects/object[@type_id='.$type_id.']');
                $oTypeProxy->removeElement('/umi/objecttypes/objecttype[@id='.$type_id.']');
                XMLFactory::getInstance()->getProxy('objectcontent/objectmap.xml')->removeElement('/umi/objectmap/entry[@tid='.$type_id.']');
                XMLFactory::getInstance()->removeFile('objectcontent/'.$type_id.'.xml');

				unset($this->types[$type_id]);
				return true;
			} else {
				return false;
			}
		}

		public function isExists($type_id) {
			return true;		//COMMENT: Deprecated. No need any more.
		}

		private function isLoaded($type_id) { 
			if(!intval($type_id)) return false;
			return (bool) array_key_exists($type_id, $this->types);
		}

		private function loadType($type_id) {
			if($this->isLoaded($type_id)) {
				return true;
			} else {
				if($type = cacheFrontend::getInstance()->load($type_id, "object_type")) {
				} else {
					try {
						$type = new umiObjectType($type_id);
					} catch(privateException $e) {
						return false;
					}
					
					cacheFrontend::getInstance()->save($type, "object_type");
				}
				
				if(is_object($type)) {
					$this->types[$type_id] = $type;
					return true;
				} else {
					return false;
				}
			}
		}

		public function getSubTypesList($type_id) {
			if(!is_numeric($type_id)) {
				throw new coreException("Type id must be numeric");
				return false;
			} 
			$res = array();
            $type_id = (int) $type_id;
            $aTypes  = XMLFactory::getInstance()->getProxy('objectsdef.xml')->getElementsArray('/umi/objecttypes/objecttype[@parent_id='.$type_id.']');
            foreach($aTypes as $Type) {
                $res[] = (int)$Type->id;
            }            
			return $res;
		}

		public function getParentClassId($type_id) {
			if($this->isLoaded($type_id)) {
				return $this->getType($type_id)->getParentId();
			} else {
				$type_id = (int) $type_id;
                return (int)XMLFactory::getInstance()->getProxy('objectsdef.xml')->getAttributeValue('/umi/objecttypes/objecttype[@id='.$type_id.']', 'parent_id');				
			}
		}

		public function getChildClasses($type_id, $childs = false) {
			// Temporary stub
			return array();
			
			$res = Array();
			if(!$childs) $childs = Array();
			$type_id = (int) $type_id;
            $aTypes  = XMLFactory::getInstance()->getProxy('objectsdef.xml')->getElementsArray('/umi/objecttypes/objecttype[@parent_id='.$type_id.']');
            foreach($aTypes as $Type) {
            	$id    = (int)$Type->id;
                $res[] = $id;
                if(!in_array($id, $childs)) $res = array_merge($res, $this->getChildClasses($id, $res));
            }
			$res = array_unique($res);
			return $res;
		}

		public function getGuidesList($public_only = false) {
			$res = Array();            
            $aTypes  = XMLFactory::getInstance()->getProxy('objectsdef.xml')->getElementsArray('/umi/objecttypes/objecttype[@is_guidable=1'.(($public_only)?' and @is_public=1':'').']');
            foreach($aTypes as $Type) {
                $res[$Type->id] = $this->translateLabel($Type->name);
            }			
			return $res;
		}

		public function getTypesByHierarchyTypeId($hierarchy_type_id, $ignoreMicroCache = false) {
			static $cache = Array();
			$hierarchy_type_id = (int) $hierarchy_type_id;
			if(isset($cache[$hierarchy_type_id])) return $cache[$hierarchy_type_id];            
            $aTypes  = XMLFactory::getInstance()->getProxy('objectsdef.xml')->getElementsArray('/umi/objecttypes/objecttype[@hierarchy_type_id='.$hierarchy_type_id.']');
            $res = array();
            foreach($aTypes as $Type) {
                $res[$Type->id] = $this->translateLabel($Type->name);
            }
			return $cache[$hierarchy_type_id] = $res;
		}

		public function getTypeByHierarchyTypeId($hierarchy_type_id, $ignoreMicroCache = false) {
			static $cache = Array();
			$hierarchy_type_id = (int) $hierarchy_type_id;
			
			if(isset($cache[$hierarchy_type_id])) return $cache[$hierarchy_type_id];
            
            $id = XMLFactory::getInstance()->getProxy('objectsdef.xml')->getAttributeValue('/umi/objecttypes/objecttype[@hierarchy_type_id='.$hierarchy_type_id.']', 'id');
            return $cache[$hierarchy_type_id] = $id;		
		}

		public function getBaseType($name, $ext = "") {
			$hierarchy_type = umiHierarchyTypesCollection::getInstance()->getTypeByName($name, $ext);

			if($hierarchy_type) {
				$hierarchy_type_id = $hierarchy_type->getId();
				$type_id = $this->getTypeByHierarchyTypeId($hierarchy_type_id);
				return (int) $type_id;
			} else {
				return false;
			}
		}
	}


	interface iUmiObjectsCollection {
		public function getObject($objectId);
		public function addObject($name, $typeId, $isLocked = false);
		public function delObject($objectId);

		public function cloneObject($iObjectId);

		public function getGuidedItems($guideId);

		public function unloadObject($objectId);
	}


	class umiObjectsCollection extends singleton implements iSingleton, iUmiObjectsCollection {
		private	$objects = Array(), $updatedObjects = Array();

		protected function __construct() {
		}

		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}

		private function isLoaded($object_id) {
			if(gettype($object_id) == "object") {
			throw new coreException("Object given!");
			}
			return (bool) array_key_exists($object_id, $this->objects);
		}

		public function isExists($object_id) {
			$object_id = (int) $object_id;			
			return (XMLFactory::getInstance()->getProxy('objects.xml')->getCount('/umi/objects/object[@id='.$object_id.']')) ? true : false;
		}

		public function getObject($object_id) {
			$object_id = (int) $object_id;

			if(!$object_id) {
				return false;
			}

			if($this->isLoaded($object_id)) {
				return $this->objects[$object_id];
			}

			if($object = cacheFrontend::getInstance()->load($object_id, "object")) {
			} else {
				try {
					$object = new umiObject($object_id);
				} catch (baseException $e) {
					return false;
				}
				cacheFrontend::getInstance()->save($object, "object");
			}

			
			if(is_object($object)) {
				$this->objects[$object_id] = $object;
				return $this->objects[$object_id];
			} else {
				return false;
			}
		}

		public function delObject($object_id) {
			if($this->isExists($object_id)) {
				$object_id = (int) $object_id;				
				//Make sure, we don't will not try to commit it later
				$object = $this->getObject($object_id);
				$object->commit();
				XMLFactory::getInstance()->getProxy('objects.xml')->removeElement('/umi/objects/object[@id='.$object_id.' and @is_locked=0]');
				if($this->isLoaded($object_id)) {
					unset($this->objects[$object_id]);
				}
                $oProxy  = XMLFactory::getInstance()->getProxy('objectcontent/objectmap.xml');
                $iTypeID = (int)$oProxy->getAttributeValue('/umi/objectmap/entry[@oid='.$object_id.']', 'tid');
                $oProxy->removeElement('/umi/objectmap/entry[@oid='.$object_id.']');
                XMLFactory::getInstance()->getProxy('objectcontent'.$iTypeID.'.xml')->removeElement('/umi/values/value[@obj_id='.$object_id.']');
                
                $memCache = cacheFrontend::getInstance();
                if(in_array("del", get_class_methods( get_class($memCache) ) ) ) {
                    $memCache->del($object_id, "object");
                }
				
				return true;
			} else {
				return false;
			}
		}

		public function addObject($name, $type_id, $is_locked = false) {
			$type_id = (int) $type_id;
			
			//$object_id = mysql_insert_id();
            $oProxy    = XMLFactory::getInstance()->getProxy('objects.xml');
            $object_id = (int)$oProxy->getAttributeValue('/umi/objects/object[last()]', 'id') + 1;            
            $oObject   = $oProxy->addElement('/umi/objects', 'object', '');
            $oObject->id      = $object_id;
            $oObject->type_id = $type_id;
            
            $oMapEntry = XMLFactory::getInstance()->getProxy('objectcontent/objectmap.xml')->addElement('/umi/objectmap', 'entry');
            $oMapEntry->oid = $object_id;
            $oMapEntry->tid = $type_id;
            
			$object = new umiObject($object_id);

			$object->setName($name);
			$object->setIsLocked($is_locked);

			//Set current user
			if($users_inst = cmsController::getInstance()->getModule("users")) {
				if($users_inst->is_auth()) {
					$user_id = cmsController::getInstance()->getModule("users")->user_id;
					$object->setOwnerId($user_id);
				}
			} else {
			    $object->setOwnerId(NULL);
			}

			$object->commit();
			$this->objects[$object_id] = $object;

			//$this->resetObjectProperties($object_id);

			$this->resetObjectProperties($object_id);

			return $object_id;
		}

		public function cloneObject($iObjectId) {
			$vResult = false;

			$oObject = $this->getObject($iObjectId);
			if ($oObject instanceof umiObject) {
				// clone object definition
                $oProxy  = XMLFactory::getInstance()->getProxy('objects.xml');
                $iNewId  = (int)$oProxy->getAttributeValue('/umi/objects/object[last()]', 'id') + 1;
                $oSource = $oProxy->getElement('/umi/objects/object[@id='.$iObjectId.']');
                $oTarget = $oProxy->addElement('/umi/objects', 'object', $oSource->getValue() );                
                $oTarget->is_locked = $oSource->is_locked;
                $oTarget->type_id   = $oSource->type_id;
                $oTarget->owner_id  = $oSource->owner_id;
                $oTarget->id        = $iNewId;
				// clone object content
                // ToDo: Rewrite for content
				//$sSql    = "INSERT INTO cms3_object_content (obj_id, field_id, int_val, varchar_val, text_val, rel_val, tree_val,float_val)  SELECT '{$iNewObjectId}' as obj_id, field_id, int_val, varchar_val, text_val, rel_val, tree_val,float_val FROM cms3_object_content WHERE obj_id = '$iObjectId'";
                $aSource = XMLFactory::getInstance()->getProxy('objectcontent/'.$oObject->getTypeId().'.xml')->getNodeTree('/umi/values/value[@object_id='.$iObjectId.']');
                $oProxy  = XMLFactory::getInstance()->getProxy('objectcontent/'.$iNewObjectId.'.xml');
                foreach($aSource as $aField) {
                    $Attrib = $aField['@attribute'];
                    $oEl = $oProxy->addElement('/umi/values', 'value', '');                    
                    $oEl->obj_id      = $Attrib['obj_id'];
                    $oEl->field_id    = $Attrib['field_id'];
                    $oEl->int_val     = $Attrib['int_val'];
                    $oEl->varchar_val = $Attrib['varchar_val'];
                    $oEl->text_val    = $Attrib['text_val'];
                    $oEl->rel_val     = $Attrib['rel_val'];
                    $oEl->tree_val    = $Attrib['tree_val'];
                    $oEl->float_val   = $Attrib['float_val'];                                        
                }
                $oMapEntry = XMLFactory::getInstance()->getProxy('objectcontent/objectmap.xml')->addElement('/umi/objectmap', 'entry');
                $oMapEntry->oid = $iNewId;
                $oMapEntry->tid = $oSource->type_id;
                
				/*l_mysql_query($sSql);

				if ($err = mysql_error()) {
					throw new coreException($err);
					return false;
				}*/

				$vResult = $iNewObjectId;
			}

			return $vResult;
		}
 
		public function getGuidedItems($guide_id) {
			$res        = Array();
			$guide_id   = (int) $guide_id;            
            $aGuideList = XMLFactory::getInstance()->getProxy('objects.xml')->getNodeTree('/umi/objects/object[@type_id='.$guide_id.']');
            
            if(!isset($aGuideList['object'])) {
            	return $res;
            }
            
            foreach($aGuideList['object'] as $aObject) {
				$res[ $aObject['@attributes']['id'] ] = $this->translateLabel($aObject['@value']);
			}
			$ignoreSorting = intval(regedit::getInstance()->getVal("//settings/ignore_guides_sort")) ? true : false;
			if(!$ignoreSorting) natsort($res);
			return $res;
		}
		
		
		protected function resetObjectProperties($object_id) {
			$object = $this->getObject($object_id);
			$object_type_id = $object->getTypeId();
			$object_type = umiObjectTypesCollection::getInstance()->getType($object_type_id);
			$object_fields = $object_type->getAllFields();
			foreach($object_fields as $object_field) {
				$object->setValue($object_field->getName(), Array());
			}
		}
		
		
		public function unloadObject($object_id) {
			if($this->isLoaded($object_id)) {
				unset($this->objects[$object_id]);
			} else {
				return false;
			}
		}

		public function getCollectedObjects() {
			return array_keys($this->objects);
		}
		
		public function addUpdatedObjectId($object_id) {
			if(!in_array($object_id, $this->updatedObjects)) {
				$this->updatedObjects[] = $object_id;
			}
		}
		
		public function getUpdatedObjects() {
			return $this->updatedObjects;
		}
		
		public function __destruct() {
			if(sizeof($this->updatedObjects)) {
				if(function_exists("deleteObjectsRelatedPages")) {
					deleteObjectsRelatedPages();
					
				}
			}
		}

	};


/*
    stream_open
    stream_read
    stream_write
    stream_tell
    stream_eof
    stream_seek
    url_stat
    stream_flush
    stream_close
*/
	interface iUmiBaseStream {

		public function stream_open($path, $mode, $options, $opened_path);
		public function stream_read($count);
		public function stream_write($data);
		public function stream_tell();
		public function stream_eof();
		public function stream_seek($offset, $whence);
		public function stream_flush();
		public function stream_close();
		public function url_stat();
		
		public function getProtocol();

		public static function getCalledStreams();
	};


	abstract class umiBaseStream implements iUmiBaseStream {
	    public static $allowTimeMark = true;
		protected	$position = 0,
				$length = 0,
				$data = "",
				$expire = 0,
				$transform = "",
				$path, $params = Array();

		protected	$scheme;
		protected static $callLog = Array();
		
		private		$start_time = false;
		
		
		public function __construct() {
			$this->start_time = microtime(true);
		}
		
		public function stream_flush() {
			return true;
		}
		
		
		public function stream_tell() {
			return $this->position;
		}
		
		
		public function stream_eof() {
			return $this->position >= $this->length;
		}
		
		
		public function stream_seek($offset, $whence) {
			switch($whence) {
				case SEEK_SET: {
					if($this->isValidOffset($offset)) {
						$this->position = $offset;
						return true;
					} else {
						return false;
					}
				}
				
				
				case SEEK_CUR: {
					if($offset >= 0) {
						$this->position += $offset;
						return true;
					} else {
						return false;
					}
				}
				
				
				case SEEK_END: {
					if($this->isValidOffset($this->position + $offset)) {
						$this->position = $this->length + $offset;
						return true;
					} else {
						return false;
					}
				}
				

				default: {
					return false;
				}
			}
		}
		
		
		public function url_stat() {
			return Array();
		}
		
		
		public function stream_stat() {
			return Array();
		}
		
		
		public function stream_close() {
			return true;
		}
		
		
		public function stream_read($count) {
			$result = bytes_substr($this->data, $this->position, $count);
			$this->position += $count;
			return $result;
		}
		
		
		public function stream_write($inputData) {
			$inputDataLength = bytes_strlen($inputData);

		        $dataLeft = bytes_substr($this->data, 0, $this->position);
			$dataRight = bytes_substr($this->data, $this->position + $inputDataLength);

			$this->data = $dataLeft . $inputData . $dataRight;

			$this->position += $inputData;
			return $inputDataLength;
		}
		
		
		public function getProtocol() {
			return $this->scheme . "://";
		}
		
		
		protected function isValidOffset($offset) {
			return ($offset >= 0) && ($offset < $this->length);
		}
		
		
		protected function translateToXml($res = Array()) {
			$executionTime = number_format(microtime(true) - $this->start_time, 6);
			self::reportCallTime($this->getProtocol() . $this->path, $executionTime);
			
			if(isset($res['plain:result'])) {
				return $res['plain:result'];
			}

			$dom = new DOMDocument("1.0", "utf-8");
			$xmlelement = $dom->createElement("udata");
			$dom->appendChild($xmlelement);
			$res['attribute:generation-time'] = $executionTime;
		
			$xslTranslator = new xmlTranslator($dom);
			$xslTranslator->translateToXml($xmlelement, $res);
			
			if($this->transform) {
				return $this->applyXslTransformation($dom, $this->transform);
			}
						
			return $dom->saveXml();
		}
		
		
		protected function applyXslTransformation(DOMDocument $dom, $xslFilePath) {
			$xsltDom = DomDocument::load("./xsltTpls/" . $xslFilePath, DOM_LOAD_OPTIONS);
			checkXmlError($xsltDom);

			$xslt = new xsltProcessor;
			$xslt->registerPHPFunctions();
			$xslt->importStyleSheet($xsltDom);
			return $xslt->transformToXML($dom);
		}
		
		
		protected function parsePath($path) {
			$protocol = $this->getProtocol();
			$path = substr($path, strlen($protocol));
			
			$parsed_url = parse_url($path);
			$this->path = $parsed_url['path'];
			
			self::$callLog[] = Array($protocol . $path, false);
			
			if($params = getArrayKey($parsed_url, 'query')) {
				parse_str($params, $params_arr);
				$this->params = $params_arr;
				
				$_REQUEST = array_merge($_REQUEST, $params_arr);
				
				if(isset($params_arr['expire'])) {
					$this->expire = getArrayKey($params_arr, 'expire');
				}
				
				if(isset($params_arr['transform'])) {
					$this->transform = getArrayKey($params_arr, 'transform');
				}
				
			}
			return $this->path;
		}
		
		
		protected function normalizeString($str) {
			$str = urldecode($str);

			if(!preg_match("/[\x{0000}-\x{FFFF}]+/u", $str)) {
				$str = iconv("CP1251", "UTF-8//IGNORE", $str);
			}

			return $str;
		}
		
		protected function setData($data) {
		    if(!$data) {
		        return false;
		    }
		    
		    $this->data = $data;
		    $this->length = bytes_strlen($data);
		    return true;
		}
		
		
		static public function registerStream($scheme) {
			$filepath = CURRENT_WORKING_DIR . "/classes/streams/{$scheme}/{$scheme}Stream.php";
			if(file_exists($filepath)) {
				include $filepath;
				if(!stream_wrapper_register($scheme, "{$scheme}Stream")) {
					throw new coreException("Failed to register stream \"{$scheme}\"");
				}
			} else {
				throw new coreException("Can't locate file \"{$filepath}\"");
			}
		}


		public static function protectParams($param) {
			return str_replace("/", "&#2F;", $param);
		}
		
		
		public static function unprotectParams($param) {
			return str_replace("&#2F;", "/", $param);
		}
		
		public static function getCalledStreams() {
			$lines_arr = Array();
			foreach(self::$callLog as $callInfo) {
				list($url, $time) = $callInfo;
				
				$line_arr = Array();
				$line_arr['attribute:generation-time'] = $time;
				$line_arr['node:url'] = $url;
				$lines_arr[] = $line_arr;
			}
			$block_arr = Array('nodes:call' => $lines_arr);
			
			
			$dom = new DOMDocument;
			$rootNode = $dom->createElement("streams-call");
			$dom->appendChild($rootNode);
			
			$xmlTranslator = new xmlTranslator($dom);
			$xmlTranslator->translateToXml($rootNode, $block_arr);
			header("Content-type: text/xml; charset=utf-8");
			echo $dom->saveXml();
		}
		
		public static function reportCallTime($path, $time) {
			foreach(self::$callLog as &$callInfo) {
				if($callInfo[0] == $path) {
					$callInfo[1] = $time;
				}
			}
		}
	};



$i18n = Array(
	"header-data-trash"		=> "Корзина удаленных страниц",
	"label-empty-all"		=> "Очистить корзину",
	"label-type-add"		=> "Добавить тип данных",
	"header-data-config"		=> "Настройки модуля",
	"header-data-types"		=> "Типы данных",
	"header-data-type_group_add"	=> "Добавление группы полей",
	"header-data-type_edit"		=> "Редактирование типа данных",
	"header-data-type_field_add"	=> "Добавление поля",
	"header-data-type_field_edit"	=> "Редактирование поля",
	"header-data-type_group_edit"	=> "Редактирование группы полей",
	"header-data-guides"		=> "Справочники",
	"header-data-guide_item_edit" => "Редактирование элемента справочника",

	"label-type-name"		=> "Название типа",
	"label-hierarchy-type"		=> "Назначение типа",
	"label-is-public"		=> "Общедоступный",
	"label-is-guide"		=> "Можно использовать как справочник",
	"label-edit-type-common"	=> "Свойства типа",
	"label-add-fields-group"	=> "Добавить группу полей",

	"label-group-title"		=> "Название группы",
	"label-group-name"		=> "Идентификатор",
	"label-group-is-visible"	=> "Видимое",

	"label-field-title"		=> "Название поля",
	"label-field-name"		=> "Идентификатор",
	"label-field-tip"		=> "Подсказка",
	"label-field-is-visible"	=> "Видимое",
	"label-field-in-search"		=> "Индексировать",
	"label-field-in-filter"		=> "Использовать в фильтрах",
	"label-field-type"		=> "Тип поля",
	"label-field-default-guide"	=> "Использовать справочник",
	"label-field-is-required"		=> "Обязательное",

	"field-is_active"		=> "Активность",

	"label-guide-add"		=> "Добавить справочник",
	"label-module"          => "Модуль",
	"label-method"          => "Метод",

	"field-name"			=> "Название",

	"header-data-guide_items"	=> "Содержание справочника",

	'object-type-blogs-blog_message'	=>	'Cообщение блога',
	'object-type-rss-lenta'			=>	'RSS-лента',
	'object-type-users-author'		=>	'Автор',
	'object-type-eshop-address'		=>	'Адрес доставки',
	'object-type-banners-banner'		=>	'Баннер',
	'object-type-banners-banner-image'		=>	'Баннер с картинкой',
	'object-type-banners-banner-swf'		=>	'Баннер с флешкой',
	'object-type-banners-banner-html'		=>	'Баннер с HTML',
	'object-type-blogs-blog'		=>	'Блог',
	'object-type-valyuta'			=>	'Валюта',
	'object-type-vote-poll_item'		=>	'Вариант ответа на опрос',
	'object-type-vid_homyachka'		=>	'Вид хомячка',
	'object-type-faq-question'		=>	'Вопрос в FAQ',
	'object-type-catalog-question'		=>	'Вопрос в матрице подбора',
	'object-type-dispatches-release'	=>	'Выпуск рассылки',
	'object-type-users-users'		=>	'Группы пользователей',
	'object-type-eshop-discount_card'	=>	'Дисконтная карта',
	'object-type-druzhelyubnost_homyachka'	=>	'Дружелюбность хомячка',
	'object-type-eshop-order'		=>	'Заказ в интернет-магазине',
	'object-type-content-ticket'		=>	'Заметка',
	'object-type-integriruemost_homyachka_v_koleso'			=>	'Интегрируемость хомячка в колесо',
	'object-type-faq-category'		=>	'Категория в FAQ',
	'object-type-kachestvo_fleshki'		=>	'Качество флешки',
	'object-type-catalog-object'		=>	'Объект каталога',
	'object-type-catalog-object-good'		=>	'Товар в магазине',
	'object-type-catalog-object-good-hamster'		=>	'Хомячок',
	'object-type-catalog-object-good-kolesa'		=>	'Колеса для хомячков',
	'object-type-catalog-object-good-povodki'		=>	'Поводки',
	'object-type-comments-comment'		=>	'Комментарий',
	'object-type-blogs-blog_comment'	=>	'Комментарий к сообщению блога',
	'object-type-forum-conf'		=>	'Конференция форума',
	'object-type-kulinarnye_predpochteniya_homyachka'			=>	'Кулинарные предпочтения хомячка',
	'object-type-news-rubric'		=>	'Лента новостей',
	'object-type-updatesrv-license'		=>	'Лицензия UMI.CMS',
	'object-type-catalog-matrix'		=>	'Матрица подбора',
	'object-type-banners-place'		=>	'Места показов баннеров',
	'object-type-eshop-order_item'		=>	'Наименование в заказе',
	'object-type-news-item'			=>	'Новость',
	'object-type-okras_homyachka'		=>	'Окрас хомячка',
	'object-type-catalog-answer'		=>	'Ответ на вопрос в матрице подбора',
	'object-type-eshop-payment_transaction'	=>	'Платежная транзакция',
	'object-type-dispatches-subscriber'	=>	'Подписчик на рассылку',
	'object-type-pol'			=>	'Пол',
	'object-type-pol_homyachka'		=>	'Пол хомячка',
	'object-type-users-user'		=>	'Пользователь',
	'object-type-faq-project'		=>	'Проект в FAQ',
	'object-type-proizvoditeli'		=>	'Производители',
	'object-type-catalog-category'		=>	'Раздел каталога',
	'object-type-razdel_sajta'		=>	'Раздел сайта',
	'object-type-dispatches-dispatch'	=>	'Рассылка',
	'object-type-filemanager-shared_file'	=>	'Скачиваемый файл',
	'object-type-eshop-discount'		=>	'Скидка в интернет-магазине',
	'object-type-eshop-store'		=>	'Склад интернет-магазина',
	'object-type-forum-message'		=>	'Сообщение в форуме',
	'object-type-dispatches-message'	=>	'Сообщение рассылки',
	'object-type-webforms-address'		=>	'Список адресов',
	'object-type-spisok_gorodov_dlya_geo'	=>	'Список городов для geo',
	'object-type-spravochnik_dlya_polya_gorod'	=>	'Справочник для поля "Город"',
	'object-type-spravochnik_dlya_polya_zhanr'	=>	'Справочник для поля "Жанр"',
	'object-type-spravochnik_dlya_polya_nalichiya_na_skladah'	=>	'Справочник для поля "Наличия на складах"',
	'object-type-spravochnik_dlya_polya_ukazatel_na_sklad'		=>	'Справочник для поля "Указатель на склад"',
	'object-type-spravochnik_dlya_polya_format_nositelya'		=>	'Справочник для поля "Формат носителя"',
	'object-type-spravochniki'		=>	'Справочники',
	'object-type-vote-poll'			=>	'Стандартный опрос',
	'object-type-eshop-order_status'	=>	'Статус заказа',
	'object-type-eshop-payment_transaction_status'	=>	'Статус платежной транзакции',
	'object-type-blogs-blog_privacy'	=>	'Степень приватности (блоги)',
	'object-type-content-'			=>	'Страница контента',
	'object-type-strany'			=>	'Страны',
	'object-type-news-subject'		=>	'Сюжет публикации',
	'object-type-tip_rss'			=>	'Тип RSS',
	'object-type-updatesrv-license_type'	=>	'Тип лицензии UMI.CMS',
	'object-type-tip_povodka'		=>	'Тип поводка',
	'object-type-tip_shersti_homyachka'	=>	'Тип шерсти хомячка',
	'object-type-eshop-store_relation'	=>	'Товар на складе интернет-магазина',
	'object-type-forum-topic'		=>	'Топик в форуме',
	'object-type-webforms-form'		=>	'Форма обратной связи',
	'object-type-photoalbum-album'		=>	'Фотоальбом',
	'object-type-photoalbum-photo'		=>	'Фотография',
	'object-type-harakter_homyachka'	=>	'Характер хомячка',
	'object-type-cvet'			=>	'Цвет',
	'object-type-webforms-template'		=>	'Шаблон письма',
	'object-type-new-data-type' 		=>  'Новый тип данных',
	'object-type-new-guide' 			=> 	'Новый справочник',

	'hierarchy-type-content-page'		=>	'Страницы контента',
	'hierarchy-type-users-user'		=>	'Пользователи',
	'hierarchy-type-catalog-category'	=>	'Разделы каталога',
	'hierarchy-type-catalog-object'		=>	'Объекты каталога',
	'hierarchy-type-users-users'		=>	'Группы пользователей',
	'hierarchy-type-news-rubric'		=>	'Ленты новостей',
	'hierarchy-type-news-item'		=>	'Новости',
	'hierarchy-type-news-subject'		=>	'Сюжет публикации',
	'hierarchy-type-vote-poll'		=>	'Опрос',
	'hierarchy-type-vote-poll_item'		=>	'Ответ в опросе',
	'hierarchy-type-eshop-order'		=>	'Заказ в интернет-магазине',
	'hierarchy-type-eshop-order_item'	=>	'Наименование заказа в интернет-магазине',
	'hierarchy-type-eshop-order_status'	=>	'Статус заказа в интернет-магазине',
	'hierarchy-type-eshop-address'		=>	'Адрес доставки',
	'hierarchy-type-forum-conf'		=>	'Конференция форума',
	'hierarchy-type-forum-topic'		=>	'Топик в форуме',
	'hierarchy-type-forum-message'		=>	'Сообщение в форуме',
	'hierarchy-type-comments-comment'	=>	'Комментарий',
	'hierarchy-type-updatesrv-license'	=>	'Лицензия UMI.CMS',
	'hierarchy-type-updatesrv-license_type'	=>	'Тип лицензии UMI.CMS',
	'hierarchy-type-banners-banner'		=>	'Баннер',
	'hierarchy-type-banners-place'		=>	'Место показа баннера',
	'hierarchy-type-dispatches-dispatch'	=>	'Рассылка',
	'hierarchy-type-dispatches-release'	=>	'Выпуск рассылки',
	'hierarchy-type-dispatches-message'	=>	'Сообщение рассылки',
	'hierarchy-type-dispatches-subscriber'	=>	'Подписчик на рассылку',
	'hierarchy-type-catalog-matrix'		=>	'Матрица подбора',
	'hierarchy-type-catalog-question'	=>	'Вопрос в матрице подбора',
	'hierarchy-type-catalog-answer'		=>	'Ответ на вопрос в матрице подбора',
	'hierarchy-type-users-author'		=>	'Автор',
	'hierarchy-type-content-ticket'		=>	'Заметка на сайте',
	'hierarchy-type-photoalbum-album'	=>	'Фотоальбом',
	'hierarchy-type-photoalbum-photo'	=>	'Фотография',
	'hierarchy-type-faq-project'		=>	'Проекты в FAQ',
	'hierarchy-type-faq-category'		=>	'Категории в FAQ',
	'hierarchy-type-faq-question'		=>	'Вопросы в FAQ',
	'hierarchy-type-filemanager-shared_file'	=>	'Скачиваемый файл',
	'hierarchy-type-eshop-store'		=>	'Склад в интернет-магазине',
	'hierarchy-type-eshop-store_relation'	=>	'Товар на складе интернет-магазина',
	'hierarchy-type-eshop-discount_card'	=>	'Дисконтная карта',
	'hierarchy-type-eshop-discount'		=>	'Скидка в интернет-магазине',
	'hierarchy-type-blogs-blog'		=>	'Блог',
	'hierarchy-type-blogs-blog_privacy'	=>	'Степень приватности блога',
	'hierarchy-type-blogs-blog_comment'	=>	'Комментарий к сообщению блога',
	'hierarchy-type-blogs-blog_message'	=>	'Сообщение блога',
	'hierarchy-type-eshop-payment_transaction'	=>	'Платежная транзакция',
	'hierarchy-type-eshop-payment_transaction_status'	=>	'Статус платежной транзакции',
	'hierarchy-type-webforms-form'		=>	'Форма обратной связи',
	'hierarchy-type-webforms-template'	=>	'Шаблон письма',
	'hierarchy-type-webforms-address'	=>	'Список адресов',
	"hierarchy-type-eshop-global-discount" => "Глобальная скидка в интернет магазине",
	'hierarchy-type-eshop-global_discount' => 'Глобальная скидка в интернет магазине',
	'hierarchy-type-blogs20-blog' => 'Блог',
	'hierarchy-type-blogs20-comment' => 'Комментарий блога',

	'field-min_discount_order_total'	=> 'Минимальный размер заказа',
	'field-max_discount_order_total'	=> 'Максимальный размер заказа',
	'field-preffered_currency'			=> 'Предпочитаемая валюта',
	'field-age'				=>	'Возраст',
	'field-gender'				=>	'Пол',
	'field-geo_targeting_is_active'		=>	'Гео-таргетинг включен',
	'field-avatar'				=>	'Аватарка',
	'field-url'				=>	'Url страницы',
	'field-rss_type'			=>	'Тип',
	'field-news_rubric'			=>	'Раздел публикаций',
	'field-anons_pic'			=>	'Картинка для анонса',
	'field-publish_pic'			=>	'Картинка для публикации',
	'field-customer_comments'		=>	'Пометки покупателя',
	'field-default_type_id'			=>	'default_type_id',
	'field-seo_prefix'			=>	'seo_prefix',
	'field-trans'				=>	'Транзитный Id',
	'field-proizvoditel'			=>	'Производитель',
	'field-predyduwaya_cena'		=>	'Предыдущая цена',
	'field-specialnaya_cena'		=>	'Специальная цена',
	'field-fiksirovannaya_cena'		=>	'Фиксированная цена',
	'field-opisanie'			=>	'Описание товара',
	'field-soputstvuyuwie_tovary'		=>	'Сопутствующие товары',
	'field-polosa_tehnologij'		=>	'Полоса технологий',
	'field-izobrazhenie'			=>	'Изображение',
	'field-bolshoe_izobrazhenie'		=>	'Большое изображение',
	'field-id_name'				=>	'ID статуса',
	'field-tip_igrovoj_pristavki'		=>	'Тип игровой приставки',
	'field-komplekt_postavki'		=>	'Комплект поставки',
	'field-tip_karty_pamyati'		=>	'Тип карты памяти',
	'field-razemy_tip'			=>	'Разъемы (тип)',
	'field-cvet_korpusa'			=>	'Цвет корпуса',
	'field-garantiya'			=>	'Гарантия',
	'field-tranzitnyj_id'			=>	'Транзитный ID',
	'field-special_naya_cena'		=>	'Специальная цена',
	'field-bol_shoe_izobrazhenie'		=>	'Большое изображение',
	'field-yes_or_no'			=>	'Да или нет?',
	'field-country'				=>	'Страна',
	'field-city'				=>	'Город',
	'field-post_index'			=>	'Почтовый индекс',
	'field-address'				=>	'Адрес',
	'field-phone'				=>	'Номер телефона для контрольного звонка',
	'field-codename'			=>	'Идентефикатор версии',
	'field-ot_baldi'			=>	'Какое-то поле',
	'field-my_house'			=>	'Фотка моего дома',
	'field-site_link'			=>	'Ссылка на сайт',
	'field-company_logo'			=>	'Логитип фирмы',
	'field-elements_links'			=>	'Ссылки на элементы',
	'field-kartinochka'			=>	'Картиночка',
	'field-objektiki'			=>	'Объектики',
	'field-currency_id'			=>	'Код валюты',
	'field-currency'			=>	'Валюта',
	'field-rate'				=>	'Курс',
	'field-model'				=>	'Модель товара',
	'field-isbn'				=>	'ISBN',
	'field-author'				=>	'Автор',
	'field-publisher'			=>	'Издатель',
	'field-year'				=>	'Год',
	'field-series'				=>	'Серия',
	'field-nazvanie'			=>	'Название',
	'field-media'				=>	'Носитель',
	'field-starring'			=>	'Актеры',
	'field-director'			=>	'Режиссер',
	'field-original_name'			=>	'Оригинальное название',
	'field-store'				=>	'Количество на складе',
	'field-quality_value'			=>	'Значение',
	'field-to_order'			=>	'на заказ (при отсутствии на складе)',
	'field-typePrefiks'			=>	'префикс',
	'field-place'				=>	'Место показа',
	'field-hall'				=>	'Зал',
	'field-date'				=>	'Дата выпуска',
	'field-premier'				=>	'Премьера',
	'field-for-kids'			=>	'для детей',
	'field-hall_plan'			=>	'План зала',
	'field-hall_part'			=>	'Места',
	'field-worldRegion'			=>	'Часть света',
	'field-region'				=>	'Курорт или город',
	'field-days'				=>	'Количество дней',
	'field-dataTour'			=>	'Даты заездов',
	'field-hotel_stars'			=>	'Звезды',
	'field-room'				=>	'Тип комнаты',
	'field-meal'				=>	'Тип питания',
	'field-included'			=>	'Что включено в стоимость тура',
	'field-transport'			=>	'Транспорт',
	'field-plus'				=>	'Процент (+)',
	'field-is_cbrf'				=>	'Использовать CBRF',
	'field-deliveryIncluded'		=>	'Доставка',
	'field-price_min'			=>	'Минимальная цена',
	'field-price_max'			=>	'Максимальная цена',
	'field-sales_notes'			=>	'Особенности товара',
	'field-color'				=>	'Цвет',
	'field-weight'				=>	'Вес',
	'field-power'				=>	'мощность',
	'field-description'			=>	'Описание',
	'field-'				=>	'Продолжительность',
	'field-descr'				=>	'Описание',
	'field-order_price'			=>	'Сумма счета',
	'field-elements'			=>	'Разделы каталога',
	'field-hex'				=>	'Цветовой код',
	'field-zhanr'				=>	'Жанр',
	'field-kratkoe_opisanie'		=>	'Краткое описание',
	'field-format_nositelya'		=>	'Формат носителя',
	'field-god_vypuska'			=>	'Год выпуска',
	'field-vozrastnoe_ogranichenie_na_prosmotr'	=>	'Возрастное ограничение на просмотр',
	'field-vid'				=>	'Вид',
	'field-okras'				=>	'Окрас',
	'field-tip_shersti'			=>	'Тип шерсти',
	'field-ves'				=>	'Вес',
	'field-pol'				=>	'Пол',
	'field-kolichestvo_lap'			=>	'Количество лап',
	'field-dlina_usov'			=>	'Длина усов',
	'field-maksimalnaya_skorost'		=>	'Максимальная скорость',
	'field-obem_legkih'			=>	'Объем легких',
	'field-razmah_lap_v_bege'		=>	'Размах лап в беге',
	'field-harakter'			=>	'Характер',
	'field-druzhelyubnost'			=>	'Дружелюбность',
	'field-kulinarnye_predpochteniya'	=>	'Кулинарные предпочтения',
	'field-integriruemost_v_koleso'		=>	'Интегрируемость в колесо',
	'field-photo'				=>	'Фотография',
	'field-diametr_kolesa'			=>	'Диаметр колеса',
	'field-type'				=>	'Тип',
	'field-s_shipami'			=>	'С шипами',
	'field-recommend'			=>	'Рекомендуем',
	'field-lock_cancel'			=>	'Статус блокирует отмену заказа',
	'field-lock_payment'			=>	'Статус блокирует оплату заказа',
	'field-destination_address'		=>	'Адрес доставки',
	'field-sender_ip'			=>	'IP-адрес отправителя',
	'field-from_email_template'		=>	'Адрес от',
	'field-from_template'			=>	'Имя от',
	'field-subject_template'		=>	'Тема письма',
	'field-master_template'			=>	'Шаблон тела письма',
	'field-autoreply_from_email_template'	=>	'Адрес получателя',
	'field-autoreply_from_template'		=>	'Имя получателя',
	'field-autoreply_subject_template'	=>	'Тема',
	'field-autoreply_template'		=>	'Тело',
	'field-form_id'				=>	'Идентификатор формы',
	'field-address_description'		=>	'Описание',
	'field-address_list'			=>	'Адреса',
	'field-fname'				=>	'Имя',
	'field-father_name'			=>	'Отчество',
	'field-lname'				=>	'Фамилия',
	'field-e-mail'				=>	'E-mail',
	'field-time_targeting_is_active'	=>	'Time-таргетинг включен',
	'field-time_targeting_by_month_days'	=>	'По числам месяца',
	'field-time_targeting_by_month'		=>	'По месяцам',
	'field-time_targeting_by_week_days'	=>	'По дням недели',
	'field-time_targeting_by_hours'		=>	'По времени суток',
	'field-title'				=>	'Поле TITLE',
	'field-h1'				=>	'Поле H1',
	'field-meta_keywords'			=>	'Поле meta KEYWORDS',
	'field-meta_descriptions'		=>	'Поле meta DESCRIPTIONS',
	'field-content'				=>	'Контент',
	'label-field-content'				=>	'Контент',
	'field-menu_pic_ua'			=>	'Изображение неактивного раздела',
	'field-menu_pic_a'			=>	'Изображение активного раздела',
	'field-header_pic'			=>	'Изображение для заголовка',
	'field-robots_deny'			=>	'Запретить индексацию поисковиками',
	'field-show_submenu'			=>	'Показывать подменю',
	'field-is_expanded'			=>	'Меню всегда развернуто',
	'field-is_unindexed'			=>	"Исключить из поиска",
	'field-login'				=>	'Логин',
	'field-password'			=>	'Пароль',
	'field-groups'				=>	'Группы пользователей',
	'field-readme'				=>	'Описание',
	'field-anons'				=>	'Анонс',
	'label-field-anons'				=>	'Анонс',
	'field-source'				=>	'Источник',
	'field-source_url'			=>	'URL источника',
	'field-publish_time'			=>	'Дата публикации',
	'field-begin_time'			=>	'Дата начала активности',
	'field-finish_time'			=>	'Дата завершения активности',
	'field-end_time'			=>	'Дата завершения скидки',
	'field-subjects'			=>	'Входит в сюжеты',
	'field-price_item'			=>	'Цена за единицу товара',
	'field-price_total'			=>	'Цена итоговая',
	'field-count'				=>	'Количество ответов',
	'field-discount_size'			=>	'Размер скидки',
	'field-catalog_relation'		=>	'Ссылка на товар',
	'field-items'				=>	'Наименования',
	'field-status'				=>	'Состояние',
	'field-order_time'			=>	'Дата заказа',
	'field-admin_comments'			=>	'Пометки администратора',
	'field-is_closed'			=>	'Голосование закрыто',
	'field-question'			=>	'Вопрос',
	'field-answers'				=>	'Ответы',
	'field-total_count'			=>	'Всего проголосовало',
	'field-cena'				=>	'Цена',
	'field-orders_refs'			=>	'Заказы',
	'field-delivery_address'		=>	'Адрес доставки',
	'field-delivery_addresses'		=>	'Адреса доставки',
	'field-message'				=>	'Сообщение',
	'field-activate_code'			=>	'Код активации',
	'field-is_activated'			=>	'Активирован',
	'field-domain_name'			=>	'Домен',
	'field-ip'				=>	'ip',
	'field-license_type'			=>	'Тип лицензии',
	'field-owner_lname'			=>	'Фамилия',
	'field-owner_fname'			=>	'Имя',
	'field-owner_mname'			=>	'Отчество',
	'field-owner_email'			=>	'E-mail',
	'field-keycode'				=>	'Лицензионный ключ',
	'field-gen_time'			=>	'Дата создания',
	'field-id'				=>	'ID',
	'field-views_count'			=>	'Количество показов',
	'field-clicks_count'			=>	'Количество переходов',
	'field-max_views'			=>	'Максимальное количество показов',
	'field-tags'				=>	'Теги',
	'field-is_active'			=>	'Активен',
	'field-view_pages'			=>	'Страницы, на которых показывать баннер',
	'field-image'				=>	'Изображение',
	'field-open_in_new_window'		=>	'Открывать в новом окне',
	'field-width'				=>	'Ширина',
	'field-height'				=>	'Высота',
	'field-alt'				=>	'Альтернативный текст',
	'field-swf'				=>	'Флеш-ролик',
	'field-swf_quality'			=>	'Качество ролика',
	'field-html_content'			=>	'HTML-содержание',
	'field-show_till_date'			=>	'Дата окончания показа',
	'field-poll_rel'			=>	'Указатель на опрос',
	'field-is_show_rand_banner'		=>	'Показ случайного баннера',
	'field-show_start_date'			=>	'Дата начала показа',
	'field-disp_last_release'		=>	'Дата последнего выпуска',
	'field-disp_description'		=>	'Описание',
	'field-disp_reference'			=>	'Ссылка на рассылку',
	'field-header'				=>	'Заголовок',
	'field-body'				=>	'Тело сообщения',
	'field-attach_file'			=>	'Прикрепленный файл',
	'field-release_reference'		=>	'Ссылка на выпуск',
	'field-uid'					=>	'Пользователь',
	'field-subscriber_dispatches'		=>	'Подписан на рассылки',
	'field-subscribe_date'			=>	'Дата подписки',
	'field-related_items'			=>	'Подходящие товары',
	'field-question_txt'			=>	'Текст вопроса',
	'field-answers_rel'			=>	'Список ответов',
	'field-per_page'			=>	'Количество выводимых результатов',
	'field-goods'				=>	'Товары',
	'field-questions_rel'			=>	'Список вопросов',
	'field-is_registrated'			=>	'Зарегистрирован',
	'field-user_id'				=>	'Владелец',
	'field-nickname'			=>	'Ник',
	'field-email'				=>	'E-mail',
	'field-author_id'			=>	'Автор',
	'field-x'				=>	'X',
	'field-y'				=>	'Y',
	'field-create_time'			=>	'Дата создания',
	'field-answer'				=>	'Ответ на вопрос',
	'field-fs_file'				=>	'Скачиваемый файл',
	'field-downloads_counter'		=>	'Количество загрузок',
	'field-user_dock'			=>	'Пользовательская панель',
	'field-user_tags'			=>	'Показывать пользователям с тэгами',
	'field-rate_voters'			=>	'Количество проголосовавших',
	'field-rate_sum'			=>	'Сумма баллов',
	'field-amount'				=>	'Количество на складе',
	'field-store_id'			=>	'Указатель на склад',
	'field-store_amounts'			=>	'Наличия на складах',
	'field-proc'				=>	'Процент скидки',
	'field-start_time'			=>	'Дата начала действия скидки',
	'field-code'				=>	'Код',
	'field-price'				=>	'Цена',
	'field-ignore_discounts'		=>	'Игнорировать скидки',
	'field-subscribed_pages'		=>	'Подписки на изменения',
	'field-is_online'			=>	'Пользователь on-line',
	'field-privacy'				=>	'Приватность',
	'field-sid'				=>	'sid',
	'field-prvlist_friends'			=>	'Мои друзья',
	'field-lmessage_time'			=>	'Дата добавления последнего сообщения',
	'field-lcomment_time'			=>	'Дата добавления последнего комментария',
	'field-privacy_forpostonly'		=>	'Приватность действует только на комментирование (не на просмотр)',
	'field-topics_count'			=>	'Количество топиков',
	'field-messages_count'			=>	'Количество сообщений',
	'field-last_post_time'			=>	'Дата последнего добавления',
	'field-last_request_time'		=>	'Время последнего обращения',
	'field-rated_pages'			=>	'Рейтингованные страницы',
	'field-sid_transaction'			=>	'Строковый идентификатор',
	'field-sid_eshoporder_status'		=>	'Соотнесенный статус заказа',
	'field-rel_transactionstatus'		=>	'Статус транзакции',
	'field-date_created'			=>	'Дата создания',
	'field-inited_whom'			=>	'Инициализатор транзакции',
	'field-rel_eshoporder'			=>	'Оплачиваемый заказ',
	'field-initprice'			=>	'Сумма заказа',
	'field-text_request'			=>	'Подробности запроса',
	'field-method_engine'			=>	'Transaction engine',
	'field-date_answered'			=>	'Дата получения результата',
	'field-text_answer'			=>	'Подробности получения результата',
	'field-date_validated'			=>	'Дата проверки результата',
	'field-text_validated'			=>	'Подробности проверки результата',
	'field-date_conseq'			=>	'Дата проверки',
	'field-text_conseq'			=>	'Подробности проверки',
	'field-city_targeting_city'		=>	'Город',
	'field-city_targeting_is_active'	=>	'Геотаргетинг включен',
	'field-news_relation'			=> 'Связано с лентой новостей',
	'field-ignore-banner-subpages'	=> 'Игнорировать подстраницы',
	'field-publish-status'		=>	'Статус публикации',



	'fields-group-short_info'		=>	'Краткая информация',
	'fields-group-more_info'		=>	'Дополнительная информация',
	'fields-group-common'			=>	'Основные',
	'fields-group-view_params'		=>	'Параметры показа',
	'fields-group-time_targeting'		=>	'Time-таргетинг',
	'fields-group-menu_view'		=>	'Отображение в меню',
	'fields-group-more_params'		=>	'Дополнительные параметры',
	'fields-group-idetntify_data'		=>	'Идентификационные данные',
	'fields-group-item_props'		=>	'Свойства публикации',
	'fields-group-subjects_block'		=>	'Сюжеты',
	'fields-group-order_item_props'		=>	'Свойства наименования',
	'fields-group-order_props'		=>	'Свойства заказа',
	'fields-group-comments'			=>	'Пометки',
	'fields-group-common_props'		=>	'Общие свойства',
	'fields-group-poll_props'		=>	'Свойства опроса',
	'fields-group-status_props'		=>	'Свойства статусы',
	'fields-group-delivery_address'		=>	'Адрес для доставки',
	'fields-group-topic_props'		=>	'Свойства топика',
    'fields-group-message_props'        =>    'Свойства сообщения',
	'fields-group-comment_props'		=>	'Свойства комментария',
	'fields-group-license_info'		=>	'Свойства лицензии',
	'fields-group-owner_info'		=>	'Информация о клиенте',
	'fields-group-currency_props'		=>	'Свойства валюты',
	'fields-group-view_pages'		=>	'Разделы отображения',
	'fields-group-banner_custom_props'	=>	'Индивидуальные параметры баннера',
	'fields-group-redirect_props'		=>	'Параметры перехода',
	'fields-group-props'			=>	'Свойства',
	'fields-group-grp_disp_props'		=>	'Свойства рассылки',
	'fields-group-grp_disp_release_props'	=>	'Свойства выпуска рассылки',
	'fields-group-grp_disp_msg_props'	=>	'Свойства сообщения рассылки',
	'fields-group-grp_sbs_props'		=>	'Информация о подписчике',
	'fields-group-grp_disp_msg_extended'	=>	'Дополнительные свойства',
	'fields-group-grp_sbs_extended'		=>	'Параметры подписки',
	'fields-group-matrix_props'		=>	'Свойства матрицы',
	'fields-group-photo_props'		=>	'Свойства фотографии',
	'fields-group-album_props'		=>	'Свойства фотоальбома',
	'fields-group-svojstva_gruppy_polzovatelej'		=>	'Свойства группы пользователей',
	'fields-group-fs_file_props'		=>	'Свойства файла',
	'fields-group-news_images'		=>	'Изображения новости',
	'fields-group-dopolnitelno'		=>	'Дополнительно',
	'fields-group-rate_props'		=>	'Свойства рейтинга',
	'fields-group-rate_voters'		=>	'Количество проголосовавших',
	'fields-group-store_props'		=>	'Свойства склада',
	'fields-group-cenovye_svojstva'		=>	'Ценовые свойства',
	'fields-group-other_proerties'		=>	'Характеристики хомячка',
	'fields-group-pictures'			=>	'Изображения',
	'fields-group-descr_grp'		=>	'Описание товара',
	'fields-group-parametry_aksessuara'	=>	'Параметры аксессуара',
	'fields-group-recommend'		=>	'Рекомендуем',
	'fields-group-common_group'		=>	'Общие параметры',
	'fields-group-privatnost'		=>	'Приватность',
	'fields-group-identity'			=>	'identity',
	'fields-group-transaction_status_props'	=>	'Свойства',
	'fields-group-transaction_props'	=>	'Свойства транзакции',
	'fields-group-trans_init'		=>	'Инициализация',
	'fields-group-trans_deliver'		=>	'Доставка',
	'fields-group-prelim'			=>	'Результат',
	'fields-group-final'			=>	'Подтверждение',
	'fields-group-consequences'		=>	'Послесловие',
	'fields-group-city_targeting'		=>	'Геотаргетинг',
	'fields-group-SendingData'		=>	'Данные для отправки',
	'fields-group-Templates'		=>	'Письмо',
	'fields-group-Binding'			=>	'Привязка',
	'fields-group-list'			=>	'Список',
	'fields-group-short_user_info'		=> 'Краткая информация',

	'field-type-int'			=>	'Число',
	'field-type-string'			=>	'Строка',
	'field-type-text'			=>	'Простой текст',
	'field-type-relation'			=>	'Выпадающий список',
	'field-type-relation-multiple'		=>	'Выпадающий список с множественным выбором',
	'field-type-file'			=>	'Файл',
	'field-type-img_file'			=>	'Изображение',
	'field-type-swf_file'			=>	'Флеш-ролик',
	'field-type-date'			=>	'Дата',
	'field-type-boolean'			=>	'Кнопка-флажок',
	'field-type-wysiwyg'			=>	'HTML-текст',
	'field-type-password'			=>	'Пароль',
	'field-type-tags-multiple'		=>	'Теги',
	'field-type-symlink-multiple'		=>	'Ссылка на дерево',
	'field-type-price'			=>	'Цена',
	'field-type-float'			=>	'Число с точкой',

	'field-expiration-date'		=>	'Дата окончания актуальности',
	'field-notification-date'	=>	'Дата предупреждения об окончании актуальности',
	'field-publish_comments'	=>	'Комментарий к публикации',
	'field-date-empty'			=>	'Никогда',
	'object-samka'				=>	'Самка',
	'object-samec'				=>	'Самец',
	'object-v_korzine'			=>	'В корзине',
	'object-ozhidaet_proverki'		=>	'Ожидает проверки',
	'object-prinyat'			=>	'Принят',
	'object-otklonen'			=>	'Отклонен',
	'object-otmenen'			=>	'Отменен',
	'object-gotov'				=>	'Готов',
	'object-v_processe_oplaty'		=>	'В процессе оплаты',
	'object-oplachen_uspeshno'		=>	'Оплачен успешно',
	'object-oshibki_v_oplate'		=>	'Ошибки в оплате',
	'object-proveren_mozhno_oplachivat'	=>	'Проверен, можно оплачивать',
	'object-proverka_oplaty'		=>	'Проверка оплаты',
	'object-1_inicializirovana'		=>	'1. Инициализирована',
	'object-2_dostavlena'			=>	'2. Доставлена',
	'object-3_ne_dostavlena'		=>	'3. Не доставлена',
	'object-4_predvaritel_no_prinyata_ozhidaetsya_podtverzhdenie'			=>	'4. Предварительно принята (ожидается подтверждение)',
	'object-5_predvaritel_no_prinyata_podtverzhdenie_ne_trebuetsya'			=>	'5. Предварительно принята (подтверждение не требуется)',
	'object-6_predvaritel_no_otklonena_ozhidaetsya_podtverzhdenie'			=>	'6. Предварительно отклонена (ожидается подтверждение)',
	'object-7_predvaritel_no_otklonena_podtverzhdenie_ne_trebuetsya'		=>	'7. Предварительно отклонена (подтверждение не требуется)',
	'object-8_prinyata_s_podtverzhdeniem'	=>	'8. Принята с подтверждением',
	'object-9_otklonena_s_podtverzhdeniem'	=>	'9. Отклонена с подтверждением',
	'object-moya_zhzh-lenta'		=>	'Моя ЖЖ-лента',
	'object-rss'				=>	'RSS',
	'object-atom'				=>	'ATOM',
	'object-dlya_vseh'			=>	'Для всех',
	'object-dlya_vladel_ca_i_druzej'	=>	'Для владельца и друзей',
	'object-tol_ko_dlya_vladel_ca_bloga'	=>	'Только для владельца блога',

	'object-height'				=> 'Высокое',
	'object-low'				=> 'Низкое',
	'object-medium'				=> 'Среднее',

	'object-status-preunpublish'	=>	'Готовится к снятию с публикации',
	'object-status-publish'		=>	'Опубликован',
	'object-status-unpublish'	=>	'Снят с публикации',

	'perms-data-main' => 'Просмотр объектов',
	'perms-data-guides' => 'Управление справочниками',
	'perms-data-trash' => 'Мусорная корзина',
	'perms-data-types' => 'Управление шаблонами данных',

	'field-form_sending_time' => 'Время отправки',
	'field-auto_reply'			=> 'Автоответ',

	'js-data-add-field'			=> 'Добавить поле',
	'js-view-guide-items'		=> 'Содержимое справочника',

	'group-currency-props'		=> 'Свойства валюты',

	'min-discount-order-total'	=>	'Минимальная сумма заказа',
	'max-discount-order-total'	=>	'Максимальная сумма заказа',
	'global-discount-order-size'	=> 'Сумма глобальной скидки',
	'global-discount-end'		=> 'Дата окончания действия',
	'global-discount-start'		=>	'Дата начала действия',
	'global-discount-active-time'	=> 'Время действия скидки',
	'order-total-range'		=> 	'Диапазон суммы заказа',
	'eshop-order-currency'	=> 'Валюта в интернет магазине',

	'eshop-currency'	=> 'Валюта в интернет магазине',

	'eshop-currency-letter-code'	=> 'Буквенный код',
	'eshop-currency-digit-code'		=>	'Цифровой код',
	'eshop-currency-exchange-rate'	=>	'Курс обмена',
	'eshop-order-currency-id'		=> 'Идентификатор валюты',
	'locktime'						=> 'Время начала блокировки',
	'lockuser'						=> 'Пользователь заблокировавший страницу',
	'label-section-description'     => 'Описание раздела',
	"eshop-order-currency-exchange_rate"	=> "Курс обмена",
	"eshop-order-currency-total"		=> "Сумма в валюте заказа",
	"date-empty-field"				=> "Никогда",


	'object-type-users-avatar'			=>	'Аватара',
	'object-type-blogs20-blog'			=>	'Блог 2.0',
	'object-type-eshop-currency'			=>	'Валюта в интернет магазине',
	'object-type-blogs20-comment'			=>	'Комментарий блога 2.0',
	'object-type-blogs20-post'			=>	'Пост блога 2.0',
	'object-type-status_stranicy'			=>	'Статус страницы',
	'object-type-webforms-page'			=>	'Страница с формой обратной связи',
	'hierarchy-type-users-avatar'			=>	'Аватар пользователя',
	'hierarchy-type-blogs20-post'			=>	'Пост блога',
	'hierarchy-type-eshop-currency'			=>	'Валюта в интернет магазине',
	'hierarchy-type-webforms-page'			=>	'Страница с формой обратной связи',
	'field-body'			=>	'Текст сообщения',
	'field-userpic'			=>	'Загрузить свой',
	'field-picture'			=>	'Картинка',
	'field-is_hidden'			=>	'Скрытая',
	'field-forced_subscribers'			=>	'Принудительно подписанные пользователи:',
	'field-msg_date'			=>	'Дата сообщения',
	'field-short_body'			=>	'Краткий текст сообщения',
	'field-new_relation'			=>	'Ссылка на новость',
	'field-publish_status_id'			=>	'id статуса',
	'field-locktime'			=>	'Время блокировки',
	'field-lockuser'			=>	'Блокирующий пользователь',
	'field-last_message'			=>	'Последнее сообщение',
	'field-friendlist'			=>	'Список друзей',
	'field-only_for_friends'			=>	'Видимо только друзьям',
	'field-user_settings_data'			=>	'Настройки пользователя',
	'field-opinion'			=>	'Как вам сайт?',
	'fields-group-userpic'			=>	'Юзерпик',
	'fields-group-auto_reply'			=>	'Автоответ',
	'fields-group-svojstva_statusa_stranicy'			=>	'Свойства статуса страницы',
	'fields-group-locks'			=>	'Блокировка',
	'fields-group-privacy'			=>	'Настройки отображения',
	'fields-group-osnovnoe'			=>	'Основное',
	'object-supervajzery'			=>	'Супервайзеры',
	'object-zaregistrirovannye_pol_zovateli'			=>	'Зарегистрированные пользователи',

	'fields-group-svojstva_publikacii' => 'Свойства публикации',
	'field-expiration_date' => 'Дата снятия с публикации',
	'field-notification_date' => 'Дата уведомления',
	'field-publish_status' => 'Статус публикации',

	'field-global-discount-size'		=> 'Сумма глобальной скидки',
	'field-eshop_order_currency'		=> 'Валюта в интернет магазине',
	'field-currency_exchange_rate'		=> 'Курс обмена',
	'field-eshop_order_currency_total'	=> 'Сумма в валюте заказа',
	'field-sending_time'				=> 'Дата отправки',

	'js-trash-confirm-title'			=> 'Очистка корзины',
	'js-trash-confirm-text'				=> 'Вы собираетесь очистить корзину. Это означает, что все страницы в корзине будут безвозвратно удалены.',
	'js-trash-confirm-cancel'			=> 'Отменить',
	'js-trash-confirm-ok'				=> 'Очистить',
	'eshop-global-discount-proc'		=> 'Процент глобальной скидки',
	
	'fields-group-currency-props'		=> 'Свойства валюты',
	'field-use_in_eshop'				=> 'Использовать в интернет-магазине',
	'field-eshop_currency_letter_code'	=> 'Код валюты',
	'field-eshop_currency_exchange_rate'	=> 'Курс обмена',
	'field-eshop-currency-symbol'				=> 'Сокращенное название валюты'

	);


	class ulangStream extends umiBaseStream {
		protected $scheme = "ulang", $prop_name = NULL;
		protected static $i18nCache = Array();

		public function stream_open($path, $mode, $options, $opened_path) {
			static $cache = Array();
			$path = trim($path, "/");

			if(substr($path, -3) == ".js" || substr($path, -3) == "?js") {
				$path = substr($path, 0, strlen($path) - 3);
				$data = $this->generateJavaScriptLabels($path);
				$this->setData($data);
				return true;
			}
			
			if(isset($cache[$path])) {
				$data = $cache[$path];
			} else {
				$i18nMixed = self::loadI18NFiles($path);
				$data = $cache[$path] = $this->translateToDTD($i18nMixed);
			}
			$this->setData($data);

			return true;
		}


		protected function translateToDTD($phrases) {
			$dtd = "<!ENTITY quote '&#34;'>\n";
			$dtd .= "<!ENTITY nbsp '&#160;'>\n";
			$dtd .= "<!ENTITY middot '&#183;'>\n";
			$dtd .= "<!ENTITY reg '&#174;'>\n";
			$dtd .= "<!ENTITY copy '&#169;'>\n";
			$dtd .= "<!ENTITY raquo '&#187;'>\n";
			$dtd .= "<!ENTITY laquo '&#171;'>\n";
			
			$request_uri = getServer('REQUEST_URI');
			$request_uri = htmlspecialchars($request_uri);
//			$dtd .= "<!ENTITY url-xml-source '" . $request_uri . ".xml'>\n";

			foreach($phrases as $ref => $phrase) {
				if($this->isRestrictedRef($ref)) continue;
				$phrase = $this->protectEntityValue($phrase);
				$dtd .= "<!ENTITY {$ref} \"{$phrase}\">\n";
			}

			return $dtd;
		}
		
		
		protected function isRestrictedRef($ref) {
			$arr = Array('field-', 'object-type-', 'hierarchy-type-', 'fields-group-', 'field-type-');
			
			for($i = 0; $i < sizeof($arr); $i++) {
				if(bytes_substr($ref, 0, bytes_strlen($arr[$i])) == $arr[$i]) {
					return true;
				}
			}
			return false;
		}


		protected function protectEntityValue($val) {
			$from = Array('&', '"');
			$to = Array('&amp;', '&quote;');

			$val = str_replace($from, $to, $val);

			return $val;
		}


		protected static function parseLangsPath($path) {
			$protocol = "ulang://";
			if(substr($path, 0, strlen($protocol)) == $protocol) {
				$path = substr($path, strlen($protocol));
			}
			$path = trim($path, "/");
			return split("\/", $path);
		}


		protected static function loadI18NFiles($path) {
global $i18n;
			static $current_module = "content", $c = 0;

			if(!$current_module) {
				$controller = cmsController::getInstance();
				$current_module = $controller->getCurrentModule();
			}

			$i18nCache = self::$i18nCache;

			$require_list = self::parseLangsPath($path);

			$lang_prefix = self::getLangPrefix();

			$i18nMixed = Array();

			if(!in_array($current_module, $require_list)) {
				$require_list[] = $current_module;
			}

			$sz = sizeof($require_list);
			for($i = 0; $i < $sz; $i++) {
				$require_name = $require_list[$i];
				
				if($require_name == false) continue;

				$filename_primary = "i18n." . $lang_prefix . ".php";
				$filename_secondary = "i18n.php";

				$folder = ($require_name == "common") ? "/classes/modules/" : "/classes/modules/" . $require_name . "/";
				$folder = CURRENT_WORKING_DIR . $folder;

				$path_primary = $folder . $filename_primary;
				$path_secondary = $folder . $filename_secondary;

				if(array_key_exists($require_name, $i18nCache)) {
					$i18n = $i18nCache[$require_name];
				} else {
					if(file_exists($path_primary)) {
						include $path_primary;
					} else if (file_exists($path_secondary)) {
						include $path_secondary;
					}
				}

				if(isset($i18n) && is_array($i18n)) {
					$i18nCache[$require_name] = $i18n;
					$i18nMixed = $i18n + $i18nMixed;
					unset($i18n);
				}
			}
			self::$i18nCache = $i18nCache;
			
			return $i18nMixed;
		}


		public static function getLabel($label, $path = false) {
			static $cache = Array();
			static $langPrefix = false;
			if($langPrefix === false) {
				$langPrefix = self::getLangPrefix();
			}
			
			if($res = cacheFrontend::getInstance()->loadSql($label . "_ulang_label_" . $langPrefix)) {
				//return $res;
			}
			if($path == false) {
				$lang_path = "common/data";
			} else {
				$lang_path = $path;
			}

			if(isset($cache[$lang_path])) {
				$i18nMixed = $cache[$lang_path];
			} else {
				$i18nMixed = self::loadI18NFiles($lang_path);
				$cache[$lang_path] = &$i18nMixed;
			}
			if(isset($i18nMixed[$label])) {
				$res = $i18nMixed[$label];
			} else {
				$res = "{$label}";
			}
			;
			cacheFrontend::getInstance()->saveSql($label . "_ulang_label", $res, 180);
			return $res;
		}


		public static function getI18n($key, $pattern = "") {
			static $cache = Array();
			
			if(!$key) {
				return $key;
			}
			
			$lang_path = "common/data";
			$prefix = "i18n::";
			
			if(isset($cache[$lang_path])) {
				$i18nMixed = $cache[$lang_path];
			} else {
				$i18nMixed = self::loadI18NFiles($lang_path);
				$cache[$lang_path] = $i18nMixed;
			}

			$result = NULL;
			foreach($i18nMixed as $i => $v) {
				if($v == $key) {
					if($pattern) {
						if(substr($i, 0, strlen($pattern)) == $pattern) {
							$result = $prefix . $i;
							break;
						}
					} else {
						$result = $prefix . $i;
						break;
					}
				}
			}

			if(!is_null($result)) {
				$allowedPrefixes = Array(
					'object-type-',
					'hierarchy-type-',
					'field-',
					'fields-group-',
					'field-type-',
					'object-'
				);
				$allowed = false;
				$tmp_result = str_replace("i18n::", "", $pattern);
				foreach($allowedPrefixes as $pattern) {
					$pattern = $pattern;

					if(substr($tmp_result, 0, strlen($pattern)) == $tmp_result) {
						$allowed = true;
					}
				}
				if($allowed == false) {
					return NULL;
				}
			}
			return $result;
		}
		
		
		public static function getLangPrefix() { return "ru";
			static $ilang;
			if(!is_null($ilang)) {
				return $ilang;
			}
			
			$cmsController = cmsController::getInstance();
			$prefix = $cmsController->getCurrentLang()->getPrefix();
			
			if($cmsController->getCurrentMode() != "admin" && !defined('VIA_HTTP_SCHEME')) {
				return $ilang = checkInterfaceLang($prefix);
			}
			
			if(!is_null($ilang = getArrayKey($_POST, 'ilang'))) {
				$ilang = checkInterfaceLang($ilang);
				setcookie('ilang', $ilang, time() + 3600*24*31, '/');
				return $ilang;
			}

			if(!is_null($ilang = getArrayKey($_GET, 'ilang'))) {
				$ilang = checkInterfaceLang($ilang);
				setcookie('ilang', $ilang, time() + 3600*24*31, '/');
				return $ilang;
			}

			
			if(!is_null($ilang = getCookie('ilang'))) {
				$ilang = checkInterfaceLang($ilang);
				setcookie('ilang', $ilang, time() + 3600*24*31, '/');
				return $ilang;
			}
			
			return $ilang = checkInterfaceLang($prefix);
		}
		
		public function __construct() {
			parent::__construct();
		}
		
		public function __destruct() {}
		
		protected function generateJavaScriptLabels($path) {
			header("Content-type: text/javascript; charset=utf-8");
			$i18n = self::loadI18NFiles($path);
			
			$result = <<<INITJS
function getLabel(key, str) {if(setLabel.langLabels[key]) {var res = setLabel.langLabels[key];if(str) {res = res.replace("%s", str);}return res;} else {return "[" + key + "]";}}
function setLabel(key, label) {setLabel.langLabels[key] = label;}setLabel.langLabels = new Array();


INITJS;
			foreach($i18n as $i => $v) {
				if(substr($i, 0, 3) != "js-") {
					continue;
				}
				$i = self::filterOutputString($i);
				$v = self::filterOutputString($v);
				$result .= "setLabel('{$i}', '{$v}');\n";
			}
			umiBaseStream::$allowTimeMark = false;
			return $result;
		}
		
		protected function filterOutputString($string) {
			$string = str_replace("\r\n", "\\r\\n", $string);
			$string = str_replace("\n", "\\n", $string);
			$string = str_replace("'", "\\'", $string);
			return $string;
		}
	};


	class cacheFrontend extends singleton implements iSingleton {
		public static $cacheMode = false;
		
		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}
		
		public function getIsConnected() {
			return false;
		}
		
		protected function __construct() {
		}
		
		public function load() {
			return false;
		}
		
		public function save() {
			return false;
		}
		
		public function loadSql() {
			return false;
		}
		
		public function saveSql() {
			return false;
		}
		
		public function flush() {
			return false;
		}
	};
	
	class umiBranch {
		public static function checkIfBranchedByHierarchyTypeId($hierarchyTypeId) {
			return false;
		}
		public static function getBranchedTableByTypeId($objectTypeId) {
			return "cms3_object_content";
		}
	};
	
	class umiEventPoint {
		public function setParam() {}
		public function addRef() {}
		public function call() {}
	};
?>