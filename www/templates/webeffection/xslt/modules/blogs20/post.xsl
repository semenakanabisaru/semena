<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet	version="1.0"
				xmlns="http://www.w3.org/1999/xhtml"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:date="http://exslt.org/dates-and-times"
				xmlns:udt="http://umi-cms.ru/2007/UData/templates"
				xmlns:umi="http://www.umi-cms.ru/TR/umi"
				exclude-result-prefixes="xsl date udt">

	<xsl:template match="result[@module = 'blogs20'][@method = 'post']">
		<xsl:variable name="publish_time" select="//property[@name = 'publish_time']/value/@unix-timestamp" />
		<xsl:variable name="tags" select="//property[@name = 'tags']" />
		<div id="blog_items">
			<div class="blog_item">
				<div class="blog_header">
					<xsl:if test="$publish_time">
						<span umi:element-id="{$pageId}" umi:field-name="publish_time">
							<strong><xsl:text>Добавлено: </xsl:text></strong>
							<strong><xsl:apply-templates select="document(concat('udata://system/convertDate/',$publish_time,'/d.m.Y/'))/udata" /></strong>
						</span>
					</xsl:if>
					<xsl:if test="$tags">
						<span umi:element-id="{@pageId}" umi:field-name="tags">
							<xsl:text>(</xsl:text>
							<xsl:apply-templates select="$tags/value" mode="post_tags" />
							<xsl:text>)</xsl:text>
						</span>
					</xsl:if>
				</div>
				<div umi:element-id="{$pageId}" umi:field-name="content">
					<xsl:value-of select="document(concat('udata://blogs20/postView/',$pageId))/udata/content" disable-output-escaping="yes" />
				</div>
			</div>
			<div class="clear" />
			<a name="subitems"></a>
				<xsl:apply-templates select="document(concat('udata://blogs20/commentsList/',$pageId,'/'))/udata" />
			<h3>Добавить комментарий:</h3><a href="#comment_add"></a>
			<xsl:apply-templates select="document('udata://blogs20/checkAllowComments/')/udata" />
		</div>
	</xsl:template>
	

	<xsl:template match="value" mode="post_tags">
		<a href="/blogs20/postsByTag/{.}">
			<xsl:value-of select="." />
		</a>
		<xsl:if test="position() != last()">
			<xsl:text>, </xsl:text>
		</xsl:if>
	</xsl:template>

</xsl:stylesheet>