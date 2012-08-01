<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://i18n/constants.dtd:file">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output method="html" />

	<xsl:template match="body">
		<xsl:text>Страница </xsl:text>
		<a href="{page_link}"><xsl:value-of select="page_header" /></a><br />
		<xsl:text>снята с публикации</xsl:text><br />
		<xsl:text>Комментарии к публикации:</xsl:text><br />
		<p><xsl:value-of select="publish_comments" /></p>
	</xsl:template>

</xsl:stylesheet>