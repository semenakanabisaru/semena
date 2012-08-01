<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="/result[@method = 'mail_config']/data[@type = 'settings' and @action = 'modify']">
		<form method="post" action="do/" enctype="multipart/form-data">
			<xsl:apply-templates select="." mode="settings-modify" />
			<xsl:call-template name="std-save-button" />
		</form>
		<xsl:apply-templates select="/result/@demo" mode="stopdoItInDemo" />
	</xsl:template>
	
	<xsl:template match="/result[@method = 'mail_config']//group" mode="settings-modify">
		<div class="panel properties-group">
			<div class="header">
				<span>
					<xsl:value-of select="@label" />
				</span>
				<div class="l" /><div class="r" />
			</div>
			<div class="content">
				<table class="tableContent">
					<tbody>
						<xsl:apply-templates select="option" mode="settings.modify" />
					</tbody>
				</table>
				
				<xsl:call-template name="std-save-button" />
			</div>
		</div>
	</xsl:template>

	<xsl:template match="/result[@method = 'mail_config']//group[@name != 'status-notifications']" mode="settings-modify">
		<xsl:variable name="from-email" select="option[position() = 2]" />
		<xsl:variable name="from-name" select="option[position() = 3]" />
		<xsl:variable name="manager-email" select="option[position() = 4]" />

		<table class="tableContent">
			<thead>
				<tr>
					<th colspan="2" class="eq-col">
						<xsl:value-of select="option[@name = 'domain']/value" />
					</th>
				</tr>
			</thead>

			<tbody>
				<tr>
					<td>
						<label for="{$from-email/@name}">
							<xsl:text>&option-email;</xsl:text>
						</label>
					</td>

					<td>
						<input type="text" name="{$from-email/@name}" value="{$from-email/value}" id="{$from-email/@name}" />
					</td>
				</tr>

				<tr>
					<td class="eq-col">
						<label for="{$from-name/@name}">
							<xsl:text>&option-name;</xsl:text>
						</label>
					</td>

					<td>
						<input type="text" name="{$from-name/@name}" value="{$from-name/value}" id="{$from-name/@name}" />
					</td>
				</tr>

				<tr>
					<td class="eq-col">
						<label for="{$manager-email/@name}">
							<xsl:text>&option-manageremail;</xsl:text>
						</label>
					</td>

					<td>
						<input type="text" name="{$manager-email/@name}" value="{$manager-email/value}" id="{$manager-email/@name}" />
					</td>
				</tr>
			</tbody>
		</table>
	</xsl:template>

</xsl:stylesheet>