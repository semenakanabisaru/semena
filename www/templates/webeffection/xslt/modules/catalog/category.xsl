<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://i18n/constants.dtd:file">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:umi="http://www.umi-cms.ru/TR/umi">

	<xsl:template match="result[@module = 'catalog'][@method = 'category']">
		<div class="descr" umi:element-id="{$pageId}" umi:field-name="descr">
			<xsl:apply-templates select=".//property[@name = 'descr']" disable-output-escaping="yes"/>
		</div>
		<xsl:apply-templates select="document(concat('udata://catalog/getCategoryList//', @id))/udata" />
		<xsl:apply-templates select="document(concat('udata://catalog/getObjectsList//', @id))/udata" />
	</xsl:template>

</xsl:stylesheet>