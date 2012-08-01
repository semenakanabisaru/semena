function typeControl(_typeId, _options) {
	var _self  = this;
	var typeId = _typeId;
	var formMode = _options.form || false;
	var container = jQuery(_options.container) || alert('container not found');
	var groups = {};
	var fields = {};
	var typesList  = [];
	var guidesList = [];
	var restrictionsList   = {};
	var restrictionsLoaded = false;

	var init = function() {
		var addButton = jQuery("<span class='fg_add_group'><a href='#' class='add'>" + getLabel('js-type-edit-add_group') + "</a></span>");
		jQuery("a", addButton).click( function() {
									_self.addGroup({id      : 'new',
													title   : getLabel("js-type-edit-new_group"),
													name    : '',
													visible : true});
									return false;
								} );
		addButton.appendTo(container);
		container.sortable({items: "div.fg_container:not(:first)",
							update : function(e, ui){
										var groupId     = ui.item.attr("umiGroupId");
										var nextGroupId = ui.item.next("div.fg_container").attr("umiGroupId") || "false";
										jQuery.get("/admin/" + (formMode ? "webforms" : "data") + "/json_move_group_after/"+groupId+"/"+nextGroupId+"/"+typeId+"/");
									  }        });
		container.before("<div id='removeConfirm' style='display:none;' >\
							<div class='eip_win_head popupHeader' onmousedown=\"jQuery('.eip_win').draggable({containment: 'window'})\">\
							<div class='eip_win_close popupClose' onclick=\"javascript:jQuery.closePopupLayer('removeConfirm'); return false;\"> </div>\
								<div class='eip_win_title'>" + getLabel("js-type-edit-confirm_title") + "</div>\
							</div>\
							<div class='eip_win_body popupBody' onmousedown=\"jQuery('.eip_win').draggable('destroy')\">\
								<div class='popupText'>" + getLabel("js-type-edit-confirm_text") + "</div>\
								<div class='eip_buttons'>\
									<input type='button' value='" + getLabel("js-confirm-unrecoverable-yes") + "' class='RemoveConfirmYes ok' />\
									<input type='button' value='" + getLabel("js-confirm-unrecoverable-no") + "' class='back' onclick=\"jQuery.closePopupLayer('removeConfirm'); return false;\" />\
									<div style='clear:both;' />\
								</div>\
							</div>\
						</div>");
	};

	this.addGroup = function(_options) {
		if(_options.id) {
			var gid = 'g' + _options.id;
			groups[_options.id] = _options;
			var groupContainer =
				jQuery("<div class=\"fg_container\">\
					<div class=\"fg_container_header\">\
						<span id='head"+gid+"title' class='left'>"+_options.title+" [" + _options.name +  "]</span>\
						<span id='" + gid + "control'>"
							+ (_options.locked ? "&nbsp;" :
							"<a href=\"#\" class=\"edit\" title='" + getLabel("js-type-edit-edit") + "' />\
							<a href=\"#\" class=\"remove\" title='" + getLabel("js-type-edit-remove") + "' />")+
						"</span>\
							<span id='" + gid + "save' style='display:none;'>\
							" + getLabel("js-type-edit-saving") + "...\
						</span>\
					</div>\
					<div class=\"group_edit\" style='display:none;'>\
						<form class='group_form'>\
						<div style=\"width:49%;float:left;\">\
							<label for='"+gid+"title'>" + getLabel("js-type-edit-title") + "</label>\
							<input type='text' id='"+gid+"title' name='data[title]' value='" + _options.title + "' />\
						</div>\
						<div style=\"width:49%;float:left;\">\
							<label for='"+gid+"name'>" + getLabel("js-type-edit-name") + "</label>\
							<input type='text' id='"+gid+"name' name='data[name]' value='" + _options.name + "' />\
						</div>"+(!formMode?"\
						<div style=\"width:49%;float:left;\">\
							<input type='checkbox' id='"+gid+"visible' name='data[is_visible]' value='1' " + (_options.visible ? "checked" : "") + " class=\"boolean\" />\
							<label for='"+gid+"visible' class='boolean'>" + getLabel("js-type-edit-visible") + "</label>\
						</div>":"<div><input type='hidden' id='"+gid+"visible' name='data[is_visible]' value='1' checked='checked'  /></div>")+"\
						<div class='buttons'>\
							<div><input type='button' value='"+getLabel("js-trash-confirm-cancel")+"' class='cancel button' />\
							<span class='l' /><span class='r' /></div>\
							<div><input type='button' value='"+getLabel("js-data-edit-field")+"' class='ok button' />\
							<span class='l' /><span class='r' /></div>\
						</div>\
						</form>\
					</div>\
					<div class=\"fg_container_body\">"
						 + (_options.locked ? "" : "<span class='fg_add_field'><a href='#' class='add'>" + getLabel("js-type-edit-add_field") + "</a></span>") +
						"<ul class=\"fg_container\">\
						</ul>\
					</div>\
				   </div>");
			if(_options.locked) {
				groupContainer.addClass('locked');
			}
			if(!_options.visible) {
				groupContainer.addClass('invisible');
			}
			jQuery("ul", groupContainer).andSelf().attr("umiGroupId", _options.id);
			groups[_options.id].container = groupContainer;
			groupContainer.appendTo(container);
			groups[_options.id].fields = {};
			jQuery(".fg_container", groupContainer).sortable({connectWith : "ul.fg_container", dropOnEmpty: true, items: "li",
														 placeholder: "ui-sortable-field-placeholder",
														 update : function(e, ui){ 
																	var fieldId     = ui.item.attr("umiFieldId");
																	var nextFieldId = ui.item.next("li").attr("umiFieldId");
																	var isLast      = (nextFieldId != undefined) ? "false" : ui.item.parent().attr("umiGroupId");
																	jQuery.get("/admin/" + (formMode ? "webforms" : "data") + "/json_move_field_after/"+fieldId+"/"+nextFieldId+"/"+isLast+"/"+typeId+"/");
																  } });
			jQuery("a.add", groupContainer).click(function() {
											_self.addField(_options.id, {id    	  : 'new',
																		 title 	  : getLabel("js-type-edit-new_field"),
																		 typeId	  : 3,
																		 typeName : '',
																		 name  	  : '',
																	     tip      : ''});
											return false;
										});
			var okButton  = jQuery("input.ok", groupContainer);
			var nameInput = jQuery("#"+gid+"name", groupContainer);
			jQuery("#"+gid+"title, #"+gid+"name", groupContainer).keyup(function(event) { okButton.attr('disabled', event.currentTarget.value.length == 0); } );
			jQuery("#"+gid+"title", groupContainer).focus(function(event) {													
													if(!nameInput.val().length)
														jQuery(event.currentTarget).bind('keyup', {nameField : nameInput}, universalTitleConvertCallback);
											}).blur(function(event) {
													jQuery(event.currentTarget).unbind('keyup', universalTitleConvertCallback);
											});
			jQuery("input.ok", groupContainer).click(
					function () {
						saveGroup(_options.id, groupContainer);
						jQuery("div.fg_container_body, div.group_edit", groupContainer).slideToggle();
					} );
			jQuery("input.cancel", groupContainer).click(
					function () {
						if(_options.id == 'new') {
							groupContainer.remove();
						} else {							
							jQuery("div.fg_container_body, div.group_edit", groupContainer).slideToggle();
						}
					} );
			jQuery("input:text", groupContainer).keydown(
					function(e) {
						if(e.keyCode == 13) {
							jQuery("input.ok", groupContainer).click();
							e.stopPropagation();
							return false;
						}
					} );
			jQuery("a.edit", groupContainer).click(
					function () {
						jQuery("div.fg_container_body, div.group_edit", groupContainer).slideToggle();
						return false;
					} );
			jQuery("a.remove", groupContainer).click(
					function () {
						jQuery.openPopupLayer({
							name   : 'removeConfirm',
							target : 'removeConfirm',
							width  : 300
						});
						jQuery("input.RemoveConfirmYes").click(function() {
							delete groups[_options.id];
							groupContainer.remove();
							jQuery.get("/admin/" + (formMode ? "webforms" : "data") + "/json_delete_group/" + _options.id + "/" + typeId + "/");
							jQuery.closePopupLayer( 'removeConfirm');
							return false;
						});						
						return false;
					} );
			if(_options.id == 'new') {
				var offset = groupContainer.offset();
				window.scrollTo(offset.left,offset.top);
				jQuery("a.edit", groupContainer).hide();
				jQuery("div.fg_container_body, div.group_edit", groupContainer).slideToggle();
			}
		}
	};

	this.addField = function(groupId, _options) {
		if(groupId && _options.id) {
			var group = groups[groupId];
			fields[_options.id]       = _options;
			group.fields[_options.id] = fields[_options.id];
			fields[_options.id].groupId = groupId;
			var fid = 'f' + _options.id;
			var fieldContainer =
				jQuery("<li>\
					<div class=\"view\" >\
						<span id='" + fid + "info'  class=\"left\" style=\"overflow:hidden;width:85%;\" >\
							<span id='head" + fid + "title' style=\"overflow:hidden;width:35%;\" >" + _options.title + "\
								<span id='head" + fid + "required' style=\"float:none;\" >" + (_options.required ? "*" : "") + "</span>\
							</span>\
							<span id='head" + fid + "name' style=\"overflow:hidden;width:24%;\" >[" + _options.name + "]</span>\
							<span id='head" + fid + "type' style=\"overflow:hidden;width:40%;\" >(" + ((_options.id == "new")? "" : _options.typeName) + (_options.restrictionId ? (": "+_options.restrictionTitle) : "" ) + ")</span>\
						</span>\
						<span id='" + fid + "control'>"
							+ (_options.locked ? "&nbsp;" :
							"<a href=\"#\" class=\"edit\" title='" + getLabel("js-type-edit-edit") + "' />\
							<a href=\"#\" class=\"remove\" title='" + getLabel("js-type-edit-remove") + "' />") +
						"</span>\
						<span id='" + fid + "save' style='display:none;'>\
							" + getLabel("js-type-edit-saving") + "...\
						</span>\
					</div>\
					<div class=\"edit\" style=\"display:none;\">\
						<form>\
						<div style=\"width:49%;float:left;\">\
							<label for='"+fid+"title'>" + getLabel("js-type-edit-title") + "</label>\
							<input type='text' id='"+fid+"title' name='data[title]' value=\"" + _options.title.replace('"', '&quot;') + "\" />\
						</div>\
						<div style=\"width:49%;float:left;\">\
							<label for='"+fid+"name'>" + getLabel("js-type-edit-name") + "</label>\
							<input type='text' id='"+fid+"name' name='data[name]' value='" + _options.name + "' />\
						</div>\
						<div style=\"width:49%;float:left;\">\
							<label for='"+fid+"tip'>" + getLabel("js-type-edit-tip") + "</label>\
							<input type='text' id='"+fid+"tip' name='data[tip]' value=\"" + _options.tip.replace('"', '&quot;') + "\" />\
						</div>\
						<div style=\"width:49%;float:left;\">\
							<label for='"+fid+"type'>" + getLabel("js-type-edit-type") + "</label>\
							<select id='"+fid+"type' name='data[field_type_id]'>\
								<option value='"+_options.typeId+"'>"+_options.typeName+"</option>\
							</select>\
						</div>\
						<div style=\"width:49%;float:left;\">\
							<label for='"+fid+"restriction'>" + getLabel("js-type-edit-restriction") + "</label>\
							<select id='"+fid+"restriction' name='data[restriction_id]'></select>\
						</div>\
						<div style=\"width:49%;float:left;display:none;\" id='"+fid+"guideCont'>\
							<label for='"+fid+"guide'>" + getLabel("js-type-edit-guide") + "</label>\
							<select id='"+fid+"guide' name='data[guide_id]'></select>\
						</div>\
						<div style=\"width:100%;float:left;height:1px;margin:0;padding:0\">\
							<!--Dummy div just to keep layout nice -->\
						</div>"+(!formMode?"\
						<div style=\"width:49%;float:left;\">\
							<input type='checkbox' id='"+fid+"visible' name='data[is_visible]' value='1' " + (_options.visible ? "checked" : "") + " class='boolean' />\
							<label for='"+fid+"visible' class='boolean'>" + getLabel("js-type-edit-visible") + "</label>\
						</div>":"<input type='hidden' id='"+fid+"visible' name='data[is_visible]' value='1' checked='checked' />")+(!formMode?"\
						<div style=\"width:49%;float:left;\">\
							<input type='checkbox' id='"+fid+"indexable' name='data[in_search]' value='1' " + (_options.indexable ? "checked" : "") + " class='boolean' />\
							<label for='"+fid+"indexable' class='boolean'>" + getLabel("js-type-edit-indexable") + "</label>\
						</div>":"")+"\
						<div style=\"width:49%;float:left;\">\
							<input type='checkbox' id='"+fid+"required' name='data[is_required]' value='1' " + (_options.required ? "checked" : "") + " class='boolean' />\
							<label for='"+fid+"required' class='boolean'>" + getLabel("js-type-edit-required") + "</label>\
						</div>"+(!formMode?"\
						<div style=\"width:49%;float:left;\">\
							<input type='checkbox' id='"+fid+"filterable' name='data[in_filter]' value='1' " + (_options.filterable ? "checked" : "") + " class='boolean' />\
							<label for='"+fid+"filterable' class='boolean'>" + getLabel("js-type-edit-filterable") + "</label>\
						</div>":"")+"\
						<div class='buttons'>\
							<div><input type='button' value='"+getLabel("js-trash-confirm-cancel")+"' class='cancel button' />\
							<span class='l' /><span class='r' /></div>\
							<div><input type='button' value='"+getLabel("js-data-edit-field")+"' class='ok button' />\
							<span class='l' /><span class='r' /></div>\
						</div>\
						</form>\
					</div>\
				   </li>");
			fieldContainer.attr('umiFieldId', _options.id);
			if(_options.locked) {
				fieldContainer.addClass('locked');
			}
			if(!_options.visible) {
				fieldContainer.addClass('invisible');
			}
			fields[_options.id].container = fieldContainer;
			fieldContainer.appendTo(jQuery(".fg_container", group.container) );
			var okButton  = jQuery("input.ok", fieldContainer);
			var nameInput = jQuery("#"+fid+"name", fieldContainer);
			jQuery("#"+fid+"title, #"+fid+"name", fieldContainer).keyup(function(event) { okButton.attr('disabled', event.currentTarget.value.length == 0); } );
			jQuery("#"+fid+"title", fieldContainer).focus(function(event) {
													if(!nameInput.val().length)
														jQuery(event.currentTarget).bind('keyup', {nameField : nameInput}, universalTitleConvertCallback);
											}).blur(function(event) {
													jQuery(event.currentTarget).unbind('keyup', universalTitleConvertCallback);
											});
			jQuery("input.ok", fieldContainer).click(
					function () {
						saveField(_options.id, fieldContainer);
						jQuery("div.edit", fieldContainer).slideUp();
					} );
			jQuery("input.cancel", fieldContainer).click(
					function () {
						if(_options.id == 'new') {
							fieldContainer.remove();
						} else {
							jQuery("div.edit", fieldContainer).slideUp();
						}
					} );
			jQuery("input:text", fieldContainer).keydown(
					function(e) {
						if(e.keyCode == 13) {
							jQuery("input.ok", fieldContainer).click();
							e.stopPropagation();
							return false;
						}
					} );
			jQuery("a.edit", fieldContainer).click( 
					function () {						
						jQuery("div.edit", fieldContainer).slideToggle("normal", function() { loadTypesInfo(_options.id, fieldContainer); } );
						return false;
					} );
			jQuery("a.remove", fieldContainer).click(
					function () {
						jQuery.openPopupLayer({
							name   : 'removeConfirm',
							target : 'removeConfirm',
							width  : 300
						});
						jQuery("input.RemoveConfirmYes").click(function() {
							delete fields[_options.id];
							delete group.fields[_options.id];
							fieldContainer.remove();
							jQuery.get("/admin/" + (formMode ? "webforms" : "data") + "/json_delete_field/" + _options.id + "/" + typeId + "/");
							jQuery.closePopupLayer('removeConfirm');
							return false;
						});
						return false;
					} );
			jQuery("#" + fid + "type", fieldContainer).change(
					function() {
						var value = this.value;
						var typeO = jQuery.grep(typesList, function(o) { return o.id == value;} );
						if(typeO.length && (typeO[0].dataType == "relation" || typeO[0].dataType == "optioned")) {
							jQuery("#" + fid + "guideCont", fieldContainer).show("normal", function() { loadGuidesInfo(_options.id, fieldContainer); } );
						} else {
							jQuery("#" + fid + "guideCont", fieldContainer).hide();
						}
						loadRestrictionsInfo(_options.id, fieldContainer);
					} );
			if(_options.id == 'new') {
				var offset = fieldContainer.offset();
				window.scrollTo(offset.left,offset.top);
				jQuery("a.edit", fieldContainer).click();
				jQuery("a.edit", fieldContainer).hide();
			}
		}
	};

	var saveGroup = function(id, context) {
		var gid   = "#g" + id;
		var group = groups[id];
		group.title      = jQuery(gid + "title", context).val();
		group.name       = jQuery(gid + "name", context).val();
		group.visible    = jQuery(gid + "visible", context).attr('checked');

		if(group.visible)
			context.removeClass('invisible');
		else
			context.addClass('invisible');

		jQuery(gid + "control", context).hide();
		jQuery(gid + "save", context).show();

		jQuery("#headg" + id + "title", context).html(group.title + " [" + group.name + "]");

		var param = jQuery("form.group_form", context).serialize();

		if(id == 'new') {
			jQuery.post("/admin/" + (formMode ? "webforms" : "data") + "/type_group_add/" + typeId + "/do/.xml?noredirect=true",
				   param,
				   function(data) {
					   var newGroupId = jQuery("group",data).attr('id');
					   jQuery("*[id]" , context).each( function() { this.id = this.id.replace(/new/, newGroupId); } );
					   jQuery("#g" + newGroupId + "control", context).show();
					   jQuery("#g" + newGroupId + "save", context).hide();
					   jQuery("a.edit", context).show();
					   group.id = newGroupId;
					   groups[newGroupId] = group;
				   });
		} else {
			jQuery.post("/admin/" + (formMode ? "webforms" : "data") + "/type_group_edit/" + id + "/" + typeId + "/do",
				   param,
				   function(data) {
					   jQuery("#g" + id + "control", context).show();
					   jQuery("#g" + id + "save", context).hide();
				   });
		}
	}

	var saveField = function(id, context) {
		var fid   = "#f" + id;
		var field = fields[id];
		field.title      = jQuery(fid + "title", context).val();
		field.name       = jQuery(fid + "name", context).val();
		field.tip        = jQuery(fid + "tip", context).val();
		field.typeId     = jQuery(fid + "type", context).val();
		field.visible    = jQuery(fid + "visible", context).attr('checked');
		field.required   = jQuery(fid + "required", context).attr('checked');
		field.indexable  = jQuery(fid + "indexable", context).attr('checked');
		field.filterable = jQuery(fid + "filterable", context).attr('checked');
		var typeO = jQuery.grep(typesList, function(o) { return o.id == field.typeId;} );
		if(typeO.length && typeO[0].dataType == "relation") {
			field.guideId = jQuery(fid + "guide", context).val();
		} else {
			field.guideId = 0;
		}

		if(field.visible)
			context.removeClass('invisible');
		else
			context.addClass('invisible');

		jQuery(fid + "control", context).hide();
		jQuery(fid + "save", context).show();

		fid = "#headf" + id;
		jQuery(fid + "title", context).html(field.title);
		jQuery(fid + "name", context).html("[" + field.name + "]");
		jQuery(fid + "type", context).html("(" + (field.typeName = jQuery.grep(typesList, function(o) { return o.id == field.typeId;} )[0].name) + ")" );
		jQuery(fid + "required", context).html(field.required ? "*" : "");

		var param = jQuery("form", context).serialize();

		if(id == 'new') {
			jQuery.post("/admin/" + (formMode ? "webforms" : "data") + "/type_field_add/" + field.groupId + "/" + typeId + "/do/.xml?noredirect=true",
				   param,
				   function(data) {
					   var newFieldId = jQuery("field",data).attr('id');					   
					   jQuery("*[id]" , context).each( function() { this.id = this.id.replace(/new/, newFieldId); } );
					   jQuery("#f" + newFieldId + "control", context).show();
					   jQuery("#f" + newFieldId + "save", context).hide();
					   field.id = newFieldId;
					   fields[newFieldId] = field;
					   groups[field.groupId].fields[newFieldId] = field;
					   if(typeO[0].dataType == "relation") {
						   field.guideId = jQuery("field",data).attr('guide-id');
						   guidesList    = [];
						   loadGuidesInfo(newFieldId, context);
					   }
					   jQuery("a.edit", context).show();
				   });
		} else {
			jQuery.post("/admin/" + (formMode ? "webforms" : "data") + "/type_field_edit/" + id + "/" + typeId + "/do/.xml?noredirect=true",
				   param,
				   function(data) {
					   jQuery("#f" + id + "control", context).show();
					   jQuery("#f" + id + "save", context).hide();
					   if(typeO[0].dataType == "relation") {
						   field.guideId = jQuery("field",data).attr('guide-id');
						   guidesList    = [];
						   loadGuidesInfo(jQuery("field",data).attr('id'), context);
					   }
				   });
		}
	};

	var loadTypesInfo = function(id, context) {
		var select = jQuery("#f" + id + "type", context);		
		if(typesList.length) {
			if(select.get(0).options.length > 1) return;
			var options = '';			
			var value   = select.attr('value');
			for(var i=0; i<typesList.length; i++) {
				var selected = (parseInt(typesList[i].id) == parseInt(value)) ? "selected" : "";
				options += "<option value='" + typesList[i].id + "' " + selected +">" + typesList[i].name + "</option>";
			}
			select.html(options);
			select.attr("disabled", false);
			select.change();
		} else {
			select.attr("disabled", true);
			jQuery.post("/udata/system/fieldTypesList/", {}, function(data){ parseTypesInfo( data, id, context ); });
		}
	};

	var loadGuidesInfo = function(id, context) {
		var select = jQuery("#f" + id + "guide", context);
		select.attr("disabled", true);
		if(guidesList.length) {
			var options = "<option value=''></option>";
			var value   = fields[id].guideId;
			for(var i=0; i<guidesList.length; i++) {
				var selected = (parseInt(guidesList[i].id) == parseInt(value)) ? "selected" : "";
				options += "<option value='" + guidesList[i].id + "' " + selected +">" + guidesList[i].name + "</option>";
			}
			select.html(options);
			select.attr("disabled", false);
		} else {
			jQuery.post("/udata/system/publicGuidesList/", {}, function(data){ parseGuidesInfo( data, id, context ); });
		}
	}

	var loadRestrictionsInfo = function(id, context) {
		var select = jQuery("#f" + id + "restriction", context);
		var typeId  = jQuery("#f" + id + "type", context).val();
		select.attr("disabled", true);
		if(restrictionsLoaded) {			
			var value   = fields[id].restrictionId;
			var options = '<option value="0" ' + (!value ? 'selected' : '') + '> </option>';
			if(restrictionsList[typeId])
			for(var i=0; i<restrictionsList[typeId].length; i++) {
				var selected = (parseInt(restrictionsList[typeId][i].id) == parseInt(value)) ? "selected" : "";
				options += "<option value='" + restrictionsList[typeId][i].id + "' " + selected +">" + restrictionsList[typeId][i].title + "</option>";
			}
			select.html(options);
			if(restrictionsList[typeId] && restrictionsList[typeId].length)
				select.attr("disabled", false);
		} else {			
			jQuery.post("/udata/data/getRestrictionsList/", {}, function(data){ parseRestrictionsInfo( data, id, context ); });
		}
	}

	var parseTypesInfo = function( data, id, context ) {
		var items = jQuery("item", data); 
		for(var i=0; i<items.length; i++) {
			var itm = jQuery(items[i]);
			typesList[typesList.length] = {id       : itm.attr("id"),
			                               name     : itm.text(),
										   dataType : itm.attr("data-type"),
										   multiple : itm.attr("is-multiple")};
		}
		loadTypesInfo(id, context);
	};

	var parseGuidesInfo = function( data, id, context ) {
		var items = jQuery("item", data);
		for(var i=0; i<items.length; i++) {
			var itm = jQuery(items[i]);
			guidesList[guidesList.length] = {id   : itm.attr("id"),
			                                 name : itm.text() };
		}
		loadGuidesInfo(id, context);
	};

	var parseRestrictionsInfo = function( data, id, context ) {
		var items = jQuery("item", data);
		for(var i=0; i<items.length; i++) {
			var itm = jQuery(items[i]);
			var typeId = itm.attr("field-type-id");
			if(!restrictionsList[typeId]) restrictionsList[typeId] = [];
			restrictionsList[typeId][restrictionsList[typeId].length] =
										{id    : itm.attr("id"),
										 name  : itm.attr("name"),
			                             title : itm.text() };
		}
		restrictionsLoaded = true;
		loadRestrictionsInfo(id, context);
	};

	var universalTitleConvertCallback = function(event) {
		event.data.nameField.val(transliterateRu(event.currentTarget.value).replace(/\s+/g, "_").replace(/[^A-z0-9_]+/g, "").toLowerCase());
	}

	init();
}