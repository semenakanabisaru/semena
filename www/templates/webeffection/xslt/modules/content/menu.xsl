<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://i18n/constants.dtd:file">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:umi="http://www.umi-cms.ru/TR/umi">

	<xsl:template match="udata[@module = 'content'][@method = 'menu']">
		<xsl:apply-templates select="items" />
	</xsl:template>

	<xsl:template match="udata[@module = 'content' and @method = 'menu']//items">
		<ul umi:add-method="popup"
			umi:sortable="sortable"
			umi:method="menu"
			umi:module="content"
			umi:element-id="{../@id}">
			<xsl:apply-templates select="item" />
		</ul>
	</xsl:template>

	<xsl:template match="udata[@module = 'content' and @method = 'menu']//item">
		<li>
			<a href="{@link}" umi:element-id="{@id}" umi:region="row" umi:field-name="name" umi:empty="&empty-section-name;" umi:delete="delete">
				<xsl:apply-templates select="@status" />
				<xsl:value-of select="@name" />
			</a>
			<xsl:apply-templates select="items" />
		</li>
	</xsl:template>

	<xsl:template match="@status">
		<xsl:attribute name="class">active</xsl:attribute>
	</xsl:template>

</xsl:stylesheet>