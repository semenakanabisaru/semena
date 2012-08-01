<?php
	class uhttpStream extends umiBaseStream {
		protected $scheme = "uhttp";


		public function stream_open($path, $mode, $options, $opened_path) {
			$cacheFrontend = cacheFrontend::getInstance();
			$path = $this->parsePath($path);
			
			if($path) {
				if($data = $cacheFrontend->loadData($path)) {
					return $this->setData($data);
				}
				
				$data = umiRemoteFileGetter::get($path);
				
				if($this->expire) $cacheFrontend->saveData($path, $data, $this->expire);
				
				return $this->setData($data);
			}
			return true;
		}
		
		
		protected function parsePath($path) {
			$path = "http://" . (string) parent::parsePath($path);
			if($path) {
				$params = $this->params;
				if(isset($params['expire'])) {
					unset($params['expire']);
				}
				
				if(sizeof($params)) {
					$path .= "?" . http_build_query($this->params, '', '&');
				}
				return $this->path = $path;
			} else {
				return $this->path = false;
			}
		}
		
		
		protected function translateToXml() {
			$args = func_get_args();
			$object = $args[0];
		
			$request = Array("full:object" => $object);
			return parent::translateToXml($request);
		}
	};
?>
