<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://i18n/constants.dtd:file">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:umi="http://www.umi-cms.ru/TR/umi">

	<xsl:template name="makeThumbnail">
		<xsl:param name="alt" />
		<xsl:param name="class" />
		<xsl:param name="width" select="'auto'" />
		<xsl:param name="height" select="'auto'" />
		<xsl:param name="element_id" />
		<xsl:param name="object_id" />
		<xsl:param name="field_name" />
		<xsl:variable name="path">
			<xsl:if test="$element_id">
				<xsl:value-of select="document(concat('upage://',$element_id,'.',$field_name))/udata/property/value/@path" />
			</xsl:if>
			<xsl:if test="$object_id">
				<xsl:value-of select="document(concat('uobject://',$object_id,'.',$field_name))/udata/property/value/@path" />
			</xsl:if>
		</xsl:variable>
		<xsl:apply-templates select="document(concat('udata://system/makeThumbnailFull/(',$path,')/',$width,'/',$height,'/void/0/1/'))/udata">
			<xsl:with-param name="element_id" select="$element_id" />
			<xsl:with-param name="object_id" select="$object_id" />
			<xsl:with-param name="field_name" select="$field_name" />
			<xsl:with-param name="alt" select="$alt" />
			<xsl:with-param name="class" select="$class" />
		</xsl:apply-templates>
	</xsl:template>

	<xsl:template match="udata[@module = 'system' and (@method = 'makeThumbnail' or @method = 'makeThumbnailFull')]">
		<xsl:param name="alt" select="''" />
		<xsl:param name="class" />
		<xsl:param name="element_id" />
		<xsl:param name="object_id" />
		<xsl:param name="field_name" />
		<img alt="{$alt}" title="{$alt}">
			<xsl:apply-templates select="width" />
			<xsl:apply-templates select="height" />
			<xsl:if test="$class"><xsl:attribute name="class"><xsl:value-of select="$class"/></xsl:attribute></xsl:if>
			<xsl:if test="$field_name">
				<xsl:if test="$element_id"><xsl:attribute name="umi:element-id"><xsl:value-of select="$element_id" /></xsl:attribute></xsl:if>
				<xsl:if test="$object_id"><xsl:attribute name="umi:object-id"><xsl:value-of select="$object_id" /></xsl:attribute></xsl:if>
				<xsl:attribute name="umi:field-name"><xsl:value-of select="$field_name" /></xsl:attribute>
			</xsl:if>
			<xsl:attribute name="src">
				<xsl:choose>
					<xsl:when test="src"><xsl:value-of select="src" /></xsl:when>
					<xsl:otherwise>&empty-photo;</xsl:otherwise>
				</xsl:choose>
			</xsl:attribute>
		</img>
	</xsl:template>

	<xsl:template match="udata[@module = 'system' and (@method = 'makeThumbnail' or @method = 'makeThumbnailFull')]/width">
		<xsl:attribute name="width"><xsl:value-of select="." /></xsl:attribute>
	</xsl:template>

	<xsl:template match="udata[@module = 'system' and (@method = 'makeThumbnail' or @method = 'makeThumbnailFull')]/height">
		<xsl:attribute name="height"><xsl:value-of select="." /></xsl:attribute>
	</xsl:template>

</xsl:stylesheet>