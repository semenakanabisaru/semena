<?php

	interface iUmiDirectory {
		public function getPath();
		public function getName();

		public function getIsBroken();
		public function getFSObjects($objectType = 0, $mask = "", $onlyReadable = false);
		public function getFiles($mask = "", $onlyReadable = false);
		public function getDirectories($mask = "", $onlyReadable = false);

		public function getAllFiles($i_obj_type=0, $s_mask="", $b_only_readable=false);

		public function delete($recursion = true);

		public static function requireFolder($folder, $basedir = "");
	};

?>