var stepHeaders = ["Проверка подлинности", "Настройка базы данных", "Способ бэкапа", "Тестирование системы", "Бэкап системы", "Установка системы", "Выбор демосайта", "Установка демосайта", "Настройки суперпользователя", "Всё готово", "UMI.CMS уже установлена"];
var testStep = 0;
var testSteps = "";
var testHeaders = "";
var request_info = {};

jQuery(document).ready(function(){
	jQuery.ajaxSetup({		
		error: function() {
			//console.log("Произошла ошибка во время запроса.");
			jQuery(".b_input").css("display", "none");
			jQuery(".img_stop img").attr("src", "http://install.umi-cms.ru/icon_stop_red.png");
			var text = "Произошла ошибка во время выполнения запроса к серверу.<br/>" +
				"<a href=\"http://errors.umi-cms.ru/15000/\" target=\"_blank\" >" +
				"Подробнее об ошибке 15000</a>";			
			jQuery(".img_stop_text").html(text)
			jQuery(".step" + step + " div.info")
				.css('display', 'none')
				.addClass("error_with_warnings")
				.fadeIn('slow', function() {
					jQuery(this).removeClass("error_with_warnings");
					return false;
				});
			var next = jQuery(".next");
			next.attr({'class':'next_step_submit marginr_px next', 'disabled':false});
			next.val('Повторить');
			jQuery(next).click(function() {
				nextHide();
				jQuery(".b_input").css("display", "block");
				jQuery(".info").css("display","none");				
				sendRequest(request_info['url'], request_info['data'], request_info['callback'], true);
				return false;
			});
		}
	});
	
	if (step<20) {
		showStep();
	}
	else {
		jQuery("div.footer").css('display', 'none');
		jQuery("title").html("Восстановление системы");
		jQuery("p.check_user").html("Восстановление из бэкапа");
		jQuery("div.step20").toggleClass("display_none");
		jQuery("div.step20 a.wrapper").click(function() {
			if (jQuery("div.step20 .progressbar_wrap").css('display')=='none') {
				jQuery(this).html("Скрыть подробности");
			}
			else {
				jQuery(this).html("Показать подробности");
			}
			jQuery("div.step20 .progressbar_wrap").toggle();
			return false;
		});
		var fields={};
		fields['doRestore']='true';
		sendRequest('install.php', jQuery.param(fields), checkRestore, true);		
	}
});

// Обеспечивает отправку запросов
function sendRequest(url, data, callback, now) {
	request_info['url'] = url;
	request_info['data'] = data;
	request_info['callback'] = callback;
	if (now!=true && sleep>0) {
		//console.log("Выполнение запроса с задержкой");
		var val = sleep/1000;
		var s = "Пауза "+ val + "<br/>";
		var details = jQuery(".vnutrenniy");
		details.append(s);
		details.scrollTop(details.get(0).scrollHeight);
		setTimeout("jQuery.post('" + url + "', '" + data + "', " + callback + ")", sleep);
	}
	else {
		//console.log("Выполнение запроса без задержки");
		jQuery.post(url, data, callback);
	}
}

