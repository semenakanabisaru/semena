<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet	version="1.0"
				xmlns="http://www.w3.org/1999/xhtml"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:date="http://exslt.org/dates-and-times"
				xmlns:udt="http://umi-cms.ru/2007/UData/templates"
				xmlns:umi="http://www.umi-cms.ru/TR/umi"
				exclude-result-prefixes="xsl date udt">

	<xsl:include href="conf.xsl" />
	<xsl:include href="confs_list.xsl" />
	<xsl:include href="conf_last_message.xsl" />
	<xsl:include href="topic.xsl" />
	<xsl:include href="topic_post.xsl" />
	<xsl:include href="topic_last_message.xsl" />
	<xsl:include href="message_post.xsl" />

</xsl:stylesheet>