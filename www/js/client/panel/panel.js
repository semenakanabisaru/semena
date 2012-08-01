var uPanel = function (_params) {
	var params = _params, self = this, editor = null;

	var renderPanel = function () {
		jQuery(params['placeholder']).html(uPanel.getSource());

		//TODO ???
		if(jQuery.cookie('eip-panel-state') == 'collapsed') {
			//self.swap(jQuery('#u-show_hide_btn'));
		}
		//TODO ???

		// DONE ostapenko
		// work for for a mounth
		if (!jQuery.cookie('eip-panel-state-first')) {
            //function collapse without animation
            var quickpanel = jQuery("#u-quickpanel");
            quickpanel.css('overflow', 'hidden');
            quickpanel.css('height', '0');
            jQuery('#u-show_hide_btn').addClass('collapse');
            //function expand with delay
            var quickpanel = jQuery("#u-quickpanel");
            quickpanel.delay(500).animate({
                height: "25px"
            }, 500, function(){
                jQuery(this).css('overflow', 'visible');
                jQuery('#u-show_hide_btn').removeClass('collapse');
            });
            quickpanel.fadeTo(300, 0.3);
            quickpanel.fadeTo(300, 1);
            jQuery.cookie('eip-panel-state', '', {
                path: '/',
                expires: 0
            });
            // set first expand cookie
            var date = new Date();
            date.setTime(date.getTime() + (30 * 24 * 60 * 60 * 1000));
            jQuery.cookie('eip-panel-state-first', 'Y', {
                path: '/',
                expires: date
            });
        }
		//
		jQuery('#u-show_hide_btn').click(function () {
			self.swap(this);
		});

		jQuery('#u-quickpanel #last_doc, #changelog_dd').click(function () {
			changeClassName(this);
		});

		jQuery('#u-quickpanel #meta').click(function  () {
			changeClassName(this);
			uPageEditor.get().enableMETA();
			return false;
		});

		jQuery('#save_meta_button').click(function() {
			uPageEditor.get().submitMeta();
			return false;
		});

		jQuery('#u-quickpanel #edit').click(function () {
			self.swapEditor();
		});

		jQuery('#on_edit_in_place').click(function () {
			self.swapEditor();
		});

		jQuery('#u-quickpanel #save_edit #save').click(function() {
			uPageEditor.get().submit();
			return false;
		});

		jQuery('#u-quickpanel #save_edit #edit_back').click(function () {
			uPageEditor.get().back(1);
			return false;
		});

		jQuery('#u-quickpanel #save_edit #edit_next').click(function () {
			uPageEditor.get().forward(1);
			return false;
		});

		jQuery('#u-quickpanel #exit').click(function  () {
			window.location = '/users/logout/';
			return false;
		});

		jQuery('#u-quickpanel #note').click(function  () {
			if(!window.ticketCreated) {
				alert(getLabel('js-panel-note-add'));
				window.ticketCreated = true;
			}
			window.initNewTicket();
			return false;
		});

		jQuery('#u-quickpanel #help').click(function  () {
			window.open("http://help.umi-cms.ru/index.html?admin_panel.htm");
			return false;
		});

		if(jQuery.cookie('eip-editor-state')) {
			changeClassName(jQuery('#u-quickpanel #edit'));
			self.swapEditor();
		}

		jQuery('#u-quickpanel #seo').click(function () {
			window.location.href = '/admin/seo/';
		});

	};

	var onLoadData = function (data) {
		jQuery('recent page', data).each(function (index, page) {
			var node = document.createElement('li');
			var name = jQuery('name', page).text();
			var link = jQuery(page).attr('link');
			jQuery(node).html("<a href='" + link + "'>" + name + "</a>");
			jQuery('ul#u-docs-recent').append(node);
		});

		var i = 0;
		jQuery('editable page', data).each(function (index, page) {
			var module = jQuery('basetype', page).attr("module");
			var hasPerm = false;
			jQuery('modules module', data).each(function () {
				if(jQuery(this).text() == module) {
					hasPerm = true;
				}
			});
			if(!hasPerm) return;

			var node = document.createElement('li');
			var name = jQuery('name', page).text();
			var link = jQuery('edit-link', page).text();
			if (langPrefix!='') {
				link = "/" + langPrefix + link;
			}
			jQuery(node).html("<a href='" + link + "'>" + name + "</a>");
			jQuery('#u-quickpanel ul#u-docs-edit').append(node);
			i++;
		});

		if(i) {
			jQuery("#u-quickpanel #edit_menu").click(function(){
				changeClassName(this);
			});
		} else {
			jQuery("#u-quickpanel #edit_menu").hide(0);
		}

		i = 0;
		jQuery('modules module', data).each(function (index, module) {
			var node = document.createElement('li');

			var label = jQuery(module).attr('label');
			var name = jQuery(module).text();
			var link = '/admin/' + name + '/';
			if (langPrefix!='') {
				link = "/" + langPrefix + link;
			}
			var type = jQuery(module).attr('type');

			var selector;
			if(type == 'system') {
				selector = 'ul#u-mods-admin';
			} else if(type == 'util') {
				selector = 'ul#u-mods-utils';
			} else {
				selector = (++i % 2) ? 'ul#u-mods-cont-left' : 'ul#u-mods-cont-right';
			}

			jQuery(node).html("<a href='" + link + "'>" + label + "</a>");
			jQuery('#u-quickpanel ' + selector).append(node);
		});
		if(i) {
			jQuery("#u-quickpanel #butterfly").click(function(){ changeClassName(this); }).addClass("butterfly_hover");
		}

		var currentLocation = window.location.pathname;

		if(jQuery('changelog', data).size() > 0) {
			jQuery('changelog revision', data).each(function (index, revision) {
				var node = document.createElement('li');
				var time = jQuery('date', revision).text();
				var login = jQuery('author', revision).attr('name');
				var link = jQuery('link', revision).text();
				var active = (jQuery(revision).attr('active') == 'active');

				var label = time;
				if(login) {
					label += ' - ' + login;
				}

				link += '?force-redirect=' + window.location.pathname;

				if(active) {
					label += '&nbsp;&nbsp;&nbsp;&larr;';
				}

				jQuery(node).html("<a href='" + link + "'>" + label + "</a>");
				jQuery('#u-changelog').append(node);
			});

			jQuery('#changelog_dd').css('display', '');
		} else {
			jQuery('#changelog_dd').css('display', 'none');
		}

		if(jQuery('tickets', data).size() && (typeof tickets != 'undefined')) {
			tickets(jQuery, data);
		} else {
			jQuery('#u-quickpanel #note').remove();
		}
	};

	var loadData = function () {
		var url = '/admin/content/frontendPanel.xml?links';
		url += '&ts=' + Math.round(Math.random() * 1000);

		jQuery.get(url, {
			referer: window.location.toString()
		}, onLoadData);
	};

	var uPageEditorOnInit = function () {
		var h = (typeof uPageEditor.onEnable == 'function') ? uPageEditor.onEnable : function () {};
		uPageEditor.onEnable = function () {
			h();
			changeClassName(jQuery('#edit'));
		};

		var h = (typeof uPageEditor.onDisable == 'function') ? uPageEditor.onDisable : function () {};
		uPageEditor.onDisable = function () {
			h();
			changeClassName(jQuery('#edit'));
		};

		uPageEditor.onStep = function (index, size) {
			jQuery('#u-quickpanel #save_edit #edit_back').attr('class', (index == -1) ? '' : 'ac');
			jQuery('#u-quickpanel #save_edit #edit_next').attr('class', ((size - index) == 1) ? '' : 'ac');
		};
	};

	renderPanel();
	uPageEditorOnInit();
	loadData();
};

