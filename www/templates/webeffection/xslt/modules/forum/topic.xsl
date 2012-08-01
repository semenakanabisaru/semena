<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet	version="1.0"
				xmlns="http://www.w3.org/1999/xhtml"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:date="http://exslt.org/dates-and-times"
				xmlns:udt="http://umi-cms.ru/2007/UData/templates"
				xmlns:umi="http://www.umi-cms.ru/TR/umi"
				exclude-result-prefixes="xsl date udt umi">

	<xsl:template match="result[@module = 'forum'][@method = 'topic']">
		<xsl:apply-templates select="document('udata://forum/topic/')/udata" />
	</xsl:template>

	<xsl:template match="udata[@module = 'forum'][@method = 'topic']">
		<xsl:if test="total">
			<div id="forum">
				<xsl:apply-templates select="lines/item" mode="forum_message" />
			</div>
			<xsl:apply-templates select="total" />
		</xsl:if>
		<xsl:apply-templates select="document(concat('udata://forum/message_post/', $pageId))/udata" />
	</xsl:template>

	<xsl:template match="item" mode="forum_message">
		<xsl:variable name="publish_time" select="document(concat('upage://',@id))//property[@name = 'publish_time']/value/@unix-timestamp" />
		<a name="{@id}" />
		<div class="forum_message">
			<strong umi:element-id="{@id}" umi:field-name="publish_time">
				<xsl:apply-templates select="document(concat('udata://system/convertDate/',$publish_time,'/d.m.Y%20%E2%20H:i/'))/udata" />
			</strong>
			<xsl:text> | </xsl:text>
			<xsl:apply-templates select="document(concat('udata://users/viewAuthor/', @author_id, '/'))/udata" />
			<div umi:element-id="{@id}" umi:field-name="message">
				<xsl:value-of select="." disable-output-escaping="yes" />
			</div>
		</div>
		<p class="descr margin_0"></p>
	</xsl:template>

</xsl:stylesheet>