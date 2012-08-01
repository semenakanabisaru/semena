<?php
	abstract class __search_catalog {
		/* All methods moved to data module */
		
		public function getDataModule() {
			static $dataModule;
			if(!$dataModule) {
				$dataModule = cmsController::getInstance()->getModule('data');
			}
			return $dataModule;
		}
		
		public function parseSearchRelation(umiField $field, $template, $template_item, $template_separator) {
			return $this->getDataModule()->parseSearchRelation($field, $template, $template_item, $template_separator);
		}

		public function parseSearchText(umiField $field, $template) {
			return $this->getDataModule()->parseSearchText($field, $template);
		}

		public function parseSearchPrice(umiField $field, $template) {
			return $this->getDataModule()->parseSearchPrice($field, $template);
		}

		public function parseSearchBoolean(umiField $field, $template) {
			return $this->getDataModule()->parseSearchBoolean($field, $template);
		}

		public function parseSearchInt(umiField $field, $template) {
			return $this->getDataModule()->parseSearchInt($field, $template);
		}
		
		public function parseSearchDate(umiField $field, $template) {
			return $this->getDataModule()->parseSearchDate($field, $template);
		}

		public function parseSearchSymlink(umiField $field, $template, $category_id) {
			return $this->getDataModule()->parseSearchSymlink($field, $template, $category_id);
		}		

		public function applyFilterName(umiSelection $sel, $value) {
			return $this->getDataModule()->applyFilterName($sel, $value);
		}

		public function applyFilterText(umiSelection $sel, umiField $field, $value) {
			return $this->getDataModule()->applyFilterText($sel, $field, $value);
		}

		public function applyFilterInt(umiSelection $sel, umiField $field, $value) {
			return $this->getDataModule()->applyFilterInt($sel, $field, $value);
		}

		public function applyFilterRelation(umiSelection $sel, umiField $field, $value) {
			return $this->getDataModule()->applyFilterRelation($sel, $field, $value);
		}

		public function applyFilterPrice(umiSelection $sel, umiField $field, $value) {
			return $this->getDataModule()->applyFilterPrice($sel, $field, $value);
		}

		public function applyFilterDate(umiSelection $sel, umiField $field, $value) {
			return $this->getDataModule()->applyFilterDate($sel, $field, $value);
		}
		
		public function applyFilterFloat(umiSelection $sel, umiField $field, $value) {
			return $this->getDataModule()->applyFilterFloat($sel, $field, $value);
		}

		public function applyFilterBoolean(umiSelection $sel, umiField $field, $value) {
			return $this->getDataModule()->applyFilterBoolean($sel, $field, $value);
		}

		public static function protectStringVariable($stringVariable = "") {
			return $this->getDataModule()->protectStringVariable($stringVariable);
		}
		
		public function applyKeyedFilters(umiSelection $sel, umiField $field, $values) {
			return $this->getDataModule()->applyKeyedFilters($sel, $field, $values);
		}
	};
?>