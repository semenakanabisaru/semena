<?php
/*
*/

class __tags_mk_cloud {
	/*
	*/

	public function tags_mk_cloud($i_domain_id = NULL, $s_template = "tags", $i_per_page = -1, $b_ignore_paging = true, $b_by_usage = false, $arr_users = array()) {
		// init and context :
		$s_tpl_tags = "cloud_tags";
		$s_tpl_tag = "cloud_tag";
		$s_tpl_tag_sep = "cloud_tagseparator";
		$s_tpl_tags_empty = "cloud_tags_empty";

		// validate input :

		if (!$arr_users || intval($arr_users) === -1 || strval($arr_users) === 'all' || $arr_users == "Все") {
			$arr_users = array();
		}
		if (is_int($arr_users)) {
			$arr_users = array(intval($arr_users));
		} elseif (is_array($arr_users)) {
			$arr_users = array_map('intval', $arr_users);
		} else {
			$arr_users = array(intval(strval($arr_users)));
		}

		$i_per_page = intval($i_per_page);
		if (!$i_per_page) $i_per_page = 10;
		if ($i_per_page === -1) $b_ignore_paging = true;

		$s_template = strval($s_template);
		if (!strlen($s_template)) $s_template = "tags";

		$i_curr_page = intval(getRequest('p'));
		if ($b_ignore_paging) $i_curr_page = 0;

		// load templates :
		list(
			$tpl_tags, $tpl_tag, $tpl_tag_sep, $tpl_tags_empty
		) = $this->loadTemplates("content/".$s_template,
			$s_tpl_tags, $s_tpl_tag, $s_tpl_tag_sep, $s_tpl_tags_empty
		);
		// process :

		$max_font_size = 32;
		$min_font_size = 10;
		//
		$s_prefix = '';
		//
		if ($b_by_usage) {

			$o_object_type = umiObjectTypesCollection::getInstance()->getTypeByGUID('root-pages-type');
			$i_tags_field_id = $o_object_type->getFieldId('tags');
			//
			$result = umiObjectProperty::objectsByValue($i_tags_field_id, 'all', true, true, ($i_domain_id ? $i_domain_id : -1));

		} else {
			$stat = cmsController::getInstance()->getModule('stat');

			$sStatIncPath = dirname(dirname(dirname(dirname(__FILE__)))) . '/stat/classes';

			if(class_exists("statisticFactory") == false) {
				return;
			}

			$factory = new statisticFactory($sStatIncPath);

			$factory->isValid('allTags');
			$report = $factory->get('allTags');

			if ($i_domain_id) {
				$s_prefix = 'Domain';
				$v_domains = $report->setDomain($i_domain_id);
			} else {
				$s_prefix = 'Account';
				$v_domains = $report->setDomain(-1);
			}

			if (is_array($arr_users) && count($arr_users)) {
				$report->setUserIDs($arr_users);
			}

			$result = $report->get();
		}

		if(isset($result['values']) && is_array($result['values'])) {
			natsort2d($result['values'], "cnt");
			$result['values'] = array_slice($result['values'], -$i_per_page, $i_per_page);
			natsort2d($result['values'], "value");
		}

		$max = intval($result['max']);
		$sum = intval($result['sum']);

		$arrTags = array();

		$s_values_label = ($b_by_usage ? 'values' : 'labels');
		$s_value_label = ($b_by_usage ? 'value' : 'tag');
		$s_value_cnt = 'cnt';

		$sz = sizeof($result[$s_values_label]);
		for ($i = 0; $i < $sz; $i++) {
			$label = $result[$s_values_label][$i];
			$tag = $label[$s_value_label];
			if (is_null($tag)) continue; //$tag = '[nontagged]';
			$cnt = intval($label[$s_value_cnt]);
			$f_weight = round($cnt * 100 / $sum, 1);
			$font_size = round(((($max_font_size - $min_font_size)/100) * $f_weight) + $min_font_size);
			$arrTags[$tag] = array('weight' => $f_weight, 'font' => $font_size);
		}
		//
		$summ_weight = 0;
		if (count($arrTags)) {
			$arrTagsTplteds = array();
			foreach ($arrTags as $sTag => $arrTagStat) {
				$summ_weight += $arrTagStat['weight'];
				$params = array(
					'tag'=>$sTag,
					'tag_urlencoded'=>rawurlencode($sTag),
					'attribute:weight' => $arrTagStat['weight'],
					'attribute:font' => $arrTagStat['font'],
					'attribute:context' => $s_prefix
				);
				$arrTagsTplteds[] = def_module::parseTemplate($tpl_tag, $params);
			}

			if (isset($arrTagsTplteds[0]) && is_array($arrTagsTplteds[0])) { // udata
				$arrForTags = array('subnodes:items'=>$arrTagsTplteds);
			} else { // not udata
				$arrForTags = array('items'=>implode($tpl_tag_sep, $arrTagsTplteds));
			}
			//
			$arrForTags['attribute:summ_weight'] = ceil($summ_weight);
			$arrForTags['attribute:context'] = $s_prefix;
			// RETURN
			return def_module::parseTemplate($tpl_tags, $arrForTags);
		} else {
			$arrForTags = array();
			// RETURN
			return def_module::parseTemplate($tpl_tags_empty, $arrForTags);
		}

	}
}

?>