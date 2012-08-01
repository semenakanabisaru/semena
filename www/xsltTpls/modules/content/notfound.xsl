<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://i18n/constants.dtd:file">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="result[@module = 'content'][@method = 'notfound']">
		<div><xsl:text>&notfound-page;</xsl:text></div>
	</xsl:template>

</xsl:stylesheet>