<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM	"ulang://i18n/constants.dtd:file">

<xsl:stylesheet	version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="result[@method = 'cart']">
		<xsl:apply-templates select="document('udata://emarket/cart')/udata" />
		<xsl:if test="$userType != 'guest'">
			<p style="margin-top:10px;">
				<a href="{$langPrefix}/emarket/ordersList/" >
					<xsl:text>&view-orders-list;</xsl:text>
				</a>
			</p>
		</xsl:if>
	</xsl:template>

	<xsl:template match="udata[@method = 'cart']">
		<div class="basket">
			<xsl:text>&basket-empty;</xsl:text>
		</div>
	</xsl:template>


	<xsl:template match="udata[@method = 'cart'][count(items/item) &gt; 0]">
		<div class="basket">
			<table class="blue">
				<thead>
					<tr>
						<th class="name">
							<xsl:text>&basket-item;</xsl:text>
						</th>
						<th>
							<xsl:text>&price;</xsl:text>
						</th>
						<th>
							<xsl:text>&amount;</xsl:text>
						</th>
						<th>
							<xsl:text>&sum;</xsl:text>
						</th>
						<th>
							<xsl:text>&delete;</xsl:text>
						</th>
					</tr>
				</thead>
				<tbody>
					<xsl:apply-templates select="items/item" />
				</tbody>
				<xsl:apply-templates select="summary" />
			</table>
			<div>
				<a href="{$langPrefix}/emarket/basket/remove_all/" class="button big basket_remove_all">
					<xsl:text>&clear-basket;</xsl:text>
				</a>
				<a href="{$langPrefix}/emarket/purchase/" class="button big">
					<xsl:text>&begin-purchase;</xsl:text>
				</a>
			</div>
			<div class="clear"></div>
		</div>
	</xsl:template>
	
	<xsl:template match="udata[@method = 'cart']//item">
		<tr class="cart_item_{@id}">
			<td class="name">
				<a href="{page/@link}">
					<xsl:value-of select="@name" />
				</a>
			</td>

			<td>
				<xsl:apply-templates select="price" />
			</td>

			<td>
				<input type="text" value="{amount}" onkeyup="
					var e = jQuery(this).next('input'), old = e.val();
					e.val(this.value);
					site.basket.modify({@id}, this.value, old);
				" />
				<input type="hidden" value="{amount}" />
			</td>

			<td>
				<span class="cart_item_price_{@id}">
					<xsl:apply-templates select="total-price" />
				</span>
			</td>
			<td>
				<a href="{$langPrefix}/emarket/basket/remove/item/{@id}/" id="{@id}" class="del" />
			</td>
		</tr>
	</xsl:template>
	
	
	
	<xsl:template match="udata[@method = 'cart']/summary">
		<tfoot>
			<tr>
				<td colspan="6" align="right">
					<xsl:text>&summary-price;: </xsl:text>
					<span class="cart_summary">
						<xsl:apply-templates select="price" />
					</span>
				</td>
			</tr>
		</tfoot>
	</xsl:template>
</xsl:stylesheet>