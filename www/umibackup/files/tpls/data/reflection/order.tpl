<?php

$FORMS = Array();

$FORMS['reflection_block'] = <<<END


<pre>
%groups%
</pre>
END;

$FORMS['reflection_group'] = <<<END

<hr />
<b>%title% (%name%)</b><br />

<table border="0" width="500">
	%fields%
</table>


END;


$FORMS['reflection_field_string'] = <<<END

	<tr>
		<td>
			%title%:
		</td>

		<td>
			%value%"
		</td>
	</tr>

END;


$FORMS['reflection_field_text'] = <<<END

	<tr>
		<td>
			%title%:
		</td>

		<td>
			%value%
		</td>
	</tr>

END;


$FORMS['reflection_field_wysiwyg'] = <<<END

	<tr>
		<td>
			%title%:
		</td>

		<td>
			%value%
		</td>
	</tr>

END;


$FORMS['reflection_field_int'] = <<<END

	<tr>
		<td>
			%title%:
		</td>

		<td>
			%value%
		</td>
	</tr>

END;


$FORMS['reflection_field_boolean'] = <<<END

	<tr>
		<td>
			%title%:
		</td>

		<td>
			%value%" />
			
		</td>
	</tr>

END;


$FORMS['reflection_field_password'] = <<<END

	<tr>
		<td>
			%title%:
		</td>

		<td>
			<input type="password" name="%input_name%" value="" size="50" />
		</td>
	</tr>

	<tr>
		<td>
			Подтверждение:
		</td>

		<td>
			<input type="password" name="%input_name%" value="" size="50" />
		</td>
	</tr>

END;


$FORMS['reflection_field_relation'] = <<<END
	<tr>
		<td>
			%title%:
		</td>

		<td>
			%value%
		</td>
	</tr>

END;

$FORMS['reflection_field_img_file'] = <<<END

	<tr>
		<td>
			%title%:
		</td>

		<td>
			<table width="100%">
				<tr>
					<td>
						<input type="file" name="%input_name%" />
					</td>

					<td>
						%data getPropertyOfObject(%object_id%, '%name%', 'avatar')%
					</td>
				</tr>
			</table>
		</td>
	</tr>


END;

?>