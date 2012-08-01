<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://i18n/constants.dtd:file">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:umi="http://www.umi-cms.ru/TR/umi">

	<xsl:template match="udata[@module = 'catalog'][@method = 'getObjectsList']">
		<xsl:apply-templates select=".//lines/item" />
	</xsl:template>
	
	<xsl:template match="udata[@module = 'catalog'  and  @method = 'getObjectsList']//item">
		<div>
			<a href="{@link}"><xsl:value-of select="." /></a>
		</div>
	</xsl:template>

</xsl:stylesheet>