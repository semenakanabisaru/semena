<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="option" mode="settings.modify">
		<tr>
			<td>
				<label for="{@name}">
					<xsl:value-of select="@label" />
				</label>
			</td>
			<td class="center">
				<xsl:apply-templates select="." mode="settings.modify-option" />
			</td>
		</tr>
	</xsl:template>

</xsl:stylesheet>