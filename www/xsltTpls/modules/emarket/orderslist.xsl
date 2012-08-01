<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM	"ulang://i18n/constants.dtd:file">

<xsl:stylesheet	version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:template match="/result[@method = 'ordersList']">
		<xsl:apply-templates select="document('udata://emarket/ordersList')/udata" />
	</xsl:template>
	
	
	<xsl:template match="udata[@method = 'ordersList']">
		<div id="con_tab_orders">
			<xsl:if test="$method = 'personal'">
				<xsl:attribute name="style">display: none;</xsl:attribute>
			</xsl:if>
			
			<table class="blue">
				<thead>
					<tr>
						<th class="name align_l">
							<xsl:text>&order-number;</xsl:text>
						</th>
						
						<th class="name align_l">
							<xsl:text>&order-status;</xsl:text>
						</th>
						
						<th class="align_l">
							<xsl:text>&order-sum;</xsl:text>
						</th>
					</tr>
				</thead>
				<tbody>
					<xsl:apply-templates select="items/item" mode="order" />
				</tbody>
			</table>
		</div>
	</xsl:template>
	
	<xsl:template match="item" mode="order">
		<xsl:apply-templates select="document(concat('udata://emarket/order/', @id))/udata" />
		<tr>
			<td colspan="3" class="separate" ></td>
		</tr>
	</xsl:template>

	<xsl:template match="item[position() = last()]" mode="order">
		<xsl:apply-templates select="document(concat('udata://emarket/order/', @id))/udata" />
	</xsl:template>


	<xsl:template match="udata[@module = 'emarket'][@method = 'order']">
		<tr>
			<td class="name align_l">
				<strong>
					<xsl:text>&number; </xsl:text>
					<xsl:value-of select="number" />
				</strong>
				<div>
					<xsl:text>&date-from; </xsl:text>
					<xsl:apply-templates select="document(concat('uobject://', @id, '.order_date'))//property" />
				</div>
			</td>
			<td class="name align_l">
				<xsl:value-of select="status/@name" />
				<div>
					<xsl:text>&date-from-1; </xsl:text>
					<xsl:apply-templates select="document(concat('uobject://', @id, '.status_change_date'))//property" />
				</div>
			</td>
			<td class="align_l">
				<xsl:apply-templates select="summary/price" />
			</td>
		</tr>
		
		<xsl:apply-templates select="items/item" />
	</xsl:template>
	
	<xsl:template match="udata[@method = 'order']/items/item">
		<tr>
			<td colspan="2" class="name align_l">
				<a href="{page/@link}">
					<xsl:value-of select="@name" />
				</a>
			</td>

			<td  class="align_l">
				<xsl:apply-templates select="price" />
				<xsl:text> x </xsl:text>
				<xsl:apply-templates select="amount" />
				<xsl:text> = </xsl:text>
				<xsl:apply-templates select="total-price" />
			</td>
		</tr>
	</xsl:template>
</xsl:stylesheet>