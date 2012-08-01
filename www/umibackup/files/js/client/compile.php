<?php
	chdir(dirname(__FILE__));
 	include "../../developerTools/jsPacker/class.JavaScriptPacker.php";
	include "../../developerTools/jsPacker/jsPacker.php";

	$xml = @simplexml_load_file('compress.xml');
	
	if (!$xml) {
		die('// No valid source for packer');
	}
	
	$file_result = ((string)$xml->pack['path']);

	$files = array();
	foreach($xml->pack->file as $k=>$v)	{
		$files[] = (string)$v['path'];
	}
	
	$packer = new jsPacker($files);
	$packer->pack($file_result, isset($_REQUEST['compress']));
?>