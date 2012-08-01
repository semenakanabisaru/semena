<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://i18n/constants.dtd:file">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:umi="http://www.umi-cms.ru/TR/umi">

	<xsl:template match="udata[@module = 'news'][@method = 'lastlents']">
		<xsl:param name="page_id" select="'0'" />
		<div class="news_lents" umi:element-id="{$page_id}" umi:region="list" umi:module="news" umi:sortable="sortable" umi:button-position="top right" />
	</xsl:template>

	<xsl:template match="udata[@module = 'news'][@method = 'lastlents'][total]">
		<xsl:param name="page_id" select="'0'" />
		<div class="news_lents" umi:element-id="{$page_id}" umi:region="list" umi:module="news" umi:sortable="sortable" umi:button-position="top right">
			<xsl:apply-templates select="items/item" />
		</div>
	</xsl:template>

	<xsl:template match="udata[@method = 'lastlents']/items/item">
		<a href="{@link}" umi:element-id="{@id}" umi:region="row" umi:field-name="name" umi:delete="delete" umi:empty="&empty-section-name;">
			<xsl:apply-templates />
		</a>
	</xsl:template>

</xsl:stylesheet>