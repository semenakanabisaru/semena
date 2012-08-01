ContextMenu = {};

ContextMenu.itemHandlers = {};

ContextMenu.itemHandlers.viewElement = function (action) {
	if (!Control.HandleItem) return false;
	if (!Control.HandleItem.id) return false;

	var items = Control.HandleItem.control.selectedList;
	var i, item, size = 0;
	for(i in items) {
		item = items[i];
		size++;
	}

	if (!item) return false;

	var viewLink = item.getData()['link'];
	
	if(!viewLink) {
	    return false;
	}
	
	var disabled = (size != 1);


	return {
		caption: getLabel('js-' + action[0]),
		icon: action[1],
		visible: true,
		disabled : disabled,
		execute: function () {
			window.location = viewLink;
			return false;
		}
	};
};

ContextMenu.itemHandlers.copyUrl = function (action) {
	if (!Control.HandleItem) return false;
	if (!Control.HandleItem.id) return false;
	
	var self = this;
	if(!self.clipboard)
	{
          self.clipboard = new ZeroClipboard.Client();
         
          self.clipboard.addEventListener('load', function() {
             //console.log("Flash movie loaded and ready.");
          });
          
          self.clipboard.addEventListener('complete', function(client, text) {
             //console.log("Copied text to clipboard: " + text );
             self.clipboard.destroy();
             jQuery.cmenu.hideAll()
          });
          
           
          jQuery(document).click(function() {
             self.clipboard.destroy();
          });
	}

	var items = Control.HandleItem.control.selectedList;
	var i, item, size = 0;
	for(i in items) {
		item = items[i];
		size++;
	}

	if (!item) return false;

	var viewLink = item.getData()['link'];
	
	if(!viewLink) {
	    return false;
	}
	
	var disabled = (size != 1);

	return {
		caption: getLabel('js-' + action[0]),
		icon: action[1],
		visible: true,
		disabled : disabled,
		onRender: function(element) {
             self.clipboard.glue( element );
             self.clipboard.reposition(element);
             self.clipboard.setText( 'http://'+location.hostname + viewLink );
             
             self.clipboard.addEventListener('mouseOver', function() {
                 jQuery(self.clipboard.domElement).trigger('mouseover');
             });
             
             self.clipboard.addEventListener('mouseOut', function() {
                 jQuery(self.clipboard.domElement).trigger('mouseout');
             });
		},
		execute: function (action) {
             // no action, flash do it
		}
	};
};

ContextMenu.itemHandlers.editItem = function (action) {
	if (!Control.HandleItem) return false;
	if (!Control.HandleItem.id) return false;

	var items = Control.HandleItem.control.selectedList;
	var i, item, size = 0;
	for(i in items) {
		item = items[i];
		size++;
	}

	if (!item) return false;

	var editLink = item.editLink;
	
	if(!editLink) {
	    return false;
	}
	
	var disabled = (size != 1 || !editLink);

	return {
		caption: getLabel('js-' + action[0]),
		icon: action[1],
		visible: true,
		disabled : disabled,
		execute: function () {
			window.location = editLink;
			return false;
		}
	};
};

ContextMenu.itemHandlers.addItem = function (action) {
	if (!Control.HandleItem) return false;

	var items = Control.HandleItem.control.selectedList;
	var i, item, size = 0;
	for(i in items) {
		item = items[i];
		size++;
	}

	if (!item) return false;

	var createLink = item.createLink;
	if(!createLink) {
		return false;
	}
	var disabled = (size != 1 || !createLink);

	return {
		caption: getLabel('js-' + action[0]),
		icon: action[1],
		visible: true,
		disabled : disabled,
		execute: function () {
			window.location = createLink;
			return false;
		}
	};
};



ContextMenu.itemHandlers.filterItem = function (action) {
	if (!Control.HandleItem) return false;
	if (!Control.HandleItem.id) return false;
	
	if(Control.HandleItem.control.flatMode || Control.HandleItem.control.objectTypesMode) {
		return false;
	}

	var control = Control.HandleItem.control;
	var items = Control.HandleItem.control.selectedList, i, n = 0;
	var hasTrue = false, hasFalse = false;
	for(i in items) {
		var item = items[i];
		if(control.isTarget(item)) {
			hasTrue = true;
		} else {
			hasFalse = true;
		}
		n++;
	}
	
	var disabled = false;
	if(n == 1) {
		if(!item.hasChilds) {
			disabled = true;
		}
	}


	return {
		caption: getLabel('js-' + action[0]),
		icon: hasTrue ? 'checked' : 'undefined',
		visible: true,
		disabled : disabled,
		execute: function () {
			var control = Control.HandleItem.control;
			control.setTargetItems(hasTrue ? {} : control.selectedList);
			return false;
		}
	};
};

