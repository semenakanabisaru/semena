<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM	"ulang://i18n/constants.dtd:file">

<xsl:stylesheet	version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:template match="/result[@method = 'personal']">
		<script>
			function tabChange(tab, prefix) {
				var i, tabs = tab.parentNode;
				for (i=0; tabs.childNodes.length > i; i++) {
					var tab_in = tabs.childNodes[i];
					if (tab_in.nodeName == "#text") continue;
					tab_in.className = "";
				}
				tab.className = "act";
				var con_tabs = jQuery('.' + prefix + tabs.className);
				var con_tabs_arr = con_tabs[0].childNodes;
				for (i=0; con_tabs_arr.length > i; i++) {
					var con_tab = con_tabs_arr[i];
					if (con_tab.nodeName == "#text") continue;
					con_tab.style.display = "none";
				}
				var con_tab_act = document.getElementById(prefix + tab.id);
				con_tab_act.style.display = "block";
			}
		</script>

		<div class="tabs">
			<div id="tab_profile" class="act" onclick="tabChange(this, 'con_');">Персональная информация</div>
			<div id="tab_orders" onclick="tabChange(this, 'con_');">Заказы</div>
		</div>

		<div class="con_tabs">
			<xsl:apply-templates select="document('udata://user/settings')" />
			<xsl:apply-templates select="document('udata://emarket/ordersList')" />
		</div>
	</xsl:template>
</xsl:stylesheet>