function checkRestore(r) {
	var error = jQuery("error", r);
	
	if (error.length>0) {		
		jQuery(".step20 .b_input").css("display", "none");
		jQuery(".step20 .img_stop img").attr("src", "http://install.umi-cms.ru/icon_stop_red.png");
		jQuery(".step20 .img_stop_text").html(error.attr("message"));		
		jQuery(".step20 .info").toggleClass("display_none");
		var next = jQuery(".next");
		next.attr({'class':'next_step_submit marginr_px next', 'disabled':false});
		next.val('Повторить');
		jQuery(next).click(function() {
			jQuery(".step20 .b_input").css("display", "block");
			jQuery(".step20 .info").toggleClass("display_none");
			jQuery(this).attr({'class':'back_step_submit marginr_px next', 'disabled':true});
			jQuery(this).unbind("click");
			var fields={};
			fields['doRestore']='true';
			sendRequest('install.php', jQuery.param(fields), checkRestore);			
			return false;
		});
		return false;
	}
	
	var s = "";
	jQuery("log message", r).each(function(){ s = s + (jQuery.browser.msie?this.text:this.textContent) + "<br/>"; });
	var details = jQuery(".step20 .vnutrenniy");
	details.append(s);
	details.scrollTop(details.get(0).scrollHeight);
	
	var install = jQuery("install", r);
	
	if (install.attr('state')=='inprogress') {
		var fields={};
		fields['doRestore']='true';		
		sendRequest('install.php', jQuery.param(fields), checkRestore);		
		return false;
	}
	
	if (install.attr('state')=='done') {
		jQuery(".step20").toggleClass("display_none");		
		jQuery(".next_step").toggleClass("display_none");		
		jQuery(".step9 .the_end_p").html("Восстановление системы завершено.");
		jQuery(".step9").toggleClass("display_none");
	}
}

// Анализирует xml ответ
function checkResponse(r) {
	var response = jQuery("response", r);
	if(response.attr('type') == 'ok') {
		nextStep();
	} else {
		var error = jQuery("error", response);
		var text = error.text() + "<br />" +
		"<a href=\"http://errors.umi-cms.ru/" + error.attr('code') + "\" target=\"_blank\" >" +
		"Подробнее об ошибке " + error.attr('code') + "</a>";
		jQuery("div.img_stop img").attr('src', 'http://install.umi-cms.ru/icon_stop_red.png');
		jQuery("div.img_stop_text").html(text);
		jQuery(".step" + step + " div.info")
			.css('display', 'none')
			.addClass("error_with_warnings")
			.fadeIn('slow', function() {
				jQuery(this).removeClass("error_with_warnings");
				return false;
			});
		// Блокируем кнопку далее
		nextHide();
	}
}

// Переключает видимость хода процесса
function toggleWrapper(a) {
	if (jQuery(".progressbar_wrap").css('display')=='none') {
		jQuery(a).html('Скрыть ход установки');
	}
	else {
		jQuery(a).html('Показать ход установки');
	}
	jQuery(".progressbar_wrap").toggle();
	return false;
}

// Выполняет переход на следующий шаг.
function nextStep() {
	jQuery("div.step"+step).css('display', 'none');
	step++;
	showStep();	
}

// Показывает кнопку назад
function showBack() {
	jQuery("input.back")// Показываем кнопку назад
		.attr({'class':'next_step_submit marginr_2px back', disabled:false})
		.unbind('click')
		.click(function() {
			jQuery("div.step"+step).css('display', 'none');
			step--;
			showStep();
			return false;
		});		
}

// Прячет кнопку назад
function backHide() {
	jQuery("input.back")// Блокируем кнопку назад
		.attr({'class':'back_step_submit marginr_2px back', disabled:true})
		.unbind('click');
}

// Прячет кнопку далее
function nextHide() {
	jQuery("input.next")
		.attr({'class':'back_step_submit marginr_px next', disabled:true})
		.unbind('click');
}


// Проверяет дочерние поля в блоке
function checkInputs(selector, type, len) {
	var canSend = true, val;
	jQuery('input', selector).each(function() {
		val = jQuery.trim(jQuery(this).val());
		if (val.length < len && jQuery(this).attr('name')!='password') {
			canSend = false;
		}		
	});
	
	if (!canSend) {
		nextHide();
	} else {
		if (type=='') { // Указания на отправку запроса нет
			jQuery("input.next")
				.attr({'class':'next_step_submit marginr_px next', disabled:false})
				.unbind('click')
				.click(function() { nextHide(); nextStep(); return false; });
		}
		else {
			var requestType = type;
			jQuery("input.next")
				.attr({'class':'next_step_submit marginr_px next', disabled:false})
				.unbind('click')
				.click(function() {
					nextHide();
					var fields = {};
					jQuery('input', selector).each(function() {
						val = jQuery.trim(jQuery(this).val());
						if (val.length < len && jQuery(this).attr('name')!='password') canSend = false;
						else fields[jQuery(this).attr('name')] = val;
					});
					if (canSend) {
						fields[requestType]=true;
						sendRequest('install.php', jQuery.param(fields), checkResponse, true);						
					}
					return false; 
				});
			
			
		}
	}
}

