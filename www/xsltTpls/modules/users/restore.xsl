<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://i18n/constants.dtd:file">

<xsl:stylesheet version="1.0" xmlns="http://www.w3.org/1999/xhtml" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="result[@module = 'users'][@method = 'restore']">
		<xsl:apply-templates select="document(concat('udata://users/restore/', $param0 ,'/'))/udata" />
	</xsl:template>

	<xsl:template match="udata[@module = 'users'][@method = 'restore'][@status = 'success']">
		<p>
			<xsl:text>&forget-message;</xsl:text>
		</p>
	</xsl:template>

	<xsl:template match="udata[@module = 'users'][@method = 'restore'][@status = 'fail']">
		<xsl:text>&activation-error;</xsl:text>
	</xsl:template>

</xsl:stylesheet>
