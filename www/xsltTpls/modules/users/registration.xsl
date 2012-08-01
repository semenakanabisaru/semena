<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM	"ulang://i18n/constants.dtd:file">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:xlink="http://www.w3.org/TR/xlink">

	<xsl:template match="result[@method = 'registrate']">
		<xsl:apply-templates select="document('udata://users/registrate')/udata" />
	</xsl:template>

	<xsl:template match="udata[@method = 'registrate']">
		<form id="registrate" enctype="multipart/form-data" method="post" action="{$langPrefix}/users/registrate_do/" onSubmit="return checkForm(this, login, email, password, password_confirm);" class="form">
			<div>
				<label>
					<span>
						<xsl:text>&login; </xsl:text> <span class="required">*</span>
					</span>
					<input type="text" name="login" />
				</label>
				<div class="clear" />
			</div>
			<div>
				<label>
					<span>
						<xsl:text>&e-mail; </xsl:text> <span class="required">*</span>
					</span>
					<input type="text" name="email" />
				</label>
				<div class="clear" />
			</div>
			<div>
				<label>
					<span>
						<xsl:text>&password; </xsl:text> <span class="required">*</span>
					</span>
					<input type="password" name="password" />
				</label>
				<div class="clear" />
			</div>
			<div>
				<label>
					<span>
						<xsl:text>&password-confirm; </xsl:text> <span class="required">*</span>
					</span>
					<input type="password" name="password_confirm" />
				</label>
				<div class="clear" />
			</div>
			<xsl:apply-templates select="document(@xlink:href)/udata" /> 
			<div class="captcha_style">
				<xsl:apply-templates select="document('udata://system/captcha')/udata" />
			</div>
			<div class="clear"></div>
			<div>
				<input type="submit" class="button" value="&registration-do;" />
				<div class="clear" />
			</div>
		</form>
	</xsl:template>

	<xsl:template match="result[@method = 'registrate_done']">
		<h4><xsl:text>&registration-done;</xsl:text></h4>
		<p><xsl:text>&registration-activation-note;</xsl:text></p>
	</xsl:template>

	<xsl:template match="result[@method = 'activate']">
		<xsl:variable name="activation-errors" select="document('udata://users/activate')/udata/error" />
		<xsl:choose>
			<xsl:when test="count($activation-errors)">
				<xsl:apply-templates select="$activation-errors" />
			</xsl:when>
			<xsl:otherwise>
				<p><xsl:text>&account-activated;</xsl:text></p>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>

	<!-- User settings -->
	<xsl:template match="result[@method = 'settings']">
		<xsl:apply-templates select="document('udata://users/settings')/udata" />
	</xsl:template>

	<xsl:template match="udata[@method = 'settings']">
		<form enctype="multipart/form-data" method="post" action="{$langPrefix}/users/settings_do/" id="con_tab_profile">
			<div>
				<label>
					<span>
						<xsl:text>&login;:</xsl:text>
					</span>
					<input type="text" name="login" disabled="disabled" value="{$userInfo//property[@name = 'login']/value}" />
				</label>
				<div class="clear" />
			</div>
			<div>
				<label>
					<span>
						<xsl:text>&password;:</xsl:text>
					</span>
					<input type="password" name="password" />
				</label>
				<div class="clear" />
			</div>
			<div>
				<label>
					<span>
						<xsl:text>&password-confirm;:</xsl:text>
					</span>
					<input type="password" name="password_confirm" />
				</label>
				<div class="clear" />
			</div>
			<div>
				<label>
					<span>
						<xsl:text>&e-mail;:</xsl:text>
					</span>
					<input type="text" name="email" value="{$userInfo//property[@name = 'e-mail']/value}" />
				</label>
				<div class="clear" />
			</div>
			<xsl:apply-templates select="document(concat('udata://data/getEditForm/', $userId))/udata" />
			<div>
				<input type="submit" class="button" value="&save-changes;" />
				<div class="clear" />
			</div>
		</form>
	</xsl:template>

</xsl:stylesheet>