<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://i18n/constants.dtd:file">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output encoding="utf-8" method="html" indent="yes" />

	<xsl:template match="mail_registrated_subject">
		<xsl:text>Регистрация на UMI.CMS Demo Site</xsl:text>
	</xsl:template>

	<xsl:template match="mail_registrated">
		<p>
			<xsl:text>Здравствуйте, </xsl:text>
			<xsl:value-of select="lname" />
			<xsl:text> </xsl:text>
			<xsl:value-of select="fname" />
			<xsl:text> </xsl:text>
			<xsl:value-of select="father_name" />
			<xsl:text>,</xsl:text><br />
			<xsl:text>Вы зарегистрировались на сайте </xsl:text>
			<a href="http://{domain}">
				<xsl:value-of select="domain" />
			</a>.
		</p>
		<p>
			<xsl:text>Логин: </xsl:text>
			<xsl:value-of select="login" /><br />
			<xsl:text>Пароль: </xsl:text>
			<xsl:value-of select="password" />
		</p>
		<p>
			<div class="notice">
				<xsl:text>Чтобы активировать Ваш аккаунт, необходимо перейти по ссылке, либо скопировать ее в адресную строку браузера:</xsl:text><br />
				<a href="{activate_link}">
					<xsl:value-of select="activate_link" />
				</a>
			</div>
		</p>
	</xsl:template>

	<xsl:template match="mail_admin_registrated_subject">
		<xsl:text>Зарегистрировался новый пользователь</xsl:text>
	</xsl:template>

	<xsl:template match="mail_admin_registrated">
		<p>
			<xsl:text>Зарегистрировался новый пользователь "</xsl:text>
			<xsl:value-of select="login" />
			<xsl:text>".</xsl:text>
		</p>
	</xsl:template>

</xsl:stylesheet>