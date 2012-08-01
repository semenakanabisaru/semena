<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://i18n/constants.dtd:file">

<xsl:stylesheet	version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:umi="http://www.umi-cms.ru/TR/umi">

	<xsl:template match="result[@module = 'catalog'][@method = 'object']">
		<xsl:variable name="cart_items" select="document('udata://emarket/cart/')/udata/items" />
		<span class="price"><xsl:value-of select=".//property[@name = 'price']/title" /></span>
		<xsl:text> : </xsl:text>
		<span class="price"><xsl:value-of select=".//property[@name = 'price']/value" /></span>
		<form class="options" action="{$langPrefix}/emarket/basket/put/element/{page/@id}/" id="form_basket">
			<xsl:apply-templates select=".//group[@name = 'catalog_option_props']" mode="table_options" />
			<input type="submit" class="button big" id="add_basket_{$pageId}">
				<xsl:attribute name="value">
					<xsl:text>&basket-add;</xsl:text>
					<xsl:if test="$cart_items/item[page/@id = $pageId]">
						<xsl:text> (</xsl:text>
						<xsl:value-of select="sum($cart_items/item[page/@id = $pageId]/amount)" />
						<xsl:text>)</xsl:text>
					</xsl:if>
				</xsl:attribute>
			</input>
		</form>
	</xsl:template>

</xsl:stylesheet>