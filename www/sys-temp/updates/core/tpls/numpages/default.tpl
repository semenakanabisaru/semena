<?php
	$FORMS = Array();

	$FORMS['pages_block'] = <<<END
	<div>
		<div class="small">Страницы:&nbsp;&nbsp;%pages%</div>
		
	</div>

END;



	$FORMS['pages_item'] = <<<END
	<a href="%link%"><b>%num%</b></a>&nbsp;%quant%
END;

	$FORMS['pages_item_a'] = <<<END
	<span>%num%</span>&nbsp;%quant%
END;

	$FORMS['pages_quant'] = <<<END
|
END;

	$FORMS['pages_block_empty'] = "";
?>