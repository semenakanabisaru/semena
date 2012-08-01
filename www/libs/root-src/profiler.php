<?php

	/*if (!defined("UMI_SYSTEM_START_TIME"))		{ define("UMI_SYSTEM_START_TIME",microtime(true)); }
	if (!defined("UMI_TIME_PROFILER_LEVEL"))	{ define("UMI_TIME_PROFILER_LEVEL",0); }
	if (!defined("UMI_TIME_PROFILER_MINDELTA"))	{ define("UMI_TIME_PROFILER_MINDELTA",0); }
	if (!defined("UMI_TIME_PROFILER_PID"))		{ define("UMI_TIME_PROFILER_PID",uniqid()); }
	if (!defined("UMI_TIME_PROFILER_LOG"))		{ define("UMI_TIME_PROFILER_LOG",rtrim($_SERVER['DOCUMENT_ROOT'],'/').'/time-profile.log'); }
	//if (!defined("UMI_XSLT_PROFILER_LOG"))		{ define("UMI_XSLT_PROFILER_LOG",rtrim($_SERVER['DOCUMENT_ROOT'],'/').'/xslt-profile.log'); }

	file_put_contents(	UMI_TIME_PROFILER_LOG,
				"Id             Action                                                  Run time           Action time                     Memory    Rusage\n",
				FILE_APPEND);

	$umi_time_profiler_mark=UMI_SYSTEM_START_TIME;*/

	/**
	* Профилирование выполнения.
    * Выводит метку, время от начала работы скрипта, и дельту времени от предыдущей метки в миллисекундах.
    * Не забывайте коментировать содержимое этой функции после отладки, чтобы не затрачивать ресурсы на production-сайтах.
	* 
	* @param string $m - метка, по которой можно однозначно определить место её установки
	* @param int $level - уровень уведомления (задается произвольно по желанию разработчика в каждом вызове функции showWorkTime)
	*/
	function showWorkTime($m, $level=0) {
		/*global $umi_time_profiler_mark;
		if ($level<=UMI_TIME_PROFILER_LEVEL) {
			$mt=microtime(true);
			$delta=$mt-$umi_time_profiler_mark;
			if ($delta>=UMI_TIME_PROFILER_MINDELTA) {
				$ru=getrusage();
				file_put_contents(UMI_TIME_PROFILER_LOG,sprintf("%-70s",substr("#".UMI_TIME_PROFILER_PID." ".$m,0,70))." ".sprintf("%-18s",substr($mt-UMI_SYSTEM_START_TIME,0,18))." [".(sprintf("%-17s",1000*($delta)))." msec]"." ".sprintf("%10s",intval(0.001*memory_get_usage()))." Kb    ".($ru['ru_utime.tv_sec']+$ru['ru_utime.tv_usec']/1000000).":".($ru['ru_stime.tv_sec']+$ru['ru_stime.tv_usec']/1000000)."\n",FILE_APPEND);
			}
		$umi_time_profiler_mark=$mt;
		}*/
	}	
	
?>