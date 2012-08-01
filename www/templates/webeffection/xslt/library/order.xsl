<?xml version="1.0" encoding="utf-8"?>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="udata[@module = 'system' and @method = 'order_by']" />
	<xsl:template match="udata[@module = 'system' and @method = 'order_by'][link]">
		<a href="{link}"><xsl:value-of select="title" /></a>
	</xsl:template>

</xsl:stylesheet>