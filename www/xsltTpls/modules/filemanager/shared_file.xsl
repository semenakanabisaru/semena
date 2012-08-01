<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet	version="1.0"
					xmlns="http://www.w3.org/1999/xhtml"
					xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
					xmlns:date="http://exslt.org/dates-and-times"
					xmlns:udt="http://umi-cms.ru/2007/UData/templates"
					exclude-result-prefixes="xsl date udt">

	<xsl:template match="result[@module = 'filemanager'][@method = 'shared_file']">
		<div class="files_in">
			<div class="text">
				<div><xsl:value-of select="//property[@name = 'content']/value" disable-output-escaping="yes" /></div>
			</div>
			<xsl:apply-templates select="document(concat('udata://filemanager/shared_file//', $pageId))/udata" />
		</div>
	</xsl:template>

	<xsl:template match="udata[@module = 'filemanager'][@method = 'shared_file']">
		<div class="gray_block">
			<p>
				<xsl:text>Имя файла: </xsl:text>
				<xsl:value-of select="file_name" />
			</p>
			<p>
				<xsl:text>Размер файла: </xsl:text>
				<xsl:value-of select="file_size" />
				<xsl:text> Kb</xsl:text>
			</p>
			<p>
				<xsl:text>Если закачивание файла не начнется через 10 сек, кликните </xsl:text>
				<a href="{download_link}">
					<xsl:text>по этой ссылке</xsl:text>
				</a>
			</p>
			<script languge="text/javascript">
				<xsl:text>window.setTimeout('document.location.href="</xsl:text>
				<xsl:value-of select="download_link" />
				<xsl:text>";', 10000);</xsl:text>
			</script>
		</div>
	</xsl:template>

</xsl:stylesheet>