<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://i18n/constants.dtd:file">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output encoding="utf-8" method="html" indent="yes" />

	<xsl:template match="mail_verification_subject">
		<xsl:text>Восстановление пароля</xsl:text>
	</xsl:template>

	<xsl:template match="mail_verification">
		<p>
			<xsl:text>Здравствуйте!</xsl:text><br />
			<xsl:text>Кто-то, возможно Вы, пытается восстановить пароль для пользователя "</xsl:text>
			<xsl:value-of select="login" />
			<xsl:text>" на сайте </xsl:text>
			<a href="http://{domain}"><xsl:value-of select="domain" /></a>.
		</p>
		<p>
			<xsl:text>Если это не Вы, просто проигнорируйте данное письмо.</xsl:text>
		</p>
		<p>
			<xsl:text>Если Вы действительно хотите восстановить пароль, кликните по этой ссылке:</xsl:text><br />
			<a href="{restore_link}"><xsl:value-of select="restore_link" /></a>
		</p>
		<p>
			<xsl:text>С уважением,</xsl:text><br />
			<b>
				<xsl:text>Администрация сайта </xsl:text>
				<a href="http://{domain}"><xsl:value-of select="domain" /></a>
			</b>
		</p>
	</xsl:template>

	<xsl:template match="mail_password_subject">
		<xsl:text>Новый пароль для сайта</xsl:text>
	</xsl:template>

	<xsl:template match="mail_password">
		<p>
			<xsl:text>Здравствуйте!</xsl:text><br />
			<xsl:text>Ваш новый пароль от сайта </xsl:text>
			<a href="http://{domain}"><xsl:value-of select="domain" /></a>.
		</p>
		<p>
			<xsl:text>Логин:	</xsl:text><xsl:value-of select="login" /><br />
			<xsl:text>Пароль:	</xsl:text><xsl:value-of select="password" />
		</p>
		<p>
			<xsl:text>С уважением,</xsl:text><br />
			<b>
				<xsl:text>Администрация сайта </xsl:text>
				<a href="http://{domain}"><xsl:value-of select="domain" /></a>
			</b>
		</p>
	</xsl:template>

</xsl:stylesheet>