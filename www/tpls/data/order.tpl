<?php
$FORMS = Array();

$FORMS['groups_block'] = <<<END
<ul>
	%lines%
</ul>
END;

$FORMS['groups_line'] = <<<END
<li>
	%data getPropertyGroup('%id%', '%group_id%', '%template%')%
</li>
END;


$FORMS['group'] = <<<END
[Group], %title% (%name%)
<ul>
    %lines%
</ul>
END;

$FORMS['group_line'] = <<<END
<li>
    %prop%
</li>
END;



$FORMS['int'] = <<<END
%title%: %value%

END;

$FORMS['price'] = <<<END
%title%: %value%
END;


$FORMS['string'] = <<<END
%title%: %value%
END;

$FORMS['text'] = <<<END
%title%: %value%
END;


$FORMS['relation'] = <<<END
%title%: %value%
END;

$FORMS['file'] = <<<END
[File], %title% (%name%)<br />
Filename: %filename%;<br />
Filepath: %filepath%;<br />
Filepath: %src%;<br />
Size: %size%<br />
Extension: %ext%<br />
<a href="%src%">%src%</a>
END;

$FORMS['swf_file'] = $FORMS['img_file'] = <<<END
[Image File], %title% (%name%)<br />
Filename: %filename%;<br />
Filepath: %filepath%;<br />
Filepath: %src%;<br />
Size: %size%<br />
Extension: %ext%<br />
%width% %height%<br />
<img src="%src%" width="%width%" height="%height%" />

END;

$FORMS['date'] = <<<END
%title%: %value%
END;

$FORMS['boolean_yes'] = <<<END
%title%: Да
END;

$FORMS['boolean_no'] = <<<END
%title%: Нет
END;


$FORMS['wysiwyg'] = <<<END
%title%: %value%

END;


/* Multiple property blocks */

$FORMS['relation_mul_block'] = <<<END
%title%: %value%
END;

/* Multiple property item */

$FORMS['relation_mul_item'] = <<<END
%title%: %value%
END;

/* Multiple property quant */
$FORMS['symlink_block'] = <<<END
%title%: %value%
END;

$FORMS['symlink_item'] = <<<END
<a href="%link%">%value%(%id%, %object_id%)</a>%quant%
END;

$FORMS['symlink_quant'] = <<<END
, 
END;


$FORMS['guide_block'] = <<<END
%value%
END;

$FORMS['guide_block_empty'] = <<<END

END;

$FORMS['guide_block_line'] = <<<END
%value%
END;


?>