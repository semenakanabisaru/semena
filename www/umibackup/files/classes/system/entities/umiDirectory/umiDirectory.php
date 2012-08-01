<?php

/**
	* Предоставляет интерфейс для работы с директориями
	* Использует итератор umiDirectoryIterator
*/

class umiDirectory implements iUmiDirectory, IteratorAggregate {
	protected $s_dir_path = "";
	protected $is_broken = false;
	protected $arr_files = array();
	protected $arr_dirs = array();
	protected $arr_objs = array();
	protected $is_readed = false;

	/**
		* Конструктор
		* @param String $s_dir_path путь к директории
	*/
	public function __construct($s_dir_path) {

		while (substr($s_dir_path, -1)=="/") $s_dir_path=substr($s_dir_path, 0, (strlen($s_dir_path)-1));

		if(is_dir($s_dir_path)) {
			$this->s_dir_path = $s_dir_path;
		} else {
			$this->is_broken = true;
			return false;
		}
	}

	/**
		* Возвращает путь к директории
		* @param String $s_dir_path путь к директории
		* @return String путь к директории
	*/
	public function getPath() {
		return $this->s_dir_path;
	}

	/**
		* Возвращает имя директории
		* @return String имя директории
	*/
	public function getName() {
		$arrDirPath = explode("/", $this->s_dir_path);
		return array_pop($arrDirPath);
	}

	public function getIterator() {
		$this->read();
		return new umiDirectoryIterator($this->arr_objs);
	}

	private function read() {
		if($this->is_readed) {
			return false;
		} else {
			$this->is_readed = true;
		}

		$this->arr_files = array();
		$this->arr_dirs = array();

		if($cache = self::cache($this->s_dir_path)) {
			list($this->arr_files, $this->arr_dirs, $this->arr_objs) = $cache;
			return;
		}

		if (is_dir($this->s_dir_path) && is_readable($this->s_dir_path)) {
			if ($rs_dir = opendir($this->s_dir_path)) {
				$s_next_file = "";
				while (($s_next_obj = readdir($rs_dir)) !== false) {
					if(defined("CURRENT_VERSION_LINE")) {
						if(CURRENT_VERSION_LINE == "demo") {
							if($s_next_obj == "demo") continue;
						}
					}
					$s_obj_path = $this->s_dir_path."/".$s_next_obj;
					if (is_file($s_obj_path)) {
						$this->arr_files[$s_next_obj] = $s_obj_path;
						$this->arr_objs[] = $s_obj_path;
					} elseif (is_dir($s_obj_path) && $s_next_obj != ".." && $s_next_obj != ".") {
						$this->arr_dirs[$s_next_obj] = $s_obj_path;
						$this->arr_objs[] = $s_obj_path;
					}
				}

				if(isset($s_dir)) {
					closedir($s_dir);
				}
			}
		}

		self::cache($this->s_dir_path, Array($this->arr_files, $this->arr_dirs, $this->arr_objs));
	}


	/**
		* Проверяет существует ли директория
		* @return Boolean true, если директория не существует
	*/
	public function getIsBroken() {
		return (bool) $this->is_broken;
	}

	/**
		* Читает директорию и возвращает массив объектов файловой системы
		* @param Integer $i_obj_type тип, который хотим получить: 1 - real files, 2 - directories, 0 - files & directories
		* @param String $s_mask="" маска по которой выбирать объекты
		* @param Boolean $b_only_readable=false сделать проверку на чтение и вернуть только объекты, доступные на чтение
		* @return Array массив объектов файловой системы. Ключ массива - имя объекта, значение - полный путь к нему
	*/
	public function getFSObjects($i_obj_type=0, $s_mask="", $b_only_readable=false) {
		$this->read();

		$arr_result =array();
		$arr_objs = array();

		switch ($i_obj_type) {
			case 1:									//1: real files
					$arr_objs = $this->arr_files;
				break;
			case 2:									//2: directories
					$arr_objs = $this->arr_dirs;
				break;
			default:
					$arr_objs = array_merge($this->arr_dirs, $this->arr_files);
		}

		foreach ($arr_objs as $s_obj_name => $s_obj_path) {
			if ((!$b_only_readable || is_readable($s_obj_path)) && (!strlen($s_mask)) || preg_match("/".$s_mask."/i", $s_obj_name)) {
				$arr_result[$s_obj_name] = $s_obj_path;
			}
		}

		ksort ($arr_result);
		return $arr_result;
	}