ContextMenu.itemHandlers.activeItem = function (action) {
	if (!Control.HandleItem) return false;
	if (!Control.HandleItem.id) return false;
	
	if(Control.HandleItem.control.flatMode || Control.HandleItem.control.objectTypesMode) {
		return false;
	}

	var items = Control.HandleItem.control.selectedList, i;
	var hasTrue = false, hasFalse = false;
	for(i in items) {
		if(items[i].isActive) {
			hasTrue = true;
		} else {
			hasFalse = true;
		}
	}
	var checked = (hasTrue && !hasFalse);

	return {
		caption: getLabel('js-' + action[0]),
		icon: checked ? 'ico_unblock' : 'ico_block',
		visible: true,
		execute: function () {
			var control = Control.HandleItem.control, i, ids = new Array();
			
			for(i in items) {
				ids.push(i);
			}
			
			control.dataSet.execute('tree_set_activity', {
				'element': ids,
				'selected_items': items,
				'active' : (checked ? 0 : 1)
			});
			
			return false;
		}
	};
};


ContextMenu.itemHandlers.activeObjectItem = function () {
	if (!Control.HandleItem) return false;
	if (!Control.HandleItem.id) return false;
	
	if(!Control.HandleItem.control.flatMode) {
		return false;
	}

	var items = Control.HandleItem.control.selectedList, i;
	var hasTrue = false, hasFalse = false;
	for(i in items) {
	    var item = items[i];
		if(item.getValue('is_activated') || item.getValue('is_active') || item.getValue('activated')) {
			hasTrue = true;
		} else {
			hasFalse = true;
		}
	}
	var checked = (hasTrue && !hasFalse);

	
	return {
		caption: getLabel('js-' + action[0]),
		icon: checked ? 'ico_unblock' : 'ico_block',
		visible: true,
		execute: function () {
			var control = Control.HandleItem.control, i, ids = new Array();
			
			for(i in items) {
				ids.push(i);
			}
			
			control.dataSet.execute('tree_set_activity', {
				'object': ids,
				'selected_items': items,
				'viewMode': 'full',
				'active' : (checked ? 0 : 1)
			});
			
			return false;
		}
	};
};

ContextMenu.itemHandlers.activeObjectItem = function (action) {
	if (!Control.HandleItem) return false;
	if (!Control.HandleItem.id) return false;
	
	if(!Control.HandleItem.control.flatMode) {
		return false;
	}

	var items = Control.HandleItem.control.selectedList, i;
	var hasTrue = false, hasFalse = false;
	for(i in items) {
	    var item = items[i];
		if(item.getValue('is_activated') || item.getValue('is_active') || item.getValue('activated')) {
			hasTrue = true;
		} else {
			hasFalse = true;
		}
	}
	var checked = (hasTrue && !hasFalse);
	
	return {
		caption: getLabel('js-' + action[0]),
		icon: checked ? 'ico_unblock' : 'ico_block',
		visible: true,
		execute: function () {
			var control = Control.HandleItem.control, i, ids = new Array();
			
			for(i in items) {
				ids.push(i);
			}
			
			control.dataSet.execute('tree_set_activity', {
				'element': Control.HandleItem.id,
				'object': ids,
				'handle_item': Control.HandleItem,
				'selected_items': items,
				'viewMode': 'full',
				'active' : (checked ? 0 : 1)
			});

			return false;
		}
	};
};

ContextMenu.itemHandlers.deleteItem = function (action) {
	if (!Control.HandleItem) return false;
	if (!Control.HandleItem.id) return false;
	
	var items = Control.HandleItem.control.selectedList;
	
	if(items.length < 1) {
		return false;
	}

	return {
		caption: getLabel('js-' + action[0]),
		icon: action[1],
		visible: true,
		execute: function () {
			var control = Control.HandleItem.control, i, ids = new Array();
			var items = control.selectedList;
			for(i in items) {
				var item = items[i];
				if (item.lockedBy) {
					alert(getLabel('js-page-is-locked') + "\n" + getLabel('js-steal-lock-question'));
					ContextMenu.getInstance().terminate();
					return false;
				}
				ids.push(i);
			}
			
			control.dataSet.execute('tree_delete_element', {
				'element' : ids,
				'selected_items': items
			});

			return false;
		}
	};
};


