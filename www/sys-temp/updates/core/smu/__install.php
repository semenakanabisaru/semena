<?php
error_reporting(0);
ini_set('display_errors', 0);

@session_start();
$sess_id = session_id();

define("UPDATE_SERVER", base64_decode('aHR0cDovL3Vkb2QudW1paG9zdC5ydS91cGRhdGVzZXJ2ZXIv'));

if (!defined("PHP_FILES_ACCESS_MODE")) {
	define("PHP_FILES_ACCESS_MODE", octdec(substr(decoct(fileperms(__FILE__)), -4, 4)));
}

if (isset($_REQUEST['doRestore'])) {
	header("Content-type: text/xml; charset=utf-8");
	echo doRestore();
	die();
}

if (isset($_REQUEST['getCodeImage'])) {
	header("Content-type: image/jpeg");
	$url1 = str_replace('updateserver/', base64_decode("Y2FwdGNoYS5waHA/cmVzZXQmUEhQU0VTU0lEPQ=="), UPDATE_SERVER);
	$url2 = str_replace('updateserver/', base64_decode("Y2FwdGNoYS5waHA/UEhQU0VTU0lEPQ=="), UPDATE_SERVER);
	get_file("{$url1}{$sess_id}");
	echo get_file("{$url2}{$sess_id}");
	die();
}
if (isset($_REQUEST['checkCode'])) {
	$code = $_REQUEST['captcha'];
	$url = str_replace('updateserver/', base64_decode("Y2FwdGNoYS5waHA/Y2hlY2s9dHJ1ZSZQSFBTRVNTSUQ9"), UPDATE_SERVER);
	$url.= "{$sess_id}&code={$code}";
	header("Content-type: text/xml; charset=utf-8");
	echo get_file($url);
	die();
}

if (isset($_REQUEST['getTrialKey'])) {
	header("Content-type: text/xml; charset=utf-8");
	echo getTrialKey();
	die();
}

if(isset($_REQUEST['check-license'])) {
	$key = isset($_REQUEST['key']) ? $_REQUEST['key'] : '';
	header("Content-type: text/xml; charset=utf-8");
	echo checkLicense($key);
	die();
}

if (isset($_REQUEST['check-mysql'])) {
	$param = array();
	$param['host'] = isset($_REQUEST['host']) ? trim(strip_tags($_REQUEST['host'])) : '';
	$param['dbname'] = isset($_REQUEST['dbname']) ? trim(strip_tags($_REQUEST['dbname'])) : '';
	$param['user'] = isset($_REQUEST['user']) ? trim(strip_tags($_REQUEST['user'])) : '';
	$param['password'] = isset($_REQUEST['password']) ? trim(strip_tags($_REQUEST['password'])) : '';
	header("Content-type: text/xml; charset=utf-8");
	echo checkMysql($param);
	die();
}

$step=0;
$demosite = '';
if(file_exists("./install.ini") && (!(file_exists("./installed")&&is_file("./installed")))) {
	$ini = parse_ini_file("./install.ini", true);

	// Проверяем предустановленный лицензионный ключ
	if (isset($ini['LICENSE']['key']) && strlen($ini['LICENSE']['key'])>=35) {
		$xml = checkLicense($ini['LICENSE']['key']);
		$dom = new DOMDocument();
		$dom->loadXML($xml);
		$type = $dom->getElementsByTagName('response')->item(0)->getAttribute('type');
		if ($type=='ok') {
			$step=1;
		}
	}

	// Проверяем предустановленные параметры подключения к базе
	if ($step==1 && isset($ini['DB']['host']) && strlen($ini['DB']['host'])>0
	 && isset($ini['DB']['dbname']) && strlen($ini['DB']['dbname'])>0
	 && isset($ini['DB']['user']) && strlen($ini['DB']['user'])>0
	 && isset($ini['DB']['password']) && strlen($ini['DB']['password'])>0 ) {
		$xml = checkMysql($ini['DB']);
		$dom = new DOMDocument();
		$dom->loadXML($xml);
		$type = $dom->getElementsByTagName('response')->item(0)->getAttribute('type');
		if ($type=='ok') {
			$step=2;
		}
	}

	// Предустановленный демосайт
	if (isset($ini['DEMOSITE']['name']) && strlen($ini['DEMOSITE']['name'])>0) {
		$demosite = $ini['DEMOSITE']['name'];
	}

}