	/**
		* Читает директорию и возвращает массив файлов в директории
		* @param String $s_mask="" маска по которой выбирать файлы
		* @param Boolean $b_only_readable=false сделать проверку на чтение и вернуть только файлы, доступные на чтение
		* @return Array массив файлов. Ключ массива - имя файла, значение - полный путь к нему
	*/
	public function getFiles($s_mask="", $b_only_readable=false) {
		return $this->getFSObjects(1, $s_mask, $b_only_readable);
	}

	/**
		* Читает директорию и возвращает массив поддиректорий
		* @param String $s_mask="" маска по которой выбирать директории
		* @param Boolean $b_only_readable=false сделать проверку на чтение и вернуть только файлы, доступные на чтение
		* @return Array массив директорий. Ключ массива - имя директории, значение - полный путь к ней
	*/
	public function getDirectories($s_mask="", $b_only_readable=false) {
		return $this->getFSObjects(2, $s_mask, $b_only_readable);
	}

	/**
		* Читает директорию и возвращает массив всех вложенных файлов и директорий на всю глубину
		* @param Integer $i_obj_type тип, который хотим получить: 1 - real files, 2 - directories, 0 - files & directories
		* @param String $s_mask="" маска по которой выбирать объекты
		* @param Boolean $b_only_readable=false сделать проверку на чтение и вернуть только объекты, доступные на чтение
		* @return Array массив объектов файловой системы. Ключ массива - полный путь к нему объекта, значение - имя
	*/
	public function getAllFiles($i_obj_type=0, $s_mask="", $b_only_readable=false) {
		$files = $this->getFSObjects($i_obj_type, $s_mask, $b_only_readable);
		$result = array_flip($files);

		$dirs = $this->getFSObjects(2, $s_mask, $b_only_readable);
		foreach ($dirs as $dir) {
			$dir = new umiDirectory($dir);
			$dirFiles = $dir->getAllFiles($i_obj_type, $s_mask="", $b_only_readable);
			$result = array_merge($result, $dirFiles);
		}
		return $result;
	}

	/**
		* Удалить директорию
		* @param Boolean $recursion = false удалить рекурсивно вместе со всем содержанием
	*/
	public function delete($recursion = false) {
		if(is_writable($this->s_dir_path)) {
			if($recursion) {
				foreach($this as $item) {
					if($item instanceof umiDirectory) {
						$item->delete(true);
					} else if ($item instanceof umiFile) {
						$item->delete();
					}
				}
			}
			return @rmdir($this->s_dir_path);
		} else {
			return false;
		}
	}

	/**
	    * Убедиться, что директория $folder существует, если нет, то создать ее
	    * @param String $folder проверяемая директория
	    * @param String $basedir = "" родительский кактог, который должен содержать проверяемую директорию
	    * @return Boolean true, если директория существует, либо успешно создана
	*/
	public static function requireFolder($folder, $basedir = "") {
	    if(!$folder) return false;

	    if(is_dir($folder) == false) {
	        mkdir($folder, 0777, true);
	    }

	    $realpath = realpath($folder);
	    $basedir = realpath($basedir);
	    return (substr($realpath, 0, strlen($basedir)) == $basedir);

	}

	public function __toString() {
		return "umiDirectory::{$this->s_dir_path}";
	}

	protected static function cache($key, $value = NULL) {
		static $cache = Array();

		if($value) {
			return $cache[$key] = $value;
		}

		if(isset($cache[$key])) {
			return $cache[$key];
		} else {
			return NULL;
		}
	}
}


class umiDirectoryIterator implements Iterator {

	private $arr_objs = array();


	public function __construct($arr_objs) {
		if (is_array($arr_objs)) {
			$this->arr_objs = $arr_objs;
		}
	}

	public function rewind() {
		reset($this->arr_objs);
	}

	public function current() {
		$oResult = null;
		$s_obj_path = current($this->arr_objs);
		if (is_file($s_obj_path)) {
			if (umiImageFile::getIsImage($s_obj_path)) {
				$oResult = new umiImageFile($s_obj_path);
			} else {
				$oResult = new umiFile($s_obj_path);
			}
		} elseif (is_dir($s_obj_path)) {
			$oResult = new umiDirectory($s_obj_path);
		}

		return $oResult;
	}

	public function key() {
		return current($this->arr_objs);
	}

	public function next() {
		$oResult = null;
		$s_obj_path = next($this->arr_objs);
		if (is_file($s_obj_path)) {
			if (umiImageFile::getIsImage($s_obj_path)) {
				$oResult = new umiImageFile($s_obj_path);
			} else {
				$oResult = new umiFile($s_obj_path);
			}
		} elseif (is_dir($s_obj_path)) {
			$oResult = new umiDirectory($s_obj_path);
		}

		return $oResult;
	}

	public function valid() {
		return !is_null($this->current());
	}
}

?>