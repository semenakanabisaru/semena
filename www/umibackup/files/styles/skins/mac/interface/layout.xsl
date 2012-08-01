<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">

<xsl:stylesheet
	version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:xlink="http://www.w3.org/TR/xlink"
	exclude-result-prefixes="xlink">

	<xsl:template match="/">
		<html>
			<head>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				<meta http-equiv="X-UA-Compatible" content="IE=edge" />
				<title>
					<xsl:value-of select="$title" />
				</title>
				<script type="text/javascript">
					<![CDATA[
						if ( self.parent && !(self.parent===self) && (self.parent.frames.length!=0) )
							self.parent.location = document.location;
					]]>
				</script>
				<link type="text/css" rel="stylesheet" href="/styles/skins/mac/design/css/style.css?{$system-build}" />
				<link type="text/css" rel="stylesheet" href="/js/jquery/jquery.jgrowl.css?{$system-build}" />

				<script type="text/javascript" language="javascript" src="/js/jquery/jquery.js?{$system-build}"></script>
				<script type="text/javascript" language="javascript" src="/js/jquery/jquery-ui.js?{$system-build}"></script>
				<script type="text/javascript" language="javascript" src="/js/jquery/jquery-ui-i18n.js?{$system-build}"></script>
				<script type="text/javascript" src="/js/jquery/jquery.umipopups.js?{$system-build}" charset="utf-8" />
				<script type="text/javascript" language="javascript" src="/js/jquery/jquery.contextmenu.js?{$system-build}"></script>
				<script type="text/javascript" language="javascript" src="/js/client/ZeroClipboard.js?{$system-build}"></script>


				<script type="text/javascript" language="javascript" src="/js/jquery/jquery.jgrowl_minimized.js?{$system-build}"></script>

				<xsl:choose>
					<xsl:when test="$module='webforms'">
						<script type="text/javascript" src="/ulang/{$iface-lang}/common/content/date/data/{$module}?js;{$system-build}" charset="utf-8"></script>
					</xsl:when>
					<xsl:otherwise>
						<script type="text/javascript" src="/ulang/{$iface-lang}/common/content/date/{$module}?js;{$system-build}" charset="utf-8"></script>
					</xsl:otherwise>
				</xsl:choose>

				<xsl:if test="$module='stat'">
					<script type="text/javascript" src="https://www.google.com/jsapi"></script>
				</xsl:if>

				<script type="text/javascript" language="javascript" src="/styles/common/js/compressed.js?{$system-build}"></script>
				<script type="text/javascript" language="javascript" src="/styles/skins/mac/design/js/scripts.js?{$system-build}"></script>
				<script	type="text/javascript" src="/js/smc/compressed.js?{$system-build}"></script>

				<script type="text/javascript">
					var	interfaceLang =	'<xsl:value-of select="$iface-lang"/>';
					window.pre_lang = '<xsl:value-of select="$lang-prefix" />';
					window.domain = '<xsl:value-of select="$domain" />';
					window.domain_id = '<xsl:value-of select="$domain-id" />';
					window.lang_id  = '<xsl:value-of select="$lang-id" />';
					window.is_page  = <xsl:value-of select="boolean(/result/data/page)" />;
					window.edition = '<xsl:value-of select="/result/@edition"/>';
					window.is_new   = <xsl:value-of select="boolean(not(/result/data/*/@id))" />;
					window.page_id  = <xsl:choose><xsl:when test="/result/data/page/@id"><xsl:value-of select="/result/data/page/@id" /></xsl:when><xsl:otherwise>0</xsl:otherwise></xsl:choose>;
					window.settingsStoreData = <xsl:apply-templates select="document('udata://users/loadUserSettings/')" mode="settings-store" />;

					window.session =  new SessionControl(<xsl:value-of select="/result/@session-lifetime"/>, '<xsl:value-of select="$myPerms///module[@name='config']/@access"/>');
				</script>

				<xsl:if test="count(//field[@type = 'wysiwyg'])">
					<xsl:call-template name="tinymce-js" />
				</xsl:if>
			</head>
			<body>
				<div id="main">
					<div id="quickpanel">
						<a id="exit" href="/admin/users/logout/" title="Выход">
							<xsl:text>&#160;</xsl:text>
						</a>
						<a id="help" target="_blank" href="http://help.umi-cms.ru/" title="Документация">
							<xsl:text>&#160;</xsl:text>
						</a>

						<xsl:apply-templates select="$site-langs" />

						<div id="butterfly">
							<span />
							<xsl:text>&modules;</xsl:text>
							<div>
								<div class="bg">
									<xsl:apply-templates select="$modules-menu" />
									<div class="clear" />
								</div>
								<div class="bottom_bg" />
							</div>
						</div>

						<xsl:call-template name="panel-buttons" />

						<xsl:apply-templates select="document('udata://autoupdate/getDaysLeft/')/udata/trial" mode="trial-days-left" />

					</div>
					<div id="dock">
						<div></div>
						<img />
					</div>
					<div id="head">
						<img src="/images/cms/admin/mac/icons/medium/{$module}.png" />
						<xsl:apply-templates select="$navibar" />

						<xsl:if test="$modules-menu/items/item[@name = $module and @config = 'config'] and $method != 'trash' and $method != 'config'">
							<a id="settings" href="{$lang-prefix}/admin/{$module}/config/">
								<xsl:text>&config;</xsl:text>
							</a>
						</xsl:if>

						<div class="help">&label-quick-help;</div>
						<div class="clear" />
					</div>

					<xsl:apply-templates select="$errors" />

					<div id="page">
						<div id="info_block" class="panel" style="display: none;">
							<div class="header">
								<xsl:text>&label-quick-help;</xsl:text>
								<div class="l" /><div class="r" />
							</div>
							<div class="content" title="{$context-manul-url}">
							</div>
						</div>
						<div id="content" class="content-expanded">
							<div style="float:left; width:100%; _float:none;">
								<xsl:apply-templates select="result" mode="tabs" />
							</div>
						</div>
						<div class="clear" />
					</div>
				</div>
				<div id="foot">
					<a target="_blank" href="http://www.umi-cms.ru/support" id="support">
						<xsl:text>&support; </xsl:text>
						<span>
							<xsl:text>&umi-cms-site;</xsl:text>
						</span>
					</a>
					<a href="http://www.umi-cms.ru" id="copy">
						<xsl:text>© &copyright; &cms-name;</xsl:text>
					</a>
				</div>

			</body>
		</html>
	</xsl:template>
</xsl:stylesheet>