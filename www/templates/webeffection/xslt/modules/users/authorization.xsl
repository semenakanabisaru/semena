<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://i18n/constants.dtd:file">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="user">
		<div class="login">
			<div>
				<xsl:text>&welcome;</xsl:text>
			</div>
			<div>
				<xsl:apply-templates select="$userInfo//property[@name = 'lname']" />
				<xsl:apply-templates select="$userInfo//property[@name = 'fname']" />
				<xsl:apply-templates select="$userInfo//property[@name = 'father_name']" />
			</div>
			<p class="p_user">
				<a href="#" title="" id="on_edit_in_place" class="link_transfer_class">&on-fast-edit;</a>
			</p>
			<p class="p_user">
				<a href="/admin/" title="">&to-admin;</a>
			</p>
			<div>
				<a href="{$langPrefix}/users/logout/">
					<xsl:text>&log-out;</xsl:text>
				</a>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="property[@name = 'fname' or @name = 'lname' or @name = 'father_name']">
		<xsl:value-of select="value" />
		<xsl:text> </xsl:text>
	</xsl:template>

	<xsl:template match="user[@type = 'guest']">
		<form class="login" action="{$langPrefix}/users/login_do/" method="post">
			<xsl:choose>
				<xsl:when test="/result[@demo='1']">
			<div>
						<input type="text" value="demo" name="login" onfocus="javascript: if(this.value == '&login;') this.value = '';" onblur="javascript: if(this.value == '') this.value = '&login;';" />
					</div>
					<div>
						<input type="password" value="demo" name="password" onfocus="javascript: if(this.value == '&password;') this.value = '';" onblur="javascript: if(this.value == '') this.value = '&password;';" />
					</div>
				</xsl:when>
				<xsl:otherwise>
					<div>			
				<input type="text" value="&login;" name="login" onfocus="javascript: if(this.value == '&login;') this.value = '';" onblur="javascript: if(this.value == '') this.value = '&login;';" />
			</div>
			<div class="clear" />
			<div>
				<input type="password" value="&password;" name="password" onfocus="javascript: if(this.value == '&password;') this.value = '';" onblur="javascript: if(this.value == '') this.value = '&password;';" />
			</div>
				</xsl:otherwise>
			</xsl:choose>
			<div>
				<input type="submit" class="button" value="&log-in;" />
			</div>
			<div>
				<a href="{$langPrefix}/users/registrate/">
					<xsl:text>&registration;</xsl:text>
				</a>
			</div>
			<div>
				<a href="{$langPrefix}/users/forget/">
					<xsl:text>&forget-password;</xsl:text>
				</a>
			</div>
		</form>
	</xsl:template>

	<xsl:template match="result[@method = 'login' or @method = 'login_do' or @method = 'auth']">
		<xsl:if test="user[@type = 'guest'] and @method = 'login_do'">
			<p><xsl:text>&user-reauth;</xsl:text></p>
		</xsl:if>
		<xsl:apply-templates select="document('udata://users/auth/')/udata" />
	</xsl:template>

	<xsl:template match="udata[@module = 'users'][@method = 'auth']">
		<form method="post" action="/users/login_do/">
			<input type="hidden" name="from_page" value="{from_page}" />
			<div>
				<label>
					<span>
						<xsl:text>&login;:</xsl:text>
					</span>
					<input type="text" name="login" class="textinputs form_input" value="&login;" onfocus="javascript: if(this.value == '&login;') this.value = '';" onblur="javascript: if(this.value == '') this.value = '&login;';" />
				</label>
				<div class="clear" />
			</div>
			<div>
				<label>
					<span>
						<xsl:text>&password;:</xsl:text>
					</span>
					<input type="password" name="password" class="textinputs" value="&password;" onfocus="javascript: if(this.value == '&password;') this.value = '';" onblur="javascript: if(this.value == '') this.value = '&password;';" />
				</label>
				<div class="clear" />
			</div>
			<div>
				<div style="float:right;">
					<a href="{$langPrefix}/users/registrate/">
						<xsl:text>&registration;</xsl:text>
					</a>
					<a href="/users/forget/" style="margin:0 15px;">
						<xsl:text>&forget-password;</xsl:text>
					</a>
				</div>
				<input type="submit" class="button" value="&log-in;" />
			</div>
		</form>
	</xsl:template>


	<xsl:template match="udata[@module = 'users'][@method = 'auth'][user_id]">
		<div>
			<xsl:text>&welcome; </xsl:text>
			<xsl:choose>
				<xsl:when test="translate(user_name, ' ', '') = ''"><xsl:value-of select="user_login" /></xsl:when>
				<xsl:otherwise><xsl:value-of select="user_name" /> (<xsl:value-of select="user_login" />)</xsl:otherwise>
			</xsl:choose>
		</div>
		<div>
			<a href="{$langPrefix}/users/logout/">
				<xsl:text>&log-out;</xsl:text>
			</a>
		</div>
	</xsl:template>

</xsl:stylesheet>