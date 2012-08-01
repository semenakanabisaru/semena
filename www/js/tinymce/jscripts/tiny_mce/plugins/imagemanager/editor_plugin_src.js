(function() {
	tinymce.PluginManager.requireLangPack('imagemanager');
	tinymce.create('tinymce.plugins.ImageManagerPlugin', {
		init : function(ed, url) {
			this.editor = ed;
			this.url    = url;
			var imp = new ImageManagerPanel(ed);
			ed.addCommand('umiToggleImageManager', function() {
				var active = imp.toggle();
				ed.controlManager.setActive('imagemanager', active);
			});
			ed.addButton('imagemanager', {
				title : 'imagemanager.toggle_btn',
				cmd   : 'umiToggleImageManager'
			});
			jQuery("head").append("<link rel=\"stylesheet\" type=\"text/css\" href=\""+url+"/css/imagemanager.css\" />");
		},
		createControl : function(n, cm) {
			return null;
		},
		getInfo : function() {
			return {
				longname  : 'Image manager plugin',
				author    : 'Leeb, Realloc',
				authorurl : 'http://umi-cms.ru/',
				infourl   : 'http://umi-cms.ru/',
				version   : '2.0'
			};
		}
	});
	tinymce.PluginManager.add('imagemanager', tinymce.plugins.ImageManagerPlugin);
})();

/**
 *
 */
