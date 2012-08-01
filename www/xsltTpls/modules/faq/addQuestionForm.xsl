<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM	"ulang://i18n/constants.dtd:file">

<xsl:stylesheet	version="1.0"
					xmlns="http://www.w3.org/1999/xhtml"
					xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
					xmlns:date="http://exslt.org/dates-and-times"
					xmlns:udt="http://umi-cms.ru/2007/UData/templates"
					exclude-result-prefixes="xsl date udt">

	<xsl:template match="udata[@module = 'faq'][@method = 'addQuestionForm']">
		<form method="post" action="{action}" onsubmit="site.forms.data.save(this); return site.forms.check(this);">
			<div class="form_element">
				<label class="required">
					<span><xsl:text>Ник:</xsl:text></span>
					<input type="text" name="nick" class="textinputs" />
				</label>
			</div>
			<div class="form_element">
				<label class="required">
					<span><xsl:text>&e-mail;:</xsl:text></span>
					<input type="text" name="email" class="textinputs" />
				</label>
			</div>
			<div class="form_element">
				<label class="required">
					<span><xsl:text>Тема:</xsl:text></span>
					<input type="text" name="title" class="textinputs" />
				</label>
			</div>
			<div class="form_element">
				<label class="required">
					<span><xsl:text>Вопрос:</xsl:text></span>
					<textarea name="question"></textarea>
				</label>
			</div>
			<div class="form_element">
				<xsl:apply-templates select="document('udata://system/captcha/')/udata" />
			</div>
			<div class="form_element">
				<input type="submit" class="button" value="Задать вопрос" />
			</div>
		</form>
	</xsl:template>

</xsl:stylesheet>