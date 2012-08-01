var ie = document.selection && window.ActiveXObject && /MSIE/.test(navigator.userAgent);

createSimple('Bold',		{'button-label': 'Bold', 'button-title': 'Жирный', 'prefix': 'b'})();
createSimple('Italic',		{'button-label': 'Italic', 'button-title': 'Курсив', 'prefix': 'i'})();
createSimple('Underline',	{'button-label': 'Underlined', 'button-title': 'Подчеркнутый', 'prefix': 'u'})();

createSimple('JustifyLeft',	{'button-label': 'Left', 'button-title': 'Выравнивание по левому краю', 'prefix': 'l'})();
createSimple('JustifyCenter',	{'button-label': 'Center', 'button-title': 'Выравнивание по центру', 'prefix': 'c'})();
createSimple('JustifyRight',	{'button-label': 'Right', 'button-title': 'Выравнивание по правому краю', 'prefix': 'r'})();

createSimple('InsertOrderedList',	{'button-label': 'InsertOrderedList', 'button-title': 'Нумерованный список', 'prefix': 'ol'})();
createSimple('InsertUnorderedList',	{'button-label': 'InsertUnorderedList', 'button-title': 'Маркированный список', 'prefix': 'ul'})();


inlineWYSIWYG.button('AddLink', {
	init: function (params) {
		var button = inlineWYSIWYG.createSimpleButton(params['editor'], 'AddLink', 'addlink', true);
		jQuery(button).attr({
			'value':		'AddLink',
			'title':		'Создать ссылку'
		});
	},
	execute: function (params, targetNode, sels) {
		var node = sels.getNode(), url = '';
		if(node.nodeType == 1 && node.tagName == 'A') {
			url = jQuery(node).attr('href');
		}

		var date = new Date;
		var ts = date.getTime();
		var _sels = sels;
		url = '/styles/common/other/inline-wysiwyg/createLink.html?ts=' + ts + '&url=' + url;
		
		sels.save();
		jQuery.openPopupLayer({
			'name'   : "CreateLink",
			'title'  : "Создание ссылки",
			'url'    : url,
			'width'  : 490,
			'height' : 95,
			'afterClose': function (value) {
				_sels.load();
				if(typeof value == 'undefined') return true;
				if(value) {
					document.execCommand('createlink', false, value);
				} else {
					document.execCommand('unlink', false, false);
				}
			}
		});
	},
	status: function () {
		return false;
	},
	params: {}
});


inlineWYSIWYG.button('UnLink', {
	init: function (params) {
		var button = inlineWYSIWYG.createSimpleButton(params['editor'], 'UnLink', 'unlink', true);
		jQuery(button).attr({
			'value':		'Unlink',
			'title':		'Удалить ссылку'
		});
	},
	execute: function (params, targetNode, sels) {
		sels.expand();
		document.execCommand('unlink', false, false);
	},
	status: function () {
		return false;
	},
	params: {}
});