uPanel.prototype.swapEditor = function () {
	var editor = uPageEditor.get();

	if(editor.isEnabled()) {
		editor.disable();
		jQuery('#u-quickpanel #edit').html('<span class="in_ico_bg">&#160;</span>' + getLabel('js-panel-edit') + ' (F2)');
		jQuery('#on_edit_in_place').html(getLabel('js-on-eip'));
	} else {
		editor.enable();
		jQuery('#u-quickpanel #edit').html('<span class="in_ico_bg">&#160;</span>' + getLabel('js-panel-view') + ' (F2)');
		jQuery('#on_edit_in_place').html(getLabel('js-off-eip'));
	}
};


uPanel.prototype.swap = function (el) {
    var quickpanel_height = jQuery("#u-quickpanel").css("height");
    if (quickpanel_height == "0px") {
        return this.expand(el);

    } else {
        if(  uPageEditor.get().isEnabledMeta()) {
            jQuery('#u-quickpanel #meta').trigger("click");
        }
        return this.collapse(el);
    }
};

uPanel.prototype.expand = function (el) {
    var quickpanel = jQuery("#u-quickpanel");
    quickpanel.css('overflow', 'visible');
    quickpanel.animate({height:"25px"}, 700);
    jQuery(el).removeClass('collapse');

    jQuery.cookie('eip-panel-state', '', { path: '/', expires: 0});
};

