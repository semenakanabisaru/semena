<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM	"ulang://i18n/constants.dtd:file">

<xsl:stylesheet	version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:include href="price.xsl" />
	<xsl:include href="compare.xsl" />
	<xsl:include href="basket.xsl" />
	<xsl:include href="cart.xsl" />
	<xsl:include href="orderslist.xsl" />
	<xsl:include href="purchase.xsl" />
	<xsl:include href="personal.xsl" />
	
	<xsl:template match="amount">
		<xsl:value-of select="concat(., ' &amount-prefix;')" />
	</xsl:template>
</xsl:stylesheet>