// Проверяет, что значение выбрано.
function checkRadio(name) {
	if (jQuery("input[name='" + name + "']:checked").length==1) {
		jQuery("input.next")
			.attr({'class':'next_step_submit marginr_px next', disabled:false})
			.unbind('click')
			.click(function() { nextHide(); nextStep(); return false; });
	}
	else {
		nextHide();
	}
}

function showDemoSites(r) {
	var sites = jQuery("site", r);	
	var part = Math.ceil(sites.length/2);	
	var parent = jQuery("div.display_page_4");
	jQuery('div.demo_example_left').html('');	
	sites.each(function(key, value) {
		s = '<label><input type="radio" name="demosite" value="' + jQuery(value).attr('name') + '" />' + jQuery('title', value).text() + '</label>';
		s+= '<p>' + jQuery('description', value).text() + '</p>';
		if (part>=key+1) {
			jQuery('div.demo_example_left').first().append(jQuery(s));
		}
		else {
			jQuery('div.demo_example_left').last().append(jQuery(s));
		}
	});
	// Если было предустановленное значение - выбираем его	
	jQuery("input[name='demosite']").each(function() {
		if (this.value==demosite) {
			jQuery(this).attr('checked', true);
		}
		jQuery(this).change(function() { demosite = this.value; return checkRadio("demosite"); });
	});
		
	checkRadio("demosite");
}

function testCallback(r) {
	error = jQuery("error", r);
	if (error.length>0) {
		// Прячем прогресс-бар
		jQuery("div.loading div.b_input").css('display', 'none');
		// Показываем сообщение об ошибке
		jQuery("div.img_stop img").attr('src', 'http://install.umi-cms.ru/icon_stop_red.png');
		jQuery("div.img_stop_text").html(error.attr('message'));
		jQuery("div.info").css('display', 'block');
		// Меняем кнопку на повторить
		jQuery("input.next")
			.val("Повторить")
			.attr({'class':"next_step_submit marginr_px next", disabled: false})
			.unbind("click")
			.click(function() {
				nextHide();
				var fields={};
				fields['step']=testSteps[testStep];
				sendRequest(request_info['url'], request_info['data'], request_info['callback'], true);
				return false;
			});
		return;
	}
	else {
		// Показываем прогресс-бар
		jQuery("div.loading div.b_input").css('display', 'block');
		// Прячем сообщение об ошибке
		jQuery("div.img_stop_text").html('');
		jQuery("div.info").css('display', 'none');
		// Изменяем текст на кнопке
		jQuery("input.next")
			.val("Далее   »")
			.unbind("click");
	}
	var s = "";
	jQuery("log message", r).each(function(){ s = s + (jQuery.browser.msie?this.text:this.textContent) + "<br/>"; });
	var details = jQuery(".vnutrenniy");
	details.append(s);
	details.scrollTop(details.get(0).scrollHeight);
	if (jQuery("install", r).attr('state')=='done') {
		testStep++;
		jQuery(".step3 .vvod_key").html(testHeaders[testStep]);
	}
	if ( testStep >= (testSteps.length) ) {
		nextStep();
		return;
	}
	var fields = {};
	fields['step'] = testSteps[testStep];
	sendRequest('installer.php', jQuery.param(fields), testCallback);	
}

