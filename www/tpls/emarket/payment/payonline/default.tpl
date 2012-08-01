<?php
$FORMS = Array();

$FORMS['form_block'] = <<<END

<form action="%formAction%" method="post">
	
	<input type="hidden" name="MerchantId" 	value="%MerchantId%" />
	<input type="hidden" name="OrderId" 	value="%OrderId%" />
	<input type="hidden" name="Currency" 	value="%Currency%" />
	<input type="hidden" name="SecurityKey" value="%SecurityKey%" />
	<input type="hidden" name="ReturnUrl" 	value="%ReturnUrl%" />
	<input type="hidden" name="Amount" value="%Amount%" />
	<!-- NB! This field should exist for proper system working -->
	<input type="hidden" name="order-id"    value="%orderId%" />
	
	<p>
		Нажмите кнопку "Оплатить" для перехода на сайт платежной системы <strong>PayOnline</strong>.
	</p>        

	<p>
		<input type="submit" value="Оплатить" />
	</p>
</form>
END;

?>