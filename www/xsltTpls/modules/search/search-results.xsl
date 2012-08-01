<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM	"ulang://i18n/constants.dtd:file">

<xsl:stylesheet	version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:umi="http://www.umi-cms.ru/TR/umi">
	<xsl:template match="/result[@method = 'search_do']">
		<xsl:apply-templates select="document('udata://search/search_do')" />
	</xsl:template>
	
	<xsl:template match="udata[@method = 'search_do']">
		<form class="search" action="/search/search_do/" method="get">
			<input type="text" value="{$search_string}" name="search_string" class="textinputs" />
			<input type="submit" class="button" value="Найти" />
		</form>

		<p>
			<strong>
				<xsl:text>&search-founded-left; "</xsl:text>
				<xsl:value-of select="$search_string" />
				<xsl:text>" &search-founded-nothing;.</xsl:text>
			</strong>
		</p>
	</xsl:template>
	
	<xsl:template match="udata[@method = 'search_do' and count(items/item)]">
		<form class="search" action="/search/search_do/" method="get">
			<input type="text" value="{$search_string}" name="search_string" class="textinputs" />
			<input type="submit" class="button" value="Найти" />
		</form>

		<p>
			<strong>
				<xsl:text>&search-founded-left; "</xsl:text>
				<xsl:value-of select="$search_string" />
				<xsl:text>" &search-founded-right;: </xsl:text>
				<xsl:value-of select="total" />
				<xsl:text>.</xsl:text>
			</strong>
		</p>

		<dl class="search">
			<xsl:apply-templates select="items/item" mode="search-result" />
		</dl>
		<xsl:apply-templates select="total" />
	</xsl:template>
	
	<xsl:template match="item" mode="search-result">
		<dt>
			<span>
				<xsl:value-of select="$p + position()" />
			</span>
			<a href="{@link}" umi:element-id="{@id}" umi:field-name="name">
				<xsl:value-of select="@name" />
			</a>
		</dt>
		<dd>
			<xsl:value-of select="." disable-output-escaping="yes" />
		</dd>
	</xsl:template>
</xsl:stylesheet>