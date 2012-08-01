<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://i18n/constants.dtd:file">

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

	<xsl:output encoding="utf-8" method="html" indent="yes" />

	<xsl:template match="status_notification">
		<xsl:text>Ваш заказ #</xsl:text>
		<xsl:value-of select="order_number" />
		<xsl:text> </xsl:text>
		<xsl:value-of select="status" />
		<br/><br/>
		<xsl:text>Посмотреть историю заказов вы можете в своем </xsl:text>
		<a href="http://{domain}/emarket/personal/">
			<xsl:text>личном кабинете</xsl:text>
		</a>.
	</xsl:template>

	<xsl:template match="status_notification_receipt">
		<xsl:text>Ваш заказ #</xsl:text>
		<xsl:value-of select="order_number" />
		<xsl:text> </xsl:text>
		<xsl:value-of select="status" />
		<br/><br/>
		<xsl:text>Посмотреть историю заказов вы можете в своем </xsl:text>
		<a href="http://{domain}/emarket/personal/">
			<xsl:text>личном кабинете</xsl:text>
		</a>.
		<br/><br/>
		<xsl:text>Квитанцию на оплату вы можете получить, перейдя по </xsl:text>
		<a href="http://{domain}/emarket/receipt/{order_id}/{receipt_signature}/">
			<xsl:text>этой ссылке</xsl:text>
		</a>.
	</xsl:template>

	<xsl:template match="neworder_notification">
		<xsl:text>Поступил новый заказ #</xsl:text>
		<xsl:value-of select="order_number" />
		<xsl:text> (</xsl:text>
		<a href="http://{domain}/admin/emarket/order_edit/{order_id}/">
			<xsl:text>Просмотр</xsl:text>
		</a>
		<xsl:text>)</xsl:text><br/><br/>
		<xsl:text>Способ оплаты: </xsl:text>
		<xsl:value-of select="payment_type" /><br/>
		<xsl:text>Статус оплаты: </xsl:text>
		<xsl:value-of select="payment_status" /><br/>
		<xsl:text>Сумма оплаты:  </xsl:text>
		<xsl:value-of select="price" /><br/>
	</xsl:template>

</xsl:stylesheet>