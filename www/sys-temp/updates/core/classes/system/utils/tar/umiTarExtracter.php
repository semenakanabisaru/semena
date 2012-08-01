<?php
/**
* Class for extracting files from uncompressed tarball (ustar) archives
* @author Leeb
* @link http://www.freebsd.org/cgi/man.cgi?query=tar&sektion=5&manpath=FreeBSD+8-current
*/
class umiTarExtracter {
	const TAR_CHUNK_SIZE = 512;
	/**
	* Tar entry type flags
	*/
	const TAR_ENTRY_REGULARFILE = '0';
	const TAR_ENTRY_HARDLINK 	= '1';
	const TAR_ENTRY_SYMLINK 	= '2';
	const TAR_ENTRY_CHARDEVICE 	= '3';
	const TAR_ENTRY_BLOCKDEVICE = '4';
	const TAR_ENTRY_DIRECTORY	= '5';
	const TAR_ENTRY_FIFO 		= '6';
	const TAR_ENTRY_RESERVED 	= '7';

	/**
	* Path to the tarball archive file
	*
	* @var string
	*/
	private $archiveFilename = null;

	/**
	* Archive file handle
	*
	* @var resource
	*/
	private $handle = null;

	/**
	* @param string $filename path to tarball archive file
	* @return umiTarExtracter
	*/
	public function __construct($filename) {
		$this->archiveFilename = $filename;
		if(!is_file($this->archiveFilename)) {
			throw new Exception("umiTarExtracter: {$this->archiveFilename} is not exist");
		}
	}

	public function __destruct() {
		$this->close();
	}

	/**
	* Extract $limit file records starting from $offset position
	*
	* @param int|false $offset
	* @param int|false $limit
	* @return int new offset (after extracting)
	*/
	public function extractFiles($offset = false, $limit = false) {
		$currentOffset = 0;

		$this->open();

		fseek($this->handle, 0, SEEK_SET);

		while($currentOffset < $offset) {
			$data = fread($this->handle, umiTarExtracter::TAR_CHUNK_SIZE);
			if($this->eof($data)) {
				return $currentOffset;
			}
			$header = $this->parseEntryHeader($data);
			if($header['typeflag'] == umiTarExtracter::TAR_ENTRY_REGULARFILE) {
				$fileChunkCount = floor($header['size'] / umiTarExtracter::TAR_CHUNK_SIZE) + 1;
				fseek($this->handle, $fileChunkCount * umiTarExtracter::TAR_CHUNK_SIZE, SEEK_CUR);
			}
			$currentOffset++;
		}

		while($limit === false || ($currentOffset < $offset + $limit)) {
			$data = fread($this->handle, umiTarExtracter::TAR_CHUNK_SIZE);
			if($this->eof($data)) {
				break;
			}
			$header = $this->parseEntryHeader($data);
			$name = (strlen($header['prefix']) ? ($header['prefix'] . '/') : '') . $header['name'];
			switch($header['typeflag']) {
				case umiTarExtracter::TAR_ENTRY_REGULARFILE : {
					$dstHandle = fopen($name, "wb");
					if(!$dstHandle) {
						throw new Exception("umiTarExtracter: can't create file {$name}");
					}
					$bytesLeft = $header['size'];
					if($bytesLeft)
					do {
						$bytesToWrite = $bytesLeft < umiTarExtracter::TAR_CHUNK_SIZE ? $bytesLeft : umiTarExtracter::TAR_CHUNK_SIZE;
						$bytes = fread($this->handle, umiTarExtracter::TAR_CHUNK_SIZE);
						fwrite($dstHandle, $bytes, $bytesToWrite);
						$bytesLeft -= umiTarExtracter::TAR_CHUNK_SIZE;
					} while($bytesLeft > 0);
					fclose($dstHandle);
					break;
				}
				case umiTarExtracter::TAR_ENTRY_DIRECTORY : {
					if(!is_dir($name) && !mkdir($name, 0777, true)) {
						throw new Exception("umiTarExtracter: can't create directory {$name}");
					}
					break;
				}
			}
			$currentOffset++;
		}

		return $currentOffset;
	}

	private function open() {
		if($this->handle == null) {
			$this->handle = fopen($this->archiveFilename, 'rb');
			if($this->handle === false) {
				throw new Exception("umiTarExtracter: Can't open {$this->archiveFilename}");
			}
		}
		return $this->handle;
	}

	private function close() {
		if($this->handle != null) {
			fclose($this->handle);
		}
	}

	private function parseEntryHeader($rawHeaderData) {
		$header = unpack('a100name/a8mode/a8uid/a8gid/a12size/a12mtime/a8checksum/atypeflag/a100linkname/a6magic/a2version/a32uname/a32gname/a8devmajor/a8devminor/a155prefix/x12pad', $rawHeaderData);
		$header['uid'] 		= octdec($header['uid']);
		$header['gid'] 		= octdec($header['gid']);
		$header['size'] 	= octdec($header['size']);
		$header['mtime'] 	= octdec($header['mtime']);
		$header['checksum'] = octdec(substr($header['checksum'], 0, 6));
		return $header;
	}

	private function eof(&$data) {
		$eofPattern = null;
		if($eofPattern == null){
			$eofPattern = str_repeat(chr(0), 512);
		}
		if(strcmp($data, $eofPattern) == 0) {
			$ahead = fread($this->handle, umiTarExtracter::TAR_CHUNK_SIZE);
			if(strcmp($ahead, $eofPattern) == 0) {
				return true;
			}
			fseek($this->handle, -umiTarExtracter::TAR_CHUNK_SIZE, SEEK_CUR);
		}
		return false;
	}

};
?>