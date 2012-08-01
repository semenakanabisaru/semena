<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet	version="1.0"
					xmlns="http://www.w3.org/1999/xhtml"
					xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
					xmlns:date="http://exslt.org/dates-and-times"
					xmlns:udt="http://umi-cms.ru/2007/UData/templates"
					xmlns:xlink="http://www.w3.org/TR/xlink"
					exclude-result-prefixes="xsl date udt xlink">

	<xsl:template match="result[@module = 'vote'][@method = 'poll']">
		<div id="vote">
			<xsl:apply-templates select="document(concat('udata://vote/poll/',$pageId))/udata" />
		</div>
	</xsl:template>

	<xsl:template match="udata[@module = 'vote'][@method = 'poll']">
		<div class="vote_text">
			<xsl:value-of select="text" />
		</div>
		<xsl:choose>
			<xsl:when test="items/item/@id">
				<form id="postFormPage_{id}" name="postFormPage_{id}" onsubmit="cms_vote_postDo('postFormPage_{id}', 'vote_results'); return false;">
					<ul><xsl:apply-templates select="items/item" mode="vote" /></ul>
					<input class="button" type="submit" value="Ответить" />
				</form>
			</xsl:when>
			<xsl:when test="items/item/@score">
				<table>
					<xsl:apply-templates select="items/item" mode="results" />
					<tr>
						<td><xsl:text>Всего голосов: </xsl:text></td>
						<td class="last"><strong><xsl:value-of select="total_posts" /></strong></td>
					</tr>
				</table>
			</xsl:when>
		</xsl:choose>
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