<?php
	abstract class __imanager_content extends baseModuleAdmin {

		protected function makePanelImageThumbnail ($image, $clearClientCache = false) {
			$imageSrcPath = umiConversion::getInstance()->Any2UTF8($image->getFilePath(true));
			$imageSrc = umiConversion::getInstance()->Any2UTF8($image->getFileName());
			$pathParts = getPathInfo($imageSrcPath);
			$thumbBase = $pathParts['dirname']."/".$pathParts['filename'];
			$thumbPath = $thumbBase."_sl_90_60.".$pathParts['extension'];
			$thumbSrc = "/autothumbs.php?img=" . $thumbPath . ($clearClientCache ? "&rnd=".time() : "");
			$thumbBase = "/autothumbs.php?img=" . $thumbBase;
			$shortFileName = truncStr($imageSrc, 16, ".." . substr($thumbBase, strlen($thumbBase) - 2) . "." . $pathParts['extension']);
			return array ('src' => $thumbSrc, 'path' => $thumbBase);
		}

		public function getDirectoryItems () {
			$dir = base64_decode(getRequest("dir"));
			$loadFileTypes = getRequest("type");
			if (!$dir) {
				$dir = "images/";
			}
			if ($loadFileTypes == "img") {
				$allowedExtensions = umiImageFile::getSupportedImageTypes();
				$fileExtensions = "^.+\.(";
				foreach ($allowedExtensions as $key=>$fileExtension) {
					if ($key != (count($allowedExtensions) -1)) {
						$fileExtensions.= $fileExtension."|";
					} else {
						$fileExtensions.= $fileExtension;
					}
				}
				$fileExtensions.= ")$";
			} elseif ($loadFileTypes == "dir") {
				$fileExtensions = "^(?!\.).*";
			}
			if ($dir!="images/") {
				preg_match("/(.+)\/.+/", $dir, $dirMatch);
				$parentDir = $dirMatch[1];
			} else {
				$parentDir = "";
			}
			$dirItemsArray = Array ();
			$directory = new umiDirectory($dir);
			if (!$directory->getIsBroken()) {
				$dirItems = $directory->getFSObjects(0,$fileExtensions);
				$dirCounter = new umiDirectoryIterator($dirItems);
				while ($currentDirItem = $dirCounter->current()) {
					$dirItem = Array ();
					if ($currentDirItem instanceof umiDirectory && $loadFileTypes == "dir") {
						$dirItem['attribute:path'] = CURRENT_WORKING_DIR."/".$currentDirItem->getPath();
						$dirItem['attribute:type'] = "dir";
						//TO DO!
						//$dirItem['attribute:is_vritable'] = ;
						$dirItem['node:name'] = $currentDirItem->getName();
						$dirItemsArray[] = $dirItem;
					} elseif ($currentDirItem instanceof umiFile && $loadFileTypes == "img") {
						if ($currentDirItem instanceof umiImageFile) {
							$thumbs = self::makePanelImageThumbnail($currentDirItem);
							$dirItem['attribute:src'] = $currentDirItem->getFilePath();
							$dirItem['attribute:ext'] = $currentDirItem->getExt();
							$dirItem['attribute:name'] = $currentDirItem->getFileName();
							$dirItem['attribute:size'] = $currentDirItem->getSize();
							$dirItem['attribute:thumb_base'] = $thumbs['path'];
							$dirItem['attribute:thumb'] = $thumbs['src'];
							$sizeInKb = $currentDirItem->getSize()/1024;
							$sizeInKb = number_format($sizeInKb, 2);
							$title = $currentDirItem->getFileName()." (";
							$dirItem['attribute:type'] = "imgFile";
							$dirItem['attribute:width'] = $currentDirItem->getWidth();
							$dirItem['attribute:height'] = $currentDirItem->getHeight();
							$thumbnail = self::makePanelImageThumbnail($currentDirItem);
							$title.= $currentDirItem->getWidth()."x".$currentDirItem->getHeight().", ";
							$title.= " $sizeInKb Kb)";
							$dirItem['node:name'] = $title;
							$dirItemsArray[] = $dirItem;
						}
					}
					$dirCounter->next();
				}
			}
			$inputData = Array (
					"Directory" =>	Array (
					"attribute:name" => $dir,
					"nodes:item" => $dirItemsArray,
					"attribute:parent" => $parentDir
					)
				);
			$this->setDataType("list");
			$this->setActionType("view");

			$this->setData($inputData);
			return $this->doData();
		}

		public function removeImanagerObject () {
			$image = base64_decode(getRequest("obj_path"));
			$type = getRequest("type");
			$rootPath = ini_get("include_path");
			$imagesPath = $rootPath."images";
			$imageFullPath = $imagesPath.$image;
			$result = "<?xml version=\"1.0\" encoding=\"utf-8\"?><result>";
			if ($type == "img") {
				$result.= "<type>image</type>";
				if (file_exists($imageFullPath)) {
					$fileToDelete = new umiFile($imageFullPath);
					if ($deletedFile = $fileToDelete->delete()) {
						$result.= "<item>".$deletedFile."</item>";
					}
				}
			} elseif ($type == "dir") {


			}
			$result.= "</result>";
			header ("Content-type: text/xml; charset=utf-8");
			$this->flush($result);
		}

		public function createImanagerDirectory () {
			$request_id = getRequest('requestId');
			$sType = strtolower(getRequest("type"));

			$sRootPath = ini_get("include_path");

			$sRootImgDir = $sRootPath.'images';

			$sName = getRequest('name');

			$sDir = "";
			if (strlen(getRequest('dir'))) {
				$sDir = base64_decode(getRequest('dir'));
			}

			$sDir = str_replace("\\", "/", $sDir);
			$sDir = str_replace("//", "/", $sDir);

			if(strpos($sDir, $sRootImgDir) === false || strpos($sDir, "..") !== false || strpos($sDir, "./") !== false) {
				$sDir = $sRootImgDir;
			}

			while (substr($sDir, -1)=="/") $sDir=substr($sDir, 0, (strlen($sDir)-1));


			if ($sType === "img") {
				if (isset($_FILES['pics'])) {
					$oNewImageFile = umiImageFile::upload("pics", "panel_item", $sDir);
					if ($oNewImageFile instanceof umiImageFile) {
						if (umiImageFile::getIsImage($oNewImageFile->getFilePath())) {
							header("Content-type: text/html; charset=utf-8");

							$sItmInfo = self::__renderPanelItem($oNewImageFile, true);
							$sResult = <<<END
								<html>
									<head>
										<script type="text/javascript">
											var responseArgs = new Array();

											{$sItmInfo}

										</script>
									</head>
									<body>Upload ok!</body>
								</html>
END;

							echo $sResult;
						} else {
						header("Content-type: text/html; charset=utf-8");

						$sResult = <<<END
							<html>
								<head>
									<script type="text/javascript">
										var error = 'uploadError';
									</script>
								</head>
								<body>Upload error!</body>
							</html>
END;
							echo $sResult;
						}
					} else {
						$sResult = <<<END
							<html>
								<head>
									<script type="text/javascript">
										var error = 'uploadError';
									</script>
								</head>
								<body>Upload error!</body>
							</html>
END;
							echo $sResult;
					}

				}
			} elseif ($sType === "dir") {
				header("Content-type: text/javascript; charset=utf-8");
				$sNewDir = $sDir . "/" . translit::convert($sName);
				@mkdir($sNewDir);
				@chmod($sNewDir, 0777);
				$sResult = <<<END
					var responseArgs = new Array();

					jsonRequestsController.getInstance().reportRequest({$request_id}, responseArgs);
END;
					$this->flush($sResult);
			}

			exit();

		}



	};
?>
