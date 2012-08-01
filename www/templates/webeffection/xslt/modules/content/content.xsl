<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://i18n/constants.dtd:file">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:umi="http://www.umi-cms.ru/TR/umi">

	<xsl:template match="result[@module = 'content'][@method = 'content']">
		<div umi:field-name="content" umi:element-id="{$pageId}" umi:empty="&empty-page-content;">
			<xsl:apply-templates select=".//property[@name = 'content']" />
		</div>
	</xsl:template>

</xsl:stylesheet>