function ImageManagerPanel(__editor) {
	var self   = this;
	var editor  = __editor;
	var panel   = null;
	var visible = false;
	var pathControl = null;
	var scrollSpeed = 0;
	var __construct = function() {
		// Nothing to do
	};
	var initPanel = function() {
		var container = editor.getContainer();
		panel = jQuery("<div class=\"imanager\"></div>");
		panel.css({display : "none"});
		panel.prependTo(container);
		pathControl = new ImageManagerPath({selectCallback : onPathSelect, container : panel.get(0)});
		panel.append('<div class="container">\
						<a class="scroll-right"></a>\
						<a class="scroll-left"></a>\
						<div class="wrapper" id="mce_editor_0_wrap">\
							<ul class="igallery"></ul>\
							<div class="del" style="left:105px; top:14px; display:none;"></div>\
						</div>\
						<div class="add-image"></div>\
					  </div>\
					  <div class="upload_image" style="display:none;">\
						<form method="post" enctype="multipart/form-data" target="'+editor.id+'imgmgr_upload">\
						<div class="button">\
							<input type="submit" value="'+editor.getLang('imagemanager.lbl_upload')+'" />\
							<span class="l"></span>\
							<span class="r"></span>\
						</div>\
						<label for="upload-image">'+editor.getLang('imagemanager.lbl_upload_image')+'\
							<input type="file" name="Filedata" />\
						</label>\
						</form>\
						<iframe id="'+editor.id+'imgmgr_upload" name="'+editor.id+'imgmgr_upload" style="display:none" />\
					 </div>');
		var wrapper = jQuery("div.wrapper", panel);
		var scrollDir   = 0;
		var timerId     = null;
		var doScroll = function() {
			if(Math.abs(scrollSpeed) < 30) {
				scrollSpeed = scrollSpeed + scrollDir*2;
			}
			wrapper.scrollLeft( wrapper.scrollLeft() + scrollSpeed );
			if(scrollSpeed == 0) {
				clearInterval(timerId);
			}
			if(scrollDir == 0) {
				if(scrollSpeed > 0)
					scrollSpeed = Math.floor( scrollSpeed * 0.8 );
				else
					scrollSpeed = Math.ceil( scrollSpeed * 0.8 );
			}
		};
		jQuery("a.scroll-left", panel).mouseenter(function(){
			timerId   = setInterval(doScroll, 50);
			scrollDir = -1;
		}).mouseleave(function(){
			scrollDir = 0;
		});
		jQuery("a.scroll-right", panel).mouseenter(function(){
			timerId   = setInterval(doScroll, 50);
			scrollDir = 1;
		}).mouseleave(function(){
			scrollDir = 0;
		});
		jQuery("div.del", panel).click(function(){ 
									var panelItem = jQuery(this).attr('panelItem');
									editor.windowManager.confirm(editor.getLang('imagemanager.lbl_remove_img_title'), function(s){if(s)removeImage(panelItem); });
									jQuery(this).hide();
								})
						   .mouseover(function(){ jQuery(this).show(); })
						   .mouseleave(function(){ jQuery(this).hide(); });
		jQuery("form", panel).submit(function() {
			jQuery("iframe#"+editor.id+"imgmgr_upload", panel).one('load', {}, function(){
				var list = jQuery("div.wrapper ul", panel);
				var path = jQuery("udata", this.contentDocument).attr("folder") + "/";
				var file = jQuery("file", this.contentDocument);
				if(file.size()) {
					addImage( file, path, list, true );
				} else {
					editor.windowManager.alert(editor.getLang('imagemanager.lbl_upload_error'));
				}
				jQuery("form input[type=submit]", panel).attr("disabled", false);
			});
			jQuery(this).attr("action", "/admin/data/uploadfile/?imagesOnly&folder="+base64encode(pathControl.getPath()));
			jQuery("input[type=submit]", this).attr("disabled", true);
		});
		var add_image = jQuery("div.add-image", panel);
		add_image.click(function(){
			jQuery("div.upload_image", panel).toggle();
			jQuery(this).toggleClass("off");
		});
		if (jQuery.browser.msie && (jQuery.browser.version == "7.0" || document.documentMode < 7)) {
			add_image.css({top:'120px', padding:"5px 0"});
		}
		self.loadFolder("/images");
		var temp = jQuery("div.wrapper ul", panel);
	};
	var onPathSelect = function(__path) {
		self.loadFolder(__path);
	};
	this.loadFolder = function(path) {
		jQuery.get("/admin/data/getfilelist/",
		     {
				 folder : base64encode(path),
				 showOnlyImages : true,
				 rrr : Math.random()
			 }, updateImageList );
	};
	var removeImage = function(panelItem) {
		panelItem.remove();
		var info = jQuery("img", panelItem).attr("information");
		jQuery.get("/admin/data/deletefiles/",
			  {
				  folder     : base64encode(info.img_path.substr(0, info.img_path.length-1)  ),
				  "delete[]" : base64encode(info.img_name),
				  "nolisting": true
			  });
	}
	var addImage = function(file, path, list, bindEvents){
		//var file = jQuery(this);
		var name = file.attr('name');
		var basename  = name.substr(0, name.lastIndexOf('.'));
		var extension = file.attr('type');
		var thumb     = '/autothumbs.php?img=' + path + basename + '_sl_90_60.' + extension;
		var width     = file.attr('width');
		var height    = file.attr('height');
		var item = jQuery('<li class="image">\
						<img class="panel_item" title="'+editor.getLang('imagemanager.lbl_move_help')+'" src="' + thumb + '"/>\
						<div class="name" title="' + file.attr('mime') + ', ' + width + 'x' + height + 'px, ' + file.attr('converted-size') + '">' + name + '</div>\
					  </li>');
		var info = {
			img_name    : name,
			img_path    : path,
			img_src     : thumb,
			img_width   : 90,
			img_height  : 60,

			img_base    : '/autothumbs.php?img=' + path + basename,
			img_ext     : extension,

			orig_src    : path + name,
			orig_width  : width,
			orig_height : height
		};
		jQuery("img", item).get(0).information = info;
		list.append(item);
		// megafix for IE
		if (jQuery.browser.msie && (jQuery.browser.version == "7.0" || document.documentMode < 8)) {
			var name_width = jQuery(".name", item).width();
			jQuery(item).css({'padding':((name_width<92)?'10px 5px':'10px '+(((name_width-92)+10)/2)+'px')});
			jQuery(".name", item).css({'width':((name_width<102)?102:name_width+12)});
		}

		if(bindEvents) {
			jQuery("img", item).mouseenter(function() {
				if(scrollSpeed !== 0) return;
				var button   = jQuery("div.del", panel);
				var position = jQuery(this).offset();
				var _left    = position.left + 43;
				if(_left < button.parent().width()) {
					button.css({left : _left});
					// megafix for IE
					if (jQuery.browser.msie && (jQuery.browser.version == "7.0" || document.documentMode < 8)) {
						var left_pos = position.left + 23;
						if (jQuery.browser.version == "7.0" || document.documentMode < 7) {
							var list_offset = Math.abs(list.offset().left - 57);
							left_pos = left_pos + list_offset;
						}
						button.css({left : left_pos, top : 0});
					}

					button.get(0).panelItem = jQuery(this).parent();
					button.show();
				}
			}).mouseleave(function() {
				jQuery("div.del", panel).hide();
			}).draggable({
				helper : 'clone',
				revert : 'invalid'}).disableSelection();
		}
	}
	var updateImageList = function(r) {
		var list = jQuery("div.wrapper ul", panel);
		var path = jQuery("udata", r).attr("folder") + "/";
		list.html("");
		jQuery("file", r).each(function(){ addImage( jQuery(this), path, list ); });
		jQuery("div.wrapper img", panel).mouseenter(function() {
			if(scrollSpeed !== 0) return;
			var button   = jQuery("div.del", panel);
			var position = jQuery(this).offset();
			var _left    = position.left + 43;
			if(_left < button.parent().width()) {
				button.css({left : _left});
				// megafix for IE
				if (jQuery.browser.msie && (jQuery.browser.version == "7.0" || document.documentMode < 8)) {
					var left_pos = position.left + 23;
					if (jQuery.browser.version == "7.0" || document.documentMode < 7) {
						var list_offset = Math.abs(list.offset().left - 57);
						left_pos = left_pos + list_offset;
					}
					button.css({left : left_pos, top : 0});
				}

				button.get(0).panelItem = jQuery(this).parent();
				button.show();
			}
		}).mouseleave(function() {
			jQuery("div.del", panel).hide();
		}).draggable({
			helper : 'clone',
			revert : 'invalid'}).disableSelection();
		jQuery("iframe", editor.getContainer()).droppable({
			accept : '#' + editor.getContainer().id + " div.wrapper img",
			drop   : function(ev, ui) {
						editor.windowManager.open({
							file   : editor.plugins.imagemanager.url + '/sizedialog.htm',
							width  : 400,
							height : 201,
							inline : 1
						}, ui.draggable[0].information);
				     }
		});
	};
	/**
	 * Toggles visibility
	 * @return boolean current visibility state
	 */
	this.toggle = function() {
		if(!panel) {
			initPanel();
		}
		visible = !visible;
		jQuery(panel).slideToggle(0);

		return visible;
	};
	__construct();
}
/**
 *
 */
