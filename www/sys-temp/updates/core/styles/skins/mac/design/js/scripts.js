// Events binding
jQuery(document).ready(function() {
	jQuery('a.unrestorable').click(function (e) {
		var link = jQuery(e.target).attr('href');

		openDialog({
			'title': getLabel('js-delete-confirm'),
			'text': getLabel('js-confirm-unrecoverable-del'),
			'OKText': getLabel('js-confirm-unrecoverable-yes'),
			'cancelText': getLabel('js-confirm-unrecoverable-no'),
			'OKCallback': function () {
				window.location = link;
			}
		});

		return false;
	});


	// Bind modules menu show method to the Butterfly button
	var toggleMenuVisibility = function() {
		var el = jQuery("#butterfly");
		if (!el.hasClass('act')) {
			el.addClass('act');
			jQuery('div:first', el).show(0);
			jQuery(document).bind('click', {}, toggleMenuVisibility );
			Control.enabled = false;
		} else {
			jQuery('div:first', el).hide(0);
			el.removeClass('act');
			jQuery(document).unbind('click', toggleMenuVisibility );
			Control.enabled = true;
		}
		return false;
	}
	jQuery("#butterfly").click(toggleMenuVisibility);
	jQuery("#butterfly > div").click(function(event){ event.stopPropagation(); });

	// Init dock

	SettingsStore.getInstance().addEventHandler("onloadcomplete", function() {
		var modules = SettingsStore.getInstance().get("dockItems");
		if(modules) {
			modules = modules.split(";");
			var div = jQuery("#dock div:first");
			for(var i=0; i<modules.length; i++) {
				if(!modules[i].length) continue;
				var title = getLabel("module-" + modules[i]);
				div.append("<a href=\""+window.pre_lang+"/admin/"+modules[i]+"/\" title=\"" + title + "\" xmlns:umi=\"http://www.umi-cms.ru/TR/umi\" umi:module=\""+modules[i]+"\"><img title=\"" + title + "\" src=\"/images/cms/admin/mac/icons/medium/"+modules[i]+".png\" alt=\"" + title + "\" /><span>" + title + "</span></a>");
			}
		}
	});


	jQuery("#dock > img").click(function() {
		var div    = jQuery("#dock div:first");
		var img    = jQuery("#dock > img:first");
		var height = div.get(0).offsetHeight;
		div.slideToggle(0, function() {
							if(height) {
								img.attr("src", "/images/cms/admin/mac/common/doc_open.png");
								SettingsStore.getInstance().set("dockState", "closed");
							} else {
								img.attr("src", "/images/cms/admin/mac/common/doc_close.png");
								SettingsStore.getInstance().set("dockState", "opened");
							}
						});
		updateDockPosition();
	});

	jQuery(window).scroll(function(){
		updateDockPosition();
	});

	jQuery(window).resize(function(){
		updateDockPosition();
	});

	function updateDockPosition() {
		var dock = jQuery('div#dock'),
			main = jQuery('div#main');
		if (jQuery(window).scrollTop() > 0) {
			var dock_left   = jQuery(window).scrollLeft(),
				dock_height = dock.height() + 25,
				main_width  = main.width();
			main.css({
				'padding-top' : dock_height,
				'min-height'  : '0'
			});
			dock.css({
				'position' : 'fixed',
				'left'     : '-' + dock_left,
				'width'    : main_width
			});
		}
		else {
			main.css({
				'padding-top': 0,
				'min-height'  : '100%'
			});
			dock.css({
				'position' : 'relative',
				'left'     : 0,
				'width'    : '100%'
			});
		}
	}

	function updateDockStore() {
		var modules = [];
		$("#dock div:first a").each( function() {modules[modules.length] = $(this).attr('umi:module');} );
		SettingsStore.getInstance().set("dockItems", modules.join(";"));
	}

	// Make dock items sortable and menu items draggable to dock
	var outItem = null;

	jQuery("#dock div:first").sortable({
		containment : 'window',
		tolerance  : 'pointer',
		revert     : false,
		placeholder: 'ui-state-highlight',
		deactivate : function (event, ui) {
						if(ui.helper) return;
						var e = jQuery(ui.item);
						var i = jQuery("img", e)[0];
						i.src = i.src.replace(/\/small\//, "/medium/");
						jQuery(e[0].childNodes[1]).wrap("<span>");
						updateDockStore();
					 },
		out  : function(event, ui) { outItem = ui.item; },
		over : function() { outItem = null; },
		beforeStop : function() { if(outItem) { jQuery(outItem).remove(); outItem = null; updateDockStore(); } },
		stop : function() { if(SettingsStore.getInstance().get("dockState") == "closed") { jQuery("#dock div:first").slideUp(0, function() { jQuery("#dock > img:first").attr("src", "/images/cms/admin/mac/common/doc_open.png"); }); } }
	}
	).disableSelection().slideUp(0);

	jQuery("#butterfly a").draggable({
		connectToSortable : "#dock div:first",
		containment : 'document',
		helper : 'clone',
		revert : 'invalid',
		start   : function(event, ui) {
			var e      = jQuery(ui.helper[0]);
			var module = e.attr('umi:module');
			jQuery("#dock div:first a").filter( function() { return jQuery(this).attr('umi:module') == module; } ).remove();
			e.addClass('drag');
			var i = jQuery("img", e)[0];
			i.src = i.src.replace(/\/small\//, "/medium/");
			e.empty();
			e.append(i);
			jQuery("#dock div:first").slideDown(0);
		}
	}).disableSelection();

	SettingsStore.getInstance().addEventHandler("onloadcomplete", function() {
		if(SettingsStore.getInstance().get("dockState") == "opened") {
			jQuery("#dock div:first").slideDown(0, function() {
					jQuery("#dock > img:first").attr("src", "/images/cms/admin/mac/common/doc_close.png");
			});
		} else {
			$("#dock > img:first").attr("src", "/images/cms/admin/mac/common/doc_open.png");
		}
	});

	//----------------------------------------------------
	// Properties group
	jQuery("div.properties-group .header").click(function(){
		var group = jQuery(this).parent();
		group.children(".content").toggle(0);
		if(group.children(".content:visible").size()) {
			setCookie(group.attr("name"), null);
			jQuery(this).children(".c").css({backgroundImage:''});
		} else {
			setCookie(group.attr("name"), "1");
			jQuery(this).children(".c").css({backgroundImage:'url(/images/cms/admin/mac/sg_arrow_down.gif)'});
		}
	}).each(function() {
		var group = jQuery(this).parent();
		if(getCookie(group.attr("name"))) {
			group.children(".content").hide(0);
		}
	});
	//----------------------------------------------------
	// Sharing
	$("div.properties-group .content .share").click(function(){
		var root = $(this).parent();
		root.children("#ya_share1").toggle(0);
		if(root.children("#ya_share1:visible").size()) {
			setCookie(root.attr("class"), null);
			$(".share .switch").attr('textContent', '»');
		} else {
			setCookie(root.attr("class"), "1");
			$(".share .switch").attr('textContent', '«');
		}
	}).each(function() {
		var root = $(this).parent();
		if(getCookie(root.attr("class"))) {
			root.children("#ya_share1").attr('style', 'display:none');
			$(".share .switch").attr('textContent', '«');
		}
	});

	//----------------------------------------------------
	// Extended fields
	var extendedFieldsContainer = jQuery("div.extended_fields");
	jQuery("a.extended_fields_expander").click(function(){
		if(!getCookie("expandExtendedFields")||extendedFieldsContainer.css("display")=="none") {
			extendedFieldsContainer.css("display","");
		} else {
			extendedFieldsContainer.css("display","none");
		}
		if(extendedFieldsContainer.css("display") == "none") {
			setCookie("expandExtendedFields", null);
			jQuery(this).text(getLabel("js-fields-expand"));
		} else {
			setCookie("expandExtendedFields", "1");
			jQuery(this).text(getLabel("js-fields-collapse"));
		}
	});
	if(getCookie("expandExtendedFields") || location.href.indexOf("/add/") != -1) {
		extendedFieldsContainer.css("display","");
		jQuery("a.extended_fields_expander").text(getLabel("js-fields-collapse"));
	} else {
		extendedFieldsContainer.css("display","none");
		jQuery("a.extended_fields_expander").text(getLabel("js-fields-expand"));
	}

	//----------------------------------------------------
	// Help panel
	if(jQuery("#info_block .content").attr("title") == "") {
		jQuery("#head .help").show();
	}
	jQuery("#info_block .content").one("helpopen", {}, function() {
		var e   = jQuery(this);
		var url = e.attr("title").substr(1);
		e.attr("title", "");
		jQuery.get(url, {}, function(data){
			data = data.substr(data.indexOf('<body>') + 6);
			data = data.substr(0, data.indexOf('</body>'));

			e.html( data );
		});
	});
	jQuery("#head .help").click(function(){
		jQuery("#info_block").toggle(0, function() {
			var e = jQuery(this);
			if(e.css("display") != "none") {
				jQuery("#content").removeClass("content-expanded");
				$('#head .help').addClass('nobg');
				jQuery(".content", e).trigger("helpopen");
				setCookie("help_" + window.location.pathname.replace(/\//gi, "_"), "1");
			} else {
				jQuery("#content").addClass("content-expanded");
				$('#head .help').removeClass('nobg');
				setCookie("help_" + window.location.pathname.replace(/\//gi, "_"), null);
			}
			Control.recalcItemsPosition();
		});
	});
	jQuery("#info_block .header").click(function(){
		jQuery("#head .help").click();
	});
	if(getCookie("help_" + window.location.pathname.replace(/\//gi, "_")) !== null) {
		jQuery("#head .help").click();
	}
	//----------------------------------------------------
	// Common controls initialization

	// Relations
	jQuery("div.relation").each(function() {
		var e = jQuery(this);
		new relationControl(e.attr("umi:type"), e.attr("id"), (e.attr("umi:empty") === "empty") );
	});
	// Symlink
	jQuery("div.symlink").each(function() {
		var e = jQuery(this);
		var l = jQuery("ul", e);

		var label = $('label', e)[0];

		var shTypes = label.className.split(' ');
		var hTypes = [];
		for(var o = 0; o<shTypes.length;o++) {
			hTypes.push( shTypes[o]);
		}

		var s = new symlinkControl(e.attr("id"), "content", ['news', 'rubric'],
							{inputName      : e.attr("name"),
							 fadeColorStart : [255, 255, 225],
							 fadeColorEnd   : [255, 255, 255]},
							 hTypes);
		jQuery("li", e).each(function(){
			var li = jQuery(this);
			s.addItem(li.attr("umi:id"), li.text(), [li.attr("umi:module"), li.attr("umi:method")], li.attr("umi:href"));
		});
		l.remove();
	});
	// Files
	jQuery("div.file").each(function() {
		var e = jQuery(this);
		var defaultFolder = './images/cms/data';
		var options = {
			inputName : e.attr("name"),
			folderHash : e.attr("umi:folder-hash"),
			fileHash : e.attr("umi:file-hash"),
			lang : e.attr("umi:lang"),
			fm : e.attr("umi:filemanager")
		};
		switch( e.attr("umi:field-type") ) {
			case "file"       :
			case "swf_file"   :defaultFolder = './files';break;
			case "video_file" :options.videosOnly = true;defaultFolder = './files/video';break;
			case "img_file"   : {
					options.imagesOnly = true;
					switch( e.attr("umi:name") ) {
						case "header_pic" :defaultFolder = './images/cms/headers';break;
						case "menu_pic_a" :
						case "menu_pic_ua":defaultFolder = './images/cms/menu';break;
					}
			}
		}
		var c = new fileControl( e.attr("id"), options);
		c.setFolder(defaultFolder, true);
		c.setFolder(e.attr("umi:folder"));
		c.add(e.attr("umi:file"), true);
	});
	jQuery("div#filemanager_upload_files a").click(function(){

		var lang = jQuery(this).attr("umi:lang");
		var fm = jQuery(this).attr("umi:filemanager");
		var folder = './files';
		var folderHash = 'umifiles_Lw';

		var functionName = 'show' + fm + 'FileBrowser';
		eval(functionName + '(folder, folderHash, lang)');

	});

	var showflashFileBrowser = function(folder, folder_hash, lang) {
		var qs    = '';

		if(folder) {
			qs = qs + '&folder=' + folder;
		}

		$.openPopupLayer({
			name   : "Filemanager",
			title  : getLabel('js-file-manager'),
			width  : 660,
			height : 460,
			url    : "/styles/common/other/filebrowser/umifilebrowser.html?"+qs
		});
	};

	var showelfinderFileBrowser = function(folder, folder_hash, lang) {
		var qs    = '';

		if(typeof(folder_hash) != 'undefined') {
			qs = qs + '&folder_hash=' + folder_hash;
		}
		if(lang) {
			qs = qs + '&lang=' + lang;
		}
		$.openPopupLayer({
			name   : "Filemanager",
			title  : getLabel('js-file-manager'),
			width  : 660,
			height : 530,
			url    : "/styles/common/other/elfinder/umifilebrowser.html?"+qs
	});

		jQuery('#popupLayer_Filemanager .popupBody').append('<div id="watermark_wrapper"><label for="add_watermark">' + getLabel('js-water-mark') + '</label><input type="checkbox" name="add_watermark" id="add_watermark"></div>');
	};
	// Date
	jQuery.datepicker.setDefaults(jQuery.extend({showOn			: 'button',
										buttonImage     : '/styles/common/other/calendar/icons_calendar_buttrefly.png',
										buttonImageOnly : true,
										duration		: 0,
										constrainInput  : false,
										dateFormat		: 'yy-mm-dd'}, jQuery.datepicker.regional["ru"]));
	jQuery("div.datePicker").each(function(){
		var input = jQuery("input", jQuery(this));
		input.datepicker({dateFormat : 'yy-mm-dd',
										onClose: function(dateText, inst) {
												if(!/\d{1,2}:\d{1,2}(:\d{1,2})?$/.exec(dateText)) {
												dateText = dateText + " 00:00:00";
											}
												var tempDate = input.val(dateText);
											}
											});
	});
	// Tags
	window.returnNewTag = function(inputId, tag, link) {
		var input = jQuery("#" + inputId);
		if(jQuery(link).hasClass('disabledTag')) {
			jQuery(link).removeClass('disabledTag');
			var tagList = input.val().split(",");
			var result  = [];
			for(var i=0; i<tagList.length; i++) {
				tagList[i] = tagList[i].replace(/^\s*/, "").replace(/\s*$/, "");
				if(tagList[i] !== tag) {
					result.push(tagList[i]);
				}
			}
			input.val( result.join(", ") );
		} else {
			jQuery(link).addClass('disabledTag');
			input.val( input.val() + ", " + tag );
		}
	};
	jQuery("a.tagPicker").each(function(){
		var e = jQuery(this);
		e.click(function(){
			jQuery.openPopupLayer({
				name   : "TagsCloud",
				title  : "Облако тегов",
				width  : 400,
				height : 200,
				url    : window.pre_lang + "/admin/stat/get_tags_cloud/" + e.attr('id').replace(/^link/,"") + "/"
			});
		});
	});
	// WYSIWYG
	jQuery("textarea.wysiwyg").each(function (i, n) {
		tinyMCE.execCommand('mceAddControl', false, jQuery(n).attr('id'));
	});
	// Permissions
	jQuery("#permissionsContainer").each(function () {
        var e = jQuery(this);
        var p = new permissionsControl(e.attr("id"));
        jQuery("ul>li", e).each(function() {
            var li = jQuery(this);
            p.add(li.attr("umi:id"), li.text(), li.attr("umi:access"));
        });
        jQuery("ul", e).remove();
        jQuery("input:submit").removeAttr("disabled");
    });
	// Optioned
	jQuery("table.optioned").each(function () {
		var e = jQuery(this);
		new relationControl(jQuery("select", e).attr("umi:guide"), e.attr("id"));
		jQuery("a.add", e).click(function() {
			jQuery("#empty_value").remove();
			var s  = jQuery("select", e).get(0);
			if(s.selectedIndex == -1) return false;
			var i  = jQuery("tr", e).size()+10;
			var tr = "<tr>";
			tr = tr + "<td>" + s.options[s.selectedIndex].text + "<input type=\"hidden\" name=\"" + jQuery(s).attr("umi:name") + "["+i+"][rel]\" value=\""+s.options[s.selectedIndex].value+"\" /></td>";
			jQuery("tfoot input[type=text]", e).not(":first").each(function(){
				var v = jQuery(this).val();
				tr = tr + "<td class=\"center\">" +
							"<input type=\"text\" name=\"" + jQuery(s).attr("umi:name") + "["+i+"]["+jQuery(this).attr("umi:type")+"]\" value=\""+v+"\" />"+
							"<input type=\"hidden\" name=\"" + jQuery(s).attr("umi:name") + "["+i+"]["+((jQuery(this).attr("umi:type")=="int")?"float":"int")+"]\" value=\"1\" />"+
							"</td>";
				jQuery(this).val("");
			});
			tr = tr + "<td class=\"center narrow\"><a href=\"#\" class=\"remove\"><img src=\"/images/cms/admin/mac/table/ico_del.gif\" /></a></td>";
			tr = tr + "</tr>";
			jQuery("tbody", e).append(tr);
			return false;
		});
		jQuery("a.remove", e).live('click', function() {
			jQuery(this).parents("tr:first").remove();
			var cnt = 0;
			jQuery("tbody tr", e).each(function() {
				cnt++
			});
			if (cnt == 0) {
				var s  = jQuery("select", e).get(0);
				var tr = "<tr id=\"empty_value\"><input type=\"hidden\" value=\"\" name=\"" + jQuery(s).attr("umi:name") +"[1][int]\" umi:type=\"int\"></tr>";
				jQuery("tbody", e).append(tr);
			}
			return false;
		});
	});

	jQuery('.smc-fast-add').click(function () {
		var control = Control.getInstanceById(jQuery(this).attr('ref'));
		var link = jQuery(this).attr('href') + 'fast.xml';

		if(control) {
			jQuery.get(link, null, function () {
				control.dataSet.refresh();
			});
		}
		return false;
	});
	// Type Selector
	jQuery("div.imgButtonWrapper a").filter(function(){ return jQuery(this).attr("umi:type"); }).each(function() {
		var e = jQuery(this);
		if(e.attr("umi:prevent-default") == "true") {
			var f = function(){return false;};
			e.bind({click : f, mousedown : f, mouseup : f, mouseover: f});
		}
		var p = jQuery("<ul xmlns:umi=\"http://www.umi-cms.ru/TR/umi\" class=\"type_select\"></ul>");
		p.css({display : "none", position : "absolute", "z-index" : 10050000});
		jQuery.get("/utype/child/" + e.attr("umi:type"), {}, function(response) {
			jQuery("type", response).each(function(){
				var type = jQuery(this);
				jQuery(p).append("<li><a href='#' umi:type-id='"+ type.attr("id")+"' title='" + type.attr("title") + "'>" + type.attr("title") + "</a></li>");
			});
		});
		p.appendTo("body");
		e.add(p).bind({
			mouseover : function() {
				Control.enabled = false;
				var offset = e.offset();
				p.css({display : "block",
						top    : offset.top + e.height(),
						left   : offset.left,
						width : e.innerWidth()});
				e.addClass("type_select_active");
				jQuery("a", p).each(function(){
					var basehref = e.attr("href");
					var a = jQuery(this);
					var li = a.parent();
					var width = parseInt(li.innerWidth()) - parseInt(li.css("padding-left")) - parseInt(li.css("padding-right"));
					a.attr("href", basehref + (basehref.indexOf("?") >= 0 ? "&" : "?") + "type-id=" + a.attr("umi:type-id"));
					a.css("width", width);
				});
			},
			mouseout  : function() {
				p.css("display", "none");
				e.removeClass("type_select_active");
				Control.enabled = true;
			}
		});
	});

	jQuery('input.discount-type-id').bind('click', function () {
		var discountTypeId = jQuery(this).attr('value');

		jQuery('div.discount-params input').attr('disabled', true);
		jQuery('div.discount-params').css('display', 'none');

		jQuery('div.discount-params#' + discountTypeId + ' input').attr('disabled', false);
		jQuery('div.discount-params#' + discountTypeId + '').css('display', '');
	});
	// Sync name/alt-name/H1 fields
	if(window.is_page) {
		var iname = $("input:text[name=name]");
		var ialt  = $("input:text[name=alt-name]");
		var ih1   = $("input:text[name$='[h1]']");
		var callback = null;
		var changeAvailable = true;
		var separator = '';
		$.ajax({
			type: "POST",
			url: '/udata/system/getSeparator',
			async: false,
			success: function (data) {
				separator = $('separator', data).attr("value");
			}
		});
		if(window.is_new || ialt.val() == '') {
			callback = function() {
				if (changeAvailable) {
					var pattern = "[^A-Za-z0-9"+separator+"]+", reg = new RegExp(pattern, 'gi');
					ialt.val(transliterateRu(this.value.toLowerCase()).replace(/\s+/g, separator).replace(reg, ""));
				}

				ih1.val(this.value);
			};
		} else {
			callback = function() {
				ih1.val(this.value);
			};
		}
		iname.focus(function() {
						if(window.is_new || (ih1.val() === iname.val())|| ialt.val() == '')
							jQuery(this).bind("keyup", callback);
					})
			  .blur(function() { jQuery(this).unbind("keyup", callback); });

		ialt.change(function() {
			changeAvailable = false;
			if(ialt.val() == '') changeAvailable = true;
		});
	}

});

//-----------------------------------------------------------------------------
// Confirm windows helpers
//-----------------------------------------------------------------------------
function openDialog(options) {
	var confirmId    = Math.round( Math.random() * 100000 );
	var _opt = {
				name        : 'macConfirm' + confirmId,
				title       : "",
				text        : "",
				width       : 300,
				closeButton : true,
				stdButtons  : true,
				OKText      : "OK",
				cancelText  : getLabel("js-cancel"),
				OKCallback	   : null,
				cancelCallback : null
	};
	jQuery.extend(_opt, options);
	var skin =
"<div class=\"eip_win_head popupHeader\" onmousedown=\"jQuery('.eip_win').draggable({containment: 'document'})\">\n\
	"+(_opt.closeButton?"<div class=\"eip_win_close popupClose\" onclick=\"javascript:jQuery.closePopupLayer('macConfirm"+confirmId+"')\">&#160;</div>":"")+"<div class=\"eip_win_title\">"+_opt.title+"</div>\n\
</div>\n\
<div class=\"eip_win_body popupBody\" onmousedown=\"jQuery('.eip_win').draggable('destroy')\">\n\
	<div class=\"popupText\">"+_opt.text+"</div>\n"+
	(_opt.stdButtons ?
	"<div class=\"eip_buttons\">\n\
		<input type=\"button\" class=\"primary ok\" value=\""+_opt.OKText+"\"  onclick=\"confirmButtonOkClick('"+_opt.name+"', "+confirmId+")\" />\n\
		<input type=\"button\" class=\"back\" value=\""+_opt.cancelText+"\" onclick=\"confirmButtonCancelClick('"+_opt.name+"', "+confirmId+")\" />\n\
		<div style=\"clear: both;\"/>\
	</div>" : "" ) +
"</div>";
	window['macConfirm'+confirmId+'OKC']     = _opt.OKCallback;
	window['macConfirm'+confirmId+'CancelC'] = _opt.cancelCallback;
	var param = {
		name : _opt.name,
		width : _opt.width,
		data : skin,
		closeable : _opt.closeButton
	};
	jQuery.openPopupLayer(param);
}

function closeDialog(name) {
	if(name)
		jQuery.closePopupLayer(name);
	else
		jQuery.closePopupLayer();
}

function confirmButtonOkClick(confirmName, confirmId) {
	var closeAllow = true;
	var callback   = window['macConfirm'+confirmId+'OKC'];
	if(callback) closeAllow = callback();
	if(closeAllow !== false) jQuery.closePopupLayer(confirmName);
}

function confirmButtonCancelClick(confirmName, confirmId) {
	var closeAllow = true;
	var callback = window['macConfirm'+confirmId+'CancelC'];
	if(callback) closeAllow = callback();
	if(closeAllow !== false) jQuery.closePopupLayer(confirmName);
}
//-----------------------------------------------------------------------------
// Tree/table confirms
//-----------------------------------------------------------------------------
function createConfirm(dataSetObject) {
	return function(arrData) {
		var Method = arrData['method'];
		var Params = arrData['params'];
		var hItem = Params['handle_item'];
		if (Params['allow']) return true;

		var dlgTitle = "";
		var dlgContent = "";
		var dlgOk = "";
		var dlgCancel =  getLabel('js-cancel');

		Control.enabled = false;
		ContextMenu.allowControlEnable = false;

		switch (Method) {
			case "tree_delete_element" :
				if(Control.HandleItem.control.flatMode) {
					dlgTitle = getLabel('js-del-object-title');
					dlgContent = getLabel('js-del-object-shured');
				} else if (Control.HandleItem.control.objectTypesMode) {
					dlgTitle = getLabel('js-del-object-type-title');
					dlgContent = getLabel('js-del-object-type-shured');
				} else {
					dlgTitle = getLabel('js-del-title');
					dlgContent = getLabel('js-del-shured');
				}

				dlgOk = getLabel('js-del-do');
			break;
			case "tree_copy_element" :

				if (!hItem.hasChilds) return true;

				if (Params['clone_mode']) {
					// real copy
					dlgTitle = getLabel('js-copy-title');
					dlgContent = getLabel('js-copy-shured');
					dlgOk = getLabel('js-copy-do');
				} else {
					// virtual copy
					dlgTitle = getLabel('js-vcopy-title');
					dlgContent = getLabel('js-vcopy-shured');
					dlgOk = getLabel('js-copy-do');
				}

				dlgContent += '<br/><br /><input type="checkbox" id="copy-all" />&nbsp;<label for="copy-all">' +  getLabel('js-copy-all') +'</label>';
			break;

			case "copy_to_lang" :

				if (!hItem || !hItem.hasChilds) return true;

				// real copy
				dlgTitle = getLabel('js-copy-title');
				dlgContent = getLabel('js-copy-shured');
				dlgOk = getLabel('js-copy-do');

				dlgContent += '<br/><br /><input type="checkbox" id="copy-all" />&nbsp;<label for="copy-all">' +  getLabel('js-copy-all') +'</label>';

			break;
			case "tree_move_element" :
				dlgTitle = getLabel('js-move-title');
				dlgContent = getLabel('js-move-shured');
				dlgOk = getLabel('js-move-do');
			break;
			default:
				return true;
			break;
		}

		dlgContent = '<div class="confirm">' + dlgContent + '</div>';

		openDialog({
			title      : dlgTitle,
			text	   : dlgContent,
			OKText     : dlgOk,
			cancelText : dlgCancel,
			OKCallback: function () {
				Params['allow'] = true;

				var methods = {tree_copy_element:1, copy_to_lang:1}
				if (methods[Method]) {
					Params['copy_all'] = jQuery('#copy-all').attr("checked") ? 1 : 0;
				}
				dataSetObject.execute(Method, Params);
				Control.enabled = true;
				ContextMenu.allowControlEnable = true;
			},
			cancelCallback: function() {
				Control.enabled = true;
				ContextMenu.allowControlEnable = true;
			}
		});

		return false;
	}
}

jQuery(window).ready(function(){
	if(jQuery('form div.panel:first')){
		jQuery('form div.panel:first').find('div.text:first').css('width', '49.7%').css('margin', '0').css('padding','0');
		jQuery('form div.panel:first').find('div.text:first input').css('width', '90%');
	}else{}
});

function changeEditLink() {
	var link = jQuery("a#edit").attr('href');
	var value = jQuery("select.edit").val();
	var newlink = '/admin/data/type_edit/'+value+'/';
	jQuery("a#edit").attr('href', newlink);
}

function seoInput() {
	if (isSafari){
		jQuery('input#host').width(jQuery('#domain-selector').width() + 3).css('padding-top','3px');
	}
	else if (isChrome){
		jQuery('input#host').width(jQuery('#domain-selector').width() - 22);
	}
	else if (isIE){
		jQuery('input#host').width(jQuery('#domain-selector').width() - 18).css('border-bottom','none');
	}
	else if (isOpera){
		jQuery('input#host').width(jQuery('#domain-selector').width() - 19);
	}
	else {
		jQuery('input#host').width(jQuery('#domain-selector').width() - 21);
	}

	onResize = false;
}
var isIE= (navigator.userAgent.indexOf('MSIE') != -1),
	isOpera   = (navigator.appName === "Opera"),
	isSafari  = ((isIE || isOpera) ? false : navigator.vendor.indexOf('Apple') != -1),
	isChrome  = ((isIE || isOpera) ? false : navigator.vendor.indexOf('Google') != -1),
	isWebKit  = (navigator.userAgent.indexOf('WebKit') != -1),
	isFirefox = (navigator.userAgent.indexOf('Firefox') != -1);
var onResize = false;

jQuery(document).ready(function(){
	setTimeout(seoInput, 600);
	jQuery(window).resize(function() {
		if(!onResize) {
			onResize = true;
			setTimeout(seoInput, 600);
		}
	});
	jQuery('.help').click(function(){
		setTimeout(seoInput, 600);
	});
	jQuery('#sale_borders').change(function(){
		if(jQuery(this).val() <= 0){
			jQuery(this).val(0);
		}else if(jQuery(this).val() >= 100) {
			jQuery(this).val(100);
		}
	});
});