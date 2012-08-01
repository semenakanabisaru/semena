<?php
	class umiHierarchyTypeWrapper extends translatorWrapper {
		public function translate($data) {
			return $this->translateData($data);
		}
		
		protected function translateData(iUmiHierarchyType $type) {
			return array(
				'attribute:id'		=> $type->getId(),
				'attribute:module'	=> $type->getName(),
				'attribute:method'	=> $type->getExt(),
				'node:title'		=> $type->getTitle()
			);
		}
	}
?>