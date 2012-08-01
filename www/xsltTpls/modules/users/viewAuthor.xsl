<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://i18n/constants.dtd:file">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:umi="http://www.umi-cms.ru/TR/umi">

	<xsl:template match="udata[@method = 'viewAuthor']">
		<span umi:object-id="{user_id}">
			<xsl:apply-templates select="fname" />
			<xsl:apply-templates select="nickname" />
			<xsl:apply-templates select="lname" />
		</span>
	</xsl:template>

	<xsl:template match="fname|lname|nickname">
		<span umi:field-name="{local-name()}" umi:empty="&empty;">
			<xsl:value-of select="." />
		</span>
		<xsl:text> </xsl:text>
	</xsl:template>

</xsl:stylesheet>