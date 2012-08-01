<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://i18n/constants.dtd:file">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:umi="http://www.umi-cms.ru/TR/umi">

	<xsl:output encoding="utf-8" method="html" indent="yes" />

	<xsl:param name="template-name" />
	<xsl:param name="template-resources" />

	<xsl:template match="/">
		<html>
			<head>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				<meta http-equiv="X-UA-Compatible" content="IE=edge" />
				<title><xsl:value-of select="result/@title" /></title>
				<link rel="search" type="application/opensearchdescription+xml" href="/xsl/onlineSearch/description.xml" title="Search on UMI.CMS" />
				<xsl:value-of select="document('udata://system/includeQuickEditJs/')/udata" disable-output-escaping="yes" />
				<link type="text/css" rel="stylesheet" href="{$template-resources}css/design/__common.css?{/result/@system-build}" />
				<link rel="canonical" href="http://{concat(result/@domain, result/@request-uri)}"/>
				<script src="/js/client/vote.js?{/result/@system-build}" type="text/javascript" />
			</head>
			<body>
				<div id="main">
					<div id="header">
						<div class="left">
							<a href="/">
								<xsl:call-template name="makeThumbnail">
									<xsl:with-param name="element_id" select="$siteInfoPage/@id" />
									<xsl:with-param name="field_name" select="'logo'" />
									<xsl:with-param name="width" select="200" />
									<xsl:with-param name="alt" select="$siteInfo[@name = 'logo']/title" />
								</xsl:call-template>
							</a>
						</div>
						<div class="center">
							<h1 umi:element-id="{$siteInfoPage/@id}" umi:field-name="brand_name" umi:empty="&empty-site-name;">
								<xsl:value-of select="$siteInfo[@name = 'brand_name']/value" />
							</h1>
							<div umi:element-id="{$siteInfoPage/@id}" umi:field-name="slogan" umi:empty="&empty;">
								<xsl:value-of select="$siteInfo[@name = 'slogan']/value" />
							</div>
						</div>
						<xsl:apply-templates select="document('udata://emarket/cart')" mode="basket" />
						<div class="clear" />
					</div>
					<div id="page">
						<div class="left">
							<xsl:apply-templates select="document('udata://content/menu/null/3/')/udata" />
							<xsl:apply-templates select="result/user" />
						</div>
						<div class="center">
							<div class="in">
								<h1 umi:element-id="{$pageId}" umi:field-name="h1" umi:empty="&empty-page-name;">
									<xsl:value-of select="result/@header" />
								</h1>
								<xsl:apply-templates select="document('udata://system/listErrorMessages/')/udata" />
								<xsl:apply-templates select="result"/>
							</div>
						</div>
						<div class="clear" />
					</div>
					<div id="footer">
						<div umi:element-id="{$siteInfoPage/@id}" umi:field-name="copyright" umi:empty="&empty;">
							<xsl:text>&copyright; </xsl:text>
							<xsl:value-of select="$siteInfo[@name = 'copyright']/value" />
						</div>
					</div>
				</div>
			</body>
		</html>
	</xsl:template>

	<xsl:include href="../__common.xsl" />

</xsl:stylesheet>