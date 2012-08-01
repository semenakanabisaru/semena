<?php

	abstract class baseSerialize implements iBaseSerialize {
		static $called = Array();
	
		final public static function serializeDocument($type, $buffer, $params) {
			$serializer = self::loadSerializer($type);
			return $serializer->execute($buffer, $params);
		}
		
		
		abstract public function execute($xmlString, $params);
		
		
		protected static function loadSerializer($type) {
			$filename = SYS_KERNEL_PATH . "subsystems/matches/serializes/{$type}/{$type}.php";
			if(is_file($filename)) {
				require $filename;
				
				$serializerClassName = strtolower($type) . "Serialize";
				
				if(class_exists($serializerClassName)) {
					return new $serializerClassName();
				} else {
					throw new coreException("Class {$serializerClassName} doesn't exsits");
				}
			} else {
				throw new coreException("Can't load serializer of type \"{$type}\"");
			}
		}
		
		
		protected function sendHTTPHeaders($params) {
			if(is_array($params)) {
				$buffer = outputBuffer::current();
				$headers = getArrayKey($params, 'headers');
				
				if(is_array($headers)) {
					foreach($headers as $i => $v) {
						if(strtolower($i) == 'content-type') {
							$buffer->contentType($v);
							continue;
						}
						$buffer->header($i, $v);
					}
				}
			} else {
				throw new coreException("First argument must be ad array in sendHTTPHeaders()");
			}
		}
	};
?>