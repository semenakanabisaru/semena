<?php
$__install = dirname(__FILE__)."/__install.php";
define("PHP_FILES_ACCESS_MODE", octdec(substr(decoct(fileperms(__FILE__)), -4, 4)));

if (!file_exists($__install) || (time()>filectime($__install)+86400)) {
	if (!is_writeable(dirname(__FILE__))) {
		header("Content-Type: text/plain; charset=utf-8");
		echo "Корневая директория \"".dirname(__FILE__)."\" недоступна для записи. Подробнее: http://errors.umi-cms.ru/13010/";
	}
	else {
		$query = base64_decode("aHR0cDovL3d3dy51bWktY21zLnJ1L2luc3RhbGwvZmlsZXMvX19pbnN0YWxsLnBocA==");
		$contents = get_file($query);
		file_put_contents($__install, $contents);
		umask(0);
		chmod($__install, PHP_FILES_ACCESS_MODE);
	}
}

function get_file($url) {
	$url = preg_replace('|^http:\/\/|i', '', $url);	
	$host = preg_replace('|\/.+$|i', '', $url);
	$query = preg_replace('|^[^/]+|i', '', $url);	
	if (is_callable("curl_init")) {
		return get_file_by_curl($host, $query);
	}
	elseif($fp=fsockopen($host, 80)) {
		fclose($fp);
		return get_file_by_remote($host, $query);
	}
	throw new Exception('Запрещены функции удаленной загрузки файлов. Подробнее: http://errors.umi-cms.ru/13041/');
}

// Получение содержимого файла через curl
function get_file_by_curl($host, $query) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "http://{$host}{$query}");
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$res = curl_exec($ch);
	curl_close($ch);
	return $res;
}

// Получение содержимого файла через сокеты
function get_file_by_remote($host, $query)	{
	$fp = fsockopen($host, 80, $errno, $errstr, 30);
	$out = "GET {$query} HTTP/1.0\r\n";
	$out .= "Host: {$host}\r\n";   
	$out .= "Connection: Close\r\n\r\n";
	fwrite($fp, $out);
	// Пропускаем заголовки
	while(!feof($fp)) {
		$str = fgets($fp, 1024);
		if (strlen($str)==2) {
			break;
		}
	}
	// Читаем содержимое
	$res = '';
	while (!feof($fp)) {
		$res .= fread($fp, 1024);
	}
	fclose($fp);
	return $res;
}

include($__install);
?>
