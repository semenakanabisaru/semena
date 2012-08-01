<?php
	header("Cache-Control: no-store, no-cache, must-revalidate");	// HTTP/1.1
	header("Cache-Control: post-check=0, pre-check=0", false);	// HTTP/1.1
	header("Pragma: no-cache");	// HTTP/1.0
	header("Date: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Expires: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("X-XSS-Protection: 0"); //Disable new IE8 XSS filter
	header("Content-type: text/html; charset=utf-8");
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />
<html>
	<head>
		<title>Тестирование хостинга для UMI.CMS</title>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<link type="text/css" rel="stylesheet" href="/smt/client/css/style.css" />
		<script type="text/javascript" src="/smt/client/prototype.js"></script>
		<script type="text/javascript" src="/smt/client/i18n.js"></script>
		<script type="text/javascript" src="/smt/client/clientTest.js"></script>

		<script type="text/javascript">
			var Test = new clientTest();

			function selectStep(activeTab, label) {
				if (!activeTab || activeTab.className == 'act') return;
				var tabs = $('left');
				for (var i = 0; i < tabs.childNodes.length; i++) {
					var tab = tabs.childNodes[i];
					tab.className = '';
				}
				$('step_header_namber').innerHTML = activeTab.innerHTML;
				$('step_header').style.display = 'inline';

				$('step_header_text').innerHTML = label;
				activeTab.className = 'act';
			}

			Test.addEventHandler("onWait", function() {
				$('container').update('<div id="progres_bar"><img src="/smt/client/img/progres_speed.gif" alt="" /></div>');
			});

			Test.addEventHandler("onConnectionError", function(errcode) {
				var msg = 'answer-error-' + errcode;
				var usr_msg = getLabel(msg);

				var html = '<h2 id="error_header">' + getLabel('answer-error-lbl') + ' #' + errcode + '</h2><p>' + usr_msg + '</p>';
				html += '<div class="button" onclick="Test.needWait = true; Test.run();"><span class="l"></span><span class="c">' + getLabel('lbl-repeat') + '</span><span class="r"></span></div>';

				$('container').update(html);

			});

			Test.addEventHandler("onLoadComplete", function(stepName) {
				selectStep($(stepName), getLabel(stepName));
			});

			Test.addEventHandler("onPrepareStep", function(stepName) {
				selectStep($(stepName), getLabel(stepName));
			});


			Test.addEventHandler("onServerException", function(error) {
				var c = $('container');
				var code = error.getAttribute('code');
				var sys_msg = error.firstChild.nodeValue;
				var lbl = getLabel('error-' + code);
				var usr_msg = (lbl == 'error-' + code) ? sys_msg : lbl;
				var html = '<h2 id="error_header">' + getLabel('error') + '</h2><p>' + usr_msg + '</p>';
				var nl_trace = error.getElementsByTagName('trace');
				if (nl_trace.length) {
					html += '<p>Следующая информация может быть полезна для решения проблемы:</p>';
					html += '<pre>' + nl_trace[0].firstChild.nodeValue + '</pre>';
				}

				html += '<div class="button" onclick="Test.needWait = true; Test.run();"><span class="l"></span><span class="c">' + getLabel('lbl-repeat') + '</span><span class="r"></span></div>';

				c.update(html);
			});

			Test.addEventHandler("onTestException", function(error) {
				var c = $('container');
				var code = error.getAttribute('code');
				var sys_msg = error.firstChild.nodeValue;
				var lbl = getLabel('error-' + code);
				var usr_msg = (lbl == 'error-' + code) ? sys_msg : lbl;
				var html = '<h2 id="error_header">' + getLabel('error') + ' #' + code + '</h2><p>' + usr_msg + '</p>';
				var nl_trace = error.getElementsByTagName('trace');
				if (nl_trace.length) {
					html += '<p>Следующая информация может быть полезна для решения проблемы:</p>';
					html += '<pre>' + nl_trace[0].firstChild.nodeValue + '</pre>';
				}

				html += '<div class="button" onclick="Test.needWait = true; Test.repeat();"><span class="l"></span><span class="c">' + getLabel('lbl-repeat') + '</span><span class="r"></span></div>';

				c.update(html);
			});

			Test.addEventHandler("onResponseFailed", function(response) {
				var c = $('container');
				var html = '<h2 id="error_header">' + getLabel('error') + ' #001</h2><p>' + getLabel('error-response-failed') + '</p>';
				c.update(html);
				var pre = document.createElement('pre');
				pre.appendChild(document.createTextNode(response.responseText));
				c.appendChild(pre);
			});


			Test.addEventHandler("onUpdateFinish", function(response) {
				var nl_license = response.getElementsByTagName('license');
				if (nl_license.length) {
					var license = nl_license[0];
					var html = '<p><strong>' + getLabel('lbl-update-success') + ' ' + license.getAttribute('version') + ' (' + getLabel('lbl-build').toLowerCase() + ': ' + license.getAttribute('build') + ')</strong></p>';


					html += '<div class="button" onclick="document.location.href = \'/\'"><span class="l"></span><span class="c">' + getLabel('lbl-tosite') + '</span><span class="r"></span></div>';

					$('container').update(html);
				}
			});


			function changeTestMode(el) {
				var mode = el.id;
				
				$('test-btn').show();
			}

			function runTest() {
				var params = "?step=test-results&test-mode=install&db-host=" + $F('dbHost') +
							'&db-user=' + $F('dbUser') + '&db-password=' + $F('dbPassword') +
							'&db-name=' + $F('dbName');
				Test.run(params);
			}

		</script>


	</head>
	<body>
		<div id="page">
			<div id="left">
				<div id="test-mode" class="act">1</div>
				<div id="test-results">2</div>
				<div id="test-change-key">3</div>
				<div id="test-complete">4</div>
			</div>
			<div id="content">
				<h1>Тестирование хостинга для <span class="umi">UMI.CMS</span>/ <span class="step" id="step_header">Шаг <span id="step_header_namber">1</span><span class="def"> - </span></span><span id="step_header_text">Выбор режима тестирования</span></h1>
				<div id="container">
					<p>Выберите режим тестирования:</p>
					<form action="">
						<div>
							<input onchange="changeTestMode(this)" type="radio" name="test-mode" id="install-mode" value="install" /><label for="install-mode">возможность установки UMI.CMS</label>
						</div>
						<div>
							<input onchange="changeTestMode(this)" type="radio" name="test-mode" id="migration-mode" value="migration" /><label for="migration-mode">возможность переноса UMI.CMS с другого хостинга</label>
						</div>
						
						<div id="test-mode-message"></div>
						<div id="test-db-params">
							<div id="dbConfig" class="form">
								<p>
									<label for="dbHost">Хост:</label><input type="text" name="db-host" id="dbHost" />
								</p>
								<p>
									<label for="dbUser">Логин:</label><input type="text" name="db-user" id="dbUser" />
								</p>
								<p>
									<label for="dbPassword">Пароль:</label><input type="text" name="db-password" id="dbPassword" />
								</p>
								<p>
									<label for="dbName">Имя БД:</label><input type="text" name="db-name" id="dbName" />
								</p>
							</div>
						</div>
							
						<div id="test-btn" style="display:none" class="button" onclick="runTest()"><span class="l"></span><span class="c">Тестировать</span><span class="r"></span></div>
					</form>
				</div>
			</div>
			<div class="clear"></div>
		</div>

		<div id="win_pop_up">
			<h2 id="win_pop_up_header"></h2>
			<div id="win_pop_up_text">

			</div>
			<div id="win_pop_up_button" class="button" onclick="this.parentNode.style.display = 'none'; return false;">
				<span class="r"></span>
				<span class="c">закрыть</span>
				<span class="l"></span>
			</div>
		</div>
	</body>
</html>