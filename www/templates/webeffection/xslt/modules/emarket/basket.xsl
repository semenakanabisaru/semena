<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM	"ulang://i18n/constants.dtd:file">

<xsl:stylesheet	version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="udata[@module = 'emarket'][@method = 'cart']" mode="basket" />
	<xsl:template match="udata[@module = 'emarket' and @method = 'cart'][summary]" mode="basket">
		<div class="basket_info">
			<div>
				<span class="emarket_basket_top"><p><a href="{$langPrefix}/emarket/cart/">&basket;</a></p>
					<span class="basket_info_summary">
						<xsl:apply-templates select="summary" mode="basket" />
					</span>
				</span>
			</div>
		</div>
	</xsl:template>
	
	<xsl:template match="summary" mode="basket">
		<xsl:text>&basket-empty;</xsl:text>
	</xsl:template>
	
	<xsl:template match="summary[amount &gt; 0]" mode="basket">
		<span>
			<xsl:apply-templates select="amount" />
		</span>
		<xsl:text> &basket-items-text;</xsl:text>
		<xsl:apply-templates select="price" />
	</xsl:template>
</xsl:stylesheet>