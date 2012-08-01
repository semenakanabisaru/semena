<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet	version="1.0"
				xmlns="http://www.w3.org/1999/xhtml"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:date="http://exslt.org/dates-and-times"
				xmlns:udt="http://umi-cms.ru/2007/UData/templates"
				xmlns:xlink="http://www.w3.org/1999/xlink"
				exclude-result-prefixes="xsl date udt xlink">

	<xsl:template match="result[@module = 'webforms'][@method = 'page']">
		<xsl:apply-templates select="document(concat('udata://webforms/add/', //property[@name = 'form_id']/value))/udata" />
	</xsl:template>

</xsl:stylesheet>