<?php
	interface iMatches {
		public function __construct($umapFileName = "sitemap.xml");
		public function setCurrentURI($uri);
		public function execute($externalCall = true);

	};
?>