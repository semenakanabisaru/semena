<?php
$FORMS = array();
	
$FORMS['price_block'] = <<<END

<h5>Вывод цены</h5>
%price-original%
%price-actual%

%emarket discountInfo(%discount_id%)%

%currency-prices%


END;

$FORMS['price_original'] = <<<END
<p>
	%value%
</p>
END;

$FORMS['price_actual'] = <<<END
<p>
	%value%
</p>
END;


$FORMS['order_block'] = <<<END
<h4>Информация о покупателе</h4>
%emarket getCustomerInfo()%

<h4>Список покупок</h4>
<table>
	<thead>
		<tr>
			<th>Сорт</th>
			<th>Наименования</th>
			<!-- <th>Дополнительные опции</th> -->
			<th>Количество</th>
			<th>Цена за ед.</th>
			<th>Цена</th>
			<th>Скидки</th>
		</tr>
	</thead>
	<tbody>
		%items%
	</tbody>
</table>

<h4>Скидка на заказ</h4>
%emarket discountInfo(%discount_id%)%

<h3>Доставка</h3>
%delivery-price%

<h4>Суммари (total prices)</h4>
%total-price%
<p>
	Товаров в корзине: %total-amount%
</p>


END;

$FORMS['order_item'] = <<<END
<tr>
	<td>
		1 %item_link%

	</td>
	
	<td>
		<a href="%link%">%name%</a>
	</td>
	
	<!--<td>
		%options%
	</td> -->
	
	<td>
		%amount%
	</td>
	
	<td>
		%price%
	</td>
	
	<td>
		%total-price%
	</td>

	<td>
		%emarket discountInfo(%discount_id%)%
	</td>
</tr>
END;

$FORMS['options_block'] = <<<END
	%items%
END;

$FORMS['options_block_empty'] = "---";

$FORMS['options_item'] = <<<END
%name% +%price%%list-comma%
END;

$FORMS['order_block_empty'] = <<<END
<p>Корзина пуста</p>
END;


$FORMS['item_link'] = <<<END
2

END;

$FORMS['purchase'] = <<<END
%purchasing%

%emarket ordersList()%
END;


$FORMS['orders_block'] = <<<END
<p>Список ваших заказов:</p>
<ul>
	%items%
</ul>
END;

$FORMS['orders_block_empty'] = <<<END
<p>Заказов нет</p>
END;

$FORMS['orders_item'] = <<<END
	<li>%name% (%id%)</li>
END;

$FORMS['purchase_successful'] = <<<END
<p>Заказ успешно добавлен</p>
END;

$FORMS['purchase_failed'] = <<<END
<p>Не удалось добавить заказ</p>
END;


$FORMS['personal'] = <<<END
	%emarket ordersList()%
END;

?>