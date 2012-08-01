<?php
/*
*/

class __tags_mk_eff_cloud {
	/*
	*/

	public function tags_mk_eff_cloud($i_domain_id = NULL, $s_template = "tags", $i_per_page = -1, $b_ignore_paging = true, $arr_users = array()) {
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
			"cloud_tags", "cloud_tag", "cloud_tagseparator", "cloud_tags_empty"
		);
		// process :

		$max_font_size = 32;
		$min_font_size = 10;

		$s_prefix = 'Account';
		if ($i_domain_id) $s_prefix = 'Domain';

		// by usage :
			$o_object_type = umiObjectTypesCollection::getInstance()->getTypeByGUID('root-pages-type');
			$i_tags_field_id = $o_object_type->getFieldId('tags');
			//
			$result_u = umiObjectProperty::objectsByValue($i_tags_field_id, 'all', true, true, ($i_domain_id ? $i_domain_id : -1));

		// by popularity
			$stat = cmsController::getInstance()->getModule('stat');
			$sStatIncPath = dirname(dirname(dirname(dirname(__FILE__)))) . '/stat/classes';
			$factory = new statisticFactory($sStatIncPath);
			$factory->isValid('allTags');
			$report = $factory->get('allTags');
			if ($i_domain_id) {
				$v_domains = $report->setDomain($i_domain_id);
			} else {
				$v_domains = $report->setDomain(-1);
			}
			if (is_array($arr_users) && count($arr_users)) {
				$report->setUserIDs($arr_users);
			}
			$result_p = $report->get();

		$arrTags = array();

		$i_sum_u = intval($result_u['sum']);
		$i_sum_p = intval($result_p['sum']);
		$arr_usage_tags = $result_u['values'];
		$arr_popular_tags = $result_p['labels'];
		$arr_u_tags = array();
		$arr_p_tags = array();
		$arr_eff_tags = array();

		foreach ($arr_usage_tags as $arr_next_tag) {
			$s_tag = $arr_next_tag['value'];
			$i_tag = intval($arr_next_tag['cnt']);
			$arr_u_tags[$s_tag] = round($i_tag * 100 / $i_sum_u, 1);
			if (!isset($arr_eff_tags[$s_tag])) $arr_eff_tags[$s_tag] = 0;
		}
		foreach ($arr_popular_tags as $arr_next_tag) {
			$s_tag = $arr_next_tag['tag'];
			$i_tag = intval($arr_next_tag['cnt']);
			$arr_p_tags[$s_tag] = round($i_tag * 100 / $i_sum_p, 1);
			if (!isset($arr_eff_tags[$s_tag])) $arr_eff_tags[$s_tag] = 0;
		}

		foreach ($arr_eff_tags as $s_tag => $i_efficiency) {
			if (isset($arr_u_tags[$s_tag]) && isset($arr_p_tags[$s_tag])) {
				$arr_eff_tags[$s_tag] = round($arr_p_tags[$s_tag] / $arr_u_tags[$s_tag], 1);
			} elseif (isset($arr_u_tags[$s_tag])) {
				$arr_eff_tags[$s_tag] = 0; // 0/100
			} elseif (isset($arr_p_tags[$s_tag])) {
				$arr_eff_tags[$s_tag] = 1000; // 100/0.1 (0.1 - round(x/y, 1))
			}
		}

		$arrTags = array();

		foreach ($arr_eff_tags as $s_tag => $i_efficiency) {
			if (is_null($s_tag)) $s_tag = '[nontagged]';

			$f_weight = round($i_efficiency / 10, 1);

			$i_font = round(((($max_font_size - $min_font_size)/100) * $f_weight) + $min_font_size);

			$arrTags[$s_tag] = array('weight' => $f_weight, 'font' => $i_font);
		}

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
			$arrForTags['attribute:summ_weight'] = $summ_weight;
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