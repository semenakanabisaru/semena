<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://i18n/constants.dtd:file">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:umi="http://www.umi-cms.ru/TR/umi">

	<xsl:template match="property[@type = 'string' or @type = 'int' or @type = 'float' or @type = 'price' or @type = 'text' or @type = 'wysiwyg']|cut|content">
		<xsl:value-of select="value|../content|../cut" disable-output-escaping="yes" />
	</xsl:template>

	<xsl:template match="property[@type = 'date']|publish_time|@publish_time">
		<xsl:param name="timestamp" select="value/@unix-timestamp|../publish_time|../@publish_time" />
		<xsl:param name="date_format" select="'&default-date-format;'" />
		<xsl:apply-templates select="document(concat('udata://system/convertDate/',$timestamp,'/',$date_format,'/'))/udata" />
	</xsl:template>

	<xsl:template match="property[@type = 'relation']">
		<ul><xsl:apply-templates select="value/item" /></ul>
	</xsl:template>

	<xsl:template match="property[@type = 'relation']/value/item">
		<li><xsl:value-of select="@name" /></li>
	</xsl:template>

	<xsl:template match="property[@type = 'tags']|tags">
		<xsl:param name="tag_link" select="''" />
		<xsl:apply-templates select="value|item/tag">
			<xsl:with-param name="tag_link" select="$tag_link" />
		</xsl:apply-templates>
	</xsl:template>

	<xsl:template match="property[@type = 'tags']/value|tags/item/tag">
		<xsl:param name="tag_link" select="''" />
		<xsl:variable name="link" select="node()|../link" />
		<a href="{$tag_link}{$link}"><xsl:value-of select="." /></a><xsl:text>, </xsl:text>
	</xsl:template>

	<xsl:template match="property[@type = 'tags']/value[last()]|tags/item[last()]/tag">
		<xsl:param name="tag_link" select="''" />
		<xsl:variable name="link" select="node()|../link" />
		<a href="{$tag_link}{$link}"><xsl:value-of select="." /></a>
	</xsl:template>

	<xsl:template match="property[@type = 'img_file']" />

</xsl:stylesheet>