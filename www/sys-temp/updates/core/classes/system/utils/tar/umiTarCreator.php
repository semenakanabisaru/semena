<?php
/**
* Class for creating uncompressed tarball (ustar) archives from list of files and folders.
* Duplicates don't handle
* @author Leeb
* @link http://www.freebsd.org/cgi/man.cgi?query=tar&sektion=5&manpath=FreeBSD+8-current
*/
class umiTarCreator {

	const TAR_CHUNK_SIZE = 512;
	const READ_CHUNK_SIZE = 2048;

	private $archiveFilename = null;
	private $listFilename = null;
	private $handle = null;

	public function __construct($archiveFilename, $listFilename) {
		$this->archiveFilename = $archiveFilename;
		$this->listFilename = $listFilename;
	}

	public function process($limit = false) {
		$list = $this->loadList();
		$this->open();
		if(!$limit) {
			$limit = count($list);
		}
		for($i = 0; $i < $limit; $i++) {
			$name = rtrim($list[$i], "\r\n");
			if(!strlen($name)) continue;
			$info = stat(rtrim($name, "/\\"));
			if(is_dir(rtrim($name, "/\\"))) {
				$this->writeDirectory($name, $info);
			} else {
				$this->writeFile($name, $info);
			}
		}
		$list = array_slice($list, $limit);
		$this->saveList($list);
		if(empty($list)) {
			// Write eof sign (512 zero-bytes twice, see documentation)
			fwrite($this->handle, str_repeat(chr(0), umiTarCreator::TAR_CHUNK_SIZE * 2));
		}
		$this->close();
		return empty($list);
	}

	private function open() {
		if($this->handle == null) {
			$this->handle = fopen($this->archiveFilename, 'ab');
			if($this->handle === false) {
				throw new Exception("umiTarCreator: Can't open {$this->archiveFilename}");
			}
		}
		return $this->handle;
	}

	private function close() {
		if($this->handle != null) {
			fclose($this->handle);
		}
	}

	private function loadList() {
		$list = file($this->listFilename, FILE_IGNORE_NEW_LINES);
		if($list == false) {
			throw new Exception("umiTarCreator: Can't read list of files {$this->archiveFilename}");
		}
		return $list;
	}

	private function saveList($list) {
		file_put_contents($this->listFilename, implode("\n", $list));
	}

	private function writeDirectory($name, $stat) {
		$this->writeHeader($name, $stat, '5');
	}

	private function writeFile($name, $stat) {
		$this->writeHeader($name, $stat);
		$input = fopen($name, "rb");
		$readed = 0;
		$oldPosition = 0;
		do{
			$data = fread($input, umiTarCreator::READ_CHUNK_SIZE);
			$position = ftell($input);
			$readed = $position - $oldPosition;
			$oldPosition = $position;
			fwrite($this->handle, $data, $readed);
		} while($readed == umiTarCreator::READ_CHUNK_SIZE);
		$chunkCount = floor($stat['size'] / umiTarCreator::TAR_CHUNK_SIZE) + 1;
		$paddingSize = $chunkCount * umiTarCreator::TAR_CHUNK_SIZE - $stat['size'];
		fwrite($this->handle, str_repeat(chr(0), $paddingSize), $paddingSize);
		fclose($input);
	}

	private function writeHeader($name, $stat, $type = '0') {
		$userInfo = function_exists('posix_getpwuid') ? posix_getpwuid($stat['uid']) : array('name' => '');
		$groupInfo = function_exists('posix_getgrgid') ? posix_getgrgid($stat['gid']) : array('name' => '');

		$mode = sprintf("%07o", fileperms(rtrim($name, "/\\")));
		$uid = sprintf("%07o", $stat['uid']);
		$gid = sprintf("%07o", $stat['gid']);
		$size = sprintf("%011o", $stat['size']);
		$mtime = sprintf("%011o", $stat['mtime']);
		$checksum = '        ';
		$uname = $userInfo['name'];
		$gname = $groupInfo['name'];

		$devmajor = '0000000';
		$devminor = '0000000';

		$magic 	  = 'ustar ';
		$version  = '00';
		$linkname = '';
		$prefix   = '';
		$uname    = '';
		if(strlen($name) > 100) {
			$splitPos = strpos($name, '/', strlen($name) - 100);
			$prefix = substr($name, 0, $splitPos);
			$name = substr($name, $splitPos + 1);
		}
		$header = pack('a100a8a8a8a12a12a8a1a100a6a2a32a32a8a8a155x12',
							$name, $mode, $uid, $gid, $size, $mtime,
							$checksum, $type, $linkname, $magic, $version,
							$uname, $gname, $devmajor, $devminor, $prefix);
		$checksum = 0;
		for($i=0; $i < umiTarCreator::TAR_CHUNK_SIZE; $i++) {
			$checksum += ord($header[$i]);
		}
		$checksum = sprintf("%06o\0 ", $checksum);
		$header = substr_replace($header, $checksum, 148, 8);
		if(fwrite($this->handle, $header, umiTarCreator::TAR_CHUNK_SIZE) != umiTarCreator::TAR_CHUNK_SIZE) {
			throw new Exception("umiTarCreator: Can't write header for {$prefix}/{$name} to {$this->archiveFilename}");
		}
	}

};
?>