if ( (file_exists("./installed")&&is_file("./installed")) || (substr(dirname(__FILE__), -4, 4)=='/smu' && (file_exists("../installed")&&is_file("../installed"))) ) {
	$step = 10;
}

if ( (file_exists("./restore")&&is_file("./restore")) || (substr(dirname(__FILE__), -4, 4)=='/smu' && (file_exists("../restore")&&is_file("../restore"))) ) {
	$step = 20;
}

if (!check_allow_remote_files()) {
	$step = 11;
	$error_header = 'Удаленные соединения запрещены';
	$error_content = 'Подробнее об ошибке: <a href="http://errors.umi-cms.ru/13041/" target="_blank">http://errors.umi-cms.ru/13041/</a>';
}
elseif ( ($errors=check_writeable()) && (count($errors)>0) ) {
	$step = 11;
	$error_header = 'Проверьте разрешения на запись';
	$error_content = 'Перечисленные файлы и папки должны быть доступны на запись:<ol>';
	foreach($errors as $path) {
		$error_content .= "<li>{$path}</li>";
	}
	$error_content .= "</ol>";
}

$sleep = get_sleep_time();

function getTrialKey() {
	$email = rawurlencode(trim($_REQUEST['email']));
	$lname = rawurlencode(trim($_REQUEST['lname']));
	$fname = rawurlencode(trim($_REQUEST['fname']));
	$domain = rawurlencode($_SERVER['HTTP_HOST']);
	$ip = rawurlencode($_SERVER['SERVER_ADDR']);
	$url = str_replace('updateserver/', base64_decode("dWRhdGEvY3VzdG9tL2dlbmVyYXRlTGljZW5zZUdhdGUvOERNRThEQ0pIRkQv"), UPDATE_SERVER);
	$url.= "{$email}/{$fname}/{$lname}/{$domain}/{$ip}/trial";
	return get_file($url);
}

function get_sleep_time() {
	$sleep = 0;
	if (file_exists('./install.ini')) {
		$info = parse_ini_file('./install.ini', true);
		if (isset($info["SETUP"]["sleep"])) {
			$sleep = (int) $info["SETUP"]["sleep"];
			if ($sleep<0) {
				$sleep=0;
			}
		}
	}	
	return $sleep;
}


function check_allow_remote_files() {
	if (!is_callable("curl_init") && !ini_get('allow_url_fopen') && !fsockopen(UPDATE_SERVER, 80)) {
		return false;
	}
	else {
		return true;
	}
}

function check_writeable() {
	$writeable = array(
		'dirs'	=> array(dirname(realpath(__FILE__))),
		'files'	=> array()
	);	
	
	$file = get_file('http://www.install.umi-cms.ru/writable_directories.txt');
	$dirs = explode("\n", $file);	
	
	foreach($dirs as $dir) {
		$dir = trim($dir);
		if (!is_dir($dir)) continue;
		$writeable['dirs'][] = realpath($dir);
	}	
	
	$errors = array();
	// Проверяем директории
	if ( isset($writeable['dirs']) && count($writeable['dirs'])>0 ) {
		foreach($writeable['dirs'] as $dir) {
			if ( file_exists($dir) && is_dir($dir) && !is_writeable($dir) ) {
				$errors[] = $dir;
			}
		}
	}
	// Проверяем файлы
	if ( isset($writeable['files']) && count($writeable['files'])>0 ) {
		foreach($writeable['files'] as $file) {
			if ( file_exists($file) && is_file($file) && !is_writeable($file) ) {
				$errors[] = $file;
			}
		}
	}
	// Возвращаем результат
	return $errors;
}

// Проверка подключения к базе данных
function checkMysql($params) {
	$link = mysql_connect($params['host'], $params['user'], $params['password']);
	if (!$link) {
		return "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n<response type=\"exception\"><error code=\"13011\"><![CDATA[Не удалось подключиться к mysql-серверу.]]></error></response>";
	}
	if (!mysql_select_db($params['dbname'])) {
		return "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n<response type=\"exception\"><error code=\"13011\"><![CDATA[Не удалось подключиться к указанной базе данных]]></error></response>";
	}
	return "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n<response type=\"ok\" />";
}

