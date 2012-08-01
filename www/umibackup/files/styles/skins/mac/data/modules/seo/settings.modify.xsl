<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="option[@name = 'megaindex-password']" mode="settings.modify-option">
		<input type="password" name="{@name}" value="{value}" id="{@name}" />
	</xsl:template>

</xsl:stylesheet>