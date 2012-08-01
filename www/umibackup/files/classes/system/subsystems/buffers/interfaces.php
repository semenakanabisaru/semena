<?php
	interface iOutputBuffer {
		public function push($data);
		public function content();
		public function length();
		public function clear();
		public function send();
		public function end();
	};


	abstract class outputBuffer implements iOutputBuffer {
		private static $buffers = array(), $current = false;
		
		final static public function current($bufferClassName = false) {
			$buffers = &self::$buffers;

			if(!$bufferClassName) {
				if(self::$current) {
					$bufferClassName = self::$current;
				} else {
					throw new coreException('No output buffer selected');
				}
			}
			self::$current = $bufferClassName;

			if(isset($buffers[$bufferClassName]) == false) {
				if(class_exists($bufferClassName)) {
					$buffer = new $bufferClassName;
					if($buffer instanceof iOutputBuffer) {
						$buffers[$bufferClassName] = $buffer;
					} else {
						throw new coreException("Output buffer class \"{$bufferClassName}\" must implement iOutputBuffer");
					}
				} else {
					throw new coreException("Output buffer of class \"{$bufferClassName}\" not found");
				}
			}

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
		
		public function __destruct() {
			$this->send();
		}
	};
?>