<?php


$f = trim((string)@$_GET['path'], '-');

$f = preg_replace("/[^a-z0-9]/i", '', $f);

if(!empty($f)) 
{
		define("CRON","CLI");
		require CURRENT_WORKING_DIR."/standalone.php";

		$connection = ConnectionPool::getInstance()->getConnection('core'); 

		$connection->query("
		   CREATE TABLE IF NOT EXISTS  cms_stat_dispatches
		   (
				`hash` Varchar(10) NOT NULL, 
				`time` INT(11) NOT NULL
		   )
		   engine=innodb DEFAULT CHARSET=utf8;
		");

		$connection->query("INSERT INTO cms_stat_dispatches (hash, time) VALUES('".$f."', '".time()."')");
}

header('Content-Type: image/gif');

$im  = imagecreatetruecolor (1, 1);

imagealphablending($im, true);

$bgc = imagecolorallocate ($im, 255, 255, 255);
imagecolortransparent($im, $bgc);

//imagestring ($im, 1, 5, 5, '', $tc);
imagegif($im);


?>