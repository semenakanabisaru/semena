<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://i18n/constants.dtd:file">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output encoding="utf-8" method="html" indent="yes" />

	<xsl:template match="comment_for_post_subj">
		<xsl:text>Новый ответ на Вашу публикацию</xsl:text>
	</xsl:template>

	<xsl:template match="comment_for_post_body">
		<xsl:value-of select="name" />
		<xsl:text>, на Вашу публикацию получен новый ответ.</xsl:text><br />
		<xsl:text>Посмотреть его Вы можете, перейдя по ссылке:</xsl:text><br />
		<a href="{link}"><xsl:value-of select="link" /></a>
	</xsl:template>

	<xsl:template match="comment_for_comment_subj">
		<xsl:text>Новый ответ на Ваш комментарий</xsl:text>
	</xsl:template>

	<xsl:template match="comment_for_comment_body">
		<xsl:value-of select="name" />
		<xsl:text>, на Ваш комментарий получен новый ответ.</xsl:text><br />
		<xsl:text>Посмотреть его Вы можете, перейдя по ссылке:</xsl:text><br />
		<a href="{link}"><xsl:value-of select="link" /></a>
	</xsl:template>

</xsl:stylesheet>