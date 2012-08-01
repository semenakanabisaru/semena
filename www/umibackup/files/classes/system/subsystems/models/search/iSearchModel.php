<?php
	interface iSearchModel {
		public function runSearch($searchString, $searchTypesArray = NULL);
		public function getContext($elementId, $searchString);
		public function getIndexPages();
		public function getAllIndexablePages();
		public function getIndexWords();
		public function getIndexWordsUniq();
		public function getIndexLast();
		public function truncate_index();
		public function index_all($limit = false);
		public function index_item($elementId);

		public function index_items($elementId);
		public function unindex_items($elementId);
		
		public function suggestions($string, $limit = 10);
	};
?>