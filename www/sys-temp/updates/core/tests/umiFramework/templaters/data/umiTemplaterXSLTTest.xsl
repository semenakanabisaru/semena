<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output encoding="utf-8" method="xml" indent="yes"/>

	<xsl:template match="/">
		<xsl:copy-of select="." />
	</xsl:template>

</xsl:stylesheet>