function checkSV(selector) {
	var canSend = true;
	jQuery(selector+" input").each(function() {
		var value = jQuery.trim(jQuery(this).val());
		if (value.length < 1) canSend = false;
		else if (jQuery(this).attr('name')=='sv_email') {
			var expr = /^[-._a-z0-9]+@(?:[a-z0-9][-a-z0-9]+\.)+[a-z]{2,6}$/i;
			if (!expr.test(value)) canSend = false;
		}
	});

	if (canSend) {// Показываем кнопку далее, одинаковость паролей проверяется сервером
		jQuery("div.info:hidden").css('display', 'none');
		jQuery("input.next")
			.attr({'class':"next_step_submit marginr_px next", disabled: false})
			.unbind("click")
			.click(function() {
				var fields = {};
				jQuery("div.step8 input").each(function() {
					fields[jQuery(this).attr('name')] = jQuery.trim(jQuery(this).val());
				});				
				fields['step']='save-settings';
				sendRequest('installer.php', jQuery.param(fields), function(r) {
					error = jQuery("error", r);					
					if (error.length>0) {
						jQuery("div.img_stop img").attr('src', 'http://install.umi-cms.ru/icon_stop_red.png');
						jQuery("div.img_stop_text").html(jQuery(error).attr('message'));
						jQuery("div.info:hidden").css('display', 'block');
						// Блокируем кнопку далее
						nextHide();
					}
					else {
						var fields={};
						fields['step']='configure';
						sendRequest('installer.php', jQuery.param(fields), nextStep);
					}					
				});
				return false;
			});
	}
	else nextHide();
	return false;
}

// Отображает шаг получения триального ключа
function showGetTrialKey() {
	// Меняем заголовок
	var oldTitle = jQuery(".check_user").html();
	jQuery(".check_user").html('Получение бесплатного ключа');
	// Выключаем кнопки вперед-назад
	nextHide();
	backHide();
	// Очищаем все поля
	jQuery("#email_trial").add("#lname_trial").add("#fname_trial").add("#code_trial")
		.val("")
		.unbind();
	// Перегружаем каптчу
	jQuery("#getTrialKey img")
		.css({"border":"darkgrey solid 1px"})
		.attr({"src":"install.php?getCodeImage="+Math.round(Math.random()*10000000)});
	// Вешаем обработчик изменение поля email
	jQuery("#email_trial").add("#code_trial")
		.bind('change keyup', function() {
			// Проверка на валидность полей
			if (checkTrialFormField()) {
				// Разрешаем кнопку далее
				jQuery("input.next")
					.attr('disabled', false)
					.removeClass('back_step_submit')
					.addClass('next_step_submit')
					.unbind()
					.click(function() {
						jQuery("input.next")
							.attr('disabled', true)
							.removeClass('next_step_submit')
							.addClass('back_step_submit')
							.unbind();
						checkTrialForm();
						return false;
					});
			} else { // Поля заполнены не правильно.
				jQuery("input.next")
					.attr('disabled', true)
					.removeClass('next_step_submit')
					.addClass('back_step_submit')
					.unbind('click');
			}
		});
	
	// Показываем кнопку назад
	jQuery("input.back")
		.attr('disabled', false)
		.removeClass('back_step_submit')
		.addClass('next_step_submit')
		.bind("click", function(){
			jQuery(".check_user").html(oldTitle);
			nextHide();
			backHide();
			jQuery("#getTrialKey")
				.toggle("display_none");
			jQuery(".step0")
				.fadeIn();
			return false;
		});

	// Показываем форму получения ключа
	jQuery(".step0").toggle("display_none");
	jQuery("#getTrialKey").fadeIn();
	return false;
}

