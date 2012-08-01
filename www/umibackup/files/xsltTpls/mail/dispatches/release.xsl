<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://i18n/constants.dtd:file">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output encoding="utf-8" method="html" indent="yes" />

	<xsl:template match="release_body">
		<xsl:value-of select="messages" disable-output-escaping="yes" />
		<hr />
		<strong>
			<xsl:text>Отписаться:</xsl:text>
		</strong>
		<xsl:text> Отписаться от рассылки можно </xsl:text>
		<a href="{unsubscribe_link}">
			<xsl:text>по этой ссылке</xsl:text>
		</a>
	</xsl:template>

	<xsl:template match="release_message">
		<h3>
			<xsl:value-of select="header" />
		</h3>
		<xsl:value-of select="body" disable-output-escaping="yes" />
	</xsl:template>

</xsl:stylesheet>