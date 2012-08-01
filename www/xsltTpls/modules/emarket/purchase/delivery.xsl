<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM	"ulang://i18n/constants.dtd:file">

<xsl:stylesheet	version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:xlink="http://www.w3.org/TR/xlink">

	<!-- Выбор адреса доставки -->
	<xsl:template match="purchasing[@stage = 'delivery'][@step = 'address']">
		<form id="delivery_address" method="post" action="{$langPrefix}/emarket/purchase/delivery/address/do/">
			<xsl:apply-templates select="items" mode="delivery-address" />
			<div>
				<input type="submit" value="&continue;" class="button big" />
			</div>
		</form>
		<script>
			jQuery('#delivery_address').submit(function(){
				var input = jQuery('input:radio:checked', this);
				if (typeof input.val() == 'undefined' || input.val() == 'new') {
					if (typeof input.val() == 'undefined') {
						jQuery('input:radio[value=new]', this).attr('checked','checked');
					}
					return site.forms.check(this);
				}
			});
		</script>
	</xsl:template>
	
	<xsl:template match="items" mode="delivery-address">
		<input type="hidden" name="delivery-address" value="new" />
		<xsl:apply-templates select="document(../@xlink:href)//field" mode="form" />
	</xsl:template>

	<xsl:template match="items[count(item) &gt; 0]" mode="delivery-address">
		<h4>
			<xsl:text>&choose-delivery-address;:</xsl:text>
		</h4> 
		
		<!--<div id="new-address">
			<xsl:apply-templates select="document(../@xlink:href)//field" mode="form" />
		</div>-->
		<xsl:apply-templates select="item" mode="delivery-address" />

		<div>
			<label>
				<input type="radio" name="delivery-address" value="new" id="radio_style" />
				<xsl:text>&new-delivery-address;</xsl:text>
			</label>
		</div>

	
	</xsl:template>
	
	<xsl:template match="item" mode="delivery-address">
		<div class="form_element">
			<label>
				<input type="radio" name="delivery-address" value="{@id}" id="radio_style" >
					<xsl:if test="position() = 1">
						<xsl:attribute name="checked">
							<xsl:text>checked</xsl:text>
						</xsl:attribute>
					</xsl:if>
				</input>
				<xsl:apply-templates select="document(concat('uobject://', @id))//property" mode="delivery-address" />
			</label>
		</div>
	</xsl:template>

	<xsl:template match="item[@id='self']" mode="delivery-address">
		<div class="form_element">
			<label>
				<input type="radio" name="delivery-address" value="{@id}" />
			</label>
		</div>
	</xsl:template>

	<xsl:template match="property" mode="delivery-address">
		<xsl:value-of select="value" />
		<xsl:text>, </xsl:text>
	</xsl:template>
	
	<xsl:template match="property[@type = 'relation']" mode="delivery-address">
		<xsl:value-of select="value/item/@name" />
		<xsl:text>, </xsl:text>
	</xsl:template>
	
	<xsl:template match="property[position() = last()]" mode="delivery-address">
		<xsl:value-of select="value" />
	</xsl:template>


	<!-- Выбор способа доставки -->
	<xsl:template match="purchasing[@stage = 'delivery'][@step = 'choose']">
		<form method="post" action="{$langPrefix}/emarket/purchase/delivery/choose/do/">
			<h4>
				<xsl:text>&delivery-agent;:</xsl:text>
			</h4>
			<xsl:apply-templates select="items" mode="delivery-choose" />
			<div>
				<input type="submit" value="&continue;" class="button big" />
			</div>
		</form>
	</xsl:template>
	
	<xsl:template match="item" mode="delivery-choose">
		<xsl:variable name="delivery-price" select="document(concat(@xlink:href, '.price'))//value" />
		<div>
			<label>
				<input type="radio" name="delivery-id" value="{@id}"  id="radio_style">
					<xsl:apply-templates select="." mode="delivery-choose-first" />
				</input>
				<xsl:value-of select="@name" />
				<xsl:apply-templates select="$delivery-price" mode="delivery-price" />
			</label>
		</div>
	</xsl:template>

	<xsl:template match="item" mode="delivery-choose-first" />
	<xsl:template match="item[1]" mode="delivery-choose-first">
		<xsl:attribute name="checked" select="'checked'" />
	</xsl:template>

	<xsl:template match="value" mode="delivery-price">
		<xsl:variable name="formatted-price" select="document(concat('udata://emarket/applyPriceCurrency/', .))/udata" />
		
		<xsl:text> - </xsl:text>
		<xsl:choose>
			<xsl:when test="$formatted-price/price">
				<xsl:apply-templates select="$formatted-price" />
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="." />
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
</xsl:stylesheet>