// Проверяет каптчу и получает ключ
function checkTrialForm() {
	// На всякий случай ещё раз проверим поля формы
	if (checkTrialFormField()) {
		// Проверяем каптчу
		url = "install.php?checkCode=true&captcha=" + jQuery("#code_trial").val().replace(/^\s+|\s+$/g, '');
		jQuery.get(url, {}, function(data) {
			if (jQuery('result', data).text()==='false') {
				// Неверно введена каптча
				jQuery("#getTrialKey img")
					.css({"border":"red solid 1px"})
					.attr({"src":"install.php?getCodeImage="+Math.round(Math.random()*10000000)});
				jQuery("#code_trial").val("");
				return false;
			}
			else {
				var fields = {};
				fields['getTrialKey']='true';
				fields['email'] = jQuery("#email_trial").val();
				fields['lname'] = jQuery("#lname_trial").val();
				fields['fname'] = jQuery("#fname_trial").val();
				jQuery.get("install.php", jQuery.param(fields), function(data) {
					jQuery(".step0 input[name='key']").val(jQuery("keycode", data).attr('domain-keycode'));
					jQuery(".back").click();
					checkInputs("div.step0", "check-license", 35);
					return false;
					});
				return false;
			}
		});
	}
	return false;
}

// Проверяет правильность заполнения формы для получения триального ключа
function checkTrialFormField() {
	var checkResult = true;
	jQuery("#email_trial").add("#code_trial").each(function() {
		if (!checkResult) return;
		var field = jQuery(this).clone();
		jQuery(field).val(jQuery(field).val().replace(/^\s+|\s+$/g, ''));
		switch(field.attr('name')) {
			case 'email_trial': {
				if (!(/^([a-z0-9_\-]+\.)*[a-z0-9_\-]+@([a-z0-9][a-z0-9\-]*[a-z0-9]\.)+[a-z]{2,6}$/i).test(field.val())) {
					checkResult = false;
				}
				break;
			}
			case 'code_trial': {
				if (!(/^[a-z0-9]{4,10}$/i).test(field.val())) {
					checkResult = false;
				}
				break;
			}
		}
	});
	return checkResult;
}

