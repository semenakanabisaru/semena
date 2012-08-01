<?xml version="1.0" encoding="utf-8"?>

<xsl:stylesheet version="1.0" xmlns="http://www.w3.org/1999/xhtml" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

<xsl:output method="html" encoding="utf-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd" />

	<xsl:variable name="errors" select="document('udata://system/listErrorMessages')/udata"/>

	<xsl:variable name="lang-prefix" select="/result/@pre-lang" />
	<xsl:variable name="contact-prefix" select="'/social_networks/vkontakte'" />
	<xsl:variable name="document-page-id" select="/result/@pageId" />
	<xsl:variable name="document-title" select="/result/@title" />
	<xsl:variable name="user-type" select="/result/user/@type" />

	<xsl:variable name="domain" select="/result/@domain" />

	<xsl:variable name="site-info-id" select="document('upage://contacts')/udata/page/@id" />
	<xsl:variable name="site-info" select="document('upage://contacts')//group[@name = 'site_info']/property" />

	<xsl:variable name="user-id" select="/result/user/@id" />
	<xsl:variable name="user-info" select="document(concat('uobject://', $user-id))" />

	<xsl:variable name="module" select="/result/@module" />
	<xsl:variable name="method" select="/result/@method" />

	<xsl:param name="p">0</xsl:param>
	<xsl:param name="search_string" />

	<xsl:template match="/">
		<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru">
			<head>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				<title>
					<xsl:value-of select="$document-title" />
				</title>
				<script type="text/javascript" src="/js/jquery/jquery.js" charset="utf-8"></script>
				<style>
					body {
						margin: 0;
						font-family: Tahoma, Arial, Sans-serif;
						font-size: 11px;
					}
					a {
						outline: none;
						text-decoration: none;
						color: #2b587a;
					}
					img {
						border: none;
					}
					ul {
						padding: 0;
						margin: 0;
					}
					.clear {
						clear: both;
					}
					.errors ul li {
						margin-left:20px;
					}
					div.container {
						background-color: white;
						border: 1px solid #dae1e8;
						margin-bottom: 10px;
						padding: 8px;
					}
					div.container div.site_name {
						font-size: 18px;
						margin-left: 13px;
					}
					div.container div.site_name a {
						color: black;
						text-decoration: none;
					}
					div.container div.site_descr {
						font-size: 11px;
						margin: 3px 13px;
					}
					div.container div.basket_info {
						float: right;
						font-weight: bold;
						margin: 5px 13px 0 0;
						width: 150px;
						padding-left: 30px;
						background: url(/xsl/social/basket.png) no-repeat;
					}
					div.container div.basket_info div span {
						display: block;
					}
					div.container div.basket_info div span span {
						display: inline;
					}
					div.container div.links {
						margin: 8px 13px;
					}
					div.container div.links a {
						margin-right: 35px;
						font-weight: bold;
					}
					div.container ul.catalog_menu {

					}
					div.container ul.catalog_menu li {
						float: left;
						margin: 0 13px;
						list-style: none;
						font-weight: bold;
						line-height: 18px;
						width: 28%;
					}
					div.container ul.catalog_menu li ul li {
						float: none;
						width: auto;
						font-weight: normal;
					}
					div.container h1.header {
						color: black;
						font-size: 14px;
						margin: 0 13px;
						height: 30px;
						line-height: 21px;
					}
					div.container div.content {
						margin: 0 13px 10px 13px;
					}
					div.container div.content hr {
						margin-top: 0;
						clear: both;
						border: none;
						border-bottom: 1px solid #c1cfd8;
					}
					div.container div.content div.object_item {
						width: 33%;
						float: left;
					}
					div.container div.content div.object_item h2 {
						font-size: 11px;
						color: #2b587a;
						margin-left: 15px;
					}
					div.container div.content div.object_item div.image {
						text-align: center;
						margin: 15px 0;
					}
					div.container div.content div.object_item div.price {
						margin: 30px 15px;
					}
					div.container div.content a.button,
					div.container div.content input.button {
						background-color: #6281a5;
						border: 1px solid #416796;
						padding: 4px 10px;
						color: white;
						-moz-border-radius: 3px;
						-webkit-border-radius: 3px;
						border-radius: 3px;
						margin-right: 15px;
						cursor: pointer;
					}
					div.container div.content div.object_item div.price span {
						float: right;
					}
					div.container div.content div.catalog div.item div.image {
						float: left;
					}
					div.container div.content div.catalog div.item div.item_top {
						margin-bottom: 15px;
					}
					div.container div.content div.catalog div.item div.price {
						font-size: 18px;
					}
					div.container div.content div.catalog div.item div.price,
					div.container div.content div.catalog div.item form {
						margin-left: 310px;
						margin-bottom: 10px;
					}
					div.container div.content div.catalog div.item div.social {
						margin-bottom: 10px;
					}
					div.container div.content div.catalog div.social span.social_button {
						float: left;
						margin-right: 5px;
						margin-bottom: 5px;
					}
					div.container div.content div.catalog div.item div.descr h4 {
						margin: 0 0 5px 0;
					}
					div.container div.content div.catalog div.item div.descr p {
						margin-top: 0;
					}
					div.container div.content table.table {
						margin: 0 -10px 15px -10px;
						border-collapse: collapse;
						width: 583px;
					}
					div.container div.content div.catalog form.options table.table {
						width: 272px;
					}
					div.container div.content table.table tr:nth-child(2n) td {
						background-color: #f7f7f7;
					}
					div.container div.content table.table th,
					div.container div.content table.table td {
						padding: 3px 11px;
						height: 25px;
						width: 50%;
					}
					div.container div.content div.basket table.table th,
					div.container div.content div.basket table.table td {
						width: auto;
					}
					div.container div.content table.table th {
						text-align: left;
					}
					table.blue {
						width:100%;
					}
					table.blue td {
						padding:4px 0;
					}
					div.container div.content div.numpages a,
					div.container div.content div.numpages span {
						margin-right: 3px;
					}
					div.container div.content form div {
						margin-bottom: 5px;
					}
					div.container div.content form div label {
						clear: both;
					}
					div.container div.content form div label span {
						float: left;
						display: block;
						width: 150px;
					}
					div.container div.content form div label input,
					div.container div.content form div label select {
						width: 200px;
					}
					div.container div.content form div label input.radio {
						width: auto;
						margin-right: 10px;
					}
					div.container div.content form div input.button {
						margin-top: 10px;
					}
				</style>
				<script src="http://vkontakte.ru/js/api/xd_connection.js?2" type="text/javascript"></script>
				<script type="text/javascript" src="http://vkontakte.ru/js/api/merchant.js" charset="windows-1251"></script>
				<script type="text/javascript" src="http://vkontakte.ru/js/api/merchant.js?13" charset="windows-1251"></script>
				<script type="text/javascript">
					//VK.init({apiId: 2207671, onlyWidgets: true});
					jQuery(document).ready(function(){
						var real_height = document.body.clientHeight;
						var real_width = document.body.clientWidth;
						VK.callMethod('resizeWindow',real_width, real_height);
					});
				</script>
				<script type="text/javascript" src="/js/jquery/jquery.js" charset="utf-8"></script>
				<script type="text/javascript" src="/js/site/__common.js" charset="utf-8"></script>
			</head>
			<body>
				<div class="container">
					<xsl:apply-templates select="document('udata://emarket/cart/')/udata" mode="basket" />
					<div class="site_name">
						<a href="{$contact-prefix}/">
							<xsl:value-of select="document('udata://social_networks/getCurrentSocialParams/nazvanie_sajta')"/>

							</a>
					</div>
					<div class="site_descr">
						<xsl:text>Добро пожаловать на демонстрационный сайт</xsl:text>
					</div>
					<div class="clear" />
				</div>
				<div class="container">
					<div class="links">
						<xsl:apply-templates select="document(concat('uobject://', /result/@socialId))//property[@name='iframe_pages']/value/page" mode="menu"/>
					</div>
				</div>
				<div class="container">
					<xsl:apply-templates select="document('udata://catalog/getCategoryList/void/shop//1/')/udata" mode="left-column" />
				</div>
				<div class="container">
					<xsl:apply-templates select="result" mode="header" />
					<div class="content">
						<xsl:apply-templates select="$errors" />
						<xsl:apply-templates select="result" />
						<div class="clear" />
					</div>
				</div>
			</body>
		</html>
	</xsl:template>

	<xsl:template match="udata[@module = 'emarket' and @method = 'cart']" mode="basket">
		<div class="basket_info" style="display:none;">
			<a href="{$lang-prefix}{$contact-prefix}/emarket/cart/">
				<span class="basket_info_summary">
				<xsl:text>В корзине </xsl:text>
				<xsl:apply-templates select="summary" mode="basket" />
				</span>
			</a>
		</div>
	</xsl:template>

	<xsl:template match="summary" mode="basket">
		<xsl:text>нет ни одного товара</xsl:text>
	</xsl:template>

	<xsl:template match="summary[amount &gt; 0]" mode="basket">
		<xsl:apply-templates select="amount" />
		<xsl:text> товаров на сумму</xsl:text>
		<xsl:apply-templates select="price" />
	</xsl:template>

	<xsl:template match="property[@name='iframe_pages']">
		<div class="links"><xsl:apply-templates select="value/page" /></div>
	</xsl:template>

	<xsl:template match="page" mode="menu">
		<a href="/{@link}"><xsl:value-of select="name" /></a>
	</xsl:template>

	<xsl:template match="udata[@method = 'getCategoryList']" mode="left-column">
		<ul class="catalog_menu">
			<xsl:apply-templates select="//item" mode="left-column" />
		</ul>
		<div class="clear" />
	</xsl:template>

	<xsl:template match="udata[@method = 'getCategoryList']//item" mode="left-column">
		<li>
			<span><a href="/{@link}"><xsl:value-of select="." /></a></span>
			<xsl:apply-templates select="document(concat('udata://catalog/getCategoryList//', @id, '//1/'))/udata" mode="left-column" />
		</li>
	</xsl:template>

	<xsl:template match="result" mode="header">

		<h1 class="header"><xsl:value-of select="@header" /></h1>


	</xsl:template>

	<xsl:template match="result[.//property[@name = 'h1']/value]" mode="header">
		<h1 class="header"><xsl:value-of select=".//property[@name = 'h1']/value" /></h1>
	</xsl:template>

	<xsl:template match="result" />

	<xsl:template match="result[@module = 'content'][@method = 'content']">
		<xsl:value-of select=".//property[@name = 'content']/value" disable-output-escaping="yes" />
	</xsl:template>

	<xsl:template match="result[@module = 'content'][@method = 'content'][page/@is-default]">
		<xsl:apply-templates select="document('udata://catalog/getObjectsList//shop/9//10/')/udata" />
	</xsl:template>

	<xsl:template match="result[@module = 'catalog'][@method = 'category']">
		<xsl:apply-templates select="document('udata://catalog/getObjectsList///9//10/')/udata" />
	</xsl:template>

	<xsl:template match="udata[@module = 'catalog'][@method = 'getObjectsList']">
		<xsl:apply-templates select="lines/item" />
		<xsl:apply-templates select="total" />
	</xsl:template>

	<xsl:template match="udata[@module = 'catalog' and @method = 'getObjectsList']/lines/item|//property[@name = 'recommended_items']/value/page">
		<xsl:apply-templates select="document(concat('upage://', @id))/udata/page" mode ="objects_list">
			<xsl:with-param name="cart_items" select="document('udata://emarket/cart/')/udata/items" />
		</xsl:apply-templates>
	</xsl:template>

	<xsl:template match="udata[@module = 'catalog' and @method = 'getObjectsList']/lines/item[position() mod 3 = 1]|//property[@name = 'recommended_items']/value/page[position() mod 3 = 1]">
		<hr />
		<xsl:apply-templates select="document(concat('upage://', @id))/udata/page" mode ="objects_list">
			<xsl:with-param name="cart_items" select="document('udata://emarket/cart/')/udata/items" />
		</xsl:apply-templates>
	</xsl:template>

	<xsl:template match="page" mode="objects_list">
		<xsl:param name="cart_items" select="false()" />
		<xsl:variable name="is_options">
			<xsl:apply-templates select="document(concat('upage://', @id))/udata/page/properties" mode="is_options" />
		</xsl:variable>
		<div class="object_item">
			<h2><xsl:value-of select=".//property[@name = 'h1']/value" /></h2>
			<div class="image">
				<a href="/{@link}" class="image">
					<xsl:call-template name="catalog-thumbnail">
						<xsl:with-param name="element-id" select="@id" />
						<xsl:with-param name="field-name">photo</xsl:with-param>
						<xsl:with-param name="width">154</xsl:with-param>
						<xsl:with-param name="height">110</xsl:with-param>
					</xsl:call-template>
				</a>
			</div>
			<div class="price">
				<a id="add_basket_{@id}" class="button basket_list_ options_{$is_options}" href="{$lang-prefix}/emarket/basket/put/element/{@id}/" style="display:none;">
					<xsl:text>В корзину</xsl:text>
					<xsl:variable name="element_id" select="@id" />
					<xsl:if test="$cart_items and $cart_items/item[page/@id = $element_id]">
						<xsl:text> (</xsl:text>
						<xsl:value-of select="sum($cart_items/item[page/@id = $element_id]/amount)" />
						<xsl:text>)</xsl:text>
					</xsl:if>
				</a>
				<span style="float:left; margin-bottom: 20px;">
					<xsl:apply-templates select="document(concat('udata://emarket/price/', @id))/udata" />
				</span>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="properties" mode="is_options">
		<xsl:value-of select="false()" />
	</xsl:template>

	<xsl:template match="properties[group[@name = 'catalog_option_props']/property]" mode="is_options">
		<xsl:value-of select="true()" />
	</xsl:template>

	<xsl:template match="result[@module = 'catalog'][@method = 'object']">
		<xsl:apply-templates select="document(concat('upage://', page/@id,'?show-empty'))/udata" mode="object-view" />
	</xsl:template>

	<xsl:template match="udata" mode="object-view">
		<xsl:variable name="cart_items" select="document('udata://emarket/cart/')/udata/items" />
		<div class="catalog">
			<div class="item">
				<div class="item_top">
					<div class="image">
						<xsl:call-template name="catalog-thumbnail">
							<xsl:with-param name="element-id" select="page/@id" />
							<xsl:with-param name="field-name">photo</xsl:with-param>
							<xsl:with-param name="width">281</xsl:with-param>
						</xsl:call-template>
					</div>
					<div class="price">
						<xsl:apply-templates select="document(concat('udata://emarket/price/', page/@id))/udata" />
					</div>
					<form class="options" action="{$lang-prefix}/emarket/basket/put/element/{page/@id}/">
						<xsl:apply-templates select=".//group[@name = 'catalog_option_props']" mode="table_options" />
						<input type="submit" class="button big" id="add_basket_{page/@id}" style="display:none;">
							<xsl:attribute name="value">
								<xsl:text>Добавить в корзину</xsl:text>
								<xsl:if test="$cart_items/item[page/@id = $document-page-id]">
									<xsl:text> (</xsl:text>
									<xsl:value-of select="sum($cart_items/item[page/@id = $document-page-id]/amount)" />
									<xsl:text>)</xsl:text>
								</xsl:if>
							</xsl:attribute>
						</input>
					</form>
					<div class="clear" />
				</div>
				<div class="social">

					<!--<span class="social_button">
						<script type="text/javascript" src="http://vkontakte.ru/js/api/share.js?10" charset="windows-1251" />
						<script type="text/javascript">
							document.write(VK.Share.button(false,{type: "round_nocount", text: "Сохранить"}));
						</script>
					</span>
					<div class="clear" />
					<span class="social_button">
						<div class="mailru">
							<a class="mrc__share" href="http://connect.mail.ru/share">Нравится</a>
							<script src="http://cdn.connect.mail.ru/js/share/2/share.js" type="text/javascript" charset="UTF-8" />
						</div>
					</span>
					<span class="social_button">
						<link href="http://stg.odnoklassniki.ru/share/odkl_share.css" rel="stylesheet" />
						<script src="http://stg.odnoklassniki.ru/share/odkl_share.js" type="text/javascript" />
						<a class="odkl-klass" href="" onclick="ODKL.Share(this); return false;" >Класс!</a>
					</span>
					<span class="social_button">
						<a name="fb_share" id="fb_share" type="button">Опубликовать</a>
						<script src="http://static.ak.fbcdn.net/connect.php/js/FB.Share" type="text/javascript" />
					</span>
					<span class="social_button">
						<a href="http://twitter.com/share" class="twitter-share-button" data-count="horizontal" data-via="umi_cms">Tweet</a>
						<script type="text/javascript" src="http://platform.twitter.com/widgets.js" />
					</span>-->
					<div class="clear" />
				</div>
				<xsl:apply-templates select=".//property[@name = 'description']" />
				<xsl:apply-templates select=".//group[@name = 'item_properties']" mode="table" />
				<div class="clear" />
			</div>
			<xsl:apply-templates select=".//property[@name = 'recommended_items']" />
			<!-- <xsl:apply-templates select="document('udata://comments/insert')/udata" /> -->
		</div>
	</xsl:template>

	<xsl:template match="property[@name = 'recommended_items']" />
	<xsl:template match="property[@name = 'recommended_items'][value/page]">
		<h4><xsl:value-of select="title" />:</h4>
		<xsl:apply-templates select="value/page" />
	</xsl:template>

	<xsl:template match="property[@name = 'description']" />
	<xsl:template match="property[@name = 'description'][value != '']">
		<div class="descr">
			<h4><xsl:value-of select="title" />:</h4>
			<div><xsl:value-of select="value" disable-output-escaping="yes" /></div>
		</div>
	</xsl:template>

	<xsl:template match="group" mode="table">
		<table class="table">
			<thead>
				<tr>
					<th colspan="2">
						<xsl:value-of select="concat(title, ':')" />
					</th>
				</tr>
			</thead>
			<tbody><xsl:apply-templates select="property" mode="table" /></tbody>
		</table>
	</xsl:template>

	<xsl:template match="property" mode="table">
		<tr>
			<td>
				<span>
					<xsl:apply-templates select="document(concat('utype://', ../../../@type-id, '.', ../@name))/udata/group/field[@name = ./@name]/tip" mode="tip" />
					<xsl:value-of select="title" />
				</span>
			</td>
			<td><xsl:apply-templates select="value" /></td>
		</tr>
	</xsl:template>

	<xsl:template match="group" mode="table_options">
		<xsl:if test="count(//option) &gt; 0">
			<h4><xsl:value-of select="concat(title, ':')" /></h4>
			<xsl:apply-templates select="property" mode="table_options" />
		</xsl:if>
	</xsl:template>

	<xsl:template match="property" mode="table_options">
		<table class="table">
			<thead>
				<tr>
					<th colspan="3">
						<xsl:value-of select="concat(title, ':')" />
					</th>
				</tr>
			</thead>
			<tbody>
				<xsl:apply-templates select="value/option" mode="table_options" />
			</tbody>
		</table>
	</xsl:template>

	<xsl:template match="option" mode="table_options">
		<tr>
			<td style="width:20px;">
				<input type="radio" class="radio" name="options[{../../@name}]" value="{object/@id}">
					<xsl:if test="position() = 1">
						<xsl:attribute name="checked">
							<xsl:text>checked</xsl:text>
						</xsl:attribute>
					</xsl:if>
				</input>
			</td>
			<td>
				<xsl:value-of select="object/@name" />
			</td>
			<td align="right">
				<xsl:value-of select="@float" />
			</td>
		</tr>
	</xsl:template>

	<xsl:template match="tip" mode="tip">
		<xsl:attribute name="title">
			<xsl:apply-templates />
		</xsl:attribute>
		<xsl:attribute name="style">
			<xsl:text>border-bottom:1px dashed; cursor:help;</xsl:text>
		</xsl:attribute>
	</xsl:template>

	<xsl:template match="udata[@method = 'price']">
		<xsl:apply-templates select="price" />
	</xsl:template>

	<xsl:template match="price|total-price">
		<xsl:value-of select="concat(@prefix, ' ', actual, ' ', @suffix)" />
	</xsl:template>

	<xsl:template match="udata[@module = 'system' and @method = 'listErrorMessages']" />
	<xsl:template match="udata[@module = 'system' and @method = 'listErrorMessages'][count(items/item) &gt; 0]">
		<div class="errors">
			<h3><xsl:text>Ошибки:</xsl:text></h3>
			<ul><xsl:apply-templates select="items/item" mode="error" /></ul>
			<br/>
		</div>
	</xsl:template>

	<xsl:template match="item" mode="error">
		<li><xsl:value-of select="." /></li>
	</xsl:template>

	<xsl:template match="error">
		<div class="errors">
			<h3><xsl:text>Ошибки:</xsl:text></h3>
			<ul><li><xsl:value-of select="." /></li></ul>
		</div>
	</xsl:template>

	<xsl:template match="property[@type = 'date']">
		<xsl:param name="pattern" />
		<xsl:call-template name="format-date">
			<xsl:with-param name="date" select="value/@unix-timestamp" />
			<xsl:with-param name="pattern" select="$pattern" />
		</xsl:call-template>
	</xsl:template>

	<xsl:template name="format-date">
		<xsl:param name="date" />
		<xsl:param name="pattern" select="'d.m.Y'" />
		<xsl:variable name="uri" select="concat('udata://system/convertDate/', $date, '/(', $pattern, ')')" />
		<xsl:value-of select="document($uri)/udata" />
	</xsl:template>

	<xsl:template match="udata[@module = 'system' and @method = 'captcha']" />
	<xsl:template match="udata[@module = 'system' and @method = 'captcha' and count(url)]">
			<div>
				<label class="required">
					<span><xsl:text>Введите код с картинки:</xsl:text></span>
					<input type="text" name="captcha" class="textinputs captcha" />
					<img src="{url}{url/@random-string}" id="captcha_img" />
					<span id="captcha_reset"><xsl:text>перезагрузить код</xsl:text></span>
				</label>
			</div>
	</xsl:template>

	<xsl:template match="total" />
	<xsl:template match="total[. &gt; ../per_page]">
		<xsl:apply-templates select="document(concat('udata://system/numpages/', ., '/', ../per_page))/udata" />
	</xsl:template>

	<xsl:template match="udata[@method = 'numpages'][count(items)]" />
	<xsl:template match="udata[@method = 'numpages']">
		<div class="numpages">
			<span class="links">
				<xsl:apply-templates select="toprev_link" />
			</span>
			<span class="pages">
				<xsl:apply-templates select="items/item" mode="numpages" />
			</span>
			<span class="links">
				<xsl:apply-templates select="tonext_link" />
			</span>
		</div>
	</xsl:template>

	<xsl:template match="item" mode="numpages">
		<a href="/{@link}"><xsl:value-of select="." /></a>
	</xsl:template>

	<xsl:template match="item[@is-active = '1']" mode="numpages">
		<span><xsl:value-of select="." /></span>
	</xsl:template>

	<xsl:template match="toprev_link">
		<a class="prev" href="/{.}"><xsl:text>Предыдущая</xsl:text></a>
	</xsl:template>

	<xsl:template match="tonext_link">
		<a class="next" href="/{.}"><xsl:text>Следующая</xsl:text></a>
	</xsl:template>

	<xsl:template match="item" mode="slider">
		<xsl:apply-templates select="preceding-sibling::item[1]" mode="slider_back" />
		<xsl:apply-templates select="following-sibling::item[1]" mode="slider_next" />
	</xsl:template>

	<xsl:template match="item" mode="slider_back">
		<a href="/{@link}" title="Предыдущая" class="back" />
	</xsl:template>

	<xsl:template match="item" mode="slider_next">
		<a href="/{@link}" title="Следующая" class="next" />
	</xsl:template>

	<xsl:template name="catalog-thumbnail">
		<xsl:param name="element-id" />
		<xsl:param name="field-name" />
		<xsl:param name="empty" />
		<xsl:param name="width">auto</xsl:param>
		<xsl:param name="height">auto</xsl:param>
		<xsl:variable name="property" select="document(concat('upage://', $element-id, '.', $field-name))/udata/property" />
		<xsl:call-template name="thumbnail">
			<xsl:with-param name="width" select="$width" />
			<xsl:with-param name="height" select="$height" />
			<xsl:with-param name="element-id" select="$element-id" />
			<xsl:with-param name="field-name" select="$field-name" />
			<xsl:with-param name="empty" select="$empty" />
			<xsl:with-param name="src">
				<xsl:choose>
					<xsl:when test="$property/value">
						<xsl:value-of select="$property/value" />
					</xsl:when>
					<xsl:otherwise>/xsl/social/nofoto.jpg</xsl:otherwise>
				</xsl:choose>
			</xsl:with-param>
		</xsl:call-template>
	</xsl:template>

	<xsl:template name="thumbnail">
		<xsl:param name="src" />
		<xsl:param name="width">auto</xsl:param>
		<xsl:param name="height">auto</xsl:param>
		<xsl:param name="empty" />
		<xsl:param name="element-id" />
		<xsl:param name="field-name" />
		<xsl:apply-templates select="document(concat('udata://system/makeThumbnailFull/(.', $src, ')/', $width, '/', $height, '/void/0/1/'))/udata">
			<xsl:with-param name="element-id" select="$element-id" />
			<xsl:with-param name="field-name" select="$field-name" />
			<xsl:with-param name="empty" select="$empty" />
		</xsl:apply-templates>
	</xsl:template>

	<xsl:template match="udata[@module = 'system' and (@method = 'makeThumbnail' or @method = 'makeThumbnailFull')]">
		<xsl:param name="element-id" />
		<xsl:param name="field-name" />
		<xsl:param name="empty" />
		<img src="{src}" width="{width}" height="{height}" />
	</xsl:template>

	<xsl:template match="result[@method = 'cart']">
		<xsl:apply-templates select="document('udata://emarket/cart')/udata" />
		<xsl:if test="$user-type != 'guest'">
			<p style="margin-top:30px;">
				<a href="{$lang-prefix}{$contact-prefix}/emarket/ordersList/" >
					<xsl:text>Посмотреть список заказов</xsl:text>
				</a>
			</p>
		</xsl:if>
	</xsl:template>

	<xsl:template match="udata[@method = 'cart']">
		<div class="basket">
			<xsl:text>В корзине нет ни одного товара</xsl:text>
		</div>
	</xsl:template>

	<xsl:template match="udata[@method = 'cart'][count(items/item) &gt; 0]">
		<div class="basket">
			<table class="table">
				<thead>
					<tr>
						<th class="name">
							<xsl:text>Наименование</xsl:text>
						</th>
						<th>
							<xsl:text>Цена</xsl:text>
						</th>
						<th>
							<xsl:text>Кол-во</xsl:text>
						</th>
						<th>
							<xsl:text>Сумма</xsl:text>
						</th>
						<th>
							<xsl:text>Удалить</xsl:text>
						</th>
					</tr>
				</thead>
				<tbody>
					<xsl:apply-templates select="items/item" />
				</tbody>
				<xsl:apply-templates select="summary" />
			</table>
			<div>
				<a href="{$lang-prefix}/emarket/basket/remove_all/" class="button big basket_remove_all">
					<xsl:text>Очистить корзину</xsl:text>
				</a>
				<a href="{$lang-prefix}{$contact-prefix}/emarket/purchase/" class="button big">
					<xsl:text>Оформить</xsl:text>
				</a>

				<br/><br/>
			</div>
			<div class="clear"></div>
		</div>
	</xsl:template>

	<xsl:template match="udata[@method = 'cart']//item">
		<tr class="cart_item_{@id}">
			<td class="name"><a href="/{page/@link}"><xsl:value-of select="@name" /></a></td>
			<td><xsl:apply-templates select="price" /></td>
			<td><xsl:value-of select="amount" /></td>
			<td>
				<span class="cart_item_price_{@id}">
					<xsl:apply-templates select="total-price" />
				</span>
			</td>
			<td>
				<a href="{$lang-prefix}/emarket/basket/remove/item/{@id}/" id="{@id}" class="del" >x</a>
			</td>
		</tr>
	</xsl:template>

	<xsl:template match="udata[@method = 'cart']/summary">
		<tfoot>
			<tr>
				<td colspan="6" align="right">
					<xsl:text>Итого: </xsl:text>
					<span class="cart_summary">
						<xsl:apply-templates select="price" />
					</span>
				</td>
			</tr>
		</tfoot>
	</xsl:template>

	<xsl:template match="result[@method = 'purchase']">
		<xsl:apply-templates select="document('udata://emarket/purchase/')/udata" />
	</xsl:template>

	<xsl:template match="purchasing">
		<h4>
			<xsl:text>Purchase is in progress: </xsl:text>
			<xsl:value-of select="concat(@stage, '::', @step, '()')" />
		</h4>
	</xsl:template>

	<xsl:template match="purchasing[@stage = 'required'  and @step = 'personal']">

		<form enctype="multipart/form-data" method="post" action="{$lang-prefix}{$contact-prefix}/emarket/purchase/required/personal/do/">
			<xsl:apply-templates select="document(concat('udata://data/getEditForm/', customer-id))/udata" />

			<div>
				<input type="submit" class="button" value="Сохранить" />
			</div>
		</form>

	</xsl:template>

	<xsl:template match="purchasing[@stage = 'result']">
		<p><xsl:text>Не удалось совершить покупку</xsl:text></p>
	</xsl:template>

	<xsl:template match="purchasing[@stage = 'result' and @step = 'successful']">
		<p><xsl:text>Заказ успешно добавлен.</xsl:text></p>
	</xsl:template>

	<xsl:template match="purchasing[@stage = 'delivery'][@step = 'address']">
		<form id="delivery_address" method="post" action="{$lang-prefix}{$contact-prefix}/emarket/purchase/delivery/address/do/">
			<xsl:apply-templates select="items" />
			<div>
				<input type="submit" value="Продолжить" class="button big" />
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

	<xsl:template match="purchasing[@stage = 'delivery' and @step = 'address']/items">
		<input type="hidden" name="delivery-address" value="new" />
		<xsl:apply-templates select="document(concat('udata://data/getCreateForm/', ../@type-id))//field" mode="form" />
	</xsl:template>

	<xsl:template match="purchasing[@stage = 'delivery' and @step = 'address']/items[count(item) &gt; 0]">
		<h4>
			<xsl:text>Выберите адрес доставки:</xsl:text>
		</h4>
		<xsl:apply-templates select="item" />
		<div>
			<label>
				<input type="radio" class="radio" name="delivery-address" value="new" />
				<xsl:text>Новый адрес доставки</xsl:text>
			</label>
		</div>
		<div id="new-address">
			<xsl:apply-templates select="document(concat('udata://data/getCreateForm/', ../@type-id))//field" mode="form" />
		</div>
	</xsl:template>

	<xsl:template match="purchasing[@stage = 'delivery' and @step = 'address']/items/item">
		<div class="form_element">
			<label>
				<input type="radio" class="radio" name="delivery-address" value="{@id}">
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
				<input type="radio" class="radio" name="delivery-address" value="{@id}" />
				<xsl:text></xsl:text>
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

	<xsl:template match="purchasing[@stage = 'delivery'][@step = 'choose']">
		<form method="post" action="{$lang-prefix}{$contact-prefix}/emarket/purchase/delivery/choose/do/">
			<h4>
				<xsl:text>Способ доставки:</xsl:text>
			</h4>
			<xsl:apply-templates select="items" mode="delivery-choose" />
			<div>
				<input type="submit" value="Продолжить" class="button big" />
			</div>
		</form>
	</xsl:template>

	<xsl:template match="item" mode="delivery-choose">
		<xsl:variable name="delivery-price" select="@price"/>

		<div>
			<label>
				<input type="radio" class="radio" name="delivery-id" value="{@id}">
					<xsl:apply-templates select="." mode="delivery-choose-first" />
				</input>
				<xsl:value-of select="@name" />

				<xsl:call-template  name="delivery-price" >
					<xsl:with-param name="price" select="$delivery-price"/>
				</xsl:call-template >
			</label>
		</div>
	</xsl:template>

	<xsl:template match="item" mode="delivery-choose-first" />
	<xsl:template match="item[1]" mode="delivery-choose-first">
		<xsl:attribute name="checked" select="'checked'" />
	</xsl:template>

	<xsl:template name="delivery-price">
		<xsl:param name="price" select="0"/>

		<xsl:variable name="formatted-price" select="document(concat('udata://emarket/applyPriceCurrency/', $price))/udata" />

		<xsl:text> - </xsl:text>
		<xsl:choose>
			<xsl:when test="$formatted-price/price">
				<xsl:apply-templates select="$formatted-price" />
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$price" />
			</xsl:otherwise>
		</xsl:choose>
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

	<xsl:template match="purchasing[@stage = 'payment'][@step = 'choose']">
		<h4>
			<xsl:text>Способы оплаты:</xsl:text>
		</h4>
		<form id="payment_choose" method="post" action="do/">
			<script>
				<![CDATA[
					window.paymentId = null;
					jQuery('#payment_choose').submit(function(){
						if (window.paymentId) {
							var checkPaymentReceipt = function(id) {
								if (jQuery(':radio:checked','#payment_choose').hasClass('receipt')) {
									var url = window.location.href;
									var win = window.open("", "_blank", "width=710,height=620,titlebar=no,toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=no");
									win.document.write("<html><head><" + "script" + ">location.href = '" + url + "do/?payment-id=" + id + "'</" + "script" + "></head><body></body></html>");
									location.href = ']]><xsl:value-of select='$contact-prefix'/><![CDATA[/emarket/ordersList/?' + Math.random();
									win.focus();
									return false;
								}
							}
							return checkPaymentReceipt(window.paymentId);
						}
						else return true;
					});
					]]>
			</script>
			<xsl:apply-templates select="items/item" mode="payment" />
			<div><input type="submit" value="Продолжить" class="button big" /></div>
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
				<input type="radio" name="payment-id" class="{@type-name} radio" value="{@id}">
					<xsl:attribute name="onclick">
						<xsl:text>this.form.action = </xsl:text>
						<xsl:choose>
							<xsl:when test="@type-name != 'receipt'"><xsl:text>'do/';</xsl:text></xsl:when>
							<xsl:otherwise>
								<xsl:text>'</xsl:text>
								<xsl:value-of select='concat($contact-prefix, "/emarket/ordersList/")'/>
								<xsl:text>'; window.paymentId = '</xsl:text><xsl:value-of select="@id" /><xsl:text>';</xsl:text>
							</xsl:otherwise>
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
		<form method="post" action="{formAction}" target='_blank'>
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
			<div><xsl:text>Нажмите кнопку 'Оплатить' для перехода на сайт платежной системы Chronopay.</xsl:text></div>
			<div>
				<input type="submit" value="Оплатить" class="button big" />
			</div>
		</form>
	</xsl:template>

	<xsl:template match="purchasing[@stage = 'payment'][@step = 'yandex']">
		<form action="{formAction}" method="post" target='_blank'>
			<input type="hidden" name="shopId" value="{shopId}" />
			<input type="hidden" name="Sum" value="{Sum}" />
			<input type="hidden" name="BankId" value="{BankId}" />
			<input type="hidden" name="scid" value="{scid}" />
			<input type="hidden" name="CustomerNumber" value="{CustomerNumber}" />
			<input type="hidden" name="order-id" value="{orderId}" />
			<div><xsl:text>Нажмите кнопку 'Оплатить' для перехода на сайт платежной системы Yandex.</xsl:text></div>
			<div><input type="submit" value="Оплатить" class="button big" /></div>
		</form>
	</xsl:template>

	<xsl:template match="purchasing[@stage = 'payment'][@step = 'payonline']">
		<form action="{formAction}" method="post" target='_blank'>
			<input type="hidden" name="MerchantId"	value="{MerchantId}" />
			<input type="hidden" name="OrderId"		value="{OrderId}" />
			<input type="hidden" name="Currency"	value="{Currency}" />
			<input type="hidden" name="SecurityKey"	value="{SecurityKey}" />
			<input type="hidden" name="ReturnUrl"	value="{ReturnUrl}" />
			<!-- NB! This field should exist for proper system working -->
			<input type="hidden" name="order-id"	value="{orderId}" />
			<div><xsl:text>Нажмите кнопку 'Оплатить' для перехода на сайт платежной системы PayOnline.</xsl:text></div>
			<div><input type="submit" value="Оплатить" class="button big" /></div>
		</form>
	</xsl:template>

	<xsl:template match="purchasing[@stage = 'payment'][@step = 'robox']">
		<form action="{formAction}" method="post" target='_blank'>
			<input type="hidden" name="MrchLogin" value="{MrchLogin}" />
			<input type="hidden" name="OutSum"	  value="{OutSum}" />
			<input type="hidden" name="InvId"	  value="{InvId}" />
			<input type="hidden" name="Desc"	  value="{Desc}" />
			<input type="hidden" name="SignatureValue" value="{SignatureValue}" />
			<input type="hidden" name="IncCurrLabel"   value="{IncCurrLabel}" />
			<input type="hidden" name="Culture"   value="{Culture}" />
			<input type="hidden" name="shp_orderId" value="{shp_orderId}" />
			<div><xsl:text>Нажмите кнопку 'Оплатить' для перехода на сайт платежной системы Robox.</xsl:text></div>
			<div><input type="submit" value="Оплатить" class="button big" /></div>
		</form>
	</xsl:template>

	<xsl:template match="purchasing[@stage = 'payment'][@step = 'rbk']">
		<form action="{formAction}" method="post" target='_blank'>
			<input type="hidden" name="eshopId" value="{eshopId}" />
			<input type="hidden" name="orderId"	value="{orderId}" />
			<input type="hidden" name="recipientAmount"	value="{recipientAmount}" />
			<input type="hidden" name="recipientCurrency" value="{recipientCurrency}" />
			<input type="hidden" name="version" value="{version}" />
			<div><xsl:text>Нажмите кнопку 'Оплатить' для перехода на сайт платёжной системы RBK Money.</xsl:text></div>
			<div><input type="submit" value="Оплатить" class="button big" /></div>
		</form>
	</xsl:template>

	<xsl:template match="purchasing[@stage = 'payment'][@step = 'invoice']" xmlns:xlink="http://www.w3.org/TR/xlink">
		<ul><xsl:apply-templates select="items/item" mode="legal-persons" /></ul>
		<form method="post" action="do">
			<xsl:apply-templates select="document(@xlink:href)" />
			<div><input type="submit" value="Выписать счет" class="button big" /></div>
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

	<xsl:template match="udata[@method = 'getCreateForm' or @method = 'getEditForm']">
		<xsl:apply-templates select="group" mode="form" />
	</xsl:template>

	<xsl:template match="group" mode="form">
		<h4><xsl:value-of select="@title" /></h4>
		<xsl:apply-templates select="field" mode="form" />
	</xsl:template>

	<xsl:template match="field" mode="form">
		<div>
			<label title="{@tip}">
				<xsl:apply-templates select="@required" mode="form" />
				<span><xsl:value-of select="concat(@title, ':')" /></span>
				<input type="text" name="{@input_name}" value="{.}" class="textinputs" />
			</label>
		</div>
	</xsl:template>

	<xsl:template match="field[@type = 'relation']" mode="form">
		<div>
			<label title="{@tip}">
				<xsl:apply-templates select="@required" mode="form" />
				<span>
					<xsl:value-of select="concat(@title, ':')" />
				</span>
				<select type="text" name="{@input_name}">
					<xsl:if test="@multiple = 'multiple'">
						<xsl:attribute name="multiple">multiple</xsl:attribute>
					</xsl:if>
					<xsl:apply-templates select="values/item" mode="form" />
				</select>
			</label>
		</div>
	</xsl:template>

	<xsl:template match="item" mode="form">
		<option value="{@id}">
			<xsl:copy-of select="@selected" />
			<xsl:value-of select="." />
		</option>
	</xsl:template>

	<xsl:template match="field[@type = 'boolean']" mode="form">
		<div>
			<label title="{@tip}">
				<xsl:apply-templates select="@required" mode="form" />
				<span>
					<xsl:value-of select="concat(@title, ':')" />
				</span>
				<input type="hidden" name="{@input_name}" value="0" />
				<input type="checkbox" name="{@input_name}" value="1">
					<xsl:copy-of select="@checked" />
				</input>
			</label>
		</div>
	</xsl:template>

	<xsl:template match="field[@type = 'text' or @type = 'wysiwyg']" mode="form">
		<div>
			<label title="{@tip}">
				<xsl:apply-templates select="@required" mode="form" />
				<span>
					<xsl:value-of select="concat(@title, ':')" />
				</span>
				<textarea name="{@input_name}" class="textinputs">
					<xsl:value-of select="." />
				</textarea>
			</label>
		</div>
	</xsl:template>

	<xsl:template match="field[@type = 'file' or @type = 'img_file']" mode="form">
		<div>
			<label title="{@tip}">
				<xsl:apply-templates select="@required" mode="form" />
				<span>
					<xsl:value-of select="concat(@title, ':')" />
				</span>

				<input type="file" name="{@input_name}" class="textinputs" />
			</label>
		</div>
	</xsl:template>

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
						<th class="name">
							<xsl:text>Номер</xsl:text>
						</th>

						<th class="name">
							<xsl:text>Статус</xsl:text>
						</th>

						<th>
							<xsl:text>Сумма</xsl:text>
						</th>
					</tr>
				</thead>
				<tbody>
					<xsl:apply-templates select="items/item" mode="ordersList" />
				</tbody>
			</table>
		</div>
	</xsl:template>

	<xsl:template match="item" mode="ordersList">
		<xsl:apply-templates select="document(concat('udata://emarket/order/', @id))/udata" />
		<tr>
			<td colspan="3" class="separate"></td>
		</tr>
	</xsl:template>

	<xsl:template match="item[position() = last()]" mode="ordersList">
		<xsl:apply-templates select="document(concat('udata://emarket/order/', @id))/udata" />
	</xsl:template>


	<xsl:template match="@required" mode="form">
		<xsl:attribute name="class">required</xsl:attribute>
	</xsl:template>

	<xsl:template match="udata[@module = 'emarket'][@method = 'order']">
		<tr>
			<td class="name">
				<strong>
					<xsl:text>№ </xsl:text>
					<xsl:value-of select="number" />
				</strong>
				<div>
					<xsl:text>от  </xsl:text>
					<xsl:apply-templates select="document(concat('uobject://', @id, '.order_date'))//property" />
				</div>
			</td>
			<td class="name">
				<xsl:value-of select="status/@name" />
				<div>
					<xsl:text>с </xsl:text>
					<xsl:apply-templates select="document(concat('uobject://', @id, '.status_change_date'))//property" />
				</div>
			</td>
			<td>
				<xsl:apply-templates select="summary/price" />
			</td>
		</tr>

		<xsl:apply-templates select="items/item" />
	</xsl:template>

	<xsl:template match="udata[@method = 'order']/items/item">
		<tr>
			<td colspan="2" class="name">
				<a href="/{page/@link}">
					<xsl:value-of select="@name" />
				</a>
			</td>

			<td>
				<xsl:apply-templates select="price" />
				<xsl:text> x </xsl:text>
				<xsl:apply-templates select="amount" />
				<xsl:text> = </xsl:text>
				<xsl:apply-templates select="total-price" />
			</td>
		</tr>
	</xsl:template>

	<xsl:template match="property[@type = 'date']">
		<xsl:param name="pattern" select="'d.m.Y'" />
		<xsl:call-template name="format-date">
			<xsl:with-param name="date" select="value/@unix-timestamp" />
			<xsl:with-param name="pattern" select="$pattern" />
		</xsl:call-template>
	</xsl:template>

	<xsl:template match="result[@module = 'blogs20'][@method = 'blog']">
		<xsl:variable name="ownerId" select="document(concat('udata://blogs20/viewBlogAuthors/',$document-page-id,'/'))/udata/users/item[@is_owner = '1']/@user_id" />
		<xsl:variable name="avatar" select="document(concat('uobject://',$ownerId,'.avatar'))/udata/property/value/@path" />



				<div class="clear" />

			<xsl:apply-templates select="document(concat('udata://blogs20/postsList/',$document-page-id))/udata" />


	</xsl:template>

	<xsl:template match="udata" mode="author_info">
		<h1>
			<xsl:choose>
				<xsl:when test="//group[@name = 'short_info']">
					<xsl:value-of select="//property[@name = 'fname']/value" />&#160;<xsl:value-of select="//property[@name = 'lname']/value" />
				</xsl:when>
				<xsl:otherwise>
					<xsl:value-of select="//property[@name = 'login']/value" />
				</xsl:otherwise>
			</xsl:choose>
		</h1>
		<div class="prof"><xsl:value-of select="//property[@name = 'jobpost']/value" /></div>
	</xsl:template>

	<xsl:template match="item" mode="page">
		<div class="blog_item">
			<div class="blog_header">
				<a href="/{post_link}">
					<strong><xsl:value-of select="name" /></strong>
				</a>
				<xsl:if test="publish_time">
					<span>
						<xsl:text>Добавлено </xsl:text>
						<xsl:apply-templates select="document(concat('udata://system/convertDate/',publish_time,'/d.m.Y/'))/udata" />
					</span>
				</xsl:if>
				<xsl:if test="tags">
					<span >
						<xsl:text>(</xsl:text>
						<xsl:apply-templates select="tags/item" mode="post_tags" />
						<xsl:text>)</xsl:text>
					</span>
				</xsl:if>
			</div>
			<div>
				<xsl:value-of select="cut" disable-output-escaping="yes" />
			</div>
			<div class="comments">
				<a href="/{post_link}#subitems">Комментарии (<xsl:value-of select="comments_count" />)</a> | <a href="/{post_link}#additem">Комментировать</a>
			</div>
		</div>
	</xsl:template>

	<xsl:template match="udata[@module = 'blogs20'][@method = 'postsList']">
		<div id="blog_items">
			<xsl:apply-templates select="items/item" mode="page" />
		</div>
		<xsl:apply-templates select="total" />
	</xsl:template>

	<xsl:template match="result[@module = 'blogs20'][@method = 'post']">
		<xsl:variable name="publish_time" select="//property[@name = 'publish_time']/value/@unix-timestamp" />
		<xsl:variable name="tags" select="//property[@name = 'tags']" />
		<div id="blog_items">
			<div class="blog_item">
				<div class="blog_header">
					<xsl:if test="$publish_time">
						<span >
							<xsl:text>Добавлено </xsl:text>
							<xsl:apply-templates select="document(concat('udata://system/convertDate/',$publish_time,'/d.m.Y/'))/udata" />
						</span>
					</xsl:if>
					<xsl:if test="$tags">
						<span >
							<xsl:text>(</xsl:text>
							<xsl:apply-templates select="$tags/value" mode="post_tags" />
							<xsl:text>)</xsl:text>
						</span>
					</xsl:if>
				</div>
				<div>
					<xsl:value-of select="document(concat('udata://blogs20/postView/',$document-page-id))/udata/content" disable-output-escaping="yes" />
				</div>
			</div>

				<div class="social">
					<script type="text/javascript">
						jQuery(document).ready(function(){ jQuery.getScript('//yandex.st/share/share.js', function() {
					new Ya.share({
						'element': 'ya_share1',
						'elementStyle': {
							'type': 'button',
							'linkIcon': true,
							'border': false,
							'quickServices': ['yaru', 'vkontakte', 'facebook', 'twitter', 'odnoklassniki', 'moimir', 'lj']
						},
						'popupStyle': {
							'copyPasteField': true
						}
					 });
							});
						});
					</script>
					<span id="ya_share1"></span>
				</div>
				<div class="clear" />

			<a name="subitems"></a>
				<xsl:apply-templates select="document(concat('udata://blogs20/commentsList/',$document-page-id,'/'))/udata" />
			<a name="comment_add" />
			<h3>Добавить комментарий:</h3>
			<xsl:apply-templates select="document('udata://blogs20/checkAllowComments/')/udata" />
		</div>
	</xsl:template>


	<xsl:template match="value" mode="post_tags">
		<a href="/blogs20/postsByTag/{.}">
			<xsl:value-of select="." />
		</a>
		<xsl:if test="position() != last()">
			<xsl:text>, </xsl:text>
		</xsl:if>

	</xsl:template>

	<xsl:template match="udata[@module = 'blogs20'][@method = 'checkAllowComments']">
		<p><xsl:text>Для того, чтобы добавить коментарий, авторизируйтесь.</xsl:text></p>
		<xsl:apply-templates select="document('udata://users/auth/')/udata" />
	</xsl:template>

	<xsl:template match="udata[@module = 'blogs20'][@method = 'checkAllowComments'][. = 1]">
		<xsl:param name="user-group" select="document(concat('uobject://', /result/user/@id))//property[@name = 'groups']/value" />
		<form id="comment_add_form" name="frm_addblogmsg" method="post" action="/blogs20/commentAdd/{$document-page-id}/">
			<div class="form_element">
				<a name="additem" />
				<label class="required">
					<span><xsl:text>Заголовок комментария:</xsl:text></span>
					<input type="text" name="title" class="textinputs" />
				</label>
			</div>
			<xsl:if test="not($user-group/item/@guid)">
				<div class="form_element">
					<label class="required">
						<span><xsl:text>Ваш ник:</xsl:text></span>
						<input type="text" name="nick" class="textinputs" />
					</label>
				</div>
				<div class="form_element">
					<label class="required">
						<span><xsl:text>Ваш email:</xsl:text></span>
						<input type="text" name="email" class="textinputs" />
					</label>
				</div>
			</xsl:if>
			<div class="form_element">
				<label class="required">
					<span><xsl:text>Текст комментария:</xsl:text></span>
					<textarea name="content"></textarea>
				</label>
			</div>
			<div class="form_element">
				<xsl:apply-templates select="document('udata://system/captcha/')/udata" />
			</div>
			<div class="form_element">
				<input type="submit" class="button" value="Добавить комментарий" />
			</div>
		</form>
	</xsl:template>

		<xsl:template match="udata[@module = 'blogs20'][@method = 'commentsList']" />

	<xsl:template match="udata[@module = 'blogs20'][@method = 'commentsList'][total]">
		<div id="comments">
			<h3>Комментарии:</h3>
			<xsl:apply-templates select="items" mode="commentsList" />
		</div>
		<xsl:apply-templates select="total" />
	</xsl:template>

	<xsl:template match="items|subcomments" mode="commentsList">
		<xsl:apply-templates select="item" mode="commentsList" />
	</xsl:template>

	<xsl:template match="item" mode="commentsList">
		<div class="comment">
			<strong >
				<xsl:apply-templates select="document(concat('udata://system/convertDate/',publish_time,'/d.m.Y%20%E2%20H:i/'))/udata" />
			</strong> |
			<xsl:apply-templates select="document(concat('uobject://',author_id,'.user_id'))//item" mode="autorComments" />
			<xsl:value-of select="document(concat('uobject://',author_id,'.nickname'))//value" />
			<xsl:if test="$user-type = 'sv'">| <a href="/blogs20/itemDelete/{@cid}/">Удалить</a></xsl:if>
			<div  class="comment_text"><xsl:value-of select="content" disable-output-escaping="yes" /></div>

			<xsl:apply-templates select="subcomments" mode="commentsList" />
			<br/>
		</div>
	</xsl:template>

	<xsl:template match="item" mode="autorComments">
		<xsl:value-of select="document(concat('uobject://',@id,'.lname'))//value" />&#160;
		<xsl:value-of select="document(concat('uobject://',@id,'.fname'))//value" />
		(<xsl:value-of select="document(concat('uobject://',@id,'.login'))//value" />)
	</xsl:template>

	<xsl:template match="result[@module = 'news'][@method = 'rubric']">
		<div >
			<xsl:apply-templates select=".//property[@name = 'readme']/value" />
		</div>
		<xsl:apply-templates select="document(concat('udata://news/lastlents/', /result/page/@id, '/'))/udata">
			<xsl:with-param name="page_id" select="/result/page/@id" />
		</xsl:apply-templates>
		<xsl:apply-templates select="document(concat('udata://news/lastlist/', /result/page/@id, '/'))/udata" />
	</xsl:template>

	<xsl:template match="/result[@module = 'news'][@method = 'rubric']">
		<xsl:apply-templates select="document('udata://news/lastlist')/udata" />
	</xsl:template>

	<xsl:template match="udata[@module = 'news'][@method = 'lastlents']">
		<xsl:param name="page_id" select="'0'" />
		<div class="news_lents"  />
	</xsl:template>

	<xsl:template match="udata[@module = 'news'][@method = 'lastlents'][total]">
		<xsl:param name="page_id" select="'0'" />
		<div class="news_lents" >
			<xsl:apply-templates select="items/item" />
		</div>
	</xsl:template>

	<xsl:template match="udata[@method = 'lastlents']/items/item">
		<a href="/{@link}" >
			<xsl:apply-templates />
		</a>
	</xsl:template>

	<xsl:template match="udata[@method = 'lastlist']">
		<dl class="news" >
			<xsl:apply-templates select="items/item" mode="news-list" />
		</dl>
		<xsl:apply-templates select="total" />
	</xsl:template>


	<xsl:template match="item" mode="news-list">
		<xsl:variable name="item-info" select="document(concat('upage://', @id))" />

		<div>
			<dt>
				<div class="date">
					<xsl:apply-templates select="$item-info//property[@name = 'publish_time']" />
				</div>

				<a href="/{@link}" >
					<xsl:value-of select="." />
				</a>
			</dt>
			<dd >
				<xsl:value-of select="$item-info//property[@name = 'anons']/value" disable-output-escaping="yes" />
			</dd>
		</div>
	</xsl:template>

	<xsl:template match="/result[@module = 'news'][@method = 'item']">
		<div >
			<xsl:value-of select=".//property[@name = 'content']/value" disable-output-escaping="yes" />
		</div>

		<xsl:apply-templates select="document('udata://news/related_links')/udata" />

		<a href="../">
			<xsl:text>Назад к списку новостей</xsl:text>
		</a>
	</xsl:template>

	<xsl:template match="udata[@method = 'related_links']" />

	<xsl:template match="udata[@method = 'related_links' and count(items/item)]">
		<h4>
			<xsl:text>Похожие новости</xsl:text>
		</h4>

		<ul>
			<xsl:apply-templates select="items/item" mode="related" />
		</ul>
	</xsl:template>

	<xsl:template match="item" mode="related">
		<li >
			<a href="/{@link}">
				<xsl:value-of select="." />
			</a>
		</li>
	</xsl:template>
</xsl:stylesheet>