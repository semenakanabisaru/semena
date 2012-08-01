<?php

	require_once("../libs/config.php");
	$regedit = regedit::getInstance();

	header("Content-type: text/javascript; charset=utf-8");

	if ($regedit->checkSelfKeycode()) {
		echo 'jQuery(document).ready(function() {
	jQuery("#licenseButton")
		.add("#license_msg")
		.add("div.b_input")
		.add("p.vvod_key")
		.remove();
	jQuery("p.check_user").html("Проверка лицензионного ключа");
	jQuery("p.the_end_p").html("Произошла ошибка при проверке лицензионного ключа.<br/>Если Вы являетесь владельцем сайта, обратитесь, пожалуйста, в службу заботы о клиентах.");
	jQuery("div.upgrading_system a").attr("href", "http://errors.umi-cms.ru/16004/");
})';
		exit();
	}
?>

jQuery.ajaxSetup({
	error: function() {
		document.getElementById('license_msg').innerHTML = "Произошла ошибка при обращению к серверу.<br/>Попробуйте повторить попытку или обратиться в <a href=\"http://www.umi-cms.ru/support/\" target=\"_blank\">Службу Заботы</a> UMI.CMS.";
		document.getElementById('licenseButton').disabled = false;
		return false;
	}
});

function checkSystem() {

	document.getElementById('more_info').style.display='none';
	var keycode = document.getElementById('keycode').value;
	if (keycode.length==0) {
		document.getElementById('license_msg').innerHTML = "Ошибка: лицензионный ключ не указан.";
		return false;
	}
	document.getElementById('license_msg').innerHTML = "Проверка лицензионного ключа... Пожалуйста, подождите.";
	document.getElementById('licenseButton').disabled = true;

	jQuery.get('/errors/save_domain_keycode.php', {'keycode':keycode}, function(response) {
		var errors = jQuery('error', response);
		if ( errors.length > 0 ) {
			document.getElementById('license_msg').innerHTML = "Ошибка: " + errors[0].textContent;
			document.getElementById('licenseButton').disabled = false;
			return false;
		}

		var result = jQuery('result', response);
		if ( result[0].textContent != 'true' ) {
			document.getElementById('license_msg').innerHTML = "Ошибка: некорректный ответ сервера. Попробуйте повторить попытку.";
			document.getElementById('licenseButton').disabled = false;
			return false;
		}

		document.getElementById('license_msg').innerHTML = "Загрузка пакета тестирования...";
		jQuery.get('/errors/save_domain_keycode.php', {'do':'load'}, function (response) {
			var errors = jQuery('error', response);
			if ( errors.length > 0 ) {
				var text = 'Ошибка: ';
				text += errors[0].textContent;
				text += '<br/><br/>Попробуйте повторить попытку или обратиться в <a href=\"http://www.umi-cms.ru/support/\" target=\"_blank\">Службу Заботы</a> UMI.CMS.';
				document.getElementById('license_msg').innerHTML = text;
				document.getElementById('licenseButton').disabled = false;
				return false;
			}

			document.getElementById('license_msg').innerHTML = "Выполняется тестирование...";
			jQuery.get('/errors/save_domain_keycode.php', {'do':'test'}, function (response) {
				// Смотрим, были ли критичные ошибки
				var critical = jQuery('error[critical=1]', response);
				if ( critical.length > 0 ) {
					var text = '<b>Критическое нарушение системных требований:</b> <br/><br/>';
					jQuery.each(critical, function(num, error) {
						text += error.textContent;
						text += ' Информация об ошибке <a href="http://errors.umi-cms.ru/' + error.getAttribute('code') + '/" target="_blank">' + error.getAttribute('code') + '</a><br/>';
					});
					document.getElementById('license_msg').innerHTML = text;
					return false;
				}
				else {
					document.getElementById('license_msg').innerHTML = 'Активация лицензии...';
					checkLicenseCode();
					document.getElementById('licenseButton').disabled = false;
				}
			});

		});
		
	});
	
}

function requestsController() {
	requestsController.self = this;
}

requestsController.prototype.requests = new Array();


requestsController.getSelf = function () {
	if(!requestsController.self) {
		requestsController.self = new requestsController();
	}
	return requestsController.self;
};



requestsController.prototype.sendRequest = function (url, handler) {
	var requestId = this.requests.length;
	this.requests[requestId] = handler;

	var url = url;
	var scriptObj = document.createElement("script");
	scriptObj.src = url + "&requestId=" + requestId;
	document.body.appendChild(scriptObj);
};

requestsController.prototype.reportRequest = function (requestId, args) {
	this.requests[requestId](args);
	this.requests[requestId] = undefined;
}


function checkLicenseCode(frm) {
	var keycodeInput = document.getElementById('keycode');
	var keycode = keycodeInput.value;

	var ip = "<?php echo isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : str_replace("\\", "", $_SERVER['DOCUMENT_ROOT']); ?>";
	var domain = "<?php echo $_SERVER['HTTP_HOST']; ?>";

	var url = "http://umi-cms-2.umi-cms.ru/updatesrv/initInstallation/?keycode=" + keycode + "&domain=" + domain + "&ip=" + ip;

	var handler = function (response) {
		if(response['status'] == "OK") {
			document.getElementById('license_msg').style.color = "green";

			var res = "Лицензия \"" + response['license_type'] + "\" активирована.<br />Владелец " + response['last_name'] + " " + response['first_name'] + " " + response['second_name'] + " (" + response['email'] + ")<br />";
			var domain_keycode = response['domain_keycode'];

			document.getElementById('licenseButton').value = "Ok >>";

			document.getElementById('licenseButton').onclick = function () {
				window.location = "/";
			}

			document.getElementById('license_msg').innerHTML = res;

			var url = "/errors/save_domain_keycode.php?domain_keycode=" + domain_keycode + "&domain=" + domain + "&ip=" + ip + "&license_codename=" + response['license_codename'];
			requestsController.getSelf().sendRequest(url, function () {});
		} else {
			document.getElementById('license_msg').innerHTML = "Ошибка: " + response['msg'];
		}
	};

	requestsController.getSelf().sendRequest(url, handler);
}