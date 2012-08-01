<?php

$FORMS = Array();


$FORMS['menu_block_level1'] = <<<END
			<div id="menu" umi:element-id="%id%" umi:module="content" umi:method="menu">
%lines%
			</div>

END;

$FORMS['menu_line_level1'] = <<<END
				<a href="%link%" umi:element-id="%id%" umi:field-name="name">%text%</a>

END;

$FORMS['menu_line_level1_a'] = <<<END
				<a class="active" href="%link%" umi:element-id="%id%" umi:field-name="name">%text%</a>

END;


?>