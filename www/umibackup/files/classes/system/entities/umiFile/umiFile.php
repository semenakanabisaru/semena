<?php
/**
	* Класс для работы с файлами в системе
*/
	class umiFile implements iUmiFile {
		protected	$filepath,
				$size, $ext, $name, $dirname, $modify_time,
				$is_broken = false;
		public static $mask = 0777;

		protected static $class_name = 'umiFile';
		protected static $allowedFileTypes = array(
			'txt', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'pps', 'ppsx',
			'odt', 'sxw', 'ods', 'odg', 'pdf', 'csv',
			'html', 'js', 'tpl', 'xsl', 'xml', 'css',
			'zip', 'rar', '7z', 'tar', 'gz', 'tar.gz', 'exe', 'msi',
			'rtf', 'chm', 'ico', 'jpg', 'jpeg', 'gif', 'png', 'bmp',
			'psd', 'flv', 'mp4', 'swf', 'mp3', 'wav', 'wma', 'ogg', 'aac'
		);

		protected static $allowedImageTypes = array('jpg', 'jpeg', 'gif', 'bmp', 'png');

		protected static $addWaterMark = false;

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
			while (@ob_end_clean());

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


		    if( !in_array(strtolower(substr($name, strrpos($name, '.') + 1)), self::$allowedFileTypes) ) return 2;


		    list(,, $extension) = array_values(getPathInfo($name));
			$name = substr($name, 0, strlen($name) - strlen($extension));
			$name = translit::convert($name);
			$name .= "." . strtolower($extension);

			$new_path = $target_folder . "/" . $name;

			if($name == ".htaccess") {
				return 3;
			}

			$extension = strtolower($extension);


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


			$target_folder_input = $target_folder;
			if(substr($target_folder_input, strlen($target_folder_input) - 1, 1) != "/") $target_folder_input .= "/";

			$target_folder = realpath($target_folder);

			if(!is_dir($target_folder)) {
				return false;
			}

			if(!is_writable($target_folder)) {
				return false;
			}

			$aForbiddenTypes = array("php", "php3", "php4", "php5", "phtml");

			if($group_name === false && $var_name === false) {

				$name = $_REQUEST['filename'];
				$content = file_get_contents('php://input');

				list(,, $extension) = array_values(getPathInfo($name));
				$name = substr($name, 0, strlen($name) - strlen($extension));
				$name = translit::convert($name);
				$extension = strtolower($extension);

				$name .= "." . $extension;

				if($name == ".htaccess") {
					return false;
				}

				if (in_array($extension, $aForbiddenTypes)) return false;
				if (!in_array( $extension, self::$allowedFileTypes)) return false;

				$new_path = $target_folder . "/" . $name;

				if(file_put_contents($new_path, $content) == 0) {
					return false;
				}

				chmod($new_path, self::$mask);
				$new_path = self::getRelPath($new_path);
				return new self::$class_name($new_path);

			} else {
				global $_FILES;
				$files_array = &$_FILES;

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

						if( in_array(substr($name, strrpos($name, '.')), $aForbiddenTypes) ) return false;

						list(,, $extension) = array_values(getPathInfo($name));
						$name = substr($name, 0, strlen($name) - strlen($extension));
						$name = translit::convert($name);
						$name .= "." . strtolower($extension);

						$new_path = $target_folder . "/" . $name;

						if($name == ".htaccess") {
							return false;
						}

						$extension = strtolower($extension);

						if( !in_array( $extension, self::$allowedFileTypes ) ) {
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
		}

		// Ф-я распаковки zip-архива
		public static function upload_zip ($var_name, $file = "", $folder = "./images/cms/data/", $addWaterMark = false)  {

			if ($file == "") {
				$temp_path = $var_name['tmp_name'];
				$name = $var_name['name'];

				list(,, $extension) = array_values(getPathInfo($name));
				$name = substr($name, 0, strlen($name) - strlen($extension));
				$name = translit::convert($name);
				$name .= "." . $extension;

				$new_path = $folder.$name;
				$upload_path = CURRENT_WORKING_DIR . "/sys-temp/uploads";
				if(!is_dir($upload_path)) {
					mkdir($upload_path);
				}
				$new_zip_path = $upload_path.'/'.$name;

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

				$file = CURRENT_WORKING_DIR . "/" . $file;

				if (!file_exists ($file) || !is_writable($file)) return "File not exists!";

				$path_parts = getPathInfo ($file);

				if ($path_parts['extension'] != "zip") {
					return "It's not zip-file!";
				}

				$new_path = $file;
				$new_zip_path = $file;
			}

			$oldAddWaterMark = self::$addWaterMark;
			self::$addWaterMark = $addWaterMark;

			$archive = new PclZip($new_zip_path);
			
			// Проверяем, что каждый файл не превышает заданного максимального размера для изображений
			$list = $archive->listContent();
			if (count($list)<1) {
				throw new publicAdminException(getLabel('zip-file-empty'));
			}

			$upload_max_filesize = cmsController::getInstance()->getModule('data')->getAllowedMaxFileSize();
			$max_img_filesize =	regedit::getInstance()->getVal("//settings/max_img_filesize");
			
			if (!$max_img_filesize) {
				$max_img_filesize = $upload_max_filesize;
			}
			// Значение указывается в мегабайтах, нам нужны байты
			$max_img_filesize = $max_img_filesize * 1024 * 1024;
			
			$summary = 0;
			foreach($list as $key=>$oneFile) {
				$extension = strtolower(preg_replace('/^[^.]*\./', '', $oneFile['filename']));
				// Пропускаем файлы, которые не будут распаковываться
				if (!umiFile::isAllowedImageType($extension)) {
					unset($list[$key]);
					continue;
				}
				// Проверяем размер файла, не должен превышать разрешенный для изображений
				if ($oneFile['size']>$max_img_filesize) {
					throw new publicAdminException(getLabel('zip-file-image-max-size')."{$oneFile['filename']}");
				}
				
				$summary+=$oneFile['size'];
			}

			// Повторная проверка, что у нас есть файлы для обработки
			if (count($list)<1) {
				throw new publicAdminException(getLabel('zip-file-images-absent'));
			}

			// Проверяем, что у нас есть место для распаковки изображений
			if (!checkAllowedDiskSize($summary)) {
				throw new publicAdminException(getLabel('zip-file-images-no-free-size'));
			}

			$list = $archive->extract(PCLZIP_OPT_PATH, $folder,
				PCLZIP_CB_PRE_EXTRACT, "callbackPreExtract",
				PCLZIP_CB_POST_EXTRACT, "callbackPostExtract",
				PCLZIP_OPT_REMOVE_ALL_PATH);

			self::$addWaterMark = $oldAddWaterMark;

			if (!is_array ($list)) {
				throw new coreException ("Zip extracting error: ".$archive->errorInfo(true));
			}

			// unlink zip
			if(is_writable($new_zip_path)) {
				unlink($new_zip_path);
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
				$sIncludePath = CURRENT_WORKING_DIR;
				if (substr($this->filepath, 0, strlen($sIncludePath)) === $sIncludePath) {
					return substr($this->filepath, strlen($sIncludePath));
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

			$pathinfo = getPathInfo($this->filepath);

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
				$info = getPathInfo($new_path);
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

		public static function getAddWaterMark() {
			return self::$addWaterMark;
		}
		
		public static function isAllowedImageType($extension) {
			return in_array($extension, self::$allowedImageTypes);
		}
		
	};


// Контроль извлекаемых из zip-архива файлов
function callbackPreExtract ($p_event, &$p_header) {
	$info = getPathInfo($p_header['filename']);

	$extension = strtolower($info['extension']);
	if (!umiFile::isAllowedImageType($extension)) {
		return 0;
	}

	$basename = substr($info['basename'], 0, (strlen($info['basename']) - strlen($info['extension']))-1);
	$basename = translit::convert($basename);
	$p_header['filename'] = $info['dirname']."/".$basename.".".$info['extension'];

	$p_header['filename'] = umiFile::getUnconflictPath($p_header['filename']);

	return 1;
}

function callbackPostExtract ($p_event, &$p_header) {

	$info = getPathInfo($p_header['stored_filename']);
	$extension = strtolower($info['extension']);
	$filename = $p_header['filename'];

	if (!umiFile::isAllowedImageType($extension)) {
		unlink ($filename);
	} else {
		$imgSize = @getimagesize($filename);
		if (!is_array($imgSize)) {
			@unlink($filename);
	}

		if(umiFile::getAddWaterMark()) {
			if (umiImageFile::addWatermark($filename) !== false) return 1;
		}

		$jpgThroughGD = (bool) mainConfiguration::getInstance()->get("kernel", "jpg-through-gd");
		if ($jpgThroughGD) {
			if ($extension == 'jpg' || $extension == 'jpeg'){
				$res = imagecreatefromjpeg($filename);
				if($res) {
					imagejpeg($res, $filename, 100);
					imagedestroy($res);
				}
			}
		}
	}

	return 1;
}
?>