uPanel.prototype.collapse = function (el) {
    var quickpanel = jQuery("#u-quickpanel");
    quickpanel.css('overflow', 'hidden');
    quickpanel.animate({height:"0"}, 700);
    jQuery(el).addClass('collapse');

	var date = new Date();
	date.setTime(date.getTime() + (30 * 24 * 60 * 60 * 1000));
	jQuery.cookie('eip-panel-state', 'collapsed', { path: '/', expires: date});
};

uPanel.loadRes = function (type, src, callback) {
	var node;
	switch(type) {
		case 'js': case 'text/javascript':
			node = document.createElement('script');
			node.src = src;
			node.charset = 'utf-8';
			break;

		case 'css': case 'text/css':
			node = document.createElement('link');
			node.href = src;
			node.rel = 'stylesheet';
			break;
		default: return;
	}

	document.body.parentNode.firstChild.appendChild(node);
	if(typeof callback == 'function') jQuery(document).one('ready', callback);
};

uPanel.getSource = function () {
	var str = '<div id="u-show_hide_btn" />\
	<div id="u-quickpanel">\
	<div id="exit" title="' + getLabel('js-panel-exit') + '">&#160;</div>\
	<div id="help" title="' + getLabel('js-panel-documentation') + '">&#160;</div>\
	<div id="butterfly">\
		<span class="in_ico_bg">&#160;</span>' + getLabel('js-panel-modules') + '\
		<div class="bg">\
			<ul id="u-mods-cont-left" />\
			<ul id="u-mods-cont-right" />\
			<div class="clear separate" />\
			<ul id="u-mods-utils" />\
			<ul id="u-mods-admin" />\
			<div class="clear" />\
		</div>\
	</div>\
	<div id="edit"><span class="in_ico_bg">&#160;</span>' + getLabel('js-panel-edit') + ' (F2)' + '</div>\
	<div id="save_edit">\
		<div id="save" title="' + getLabel('js-panel-save') + '">&#160;</div>\
		<div id="edit_back" title="' + getLabel('js-panel-cancel') + '">&#160;</div>\
		<div id="edit_next" title="' + getLabel('js-panel-repeat') + '">&#160;</div>\
	</div>\
	<div id="edit_menu" title="' + getLabel('js-panel-edit-menu') + '">\
		<span class="in_ico_bg">&#160;</span>\
		<div>\
			<ul id="u-docs-edit"/>\
			<span class="clear" />\
		</div>\
	</div>\
	<div id="last_doc">\
		<span class="in_ico_bg" />\
		' + getLabel('js-panel-last-documents') + '\
		<div>\
			<ul id="u-docs-recent" />\
			<span class="clear" />\
		</div>\
	</div>\
	<div id="changelog_dd" style="display:none;">\
		<span class="in_ico_bg">&#160;</span>\
		' + getLabel('js-panel-history-changes') + '\
		<div>\
			<ul id="u-changelog" />\
			<span class="clear" />\
		</div>\
	</div>\
	<div id="note">\
		<span class="in_ico_bg">&#160;</span>\
		' + getLabel('js-panel-note') + '\
	</div>';

	var has_meta = typeof EIP_META != "undefined" && EIP_META.element_id;
	if(has_meta) {
	  str += '<div id="meta">\
		<span class="in_ico_bg">&#160;</span>\
		' + getLabel('js-panel-meta') + '\
	  </div>\
	  ';

	}
	str += '<div id="seo">\
		<span class="in_ico_bg">&#160;</span>\
		' + getLabel('module-seo') + '\
	</div>';

str += '</div>';
	if(has_meta) {
		str += '<div id="u-quickpanel-meta">\
				<table>\
					<tr><td width="100px">'+getLabel('js-panel-meta-altname')+': </td><td><input type="text" name="alt_name" id="u-quickpanel-metaaltname" value="'+EIP_META.alt_name+'"/> <div class="meta_count" id="u-quickpanel-metaaltname-count"/></td></tr>\
					<tr><td width="100px">'+getLabel('js-panel-meta-title')+': </td><td><input type="text" name="title" id="u-quickpanel-metatitle" value="'+EIP_META.title+'"/> <div class="meta_count" id="u-quickpanel-metatitle-count"/></td></tr>\
					<tr><td>'+getLabel('js-panel-meta-keywords')+': </td><td><input type="text" name="meta_keywords" id="u-quickpanel-metakeywords" value="'+EIP_META.meta_keywords+'"/><div class="meta_count" id="u-quickpanel-metakeywords-count"/><div class="meta_buttons"><a href="/admin/seo/" style="color:white;">'+getLabel('js-panel-meta-analysis')+'</a></div></td></tr>\
					<tr><td>'+getLabel('js-panel-meta-descriptions')+':</td><td> <input type="text" name="meta_descriptions" id="u-quickpanel-metadescription" value="'+EIP_META.meta_descriptions+'"/> \
						<div class="meta_count" id="u-quickpanel-metadescription-count"/>\
						<div class="meta_buttons">\
						<input type="submit"  id="save_meta_button"  value="'+getLabel('js-panel-save')+'">\
						</div>\
					</td></tr>\
				</table>\
				</div>';
	}
	return str;
};

