<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:template match="/result[@method = 'modules']/data">
		<xsl:apply-templates select="/result/@demo" mode="stopdoItInDemo" />
		<form  action="{$lang-prefix}/admin/config/add_module_do/" enctype="multipart/form-data" method="post">
			<div class="field modules">
				<label>
					<span class="label"><xsl:text>&label-install-path;</xsl:text></span>
					<input value="classes/" name="module_path" />
				</label>
				<div class="buttons">
					<div>
						<input type="submit" class="{/result/@module}_{/result/@method}_btn"  value="&label-install;" />
						<span class="l" /><span class="r" />
					</div>
				</div>
			</div>
		</form>
	
		<table class="tableContent">
			<thead>
				<th>
					<xsl:text>&label-modules-list;</xsl:text>
				</th>
				
				<th>
					<xsl:text>&label-delete;</xsl:text>
				</th>
			</thead>
			
			<tbody>
				<xsl:apply-templates select="module" mode="list-view" />
			</tbody>
		</table>
	</xsl:template>
	
	
	<xsl:template match="module" mode="list-view">
		<tr>
			<td>
				<a href="{$lang-prefix}/admin/{.}/">
					<xsl:value-of select="@label" />
				</a>
			</td>
			
			<td>
				<a href="{$lang-prefix}/admin/config/del_module/{.}/" class="delete unrestorable {/result/@module}_{/result/@method}_btn">
					<span><xsl:text>&label-delete;</xsl:text></span>
				</a>
			</td>
		</tr>
	</xsl:template>
</xsl:stylesheet>