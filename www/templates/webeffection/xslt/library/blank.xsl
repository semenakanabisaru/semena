<?xml version="1.0" encoding="utf-8"?>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<!-- Undefined result -->
	<xsl:template match="result">
		<p>
			<xsl:text>Undefined result for </xsl:text>
			<xsl:value-of select="concat(@module, '::', @method)" />
			<xsl:text>() method.</xsl:text>
		</p>
		<textarea style="width: 900px; height: 400px;">
			<xsl:copy-of select="document(concat('udata://', @module, '/', @method))/udata" disable-output-escaping="no" />
		</textarea>
	</xsl:template>

	<xsl:template match="property|page|content|object">
		<xsl:text>Undefined result</xsl:text>
	</xsl:template>

	<xsl:template match="*" mode="code_view">
		<div>
			<textarea style="width: 900px; height: 400px;">
				<xsl:copy-of select="." disable-output-escaping="no" />
			</textarea>
		</div>
	</xsl:template>

</xsl:stylesheet>