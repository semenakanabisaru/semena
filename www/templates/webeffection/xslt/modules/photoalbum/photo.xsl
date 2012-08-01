<?xml version="1.0" encoding="utf-8"?>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="result[@module = 'photoalbum'][@method = 'photo']">
		<div id="photo" class="gray_block">
			<xsl:call-template name="makeThumbnail">
				<xsl:with-param name="element_id" select="$pageId" />
				<xsl:with-param name="field_name">photo</xsl:with-param>
				<xsl:with-param name="width">495</xsl:with-param>
			</xsl:call-template>
			<div class="clear padding10px"></div>
			<xsl:apply-templates select="document(concat('udata://photoalbum/album/',page/@parentId,'//1000'))/udata/items/item[@id = $pageId]" mode="slider" />
		</div>
		<div>
			<xsl:value-of select="//property[@name = 'descr']/value" disable-output-escaping="yes" />
		</div>
		<div>
			<a href="{parents/page[last()]/@link}">Показать все фотографии</a>
			
		</div>
	</xsl:template>

</xsl:stylesheet>