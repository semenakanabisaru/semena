<?php
	class umapStream extends umiBaseStream {
		protected $scheme = "umap";


		public function stream_open($path, $mode, $options, $opened_path) {
			$cacheFrontend = cacheFrontend::getInstance();
			$path = $this->parsePath($path);
			
			if($data = $cacheFrontend->loadData($path)) {
				return $this->setData($data);
			}
			
			if($path) {
				try {
					$matches = new matches("sitemap.xml");
					$matches->setCurrentURI($path);
					$data = $matches->execute(false);
				} catch (Exception $e) {
					traceException($e);
				}
				
				if($this->expire) $cacheFrontend->saveObject($path, $data, $this->expire);
				return $this->setData($data);
			} else {
				return $this->setDataNotFound();
			}
		}
		
		
		protected function parsePath($path) {
			$path = parent::parsePath($path);
			if($path) {
				return $this->path = $path;
			} else {
				return $this->path = false;
			}
		}
	};
?>