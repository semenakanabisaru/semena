<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet	version="1.0"
					xmlns="http://www.w3.org/1999/xhtml"
					xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
					xmlns:date="http://exslt.org/dates-and-times"
					xmlns:udt="http://umi-cms.ru/2007/UData/templates"
					xmlns:xlink="http://www.w3.org/TR/xlink"
					exclude-result-prefixes="xsl date udt xlink">

	<xsl:template match="result[@module = 'filemanager'][@method = 'list_files']">
		
	</xsl:template>

	<xsl:template match="udata[@module = 'filemanager'][@method = 'list_files']">
		<xsl:apply-templates select="items" mode="list_files" />
	</xsl:template>

	<xsl:template match="items" mode="list_files">
		<xsl:variable name="fs_file" select="document(concat('upage://',@id,'.fs_file'))/udata/property/value" />
		<xsl:variable name="file" select="document(concat('ufs://',$fs_file))/udata/file" />
		<div class="blue-block">
			<div class="c">
				<div class="c">
					<div class="c">
						<div class="content">
							<xsl:if test="document(concat(@xlink:href,'.version'))/udata/property/value != ''">
								<xsl:attribute name="style">padding-left:65px;</xsl:attribute>
								<div class="date">
									<xsl:if test="$fs_file != ''">
										<xsl:value-of select="document(concat('udata://system/convertdate/',$file/@modify-time,'/d.m.y/'))/udata" />
									</xsl:if>
									<xsl:if test="$fs_file = ''">
										<xsl:apply-templates select="document(concat(@xlink:href,'.content'))//value" mode="xvalue" />
									</xsl:if>
								</div>
							</xsl:if>
							<h3 xmlns:umi="umi" umi:element-id="{@id}" umi:field-name="name"><xsl:value-of select="@name" /></h3>
							<xsl:if test="document(concat(@xlink:href,'.version'))/udata/property/value != ''">
								<span class="orange" xmlns:umi="umi" umi:element-id="{@id}" umi:field-name="version">(текущая версия - <xsl:value-of select="document(concat(@xlink:href,'.version'))/udata/property/value" />)</span><br />
							</xsl:if>
							<xsl:choose>
								<xsl:when test="$fs_file = ''">
									<a class="o_button" href="http://demo.umi-cms.ru/" onclick="pageTracker._setVar('Переход в онлайн-демо');" target="_blank"><span class="l" /><span class="c">Перейти</span><span class="r" /></a>
								</xsl:when>
								<xsl:otherwise>
									<a class="o_button" href="{@link}"><span class="l" /><span class="c">Скачать</span><span class="r" /></a>
									<a href="{@link}" style="padding-left:10px; font-weight:bold; color:#0153c2;"><xsl:value-of select="@name" /></a> 
									(.<xsl:value-of select="$file/@ext" />, 
									<xsl:choose>
										<xsl:when test="$file/@size &gt; '1073741824'">
											<xsl:value-of select="format-number($file/@size div '1073741824', '#.##')" /> Gб)
										</xsl:when>
										<xsl:when test="$file/@size &gt; '1048576'">
											<xsl:value-of select="format-number($file/@size div '1048576', '#.##')" /> Мб)
										</xsl:when>
										<xsl:when test="$file/@size &gt; '1024'">
											<xsl:value-of select="format-number($file/@size div '1024', '#.##')" /> Kб)
										</xsl:when>
										<xsl:otherwise>
											<xsl:value-of select="format-number($file/@size, '#.##')" /> б)
										</xsl:otherwise>
									</xsl:choose>
								</xsl:otherwise>
							</xsl:choose>
							<div xmlns:umi="umi" umi:element-id="{@id}" umi:field-name="additional_description"><xsl:apply-templates select="document(concat(@xlink:href,'.additional_description'))/udata/property/value" mode="xvalue" /></div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</xsl:template>

</xsl:stylesheet>