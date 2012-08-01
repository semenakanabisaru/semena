<?xml version="1.0" encoding="utf-8"?>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="result[@module = 'news'][@method = 'item']">
		<xsl:apply-templates select=".//property[@name = 'content']" />
		<div>
			<xsl:apply-templates select="document(concat('udata://comments/insert/',$pageId))/udata" />
		</div>
	</xsl:template>

</xsl:stylesheet>