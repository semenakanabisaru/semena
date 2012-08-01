<?php
	class RSSItem implements iRSSItem {
		private $url, $title, $anons, $content;

		public function setTitle($title) {
			$this->title = (string) $title;
		}

		public function getTitle() {
			if($this->title) {
				return $this->title;
			} else {
				return $this->date;
			}
		}


		public function setContent($content) {
			$this->content = (string) $content;
		}

		public function getContent() {
			return $this->content;
		}


		public function setDate($date) {
			$this->date = (string) $date;
		}

		public function getDate() {
			return $this->date;
		}

		public function setUrl($url) {
			$this->url = (string) $url;
		}

		public function getUrl() {
			return $this->url;
		}
	}
?>