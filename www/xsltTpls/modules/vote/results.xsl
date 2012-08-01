<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet	version="1.0"
					xmlns="http://www.w3.org/1999/xhtml"
					xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
					xmlns:date="http://exslt.org/dates-and-times"
					xmlns:udt="http://umi-cms.ru/2007/UData/templates"
					xmlns:xlink="http://www.w3.org/TR/xlink"
					exclude-result-prefixes="xsl date udt xlink">

	<xsl:template match="udata[@module = 'vote'][@method = 'results']">
		<div class="vote_text">
			<xsl:value-of select="text" />
		</div>
		<table class="vote">
			<xsl:apply-templates select="items/item" mode="results" />
			<tr>
				<td><xsl:text>Всего голосов: </xsl:text></td>
				<td class="right"><strong><xsl:value-of select="total_posts" /></strong></td>
				<td class="right"></td>
			</tr>
		</table>
	</xsl:template>

	<xsl:template match="item" mode="vote">
		<li>
			<label for="item_{position()}">
				<input type="radio" id="item_{position()}" name="vote_results" value="{@id}" />
				<xsl:value-of select="." />
			</label>
		</li>
	</xsl:template>

	<xsl:template match="item" mode="results">
		<tr>
			<td><xsl:value-of select="."/>:</td>
			<td class="right"><xsl:apply-templates select="@score" mode="score" /></td>
			<td class="right"><xsl:apply-templates select="@score-rel" mode="score-rel" /></td>
		</tr>
	</xsl:template>

	<xsl:template match="@score-rel" mode="score-rel">
		<span><xsl:value-of select="." />%</span>
	</xsl:template>

	<xsl:template match="@score" mode="score">
		<span><xsl:value-of select="." /></span>
	</xsl:template>

</xsl:stylesheet>