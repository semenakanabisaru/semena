<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM	"ulang://common">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	
	<!-- Current language info -->
	<xsl:variable name="lang-prefix" select="/result/@pre-lang"/>
	
	<!-- Header and title of current page -->
	<xsl:variable name="header"	select="document('udata://core/header')/udata"/>
	<xsl:variable name="title" select="concat('&cms-name; - ', $header)" />
	
	<!-- Skins and langs list from system settings -->
	<xsl:variable name="skins" select="document('udata://system/getSkinsList/')/udata" />
	<xsl:variable name="interface-langs" select="document('udata://system/getInterfaceLangsList/')/udata" />
	<xsl:variable name="from_page" select="document('udata://system/getCurrentURI/1/')/udata"/>
	
	<xsl:template match="/">
		<html>
			<head>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				<title>
					<xsl:value-of select="$title" />
				</title>
				<style>
					html {
						height: 100%;
					}
					body {
						background: url(/images/cms/admin/mac/common/bg1.jpg) center center;
						height: 100%;
						margin: 0;
					}
					#main {
						min-height: 100%;
						_height: 100%;
					}
					#main #auth {
						position: absolute;
						width: 414px;
						height: 250px;
						top: 50%;
						left: 50%;
						margin: -125px 0 0 -207px;
						color: #333;
						font-family: Arial, Helvetica, Sans-Serif;
						font-size: 12px;
					}
					#main #auth .head {
						background: url(/images/cms/admin/mac/common/login_header.png) no-repeat;
						height: 28px;
						line-height: 27px;
						text-indent: 8px;
						font-family: Tahoma, Arial, Helvetica, Sans-Serif;
						font-size: 14px;
						font-weight: bold;
					}
					#main #auth .head span {
						background: url(/images/cms/admin/mac/common/butterfly.png) no-repeat;
						height: 24px;
						width: 25px;
						float: left;
						display: block;
						margin: 2px 0 0 10px;
					}
					#main #auth .title {
						background: white url(/images/cms/admin/mac/icons/medium/auth.png) no-repeat 137px 0;
						height: 55px;
						text-align: center;
						text-indent: 54px;
						display: none;
					}
					#main #auth .title strong {
						text-transform: uppercase;
						padding-top: 20px;
						display: block;
					}
					#main #auth .cont {
						background: white;
						border: solid 1px white;
						position: relative;
					}
					#main #auth .error {
						margin: 5px 0 0 125px;
						color: red;
					}
					#main #auth form {
						padding: 10px 0;
						*padding: 10px 0 0 0;
						width: 100%;
						margin: 3px 0 0 0;
					}
					#main #auth form label {
						display: block;
						margin: 5px 0;
						text-align: right;
					}
					#main #auth form input,
					#main #auth form select {
						border: solid 1px #d3d3d3;
						font-family: Arial, Helvetica, Sans-Serif;
						font-size: 12px;
						margin: 0 65px 0 15px;
					}
					#main #auth form input,
					#main #auth form select {
						width: 224px;
					}
					#main #auth form div label {
						display: inline;
						line-height: 16px;
						margin: 0;
					}
					#main #auth form div label input {
						margin: 2px 10px 0 0;
						display: block;
						float: left;
						border: none;
					}
					#main #auth form div {
						margin: 20px 0 0 0;
					}
					#main #auth form div input {
						width: auto;
						margin: 0;
					}
					#main #auth form div div {
						margin: 0 50px 0 125px;
						position: relative;
						float: left;
						*width: 70px;
						*height: 17px;
					}
					#main #auth form div div input {
						background: url(/images/cms/admin/mac/common/button_center.jpg) repeat-x;
						border: none;
						color: white;
						cursor: pointer;
						padding: 1px 0;
						height: 17px;
						width:50px;
						display:inline-block;
						text-align:center;
						font-family: Tahoma, Arial, Helvetica, Sans-Serif;
						font-size: 11px;
						font-weight: bold;
						*zoom:1;
						*position:absolute;
						*left:0;
					}
					#main #auth form div div span {
						display: block;
						height: 17px;
						position: absolute;
						top: 0;
						width: 5px;
					}
					#main #auth form div div span.l {
						background: url(/images/cms/admin/mac/common/button_left.jpg) no-repeat;
						left: 0;
					}
					#main #auth form div div span.r {
						background: url(/images/cms/admin/mac/common/button_right.jpg) no-repeat;
						right: 0;
						*right:20px;
					}
					#main #auth .foot {
						background: url(/images/cms/admin/mac/common/login_footer.png) no-repeat;
						height: 15px;
					}
				</style>
			</head>
			<body onload="javascript:document.getElementById('login_field').focus();">
				<div id="main">
					<div id="auth">
						<div class="head"><span />&main-authorization;</div>
						<div class="title">
							<strong>
								<xsl:value-of select="$header" />
							</strong>
						</div>
						<div class="cont">
							<xsl:apply-templates select="result/data/error" />
						
							<form action="{$lang-prefix}/admin/users/login_do/" method="post">
								<input type="hidden" name="from_page" value="{$from_page}"/>
								
								<label>
									<xsl:text>&label-login;</xsl:text>
									<xsl:choose>
										<xsl:when test="/result[@demo='1']">
											<input type="text" id="login_field" name="login" value="demo" />
										</xsl:when>
										<xsl:otherwise>
									<input type="text" id="login_field" name="login" />
										</xsl:otherwise>
									</xsl:choose>
								</label>
								
								<label>
									<xsl:text>&label-password;</xsl:text>
									<xsl:choose>
										<xsl:when test="/result[@demo='1']">
											<input type="password" id="password_field" name="password" value="demo" />
										</xsl:when>
										<xsl:otherwise>
									<input type="password" id="password_field" name="password" />
										</xsl:otherwise>
									</xsl:choose>									
								</label>
								
								<xsl:if test="count($skins//item) &gt; 1">
									<label>
										<xsl:text>&label-skin;</xsl:text>
										<select id="skin_field" name="skin_sel">
											<xsl:apply-templates select="$skins" />
										</select>
									</label>
								</xsl:if>
								<xsl:if test="count($interface-langs//item) &gt; 1">
									<label>
										<xsl:text>&label-interface-lang;</xsl:text>
										<select id="ilang" name="ilang">
											<xsl:apply-templates select="$interface-langs" />
										</select>
									</label>
								</xsl:if>
								<div>
									<div>
										<input type="submit" id="submit_field" value="&label-login-do;" />
										<span class="l" /><span class="r" />
									</div>
									<label>
										<input type="checkbox" value="1" name="u-login-store" />
										<xsl:text>&label-remember-login;</xsl:text>
									</label>
								</div>
							</form>
							<div style="clear:both" />
						</div>
						<div class="foot" />
					</div>
				</div>
			</body>
		</html>
	</xsl:template>
	
	<xsl:template match="udata[@module = 'system' and @method = 'getSkinsList']//item">
		<option value="{@id}">
			<xsl:value-of select="." />
		</option>
	</xsl:template>
	
	<xsl:template match="udata[@module = 'system' and @method = 'getSkinsList']//item[@id = ../@current]">
		<option value="{@id}" selected="selected">
			<xsl:value-of select="." />
		</option>
	</xsl:template>
	
	
	<xsl:template match="udata[@module = 'system' and @method = 'getInterfaceLangsList']//item">
		<option value="{@prefix}">
			<xsl:value-of select="." />
		</option>
	</xsl:template>
	
	<xsl:template match="udata[@module = 'system' and @method = 'getInterfaceLangsList']//item[@prefix = ../@current]">
		<option value="{@prefix}" selected="selected">
			<xsl:value-of select="." />
		</option>
	</xsl:template>
	
	
	<xsl:template match="error">
		<p class="error">
			<strong>
				<xsl:text>&label-error;: </xsl:text>
			</strong>
			<xsl:value-of select="." />
		</p>
	</xsl:template>

</xsl:stylesheet>