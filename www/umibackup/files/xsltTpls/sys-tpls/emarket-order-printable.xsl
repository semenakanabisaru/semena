<?xml version="1.0" encoding="utf-8"?>

<xsl:stylesheet	version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output encoding="utf-8" method="html" indent="yes"/>

	<xsl:template match="/">
		<xsl:apply-templates />
	</xsl:template>
	
	<xsl:template match="/udata/object">
		<xsl:variable name="order-info" select="document(concat('udata://emarket/order/',@id))/udata" />
		<xsl:variable name="order" select="properties/group[@name='order_props']" />
		<xsl:variable name="payment" select="properties/group[@name='order_payment_props']" />
		<xsl:variable name="delivery" select="properties/group[@name='order_delivery_props']" />
		<xsl:variable name="delivery-address" select="document(concat('uobject://', $delivery/property[@name='delivery_address']/value/item[1]/@id))/udata/object/properties/group" />
	
		<html>
			<head>
				<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
				<title><xsl:value-of select="@name" /></title>
				<style>					
					body {
						font-family : Trebuchet MS, Tahoma;						
					}
					table#content {
						width : 190mm;
						font-size : 3.5mm;
					}
					tr.add-space td {
						padding : 5mm 0 5mm 0;
					}
					td.title {
						
					}
					td.value, span.value {
						font-weight : bold;					
					}					
					table#items, table#items tr {
						padding : 0;
						margin : 0;
					}
					table#items {
						font-size : 3.5mm;
						border-top : 1px solid black;
						border-left : 1px solid black;
					}
					table#items td {
						border-right : 1px solid black;
						border-bottom : 1px solid black;
						padding : 2mm;						
					}
				</style>
			</head>
			<body>
				<table id="content">
					<tr class="add-space">
						<td colspan="4">
							<xsl:value-of select="@name" />
							<xsl:text> от </xsl:text>
							<xsl:value-of select="document(concat('udata://system/convertDate/',$order/property[@name='order_date']/value/@unix-timestamp,'/Y-m-d%20H:i:s'))/udata" />
							<xsl:text> с сайта </xsl:text>
							<xsl:value-of select="$order/property[@name='domain_id']/value" />
						</td>
					</tr>
					<tr>
						<td class="title">
							Статус:
						</td>
						<td class="value">
							<xsl:value-of select="$order/property[@name='status_id']/value/item[1]/@name" />
						</td>
						<td class="title">
							Менеджер:
						</td>
						<td class="value">
							<xsl:apply-templates select="$order/property[@name='manager_id']/value/item[1]" mode="manager" />
						</td>
					</tr>
					<tr>
						<td class="title">
							Способ оплаты:
						</td>
						<td class="value">
							<xsl:value-of select="$payment/property[@name='payment_id']/value/item[1]/@name" />
						</td>
						<td class="title">
							Статус оплаты:
						</td>
						<td class="value">
							<xsl:value-of select="$payment/property[@name='payment_status_id']/value/item[1]/@name" />
						</td>
					</tr>
					<tr>
						<td class="title">
							Дата оплаты:
						</td>
						<td class="value">
							<xsl:value-of select="document(concat('udata://system/convertDate/',$payment/property[@name='payment_date']/value/@unix-timestamp,'/Y-m-d'))/udata" />
						</td>
						<td class="title">
							Номер документа:
						</td>
						<td class="value">
							<xsl:value-of select="$payment/property[@name='payment_document_num']/value" />
						</td>
					</tr>
					<tr class="add-space">
						<td class="title">
							Способ доставки:
						</td>
						<td class="value">
							<xsl:value-of select="$delivery/property[@name='delivery_id']/value/item[1]/@name" />
						</td>
						<td class="title">
							Статус доставки:
						</td>
						<td class="value">							
							<xsl:value-of select="$delivery/property[@name='delivery_status_id']/value/item[1]/@name" />
						</td>
					</tr>
					<tr>
						<td class="title">
							Страна:
						</td>
						<td class="value">
							<xsl:value-of select="$delivery-address/property[@name='country']/value/item[1]/@name" />
						</td>
						<td class="title">
							Дата разрешения доставки:
						</td>						
						<td class="value">
							<xsl:value-of select="document(concat('udata://system/convertDate/',$delivery/property[@name='delivery_allow_date']/value/@unix-timestamp,'/Y-m-d'))/udata" />
						</td>
					</tr>
					<tr>
						<td class="title">
							Регион:
						</td>
						<td class="value">
							<xsl:value-of select="$delivery-address/property[@name='region']/value" />
						</td>
						<td class="title">
							Стоимость доставки:
						</td>
						<td class="value">
							<xsl:value-of select="$order-info/summary/price/@prefix" />
							<xsl:value-of select="$delivery/property[@name='delivery_price']/value" />
							<xsl:text>&#160;</xsl:text>
							<xsl:value-of select="$order-info/summary/price/@suffix" />
						</td>
					</tr>
					<tr>
						<td class="title">
							Город:
						</td>
						<td class="value">
							<xsl:value-of select="$delivery-address/property[@name='city']/value" />
						</td>						
					</tr>
					<tr>
						<td class="title">
							Улица:
						</td>
						<td class="value">
							<xsl:value-of select="$delivery-address/property[@name='street']/value" />
						</td>						
					</tr>
					<tr>
						<td class="title">
							Дом:
						</td>
						<td class="value">
							<xsl:value-of select="$delivery-address/property[@name='house']/value" />
						</td>						
					</tr>
					<tr>
						<td class="title">
							Квартира:
						</td>
						<td class="value">
							<xsl:value-of select="$delivery-address/property[@name='flat']/value" />
						</td>						
					</tr>
					<xsl:apply-templates select="$order/property[@name='customer_id']/value/item[1]" mode="customer" />
					<xsl:apply-templates select="." mode="items">
						<xsl:with-param name="order-info" select="$order-info" />
					</xsl:apply-templates>
				</table>
			</body>
		</html>
	</xsl:template>
	
	<xsl:template match="item" mode="manager">	
		<xsl:variable name="info" select="document(concat('uobject://', @id))/udata/object/properties/group[@name='short_info']" />
		
		<xsl:value-of select="$info/property[@name='fname']/value" />
		<xsl:text> </xsl:text>
		<xsl:value-of select="$info/property[@name='lname']/value" />
	</xsl:template>
	
	<xsl:template match="item" mode="customer">
		<tr class="add-space">
			<td colspan="4"><xsl:text>Информация о покупателе:</xsl:text></td>
		</tr>
		<xsl:apply-templates select="document(concat('uobject://', @id))/udata" mode="customer" />	
	</xsl:template>
	
	<xsl:template match="object" mode="customer">				
		<tr>
			<td class="title">
				Логин:
			</td>
			<td class="value">
				<xsl:value-of select="//property[@name='login']/value" />
			</td>
			<td class="title">
				Фамилия:
			</td>
			<td class="value">
				<xsl:value-of select="//property[@name='lname']/value" />
			</td>			
		</tr>
		<tr>
			<td class="title">
				E-mail:
			</td>
			<td class="value">
				<xsl:value-of select="//property[@name='email']/value" />
				<xsl:value-of select="//property[@name='e-mail']/value" />
			</td>
			<td class="title">
				Имя:
			</td>
			<td class="value">
				<xsl:value-of select="//property[@name='fname']/value" />
			</td>
		</tr>
	</xsl:template>
	
	<xsl:template match="object" mode="items">
		<xsl:param name="order-info" select="document(concat('udata://emarket/order/',@id))/udata" />
		<xsl:variable name="suffix" select="$order-info/summary/price/@suffix" />
		<xsl:variable name="prefix" select="$order-info/summary/price/@prefix" />
		
		<tr class="add-space">
			<td colspan="4"><xsl:text>Состав заказа:</xsl:text></td>
		</tr>
		<tr>
			<td colspan="4">
				<table id="items" cellspacing="0">
					<tr>
						<td>Наименование</td>
						<td>Цена</td>
						<td>Скидка</td>
						<td>Цена с учетом скидки</td>
						<td>Количество</td>
						<td>Сумма</td>
					</tr>
					<xsl:apply-templates select="$order-info/items/item" mode="order-item" />
					<tr>
						<td>Доставка</td>
						<td>&#160;</td>
						<td>&#160;</td>
						<td>&#160;</td>
						<td>&#160;</td>
						<td>
							<xsl:value-of select="$prefix" />
							<xsl:value-of select="//property[@name='delivery_price']/value" />
							<xsl:text>&#160;</xsl:text>
							<xsl:value-of select="$suffix" />
						</td>
					</tr>
					<tr>
						<td>Итого:</td>
						<td>&#160;</td>
						<td>&#160;</td>
						<td>&#160;</td>
						<td>&#160;</td>
						<td>
							<xsl:value-of select="$prefix" />
							<xsl:value-of select="//property[@name='total_price']/value" />
							<xsl:text>&#160;</xsl:text>
							<xsl:value-of select="$suffix" />
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</xsl:template>
	
	<xsl:template match="item" mode="order-item">
		<tr>
			<td>
				<xsl:value-of select="@name" />
			</td>			
			<td>
				<xsl:choose>
					<xsl:when test="price/original &gt; 0">
						<xsl:apply-templates select="price/original" mode="price" />
					</xsl:when>
					<xsl:otherwise>
						<xsl:apply-templates select="price/actual" mode="price" />
					</xsl:otherwise>
				</xsl:choose>
			</td>			
			<td>
				<xsl:choose>
					<xsl:when test="discount">
						<xsl:apply-templates select="discount" />
					</xsl:when>
					<xsl:otherwise>
						<xsl:text>&#160;&#8212;</xsl:text>
					</xsl:otherwise>
				</xsl:choose>				
			</td>			
			<td>
				<xsl:apply-templates select="price/actual" mode="price" />
			</td>
			<td>
				<xsl:apply-templates select="amount" />
			</td>			
			<td>
				<xsl:apply-templates select="total-price/actual" mode="price" />
			</td>
		</tr>		
	</xsl:template>	
		
	
	<xsl:template match="discount">		
		<xsl:apply-templates select="document(concat('uobject://', @id, '.discount_modificator_id'))//item" mode="discount-size" />
	</xsl:template>
	
	<xsl:template match="*" mode="price">
		<xsl:value-of select="." />
	</xsl:template>
	
	<xsl:template match="*[../@prefix]" mode="price">
		<xsl:value-of select="concat(../@prefix, '&#160;', .)" />
	</xsl:template>
	
	<xsl:template match="*[../@suffix]" mode="price">
		<xsl:value-of select="concat(., '&#160;', ../@suffix)" />
	</xsl:template>
	
</xsl:stylesheet>