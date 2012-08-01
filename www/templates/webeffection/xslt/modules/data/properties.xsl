<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM	"ulang://i18n/constants.dtd:file">

<xsl:stylesheet	version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:template match="property|fields/field//value">
		<xsl:value-of select="value" />
	</xsl:template>
	
	<xsl:template match="property[@type = 'boolean'][value]">
		<xsl:text>&no;</xsl:text>
	</xsl:template>

	<xsl:template match="property[@type = 'boolean'][value = 1]|fields/field[@type = 'boolean']//value[.]">
		<xsl:text>&yes;</xsl:text>
	</xsl:template>
	
	<xsl:template match="property[@type = 'relation']|fields/field[@type = 'relation']//value">
		<xsl:apply-templates select="value/item" />
	</xsl:template>
	
	<xsl:template match="value/item">
		<xsl:value-of select="concat(@name, ', ')" />
	</xsl:template>
	
	<xsl:template match="value/item[position() = last()]">
		<xsl:value-of select="@name" />
	</xsl:template>
</xsl:stylesheet>