<?php
  	require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . "elFinder.class.php");

	class elFinderUmiru extends elFinder {

		/**
		 * Copy/move files into new destination && correct error message
		 *
		 * @param  array  command arguments
		 * @return array
		 * @author Dmitry (dio) Levashov && krutuzick
		 **/
		protected function paste($args) {
			$dst     = $args['dst'];
			$targets = is_array($args['targets']) ? $args['targets'] : array();
			$cut     = !empty($args['cut']);
			$result  = array('removed' => array(), 'added' => array());
			$error   = $cut ? self::ERROR_MOVE : self::ERROR_COPY;

			if (!$targets) {
				return array();
			}

			if (($dstVolume = $this->volume($dst)) == false
			|| ($dstDir = $dstVolume->dir($dst)) == false) {
				return array('error' => $this->error($error, '#'.$targets[0], self::ERROR_TRGDIR_NOT_FOUND, '#'.$dst));
			}

			if (!$dstDir['write']) {
				return array('error' => $this->error($error, '#'.$targets[0], self::ERROR_PERM_DENIED));
			}

			foreach ($targets as $target) {
				if (($srcVolume = $this->volume($target)) == false
				|| ($src = $srcVolume->file($target)) == false) {
					$result['warning'] = $this->error($error, '#'.$target, self::ERROR_FILE_NOT_FOUND);
					break;
				}

				if ($dstVolume != $srcVolume) {
					if (!$srcVolume->copyFromAllowed()) {
						$root = $srcVolume->file($srcVolume->root());
						$result['warning'] = $this->error($error, self::ERROR_COPY_FROM, $root['name']);

						break;
					}

					if (!$dstVolume->copyToAllowed()) {
						$root = $dstVolume->file($dstVolume->root());
						$result['warning'] = $this->error($error, self::ERROR_COPY_TO, $root['name']);
						break;
					}
				}

				if (($file = $this->copy($srcVolume, $dstVolume, $src, $dst, $cut)) == false) {
					$result['warning'] = $this->error($error, $this->copyError, $srcVolume->error());
					break;
				}

				if (!$dstVolume->mimeAccepted($file['mime'], $args['mimes'])) {
					$file['hidden'] = true;
				}

				$result = $this->merge($result, $this->trigger('paste', array($srcVolume, $dstVolume), array('added' => array($file))));

				if ($cut) {
					if (!$srcVolume->rm($src['hash'])) {

						$result['warning'] = $this->error($error, $src['name'], $srcVolume->error());
						break;
					}
					$result = $this->merge($result, $this->trigger('rm', $srcVolume, array('removed' => array($src['hash']), 'removedDetails' => array($src))));
				}
			}

			return $result;
		}

		/**
		 * Save uploaded files
		 *
		 * @return args
		 * @author Dmitry (dio) Levashov && krutuzick
		 **/
		protected function upload($args) {

			$target = $args['target'];
			$volume = $this->volume($target);
			$header = !empty($args['html']) ? 'Content-Type: text/html; charset=utf-8' : false;
			$result = array('added' => array(), 'header' => $header);
			$files  = !empty($args['FILES']['upload']) && is_array($args['FILES']['upload'])
				? $args['FILES']['upload']
				: array();

			if (empty($files)) {
				return array('error' => $this->error(self::ERROR_UPLOAD_NO_FILES), 'header' => $header);
			}

			if (!$volume) {
				return array('error' => $this->error(self::ERROR_UPLOAD, $files['name'][0], self::ERROR_TRGDIR_NOT_FOUND, '#'.$target), 'header' => $header);
			}

			foreach ($files['name'] as $i => $name) {
				$tmpPath = $files['tmp_name'][$i];

				if ($files['error'][$i]) {
					$uploadErrorText = $this->uploadErrorText($files['error'][$i], $name);
					if(is_null($uploadErrorText)) {
						$result['warning'] = $this->error(self::ERROR_UPLOAD_TRANSFER, $name);
					} else {
						$result['warning'] = $this->error(self::ERROR_UPLOAD_TRANSFER, $name, $uploadErrorText);
					}
					break;
				}

				if (!$volume->uploadAllow($tmpPath, $name)) {
					$result['warning'] = $this->error(self::ERROR_UPLOAD, $name, $volume->error());
					break;
				}

				if(($fp  = fopen($tmpPath, 'rb')) == false) {
					$result['warning'] = $this->error(self::ERROR_UPLOAD, $name, self::ERROR_UPLOAD_NO_FILES);
					break;
				} elseif(($file = $volume->save($fp, $target, $name, 'upload')) == false) {
					$result['warning'] = $this->error(self::ERROR_UPLOAD, $name, $volume->error());
					break;
				}

				if (!$volume->mimeAccepted($file['mime'], $args['mimes'])) {
					$file['hidden'] = true;
				}

				$result = $this->merge($result, $this->trigger('upload', $volume, array('added' => array($file))));
			}

			$result['header'] = $header;
			return $result;
		}

		/**
		* Словесное описание ошибки при загрузке
		*
		* @param mixed $errorCode
		* @param mixed $name
		*/
		protected function uploadErrorText($errorCode, $name) {
			switch($errorCode) {
				case UPLOAD_ERR_INI_SIZE: {
					$sUploadMaxFilesize = (is_numeric(ini_get('upload_max_filesize'))) ? ini_get('upload_max_filesize') . "M" : ini_get('upload_max_filesize');
					return  getLabel('error-max_filesize') . " {$sUploadMaxFilesize}";
				} break;
				case UPLOAD_ERR_FORM_SIZE: {
					return getLabel('error-max_filesize');
				}
			}
			return null;
		}

	}
?>
