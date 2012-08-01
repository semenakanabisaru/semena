<?php
$FORMS = Array();

$FORMS['form_block'] = <<<END

<form action="%formAction%" method="post">

	<input type="hidden" name="MNT_ID" value="%mntId%" />
	<input type="hidden" name="MNT_TRANSACTION_ID" value="%mnTransactionId%" />
	<input type="hidden" name="MNT_CURRENCY_CODE" value="%mntCurrencyCode%" />
	<input type="hidden" name="MNT_AMOUNT" value="%mntAmount%" />
	<input type="hidden" name="MNT_TEST_MODE" value="%mntTestMode%" />
	<input type="hidden" name="MNT_SIGNATURE" value="%mntSignature%" />
	<input type="hidden" name="MNT_SUCCESS_URL" value="%mntSuccessUrl%" />
	<input type="hidden" name="MNT_FAIL_URL" value="%mntFailUrl%" />

	<p>
		Нажмите кнопку "Оплатить" для перехода на сайт платежной системы <strong>PayAnyWay</strong>.
	</p>

	<p>
		<input type="submit" value="Оплатить" />
	</p>
</form>
END;

?>