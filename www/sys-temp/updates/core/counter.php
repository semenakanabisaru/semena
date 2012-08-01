<?php
	
$f = trim((string)@$_GET['path'], '-');

$f = preg_replace("/[^a-z0-9]/i", '', $f);

if(!empty($f)) 
{
		define("CRON","CLI");
		
		$connection = mysql_connect("localhost", "dispatch_counter", '76YHsmwl98');
		
		if (!$connection) {
			die('Could not connect: ' . mysql_error());
		}
		;
		if (!mysql_select_db('dispatch_counter', $connection)) {
			die ('Can\'t use  : ' . mysql_error());
		}
		
		
		mysql_query("INSERT INTO cms_stat_dispatches (hash, time) VALUES('".$f."', '".time()."')");
		
		mysql_close($connection);
}

header('Content-Type: image/gif');

$im  = imagecreatetruecolor (1, 1);

imagealphablending($im, true);

$bgc = imagecolorallocate ($im, 255, 255, 255);
imagecolortransparent($im, $bgc);

//imagestring ($im, 1, 5, 5, '', $tc);
imagegif($im);


?>