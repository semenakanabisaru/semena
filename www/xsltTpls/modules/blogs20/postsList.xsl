<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet	version="1.0"
				xmlns="http://www.w3.org/1999/xhtml"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:date="http://exslt.org/dates-and-times"
				xmlns:udt="http://umi-cms.ru/2007/UData/templates"
				xmlns:umi="http://www.umi-cms.ru/TR/umi"
				exclude-result-prefixes="xsl date udt">

	<xsl:template match="udata[@module = 'blogs20'][@method = 'postsList']">
		<div id="blog_items">
			<xsl:apply-templates select="items/item" mode="page" />
		</div>
		<xsl:apply-templates select="total" />
	</xsl:template>

	<xsl:template match="item" mode="page">
		<div class="blog_item">
			<div class="blog_header">
				<a href="{post_link}" umi:element-id="{@id}" umi:field-name="name" umi:delete="delete">
					<strong><xsl:value-of select="name" /></strong>
				</a>
				<xsl:if test="publish_time">
					<span umi:element-id="{@id}" umi:field-name="publish_time" class="adding_blogs news_timestamp">
						<xsl:text>Добавлено </xsl:text>
						<xsl:apply-templates select="document(concat('udata://system/convertDate/',publish_time,'/d.m.Y/'))/udata" />
					</span>
				</xsl:if>
				<xsl:if test="tags">
					<span umi:element-id="{@id}" umi:field-name="tags">
						<xsl:text>(</xsl:text>
						<xsl:apply-templates select="tags/item" mode="post_tags" />
						<xsl:text>)</xsl:text>
					</span>
				</xsl:if>
			</div>
			<div umi:element-id="{@id}" umi:field-name="content" umi:delete="delete">
				<xsl:value-of select="cut" disable-output-escaping="yes" />
			</div>
			<div class="comments">
				<a href="{post_link}#subitems">Комментарии (<xsl:value-of select="comments_count" />)</a> | <a href="{post_link}#additem">Комментировать</a>
			</div>
		<p class="descr"></p>
		</div>
	</xsl:template>

	<xsl:template match="item" mode="post_tags">
		<a href="{link}">
			<xsl:value-of select="tag" />
		</a>
		<xsl:if test="position() != last()">
			<xsl:text>, </xsl:text>
		</xsl:if>
	</xsl:template>

</xsl:stylesheet>