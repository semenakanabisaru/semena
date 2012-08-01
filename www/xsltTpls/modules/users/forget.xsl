<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM	"ulang://i18n/constants.dtd:file">

<xsl:stylesheet	version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:xlink="http://www.w3.org/TR/xlink">

	<xsl:template match="result[@method = 'forget']">
		<form method="post" action="/users/forget_do/" id="forget">
			<script>
				<![CDATA[
				jQuery(document).ready(function(){
					jQuery('#forget input:radio').click(function() {
						jQuery('#forget input:text').attr('name', jQuery(this).attr('id'));
					});
				});

				]]>
			</script>

			<div>
				<input type="radio" id="forget_login" name="choose_forget" checked="checked" />
				<xsl:text>&login;:</xsl:text>
			</div>
			<div>
				<input type="radio" id="forget_email" name="choose_forget" />
				<xsl:text>&e-mail;:</xsl:text>
			</div>
			<div>
				<input type="text" name="forget_login" style="margin:5px 0;" />
			</div>
			<div>
				<input type="submit" class="button" value="Выслать пароль" />
				<div class="clear" />
			</div>
		</form>
	</xsl:template>

	<xsl:template match="result[@method = 'forget_do']">
		<p>
			<xsl:text>&registration-activation-note;</xsl:text>
		</p>
	</xsl:template>
</xsl:stylesheet>