<?php

$FORMS = array();

$FORMS['scope_dump_block'] = <<<END
<div name="scopeDump" style="border:1px solid #999; margin : 5px; padding : 0; overflow : hidden">
	<div style="padding : 10px 5px 0px 5px; font-weight: bold; font-size:120%; background: #dbdbdb">%block_name%</div>
	<div style="padding : 0px 5px 10px 5px; font-size: 90%; border-bottom: 1px solid #999; background: #dbdbdb" title="%block_file%">%block_file%</div>
	<ul style="margin : 0; padding : 10px 5px 5px; white-space:nowrap;">
		%lines%		
	</ul>
</div>
END;

$FORMS['scope_dump_line_variable'] = <<<END
		<li style="list-style-type: none;">
			&#37;<span style="font-weight: bold;">%name%</span>&#37;
			<span>&nbsp;=&nbsp;</span>
			<span style="font-style:italic; color : green">(%type%)</span>
			<span title="%value%">%value%</span>
		</li>
END;

$FORMS['scope_dump_line_macro'] = <<<END
		<li style="list-style-type: none;">
			&#37;<span style="font-weight: bold;">%name%</span>&#37;			
			<span style="font-style:italic; color: red">&nbsp;macro</span>			
		</li>
END

?>