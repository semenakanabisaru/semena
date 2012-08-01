<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common/">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="option[@name='message']" mode="settings.view">
		<tr>
			<td class="eq-col">
				<label for="{@name}">
					<xsl:value-of select="@label" />
				</label>
			</td>
			<td>
				<xsl:value-of select="value" disable-output-escaping="yes" />
			</td>
		</tr>
	</xsl:template>	

</xsl:stylesheet>