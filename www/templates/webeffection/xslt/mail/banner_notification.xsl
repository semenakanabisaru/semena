<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://i18n/constants.dtd:file">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output method="html" />

	<xsl:template match="subject">
		<xsl:text>Оповещение о приближении окончания показа баннеров</xsl:text>
	</xsl:template>

	<xsl:template match="body">
		<xsl:text>У следующих баннеров истекает срок показа:</xsl:text><br />
		<xsl:value-of select="items" disable-output-escaping="yes"/>
	</xsl:template>

	<xsl:template match="item">
		<xsl:value-of select="bannerName" />
		<xsl:text> </xsl:text>
		<xsl:value-of select="tillDate" />
		<xsl:text> Ссылка для редактирования: </xsl:text>
		<a href = "{link}">
			<xsl:value-of select="link" />
		</a>
		<br />
	</xsl:template>

</xsl:stylesheet>