// Показывет очередной шаг установки
function showStep() {
	// Прячем сообщение об ошибке
	if (step!=0) {
		jQuery("div.info").css('display', 'none');
	}
	else {
		jQuery("div.img_stop img").attr("src", "http://install.umi-cms.ru/ikon_stop.png");
		jQuery("div.img_stop_text").html("<a href=\"http://www.umi-cms.ru/buy_now/licence_agreement/licence_agreement/\" target=\"_blank\">Прочитать лицензионное соглашение</a> &nbsp;&#124;&nbsp; <a id=\"getTrialLink\" href=\"http://www.umi-cms.ru/buy/free_license/?licence=trial\" target=\"_blank\">Получить бесплатный ключ</a>");
		jQuery("div.info").css('display', 'block');
		// Получение бесплатного ключа
		jQuery("#getTrialLink").click(function() {
			return showGetTrialKey();
		});
	}
	
	jQuery("p.check_user").html(stepHeaders[step]);	
	jQuery("div.step"+step).css('display', 'block');
	var li = jQuery("div.load_bottom li");
	if (step>=li.length) {
		jQuery("div.footer").css("display", "none");
		jQuery("div.next_step").css('display', 'none');
		return false;
	}
	
	switch(step) {
		case 0: { // Проверка лицензионного ключа
			checkInputs("div.step0", "check-license", 35);// Проверяем, может ключ уже введен
			jQuery("div.step0 input").bind("keyup blur", function() { return checkInputs("div.step0", "check-license", 35); });// В случае изменения ключа			
			backHide();
		}
		break;
		case 1: { // Настройка подключения к базе данных
			checkInputs("div.step1", "check-mysql", 1); // Проверяем, возможно параметры уже заданы
			jQuery("div.step1 input").bind("keyup blur", function() { return checkInputs("div.step1", "check-mysql", 1); });// Изменение в настройках подключения к базе		
			showBack();
		}
		break;
		case 2: { // Настройки бекапирования
			checkRadio("backup"); // Проверяем, возможно значение уже установлено
			jQuery("input[name='backup']").change(function() { return checkRadio("backup"); });
			showBack();
		}
		break;
		case 3: { // Выполняем тестирование
			backHide();
			testSteps = ["save-settings", "download-service-package", 'extract-service-package', 'write-initial-configuration', 'run-tests'];
			testHeaders = ["Сохранение настроек", "Загрузка тестов", 'Распаковка тестов', 'Запись начальной конфигурации', 'Выполнение тестирования'];
			// Показать ход процесса
			jQuery(".wrapper").click(function() { return toggleWrapper(this); });
			// Перед тестированием сохраняем данные
			fields = {};
			fields['license_key'] = jQuery.trim(jQuery("input[name='key']").val());
			fields['db_host'] = jQuery.trim(jQuery("input[name='host']").val());
			fields['db_login'] = jQuery.trim(jQuery("input[name='user']").val());
			fields['db_password'] = jQuery.trim(jQuery("input[name='password']").val());
			fields['db_name'] = jQuery.trim(jQuery("input[name='dbname']").val());
			fields['backup_mode'] = jQuery.trim(jQuery("input[name='backup']:checked").val());
			jQuery(".step3 .vvod_key").html(testHeaders[testStep]);
			fields['step']='save-settings';
			sendRequest('installer.php', jQuery.param(fields), testCallback, true);
		}
		break;
		case 4: {
			backHide();
			testStep=0;
			testSteps = ["backup-files", "backup-mysql"];
			testHeaders = ["Бэкап системы", "Бэкап базы данных"];
			jQuery(".step4 .vvod_key").html(testHeaders[testStep]);
			var fields = {};
			fields['step']=testSteps[testStep];
			sendRequest('installer.php', jQuery.param(fields), testCallback);			
		}
		break;
		case 5: {
			backHide();
			testStep=0;
			testSteps = ["get-update-instructions", "download-components", "extract-components", "check-components", "update-database", "install-components", "set-default-domain"];
			testHeaders = ["Получение инструкций", "Загрузка компонентов", "Распаковка компонентов", "Проверка компонентов", "Обновление базы данных", "Установка компонентов", "Установка домена"];
			jQuery(".step5 .vvod_key").html(testHeaders[testStep]);
			var fields = {};
			fields['step']=testSteps[testStep];
			sendRequest('installer.php', jQuery.param(fields), testCallback);			
		}
		break;
		case 6: { // Получаем список демосайтов
			var fields = {};
			fields['step']='get-demosite-list';
			sendRequest('installer.php', jQuery.param(fields), showDemoSites, true);			
		}
		break;
		case 7: { // install demosite
			nextHide();
			testStep=0;
			testSteps = ["download-demosite", "extract-demosite", "check-demosite", "install-demosite"];
			testHeaders = ["Загрузка демосайта", "Распаковка демосайта", "Проверка демосайта", "Установка демосайта"];			
			jQuery(".step7 .vvod_key").html(testHeaders[testStep]);
			// Сохраняем выбранный демосайт
			var fields={};
			fields['step']='save-settings';
			fields['demosite']=demosite;
			sendRequest('installer.php', jQuery.param(fields), function() {
				nextHide();
				var fields={};
				fields['step']=testSteps[testStep];
				sendRequest('installer.php', jQuery.param(fields), testCallback);				
			});
		}
		case 8: { // Настройки SV			
			backHide();
			checkSV("div.step8");			
			jQuery("div.step8 input").bind("keyup blur", function() { return checkSV("div.step8"); } );
		}
		break;
	}
	
	jQuery("p.step_up").html('Шаг ' + (step+1) + ' из ' + (stepHeaders.length-2));
	// Сбрасываем у всех на классы по умолчанию
	jQuery(li).each(function() {
		jQuery(this).add(jQuery("div", this)).attr('class', '');
	});
	// И устанавливаем снова у пройденных
	for (i=0; i<=step; i++) {
		switch(i) {
			case 0:	jQuery(li[i]).attr('class', 'list_style_noneleft'); break;			
			case 8: jQuery(li[i]).attr('class', 'list_style_noneright'); break;
			default: jQuery(li[i]).attr('class', 'list_style_nonetwo'); break;
		}
		jQuery('div', li[i]).attr('class', 'color_dif');
	}
}
