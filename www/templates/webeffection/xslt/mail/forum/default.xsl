<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://i18n/constants.dtd:file">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output encoding="utf-8" method="html" indent="yes" />

	<xsl:template match="mail_subject">
		<xsl:text>New reply</xsl:text>
	</xsl:template>

	<xsl:template match="mail_message">
		<h1><xsl:value-of select="h1" /></h1>
		<xsl:value-of select="message" />
	</xsl:template>

</xsl:stylesheet>