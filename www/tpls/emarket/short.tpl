<?php
$FORMS = array();
	
$FORMS['price_block'] = <<<END
%price-original%
%price-actual%
END;

$FORMS['price_original'] = <<<END
<!--<strike>%prefix% %original% %suffix%</strike>->>
%original%

END;

$FORMS['price_actual'] = <<<END
<!--%prefix% %actual% %suffix%-->
%actual%

END;
?>