inlineWYSIWYG.button('InsertImage', {
	init: function (params) {
		var button = inlineWYSIWYG.createSimpleButton(params['editor'], 'InsertImage', 'insertimage', true);
		jQuery(button).attr({
			'value':		'InsertImage',
			'title':		'Вставить/редактировать изображение'
		});
	},
	execute: function (params, targetNode, sels) {
		if (typeof inlineWYSIWYG.select == 'undefined') {
			inlineWYSIWYG.select = {node:false,params:{}};
		}
		var node = inlineWYSIWYG.select.node,
			src = '', fileName = '', css = [], i, cssItem;
		if (typeof node == 'undefined' || node === false) {
			alert('Установите курсор в место вставки изображения');
			return false;
		}
		inlineWYSIWYG.select.params.folder = './images/cms/data';
		if((ie ? node.tagName.toLowerCase() == 'img' : node instanceof HTMLImageElement)) {
			src = jQuery(node).attr('src');
			src = src.toString();
			var arr = src.split(/\//g);
			fileName = arr[arr.length - 1];
			inlineWYSIWYG.select.params.folder = '.' + src.substr(0, src.length - fileName.length - 1);
			inlineWYSIWYG.select.params.file = src;
			if (typeof node.attributes.style != 'undefined') {
				css = (ie
					? node.style.cssText.toLowerCase().replace(/;[\s]*$/g, '').split('; ')
					: node.attributes.style.nodeValue.replace(/;[\s]*$/g, '').split('; '));
			}
			inlineWYSIWYG.select.params.css = {};
			for (i = 0; i < css.length; i++) {
				cssItem = css[i].split(': ');
				if (cssItem[0] == "margin") {
					var margin = ["margin-top", "margin-right", "margin-bottom", "margin-left"],
						mar = cssItem[1].split(" "), mi;
					switch(mar.length) {
						case 3:{
							mar.push(mar[1]);
							break;
						}
						case 2:{
							mar.push(mar[0]);
							mar.push(mar[1]);
							break;
						}
						case 1:{
							mar.push(mar[0]);
							mar.push(mar[0]);
							mar.push(mar[0]);
							break;
						}
					}
					for (mi in margin) {
						inlineWYSIWYG.select.params.css[margin[mi]] = parseInt(mar[mi]);
					}
					continue;
				}
				if (cssItem[0] == 'float') cssItem[0] = 'vertical-align';
				inlineWYSIWYG.select.params.css[cssItem[0]] = ((isNaN(parseInt(cssItem[1]))) ? cssItem[1] : parseInt(cssItem[1]));

			}
		}
		
		var _sels = sels;
		if(ie && (!sels.isSomethingSelected() && !css.length)) {
			alert("Выделите фрагмент текста");
			return false;
		}
		
		sels.save();
		
		jQuery.openPopupLayer({
			'name'   : "ImageEditor",
			'title'  : "Редактор изображений",
			'width'  : 360,
			'url'    : '/styles/common/other/inline-wysiwyg/imageEditor.html',
			'afterClose': function (value) {
				if(typeof value == 'undefined') return;
				if(value.src.length > 0)  {
					_sels.load();

					setTimeout(function () {
						var img, i;
						if ((ie ? node.tagName.toLowerCase() == 'img' : node instanceof HTMLImageElement)) {
							node.src = value.src;
							img = jQuery(node);
						}
						else {
							var temp = jQuery("img[src='" + value.src  + "']", targetNode);
							if (!temp.length) temp = jQuery("img[src='" + location.protocol + "//" + location.host + value.src + "']", targetNode);
							document.execCommand('InsertImage', false, value.src);
							img = jQuery("img[src='" + value.src  + "']", targetNode);
							if (!img.length) img = jQuery("img[src='" + location.protocol + "//" + location.host + value.src + "']", targetNode);
							if (img.length > 1) {
								for (i = 0; i < img.length; i++) {
									if (img[i] !== temp[i]) {
										img = jQuery(img[i]);
										break;
									}
								}
							}
						}
						img.css(value.css);
					}, 100);
				}
			}
		});
		return true;
	},
	status: function () {
		return false;
	},
	params: {}
});


inlineWYSIWYG.button('XmlOff', {
	init: function (params) {
		var button = inlineWYSIWYG.createSimpleButton(params['editor'], 'XmlOff', 'xmloff');
		jQuery(button).attr({
			'value':		'XmlOff',
			'title':		'Очистить код'
		});
	},
	execute: function (params, targetNode) {
		var html = jQuery(targetNode).html();
		var strict = true;

		html = html.replace(/<![\s\S]*?--[ \t\n\r]*>/ig, ' ');
		html = html.replace(/<!--[\w\W\n]*?-->/ig, ' ');
		html = html.replace(/<\/?(title|style|font|meta)\s*[^>]*>/ig, '');
		html = html.replace(/\s*mso-[^:]+:[^;""]+;?/ig, '');
		html = html.replace(/<\/?o:[^>]*\/?>/ig, '');
		html = html.replace(/ style=['"]?[^'"]*['"]?/ig, '');

		if(strict) html = html.replace(/ class=['"]?[^'">]*['"]?/ig, '');

		html = html.replace(/<span\s*[^>]*>\s*&nbsp;\s*<\/span>/ig, '');
		html = html.replace(/<span\s*[^>]*>/ig, '');
		html = html.replace(/<\/span\s*[^>]*>/ig, '');

		// Glue
		html = html.replace(/<\/(b|i|s|u|strong|center)>[\t\n]*<\1[^>]*>/gi, "");
		html = html.replace(/<\/(b|i|s|u|strong|center)>\s*<\1[^>]*>/gi, " ");
		// Cut epmty
		html = html.replace(/<(b|i|s|u|strong|center)[^>]*>[\s\t\n\xC2\xA0]*<\/\1>/gi, "");
		// Cut trash symbols
		html = html.replace(/(\t|\n)/gi, " ");
		html = html.replace(/[\s]{2,}/gi, " ");

		if(jQuery.browser.safari) {
			html = html.replace(/\bVersion:\d+\.\d+\s+StartHTML:\d+\s+EndHTML:\d+\s+StartFragment:\d+\s+EndFragment:\d+\s*\b/gi, "");
		}

		jQuery(targetNode).html(html);
	},
	status: function () {
		return false;
	},
	params: {}
});

inlineWYSIWYG.button('ClipboardPaste', {
	init: function (params) {
		var button  = inlineWYSIWYG.createSimpleButton(params['editor'], 'ClipboardPaste', 'clipboardpaste');
		var toolbox = jQuery(params['toolbox']);
		var tip = jQuery("<div class='eip-wysiwyg-toolbox eip-ui-element eip-wysiwyg_tip'>Для вставки из буфера обмена нажмите Ctrl+V</div>");
		jQuery(button).attr({
			'value':		'ClipboardPaste'
		}).mouseenter(function() {
			jQuery(document.body).append(tip);
			tip.css({
				position : 'absolute',
				display  : 'none',
				top      : (parseInt(toolbox.css('top')) - parseInt(tip.height())) + "px",
				left	 : toolbox.css('left'),
				width    : (parseInt(toolbox.outerWidth()) - 10) + "px"
			}).fadeIn(400);
		}).mouseleave(function(){
			tip.fadeOut(400, function(){ tip.remove(); });
		});
	},
	execute: function (params, targetNode) {
		// Nothing to do here
	},
	status: function () {
		return false;
	},
	params: {}
});
