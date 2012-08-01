<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://i18n/constants.dtd:file">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output encoding="utf-8" method="html" indent="yes" />

	<xsl:template match="mail_registrated_subject">
		<xsl:text>Регистрация в интернет-магазине</xsl:text>
	</xsl:template>

<xsl:template match="mail_registrated">
		<p>Здравствуйте, <br />
вы получили это письмо, потому что кто-то, возможно, вы, указал вашу электронную почту при регистрации в интернет-магазине «Семяныч» по адресу semena-kanabisa.ru </p>
<p>Логин: <xsl:value-of select="login" /></p>

<p>Если вы не понимаете, о чем идет речь – просто проигнорируйте это письмо. </p>

<p>Чтобы активировать Ваш аккаунт, <br />
необходимо перейти по ссылке, либо скопировать ее в адресную строку браузера:</p>

<p><a href="{activate_link}"><xsl:value-of select="activate_link" /></a></p>

 <p><i>Данное письмо сгенерировано автоматически – отвечать на него не нужно.</i></p>
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