// Проверка лицензионного ключа
function checkLicense($key) {
	$param = array();
	$param['type'] = 'get-installer';
	$param['ip']   = isset($_SERVER['SERVER_ADDR'])?($_SERVER['SERVER_ADDR']):str_replace("\\", "", $_SERVER['DOCUMENT_ROOT']);
	$param['host'] = $_SERVER['HTTP_HOST'];
	$param['key'] = $key;
	$param['revision'] = 'last';
	
	$url = UPDATE_SERVER . '?' . http_build_query($param, '', '&');
	$contents = get_file($url);
	// Check valid xml
	$dom = new DOMDocument();
	if($dom->loadXML($contents)) {
		return $contents;
	} else {
		$installerPath = dirname(__FILE__) . "/installer.php";
		file_put_contents($installerPath, $contents);
		umask(0);
		chmod($installerPath, PHP_FILES_ACCESS_MODE);
		return "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n<response type=\"ok\" />";
	}
}

// Проверяет, была ли система установлена и забекапирована
function isBackup() {
	if (substr(dirname(__FILE__), -4, 4)=='/smu') {
		$backup_dir = realpath('..');
	}
	else {
		$backup_dir = realpath('.');
	}

	$backup_dir.='/umibackup';
	// Директория бэкапов отстутствует
	if (!is_dir($backup_dir)) return false;
	// Отсутствуют файлы с информацией о бекапировании
	if (!is_file($backup_dir.'/backup_database.xml') && !is_file($backup_dir.'/backup_files.xml')) return false;
	
	return true;
}

function doRestore() {
	$_REQUEST['step']='rollback';
	$_REQUEST['guiUpdate']='true';
	return include('installer.php');
}

