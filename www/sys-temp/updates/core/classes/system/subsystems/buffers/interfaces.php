<?php
	interface iOutputBuffer {
		static public function current($bufferClassName = false);
		public function push($data);
		public function calltime();
		public function content();
		public function length();
		public function clear();
		public function send();
		public function end();
		public static function contentGenerator($generatorType = null);
	};


	abstract class outputBuffer implements iOutputBuffer {
		private static $buffers = array(), $current = false;

		final static public function current($bufferClassName = false) {
		showWorkTime("buffer current init",2);
		$buffers = &self::$buffers;
        showWorkTime("buffer current self done",2);
			if(!$bufferClassName) {
				if(self::$current) {
					$bufferClassName = self::$current;
				} else {
					throw new coreException('No output buffer selected');
				}
			}
			showWorkTime("buffer not exists class",2);
			self::$current = $bufferClassName;
			showWorkTime("buffer self current initiated",2);

			if(isset($buffers[$bufferClassName]) == false) {
				if(class_exists($bufferClassName)) {
					$buffer = new $bufferClassName;
					showWorkTime("buffer new bufferClassName initiated",2);
					if($buffer instanceof iOutputBuffer) {
						$buffers[$bufferClassName] = $buffer;
						showWorkTime("buffers array element initiated",2);
					} else {
						throw new coreException("Output buffer class \"{$bufferClassName}\" must implement iOutputBuffer");
					}
				} else {
					throw new coreException("Output buffer of class \"{$bufferClassName}\" not found");
				}
			}
			showWorkTime("buffer current end",2);

			return $buffers[$bufferClassName];
		}


		//Methods useful for extending
		protected $buffer = "", $invokeTime;

		public function __construct() { $this->invokeTime = microtime(true); }

		public function clear() { $this->buffer = ""; }

		public function length() { return strlen($this->buffer); }

		public function content() { return $this->buffer; }

		public function push($data) { $this->buffer .= $data; }

		public function end() { $this->send(); }

		public function calltime() { return round(microtime(true) - $this->invokeTime, 6);  }

		public function __call($method, $params) { return null; }

		public function redirect($url, $status = '301 Moved Permanently') { }

		/**
		 * @static
		 * Возвращает / устанавливает название генератора контента
		 * Используется для вывода в generate time блоке
		 * @param string|null $generatorType
		 *
		 * @return string|null
		 */
		public static function contentGenerator($generatorType = null) {
			static $contentGenerator = null;
			if (is_null($generatorType)) {
				return $contentGenerator;
			}
			return $contentGenerator = $generatorType;
		}

		public function __destruct() {
			$this->send();
		}
	};
?>