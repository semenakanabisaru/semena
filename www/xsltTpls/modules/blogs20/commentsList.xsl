<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet	version="1.0"
				xmlns="http://www.w3.org/1999/xhtml"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:date="http://exslt.org/dates-and-times"
				xmlns:udt="http://umi-cms.ru/2007/UData/templates"
				xmlns:umi="http://www.umi-cms.ru/TR/umi"
				exclude-result-prefixes="xsl date udt">

	<xsl:template match="udata[@module = 'blogs20'][@method = 'commentsList']" />

	<xsl:template match="udata[@module = 'blogs20'][@method = 'commentsList'][total]">
		<div id="comments">
			<h3>Комментарии:</h3>
			<xsl:apply-templates select="items" mode="commentsList" />
		</div>
		<xsl:apply-templates select="total" />
	</xsl:template>

	<xsl:template match="items|subcomments" mode="commentsList">
		<xsl:apply-templates select="item" mode="commentsList" />
	</xsl:template>

	<xsl:template match="item" mode="commentsList">
		<div class="comment">
			<strong umi:element-id="{@cid}" umi:field-name="publish_time">
				<xsl:apply-templates select="document(concat('udata://system/convertDate/',publish_time,'/d.m.Y%20%E2%20H:i/'))/udata" />
			</strong> | 
			<xsl:apply-templates select="document(concat('uobject://',author_id,'.userId'))//item" mode="autorComments" />
			<xsl:value-of select="document(concat('uobject://',author_id,'.nickname'))//value" />
			<xsl:if test="$userType = 'sv'">| <a href="/blogs20/itemDelete/{@cid}/">Удалить</a></xsl:if>
			<div umi:element-id="{@cid}" umi:field-name="content" class="comment_text"><xsl:value-of select="content" disable-output-escaping="yes" /></div>
			<a href="#comment_add" class="comment_add_link" id="{@cid}" name="comment_add">Комментировать</a>
			<xsl:apply-templates select="subcomments" mode="commentsList" />
		</div>
	</xsl:template>

	<xsl:template match="item" mode="autorComments">
		<xsl:value-of select="document(concat('uobject://',@id,'.lname'))//value" />&#160;
		<xsl:value-of select="document(concat('uobject://',@id,'.fname'))//value" />
		(<xsl:value-of select="document(concat('uobject://',@id,'.login'))//value" />)
	</xsl:template>

</xsl:stylesheet>