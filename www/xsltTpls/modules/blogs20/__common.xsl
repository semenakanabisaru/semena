<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet	version="1.0"
				xmlns="http://www.w3.org/1999/xhtml"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:date="http://exslt.org/dates-and-times"
				xmlns:udt="http://umi-cms.ru/2007/UData/templates"
				xmlns:xlink="http://www.w3.org/TR/xlink"
				exclude-result-prefixes="xsl date udt xlink">

	<xsl:include href="blog.xsl" />
	<xsl:include href="post.xsl" />
	<xsl:include href="postsList.xsl" />
	<xsl:include href="commentsList.xsl" />
	<xsl:include href="checkAllowComments.xsl" />

</xsl:stylesheet>