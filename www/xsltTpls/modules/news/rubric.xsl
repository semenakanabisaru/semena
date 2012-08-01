<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://i18n/constants.dtd:file">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:umi="http://www.umi-cms.ru/TR/umi">

	<xsl:template match="result[@module = 'news'][@method = 'rubric']">
		<div umi:element-id="{$pageId}" umi:field-name="readme" umi:empty="&empty-page-content;">
			<xsl:apply-templates select=".//property[@name = 'readme']/value" />
		</div>
		<xsl:apply-templates select="document(concat('udata://news/lastlents/', $pageId, '/'))/udata">
			<xsl:with-param name="page_id" select="$pageId" />
		</xsl:apply-templates>
		<xsl:apply-templates select="document(concat('udata://news/lastlist/', $pageId, '/'))/udata" />
	</xsl:template>

</xsl:stylesheet>