function ImageManagerPath(options) {
	var container = options.container || null;
	var callback  = options.selectCallback || null;
	var list      = null;
	var subfolders = null;
	var items     = [];
	var path      = [];
	var cache     = {};
	var __construct = function() {
		subfolders = document.createElement("ul");
		subfolders.className = "subfoldersMenu";
		subfolders.style.display = "none";
		document.body.appendChild(subfolders);
		list = document.createElement("ul");
		list.className = "insets";
		container.appendChild(list);
		//TODO: Watch to en.js||ru.js.
		var name;
		if (mce_lang = 'en') {
			name = "Images";
		} else if (mce_lang = 'ru') {
			name = "Изображения";
		}
		var root = createPathElement(name, "images");
		list.appendChild(root);		
	};
	var createPathElement = function(name, relativePath) {
		var item = jQuery("<li><a href=\"javascript:void(0);\">" + name + "</a><span /></li>");
		if(!items.length) {
			item.addClass("first");
		}
		items.push(item);
		path.push(relativePath || name);
		jQuery("a", item).click(function() {
			reduceToItem(item);
			callback("/" + path.join("/"));
		});
		jQuery("span", item).click(function(event) {
			event.stopPropagation();
			if(!jQuery(this).hasClass("active") || jQuery(subfolders).css("display") == "none") {
				var pathString   = getItemPath(item);
				jQuery("span.active", list).removeClass("active");
				jQuery(this).addClass("active");
				updateSubfolders(pathString, item);
			} else {
				hideSubfolders();
				jQuery(this).removeClass("active");
			}
		});
		return item.get(0);
	};
	var updateSubfolders = function(_pathString, _item) {
		if(cache[_pathString] instanceof Array) {
			var span = jQuery("span", _item);
			if(!cache[_pathString].length) {
				span.hide();
				return;
			}
			var pos  = span.offset();
			var _top  = pos.top + span.height();
			var _left = pos.left;
			jQuery(subfolders).html(
				jQuery.map(cache[_pathString], function(n){ return "<li>" + n + "</li>"; } ).join("")
			);
			showSubfolders();
			jQuery(subfolders).css({position : "absolute",
			                   "z-index": 1000,
				               top      : _top  + "px",
				               left     : _left + "px"});
			jQuery("li", subfolders).click(function(event) {
				reduceToItem(_item);
				var li = jQuery(this);
				var el = createPathElement(li.text());
				list.appendChild(el);
				hideSubfolders();
				span.removeClass("active");
				callback("/" + path.join("/"));
				event.stopPropagation();
			});
			jQuery("li", subfolders).mouseenter(function(){ jQuery(this).addClass("highlighted"); });
			jQuery("li", subfolders).mouseleave(function(){ jQuery(this).removeClass("highlighted"); });
		} else {
			jQuery.get("/admin/data/getfolderlist/", {folder : base64encode(_pathString)},
				  function(r) {
					  var folders = jQuery("folder", r);
					  cache[_pathString] = [];
					  for(var i=0; i<folders.length; i++) {
						  cache[_pathString].push( jQuery(folders[i]).attr("name") );
					  }
					  updateSubfolders(_pathString, _item);
				  });
		}
	};
	var reduceToItem = function(item) {
		while(items.length && items[items.length-1] != item) {
			items.pop().remove();
			path.pop();
		}
	};
	var getItemPath = function(item) {
		var index = jQuery.inArray(item, items);
		if(index != -1) {
			return "/" + path.slice(0, index+1).join("/");
		} else {
			return "/" + path.join("/");
		}
	};
	var showSubfolders = function() {
		jQuery(subfolders).css("display", "block");
		jQuery(document).bind("click", documentClickHandler);
	};
	var hideSubfolders = function() {
		jQuery(subfolders).css("display", "none");
		jQuery(document).unbind("click", documentClickHandler);
	};
	var documentClickHandler = function() {
		jQuery("span.active", list).removeClass("active");
		hideSubfolders();
	};
	this.getPath = function() {
		return "/" + path.join("/");
	};
	// Init
	__construct();
}