ContextMenu.itemHandlers.templatesItem = function (action) {


	if (!Control.HandleItem) return false;
	if (!Control.HandleItem.id) return false;
	
	if(Control.HandleItem.control.flatMode || Control.HandleItem.control.objectTypesMode) {
		return false;
	}
	
	//Inspect selected items
	var items = Control.HandleItem.control.selectedList;
	var i, langId = false, domainId = false, templateId = false, multipleTemplates = false;
	for(i in items) {
		var item = items[i];
		
		if(!langId || !domainId) {
			langId = item.langId;
			domainId = item.domainId;
		}
		
		if(templateId && multipleTemplates) {
			if(templateId != item.templateId) {
				templateId = false;
			}
		}
		
		if(!templateId && !multipleTemplates) {
			templateId = item.templateId;
			multipleTemplates = true;
		}
	}
	
	//Process templates list
	var tds = TemplatesDataSet.getInstance();
	var templateItems = {}, templates = tds.getTemplatesList(domainId, langId);

	var getClickCallback = function (template_id) {
		return function () {
			if(!Control.HandleItem) return false;
			
			var control = Control.HandleItem.control, i, ids = new Array();
			for(i in items) {
				ids.push(i);
			}
			
			control.dataSet.execute('change_template', {
				'element' : ids,
				'selected_items': items,
				'template-id' : template_id,
				'templates' : 1,
				'childs' : 1,
				'permissions' : 1,
				'virtuals' : 1,
				'links': 1
			});
			
			return false;
		};
	};

	for(i in templates) {
		var template = templates[i];
		var id = template['id'], title = template['title'], checked = false;
		
		if(!id) continue;   //TODO: Fix it in TemplatesDataSet class
		
		if(templateId && templateId == id) {
			checked = true;
		}
		
		templateItems[id] = {
			icon : checked ? 'checked' : 'undefined',
			caption: title,
			execute: getClickCallback(id)
		};
	}

	return {
		caption: getLabel('js-' + action[0]),
		icon: action[1],
		visible: true,
		execute: function () {
			return false;
		},
		submenu : templateItems
	};

};




ContextMenu.itemHandlers.storeGoodsItem = function () {
	if (!Control.HandleItem) return false;
	if (!Control.HandleItem.id) return false;

	var items = Control.HandleItem.control.selectedList;
	var i, item, size = 0;
	for(i in items) {
		item = items[i];
		size++;
	}

	if (!item) return false;

	var viewLink = window.pre_lang + "/admin/eshop/store_view/" + item.id + "/";
	
	if(!viewLink) {
	    return false;
	}
	
	var disabled = (size != 1);

	return {
		'title': getLabel('js-view-store-goods'),
		'callback': function () {
			window.location = viewLink;
			return false;
		},
		'disabled': disabled
	};
};



ContextMenu.itemHandlers.guideViewItem = function () {
	if (!Control.HandleItem) return false;
	if (!Control.HandleItem.id) return false;

	var items = Control.HandleItem.control.selectedList;
	var i, item, size = 0;
	for(i in items) {
		item = items[i];
		size++;
	}

	if (!item) return false;

	var viewLink = window.pre_lang + "/admin/data/guide_items/" + item.id + "/";

	if(!viewLink) {
	    return false;
	}
	
	var _disabled = (size > 1);

	return {
		caption : getLabel('js-view-guide-items'),
		icon    : 'view',
		execute : function () {
			window.location = viewLink;
			return false;
		},
		disabled : _disabled
	};
};

ContextMenu.itemHandlers.viewMessage = function () {
	if (!Control.HandleItem) return false;
	if (!Control.HandleItem.id) return false;

	var items = Control.HandleItem.control.selectedList;
	var i, item, size = 0;
	for(i in items) {
		item = items[i];
		size++;
	}

	if (!item) return false;

	var viewLink = window.pre_lang + "/admin/webforms/message/" + item.id + "/";	
	
	var _disabled = (size > 1);

	return {
		caption : getLabel('js-view-message'),
		icon    : 'view',
		execute : function () {
			window.location = viewLink;
			return false;
		},
		disabled : _disabled
	};
};

