<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet	version="1.0"
					xmlns="http://www.w3.org/1999/xhtml"
					xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
					xmlns:date="http://exslt.org/dates-and-times"
					xmlns:udt="http://umi-cms.ru/2007/UData/templates"
					xmlns:umi="http://www.umi-cms.ru/TR/umi"
					exclude-result-prefixes="xsl date udt umi">

	<xsl:template match="result[@module = 'faq'][@method = 'question']">
		<h3><xsl:value-of select="//property[@name = 'question']/title" /></h3>
		<div><xsl:value-of select="//property[@name = 'question']/value" /></div>
		<h3><xsl:value-of select="//property[@name = 'answer']/title" /></h3>
		<div umi:element-id="{$pageId}" umi:field-name="answer" umi:empty="Ответ на вопрос">
			<xsl:value-of select="//property[@name = 'answer']/value" />
		</div>
	</xsl:template>

</xsl:stylesheet>