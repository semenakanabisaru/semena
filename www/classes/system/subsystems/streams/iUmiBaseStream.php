<?php
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
?>