header("Content-Type: text/html; charset=utf-8");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link href="http://install.umi-cms.ru/style_2.css" type="text/css" rel="stylesheet" />
		<script src="http://install.umi-cms.ru/js/jquery-1.4.2.min.js" type="text/javascript"></script>
		<script src="http://install.umi-cms.ru/js/jquery.corner.js" type="text/javascript"></script>
		<script src="http://install.umi-cms.ru/js/script_2.js" type="text/javascript"></script>
		<script src="http://install.umi-cms.ru/a.php" type="text/javascript"></script>
		<script type="text/javascript">
			var step = <?php echo $step; ?>;
			var demosite = <?php echo ($demosite==''?"''":"'".$demosite."'"); ?>;
			var sleep = <?php echo $sleep; ?>;
		</script>
		<title>Установка UMI.CMS</title>
	</head>
	<body>
		<form id="form1" action="">
			<div class="header">
				<p class="check_user"><?php echo (strlen($error_header)>0)?$error_header:''; ?></p>
				<a href="http://umi-cms.ru" title="UMI.CMS" target="_blank"><img alt="" src="http://install.umi-cms.ru/logo_.png" class="logo" /></a>
			</div>
			<div class="main">
				<div class="display_none shadow_some step0">
					<div class="padding_big">
						<p class="vvod_key">Введите ключ</p>
						<div class="clear"></div>
						<div class="b_input">
							<input type="text" name="key" value="<?php echo isset($ini['LICENSE']['key'])?$ini['LICENSE']['key']:"";?>" />
						</div>
						<div class="info">
							<div class="img_stop">
								<img alt="" src="http://install.umi-cms.ru/ikon_stop.png"  />
							</div>
							<div class="img_stop_text">
								<a href="http://www.umi-cms.ru/buy_now/licence_agreement/licence_agreement/" target="_blank">Лицензионное соглашение</a>
								&nbsp;&#124;&nbsp;
								<a href="http://www.umi-cms.ru/buy/free_license/?licence=trial" target="_blank">Получить триальный ключ бесплатно</a>
							</div>
						</div>
						<div class="clear"></div>
					</div>
				</div>
				<div class="display_none first_block shadow_some" id="getTrialKey">
					<div class="padding_big field_license_user">
						<div class="display_block_left">
							<label for="lname_trial">Фамилия</label>
							<input id="lname_trial" name="lname_trial" class="">
							<div class="clear"></div>
						</div>
						<div class="display_block_left">
							<label for="fname_trial">Имя</label>
							<input id="fname_trial" name="fname_trial" class="">
							<div class="clear"></div>
						</div>
						<div class="display_block_left">
							<label for="email_trial">E-mail *</label>
							<input id="email_trial" name="email_trial" class="">
							<div class="clear"></div>
						</div>
						<div class="display_block_left">
							<label for="code_trial"><img src="install.php?getCodeImage"/></label>
							<input class="" name="code_trial" id="code_trial">
							<div class="clear"></div>
						</div>
						<div class="clear"></div>
					</div>
				</div>
				<div class="display_none shadow_some step1">
					<div class="padding_big">
						<div class="display_block_left mar_left220px">
							<label>Имя хоста<br />
							<input name="host" type="text" value="<?php echo isset($ini['DB']['host'])?$ini['DB']['host']:"localhost";?>" tabindex="1" /></label><br />
							<label>Логин<br />
							<input name="user" type="text" value="<?php echo isset($ini['DB']['user'])?$ini['DB']['user']:"";?>" tabindex="3" /></label>
						</div>
						<div class="display_block_left posit_text24px">
							<label>Имя базы данных<br />
							<input name="dbname"  type="text" value="<?php echo isset($ini['DB']['dbname'])?$ini['DB']['dbname']:"";?>" tabindex="2" /></label><br />
							<label>Пароль<br />
							<input name="password" type="password" value="<?php echo isset($ini['DB']['password'])?$ini['DB']['password']:"";?>" tabindex="4"/></label>
						</div>
						<div class="clear"></div>
						<div class="info display_none marn">
							<div class="img_stop">
								<img alt="" src="http://install.umi-cms.ru/ikon_stop.png"  />
							</div>
							<div class="img_stop_text"></div>
						</div>
						<div class="info1">
							<p>Предупреждение: при установке будут очищены все таблицы, используемые UMI.CMS.</p>
						</div>
						<div class="clear"></div>
					</div>
				</div>
				<div class="display_none shadow_some step2">
					<div class="padding_big">
						<div class="display_block_left mar_left220px">
							<p class="style_p">Если вы устанавливаете систему поверх существующего сайта, то его содержимое будет заменено на содержимое UMI.CMS. Настоятельно рекомендуется выполнить бэкап (резервное копирование) вашего сайта.</p>
							<p>Выберите, что бэкапировать:</p>
							<label><input name="backup" type="radio" value="all" class="radio_but" <?php echo (isset($ini['BACKUP']['mode'])&&$ini['BACKUP']['mode']=='all')?"checked=\"checked\"":""; ?> />Базу данных и программные файлы</label><br />
							<!--label><input name="backup" type="radio" value="base" class="radio_but" <?php echo (isset($ini['BACKUP']['mode'])&&$ini['BACKUP']['mode']=='base')?"checked=\"checked\"":""; ?> />Только базу данных</label><br />
							<label><input name="backup" type="radio" value="files" class="radio_but" <?php echo (isset($ini['BACKUP']['mode'])&&$ini['BACKUP']['mode']=='files')?"checked=\"checked\"":""; ?> />Только основные файлы</label><br /-->
							<label><input name="backup" type="radio" value="none" class="radio_but" <?php echo (isset($ini['BACKUP']['mode'])&&$ini['BACKUP']['mode']=='none')?"checked=\"checked\"":""; ?> />Ничего (не рекомендуется)</label>
						</div>
						<div class="clear"></div>
					</div>
				</div>
				<div class="display_none shadow_some step3 step4 step5 step7">
					<div class="padding_big">
						<div class="loading">
							<p class="vvod_key">Установка системы</p>
							<p class="slider">
								<a href="#" class="wrapper">Показать ход установки</a>
							</p>
							<div class="clear"></div>
							<div class="b_input">
								<img alt="" src="http://install.umi-cms.ru/progress_bar_img.gif" class="progressbar_img" />
							</div>
							<div class="progressbar_wrap" style="display:none;">
								<div class="vnutrenniy" class="scroll-pane"></div>
							</div>
						</div>
						<div class="info display_none">
							<div class="img_stop">
								<img alt="" src="http://install.umi-cms.ru/ikon_stop.png"  />
							</div>
							<div class="img_stop_text"></div>
						</div>
					</div>
				</div>
				<div class="display_none fourth_block shadow_some step6">
					<div class="display_page_4">
						<div class="no_demo shadow_some">
							<label><input type="radio" name="demosite" value="_blank" />Без демо-сайта</label>
						</div>
						<div class="clear"></div>
						<div class="demo_example_left posit_text24px"></div>
						<div class="demo_example_left"></div>
						<div class="clear"></div>
					</div>
				</div>
				<div class="display_none third_block shadow_some step8">
					<div class="padding_big">
						<div class="display_block_left mar_left220px">
							<label>Логин<br />
							<input name="sv_login" type="text" value="<?php echo isset($ini['SUPERVISOR']['login'])?$ini['SUPERVISOR']['login']:"";?>" tabindex="1"/></label><br />
							<label>Пароль<br />
							<input name="sv_password" type="password" value="<?php echo isset($ini['SUPERVISOR']['password'])?$ini['SUPERVISOR']['password']:"";?>" tabindex="3"/></label>
						</div>
						<div class="display_block_left posit_text24px">
							<label>E-mail<br />
							<input name="sv_email"  type="text" value="<?php echo isset($ini['SUPERVISOR']['email'])?$ini['SUPERVISOR']['email']:"";?>" tabindex="2"/></label><br />
							<label>Пароль ещё раз<br />
							<input name="sv_password2" type="password" value="<?php echo isset($ini['SUPERVISOR']['password'])?$ini['SUPERVISOR']['password']:"";?>" tabindex="4"/></label>
						</div>
						<div class="info display_none">
							<div class="img_stop">
								<img alt="" src="http://install.umi-cms.ru/ikon_stop.png"  />
							</div>
							<div class="img_stop_text"></div>
						</div>
						<div class="clear"></div>
					</div>
				</div>
				<div class="display_none last_block text_align2 shadow_some step9">
					<div class="padding_big">
						<img alt="" src="http://install.umi-cms.ru/galochka_big.png" />
						<p class="the_end_p">Установка системы завершена</p>
						<p class="the_end_p" style="font-size: 0.9em; margin-bottom: 30px;">В целях безопасности <b>настоятельно рекомендуем</b> удалить файл install.ini из корневой директории сайта</p>
						<div class="next_step_but"><a href="/"><span>Перейти на сайт</span></a></div>
						<div class="clear"></div>
					</div>
				</div>
				<div class="display_none last_block text_align2 shadow_some step10">
					<div class="padding_big">
						<p class="the_end_p">Если вы хотите переустановить систему, удалите файл "installed"<br> из корневой директории сайта и обновите эту страницу.</p>
						<?php
							if (isBackup()) {
								echo '<p class="the_end_p">Если вы делали бэкап (средствами UMI.CMS) <br>и хотите восстановить систему, создайте в корневой<br> директории сайта файл "restore" и обновите эту страницу.</p>';
							}
						?>
						<p class="the_end_p"><b>Внимание! Переустановка или восстановление системы<br />приведут к уничтожению всех имеющихся сейчас<br /> данных вашего сайта. <br />Это действие нельзя будет отменить.</b></p>
						<div class="clear"></div>
					</div>
				</div>
				<div class="display_none last_block text_align_if_error step11">
					<p class="the_end_p"><?php echo $error_content; ?></p>
					<div class="clear"></div>
				</div>
				
				<div class="display_none shadow_some step20">
					<div class="padding_big">
						<div class="loading">
							<p class="vvod_key">Восстановление</p>
							<p class="slider">
								<a href="#" class="wrapper">Показать подробности</a>
							</p>
							<div class="clear"></div>
							<div class="b_input">
								<img alt="" src="http://install.umi-cms.ru/progress_bar_img.gif" class="progressbar_img" />
							</div>
							<div class="progressbar_wrap" style="display:none;">
								<div class="vnutrenniy" class="scroll-pane"></div>
							</div>
						</div>
						<div class="info display_none">
							<div class="img_stop">
								<img alt="" src="http://install.umi-cms.ru/ikon_stop.png"  />
							</div>
							<div class="img_stop_text"></div>
						</div>
					</div>
				</div>
								
				<div class="next_step">
					<input type="button" class="back_step_submit marginr_2px back" value="&laquo;   Назад" />
					<input type="submit" class="back_step_submit marginr_px next" value="Далее   &raquo;" disabled="disabled" />
				</div>

				<div id="install_ad"><script type="text/javascript">show_install_ad();</script></div>

			</div>			
			<div class="footer">
				<div class="load_bottom">
					<p class="step_up">Шаг 1 из 9</p>
					<ul>
						<li class="list_style_noneleft"><div class="color_dif">Проверка <br />подлинности</div></li>
						<li><div>Настройка<br />базы данных</div></li>
						<li><div>Настройка<br />бэкапа</div></li>
						<li><div>Проверка<br />сервера</div></li>
						<li><div>Бэкап<br />системы</div></li>
						<li><div>Установка<br />системы</div></li>
						<li><div>Выбор<br />демосайта</div></li>
						<li><div>Установка<br />демосайта</div></li>
						<li style="width /*\**/:84px\9; *width:84.34px;"><div>Настройка<br />доступа</div></li>
					</ul>
				</div>
			</div>
		</form>
	</body>
</html>
