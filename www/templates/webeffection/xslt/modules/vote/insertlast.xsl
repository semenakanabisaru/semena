<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet	version="1.0"
					xmlns="http://www.w3.org/1999/xhtml"
					xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
					xmlns:date="http://exslt.org/dates-and-times"
					xmlns:udt="http://umi-cms.ru/2007/UData/templates"
					xmlns:xlink="http://www.w3.org/TR/xlink"
					exclude-result-prefixes="xsl date udt xlink">

	<xsl:template match="udata[@module = 'vote'][@method = 'insertlast']">
		<div class="infoblock">
			<div class="title"><h2><xsl:text>Опрос</xsl:text></h2></div>
			<div class="body">
				<div class="in">
					<div class="vote_text">
						<xsl:value-of select="text" />
					</div>
					<xsl:choose>
						<xsl:when test="items/item/@id">
							<form id="postForm_{id}" onsubmit="site.forms.vote(this, {id}); return false;">
								<ul class="vote"><xsl:apply-templates select="items/item" mode="vote" /></ul>
								<input class="button" type="submit" value="Ответить" />
							</form>
						</xsl:when>
						<xsl:when test="items/item/@score">
							<table class="vote">
								<xsl:apply-templates select="items/item" mode="results" />
								<tr>
									<td><xsl:text>Всего голосов: </xsl:text></td>
									<td class="right"><strong><xsl:value-of select="total_posts" /></strong></td>
									<td class="right"></td>
								</tr>
							</table>
						</xsl:when>
						<xsl:otherwise>
							<ul><li><xsl:text>Результаты голосования отсутствуют</xsl:text></li></ul>
						</xsl:otherwise>
					</xsl:choose>
				</div>
			</div>
		</div>
	</xsl:template>

</xsl:stylesheet>