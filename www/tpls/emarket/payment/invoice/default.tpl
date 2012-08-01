<?php

$FORMS = Array();

$FORMS['legal_person_block'] = <<<END

<form method="post" action="do" id="invoice">
	Выберите юридическое лицо:
	<ul>
		%items%
		<li>
			<input type="radio" name="legal-person" value="new" />Новое юридическое лицо
			<div>
			%data getCreateForm(%type_id%)%
			</div>
		</li>
	</ul>
<div><input type="submit" value="Выписать счет" class="button big" /></div>

</form>

END;

$FORMS['legal_person_item'] = <<<END
<li>
<li><input type="radio" name="legal-person" value="%id%" />%name%</li>
</li>
END;

?>