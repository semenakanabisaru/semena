<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	


	<!--  -->
	<xsl:template match="/result[@module = 'social_networks']/data/object" mode="form-modify">
		<xsl:apply-templates select="properties/group" mode="form-modify">
			<xsl:with-param name="show-name"><xsl:text>0</xsl:text></xsl:with-param>
		</xsl:apply-templates>
	</xsl:template>
	
	<xsl:template match="field[@type = 'symlink' and @name='iframe_pages']" mode="form-modify">
		<div class="field symlink" id="{generate-id()}" name="{@input_name}">
			<label for="symlinkInput{generate-id()}" class='content-page blogs20-post blogs20-blog faq-category faq-project news-rubric news-item'>
				<span class="label">
					<acronym>
						<xsl:apply-templates select="." mode="sys-tips" />
						<xsl:value-of select="@title" />
					</acronym>
					<xsl:apply-templates select="." mode="required_text" />
				</span>
			
				<span id="symlinkInput{generate-id()}" rel="1">
					<ul>
						<xsl:apply-templates select="values/item" mode="symlink" />
					</ul>
				</span>
			</label>
		</div>
	</xsl:template>
</xsl:stylesheet>