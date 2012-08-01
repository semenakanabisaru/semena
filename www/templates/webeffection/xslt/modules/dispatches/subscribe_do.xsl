<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet	version="1.0"
				xmlns="http://www.w3.org/1999/xhtml"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:date="http://exslt.org/dates-and-times"
				xmlns:udt="http://umi-cms.ru/2007/UData/templates"
				xmlns:xlink="http://www.w3.org/1999/xlink"
				exclude-result-prefixes="xsl date udt xlink">

	<xsl:template match="result[@module = 'dispatches'][@method = 'subscribe_do']">
		<xsl:apply-templates select="document('udata://dispatches/subscribe_do/')/udata" />
	</xsl:template>

	<xsl:template match="udata[@module = 'dispatches'][@method = 'subscribe_do']">
		<xsl:apply-templates select="result" mode="subscribe_do" />
	</xsl:template>

	<xsl:template match="udata[@module = 'dispatches'][@method = 'subscribe_do'][unsubscribe_link]">
		<xsl:if test="$user-type = 'guest'">
			<p><xsl:text>Вы получили это сообщение, так как адрес Вашей электронной почты был подписан на рассылку.</xsl:text></p>
			<p>
				<xsl:text>Если вы не хотите получать нашу рассылку, Вы можете отказаться от подписки, перейдя по </xsl:text>
				<a href="{unsubscribe_link}"><xsl:text>этой</xsl:text></a>
				<xsl:text> ссылке.</xsl:text>
			</p>
		</xsl:if>
	</xsl:template>

	<xsl:template match="result" mode="subscribe_do">
		<xsl:choose>
			<xsl:when test="$user-type != 'guest'">
				<p><xsl:text>Вы отписались от рассылок.</xsl:text></p>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="." disable-output-escaping="yes" />
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<xsl:template match="result[items]" mode="subscribe_do">
		<p><xsl:text>Вы подписались на рассылки:</xsl:text></p>
		<ul><xsl:apply-templates select="items" mode="subscribe_do" /></ul>
	</xsl:template>

	<xsl:template match="items" mode="subscribe_do">
		<li><xsl:value-of select="." disable-output-escaping="yes" /></li>
	</xsl:template>

</xsl:stylesheet>