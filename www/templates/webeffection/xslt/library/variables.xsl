<?xml version="1.0" encoding="utf-8"?>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:variable name="pageId" select="/result/@pageId" />
	<xsl:variable name="module" select="/result/@module" />
	<xsl:variable name="method" select="/result/@method" />
	<xsl:variable name="requestUri" select="/result/@request-uri" />
	<xsl:variable name="errors" select="document('udata://system/listErrorMessages')/udata" />
	<xsl:variable name="langPrefix" select="/result/@pre-lang" />
	<xsl:variable name="userId" select="/result/user/@id" />
	<xsl:variable name="userType" select="/result/user/@type" />
	<xsl:variable name="userInfo" select="document(concat('uobject://', $userId))/udata" />
	<xsl:variable name="siteInfoPage" select="document('upage://contacts')/udata/page" />
	<xsl:variable name="siteInfo" select="$siteInfoPage//group[@name = 'site_info']/property" />

	<xsl:param name="p" select="'0'" />
	<xsl:param name="search_string" />
	<xsl:param name="param0" />

</xsl:stylesheet>