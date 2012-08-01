<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://i18n/constants.dtd:file">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:umi="http://www.umi-cms.ru/TR/umi">

	<xsl:template match="result[@module = 'content'][@method = 'sitemap']">
		<xsl:apply-templates select="document('udata://content/sitemap/')/udata" />
	</xsl:template>

	<xsl:template match="udata[@module = 'content' and @method = 'sitemap']">
		<xsl:apply-templates select="items" />
	</xsl:template>

	<xsl:template match="udata[@module = 'content' and @method = 'sitemap']/items">
		<ul umi:element-id="{../@id}" umi:module="content" umi:method="sitemap" umi:region="list" umi:sortable="sortable">
			<xsl:apply-templates select="item" mode="sitemap" />
		</ul>
	</xsl:template>

	<xsl:template match="udata[@module = 'content' and @method = 'sitemap']/items/item">
		<li umi:element-id="{@id}" umi:region="row">
			<a href="{@link}" umi:field-name="name" umi:delete="delete" umi:empty="&empty-section-name;">
				<xsl:value-of select="@name" />
			</a>
			<xsl:apply-templates select="items" />
		</li>
	</xsl:template>

</xsl:stylesheet>