jQuery(document).ready(function () {
	var placeholder = jQuery('#u-panel-holder');
	if(placeholder.size() == 0) {
		var div = document.createElement('div');
		div.id = 'u-panel-holder';
		document.body.appendChild(div);
		placeholder = jQuery('#u-panel-holder');
	}
	jQuery('html').addClass('u-eip');

	var panel = new uPanel({
		'placeholder':		placeholder
	});

	uPageEditor.get().bindEventsMeta();

	jQuery(document).keypress(function (e) {
		var code = e.charCode || e.keyCode;

		if(e.shiftKey && (code == 68 || code == 100 || code == 1042 || code == 1074)) {
			jQuery('#u-quickpanel #edit_menu').each(function (i, node) {
				changeClassName(node);
			});
		}
	});
});

function changeClassName(el) {
	var editor = uPageEditor.get()

	var eCond = (editor.isEnabled()) ? '[id != \'edit\']' : '';

	if (!jQuery(el).hasClass('act')) {
		var act_arr = jQuery('#u-quickpanel .act');
		var opera_width = false;
		if (act_arr.size()) {
			jQuery('#u-quickpanel .act div:first').hide();
			jQuery('#u-quickpanel .act' + eCond).removeClass('act');
			if (el.id == 'edit' && !eCond)
				jQuery("#save_edit").css('display', 'none');
		}
		if (jQuery.browser.opera) {
			opera_width = jQuery(el).width();
		}
		jQuery(el).addClass('act');
		if (opera_width) jQuery(el).width(opera_width);
		jQuery('#u-quickpanel .act div:first').show();
		if (jQuery(el).attr('id') == 'edit')
			jQuery("#save_edit").css('display', 'block');
	}
	else {
		jQuery('#u-quickpanel .act div:first').hide();
		jQuery('#u-quickpanel .act' + eCond).removeClass('act');
		if (jQuery(el).attr('id') == 'edit')
			jQuery("#save_edit").css('display', 'none');
	}

	if(jQuery(el).attr("id")!="meta" && editor.isEnabledMeta()) {
		jQuery('#u-quickpanel #meta').addClass('act');
	}

};
