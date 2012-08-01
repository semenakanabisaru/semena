<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:xlink="http://www.w3.org/TR/xlink">
	<xsl:template match="/result[@method = 'order_list']/data[@type = 'list' and @action = 'view']">
        <script type="text/javascript">
            $(document).ready(function() {
                var $num_span = $('#num_selected');
                var $num_selected_strong = $num_span.parent();
                var $checkboxes = $('.orders_checkbox');
                var num_selected = $checkboxes.filter(':checked').length;
                num_selected += $checkboxes.filter('.payment882:checked').length;
                var $get_package_labels_button = $('#get_package_labels_button');
                var $select_all = $('#select_all');

                if (num_selected > 12) {
                    $num_selected_strong.css('color', 'red');
                    $get_package_labels_button.attr('disabled', true);
                }

                $num_span.html(num_selected);

                $checkboxes.click(function() {
                    if ($(this).is(':checked')) {
                        if( $(this).hasClass('payment882')) {
                            num_selected += 2;
                        } else {
                            num_selected++;
                        }
                        if (num_selected > 12) {
                            $num_selected_strong.css('color', 'red');
                            $get_package_labels_button.attr('disabled', true);
                        }

                        if ($checkboxes.filter(':checked').length === $checkboxes.length) {
                            $select_all.attr('checked', true);
                        }
                    } else {
                        $select_all.attr('checked', false);

                        if( $(this).hasClass('payment882')) {
                            num_selected -= 2;
                        } else {
                            num_selected--;
                        }

                        if (num_selected &lt; 13) {
                            $num_selected_strong.css('color', 'black');
                            $get_package_labels_button.attr('disabled', false);
                        }
                    }

                    $num_span.html(num_selected);
                });
                    
                $('#clear_checkboxes').click(function(e) {
                    $select_all.attr('checked', false);
                    e.preventDefault();
                    num_selected = 0;
                    $num_span.html(num_selected);
                    $num_selected_strong.css('color', 'black');
                    $checkboxes.attr('checked', false); 
                    $get_package_labels_button.attr('disabled', false);
                 });
                    
                 $('#get_shipment_blank_button').click(function(e) {
                    e.preventDefault();
                    $orders_form = $('#orders_form');
                    $orders_form.attr('action', '/admin/doc_generator/get_shipment_blank/');
                    $orders_form.submit();
                    $orders_form.attr('action', '/admin/doc_generator/get_package_labels/');
                 });
                    
                 $select_all.click(function() {
                    if ($(this).is(':checked')) {
                        $checkboxes.attr('checked', true); 
                        num_selected = $checkboxes.filter(':checked').length;
                        num_selected += $checkboxes.filter('.payment882:checked').length;
                        $num_span.html(num_selected);
                        if (num_selected > 12) {
                            $num_selected_strong.css('color', 'red');
                            $get_package_labels_button.attr('disabled', true);
                        } else {
                            $num_selected_strong.css('color', 'black');
                        }
                    } else {
                        num_selected = 0;
                        $num_span.html(num_selected);
                        $num_selected_strong.css('color', 'black');
                        $checkboxes.attr('checked', false); 
                        $get_package_labels_button.attr('disabled', false);
                    }
                 });
            });
        </script>

        <form method="post" id="orders_form" action="/admin/doc_generator/get_package_labels/" target="_blank"> 
            <div style="border: 1px solid #e4e4e4; padding: 5px 10px 5px 10px; margin: 0px 20px 0px 0px; float: left;">
                <button id="clear_checkboxes">Сбросить</button>
            </div>

            <div style="width: 314px; border: 1px solid #e4e4e4; padding: 5px 10px 5px 10px; margin: 0px 20px 0px 0px; float: left;">
                <input type="submit" id="get_package_labels_button" value="Сгенерировать наклейки"/>
                <strong id="num_selected_strong" style="margin-left: 25px;">Наклейки <span id="num_selected">0</span> из 12</strong>
            </div>

            <div style="border: 1px solid #e4e4e4; padding: 5px 10px 5px 10px; float: left;">
                <input id="get_shipment_blank_button" type="submit" value="Сгенерировать список на компановку"/>
            </div>

            <div style="width: 100$; height: 1px; clear: both;"></div>
            <table class="tableLists" style="margin-top: 10px;">
                <thead>
                    <tr class="header-row">
                        <th><input style="height: 12px; width: 12px;" type="checkbox" id="select_all" name="select_all" value="1" /></th>
                        <th>Дата и время</th>
                        <th>Номер заказа</th>
                        <th>Статус заказа</th>
                        <th>Комментарий</th>
                        <th>Способ оплаты</th>
                        <th>Стоимость (без скидки)</th>
                        <th>Покупатель</th>
                    </tr>
                </thead>
                <tbody>
                    <xsl:apply-templates select="/result/data/object[@type-id=79]"/>
                </tbody>
            </table>
        </form>
	</xsl:template>

    <xsl:template match="/result/data/object[@type-id=79]">
        <tr>
            <xsl:apply-templates select="document(concat('uobject://', @id))/udata" />
        </tr>
    </xsl:template>

    <xsl:template match="udata">
        <xsl:variable name="payment_id" select="object/properties/group[@name='order_payment_props']/property[@name='payment_id']/value/item/@id" />

        <td><input style="height: 12px; width: 12px;" type="checkbox" class="orders_checkbox payment{$payment_id}" name="orders[]" value="{object/@id}" /></td>
        <td><xsl:value-of select="object/properties/group[@name='order_props']/property[@name='order_date']/value/@formatted-date"/></td>
        <td><xsl:value-of select="object/properties/group[@name='order_props']/property[@name='number']/value"/></td>
        <td><xsl:value-of select="object/properties/group[@name='order_props']/property[@name='status_id']/value/item/@name"/>
            (<xsl:value-of select="object/properties/group[@name='order_props']/property[@name='status_change_date']/value/@formatted-date"/>)
        </td>
        <td><xsl:value-of select="object/properties/group[@name='dopolnitelno']/property[@name='kommentarij']/value"/></td>
        <td class="payment_type">
            <xsl:value-of select="object/properties/group[@name='order_payment_props']/property[@name='payment_id']/value/item/@name"/>
            <xsl:if test="$payment_id = 882">
                (<a href="/admin/doc_generator/get_cash_on_delivery_blank/{object/@id}" target="_blank">Бланк</a>)
            </xsl:if>
        </td>
        <td><xsl:value-of select="object/properties/group[@name='order_props']/property[@name='total_original_price']/value"/></td>
        <td><xsl:value-of select="object/properties/group[@name='order_props']/property[@name='customer_id']/value/item/@name"/></td>
        <td></td>
    </xsl:template>
</xsl:stylesheet>
