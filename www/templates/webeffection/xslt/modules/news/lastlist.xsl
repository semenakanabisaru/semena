<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://i18n/constants.dtd:file">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:umi="http://www.umi-cms.ru/TR/umi">

	<xsl:template match="udata[@module = 'news'][@method = 'lastlist']">
		<div class="news" umi:element-id="{category_id}" umi:module="news" umi:method="lastlist" umi:sortable="sortable" umi:add-method="popup" />
	</xsl:template>

	<xsl:template match="udata[@module = 'news'][@method = 'lastlist'][total]">
		<div class="news" umi:element-id="{category_id}" umi:module="news" umi:method="lastlist" umi:sortable="sortable">
			<xsl:apply-templates select="items/item" />
		</div>
		<xsl:apply-templates select="document(concat('udata://system/numpages/', total, '/', per_page, '/'))/udata">
			<xsl:with-param name="numpages" select="ceiling(total div per_page)" />
		</xsl:apply-templates>
	</xsl:template>

	<xsl:template match="udata[@method = 'lastlist']/items/item">
		<div class="new" umi:region="row" umi:element-id="{@id}">
			<a href="{@link}" umi:field-name="name" umi:delete="delete">
				<xsl:apply-templates />
			</a>
			<div umi:field-name="publish_time" umi:empty="&empty-page-date;" class="news_timestamp">
				<xsl:apply-templates select="@publish_time">
					<xsl:with-param name="date_format" select="'d.m.Y%20%D0%B2%20H:i'" />
				</xsl:apply-templates>
			</div>
			<div umi:field-name="anons" umi:empty="&empty-page-content;">
				<xsl:apply-templates select="document(concat('upage://', @id, '.anons'))/udata/property" />
			</div>
		</div>
	</xsl:template>

</xsl:stylesheet>