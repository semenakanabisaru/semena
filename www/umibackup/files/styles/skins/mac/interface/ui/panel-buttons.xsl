<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM	"ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:variable name="edition" select="/result/@edition" />
	<xsl:variable name="result-user-id" select="/result/@user-id" />

	<xsl:template name="panel-buttons">
		<xsl:param name="user-group" select="document(concat('uobject://',$result-user-id))//property[@name = 'groups']/value" />
		<xsl:call-template name="profile" />

		<a id="site_link" target="_blank" href="{$lang-prefix}/">
			<xsl:text>&site-link;</xsl:text>
		</a>

<!--
		<xsl:if test="$edition = 'demo' or $edition = 'free' or $edition = 'trial' or not(string-length($edition))">
			<a id="buy" href="http://www.umi-cms.ru/market/">
				<xsl:text>&buy-umi-cms;</xsl:text>
			</a>
		</xsl:if>
-->
		<a id="cache" href="/admin/config/cache/">
			<xsl:choose>
				<xsl:when test="$cache-enabled = 0">
					<xsl:text>&cache-disabled-message;</xsl:text>
				</xsl:when>
				<xsl:otherwise>
					<xsl:text>&cache-enabled-message;</xsl:text>
				</xsl:otherwise>
			</xsl:choose>
		</a>
		<!--xsl:if test= "$user-group/item[@guid = 'users-users-15']">
			<a href="" id="new_updates">
				&new-updates;
			</a>
		</xsl:if-->
	</xsl:template>

	<xsl:template match="trial" mode="trial-days-left">
		<a id="buy" href="http://www.umi-cms.ru/market/" target="_blank">
			<xsl:apply-templates select="@daysleft" mode="prefix"/><xsl:text> </xsl:text>
			<xsl:value-of select="@daysleft" /><xsl:text> </xsl:text>
			<xsl:apply-templates select="@daysleft" mode="suffix"/>
			<xsl:text>. &buy-umi-cms;</xsl:text>
		</a>
	</xsl:template>

	<xsl:template match="@daysleft" mode="suffix">&days-left-number1;</xsl:template>
	<xsl:template match="@daysleft[not(. &gt; 10 and . &lt; 20) and ((. mod 10) = 2 or (. mod 10) = 3 or (. mod 10) = 4)]" mode="suffix">&days-left-number2;</xsl:template>
	<xsl:template match="@daysleft[not(. &gt; 10 and . &lt; 20) and ((. mod 10) = 1)]" mode="suffix">&days-left-number3;</xsl:template>

	<xsl:template match="@daysleft" mode="prefix">&days-left1;</xsl:template>
	<xsl:template match="@daysleft[((. mod 10) = 1)]" mode="prefix">&days-left2;</xsl:template>
	
</xsl:stylesheet>