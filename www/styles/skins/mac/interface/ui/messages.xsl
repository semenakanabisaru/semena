<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:umi="http://www.umi-cms.ru/TR/umi">
	
	<xsl:template match="udata [@module = 'users' and @method = 'loadUserMessages']">
		<xsl:if test="1 >= 2">
			<xsl:if test="./system/edition != 'start'">
				<!-- Блок скрыть и блок больше не показывать -->
				<div class="mess_actions">[<a href="#" class="close">Скрыть</a>] [<a href="#" class="hide">Не уведомлять больше</a>]</div>
				<script language="javascript">
				<![CDATA[
					jQuery(document).ready(function() {
						jQuery("#umiMessages div.mess_actions a.close").click(function() {
							jQuery("#umiMessages").toggle(0, function() {
								jQuery("#foot").css('margin-top', -30);
								var rel = jQuery("#umiMessages > div:last").attr('rel');
								jQuery.get("/admin/users/closeUmiMessage/", {'value':rel});
							});
							return false;
						});
						jQuery("#umiMessages div.mess_actions a.hide").click(function() {
							jQuery("#umiMessages").toggle(0, function() {
								jQuery("#foot").css('margin-top', -30);
								jQuery.get("/admin/users/saveUserSettings/", {'key':'umiMessages', 'value':'true', 'tags[]':'notShow'});
							});
							return false;
						});
					});
				]]>
				</script>
			</xsl:if>
			<div style="padding: 10px;">
				<xsl:attribute name="rel">
					<xsl:value-of select=".//messages/message[@active]/@id" />
				</xsl:attribute>
				<xsl:value-of select=".//messages/message[@active]" disable-output-escaping="yes" />
			</div>
			<script language="javascript">
			<![CDATA[
				jQuery(document).ready(function() {
					var height = 30 + jQuery("#umiMessages").height();
					jQuery("#foot").css('margin-top', -height);
					jQuery("#umiMessages").toggle();
				});
			]]>
			</script>
		</xsl:if>
	</xsl:template>

</xsl:stylesheet>