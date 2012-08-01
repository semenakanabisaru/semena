<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://i18n/constants.dtd:file">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output encoding="utf-8" method="html" indent="yes" />

	<xsl:template match="answer_mail_subj">
		<xsl:text>[#</xsl:text>
		<xsl:value-of select="ticket" />
		<xsl:text>] Ответ на Ваш вопрос</xsl:text>
	</xsl:template>

	<xsl:template match="answer_mail">
		<xsl:text>Здравствуйте,</xsl:text><br /><br />
		<xsl:text>Ответ на Ваш вопрос Вы можете прочитать по следующему адресу:</xsl:text><br />
		<a href="{question_link}"><xsl:value-of select="question_link" /></a><br /><br /><hr />
		<xsl:text>С уважением,</xsl:text><br />
		<xsl:text>Администрация сайта </xsl:text><b><xsl:value-of select="domain" /></b>
	</xsl:template>

	<xsl:template match="confirm_mail_subj_user">
		<xsl:text>Спасибо за Ваш вопрос</xsl:text>
	</xsl:template>

	<xsl:template match="confirm_mail_user">
		<xsl:text>Вашему вопросу присвоен тикет #</xsl:text>
		<xsl:value-of select="ticket" /><br />
		<xsl:text>Мы ответим Вам в ближайшее время.</xsl:text><br /><br /><hr />
		<xsl:text>С уважением,</xsl:text><br />
		<xsl:text>Администрация сайта </xsl:text><b><xsl:value-of select="domain" /></b>
	</xsl:template>

	<xsl:template match="confirm_mail_subj_admin">
		<xsl:text>Новый вопрос в FAQ</xsl:text>
	</xsl:template>

	<xsl:template match="confirm_mail_admin">
		<xsl:text>В базу знаний поступил новый вопрос:</xsl:text><br />
		<a href="{question_link}">
			<xsl:value-of select="question_link" />
		</a><br />
		<hr />
		<xsl:value-of select="question" />
		<hr />
	</xsl:template>

</xsl:stylesheet>