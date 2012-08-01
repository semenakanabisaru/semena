<?php
	error_reporting(~E_ALL);
	ini_set("display_errors", 0);

	require CURRENT_WORKING_DIR . "/libs/root-src/standalone.php";

	define("UMI_AUTHOTHUMBS_PATH", "./images/cms/autothumbs");

	ini_set('include_path', str_replace("\\", "/", dirname(__FILE__)) . '/');
	
	require_once CURRENT_WORKING_DIR."/libs/lib.php";

	$sImgPath = isset($_GET['img']) ? trim($_GET['img']) : "";
	$sImgPath = '/' . str_replace("./", "/", $sImgPath);

	$checkPath = realpath(dirname(CURRENT_WORKING_DIR . $sImgPath));
	$allowedPath = array(realpath(CURRENT_WORKING_DIR . "/images"), realpath(CURRENT_WORKING_DIR . "/files")); 
	
	if(strcmp(substr($checkPath, 0, strlen($allowedPath[0])), $allowedPath[0]) != 0 &&
	   strcmp(substr($checkPath, 0, strlen($allowedPath[1])), $allowedPath[1]) != 0) {
		header('Status: 404 Not Found', true, 404);
		header('HTTP/1.0 404 Not Found', true, 404);
		exit;
	}

	if (strlen($sImgPath)) {
		$sRealThumbFName = md5($sImgPath);
		$sRealThumbPath = UMI_AUTHOTHUMBS_PATH . "/" . $sRealThumbFName;

        $sImgPath = ltrim($sImgPath, "/\\");

		$arrPath = explode("/", $sImgPath);
		$sThumbFileName = array_pop($arrPath);

		$arrThumbFN = explode(".", $sThumbFileName);
		$sThumbExt = array_pop($arrThumbFN);
		$sThumbBaseName = implode(".", $arrThumbFN);

		$arrThumbFNParts = explode("_", $sThumbBaseName);
		$iTumbHeight = (int) array_pop($arrThumbFNParts);
		$iTumbWidth = (int) array_pop($arrThumbFNParts);
		
		$arrTmp = $arrThumbFNParts;
		$bSlide = array_pop($arrTmp) === 'sl';
		if ($bSlide) array_pop($arrThumbFNParts);
		unset($arrTmp);

		$sRealImagePath = "./" . implode("/", $arrPath) . "/" . implode("_", $arrThumbFNParts) . "." . $sThumbExt;

		if( !file_exists($sRealImagePath) || false===($imageInfo=getimagesize($sRealImagePath)) ) {
			header('Status: 404 Not Found', true, 404);
			header('HTTP/1.0 404 Not Found', true, 404);
			exit;
		}

		$imageType = $imageInfo[2];

			if (!file_exists($sRealThumbPath)) {
			check_autothumbs_bytes($allowedPath);
				$sRealThumbPath = createThumbnail($sRealImagePath, $iTumbWidth, $iTumbHeight, $sRealThumbPath, 90, $bSlide);
			} else {
				if(filemtime($sRealImagePath) > filemtime($sRealThumbPath)) {
				check_autothumbs_bytes($allowedPath);
					$sRealThumbPath = createThumbnail($sRealImagePath, $iTumbWidth, $iTumbHeight, $sRealThumbPath, 90, $bSlide, true);
				}
			}

		if (file_exists($sRealThumbPath)) {
			$imageType = (int) $imageType;

			$aliases = array (
				1=>"gif",
				2=>"jpg",
				3=>"png",
				6=>"bmp",
				15=>"wbmp",
				16=>"xbmp"
			);

			if (isset($aliases[$imageType])) {
				$fp = fopen($sRealThumbPath, 'rb');
				header("Content-Type: image/" . $aliases[$imageType]);//$sThumbExt);
				header("Content-Length: " . filesize($sRealThumbPath));

				fpassthru($fp);
				exit();
			}
		}

		header('Status: 404 Not Found', true, 404);
		header('HTTP/1.0 404 Not Found', true, 404);
	}

	function check_autothumbs_bytes($dirs) {
		$busy_size = 0;
		foreach($dirs as $dir) {
			$busy_size += getDirSize($dir);
		}
		$max_size = getBytesFromString(mainConfiguration::getInstance()->get("system", "quota-files-and-images"));
		
		if ($max_size!=0 && $busy_size>=$max_size) {
			header('Status: 404 Not Found', true, 404);
			header('HTTP/1.0 404 Not Found', true, 404);
			exit;
		}
	}

	function createThumbnail($sImgPath, $iWidth = 0, $iHeight = 0, $sThumbFile="", $iJpgQuality = 90, $bSlide = false, $bReplace = false) {
		if (!file_exists($sImgPath)) return false;

		$sFileName = getPathInfo($sImgPath, PATHINFO_BASENAME);
		$sFileExt = strtolower(getPathInfo($sImgPath, PATHINFO_EXTENSION));
		$arrInfo = @getimagesize($sImgPath);
		$iImgWidth = (int) $arrInfo[0];
		$iImgHeight = (int) $arrInfo[1];

		$bNeedSlide = $bSlide && $iWidth > 0 && $iHeight > 0 && ($iWidth != $iImgWidth || $iHeight != $iImgHeight);

		$bSuccess = false;
		if (!strlen($sThumbFile)) {
			$sThumbName = $sFileName."_".$iWidth."_".$iHeight.".".$sFileExt;
			$sThumbFile = UMI_AUTHOTHUMBS_PATH . "/" . $sThumbName;
		}

		if (!$bReplace && file_exists($sThumbFile)) return $sThumbFile;

		if ( ($iWidth>$iImgWidth && $iHeight>$iImgHeight) // Оба параметра больше размеров исходной картинки
			|| ($iWidth>$iImgWidth && $iHeight==0) // Ширина больше исходной, высота автоматическая
			|| ($iWidth==0 && $iHeight>$iImgHeight) ) { // Высота больше исходной, ширина автоматическая
			$iWidth = 0;
			$iHeight = 0;
		}

		if ($iWidth > 0 || $iHeight > 0) {
			// resize
			if (!$iHeight) {
				$iHeight = (int) round($iImgHeight * ($iWidth / $iImgWidth));
			}

			if (!$iWidth) {
				$iWidth = (int) round($iImgWidth * ($iHeight / $iImgHeight));
			}

			$rThumb = imagecreatetruecolor($iWidth, $iHeight);
			imagealphablending($rThumb,true);
			$thumb_white_color = imagecolorallocate($rThumb, 255, 255, 255);
			imagefill($rThumb, 0, 0, $thumb_white_color);

			list (,,$imageType) = getimagesize ($sImgPath);
			$imageType = (int) $imageType;

			switch ($imageType) {
				case 1 : { // GIF
					$rSource = imagecreatefromgif($sImgPath);
					imagecolortransparent($rThumb, $thumb_white_color);
					imagealphablending($rSource, true);
					imagealphablending($rThumb, true);
					break;
				}
				case 2 : { // JPG
					$rSource = imagecreatefromjpeg($sImgPath);
					break;
				}
				case 3 : { // PNG
					$rSource = imagecreatefrompng($sImgPath);
					imagecolortransparent($rThumb, $thumb_white_color);
					imagealphablending($rSource, true);
					imagealphablending($rThumb, true);
					break;
				}
				case 6 : { // BMP - not supported
					return false;
					break;
				}
				case 15 : { // WBMP
					$rSource = imagecreatefromwbmp ($sImgPath);
					break;
				}
				case 16 : { // XBMP
					$rSource = imagecreatefromxbm ($sImgPath);
					break;
				}
			}

			if ($bNeedSlide) {
				// TODO create image slide

				if ($iImgWidth < $iWidth && $iImgHeight < $iHeight) {
					// not resize. centred..
					$iPaddingTop = (int) round(($iHeight - $iImgHeight) / 2);
					$iPaddingLeft = (int) round(($iWidth - $iImgWidth) / 2);
					imagecopyresampled($rThumb, $rSource, $iPaddingLeft, $iPaddingTop, 0, 0, $iImgWidth, $iImgHeight, $iImgWidth, $iImgHeight);
				} elseif ($iImgHeight < $iImgWidth) {
					// resize height
					$iSlHeight = (int) round($iImgHeight * ($iWidth / $iImgWidth));
					$iPaddingTop = (int) round(($iHeight - $iSlHeight) / 2);
					imagecopyresampled($rThumb, $rSource, 0, $iPaddingTop, 0, 0, $iWidth, $iSlHeight, $iImgWidth, $iImgHeight);
				} else {
					// resize width
					$iSlWidth = (int) round($iImgWidth * ($iHeight / $iImgHeight));
					$iPaddingLeft = (int) round(($iWidth - $iSlWidth) / 2);
					imagecopyresampled($rThumb, $rSource, $iPaddingLeft, 0, 0, 0, $iSlWidth, $iHeight, $iImgWidth, $iImgHeight);
				}

			} else {
				imagecopyresampled($rThumb, $rSource, 0, 0, 0, 0, $iWidth, $iHeight, $iImgWidth, $iImgHeight);
			}

			if (strtolower(getPathInfo($sImgPath, PATHINFO_EXTENSION)) == "gif") {
				$bSuccess = imagegif($rThumb, $sThumbFile);
			} elseif (strtolower(getPathInfo($sImgPath, PATHINFO_EXTENSION)) == "png") { 
				$bSuccess = imagepng($rThumb, $sThumbFile);
			} else {
				$bSuccess = imagejpeg($rThumb, $sThumbFile, $iJpgQuality);
			}
		} else {
			$bSuccess = copy($sImgPath, $sThumbFile);
		}
		return $sThumbFile;
	}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 3.2//EN">

<html>
<head>
  <meta name="generator" content=
  "HTML Tidy for Windows (vers 14 February 2006), see www.w3.org">

  <title></title>
</head>

<body>
</body>
</html>
