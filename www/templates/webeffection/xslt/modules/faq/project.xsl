<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet	version="1.0"
					xmlns="http://www.w3.org/1999/xhtml"
					xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
					xmlns:date="http://exslt.org/dates-and-times"
					xmlns:udt="http://umi-cms.ru/2007/UData/templates"
					xmlns:umi="http://www.umi-cms.ru/TR/umi"
					exclude-result-prefixes="xsl date udt umi">

	<xsl:template match="result[@module = 'faq'][@method = 'project']">
		<div umi:element-id="{$pageId}" umi:field-name="content">
			<xsl:value-of select="//property[@name = 'content']/value" disable-output-escaping="yes" />
		</div>
		<xsl:apply-templates select="document(concat('udata://faq/project//', $pageId))/udata" />
	</xsl:template>

	<xsl:template match="udata[@module = 'faq'][@method = 'project']">
		<div id="faq">
			<xsl:apply-templates select="lines/item" mode="faq_project" />
		</div>
		<xsl:apply-templates select="total" />
	</xsl:template>

	<xsl:template match="item" mode="faq_project">
		<div>
			<a href="{@link}" umi:element-id="{@id}" umi:field-name="name">
				<xsl:value-of select="@name" />
			</a>
			<div umi:element-id="{@id}" umi:field-name="content">
				<xsl:value-of select="document(concat('upage://',@id,'.content'))/udata/property/value" disable-output-escaping="yes" />
			</div>
		</div>
	</xsl:template>

</xsl:stylesheet>