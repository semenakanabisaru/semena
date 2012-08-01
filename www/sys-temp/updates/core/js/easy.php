<?php
	// Deprecated !
	ini_set("display_errors", "1");
	error_reporting(E_ALL);
	require "../standalone.php";

	header("Content-type: text/javascript; charset=utf-8");

	enableOutputCompression();

	$s_referer = trim(getServer('HTTP_REFERER'), "/");
	$o_lang = langsCollection::getInstance()->getDefaultLang();
	
	$arr_referer = explode("/", $s_referer);
	
	if (isset($arr_referer[3])) {
		$s_lang = $arr_referer[3];
		if ($i_lang_id = langsCollection::getInstance()->getLangId($s_lang)) {
			$o_lang = langsCollection::getInstance()->getLang($i_lang_id);
		}
	}
	echo "\nvar pre_lang = '".$o_lang->getPrefix()."';\n";
	
	$cLang = isset($_COOKIE['ilang']) ? $_COOKIE['ilang'] : "";
	
	// quick security fix
	$cLang = str_replace('\\', "", $cLang);
	$cLang = str_replace('/', "", $cLang);
	$cLang = substr($cLang, 0, 2);
	echo "\naLang = '".$cLang."';\n";
	echo file_get_contents (CURRENT_WORKING_DIR . "/js/client/lang/qEdit_".$cLang.".js");
?>

var is_ie = !(navigator.appName.indexOf("Netscape") != -1);

<?php
	if(file_exists("./client/commonClient.js")) {
		echo <<<END
includeJS("/js/client/commonClient.js");

END;
	} else {
		echo <<<END
includeJS("/js/custom.js");
includeJS("/js/client/cookie.js");
includeJS("/js/client/catalog.js");
includeJS("/js/client/stat.js");
includeJS("/js/client/vote.js");
includeJS("/js/client/users.js");
includeJS("/js/client/eshop.js");
includeJS("/js/client/forum.js");
includeJS("/js/client/mouse.js");
includeJS("/js/client/quickEdit.js");
includeJS("/js/client/qPanel.js");
includeJS("/js/client/umiTicket.js");
includeJS("/js/client/umiTickets.js");
includeJS("/js/client/floatReferers.js");

END;
	}
?>

Event.observe(document, "keydown", function(event) {
	var oTargetEl = Event.element(event);
	if (oTargetEl) {
		if (oTargetEl.tagName.toUpperCase() == 'INPUT' || oTargetEl.tagName.toUpperCase() == 'TEXTAREA' || oTargetEl.tagName.toUpperCase() == 'IFRAME') {
			return true;
		}
	}

	var iKCode = event.which;

	if(event.keyCode == 27) {
		quickEdit.getInstance().hide();
	}

	if ((event.shiftKey || event.metaKey) && event.keyCode == 68) {
		quickEdit.getInstance().show();
	}

	if ((event.shiftKey || event.metaKey) && event.keyCode == 67) {
		umiTickets.getInstance().beginCreatingTicket();
	}

	if(event.ctrlKey || event.metaKey) {
		
		if(event.keyCode == 37) {
			var obj = document.getElementById('toprev');
			if(obj) {
				document.location = obj.href.toString();
				
				if(is_safari()) {
					return false;
				}
			}
		}

		if(event.keyCode == 39) {
			var obj = document.getElementById('tonext');
			if(obj) {
				document.location = obj.href.toString();
			}
		}

		if(event.keyCode == 36) {
			var obj = document.getElementById('tobegin');
			if(obj) {
				document.location = obj.href.toString();
				
				if(is_safari()) {
					return false;
				}
			}
		}

		if(event.keyCode == 35) {
			var obj = document.getElementById('toend');
			if(obj) {
				document.location = obj.href.toString();
				
				if(is_safari()) {
					return false;
				}
			}
		}
	}


});
