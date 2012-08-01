<?php
	abstract class __admin_tags_cloud_stat extends baseModuleAdmin {

		public function tags_cloud() {
			$max_font_size = 28;
			$min_font_size = 8;

			$factory = new statisticFactory(dirname(__FILE__) . '/classes');
			$factory->isValid('allTags');
			$report = $factory->get('allTags');
			
			$report->setDomain($this->domain); 
            $report->setUser($this->user);
            $report->setStart($this->from_time);
            $report->setFinish($this->to_time);            

			$result = $report->get();
			$max = $result['max'];
			$sum = $result['sum'];

			$lines = Array();

			$sz = sizeof($result['labels']);
			for($i = 0; $i < $sz; $i++) {
				$label = $result['labels'][$i];

				$id  = $label['id'];
                $tag = $label['tag'];
				$cnt = $label['cnt'];
				$font_size = ceil(($max_font_size - $min_font_size) * ($cnt / $max)) + $min_font_size;
				$proc = round($cnt * 100 / $sum, 1);

				$lines[] = array('attribute:id' => $id,
                                 'attribute:weight' => $proc, 
                                 'attribute:fontweight' =>$font_size, 
                                 'node:name' => $tag);                
			}
			return (!empty($lines)) ? array('nodes:tag' => $lines) : 
                                      array('nodes:message' => array( array('node:name' => getLabel('message-no-tags')) ) );
		}


		public function get_tags_cloud() {
			$id = getRequest('param0');
            $existing_tags = isset($_GET['exist']) ? explode(',', $_GET['exist']) : false;
            if($existing_tags !== false) array_walk($existing_tags, 'trim');            

			$max_font_size = 18;
			$min_font_size = 6;

			$tagFieldId = umiObjectTypesCollection::getInstance()->getTypeByGUID('root-pages-type')->getFieldId('tags');

			$sql = <<<SQL
SELECT varchar_val AS `tag`, COUNT(*) AS `cnt` FROM cms3_object_content WHERE field_id = {$tagFieldId} AND varchar_val IS NOT NULL GROUP BY varchar_val;
SQL;
			$sql_result = l_mysql_query($sql);

			$result = Array();
			$max = 0;
			$sum = 0;

			while($row = mysql_fetch_assoc($sql_result)) {
				$result[] = $row;
				$sum += $row['cnt'];

				if($row['cnt'] > $max) {
					$max = $row['cnt'];
				}
			}

			$lines = Array();
            
			$sz = sizeof($result);
			for($i = 0; $i < $sz; $i++) {
				$label = $result[$i];

				$tag = $label['tag'];                                     
				$cnt = $label['cnt'];
				$font_size = ceil(($max_font_size - $min_font_size) * ($cnt / $max)) + $min_font_size;
				$proc  = round($cnt * 100 / $sum, 1);                                
				$lines[] = "<a href=\"javascript:void(0);\" name=\"{$id}_tag_list_item\" onclick=\"javascript: return window.parent.returnNewTag('{$id}', '{$tag}', this);\" style=\"font-size: {$font_size}pt;\">{$tag}</a>";
			}  
			$res = implode(", ", $lines);
			$exitButton = getLabel ('button-tag-cloud-exit');
			header("Content-type: text/html; charset=utf-8");

			$res = <<<HTML
<html>
<head>
<style>
a {
	text-decoration: none;
	color: #0088e8;
}

a.disabledTag {
	text-decoration: none;
	color: #676767;
}

select, input, button { font: 11px Tahoma,Verdana,sans-serif; }
button { width: 70px; }


#buttons {
    margin-top: 1em; border-top: 1px solid #999;
    padding: 2px; text-align: right;
}
</style>

<script>
	function onExit() {
		window.parent.focusTagsInput('{$id}');
		window.parent.Windows.closeAll();
		return false;
	}
    function onLoad() {
        var aTags   = document.getElementsByTagName('a'); 
        var sExTags = window.parent.document.getElementById('{$id}').value;
        for(i=0; i<aTags.length; i++) { 
            if(aTags[i].getAttribute('name') == '{$id}_tag_list_item') { 
                var sTagText = "";
                if(aTags[i].text) sTagText = aTags[i].text;
                else              sTagText = aTags[i].innerText;
                if(sExTags.lastIndexOf(sTagText) != -1) {
                    aTags[i].className = 'disabledTag';
                }
            }
        }
    }
</script>
</head>
<body onload="onLoad()">
<table width="100%" height="100%" border="0">
<tr><td valign="middle" align="center">{$res}</td></tr>

<tr><td>
<!--div id="buttons">
	<button type="button" name="cancel" onclick="return onExit();">{$exitButton}</button>
</div-->
</td></tr>
</table>
</body>
</html>
HTML;

			$this->flush($res);			
		}

	};
?>
