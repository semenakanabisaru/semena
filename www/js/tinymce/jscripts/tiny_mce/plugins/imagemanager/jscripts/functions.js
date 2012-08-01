tinyMCEPopup.requireLangPack();

function init() {	
	jQuery('#user_size_width').val(tinyMCEPopup.getWindowArg('orig_width'));
	jQuery('#user_size_height').val(tinyMCEPopup.getWindowArg('orig_height'));
}

function cancelAction() {
	tinyMCEPopup.close();
}

function insertAction() {
	var editor = tinyMCEPopup.editor;
	editor.contentWindow.focus();


	var iWidth = tinyMCEPopup.getWindowArg('img_width');
	var iHeight = tinyMCEPopup.getWindowArg('img_height');


	var sSrc = tinyMCEPopup.getWindowArg('img_src');
	var checked_id = jQuery('input:radio:checked', this.document).attr('id');
	if (checked_id == 'orig_size') {
		sSrc = tinyMCEPopup.getWindowArg('orig_src');
		iWidth = tinyMCEPopup.getWindowArg('orig_width');
		iHeight = tinyMCEPopup.getWindowArg('orig_height');
	} else if (checked_id == 'user_size') {
		iWidth  = jQuery('#user_size_width').val();
		iHeight = jQuery('#user_size_height').val();
		sSrc = tinyMCEPopup.getWindowArg('img_base') + "_" + iWidth + "_" + iHeight + "." + tinyMCEPopup.getWindowArg('img_ext');
	}

	var html = "<img";

		html += makeAttrib('src', sSrc);
		html += makeAttrib('alt' , '');

		html += makeAttrib('border', 0);
		html += makeAttrib('width', iWidth);
		html += makeAttrib('height', iHeight);
		html += " />";

	editor.execCommand("mceInsertContent", false, html);

	tinyMCEPopup.close();
}

function makeAttrib(attrib, value) {
	value = value.toString();
	var formObj = document.forms[0];
	var valueElm = formObj.elements[attrib];

	if (typeof(value) == "undefined" || value == null) {
		value = "";

		if (valueElm)
			value = valueElm.value;
	}

	if (value == "")
		return "";

	// XML encode it
	value = value.replace(/&/g, '&amp;');
	value = value.replace(/\"/g, '&quot;');
	value = value.replace(/</g, '&lt;');
	value = value.replace(/>/g, '&gt;');

	return ' ' + attrib + '="' + value + '"';
}


function setUserSizesActive() {
	jQuery('#user_size').checked = 1;
}

function validateIntVals(event) {
	setUserSizesActive();
	var oInputEl = event.currentTarget;

	if (oInputEl) {
		var iKCode = window.event ? window.event.keyCode : event.which;
		if (iKCode > 47 && iKCode < 58 || iKCode == 8 || iKCode == 46 || iKCode == 36 || iKCode == 35 || iKCode == 45 || iKCode == 37 || iKCode == 39 || iKCode == 9) {
			return true;
		}
	}
	return false;
}

function calculateProportions(event) {
	var oInputEl = event ? event.currentTarget : window.event.currentTarget;
	if (oInputEl.tagName.toLowerCase() !== 'input') {
		oInputEl = jQuery('#user_size_width').get(0);
	}

	var iOrigWidth = tinyMCEPopup.getWindowArg('orig_width');
	var iOrigHeight = tinyMCEPopup.getWindowArg('orig_height');
	var bSaveProportions = jQuery('#save_proportions').attr("checked");

	if (!bSaveProportions) return true;

	if (oInputEl) {
		if (oInputEl.id == 'user_size_width') {
			var oHInput = jQuery('#user_size_height');
			oHInput.val(Math.round(oInputEl.value * iOrigHeight / iOrigWidth));
		} else {
			var oWInput = jQuery('#user_size_width');
			oWInput.val(Math.round(oInputEl.value * iOrigWidth / iOrigHeight));
		}
	}
	return true;
}

tinyMCEPopup.onInit.add(init);