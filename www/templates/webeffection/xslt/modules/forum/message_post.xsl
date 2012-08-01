<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet	version="1.0"
				xmlns="http://www.w3.org/1999/xhtml"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:date="http://exslt.org/dates-and-times"
				xmlns:udt="http://umi-cms.ru/2007/UData/templates"
				xmlns:umi="http://www.umi-cms.ru/TR/umi"
				exclude-result-prefixes="xsl date udt umi">

	<xsl:template match="udata[@module = 'forum'][@method = 'message_post']" />

	<xsl:template match="udata[@module = 'forum'][@method = 'message_post'][action]">
		<a name="add"></a>
		<form method="post" action="{action}" onsubmit="site.forms.data.save(this); return site.forms.check(this);" class="form_conf">
			<xsl:choose>
				<xsl:when test="$userId = '2373'">
					<div class="form_element">
						<label class="required">
							<span><xsl:text>Имя:</xsl:text></span>
							<input type="text" name="nickname" class="textinputs" />
						</label>
					</div>
					<div class="form_element">
						<label class="required">
							<span><xsl:text>Email:</xsl:text></span>
							<input type="text" name="email" class="textinputs" />
						</label>
					</div>
				</xsl:when>
				<xsl:otherwise>
					<input type="hidden" name="login" disabled="disabled" />
				</xsl:otherwise>
			</xsl:choose>
			<div class="form_element">
				<label class="required">
					<span><xsl:text>Заголовок сообщения:</xsl:text></span>
					<input type="text" name="title" class="textinputs" />
				</label>
			</div>
			<div class="form_element">
				<label class="required">
					<span><xsl:text>Комментарии:</xsl:text></span>
					<textarea name="body"></textarea>
				</label>
			</div>
			<div class="form_element">
				<xsl:apply-templates select="document('udata://system/captcha/')/udata" />
			</div>
			<div class="form_element">
				<input type="submit" class="button" value="Добавить cообщение" />
			</div>
		</form>
	</xsl:template>

</xsl:stylesheet>