(function () {
	var checkPrivateMessages = function () {
		jQuery.get('/umess/inbox/?mark-as-opened=1', function (xml) {
			jQuery('message', xml).each(function (index, node) {
				var title = jQuery(node).attr('title');
				var content = jQuery('content', node).text();
				var date = jQuery('date', node).text();
				var sender = jQuery('sender', node).attr('name');
				
				content = '<p>' + content + '</p><div class="header">' + date + ', ' + sender + '</div>';
				jQuery.jGrowl(content, {
					'header': title,
					'life': 10000
				});
			});
		});
		setTimeout(checkPrivateMessages, 15000);
	};
	//checkPrivateMessages();
})();

var askSupport = function() {

	var h  = '<h3 id="license_message">Сейчас мы проверим Ваш доменный ключ</h3>';
	h += '<div id="ask_support_form">';
	h += '<div id="loading"></div>';
	h += '<span id="show_info" style="">Информация</span>';
	h += '<div id="info_support">';
	h += '<div class="left"><h4>Создавая заявку, помните:</h4>';
	h += '<ul>';
	h += '<li>не пишите в одну заявку несколько не связанных между собой вопросов;</li>';
	h += '<li>проблема может решаться дольше, если она требует детальной диагностики, доработки функционала или выпуска обновления продукта;</li>';
	h += '<li>не дублируйте Ваши вопросы.</li>';
	h += '<li>в демонстрационном режиме запросы в Службу Заботы не отправляются</li>';
	h += '</ul></div>';
	h += '<div class="right"><h4>Мы не оказываем поддержку в случае, если:</h4>';
	h += '<ul>';
	h += '<li>вносились какие-либо изменения в программный код продукта, либо его модулей;</li>';
	h += '<li>хостинг (сервер) не соответствует системным требованиям;</li>';
	h += '<li>вопрос выходит за рамки технической поддержки.</li>';
	h += '</ul></div>';
	h += '<div class="clear" />';
	h += '</div>';
	h += '<div id="form_body"></div>';
	h += '</div>';
	h += '<div class="eip_buttons">';
	h += '<input id="checkLicenseKey" type="button" value="Отправить" class="ok" style="display:none;"/>';
	h += '<input id="stop_btn" type="button" value="Закрыть" class="stop" />';
	h += '<div style="clear: both;"/>';
	h += '</div>';

	openDialog({
		stdButtons: false,
		title      : getLabel('js-ask-support'),
		text       : h,
		width      : 637,
		OKCallback : function () {

		}
	});
	
	$('#stop_btn').one("click", function() { closeDialog(); });
	
	jQuery("#loading").ajaxStart(function(){
		jQuery(this).html('<img src="/images/cms/admin/mac/ajax_loader.gif" alt="Loading..." />');
	});

	jQuery.ajax({
		type: "POST",
		url: "/udata/system/checkLicenseKey/",
		dataType: "xml",

		success: function(doc) {
		
			jQuery("#form_body").html('');
			jQuery("#loading").html('');
			var message = '';
		
			var errors = doc.getElementsByTagName('error');
			if (errors.length) message = errors[0].firstChild.nodeValue;
		
			var notes = doc.getElementsByTagName('notes');	
			if (notes.length) message += notes[0].firstChild.nodeValue;
				
			jQuery("#license_message").html(message);
			
			var forms = doc.getElementsByTagName('form');

			if (forms.length) {
				var user = doc.getElementsByTagName('user');
				
				jQuery("#form_body").html('<form id="support_request" action="" method="post">' + forms[0].firstChild.nodeValue + '</form>');
				
				jQuery('input[name="data[fio_frm]"]').val(user[0].getAttribute('name'));
				jQuery('#email_frm').val(user[0].getAttribute('email'));
				
				var parent = jQuery('input[name="data[cms_domain]"]').parent();
				jQuery('input[name="data[cms_domain]"]').remove();
				
				var select = document.createElement('select');
				select.name = "data[cms_domain]";
				
				var domains = doc.getElementsByTagName('domains');
				
				for(var i = 0; i < domains[0].getElementsByTagName('domain').length; i++) {
					var domain = domains[0].getElementsByTagName('domain');
					domain = domain[i];
					var option   = document.createElement('option');
					option.value = domain.getAttribute('host');
					if (domain.getAttribute('host') == user[0].getAttribute('domain')) option.selected = true;
					option.appendChild(document.createTextNode(domain.getAttribute('host')));
					select.appendChild(option);
				}
				parent.append(select);
				
				jQuery("#attach_file").parent('div').remove();
				jQuery(".button_1").remove();
				jQuery("#checkLicenseKey").attr("style", "");
				
				jQuery("#show_info").attr("style", "display:inline-block;");
				jQuery("#show_info").click(function(){
					jQuery("#info_support").toggle('slow');
				});
				
				jQuery.centerPopupLayer();
				
				jQuery('#checkLicenseKey').bind("click", function() {
					
					jQuery.ajax({  
						type: "POST",  
						url: "/udata/system/sendSupportRequest/",  
						data: jQuery("#support_request").serializeArray(),
						success: function(data) {
						
							jQuery("#loading").html('');  
							
							var error = data.getElementsByTagName('error');
							if (error.length) {
								message = '<span style="color:red;">' + error[0].firstChild.nodeValue + '</span>';
							}
						
							var success = data.getElementsByTagName('success');	
							if (success.length) {
								message = success[0].firstChild.nodeValue;
								jQuery("#ask_support_form").remove();
								jQuery("#checkLicenseKey").remove();
							}
							
							jQuery("#license_message").html(message);
							
							jQuery.centerPopupLayer();
							
						}  
					});  
					return false;  
					
				});
												
			}
			
			
			return;

		},

		error: function(jqXHR, textStatus, errorThrown) {
			if(window.session) {
				window.session.stopAutoActions();
			}
		}

	});
}