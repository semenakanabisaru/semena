<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://i18n/constants.dtd:file">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="total" />

	<xsl:template match="total[. &gt; ../per_page]">
		<xsl:apply-templates select="document(concat('udata://system/numpages/', ., '/', ../per_page))/udata" />
	</xsl:template>

	<xsl:template match="udata[@module = 'system'][@method = 'numpages']">
		<div class="navigator">
			<xsl:apply-templates select="toprev_link" />
			<xsl:apply-templates select="tobegin_link[../items/item[1] &gt; 1]" />
			<xsl:apply-templates select="items/item" mode="numpages" />
			<xsl:apply-templates select="toend_link[@page-num &gt; ../items/item[last()]/@page-num]" />
			<xsl:apply-templates select="tonext_link" />
		</div>
	</xsl:template>

	<xsl:template match="toprev_link">
		<a class="prev" href="{.}"><xsl:text>&previous-page;</xsl:text></a>
	</xsl:template>

	<xsl:template match="tobegin_link">
		<a href="{.}"><xsl:text>1</xsl:text></a>
	</xsl:template>

	<xsl:template match="tobegin_link[../items/item[1] != 2]">
		<a href="{.}"><xsl:text>1</xsl:text></a><xsl:text>&#8230;</xsl:text>
	</xsl:template>

	<xsl:template match="item" mode="numpages">
		<a href="{@link}"><xsl:value-of select="." /></a>
	</xsl:template>

	<xsl:template match="item[@is-active = '1']" mode="numpages">
		<strong><xsl:value-of select="." /></strong>
	</xsl:template>

	<xsl:template match="toend_link">
		<a href="{.}"><xsl:value-of select="@page-num + 1" /></a>
	</xsl:template>

	<xsl:template match="toend_link[@page-num &gt; ../items/item[last()]]">
		<xsl:text>&#8230;</xsl:text><a href="{.}"><xsl:value-of select="@page-num + 1" /></a>
	</xsl:template>

	<xsl:template match="tonext_link">
		<a class="next" href="{.}"><xsl:text>&next-page;</xsl:text></a>
	</xsl:template>

</xsl:stylesheet>