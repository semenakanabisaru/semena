<?xml version="1.0" encoding="UTF-8"?>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="field">
		<div>
			<label>
				<xsl:apply-templates select="." mode="webforms_required" />
				<span><xsl:value-of select="@title" /><xsl:text>:</xsl:text></span>
				<xsl:apply-templates select="." mode="webforms_input_type" />
			</label>
		</div>
	</xsl:template>

	<xsl:template match="field" mode="webforms_input_type">
		<input type="text" name="{@input_name}" />
	</xsl:template>

	<xsl:template match="field[@type = 'text']" mode="webforms_input_type">
		<textarea name="{@input_name}"></textarea>
	</xsl:template>

	<xsl:template match="field[@type = 'relation']" mode="webforms_input_type">
		<select name="{@input_name}">
			<xsl:apply-templates select="." mode="webforms_multiple" />
			<option value=""></option>
			<xsl:apply-templates select="values/item" mode="webforms_input_type" />
		</select>
	</xsl:template>

	<xsl:template match="item" mode="webforms_input_type">
		<option value="{@id}"><xsl:apply-templates /></option>
	</xsl:template>

	<xsl:template match="field" mode="webforms_required" />
	<xsl:template match="field[@required]" mode="webforms_required">
		<xsl:attribute name="class"><xsl:text>required</xsl:text></xsl:attribute>
	</xsl:template>

	<xsl:template match="field" mode="webforms_multiple" />
	<xsl:template match="field[@multiple]" mode="webforms_multiple">
		<xsl:attribute name="multiple"><xsl:text>multiple</xsl:text></xsl:attribute>
	</xsl:template>

</xsl:stylesheet>