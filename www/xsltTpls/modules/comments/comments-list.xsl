<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM	"ulang://i18n/constants.dtd:file">

<xsl:stylesheet	version="1.0"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:umi="http://www.umi-cms.ru/TR/umi"
				xmlns:xlink="http://www.w3.org/TR/xlink"
				exclude-result-prefixes="xsl umi xlink">

	<xsl:template match="udata[@module = 'comments' and @method = 'insert']">
		<hr /><a name="comments" />
		<h4>
			<xsl:text>&comments;:</xsl:text>
		</h4>
		<div class="comments" umi:module="comments" umi:add-method="none" umi:region="list" umi:sortable="sortable">
			<xsl:apply-templates select="items/item" mode="comment" />
			<xsl:choose>
				<xsl:when test="$userType = 'guest'">
					<xsl:apply-templates select="add_form" mode="guest" />
				</xsl:when>
				<xsl:otherwise>
					<xsl:apply-templates select="add_form" mode="user" />
				</xsl:otherwise>
			</xsl:choose>
		</div>
	</xsl:template>
	
	<xsl:template match="udata[@method = 'insert']/add_form" mode="guest">
		<a name="add-comment" />
		<form method="post" action="{action}" id="form_for_comments">
			<div class="form_element">
				<label>
					<span>Заголовок комментария:</span>
					<input type="text" name="title" class="textinputs" />
				</label>
			</div>
			<div class="form_element">
				<label>
					<span>Ваш ник:</span>
					<input type="text" name="author_nick" class="textinputs" />
				</label>
			</div>
			<div class="form_element">
				<label>
					<span>Ваш e-mail:</span>
					<input type="text" name="author_email" class="textinputs" />
				</label>
			</div>
			<div class="form_element">
				<label>
					<span>Текст комментария:</span>
					<textarea name="comment"></textarea>
				</label>
			</div>
			<div class="form_element">
				<xsl:apply-templates select="document('udata://system/captcha/')/udata" />
			</div>
			<div class="form_element">
				<input type="submit" class="button" value="Добавить комментарий" />
			</div>
		</form>
	</xsl:template>
	
	<xsl:template match="udata[@method = 'insert']/add_form" mode="user">
		<a name="add-comment" />
		<form method="post" action="{action}">
			<div class="form_element">
				<label>
					<span><xsl:text>&comment-body;:</xsl:text></span>
					<textarea name="comment" />
				</label>
			</div>
			<div class="form_element">
				<input type="submit" class="button wide_but" value="&comment-submit;" />
			</div>
		</form>
	</xsl:template>

	<xsl:template match="item" mode="comment">
		<div class="item" umi:element-id="{@id}" umi:region="row">
			<p class="descr" umi:field-name="message" umi:delete="delete" umi:empty="&empty;">
				<xsl:value-of select="." disable-output-escaping="yes" />
			</p>
		</div>
	</xsl:template>
</xsl:stylesheet>