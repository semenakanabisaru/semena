<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM	"ulang://i18n/constants.dtd:file">

<xsl:stylesheet	version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:umi="http://www.umi-cms.ru/TR/umi">

	<xsl:template match="udata[@method = 'getCompareList']" />

	<xsl:template match="udata[@method = 'getCompareList'][count(items/item) &gt; 0]">
		<div class="infoblock">
			<div class="title">
				<h2>
					<xsl:text>&compare-title;</xsl:text>
				</h2>
			</div>
			<div class="body">
				<div class="in">
					<ul class="compare">
						<xsl:apply-templates select="items/item" />
					</ul>

					<xsl:if test="count(items/item) &gt; 1">
						<a href="{$langPrefix}/emarket/compare/" class="button">
							<xsl:text>&compare-submit;</xsl:text>
						</a>
					</xsl:if>
				</div>
			</div>
		</div>
	</xsl:template>


	<xsl:template match="udata[@method = 'getCompareList']/items/item">
		<li>
			<a href="{$langPrefix}/emarket/removeFromCompare/{@id}/" class="del" />
			<a href="{@link}" class="title" title="{.}" umi:element-id="{@id}" umi:field-name="name">
				<xsl:value-of select="." />
			</a>
		</li>
	</xsl:template>


	<xsl:template match="udata[@method = 'getCompareLink']/add-link">
		<a href="{.}" class="compare">
			<xsl:text>&compare-add;</xsl:text>
		</a>
	</xsl:template>

	<xsl:template match="udata[@method = 'getCompareLink']/del-link">
		<a href="{.}" class="compare">
			<xsl:text>&compare-del;</xsl:text>
		</a>
	</xsl:template>



	<xsl:template match="/result[@method = 'compare']">
		<xsl:apply-templates select="document('udata://emarket/compare')" />
	</xsl:template>

	<xsl:template match="udata[@method = 'compare']">
		<div class="catalog">
			<table class="compare">
				<thead>
					<tr>
						<th />
						<xsl:apply-templates select="headers/items/item" mode="compare-header" />
					</tr>
				</thead>
				<tbody>
					<tr>
						<td class="name"></td>
						<xsl:apply-templates select="headers/items/item" mode="compare-photo" />
					</tr>
					<tr>
						<td class="name">
							<xsl:text>&price;</xsl:text>
						</td>
						<xsl:apply-templates select="headers/items/item" mode="compare-price" />
					</tr>
					<xsl:apply-templates select="fields/field" mode="compare" />
				</tbody>
				<tfoot>
					<td />
					<xsl:apply-templates select="headers/items/item" mode="compare-sale" />
				</tfoot>
			</table>
		</div>
	</xsl:template>
	
	<xsl:template match="headers/items/item" mode="compare-header">
		<th>
			<a href="{@link}" class="title">
				<xsl:value-of select="." />
			</a>
		</th>
	</xsl:template>
	
	<xsl:template match="headers/items/item" mode="compare-photo">
		<td>
			<a href="{@link}" class="image">
				<xsl:call-template name="makeThumbnail">
					<xsl:with-param name="element_id" select="@id" />
					<xsl:with-param name="field_name">photo</xsl:with-param>
					
					<xsl:with-param name="width">115</xsl:with-param>
					<xsl:with-param name="height">90</xsl:with-param>
				</xsl:call-template>
			</a>
		</td>
	</xsl:template>

	<xsl:template match="headers/items/item" mode="compare-price">
		<td>
			<div class="price">
				<span>
					<xsl:apply-templates select="document(concat('udata://emarket/price/', @id))" />
				</span>
			</div>
		</td>
	</xsl:template>

	<xsl:template match="headers/items/item" mode="compare-sale">
		<xsl:variable name="is_options">
			<xsl:choose>
				<xsl:when test="document(concat('upage://', @id))//group[@name = 'catalog_option_props']/property">
					<xsl:value-of select="true()" />
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="false()" />
				</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<td>
			<a	href="{$langPrefix}/emarket/basket/put/element/{@id}/"
				id="add_basket_{@id}" class="button"
				onclick="frontEndBasket.addFromList('{@id}', {$is_options}); return false;">
				<xsl:text>&basket-add;</xsl:text>
			</a>
		</td>
	</xsl:template>

	<xsl:template match="field[@type = 'wysiwyg' or @type = 'img_file' or @type = 'symlink']" mode="compare" />

	<xsl:template match="field" mode="compare">
		<tr>
			<td class="name">
				<xsl:value-of select="@title" />
			</td>
			<xsl:apply-templates select="values/item" mode="compare" />
		</tr>
	</xsl:template>

	<xsl:template match="item" mode="compare">
		<td>
			<xsl:apply-templates select="value" />
		</td>
	</xsl:template>
</xsl:stylesheet>