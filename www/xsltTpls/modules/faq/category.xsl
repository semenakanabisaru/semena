<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet	version="1.0"
				xmlns="http://www.w3.org/1999/xhtml"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:date="http://exslt.org/dates-and-times"
				xmlns:udt="http://umi-cms.ru/2007/UData/templates"
				xmlns:umi="http://www.umi-cms.ru/TR/umi"
				exclude-result-prefixes="xsl date udt umi">

	<xsl:template match="result[@module = 'faq'][@method = 'category']">
		<div id="faq">
			<div umi:element-id="{$pageId}" umi:field-name="content">
				<xsl:value-of select="//property[@name = 'content']/value" disable-output-escaping="yes" />
			</div>
			<xsl:apply-templates select="document(concat('udata://faq/category//', $pageId))/udata" />
			<xsl:apply-templates select="document(concat('udata://faq/addQuestionForm//', $pageId))/udata" />
		</div>
	</xsl:template>

	<xsl:template match="udata[@module = 'faq'][@method = 'category']">
		<xsl:apply-templates select="items/item" mode="question" />
		<xsl:apply-templates select="total" />
	</xsl:template>

	<xsl:template match="item" mode="question">
		<div class="question">
			<a href="{@link}" umi:element-id="{@id}" umi:field-name="name">
				<xsl:value-of select="@name" />
			</a>
			<h4><xsl:text>Вопрос</xsl:text></h4>
			<div><xsl:value-of select="question" /></div>
			<h4><xsl:text>Ответ на вопрос</xsl:text></h4>
			<div umi:element-id="{@id}" umi:field-name="answer" umi:empty="Ответ на вопрос">
				<xsl:value-of select="answer" />
			</div>
		</div>
	</xsl:template>

</xsl:stylesheet>