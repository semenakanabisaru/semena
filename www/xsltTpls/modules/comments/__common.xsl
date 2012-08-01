<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM	"ulang://i18n/constants.dtd:file">

<xsl:stylesheet	version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:include href="comments-list.xsl" />

	<xsl:template match="udata[@method = 'countComments']">
		<xsl:param name="link" />
		<a href="{$link}#add-comment" class="comments">
			<xsl:text>&no-comments;</xsl:text>
		</a>
	</xsl:template>
	
	<xsl:template match="udata[@method = 'countComments'][. &gt; 0]">
		<xsl:param name="link" />
		<a href="{$link}#comments" class="comments">
			<xsl:text>&comments; </xsl:text>
			<strong>
				<xsl:value-of select="concat(' (', ., ')')" />
			</strong>
		</a>
	</xsl:template>
</xsl:stylesheet>