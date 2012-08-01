<?php

	class elFinderVolumeUmiLocalFileSystem extends elFinderVolumeLocalFileSystem {
    	protected $driverId = 'umi';
		public function fullRoot() {
			return $this->root;
		}

		/**
		* Переименовываение файла (исключаем проверку на существование - меняем название в случае коллизий)
		*
		* @param mixed $hash
		* @param mixed $name
		* @return string|false
		*/
		public function rename($hash, $name) {
			$path = $this->decode($hash);


			if (!($file = $this->file($hash))) {
				return $this->setError(elFinder::ERROR_FILE_NOT_FOUND);
			}

			$dir = $this->_dirname($path);

			if ($this->attr($path, 'locked')) {
				return $this->setError(elFinder::ERROR_LOCKED, $file['name']);
			}

			if (!$this->nameAccepted($name)) {
				return $this->setError(elFinder::ERROR_INVALID_NAME, $name);
			}

			if ($name == $file['name']) {
				return $file;
			}

			if ($this->_moveWithRename($path, $dir, $name)) {
				$this->rmTmb($path);
				return $this->stat($this->_joinPath($dir, $name));
			}
			return false;
		}

		/**
		* Дубликат (вместо постфикса " copy" делаем "_copy")
		*
		* @param mixed $hash
		* @return false
		*/
		public function duplicate($hash) {
			if (($file = $this->file($hash)) == false) {
				return $this->setError(elFinder::ERROR_FILE_NOT_FOUND);
			}

			$path = $this->decode($hash);
			$dir  = $this->_dirname($path);

			return ($path = $this->doCopy($path, $dir, $this->uniqueName($dir, $file['name'], "_copy"))) == false
				? false
				: $this->stat($path);
		}

		/**
		* Создание папки с корректировкой названия
		*
		* @param string $dst
		* @param string $name
		* @param mixed $copy
		* @return bool
		*/
		public function mkdir($dst, $name, $copy=false) {
			$path = $this->decode($dst);

			if (($dir = $this->dir($dst)) == false) {
				return $this->setError(elFinder::ERROR_TRGDIR_NOT_FOUND, '#'.$dst);
			}

			if (!$dir['write']) {
				return $this->setError(elFinder::ERROR_PERM_DENIED);
			}

			if (!$this->nameAccepted($name)) {
				return $this->setError(elFinder::ERROR_INVALID_NAME);
			}

			if ($copy && !$this->options['copyOverwrite']) {
				$name = $this->uniqueName($path, $name, '-', false);
			}

			$dst = $this->_joinPath($path, $name);

			if ($this->_fileExists($dst)) {

				if ($copy) {
					if (!$this->options['copyJoin'] && $this->attr($dst, 'write')) {
						foreach ($this->_scandir($dst) as $p) {
							$this->doRm($p);
						}
					}
					return $this->stat($dst);
				}

				return $this->setError(elFinder::ERROR_EXISTS, $name);
			}

			return $this->_mkdirWithRename($path, $name) ? $this->stat($this->_joinPath($path, $name)) : false;
		}

		/**
		* Сохранение загруженного файла
		*
		* @param mixed $fp
		* @param string $dst
		* @param mixed $name
		* @param mixed $cmd
		* @return string|false
		*/
		public function save($fp, $dst, $name, $cmd = 'upload') {

			if (($dir = $this->dir($dst, true, true)) == false) {
				return $this->setError(elFinder::ERROR_TRGDIR_NOT_FOUND, '#'.$dst);
			}

			if (!$dir['write']) {
				return $this->setError(elFinder::ERROR_PERM_DENIED);
			}

			if (!$this->nameAccepted($name)) {
				return $this->setError(elFinder::ERROR_INVALID_NAME, $name);
			}

			$dst = $this->decode($dst);

			if (strpos($dst, CURRENT_WORKING_DIR . '/files/') !== false || strpos($dst, CURRENT_WORKING_DIR . '/images/') !== false) {

				$files_size = getDirSize(CURRENT_WORKING_DIR.'/files/');
				$images_size = getDirSize(CURRENT_WORKING_DIR.'/images/');
				$all_size = $files_size + $images_size;
				$quota_byte = getBytesFromString( mainConfiguration::getInstance()->get('system', 'quota-files-and-images') );

				if($quota_byte  && $all_size >= $quota_byte) {
					return $this->setError(getLabel('error-files_quota_exceeded'));
				}
			}

			//Загрузка файла
			$sMethodName = method_exists($this, "_doSave_{$cmd}") ? "_doSave_{$cmd}" : "_doSave_unknown";
			$path = $this->$sMethodName($fp, $dst, $name);

			$result = false;
			if ($path) {
				$result = $this->stat($path);
			}
			return $result;
		}

		/**
		* Переместить файл в новое место (перемещение/переименование)
		*
		* @param  string  $source  source file path
		* @param  string  $target  target dir path
		* @param  string  $name    file name
		* @return bool
		*/
		protected function _moveWithRename($source, &$targetDir, &$name='') {
			$i = 0;
			$bNeedRename = true;

			$old_locale = setlocale(LC_ALL, NULL);
			setlocale(LC_ALL, array("ru_RU.UTF-8", "ru_RU.CP1251", "ru_RU.KOI8-R", "ru_SU.CP1251", "ru_RU", "russian", "ru_SU", "ru"));

			while($bNeedRename) {
				$name = $this->_getNewFilename($name, $i);
				$target = $targetDir . DIRECTORY_SEPARATOR . ($name ? $name : basename($source));
				clearstatcache();
				$bNeedRename = (file_exists($target) || is_dir($target));
				$i++;
			}

			setlocale($old_locale);

			return @rename($source, $target);
		}

		/**
		 * Создать папку с переименованием
		 *
		 * @param  string  $path  parent dir path
		 * @param string  $name  new directory name
		 * @return bool
		 * @author Dmitry (dio) Levashov
		 */
		protected function _mkdirWithRename($path, &$name) {
			$i = 0;
			$bNeedRename = true;

			$old_locale = setlocale(LC_ALL, NULL);
			setlocale(LC_ALL, array("ru_RU.UTF-8", "ru_RU.CP1251", "ru_RU.KOI8-R", "ru_SU.CP1251", "ru_RU", "russian", "ru_SU", "ru"));

			while($bNeedRename) {
				$name = $this->_getNewFilename($name, $i);
				$target = $path.DIRECTORY_SEPARATOR.$name;
				clearstatcache();
				$bNeedRename = (file_exists($target) || is_dir($target));
				$i++;
			}

			setlocale($old_locale);

			if (@mkdir($target)) {
				@chmod($target, $this->options['dirMode']);
				return true;
			}
			return false;
		}

		/**
		* Получить корректное название файла с числовым постфиксом в названии
		*
		* @param mixed $sOldName Имя файла
		* @param mixed $i Числовой постфикс
		* @return string Новое имя файла
		*/
		protected function _getNewFilename($sOldName, $i) {
			if($sOldName == '') return $sOldName;

			$iLastDotPosition = strrpos($sOldName, '.');
			$sBaseName = ($iLastDotPosition) ? substr($sOldName, 0, strrpos($sOldName, '.')) : $sOldName;
			$sBaseName = $this->_convertFilename($sBaseName);

			$sExt = ($iLastDotPosition) ? substr($sOldName, strrpos($sOldName, '.')) : '';
			$sExt = $this->_convertFilename($sExt);

			if($i == 0) {
				return "{$sBaseName}{$sExt}";
			} else {
				return "{$sBaseName}_{$i}{$sExt}";
			}
		}

		/**
		* Конвертация имени файла
		*
		* @param string $sFileBaseName
		* @return string
		*/
		protected function _convertFilename($sFileBaseName) {
			$arConvertions = array(
				array('a', array('а', 'А')), array('b', array('б', 'Б')), array('v', array('в', 'В')),
				array('g', array('г', 'Г')), array('d', array('д', 'Д')), array('e', array('е', 'Е')),
				array('e', array('ё', 'Ё')), array('zsh', array('ж', 'Ж')), array('z', array('з', 'З')),
				array('i', array('и', 'И')), array('i', array('й', 'Й')), array('k', array('к', 'К')),
				array('l', array('л', 'Л')), array('m', array('м', 'М')), array('n', array('н', 'Н')),
				array('o', array('о', 'О')), array('p', array('п', 'П')), array('r', array('р', 'Р')),
				array('s', array('с', 'С')), array('t', array('т', 'Т')), array('u', array('у', 'У')),
				array('f', array('ф', 'Ф')), array('h', array('х', 'Х')), array('c', array('ц', 'Ц')),
				array('ch', array('ч', 'Ч')), array('sh', array('ш', 'Ш')), array('sh', array('щ', 'Щ')),
				array('', array('ъ', 'Ъ')), array('i', array('ы', 'Ы')), array('', array('ь', 'Ь')),
				array('e', array('э', 'Э')), array('yu', array('ю', 'Ю')), array('ya', array('я', 'Я')),
				array('_', ' '), array('', '~'), array('', '`'),
				array('', '!'), array('', '@'), array('', '"'),
				array('', "'"), array('', '#'), array('', '№'),
				array('', '$'), array('', ';'), array('', '%'),
				array('', '^'), array('', ':'), array('', '&'),
				array('', '?'), array('', '*'), array('', '+'),
				array('', '='), array('', '|'), array('', "\\"),
				array('', '/'), array('', ','), array('', '<'),
				array('', '>')
			);

			foreach($arConvertions as $arConvPair) {
				$sFileBaseName = str_replace($arConvPair[1], $arConvPair[0], $sFileBaseName);
			}

			return $sFileBaseName;
		}

		/**
		* Действия для сохранения файла при его загрузке
		*
		* @param mixed $dst
		* @param mixed $name
		* @return false
		*/
		protected function _doSave_upload($fp, $dst, $name) {
			$cwd = getcwd();
			chdir(CURRENT_WORKING_DIR);

			$files_index = 0;

			$regedit = regedit::getInstance();
			$controller = cmsController::getInstance();

			$filename = "." . rtrim($dst, "/\\") . DIRECTORY_SEPARATOR . $name;
			if(isset($_FILES['upload'])) {
				foreach($_FILES['upload']['name'] as $i => $f_name) {
					if($f_name == $name) {
						$filename = $_FILES['upload']['tmp_name'][$i];
						$files_index = $i;
					}
				}
			}
			$filesize = (int) filesize($filename);
			if (umiImageFile::getIsImage($name)) {
				$max_img_filesize =	$controller->getModule('data')->getAllowedMaxFileSize('img') * 1024 * 1024;
				if ($max_img_filesize > 0) {
					if ($max_img_filesize < $filesize) {
						chdir($cwd);
						return $this->setError(getLabel('error-max_img_filesize') . ' ' . ($max_img_filesize / 1024 / 1024) . "M");
					}
				}
				if(getRequest('water_mark')) umiImageFile::setWatermarkOn();
				$file = umiImageFile::upload('upload', $files_index, $dst);
			}
			else {
				$upload_max_filesize = $controller->getModule('data')->getAllowedMaxFileSize() * 1024 * 1024;
				if ($upload_max_filesize > 0) {
					if ($upload_max_filesize < $filesize) {
						chdir($cwd);
						return $this->setError(getLabel('error-max_filesize') . ' ' . ($upload_max_filesize / 1024 / 1024) . "M");
					}
				}
				$file = umiFile::upload('upload', $files_index, $dst);
			}

			chdir($cwd);

			if(!$file instanceof umiFile || $file->getIsBroken()) {
				return $this->setError(elFinder::ERROR_UPLOAD);
			} else {
				return CURRENT_WORKING_DIR . $file->getFilePath(true);
			}
		}

		/**
		* Действия для сохранения файла при его копировании
		*
		* @param mixed $dst
		* @param mixed $name
		*/
		protected function _doSave_copy($fp, $dst, $name) {
			$path = $dst.DIRECTORY_SEPARATOR.$name;

			if (!($target = @fopen($path, 'wb'))) {
				$this->setError(elFinder::ERROR_COPY);
				return false;
			}

			while (!feof($fp)) {
				fwrite($target, fread($fp, 8192));
			}
			fclose($target);
			@chmod($path, $this->options['fileMode']);
			clearstatcache();

			return $path;
		}

		/**
		* Неизвестный режим сохранения файла
		*
		* @param mixed $dst
		* @param mixed $name
		* @return false
		*/
		protected function _doSave_unknown($fp, $dst, $name) {
			return $this->setError(elFinder::ERROR_UNKNOWN_CMD);
		}

	}
?>
