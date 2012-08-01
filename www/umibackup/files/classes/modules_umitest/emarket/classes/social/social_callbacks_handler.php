<?php
	abstract class socialCallbackHandler {
		final public static function get($network_id) {
			
				if(empty($network_id)) {
					return false;
				}
				
				$file = CURRENT_WORKING_DIR.'/classes/modules/emarket/classes/social/callbacks/'.$network_id .'.php';
				if(file_exists( $file )) {
					require $file;
					$c = $network_id.'SocialCallbackHandler';
					return new $c();
				}
				
				return false;
		}
		
		public function flushMessage($output = "", $ctype = 'text/xml') {

			$buffer = outputBuffer::current('HTTPOutputBuffer');
			$buffer->charset("utf-8");
			$buffer->contentType($ctype);
			$buffer->push($output);
			$buffer->end();
			return;
		}
		
		abstract function response();
	};
?>