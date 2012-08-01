	


	/*				общие функции

	 *************************************************/

	// создать XMLHttp запрос
	function createXMLHttpRequest() {
		var request = false;
		try {
			// нормальные браузеры
			request = new XMLHttpRequest(); 
		} catch (e) {
			try {
				// некоторые версии IE
				request = new ActiveXObject("MsXML2.XMLHTTP");
			} catch (e) {
				try {
					// другие версии IE
					request = new ActiveXObject("Microsoft.XMLHTTP");
				} catch (e) {
					request = false;
				}
			}
		}	
		return request;
	}


	// находит все текстовые поля
	// и проставляет им дефолтные значения собранные 
	// в setTextfieldsHandlers()
	function setTextfielsDefaults() {
		var i = 0;
		$("textarea[clean], input[clean]").each(function() {
			$(this).val(textfields_defaults[i]);
			i++;
		});
	}
	
	var textfields_defaults = new Array;  	
	// установить обработчики для текстовых элементов формы (дефолтовое значение)
	function setTextfieldsHandlers() {
		var i = 0;

		$("textarea[clean], input[clean]").each(function() {
			var default_value = this.value;
			textfields_defaults[i] = default_value;	
			i++;			
			$(this).focus(function() {
				if(this.value == default_value) {
					this.value = '';
				}
			});
			$(this).blur(function() {
				if(this.value == '') {
					this.value = default_value;
				}
			});
		});

	}

	/*				специальные функции
	 *************************************************/


	 							//---------------------------------------//
								// 		запрос на удаление  аккаунта     //
								//---------------------------------------//

	function sendRequestForAccDeletion() {
		var name=prompt("Введите пароль");

		if (name!=null && name!='') {
			$(".personal_delete .preloader").html("<img src='/templates/webeffection/images/delete_cart_preloader.gif' />");
		  	var request = createXMLHttpRequest();
		  	request.open('GET', '/udata/custom/sendRequestForAccDeletion/?oldpass='+name, true);
			request.onreadystatechange = function(){
				if (request.readyState == 4) {
					if (request.status == 200) {
						var response = $(request.responseXML).find('udata').text();
						console.log(response);
						if ( response == 'good' )	{
							$(".personal_delete .preloader").addClass('good').html("запрос отправлен");
						} else {
							sendRequestForAccDeletion();
							//$(".personal_delete .preloader").addClass('bad').html("запрос неудался");
							
						}
						setTimeout(function() {
							$(".personal_delete .preloader").html("");
							$(".personal_delete .preloader").removeClass('bad').removeClass('good');
						}, 4000);	
					}
				}
			};
			request.send(null);
		} 
	}
	// function sendRequestForAccDeletion() {
	// 	var name=prompt("Введите пароль");

	// 	if (name("Вы действительно хотите удалить аккаунт?")) {
	// 		$(".personal_delete .preloader").html("<img src='/templates/webeffection/images/delete_cart_preloader.gif' />");
	// 	  	var request = createXMLHttpRequest();
	// 	  	request.open('GET', '/udata/custom/sendRequestForAccDeletion/?oldpass'+pass, true);
	// 		request.onreadystatechange = function(){
	// 			if (request.readyState == 4) {
	// 				if (request.status == 200) {
	// 					var response = $(request.responseXML).find('udata').text();
	// 					if ( response == 'good' )	{
	// 						$(".personal_delete .preloader").addClass('good').html("запрос отправлен");
	// 					} else {
	// 						$(".personal_delete .preloader").addClass('bad').html("запрос неудался");
							
	// 					}
	// 					setTimeout(function() {
	// 						$(".personal_delete .preloader").html("");
	// 						$(".personal_delete .preloader").removeClass('bad').removeClass('good');
	// 					}, 4000);	
	// 				}
	// 			}
	// 		};
	// 		request.send(null);
	// 	} 
	// }

	 							//-----------------------------//
								// 		форматирует валюту     //
								//-----------------------------//

	function number_format (number, decimals, dec_point, thousands_sep) {
	    // Formats a number with grouped thousands  
	    number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
	    var n = !isFinite(+number) ? 0 : +number,
	        prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
	        sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
	        dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
	        s = '',
	        toFixedFix = function (n, prec) {
	            var k = Math.pow(10, prec);
	            return '' + Math.round(n * k) / k;
	        };
	    // Fix for IE parseFloat(0.55).toFixed(0) = 0;
	    s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
	    if (s[0].length > 3) {
	        s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
	    }
	    if ((s[1] || '').length < prec) {
	        s[1] = s[1] || '';
	        s[1] += new Array(prec - s[1].length + 1).join('0');
	    }
	    return s.join(dec);
	}
	
								//-----------------------------//
								// 		работа с куками        //
								//-----------------------------//

	function setCookie(name, value, expires, path, domain, secure) {
		if (!name || !value) return false;
		var str = name + '=' + encodeURIComponent(value);
	
		if (expires) str += '; expires=' + expires.toGMTString();
		if (path)    str += '; path=' + path;
		if (domain)  str += '; domain=' + domain;
		if (secure)  str += '; secure';
		
		document.cookie = str;
		return true;
	}

	function getCookie(name) {
		var pattern = "(?:; )?" + name + "=([^;]*);?";
		var regexp  = new RegExp(pattern);
		
		if (regexp.test(document.cookie))
		return decodeURIComponent(RegExp["$1"]);
		
		return false;
	}

								//-----------------------------------------------------//
								// 		сохранение значений форм после отправки        //
								//-----------------------------------------------------//

	function myRestoreFormData(form) {
		if(!form) {
			return false;
		}
		
		if(!form.id) {
			alert("You should set id attribute in form tag to save or restore it.");
			return false;
		}
		var cookieName = "frm" + form.id + "=";
		
		var cookie = new String(unescape(document.cookie));
		var posStart, posEnd;
		if((posStart = cookie.indexOf(cookieName)) == -1) {
			return false;
		}
		
		if((posEnd = cookie.indexOf(";", posStart)) == -1) {
			posEnd = cookie.length;
		}
		
		var data = cookie.substring(posStart + cookieName.length, posEnd);
		var pos = 0, cookieData = new Array;

		while(pos < data.length) {
			var inputName;
			var type = data.substring(pos, pos + 1);
			pos += 2;
			
			var length = parseInt(data.substring(pos, data.indexOf(",", pos)));
			pos = data.indexOf(",", pos) + 1;
			var inputName = data.substring(pos, pos + length);
			pos += length + 1;

			var length = parseInt(data.substring(pos, data.indexOf(",", pos)));
			if(length == 0) {
				pos += 2;
				continue;
			} else {
				pos = data.indexOf(",", pos) + 1;
			}
			
			var value = data.substring(pos, pos + length);
			pos += length;
			
			cookieData.push({type: type, name: inputName, value: value});
		}
		
		for(var i = 0; i < cookieData.length; i++) {
			var elementData = cookieData[i];
			if (elementData.type && elementData.name && form.elements[elementData.name]) {
				switch(elementData.type) {
					case "T": {
						form.elements[elementData.name].value = elementData.value;
						break;
					}
					
					case "C": {
						form.elements[elementData.name].checked = elementData.value ? true : false;
						break;
					}
					
					case "S": {
						form.elements[elementData.name].selectedIndex = elementData.value;
						break;
					}
				}
			}
		}
	}

	function mySaveFormData(form) {
		if(!form) {
			return false;
		}
		
		if(!form.id) {
			alert("You should set id attribute in form tag to save or restore it.");
			return false;
		}

		var cookieData = new Array;	
		for(var i = 0; i < form.elements.length; i++) {
			var input = form.elements[i];
			if (input.name) {
				var inputName = input.name.replace(/([)\\])/g, "\\$1");

				switch(input.type) {
					case "file": {
						if (input.value == '') input.parentNode.removeChild(input);
						break;
					}
					case "password":
					
					case "text":
					case "textarea": {
						cookieData.push({type: 'T', name: inputName, value: input.value});
						break;
					}
					
					case "checkbox":
					case "radio": {
						cookieData.push({type: 'C', name: inputName, value: (input.checked ? 1 : 0)});
						break;
					}
					
					case "select-multiple":
					case "select-one": {
						cookieData.push({type: 'S', name: inputName, value: input.selectedIndex});
						break;
					}
				}
			}

		}
		
		var str = "";
		for(var i = 0; i < cookieData.length; i++) {
			var elementData = cookieData[i];
			var value = new String(elementData.value);
			var inputName = new String(elementData.name);
		
			if(!inputName || !value) {
				continue;
			}
			
			str += elementData.type + "," + inputName.length + "," + inputName + "," + value.length + "," + value;
		}
		document.cookie="frm" + form.id + "=" + escape(str.replace(/([|\\])/g, "\\$1"));
		return true;
	}

	// удаляет куку для формы
	function truncateFormData(name) {
		setCookie('frm' + name, 1, new Date(0));
	}


								//------------------------------//
								// 		обновление капчи        //
								//------------------------------//

	// обновляет капчу
	function updateCaptcha() {
		var captcha = $("#captcha");
		var sourse = $(captcha).attr('srcfirst');
		var rnd = Math.round((Math.random()*999999999));
		$(captcha).attr('src', sourse + "&rnd=" + rnd);
	}


								//------------------------------------------------------------------//
								// 		скрипт изменения числа выводимых на страницы товаров        //
								//------------------------------------------------------------------//

	// изменяет страницу при изменении значения per_page
	function perPageChanged(select_object) {
		var current = document.location;
		current = deleteGetParams(current, ['p', 'per_page']);
		current += '&per_page=' + select_object.options[select_object.selectedIndex].value;
		document.location = current;
	}

	// удаляет из адресной строки указанные параметры
	function deleteGetParams(url, params) {
		// получаем основной путь
		url = url.toString();
		var pos = url.indexOf('?');
		var path = url.substring(0, pos) + "?";
		// разбиваем адресную строку на пары значений
		var pairs = new Object;
		var matches;
		var search = /[\?&]{1}([^\?&=]+)=([^\?&=]+)/ig;
		while (matches = search.exec(url)) {
			pairs[matches[1]] = matches[2];
		}
		// если параметры есть, удаляем
		for ( key in params ) {
			if ( params[key] in pairs ) { delete pairs[params[key]]; }
		}
		// формируем новый url
		for ( prop in pairs ) {
			path += "&" + prop + "=" + pairs[prop];
		}
		return path;
	}



								//------------------------------------------//
								// 		крутит все карусели на сайте        //
								//------------------------------------------//

	// крутит карусель 
	function rollCarousel(carousel, direction, visible, step_length, steps_count) {

		var roll_right = $(carousel).find('.roll.right');
		var roll_left = $(carousel).find('.roll.left');
		var scroll_me = $(carousel).find('.cut_box ul');

		var max_scrolls = $(scroll_me).find('li').length - visible;
		if ( max_scrolls <= 0) { return; }
		
		var scrolled = Math.round(Math.abs(parseInt($(scroll_me).css('margin-left'))/step_length)); 

		if ( direction == 'right' ) {
			$(roll_left).removeClass('disabled'); 
			var do_steps = scrolled + steps_count;
			if ( do_steps >= max_scrolls ) { 
				$(roll_right).addClass('disabled');
				do_steps = max_scrolls; 
			}
		} else {
			$(roll_right).removeClass('disabled');
			var do_steps = scrolled - steps_count;
			if ( do_steps <= 0 ) {
				$(roll_left).addClass('disabled'); 
				do_steps = 0;
			}
		}

		var do_margin = -step_length*do_steps;
		$(scroll_me).animate({'margin-left': do_margin});

	}


								//------------------------------------------------------------------//
								// 		хранит состояние бокового меню от страницы к странице       //
								//------------------------------------------------------------------//

	// записывает состояние расширенного поиска 
	// (что свернуто, что развернуто)\
	var acstate_active = getCookie('acstate_active');
	acstate_active = parseInt(acstate_active); 
	if ( isNaN(acstate_active) ) { acstate_active = false; } 

	function saveAccordionState() {
		var active = false;
		var opened = new Array;
		// состояние самого аккордиона
		$("#sidebar_menu .body").each(function() {
			if ( $(this).css('display') == 'block' ) { 
				active = $(this).index('.body'); 
			}
		});
		// состояние областей расширенного поиска
		$("#sidebar_menu .scope.opened").each(function() {
			opened.push($(this).index());
		});
		if ( opened.length == 0 ) {  // чтобы сбросить старое значение, если все закрыто
			opened.push(-1); 
		}
		// сохраняем состояние
		setCookie('acstate_active', active + '', 0, '/');
		setCookie('acstate_opened', opened.join('|'), 0, '/');
	}

	// восстанавливает состояние расширенного поиска 
	// (что свернуто, что развернуто)
	function restoreAccordionState() {
		var opened = getCookie('acstate_opened');
		if ( opened !== false ) {
			indexes = opened.split('|');
			for ( key in indexes ) {
				$("#sidebar_menu .scope").eq(indexes[key]).addClass('opened');
			}
		}
	}


								//---------------------------------------------------//
								// 		функции для работы расширенного поиска       //
								//---------------------------------------------------//

	// сохраняет в куки значения фильтров с множественным выбором 
	// для того, чтобы иметь возможность восстановить значения фильтров
	function saveSelectableValues(filter_id, value) {

		var insert_case = true; // по умолчанию добавляем
		var values_string = getCookie(filter_id) || '';
		var values = values_string.split('|');

		var values_new = new Array;
		for ( key in values ) {
			if ( values[key] == value ) {
				delete values[key];
				insert_case = false;
			}
			if ( values[key] !== undefined && values[key] != '' ) {
				values_new.push(values[key]);
			}
		}
		if ( insert_case ) { values_new.push(value); }

		var cookie_value = values_new.join('|');
		if ( cookie_value == '' ) { setCookie(filter_id, 1, new Date(0), '/'); } 
		else { setCookie(filter_id, cookie_value, false, '/'); }

		last_modified_block = filter_id.substring(1);
		restoreFiltersState();
		runSearchTimer();

	}

	// сохраняет в куки значения ОТ и ДО для ЦЕНЫ И ТГК
	// для того, чтобы иметь возможность восстановить значения фильтров
	function saveRangeValues(filter_id) {

		var from = parseInt($("#" + filter_id + "_from").val()); 
		var to = parseInt($("#" + filter_id + "_to").val()); 

		cookie_name = 'f' + filter_id;
		setCookie(cookie_name, from + '|' + to, false, '/');

		last_modified_block = filter_id;
		runSearchTimer();

	}

	// ставит значения диапазонов как значения инпутов
	function setRangeValues(filter_id, from, to) {

		var from = from || $("#" + filter_id + "_params").attr('min');
		var to = to || $("#" + filter_id + "_params").attr('max');
		var unit = $("#" + filter_id + "_params").attr('unit') || '';

		$("#" + filter_id + "_to").val(to + unit);
		$("#" + filter_id + "_from").val(from + unit);

		saveRangeValues(filter_id);

	}

	// проверяет значения полей, измененных вручную
	function checkHandEditedValues(filter_id, type) {

		var set_another = false;
		var unit = $("#" + filter_id + "_params").attr('unit') || '';
		var min = parseInt($("#" + filter_id + "_params").attr('min'));
		var max = parseInt($("#" + filter_id + "_params").attr('max'));
		var value = parseInt($("#" + filter_id + "_" + type).val());

		if ( value > max || value < min || isNaN(value) ) {
			if ( type == 'to' ) { set_another = max + unit; }
			else { set_another = min + unit; }
		}

		if ( !set_another ) { set_another = value + unit; }
		$("#" + filter_id + "_" + type).val(set_another);

		saveRangeValues(filter_id);

	}

	// восстанавливает избираемый фильтр
	function restoreSelectable(object_id, child_prefix, filter_id) {

		$("#" + object_id + " span").removeClass('take_part');
		var values = getCookie(filter_id);
		if ( values ) {
			values = values.split('|');
			for ( key in values) {
				$("#" + child_prefix + "_" + values[key]).addClass('take_part');
			}
		}

	}

	// восстанавливает диапазоновый фильтр
	function restoreRange(filter_name) {
		var filter_id = 'f' + filter_name;
		var values = getCookie(filter_id);
		if ( values ) {
			values = values.split('|');
			var unit = $("#" + filter_name + "_params").attr('unit') || '';
			if (values[0]) { $("#" + filter_name + "_from").val(values[0] + unit); }
			if (values[1]) { $("#" + filter_name + "_to").val(values[1] + unit); }
		}
	}

	// функция восстанавливает состояние фильтров
	// на основе данных взятых из кук
	function restoreFiltersState() {

		restoreSelectable('producers_list', 'producer', 'fproducers');
		restoreSelectable('cats_list', 'cat', 'fcats');
		restoreSelectable('types_list', 'type', 'ftypes');

		restoreRange('price');
		restoreRange('tgk');

	}

	// текстовая функция отправки запроса количества
	// для расширенного поиска
	var search_timeout;
	var last_modified_block;
	function runSearchTimer() {
		if ( search_timeout !== undefined ) { clearTimeout(search_timeout); }
		search_timeout = setTimeout(runSearch, 500);
	}

	// формирует строку значений, разделенных запятой
	// для отправки запроса на поиск. принимает имя cookie
	function makeSearchUrlPart(cookie_name) {
		var values = getCookie(cookie_name) || '';
		values = values.split('|');
		values = values.join(',');
		return values;
	}

	// формирует строку запроса
	// для получения количества товаров 
	function runSearch() {
		
		// формируем строку запроса
		// множественные значения передаем через запятую
		var url = 	"?&cats=" + makeSearchUrlPart('fcats') + 
					"&producers=" + makeSearchUrlPart('fproducers') + 
					"&types=" + makeSearchUrlPart('ftypes');

		var price = getCookie('fprice') || '';
		price = price.split('|');
		if ( price[0] ) { url += "&price[0]=" + price[0] };
		if ( price[1] ) { url += "&price[1]=" + price[1] };
		
		var tgk = getCookie('ftgk') || '';
		tgk = tgk.split('|');
		if ( tgk[0] ) { url += "&tgk[0]=" + tgk[0] };
		if ( tgk[1] ) { url += "&tgk[1]=" + tgk[1] };


		// включаем preloader
		var top_offset = $("#" + last_modified_block + "_list").offset().top - 136;
		$("#sidebar_menu .counter").css({
			display: 'block', 
			top: top_offset 
		}).html("<img src='/templates/webeffection/images/search_preloader.gif' />");

		// отправляем запрос и выводим результат
		var request = createXMLHttpRequest();
		request.open('GET', '/udata/custom/myAdvancedSearch/.json' + url, true);
		request.onreadystatechange = function(){
			if (request.readyState == 4) {
				if (request.status == 200) {
					var result = eval("(" + request.responseText + ")");
					if ( result.total > 0 ) {
						$("#sidebar_menu .counter").html("Показать: " + result.total).attr('href', '/results/' + url + '&order_filter[name]=1');
					} else {
						$("#sidebar_menu .counter").html("Не найдено");
					}
					
				}
			}
		};
		request.send(null);

	}



								//------------------------------------------//
								// 		функции для работы с корзиной       //
								//------------------------------------------//


	// заполняет цену товара и формирует тег select
	// для количества при просмотре одного товара 
	function fillPackageOptions() {
		var selector = document.getElementById('package_selector');
		if ( selector ) {
			var price = $(selector).find("option:selected").attr("price");
			var count = $(selector).find("option:selected").attr("count");
			var options = '';
			for (var i = 1; i <= count; i++) {
				options += "<option>" + i + "</option>";
			}
			$("#package_count").html(options);
			$("#package_price").text(price);
		}
	}

	// добавляет товар к корзину
	function addPackageToCart(button) {

		var total_count, total_amount;
		var element_id = $("#package_selector option:selected").val();
		var count = $("#package_count option:selected").val();
		var url = '/udata/emarket/basket/put/element/' + element_id + '/.json?amount=' + count;
		var button_text = $(button).html(); // текст на кнопке, чтобы вернуть
		
		var request = createXMLHttpRequest();
		$(button).html("<img src='/templates/webeffection/images/add_to_cart_preloader.gif' />");

		request.open('GET', url, true);
		request.onreadystatechange = function(){
			if (request.readyState == 4) {
				if (request.status == 200) {
					$(button).html(button_text);
					var result = eval("(" + request.responseText + ")");
					total_count = result.summary.amount;
					total_amount = result.summary.price.actual;
					$("#cart").animate({opacity: 0}, 500, function() {
						$("#cart").html("<div class='details'><span>" + total_count + "</span> товаров на сумму <span>" 
						+ total_amount + " руб.</span></div><a href='/cart_content/'>Оформить заказ</a>");
						$("#cart").animate({opacity: 1}, 500);
					});
				}
			}
		};
		request.send(null);
	}

	// очищает корзину целиком
	function truncateCart() {
		var request = createXMLHttpRequest();
		$("#cart_thinking").html("<img src='/templates/webeffection/images/change_cart_preloader.gif' />"); 
		request.open('GET', '/udata/emarket/basket/remove_all/.json', true);
		request.onreadystatechange = function(){
			if (request.readyState == 4) {
				if (request.status == 200) {
					var result = eval("(" + request.responseText + ")");
					if ( result.summary.amount == 0 ) {
						setTimeout(function() {
							document.location = document.location + '?rnd=' + Math.round(Math.random()*999999999);
						}, 2000);
					}
				}
			}
		};
		request.send(null);
	}


	// удаляет товар с корзины
	function deletePackageFromCart(item_id) {

		var count_before = $("#cart_count").val();

		var request = createXMLHttpRequest();
		$("#cart_tr_" + item_id + " .marked span.trash").remove(); 
		$("#cart_tr_" + item_id + " .marked ").append("<img src='/templates/webeffection/images/delete_cart_preloader.gif' />");
		request.open('GET', '/udata/emarket/basket/remove/item/' + item_id + '/.json', true);
		request.onreadystatechange = function(){
			if (request.readyState == 4) {
				if (request.status == 200) {

					var result = eval("(" + request.responseText + ")");
					var count_after = result.summary.amount;

					// пустая корзина
					if ( count_after == 0 ) {
						document.location = document.location + '?rnd=' + Math.round(Math.random()*999999999);
					} 
					// убавилось
					else if ( count_after < count_before ) {

						// новое значение
						$("#cart_count").val(count_after);

						var total_original = result.summary.price.original || false;
						var total_actual = result.summary.price.actual || false;
						var discount_name = false;
						console.log(result);
						if ( total_original ) { discount_name = result.discount.name; }
						updateTotalValues(total_original, total_actual, discount_name);

						// изменяем вид корзины
						var table_size = $("#cart_tr_" + item_id).parent().find('tr').size();
						if (table_size == 1) {
							$("#cart_tr_" + item_id).closest('ul.good').remove();
						} else {
							$("#cart_tr_" + item_id).remove();
						}

					}

				}
			}
		};
		request.send(null);
	}

	// минус 1 / плюс 1
	function changePackageCount(element_id, step) {

		var limit = parseInt($("#cart_count_" + element_id).attr('limit'));
		var current_count = parseInt($("#cart_count_" + element_id).val());
		var new_count = current_count + step;
		if ( new_count == 0 || new_count > limit ) { return; }

		// общее количество
		var count_before = $("#cart_count").val();

		var request = createXMLHttpRequest();
		$("#cart_thinking").html("<img src='/templates/webeffection/images/change_cart_preloader.gif' />");
		var url = '/udata/emarket/basket/put/element/' + element_id + '/.json?amount=' + new_count;
		request.open('GET', url, true);
		request.onreadystatechange = function(){
			if (request.readyState == 4) {
				if (request.status == 200) {

					var result = eval("(" + request.responseText + ")");
					if ( result.summary.amount != count_before  ) {
						// произошли изменения в количестве

						$("#cart_count").val(result.summary.amount);
						$("#cart_count_" + element_id).val(new_count);
						$("#cart_thinking").html('');

						var total_original = result.summary.price.original || false;
						var total_actual = result.summary.price.actual || false;
						var discount_name = false;
						if ( total_original ) { discount_name = result.discount.name; }
						updateTotalValues(total_original, total_actual, discount_name);
						
						var item_tr = $("#cart_count_" + element_id).closest('tr');
						var item_price = parseInt($(item_tr).attr('price'));
						$(item_tr).find('.price').text(number_format(item_price*new_count, 0, ',', ' '));

					}

				}
			}
		};
		request.send(null); 
	}


	// обновляет итоговые значения
	function updateTotalValues(original, actual, size) {
		if ( original ) {
			// сумму товаров и итоговую сумму
			$("#order_table .total").css('display', 'block');
			$("#order_table .total .sum").text(number_format(actual, 0, ',', ' '));
			if (!parseInt(size)) {
				$("#order_table .total .size").text(size);
			} else {
				$("#order_table .total .size").text(parseInt(size) + '%');
			}
			// $("#order_table .total .size").text(size + '%');
			$("#order_table .total_no_discount .sum").text(number_format(original, 0, ',', ' '));
		} else {
			// только сумму товаров
			$("#order_table .total").css('display', 'none');
			$("#order_table .total_no_discount .sum").text(number_format(actual, 0, ',', ' '));
		}
	}

	// выбор способа платежа
	function setPayMethod(method) {
		// скрываем описание
		$("#payment_russian_post, #payment_other").slideUp('fast', function() {
			if ( method == 'russian_post' ) {
				// наложенный платеж
				$("#payment_insurance").slideDown('fast');
				$("#payment_total").text($("#sum_total_insurance").val());
				setTimeout(function() {
					$("#payment_russian_post").slideDown('slow');
				}, 500);
			} else {
				// с предоплатой
				$("#payment_insurance").slideUp('fast');
				$("#payment_total").text($("#sum_total").val());
				setTimeout(function() {
					$("#payment_other").slideDown('slow');
				}, 500);
			}
		});
	}


	// проверяет корректность заполнения поля
	// запускается при потере фокуса любого поля с атрибутом required='required'
	// поля с атрибутом its='index', проверяется как индекс
	// поля с атрибутом its='email', проверяется как email
	// остальные поля проверяются на пустоту и наличие только кириллических символов
	function checkTextfieldValue(object) {

		// паттерны
		var pattern_index = /^[0-9]{6}\s*$/;
		var pattern_email = /^[a-z0-9_\-\.]+@[a-z0-9_\-\.]{2,}\.[a-z]{2,4}\s*$/i;
		var pattern_common = /^[^a-z]+$/i;

		// прячем информацию о прошлых ошибках
		$(".report_top").html("");
		$(object).removeClass('mistaken');
		 
		// выполняется проверка 
		var value = $(object).val();
		var its = $(object).attr('its');

		if ( its == 'index' ) {
			if ( !pattern_index.test(value) ) {
				$(".report_top").html("Почтовый индекс - это значение из 6 цифр");
				$(object).addClass('mistaken');
			}
		} else if (  its == 'email' ) {
			if ( !pattern_email.test(value) ) {
				$(".report_top").html("Email некорректный");
				$(object).addClass('mistaken');
			}
		} else {
			if ( !pattern_common.test(value) ) {
				$(".report_top").html("Поле должно быть заполнено, недопустимы лантинские символы");
				$(object).addClass('mistaken');
			}
		}

	}

	function checkTextfieldValueUser(object) {

		// паттерны
		var pattern_index = /^[0-9]{6}\s*$/;
		var pattern_email = /^[a-z0-9_\-\.]+@[a-z0-9_\-\.]{2,}\.[a-z]{2,4}\s*$/i;
		var pattern_common = /^[^a-z]+$/i;

		// прячем информацию о прошлых ошибках
		$(".report_top").html("");
		$(object).removeClass('mistaken');
		 
		// выполняется проверка 
		var value = $(object).val();
		var its = $(object).attr('it');

		if ( its == 'index' ) {
			if ( !pattern_index.test(value) ) {
				$(".report_top").html("Почтовый индекс - это значение из 6 цифр");
				$(object).addClass('mistaken');
			}
		}
		//  else if (  its == 'email' ) {
		// 	if ( !pattern_email.test(value) ) {
		// 		$(".report_top").html("Email некорректный");
		// 		$(object).addClass('mistaken');
		// 	}
		// }
		//  else {
		// 	if ( !pattern_common.test(value) ) {
		// 		$(".report_top").html("Поле должно быть заполнено, недопустимы лантинские символы");
		// 		$(object).addClass('mistaken');
		// 	}
		// }

	}

	// при выборе способа оплаты "Почтовое отправление до востребования"
	// поля улица, дом и квартира не обязательны для заполнения
	// их нужно скрыть и записать в них прочерки, чтобы umi съел эти значения
	// т.к. в шаблоне данных они заданы как required

	// при загрузке страницы, а также при изменении способа оплаты
	// срабатывает функция, которая 
	// а) делает поля невидимыми и пишет в них значения
	// б) делает поля видимыми и пишет в них пустое значение, если текущее == '---'

	function checkPostMethod() {
		
		// скрываем сообщение об ошибке
		$(".report_top")

		// получаем текущие значения
		var street_val = $("input[its='street']").val();
		var house_val = $("input[its='house']").val();
		var flat_val = $("input[its='flat']").val();

		// записываем их как дефолтные, если они не равны '---'
		if ( street_val != '---' ) { $("input[its='street']").attr('default', street_val); }
		if ( house_val != '---' ) { $("input[its='house']").attr('default', house_val); }
		if ( flat_val != '---' ) { $("input[its='flat']").attr('default', flat_val); }

		var first_class_checked = $("input[name='delivery-id']").eq(0).attr('checked');
		if ( first_class_checked == 'checked' ) {

			// б)
				
			// получаем дефолтные значения 	
			var street_dval = $("input[its='street']").attr('default');
			var house_dval = $("input[its='house']").attr('default');
			var flat_dval = $("input[its='flat']").attr('default');

			// ставим дефолтные
			$("input[its='street']").val(street_dval);
			$("input[its='house']").val(house_dval);
			$("input[its='flat']").val(flat_dval);

			// показываем блоки
			$("input[its='street'], input[its='house'], input[its='flat']").closest('li').css('display', 'block');

		} else {

			// a)
			// скрываем блоки
			$("input[its='street'], input[its='house'], input[its='flat']").closest('li').css('display', 'none');
			// ставим прочерки
			$("input[its='street']").val('---');
			$("input[its='house']").val('---');
			$("input[its='flat']").val('---');

		}

	}

	function changePass(forms) {
		$(forms).find('span.report').remove();
		$.ajax({
  			url: '/udata/users/oldPass.json',
  			data: $(forms).serialize(),
  			type: "post",
  			cache: false,
  			dataType: "json",
  			success: function(msg) {
  				if (msg.result == '0') {
  					$(forms).find('span.button').after('<span class="report">Неверный старый пароль</span>');
  				} else if (msg.result == '1') {
  					$(forms).submit();
  				}

  			}
		});
	}

	/*				после загрузки страницы

	 *************************************************/
	
	$(document).ready(function() {
		$('#loup').jloupe({
    		radiusLT: 0,
    		radiusRT: 100,
    		radiusRB: 100,
    		radiusLB: 100,
 		cursorOffsetY: 0,
		cursorOffsetX: 5,                      		
		width: 120,
    		height: 120,
		margin: 1,
    		borderColor: '#999999',
    		backgroundColor: '#fff',
    		fade: false
		});
		// $('.zoom_img').click(function() {return false;})
		$('.zoom_img').fancybox();



		// заполняет цену товара и формирует тег select
		// для страниц просмотра товара
		fillPackageOptions() 

		// установка обработчиков текстовых элементов формы
		setTextfieldsHandlers();




		// иницилизация кнопок + / - для расширенного поиска
		$("#sidebar_menu .more, #sidebar_menu .less ").click(function() {

			var params_obj =  $(this).closest('li').find("input[type='hidden']");
			var changeable = $(this).closest('.more_less').find('input');
			var step = parseInt($(params_obj).attr('step'));
			var unit = $(params_obj).attr('unit') || '';
			var now_value = Math.round(parseInt($(changeable).val())/step) * step;
			
			var filter_id = params_obj.attr('id');
			filter_id = filter_id.substring(0, filter_id.indexOf('_')); 
			
			if ( $(this).hasClass("more") ) {
				var new_value = now_value + step;
				if ( unit == '%' && new_value > 100 ) { new_value = 100; }
			} else {
				var new_value = now_value - step;
				if ( new_value < 0 ) { new_value = 0; }
			}

 			$(changeable).val(new_value + unit);
 			saveRangeValues(filter_id); 
			
		});


		// обработчик для меню, наведение на область
		$('#sidebar_menu .scope').bind({
			mouseover: function() { $(this).addClass('over'); },
			mouseout: function() { $(this).removeClass('over'); } 
		});

		// обработчик для меню, раскрытие/сворачивание области
		$("#sidebar_menu .title").click(function () {

			$("#sidebar_menu .counter").css('display', 'none');

			var next = $(this).next();
			var parent = $(this).parent();

	    	if ( $(next).css('display') == 'block' ) {
	    		$(next).slideUp('fast', function() {
	    			$(parent).removeClass('opened');
	    		});
	    	}
		    else {
		    	$(next).slideDown('fast', function() {
		    		$(parent).addClass('opened');
		    	});
		    }

		    setTimeout(function () { saveAccordionState(); }, 500);

	    });

	    // инициализация аккордиона
	    $("#sidebar_menu").accordion({
	    	header : '.header', 
	    	autoHeight: false, 
	    	collapsible: true,
	    	active: acstate_active,
	    	change: function(event, ui) { 
	    		ui.newHeader.addClass('opened');
	    		ui.oldHeader.removeClass('opened');
	    		saveAccordionState();
	    		$("#sidebar_menu .counter").css('display', 'none');
	    	}
	    });

	    // восстановление состояния аккордиона
	    restoreAccordionState();

	    // восстанавливает состояние фильтров
	    restoreFiltersState();






	    // деактивация кнопок скроллинга для карусели на главной
	    $('.carousel').each(function() {
	    	if ( $(this).find('li').length <=4 ) {
	    		$(this).find('.roll.right').addClass('disabled');
	    	}
	    });

	    // инициализация каруселей на главной
	    $('.carousel .roll').bind('click', function() {
	    	var carousel = $(this).parent('.carousel');
	    	if ( $(this).is('.right') ) { rollCarousel(carousel, 'right', 4, 172, 1); }
	    	else { rollCarousel(carousel, 'left', 4, 172, 1); }
	    });






	    // деактивация кнопок скроллинга для фотогалереи
    	if ( $("#photos_carousel li").length <=3 ) {
    		$("#photos_carousel").find('.roll.right').addClass('disabled');
    	}


	    // обработчики для фотогаллереи
	    $('#photos_carousel .roll').bind('click', function() {
	    	var carousel = $(this).parent('#photos_carousel');
	    	if ( $(this).is('.right') ) { rollCarousel(carousel, 'right', 3, 108, 1); }
	    	else { rollCarousel(carousel, 'left', 3, 108, 1); }
	    });
	    $("#photos_carousel li img").click(function() {

	    	var image = $("#view_good .photos .image");
	    	var zoom = $("#view_good .photos .zoom");

	    	$(image).html('<a href="' + $(this).attr('big') + '" class="zoom_img"><img src="' + $(this).attr('big') + '" alt="" style="display: none;" id="loup" /></a>');

	    	$(image).addClass('loading');
	    	$(zoom).addClass('loading');
	    	$(image).find('.zoom_img').fancybox();
	    	$(image).find('#loup').load(function() {
	    		$(image).removeClass('loading');
	    		$(zoom).removeClass('loading');
	    		$(this).css('display', 'block');
	    		$('.zoom_img').click(function() {return false;})
	    		$('#loup').jloupe({
    				radiusLT: 0,
    				radiusRT: 100,
    				radiusRB: 100,
    				radiusLB: 100,
 					cursorOffsetY: 0,
					cursorOffsetX: 5,                      		
					width: 120,
    				height: 120,
					margin: 1,
    				borderColor: '#999999',
    				backgroundColor: '#fff',
    				fade: false
				});
	    	}); 

	    });





		// обработчик для чекбокса
		$(".nice_checkbox span").bind('click', function() {

			var checkbox = $(this).prev();
			if ( $(checkbox).attr('checked') ) {
				return false;
			} else {
				// снимаем выделения, если взаимоисключающие
				if ($(this).attr('type') == 'excepting') {
					var parent = $(this).attr('parent');
					$('#' + parent).find('.nice_checkbox span').removeClass('checked');
					$('#' + parent).find('.nice_checkbox input').attr('checked', false);
				}
				// выделяем текущий
				$(checkbox).attr('checked', 'checked');
				$(this).addClass('checked');
			}

			checkPostMethod();

		});
		// нужно ставить первый по умолчанию
		var shipping_method = false;
		$("#forming_form input[name='delivery-id']").each(function() {
			if ( $(this).attr('checked') == 'checked' ) {
				shipping_method = true;
			}
		});
		if ( !shipping_method ) {
			$("#forming_form input[name='delivery-id']").eq(0).attr('checked', 'checked');
		}
		var pay_method = false;
		$("#payment_form input[name='payment-id']").each(function() {
			if ( $(this).attr('checked') == 'checked' ) {
				pay_method = true;
			}
		});
		if ( !pay_method ) { 
			$("#payment_form input[name='payment-id']").eq(0).attr('checked', 'checked');
		}

		// метит выбранный при загрузке
		$(".nice_checkbox input").each(function() {
			if ($(this).attr('checked')) {
				$(this).next().addClass('checked');
			}
		});





		// удаляет дубликаты при отображении содержимого корзины
		$("#order_table ul.good").each(function() {
			var sort_id = $(this).attr('sort_id');
			$("#order_table ul.good[sort_id='" + sort_id + "']").each(function() {
				var index = $(this).index(".good[sort_id='" + sort_id + "']");
				if (index > 0) { $(this).remove(); }
			});
		});


		// установка обработчика для обязательных полей
		$("input[required='required'], textarea[required='required']").blur(function() {
			checkTextfieldValue(this);
		});


		// прячем поля в зависимости от способа доставки
		checkPostMethod();

		
			
		
	});

