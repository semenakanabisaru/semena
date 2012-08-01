<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet	version="1.0"
				xmlns="http://www.w3.org/1999/xhtml"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:date="http://exslt.org/dates-and-times"
				xmlns:udt="http://umi-cms.ru/2007/UData/templates"
				xmlns:xlink="http://www.w3.org/1999/xlink"
				exclude-result-prefixes="xsl date udt xlink">

	<xsl:template match="result[@module = 'webforms'][@method = 'post']">
		<xsl:apply-templates select="document('udata://webforms/post/')/udata" />
	</xsl:template>

	<xsl:template match="udata[@module = 'webforms'][@method = 'post']">
		<xsl:value-of select="error" disable-output-escaping="yes" />
	</xsl:template>

</xsl:stylesheet>