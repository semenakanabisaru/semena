<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://i18n/constants.dtd:file">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="udata[@module = 'system' and @method = 'captcha']" />
	<xsl:template match="udata[@module = 'system' and @method = 'captcha' and count(url)]">
		<!--<div>
			<label>
				<span><xsl:text>&enter-captcha;</xsl:text>&nbsp;<span class="required">*</span></span><br/>
				<input type="text" name="captcha" class="textinputs captcha" />
				<div class="img_captcha1">
					<img src="{url}{url/@random-string}" />
				</div>
			</label>
		</div>-->
        
                <label>
            <span><xsl:text>&enter-captcha;</xsl:text>&nbsp;<span class="required">*</span></span>
            <img src="{url}{url/@random-string}" />
            <input type="text" name="captcha" class="textinputs captcha" />
        </label>
	</xsl:template>

</xsl:stylesheet>