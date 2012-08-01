<?php

	$FORMS = Array();

	$FORMS['subject'] = 'Оповещение о приближении окончания показа баннеров';
	$FORMS['body'] = <<<END
		У следующих баннеров истекает срок показа:<br />
		%items%
END;

	$FORMS['item'] = <<<END
		%bannerName% %tillDate% Ссылка для редактирования: <a href = "%link%">%link%</a>  <br/>
END;

?>

