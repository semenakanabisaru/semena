<?php
	class ManifestAction extends atomicAction {
		protected $subConfig, $subManifest;
		
		public function execute() {
			$manifest_path = $this->getParam('manifest');
			
			$subConfig = new ManifestConfig($manifest_path);
			$subConfig->read();	//Read config here to avoid Exception in constructor of manifest
			
			$subManifest = new manifest($subConfig);
			
			if($this->callback instanceof iManifestCallback) {
				$subManifest->setCallback($this->callback);
			}

			$subManifest->execute();
			
			$this->subManifest = $subManifest;
		}
		
		public function rollback() {
			$subManifest = $this->subManifest;
			
			if($subManifest instanceof iManifest) {
				$subManifest->rollback();
			}
		}
	};
?>