ContextMenu.itemHandlers.csvExport = function (action) {
	if (!Control.HandleItem) return false;
	if (!Control.HandleItem.id) return false;
	if(Control.HandleItem.control.objectTypesMode || Control.HandleItem.control.flatMode) return false;

	var items = Control.HandleItem.control.selectedList;
	var i, item, size = 0;
	for(i in items) {
		item = items[i];
		size++;
	}
	
	if(!item.hasChilds) {
		return false;
	}
	
	if(size != 1) {
		return false;
	}

	var elementId = item.id;
	var _item = item;
	return {
		caption : getLabel('js-csv-export'),
		icon    : action[1],
		visible : true,
		execute : function () {
			var link = _item.getExportLink(elementId);
			window.location = link;
			return false;
		}
	};
};

ContextMenu.itemHandlers.csvImport = function (action) {
	if (!Control.HandleItem) return false;
	if (!Control.HandleItem.id) return false;
	if(Control.HandleItem.control.objectTypesMode || Control.HandleItem.control.flatMode) return false;

	var items = Control.HandleItem.control.selectedList;
	var i, item, size = 0;
	for(i in items) {
		item = items[i];
		size++;
	}

	if(!item.hasChilds) {
		//return false;
	}

	if(size != 1) {
		return false;
	}

	var elementId = item.id;
	var _item = item;
	return {
		caption : getLabel('js-csv-import'),
		icon    : action[1],
		visible : true,
		execute : function () {
			var link = _item.getImportLink(elementId);
			_item.callCsvImport(link);
			return false;
		}
	};
};

ContextMenu.itemHandlers.restoreItem = function (action) {
	if (!Control.HandleItem) return false;
	if (!Control.HandleItem.id) return false;

	var items = Control.HandleItem.control.selectedList;

	if(items.length < 1) {
		return false;
	}

	return {
		caption: getLabel('js-trash-restore'),
		icon: action[1],
		visible: true,
		execute: function () {
			var control = Control.HandleItem.control, i, ids = new Array();
			var items   = control.selectedList;

			for(i in items) {								
				ids.push(i);
			}

			
			control.dataSet.execute('restore_element', {
				'element' : ids,
				'selected_items': items
			});

			return false;
		}
	};
};

ContextMenu.itemHandlers.copyItem = function (action) {

	if (!Control.HandleItem) return false;
	if (!Control.HandleItem.id) return false;

	if(Control.HandleItem.control.flatMode || Control.HandleItem.control.objectTypesMode) {
		return false;
	}

	var control = Control.HandleItem.control;
	var items = control.selectedList;
	var i, item, domainId = false;langId  = false;
	for(i in items) {
		item = items[i];
		domainId = item.domainId;
		langId = item.langId;
	}
	
	
	var tds = TemplatesDataSet.getInstance();
	var langsList = tds.getLangsList();
	var domainList = tds.getDomainsList();
	
	var getClickCallback = function (domainId, langId) {
		return function () {
			var i, ids = new Array();
			for(i in items) {
				ids.push(i);
			}
			
			control.dataSet.addEventHandler('onBeforeExecute',	createConfirm(control.dataSet));
			control.dataSet.execute('copy_to_lang', {
				'element' : ids,
				'handle_item' : $.extend(true, {}, Control.HandleItem),
				'selected_items': items,
				'lang-id' : langId,
				'domain-id' : domainId,
				'templates' : 1,
				'childs' : 1,
				'permissions' : 1,
				'virtuals' : 1,
				'links': 1
			});

			return false;
		};
	};
	
	var menuItems = {};
	for(i = 0; i < domainList.length; i++) {
		var d = domainList[i];
		
		var checked = (domainId == d['id']);
		
		var smenuItems = {};
		for(j = 0; j < langsList.length; j++) {
			var lang = langsList[j];
			
			var schecked = checked && (langId == lang['id']);
			
		
			
			smenuItems[lang['id']] = {
				icon : schecked ? 'checked' : 'undefined',
				caption: lang['nodeValue'],
				execute: getClickCallback(d['id'], lang['id'])
			};
		}
	
		menuItems[d['id']] = {
			icon : checked ? 'checked' : 'undefined',
			caption: d['host'],
			execute: function () {
				return false;
			},
			submenu : smenuItems
		};
	}

	return {
		caption: getLabel('js-' + action[0]),
		visible: true,
		execute: function () {
			return false;
		},
		submenu : menuItems
	};
};


