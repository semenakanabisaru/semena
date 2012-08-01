<?php
	abstract class __tags_cloud_stat extends baseModuleAdmin {

		public function tagsCloud($template = "default", $limit = 50, $max_font_size = 16) {
			list($template_block, $template_line, $template_separator) = def_module::loadTemplates("stat/".$template, "tags_block", "tags_block_line", "tags_separator");

			$factory = new statisticFactory(dirname(__FILE__) . '/classes');
			$factory->isValid('allTags');
			$report = $factory->get('allTags');
			$report->setStart(0);
            $report->setFinish(strtotime("+1 day", time()));			
			
			$result = $report->get();
			$max = $result['max'];

			$lines = Array();

			$i = 0;
			$sz = sizeof($result['labels']);
			for($i = 0; $i < min($sz, $limit); $i++) {
				$label = $result['labels'][$i];
				$line_arr = Array();

				$tag = $label['tag'];
				$cnt = $label['cnt'];

				$fontSize = ceil($max_font_size * ($cnt / $max));

				$line_arr['node:tag'] = $tag;
				$line_arr['attribute:cnt'] = $cnt;
				$line_arr['attribute:font-size'] = $fontSize;
				$line_arr['void:separator'] = ($i < $sz - 1) ? $template_separator : "";
				$line_arr['void:font_size'] = $fontSize;

				$lines[] = def_module::parseTemplate($template_line, $line_arr);
			}
			
			$block_arr = Array();
			$block_arr['subnodes:lines'] = $lines;
			$block_arr['total'] = $sz;
			$block_arr['per_page'] = $limit;
			return def_module::parseTemplate($template_block, $block_arr);
		}
	};

?>