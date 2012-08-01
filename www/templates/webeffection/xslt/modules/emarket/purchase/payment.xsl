<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM	"ulang://i18n/constants.dtd:file">

<xsl:stylesheet	version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:template match="purchasing[@stage = 'payment'][@step = 'choose']">
		<h4>
			<xsl:text>&payment-type;:</xsl:text>
		</h4>
		
		<form id="payment_choose" method="post" action="do/">
			<script>
				window.paymentId = null;
				jQuery('#payment_choose').submit(function(){
					if(window.paymentId) return checkPaymentReceipt(window.paymentId);
					else return true;
				});
			</script>
		
			<xsl:apply-templates select="items/item" mode="payment" />
			
			<div>
				<input type="submit" value="&continue;" class="button big" />
			</div>
		</form>
	</xsl:template>
	
	<xsl:template match="item" mode="payment">
		<div>
			<label>
				<xsl:if test="(position() = 1) and (@type-name = 'receipt')">
					<script>
						window.paymentId = <xsl:value-of select="@id" />;
					</script>
				</xsl:if>
				<input type="radio" name="payment-id" class="{@type-name}" value="{@id}">
					<xsl:attribute name="onclick">
						<xsl:text>this.form.action = </xsl:text>
						<xsl:choose>
							<xsl:when test="@type-name != 'receipt'"><xsl:text>'do/';</xsl:text></xsl:when>
							<xsl:otherwise><xsl:text>'/emarket/ordersList/'; window.paymentId = '</xsl:text><xsl:value-of select="@id" /><xsl:text>';</xsl:text></xsl:otherwise>
						</xsl:choose>
					</xsl:attribute>
					<xsl:if test="position() = 1">
						<xsl:attribute name="checked">
							<xsl:text>checked</xsl:text>
						</xsl:attribute>
					</xsl:if>
				</input>
				<xsl:value-of select="@name" />
			</label>			
		</div>
	</xsl:template>
	
	<xsl:template match="purchasing[@stage = 'payment'][@step = 'chronopay']">
		<form method="post" action="{formAction}">
			<input type="hidden" name="product_id" value="{product_id}" />
			<input type="hidden" name="product_name" value="{product_name}" />
			<input type="hidden" name="product_price" value="{product_price}" />
			<input type="hidden" name="language" value="{language}" />
			<input type="hidden" name="cs1" value="{cs1}" />
			<input type="hidden" name="cs2" value="{cs2}" />
			<input type="hidden" name="cs3" value="{cs3}" />
			<input type="hidden" name="cb_type" value="{cb_type}" />
			<input type="hidden" name="cb_url" value="{cb_url}" />
			<input type="hidden" name="decline_url" value="{decline_url}" />
			<input type="hidden" name="sign" value="{sign}" />

			<div>
				<xsl:text>&payment-redirect-text; Chronopay.</xsl:text>
			</div>        

			<div>
				<input type="submit" value="Оплатить" class="button big" />
			</div>
		</form>
	</xsl:template>

	<xsl:template match="purchasing[@stage = 'payment'][@step = 'yandex']">
		<form action="{formAction}" method="post">
			<input type="hidden" name="shopId"	value="{shopId}" />
			<input type="hidden" name="Sum"		value="{Sum}" />
			<input type="hidden" name="BankId"	value="{BankId}" />
			<input type="hidden" name="scid"	value="{scid}" />
			<input type="hidden" name="CustomerNumber" value="{CustomerNumber}" />
			<input type="hidden" name="order-id" value="{orderId}" />

			<div>
				<xsl:text>&payment-redirect-text; Yandex.</xsl:text>
			</div>

			<div>
				<input type="submit" value="Оплатить" class="button big" />
			</div>
		</form>
	</xsl:template>

	<xsl:template match="purchasing[@stage = 'payment'][@step = 'payonline']">
		<form action="{formAction}" method="post">
		
			<input type="hidden" name="MerchantId" 	value="{MerchantId}" />
			<input type="hidden" name="OrderId" 	value="{OrderId}" />
			<input type="hidden" name="Currency" 	value="{Currency}" />
			<input type="hidden" name="SecurityKey" value="{SecurityKey}" />
			<input type="hidden" name="ReturnUrl" 	value="{ReturnUrl}" />
			<!-- NB! This field should exist for proper system working -->
			<input type="hidden" name="order-id"    value="{orderId}" />
			
			<div>
				<xsl:text>&payment-redirect-text; PayOnline.</xsl:text>
			</div>

			<div>
				<input type="submit" value="Оплатить" class="button big" />
			</div>
		</form>
	</xsl:template>
	
	<xsl:template match="purchasing[@stage = 'payment'][@step = 'robox']">
		<form action="{formAction}" method="post">
			<input type="hidden" name="MrchLogin" value="{MrchLogin}" />
			<input type="hidden" name="OutSum"	  value="{OutSum}" />
			<input type="hidden" name="InvId"	  value="{InvId}" />
			<input type="hidden" name="Desc"	  value="{Desc}" />
			<input type="hidden" name="SignatureValue" value="{SignatureValue}" />
			<input type="hidden" name="IncCurrLabel"   value="{IncCurrLabel}" />
			<input type="hidden" name="Culture"   value="{Culture}" />
			<input type="hidden" name="shp_orderId" value="{shp_orderId}" />

			<div>
				<xsl:text>&payment-redirect-text; Robox.</xsl:text>
			</div>

			<div>
				<input type="submit" value="Оплатить" class="button big" />
			</div>
		</form>
	</xsl:template>
	
	<xsl:template match="purchasing[@stage = 'payment'][@step = 'rbk']">
		<form action="{formAction}" method="post">
			<input type="hidden" name="eshopId" value="{eshopId}" />
			<input type="hidden" name="orderId"	value="{orderId}" />
			<input type="hidden" name="recipientAmount"	value="{recipientAmount}" />
			<input type="hidden" name="recipientCurrency" value="{recipientCurrency}" />
			<input type="hidden" name="version" value="{version}" />		

			<div>
				<xsl:text>&payment-redirect-text; RBK Money.</xsl:text>
			</div>

			<div>
				<input type="submit" value="Оплатить" class="button big" />
			</div>
		</form>
	</xsl:template>
	
	<xsl:template match="purchasing[@stage = 'payment'][@step = 'invoice']" xmlns:xlink="http://www.w3.org/TR/xlink">
		<ul>	
			<xsl:apply-templates select="items/item" mode="legal-persons" />
		</ul>
		<form method="post" action="do">
			<xsl:apply-templates select="document(@xlink:href)" />		
			<div>
				<input type="submit" value="Выписать счет" class="button big" />
			</div>
		</form>
	</xsl:template>
	
	<xsl:template match="item" mode="legal-persons">
		<li>
			<form method="post" action="do">
				<input type="hidden" name="person-id" value="{@id}" />
				<span><xsl:value-of select="@name" /></span>
				<input type="submit" value="Выписать счет" />
			</form>
		</li>
	</xsl:template>

</xsl:stylesheet>