ContextMenu.itemHandlers.moveItem = function (action) {

	if (!Control.HandleItem) return false;
	if (!Control.HandleItem.id) return false;

	if(Control.HandleItem.control.flatMode || Control.HandleItem.control.objectTypesMode) {
		return false;
	}

	var control = Control.HandleItem.control;
	var items = control.selectedList;
	var i, item, domainId = false;langId  = false;
	for(i in items) {
		item = items[i];
		domainId = item.domainId;
		langId = item.langId;
	}
	
	
	var tds = TemplatesDataSet.getInstance();
	var langsList = tds.getLangsList();
	var domainList = tds.getDomainsList();
	
	var getClickCallback = function (domainId, langId) {
		return function () {
			var i, ids = new Array();
			for(i in items) {
				ids.push(i);
			}
			
			control.dataSet.execute('move_to_lang', {
				'element' : ids,
				'handle_item' : $.extend(true, {}, Control.HandleItem),
				'selected_items': items,
				'lang-id' : langId,
				'domain-id' : domainId,
				'templates' : 1,
				'childs' : 1,
				'permissions' : 1,
				'virtuals' : 1,
				'links': 1
			});

			return false;
		};
	};
	
	var menuItems = {};
	for(i = 0; i < domainList.length; i++) {
		var d = domainList[i];
		
		var checked = (domainId == d['id']);
		
		var smenuItems = {};
		for(j = 0; j < langsList.length; j++) {
			var lang = langsList[j];
			
			var schecked = checked && (langId == lang['id']);
			
		
			
			smenuItems[lang['id']] = {
				icon : schecked ? 'checked' : 'undefined',
				caption: lang['nodeValue'],
				execute: getClickCallback(d['id'], lang['id'])
			};
		}
	
		menuItems[d['id']] = {
			icon : checked ? 'checked' : 'undefined',
			caption: d['host'],
			execute: function () {
				return false;
			},
			submenu : smenuItems
		};
	}

	return {
		caption: getLabel('js-' + action[0]),
		visible: true,
		execute: function () {
			return false;
		},
		submenu : menuItems
	};
};

ContextMenu.itemHandlers.copyItemOld = function (action) {

	if (!Control.HandleItem) return false;
	if (!Control.HandleItem.id) return false;

	if(Control.HandleItem.control.flatMode || Control.HandleItem.control.objectTypesMode) {
		return false;
	}

	var control = Control.HandleItem.control;
	var items = control.selectedList;
	var i, j, item, domainId = false, langId = false;
	for(i in items) {
		item = items[i];
		domainId = item.domainId;
		langId = item.langId;
	}


	var tds = TemplatesDataSet.getInstance();
	var langsList = tds.getLangsList();
	var domainList = tds.getDomainsList();

	var getClickCallback = function (domainId, langId) {
		return function () {
			var i, ids = new Array();
			for(i in items) {
				ids.push(i);
			}

			control.dataSet.addEventHandler('onBeforeExecute',	createConfirm(control.dataSet));
			control.dataSet.execute('copy_to_lang_old', {
				'element' : ids,
				'handle_item' : $.extend(true, {}, Control.HandleItem),
				'selected_items': items,
				'lang-id' : langId,
				'domain-id' : domainId,
				'templates' : 1,
				'childs' : 1,
				'permissions' : 1,
				'virtuals' : 1,
				'links': 1
			});

			return false;
		};
	};

	var menuItems = {};
	for(i = 0; i < domainList.length; i++) {
		var d = domainList[i];

		var checked = (domainId == d['id']);

		var smenuItems = {};
		for(j = 0; j < langsList.length; j++) {
			var lang = langsList[j];

			var schecked = checked && (langId == lang['id']);

			smenuItems[lang['id']] = {
				icon : schecked ? 'checked' : 'undefined',
				caption: lang['nodeValue'],
				execute: getClickCallback(d['id'], lang['id'])
			};
		}

		menuItems[d['id']] = {
			icon : checked ? 'checked' : 'undefined',
			caption: d['host'],
			execute: function () {
				return false;
			},
			submenu : smenuItems
		};
	}

	return {
		caption: getLabel('js-' + action[0]),
		visible: true,
		execute: function () {
			return false;
		},
		submenu : menuItems
	};
};

ContextMenu.itemHandlers.typeRemove = function () {

};

ContextMenu.itemHandlers.typeEdit = function () {

};

ContextMenu.allowControlEnable = true;


