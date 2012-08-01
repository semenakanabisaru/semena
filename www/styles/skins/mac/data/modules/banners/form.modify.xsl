<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common"[
	<!ENTITY sys-module 'banners'>	
]>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:umi="http://www.umi-cms.ru/TR/umi">
	
	<xsl:template match="group[@name='common']" mode="form-modify">
		<xsl:param name="show-name"><xsl:text>1</xsl:text></xsl:param>
		<xsl:param name="show-type"><xsl:text>1</xsl:text></xsl:param>
	
		<div class="panel properties-group" name="g_{@name}">
			<div class="header">
				<span>
					<xsl:value-of select="@title" />
				</span>				
				<div class="l" /><div class="r" />
			</div>
			
			<div class="content">
								
				<xsl:apply-templates select="." mode="form-modify-group-fields">
					<xsl:with-param name="show-name" select="$show-name" />
					<xsl:with-param name="show-type" select="$show-type" />
				</xsl:apply-templates>
				
				<xsl:call-template name="calculate-ctr" />				

				<xsl:choose>
					<xsl:when test="$data-action = 'create'">
						<xsl:call-template name="std-form-buttons-add" />
					</xsl:when>
					<xsl:otherwise>
						<xsl:call-template name="std-form-buttons" />
					</xsl:otherwise>
				</xsl:choose>
			</div>
		</div>
	</xsl:template>
	
	<xsl:template name="calculate-ctr">
		<xsl:variable name="group" select="/result/data/object/properties/group[@name = 'view_params']" />
		<xsl:variable name="views-count" select="$group/field[@name = 'views_count']" />
		<xsl:variable name="clicks-count" select="$group/field[@name = 'clicks_count']" />
		<div class="field">
			<label for="{generate-id()}">
				<span class="label">
					<acronym>
						<xsl:attribute name="title"><xsl:text>&ctr-description;</xsl:text></xsl:attribute>
						<xsl:attribute name="class"><xsl:text>acr</xsl:text></xsl:attribute>
						<xsl:text>CTR</xsl:text>
					</acronym>					
				</span>
				<span>
					<xsl:value-of select="format-number(translate(number($clicks-count) div number($views-count), 'Na', '00'), '0.####%')" />					
				</span>
			</label>
		</div>		
	</xsl:template>
	
</xsl:stylesheet>