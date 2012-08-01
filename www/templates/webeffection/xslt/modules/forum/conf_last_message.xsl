<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet	version="1.0"
				xmlns="http://www.w3.org/1999/xhtml"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:date="http://exslt.org/dates-and-times"
				xmlns:udt="http://umi-cms.ru/2007/UData/templates"
				xmlns:umi="http://www.umi-cms.ru/TR/umi"
				exclude-result-prefixes="xsl date udt umi">

	<xsl:template match="udata[@module = 'forum'][@method = 'conf_last_message']">
		<a href="{@link}" umi:empty="Название сообщения" umi:field-name="name" umi:element-id="{@id}"><xsl:value-of select="@name" /></a>
		<xsl:apply-templates select="document(concat('upage://',@id))/udata" mode="last_message" />
	</xsl:template>

	<xsl:template match="udata" mode="last_message">
		<xsl:variable name="publish_time" select="//property[@name = 'publish_time']/value/@unix-timestamp" />
		<div><xsl:apply-templates select="document(concat('udata://users/viewAuthor/', //property[@name = 'author_id']/value/item[1]/@id, '/'))/udata" /></div>
		(<span umi:empty="Дата сообщения" umi:field-name="publish_time" umi:element-id="{page/@id}">
			<xsl:apply-templates select="document(concat('udata://system/convertDate/',$publish_time,'/d.m.Y%20H:i/'))/udata" />
		</span>)
	</xsl:template>

</xsl:stylesheet>