var editBox = function (editor, node, boxNode, filesDataTransfer) {
	var editorInstance, boxDomNode, domNode, elementId = '', objectId = '', fieldName, fieldType, originalValue, newValue, fieldParams, files = filesDataTransfer;
	var self = this, cleanedUp = false;
	var voidFinish = function (apply) { self.cleanup(); };

	this.finish = voidFinish;

	this.cleanup = function () {
		if(cleanedUp) return; else cleanedUp = true;

		editorInstance.bindEvents();

		this.finish = voidFinish;

		editorInstance.highlightNode(domNode);
		jQuery('.u-eip-add-button').css('display', 'block');
		uPageEditor.normalizeBoxes();

		editBox.currentInstance = null;
		domNode.blur();
		jQuery(domNode).removeClass('u-eip-editing');
	};

	var onLoadValue = function (value, type, params) {
		originalValue = value;
		fieldType = type;
		fieldParams = params;

		switch(fieldType) {
			case 'relation':
				drawRelationEditor(); break;

			case 'video_file':
			case 'file':
				drawImageEditor(false); break;

			case 'img_file':
				drawImageEditor(true); break;

			case 'date':
				drawDateEditor(); 
				if('' == objectId) {
					originalValue = '';
				}
				break;

			case 'wysiwyg':
				drawHTMLEditor(true);
				break;

			case 'boolean':
				drawBooleanEditor();
				break;

			case 'text':
			case 'int':
			case 'counter':
			case 'string':
			case 'float':
			case 'tags':
			case 'price':
				drawTextEditor();
				break;

			case 'symlink':
				drawSymlinkEditor();
				break;

			default:
				alert("Unkown field type \"" + fieldType + "\"");
		}

		jQuery('.u-eip-add-button, .u-eip-add-box').css('display', 'none');
	};

	var commit = function () {
		if(fieldType == 'string' && typeof newValue == 'string') {
			newValue = newValue	.replace(/\n/g, ' ')
								.replace(/<br>/g, ' ')
								.replace(/[\s]{2,}/g, ' ');
			jQuery(domNode).html(newValue);
		}


		//If value modified, we should create new revision
		if(newValue != originalValue) {
			var revision = editorInstance.queue.add({
				'element-id':		elementId,
				'object-id':		objectId,
				'field-name':		fieldName,
				'original-value':	originalValue,
				'new-value':		newValue,
				'field-type':		fieldType,
				'params':			fieldParams
			});
			revision.apply();
		}
	};

	var bindFinishEvent = function () {
		jQuery(document).bind('click', function(e) {
			if(jQuery(e.target).attr('contentEditable') == 'true') return true;
			if(jQuery(e.target).attr('class') == 'eip-ui-element') return true;
			if(jQuery(e.target).attr('class') == 'ui-datepicker') return true;
			if(jQuery(e.target).parents('.eip-ui-element, .ui-datepicker, .ui-datepicker-title, .ui-datepicker-header, .ui-datepicker-calendar').size()) return true;
			var parents = jQuery(e.target).parents();
			for(var i = 0; i < parents.size(); i++) {
				var pNode = parents[i];
				if(pNode.tagName == 'TABLE') continue;
				if(pNode.tagName == 'BODY') break;
				if(jQuery(pNode).attr('contentEditable') == 'true') return true;
				if(jQuery(pNode).attr('class') && jQuery(pNode).attr('class').indexOf('mceEditor') != -1) return true;
			}

			self.finish(true);
		});
	};

	var drawTextEditor = function (allowHTMLTags) {
		var sourceValue = jQuery(domNode).html();

		jQuery(domNode).html(originalValue ? originalValue : '&nbsp;');
		jQuery(domNode).attr('contentEditable', true);
		jQuery(domNode).blur().focus();

		self.finish = function (apply) {
			self.finish = function () {};

			jQuery(document).unbind('keyup');
			jQuery(document).unbind('keydown');
			jQuery(document).unbind('click');

			jQuery(domNode).attr('contentEditable', false);
			jQuery('.u-eip-sortable').sortable('enable');

			if(apply) {
				newValue = jQuery(domNode).html();

				if(newValue == ' ' || newValue == '&nbsp;') {
					newValue = '';
					jQuery(domNode).html(newValue);
				}

				if(fieldType != 'wysiwyg' && fieldType != 'text') {
					newValue = jQuery.trim(newValue);
					if(newValue.substr(-4, 4) == '<br>') {
						newValue = newValue.substr(0, newValue.length -4);
					}
				} else {
					newValue = newValue.replace(/%[A-z0-9_]+(?:%20)+[A-z0-9_]+%28[A-z0-9%]*%29%/gi, unescape);
				}

				commit();
			} else jQuery(domNode).html(sourceValue);
			self.cleanup();
		};



		bindFinishEvent();

		var prevWidth = jQuery(domNode).width();
		var prevHeight = jQuery(domNode).height();
		var timeoutId = null;

		jQuery(document).bind('keyup', function () {
			if(prevWidth != jQuery(domNode).width() || prevHeight != jQuery(domNode).height()) {
				prevWidth = jQuery(domNode).width();
				prevHeight = jQuery(domNode).height();

				if(timeoutId) clearTimeout(timeoutId);
				timeoutId = setTimeout(function () {
					uPageEditor.normalizeBoxes();
					timeoutId = null;
				}, 1000);
			}
		});


		if(!allowHTMLTags && fieldType != 'wysiwyg') {
			jQuery(document).bind('keyup', function (e) {
				var html = jQuery(domNode).html();
				var originalHtml = html;
				html = html.replace(/<!--[\w\W\n]*?-->/mig, '');
				html = html.replace(/<style[^>]*>[\w\W\n]*?<\/style>/mig, '');
				html = html.replace(/<([^>]+)>/mg, '');
				html = html.replace(/(\t|\n)/gi, " ");
				html = html.replace(/[\s]{2,}/gi, " ");
				if (jQuery.browser.safari) {
					html = html.replace(/\bVersion:\d+\.\d+\s+StartHTML:\d+\s+EndHTML:\d+\s+StartFragment:\d+\s+EndFragment:\d+\s*\b/gi, "");
				}
				if (html != originalHtml)  domNode.innerHtml = html;
			});
		}

		jQuery('.u-eip-sortable').sortable('disable');
		domNode.focus();

		var prevLength = null;
		jQuery(document).bind('keydown', function (e) {
			if(e.keyCode == 46) {
				prevLength = jQuery(domNode).html().length;
			}
		});

		jQuery(document).bind('keyup', function (e) {
			if(e.keyCode == 46) {
				if(prevLength == jQuery(domNode).html().length) {
					if(prevLength == 1) {
						jQuery(domNode).html('');
					}
				}
			}
		});

		jQuery(document).bind('keydown', function (e) {
			//Enter key - save content
			if(e.keyCode == 13 && fieldType != 'wysiwyg' && fieldType != 'text') {
				self.finish(true);
				return false;
			}

			//Esc key - cancel and revert original value
			if(e.keyCode == 27) {
				self.finish(false);
				return false;
			}

			return true;
		});
	};

	var drawHTMLEditor = function () {

		drawTextEditor();
		jQuery(document).unbind('keydown');

		if(uPageEditor.onPasteCleanup) {
			var ctrl  = false;
			var shift = false;
			var vkey  = false;
			var ins   = false;
			jQuery(document).bind('keyup', function (e) {				
				if((e.keyCode == 86 || ctrl) || (e.keyCode == 45 && shift)) {
					var html = uPageEditor.cleanupHTML(jQuery(domNode).html());
					if(html != jQuery(domNode).html()) {
						jQuery(domNode).html(html);
					}
					if(ctrl && !e.ctrlKey) ctrl = false
					if(shift && !e.shiftKey) shift = false
				}												
				switch(e.keyCode) {
					case 16: if(!ins) shift = false; break;
					case 17: if(!vkey) ctrl = false; break;					
					case 45: ins  = false; break;
					case 86: vkey = false; break;
				}
			}).bind('keydown', function(e) {
				switch(e.keyCode) {
					case 16: shift = true; break;
					case 17: ctrl  = true; break;					
					case 45: ins   = true; break;
					case 86: vkey  = true; break;
				}
			});
		}

		var editor = {};
		switch (uPageEditor.wysiwyg) {
			case "tinymce": {
				var date = new Date();
				domNode.id = editor.id = 'mceEditor-' + date.getTime();
				tinyMCE.execCommand("mceAddControl", true, editor.id);
				editor.destroy = function() {
					var oldNode = jQuery('#'+editor.id),
						newNode = jQuery('#'+editor.id+'_parent'),
						frame = jQuery('iframe', newNode)[0],
						content;
					if (ie && (typeof document.documentMode == 'undefined' || document.documentMode < 7)) {
						content = window.frames[window.frames.length-1].document.body.innerHTML;
					}
					else content = frame.contentDocument.body.innerHTML;
					oldNode.html(content);
					newNode.remove();
					oldNode.css('display','');
					oldNode[0].id = '';
				};
				break;
			}
			default:editor = new inlineWYSIWYG(domNode);
		}

		var finish = self.finish;
		self.finish = function (apply) {
			editor.destroy();
			finish(apply);
			uPageEditor.get().bindEvents();
		};
	};

	var drawImageEditor = function (imageOnly) {
		var folder = './images/cms/data/', filename = '';

		self.finish = function (apply) {
			if(apply) commit();
			self.cleanup();
		};

		if(originalValue) {
			originalValue = originalValue.toString();
			var arr = originalValue.split(/\//g);
			fileName = arr[arr.length - 1];
			folder = '.' + originalValue.substr(0, originalValue.length - fileName.length - 1);
		}

		var qs = 'folder=' + folder;
		if(originalValue)	qs += '&file=' + originalValue;
		if(imageOnly)		qs += '&image=1';

		if(files && files.length) {
			var file = files[0];
		    jQuery.ajax({
	  			url: "/admin/data/uploadfile/?filename="+ file.fileName + "&" + qs,
				type: "POST",
				processData: false,
				data: file,
				complete: function(r, t) {
					var value = jQuery('file', r.responseXML).attr('path');
					if(value) {
						if(typeof value == 'object') value = value[0];
						newValue = value ? value.toString() : '';
						self.finish(true);
					} else self.finish();
				}
	  		});
		} else {

			jQuery.ajax({
				url: "/admin/data/get_filemanager_info/",
				data: "folder=" + folder + '&file=' + originalValue,
				dataType: 'json',
				complete: function(data){
					data = eval('(' + data.responseText + ')');
					var folder_hash = data.folder_hash;
					var file_hash = data.file_hash;
					var lang = data.lang;
					var fm = data.filemanager;

					var functionName = 'draw' + fm + 'ImageEditor';
					eval(functionName + '(qs, lang, folder_hash, file_hash)');
		}
			});


		}
	};

	var drawflashImageEditor = function (qs, lang, folder_hash, file_hash) {

			jQuery.openPopupLayer({
				name   : "Filemanager",
			title  : getLabel('js-file-manager'),
				width  : 660,
				height : 460,
				url    : "/styles/common/other/filebrowser/umifilebrowser.html?" + qs,
				afterClose : function (value) {
					if(value) {
						if(typeof value == 'object') value = value[0];
						newValue = value ? value.toString() : '';
						self.finish(true);
					} else self.finish();
				}
			});
	};

	var drawelfinderImageEditor = function (qs, lang, folder_hash, file_hash) {

		qs = qs + '&lang=' + lang + '&file_hash=' + file_hash + '&folder_hash=' + folder_hash;

		jQuery.openPopupLayer({
			name   : "Filemanager",
			title  : getLabel('js-file-manager'),
			width  : 660,
			height : 530,
			url    : "/styles/common/other/elfinder/umifilebrowser.html?" + qs,
			afterClose : function (value) {
				if(value) {
					if(typeof value == 'object') value = value[0];
					newValue = value ? value.toString() : '';
					self.finish(true);
				} else self.finish();
		}
		});

		jQuery('#popupLayer_Filemanager .popupBody').append('<div id="watermark_wrapper"><label for="add_watermark">' + getLabel('js-water-mark') + '</label><input type="checkbox" name="add_watermark" id="add_watermark"></div>');
	};

	var drawDateEditor = function () {
		var date = Date.createDate(originalValue);
		if(!date) date = new Date;
		originalValue = date.getFormattedDate(true);

		drawTextEditor();

		var textFinish = self.finish;

		var position = uPageEditor.nodePositionInfo(domNode);
		var node = jQuery('#u-datepicker-input');
		if(!node.size()) {
			node = document.createElement('input');
			node.id = 'u-datepicker-input';
			document.body.appendChild(node);
		}

		self.finish = function (apply) {
			jQuery(node).datepicker('destroy');
			jQuery('.ui-datepicker-trigger').remove();
			textFinish(apply);
		};

		jQuery(node).css({
			'position':		'absolute',
			'left':			(position.x + position.width + 5),
			'top':			(position.y),
			'width':		'1',
			'height':		'1',
			'visibility':	'hidden',
			'font-size':	'62.5%',
			'z-index':		560
		});

		var time = date.getFormattedDate(false);
		jQuery(node).datepicker(jQuery.extend({
			showOn: 'button',
			buttonImage: '/styles/common/other/calendar/icons_calendar_buttrefly_eip.png',
			buttonImageOnly: true,
			'dateFormat':	'yy-mm-dd',
			'defaultDate':	date,
			'onSelect': function (dateText) {
				newValue = dateText + ' ' + time;
				jQuery(domNode).html(newValue);
				jQuery(domNode).focus();
			}
		}, jQuery.datepicker.regional["ru"]));

		//jQuery('#u-datepicker').datepicker('enable');
		uPageEditor.placeWith(domNode, jQuery('.ui-datepicker-trigger'), 'right', 'middle');

	};

	var drawRelationEditor = function () {
		jQuery(document).one('mouseup', function () {
			setTimeout(function () {
				bindFinishEvent();
			}, 100);
		});
		setTimeout(function () {
			jQuery(document).die('mouseup');
			bindFinishEvent();
		}, 1000);

		var position = uPageEditor.nodePositionInfo(domNode);
		var selectBox = document.createElement('select');
		var searchBox = document.createElement('input');
		document.body.appendChild(selectBox);

		if(fieldParams['guide-id'] && fieldParams['public']) {
			var relationButton = document.createElement('input');
			relationButton.type = 'button';
			relationButton.value = ' ';
			relationButton.id = 'relationButton' + fieldParams['guide-id'];
			relationButton.className = 'relationAddButton';
			document.body.appendChild(relationButton);
		}

		jQuery(selectBox).attr('class', 'eip-ui-element');
		jQuery(selectBox).css({
			'position':		'absolute',
			'left':			position.x,
			'top':			position.y,
			'z-index':		1100
		});


		uPageEditor.applyStyles(domNode, selectBox);
		jQuery(domNode).css('visibility', 'hidden');

		if(fieldParams['multiple']) {
			jQuery(selectBox).attr('multiple', 'multiple');
			jQuery(selectBox).attr('size', 3);
		}

		var i;
		for(i in originalValue) {
			var option = document.createElement('option');
			jQuery(option).attr('value', i);
			jQuery(option).attr('selected', 'selected');
			jQuery(option).html(originalValue[i]);
			selectBox.appendChild(option);
		}


		jQuery(selectBox).focus();
		jQuery(selectBox).attr('name', 'rel_input');
		jQuery(selectBox).attr('id', 'relationSelect' + fieldParams['guide-id']);

		document.body.appendChild(searchBox);


		uPageEditor.applyStyles(domNode, searchBox);
		jQuery(searchBox).attr({
			'id':		'relationInput' + fieldParams['guide-id'],
			'class':	'eip-ui-element',
			'name':		'rel_input_new'
		});

		var sourceUri = uRevisionsQueue.getEditUri(fieldName, 'guides');
		var control = new relationControl(fieldParams['guide-id'], null, true, sourceUri);

		self.finish = function () {
			jQuery(domNode).css('visibility', 'visible');

			newValue = control.getValue();
			commit();

			jQuery(selectBox).resizable('destroy');

			jQuery(selectBox).remove();
			jQuery(searchBox).remove();
			jQuery(relationButton).remove();
			jQuery('#u-ep-search-trigger').remove();

			self.cleanup();
		};

		control.loadItemsAll();

		if(fieldParams['multiple']) {
			var minHeight = jQuery(selectBox).height(), maxHeight = 350;
			if(minHeight < 150) {
				minHeight = 75;
				jQuery(selectBox).css('height', minHeight);
			}

			jQuery(selectBox).resizable({
				'minWidth':	jQuery(selectBox).width(),
				'maxWidth':	jQuery(selectBox).width(),
				'minHeight':	minHeight,
				'maxHeight':	maxHeight
			});

			jQuery('.ui-wrapper').css('z-index', '1100');
		}
		jQuery(searchBox).css({
			'position'	: 'absolute',
			'width'		: jQuery(selectBox).width(),
			'left'		: position.x,
			'top'		: (position.y + jQuery(selectBox).height()+5),
			'z-index'	: 1111
		});
		jQuery(relationButton).css({
			'position' : 'absolute',
			'left'     : (position.x + jQuery(searchBox).width() + 5),
			'top'      : (position.y + jQuery(selectBox).height() + Math.round((jQuery(searchBox).height() - jQuery(relationButton).height()) / 2)),
			'z-index'  : 1112
		});
	};

	var drawSymlinkEditor = function () {
		alert("В данный момент поля типа \"Ссылка на дерево\" редактировать через edit-in-place нельзя");
	};

	var drawBooleanEditor = function () {
		var position = uPageEditor.nodePositionInfo(domNode);

		if(jQuery(domNode).attr('tagName') == 'INPUT' && jQuery(domNode).attr('type') == 'checkbox') {
			setTimeout(function () {
				newValue = originalValue ? 0 : 1;
				jQuery(domNode).attr('checked', newValue);
				commit();
				self.cleanup();
			}, 300);
			return;
		}

		var checkboxNode = document.createElement('input');
		checkboxNode.type = 'checkbox';
		document.body.appendChild(checkboxNode);

		self.finish = function (apply) {
			if(apply) {
				newValue = checkboxNode.checked ? 1 : 0;
				commit();
			}

			jQuery(checkboxNode).remove();
			jQuery(domNode).css('visibility', 'visible');
			self.cleanup();
		};

		checkboxNode.checked = originalValue;
		jQuery(checkboxNode).attr('class', 'eip-ui-element eip-ui-boolean');
		jQuery(checkboxNode).css({
			'position':		'absolute',
			'left':			position.x,
			'top':			position.y,
			'z-index':		1100
		});
		uPageEditor.applyStyles(domNode, checkboxNode);
		jQuery(domNode).css('visibility', 'hidden');

		jQuery(checkboxNode).click(function () { self.finish(true); });
	};


	var init = function (node) {
		if(editBox.currentInstance) {
			editBox.currentInstance.finish(true);
		}
		editBox.currentInstance = self;

		domNode = node;

		if(ids = uPageEditor.searchAttr(node, 20, true)) {
			if(ids['element-id']) elementId = parseInt(ids['element-id']);
			if(ids['object-id']) objectId = parseInt(ids['object-id']);
		}
		fieldName = jQuery(node).attr('umi:field-name');

		if(!elementId && !objectId) alert("You should specify umi:element-id or umi:object attribute");
		if(!fieldName) alert("You should specify umi:field-name attribute");

		//Load current value from revision or from server
		editorInstance.queue.getValue({
			'element-id':	elementId,
			'object-id':	objectId,
			'field-name':	fieldName,
			'field-type':	fieldType
		}, onLoadValue);
	};


	boxDomNode = boxNode;
	editorInstance = editor;
	init(node);

};
editBox.currentInstance = null;
