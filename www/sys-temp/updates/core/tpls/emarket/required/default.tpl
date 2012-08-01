<?php
$FORMS = array();

$FORMS['required_block'] = <<<END

	<form action="%pre_lang%/emarket/purchase/required/personal/do/" method="post" onsubmit="saveFormData(this); return true;" id="personal">
		<table cellspacing="1" cellpadding="1" width="100%" border="0">
			%data getEditForm(%customer_id%, 'users')%
		</table>
		
		<p>
			<input type="submit" />
		</p>
		
		<script type="text/javascript">
			restoreFormData(document.getElementById('personal'));
		</script>
		
	</form>

END;
?>