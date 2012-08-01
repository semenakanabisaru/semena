<?php
/**
	* Класс для работы с поисковой базой по сайту.
*/
	class searchModel extends singleton implements iSingleton, iSearchModel {
		public function __construct() {
		}

		/**
			* Получить экземпляр класса
			* @return searchModel экземпляр класса
		*/
		public static function getInstance($c = NULL) {
			return parent::getInstance(__CLASS__);
		}

		/**
			* Проиндексировать все страницы, где дата последней модификации меньше даты последней индексации
			* @param Integer $limit = false ограничить количество индексируемых страниц
			* @return Integer количество проиндексированных страниц
		*/
		public function index_all($limit = false, $lastId = 0) {
			$total = 0;

			$sql = "SELECT id, updatetime FROM cms3_hierarchy WHERE is_deleted = '0' AND is_active = '1' AND id > '{$lastId}' ORDER BY id LIMIT 1";
			$result = l_mysql_query($sql, true);

			while(list($element_id, $updatetime) = mysql_fetch_row($result)) {
				++$total;
				$lastId = $element_id;
				$sql = "SELECT id, updatetime FROM cms3_hierarchy WHERE is_deleted = '0' AND is_active = '1' and id > '{$element_id}' ORDER BY id LIMIT 1";
				$result = l_mysql_query($sql, true);

				if(!$this->elementIsReindexed($element_id, $updatetime)) {
					$indexResult = $this->index_item($element_id, true);
					}

					if(($limit !== false) && (--$limit == 0)) {
						break;
					}
				}

			$sql = "SELECT COUNT(*) FROM `cms3_search` LIMIT 1";
			$current = mysql_result(l_mysql_query($sql, true), 0);

			return array("current"=>$current, "lastId"=>$lastId);
			}

		/**
			* Проиндексировать определенную страницу
			* @param Integer $element_id id страницы
			* @param Boolean $is_manual = false устаревший параметр, больше не используется
		*/
		public function index_item($element_id, $is_manual = false) {
			if(defined("UMICMS_CLI_MODE") || defined("DISABLE_SEARCH_REINDEX")) {
				return false;
			}

			l_mysql_query("START TRANSACTION /* Reindexing element #{$element_id} */", true);
			$index_data = $this->parseItem($element_id);
			l_mysql_query("COMMIT", true);

			return $index_data;
		}

		/**
			* Узнать, индесировалась ли страница $element_id после даты $updatetime
			* @param Integer $element_id id страницы
			* @param Integer $updatetime требуемое время индексации
			* @return Boolean результат операции
		*/
		public function elementIsReindexed($element_id, $updatetime) {
			$sql = "SELECT COUNT(*) FROM cms3_search WHERE rel_id = '{$element_id}' AND indextime > '{$updatetime}'";
			$result = l_mysql_query($sql, true);
			list($c) = mysql_fetch_row($result);

			return (bool) $c;
		}

		public function parseItem($element_id) {
			if(!($element = umiHierarchy::getInstance()->getElement($element_id, true, true))) {
				return false;
			}

			if($element->getValue("is_unindexed")) {
				$domain_id = $element->getDomainId();
				$lang_id = $element->getLangId();
				$type_id = $element->getTypeId();

				$sql = "SELECT COUNT(*) FROM cms3_search WHERE rel_id = '{$element_id}'";
				list($c) = mysql_fetch_row(l_mysql_query($sql, true));

		    		if(!$c) {
					$sql = "INSERT INTO cms3_search (rel_id, domain_id, lang_id, type_id) VALUES('{$element_id}', '{$domain_id}', '{$lang_id}', '{$type_id}')";
					l_mysql_query($sql, true);
				}
				return false;
			}

			$index_fields = Array();

			$type_id = $element->getObject()->getTypeId();
			$type = umiObjectTypesCollection::getInstance()->getType($type_id);

			$field_groups = $type->getFieldsGroupsList();
			foreach($field_groups as $field_group_id => $field_group) {
				foreach($field_group->getFields() as $field_id => $field) {
					if($field->getIsInSearch() == false) continue;

					$field_name = $field->getName();
					$val = $element->getValue($field_name);
					$data_type = $field->getFieldType()->getDataType();

					if($data_type) {
						if(is_array($val)) {
							if($data_type == 'relation') {
								foreach($val as $i => $v) {
									if($item = selector::get('object')->id($v)) {
										$val[$i] = $item->name;
										unset($item);
									}
								}
							}
							$val = implode(' ', $val);
						} else {
							if(is_object($val)) {
								continue;
							}

							if($data_type == 'relation') {
								if($item = selector::get('object')->id($val)) {
									$val = $item->name;
								}
							}
						}
					}

					if(is_null($val) || !$val) continue;


					// kill macroses
					$val = preg_replace("/%([A-z_]*)%/m", "", $val);
					$val = preg_replace("/%([A-zЂ-пРђ-СЏ \/\._\-\(\)0-9%:<>,!@\|'&=;\?\+#]*)%/m", "", $val);

					$index_fields[$field_name] = $val;
				}
			}

			$index_image = $this->buildIndexImage($index_fields);
			$this->updateSearchIndex($element_id, $index_image);
		}

		public function buildIndexImage($indexFields) {
			$img = Array();

			$weights = Array(
				'h1' => 5,
				'title' => 5,
				'meta_keywords' => 3,
				'meta_descriptions' => 3,
				'tags' => 3
			);

			foreach($indexFields as $fieldName => $str) {
				$arr = $this->splitString($str);

				if(isset($weights[$fieldName])) {
					$weight = (int) $weights[$fieldName];
				} else {
					$weight = 1;
				}

				foreach($arr as $word)  {
					if(array_key_exists($word, $img)) {
						$img[$word] += $weight;
					} else {
						$img[$word] = $weight;
					}
				}
			}
			return $img;
		}

		public static function splitString($str) {
			if(is_object($str)) {    //TODO: Temp
				return NULL;
			}

			$to_space = Array("&nbsp;", "&quote;", ".", ",", "?", ":", ";", "%", ")", "(", "/", 0x171, 0x187, "<", ">", "-");

			$str = str_replace(">", "> ", $str);
			$str = str_replace("\"", " ", $str);
			$str = strip_tags($str);
			$str = str_replace($to_space, " ", $str);
			$str = preg_replace("/([ \t\r\n]{1-100})/u", " ", $str);
			//$str = wa_strtolower($str);
			$tmp = explode(" ", $str);

			$res = Array();
			foreach($tmp as $v) {
				$v = trim($v);

				if(wa_strlen($v) <= 2) continue;

				$res[] = $v;
			}

			return $res;
		}

		public function updateSearchIndex($element_id, $index_image) {
			$element = umiHierarchy::getInstance()->getElement($element_id, true);

			$domain_id = $element->getDomainId();
			$lang_id = $element->getLangId();
			$type_id = $element->getTypeId();

			$sql = "SELECT COUNT(*) FROM cms3_search WHERE rel_id = '{$element_id}'";
			list($c) = mysql_fetch_row(l_mysql_query($sql, true));

			if(!$c) {
				$sql = "INSERT INTO cms3_search (rel_id, domain_id, lang_id, type_id) VALUES('{$element_id}', '{$domain_id}', '{$lang_id}', '{$type_id}')";
				l_mysql_query($sql, true);
			}

			$sql = "DELETE FROM cms3_search_index WHERE rel_id = '{$element_id}'";
			l_mysql_query($sql, true);

			$sql = "INSERT INTO cms3_search_index (rel_id, weight, word_id, tf) VALUES ";
			$n = 0;

			$total_weight = array_sum($index_image);
			foreach($index_image as $word => $weight) {
				if(($word_id = $this->getWordId($word)) == false) continue;
				$TF = $weight / $total_weight;
				$sql .= "('{$element_id}', '{$weight}', '{$word_id}', '{$TF}'), ";
				++$n;
			}

			if($n) {
				$sql = substr($sql, 0, wa_strlen($sql) - 2);
				l_mysql_query($sql, true);
			}

			$time = time();

			$sql = "UPDATE cms3_search SET indextime = '{$time}' WHERE rel_id = '{$element_id}'";
			l_mysql_query($sql, true);

			umiHierarchy::getInstance()->unloadElement($element_id);

			return true;
		}

		/**
			* Получить id слова $word в поисковой базе
			* @param String $word слово
			* @return Integer|Boolean id слова, либо false
		*/
		public static function getWordId($word) {
			$word = str_replace("037", "", $word);
			$word = trim($word, "\r\n\t? ;.,!@#$%^&*()_+-=\\/:<>{}[]'\"`~|");
			$word = wa_strtolower($word);

			if(wa_strlen($word) < 3) {
				return false;
			}

			$word = l_mysql_real_escape_string($word);

			$sql = "SELECT id FROM cms3_search_index_words WHERE word = '{$word}'";
			$result = l_mysql_query($sql, true);

			if(list($word_id) = mysql_fetch_row($result)) {
				return $word_id;
			} else {
				$sql = "INSERT INTO cms3_search_index_words (word) VALUES('{$word}')";
				$result = l_mysql_query($sql, true);

				return (int) l_mysql_insert_id();
			}
		}

		/**
			* Получить количество проиндексированных страниц
			* @return Integer кол-во проиндексированных страниц
		*/
		public function getIndexPages() {
			$sql = "SELECT SQL_SMALL_RESULT COUNT(*) FROM cms3_search";
			$result = l_mysql_query($sql, true);

			list($c) = mysql_fetch_row($result);
			return (int) $c;
		}

		/**
			* Получить общее количество страниц, которые можно проиндексировать
			* @return Integer кол-во страниц, годных к индексации
		*/
		public function getAllIndexablePages() {
			$sql = "SELECT COUNT(*) FROM cms3_hierarchy WHERE is_deleted = '0' AND is_active = '1' ORDER BY id LIMIT 1";
			$result = l_mysql_query($sql, true);

			list($c) = mysql_fetch_row($result);
			return (int) $c;
		}

		/**
			* Получить количество проиндескированных слов
			* @return Integer количество слов
		*/
		public function getIndexWords() {
			$sql = "SELECT SQL_SMALL_RESULT SUM(weight) FROM cms3_search_index";
			$result = l_mysql_query($sql, true);

			list($c) = mysql_fetch_row($result);
			return (int) $c;
		}

		/**
			* Получить количество проиндескированных уникальных слов
			* @return Integer количество уникальных слов
		*/
		public function getIndexWordsUniq() {
			$sql = "SELECT SQL_SMALL_RESULT COUNT(*) FROM cms3_search_index_words";
			$result = l_mysql_query($sql, true);

			list($c) = mysql_fetch_row($result);
			return (int) $c;
		}

		/**
			* Получить дату последней индексации
			* @return Integer дата последней индексации
		*/
		public function getIndexLast() {
			$sql = "SELECT SQL_SMALL_RESULT indextime FROM cms3_search ORDER BY indextime DESC LIMIT 1";
			$result = l_mysql_query($sql, true);

			list($c) = mysql_fetch_row($result);
			return (int) $c;
		}

		/**
			* Очистить поисковый индекс
		*/
		public function truncate_index () {
			$sql = "TRUNCATE TABLE cms3_search_index_words";
			l_mysql_query($sql, true);

			$sql = "TRUNCATE TABLE cms3_search_index";
			l_mysql_query($sql, true);

			$sql = "TRUNCATE TABLE cms3_search";
			l_mysql_query($sql, true);

			return true;
		}

		/**
			* Искать по поисковому индексу
			* @param String $str поисковая строка
			* @param Array $search_types = NULL если указан, то будут выраны только страницы с необходимым hierarchy-type-id
			* @param Array $hierarchy_rels = NULL если указан, то искать только в определенном разделе сайта
			* @param Boolean $orMode = false если true, то искать в режиме OR, иначе в режиме AND
			* @return Array массив, стостоящий из id найденых страниц
		*/
		public function runSearch($str, $search_types = NULL, $hierarchy_rels = NULL, $orMode = false) {
			$words_temp = preg_split("/[ \-\_]/", $str);    //TODO
			$words = Array();

			foreach($words_temp as $word) {
				if(wa_strlen($word) >= 3) {
					$words[] = $word;
				}
			}

			$elements = $this->buildQueries($words, $search_types, $hierarchy_rels, $orMode);

			return $elements;
		}

		public function buildQueries($words, $search_types = NULL, $hierarchy_rels = NULL, $orMode = false) {
			$lang_id = cmsController::getInstance()->getCurrentLang()->getId();
			$domain_id = cmsController::getInstance()->getCurrentDomain()->getId();

			$morph_disabled  = mainConfiguration::getInstance()->get('system','search-morph-disabled');
			$words_conds = Array();
			foreach($words as $i => $word) {
				if(wa_strlen($word) < 3) {
					unset($words[$i]);
					continue;
				}

				$word = l_mysql_real_escape_string($word);
				$word = str_replace(Array("%", "_"), Array("\\%", "\\_"), $word);

				$word_subcond = "siw.word LIKE '{$word}%' ";

				if(!$morph_disabled)  {
					$word_subcond .=' OR ';
					$word_base = language_morph::get_word_base($word);

					if(wa_strlen($word_base) >= 3) {
						$word_base = l_mysql_real_escape_string($word_base);
						$word_subcond .= "siw.word LIKE '{$word_base}%'";
					} else {
						$word_subcond = trim($word_subcond, " OR ");
					}
				}

				$words_conds[] = "(" . $word_subcond . ")";
			}

			$words_cond = implode(" OR ", $words_conds);

			$users = cmsController::getInstance()->getModule("users");
			$user_id = $users->user_id;
			$user = umiObjectsCollection::getInstance()->getObject($user_id);
			$groups = $user->getValue("groups");
			$groups[] = $user_id;
			$groups[] = regedit::getInstance()->getVal("//modules/users/guest_id");
			$groups = array_extract_values($groups);

			$perms_sql = "";
			$sz = sizeof($groups);
			for($i = 0; $i < $sz; $i++) {
				if($i == 0) {
					$perms_sql .= " AND (";
				}

				$perms_sql .= "(c3p.owner_id = '{$groups[$i]}' AND c3p.rel_id = h.id AND level >= 1)";

				if($i == ($sz - 1)) {
					$perms_sql .= ")";
				} else {
					$perms_sql .= " OR ";
				}
			}
			$perms_table = ", cms3_permissions c3p";

			if(cmsController::getInstance()->getModule('users')->isSv()) {
				$perms_table = "";
				$perms_sql = "";
			}

			$types_sql = "";
			if(is_array($search_types)) {
				if(sizeof($search_types)) {
					if($search_types && $search_types[0]) {
						$types_sql = " AND s.type_id IN (" . implode(", ", $search_types) . ")";
					}
				}
			}

			$hierarchy_rels_sql = "";
			if (is_array($hierarchy_rels) && count($hierarchy_rels)) {
				$hierarchy_rels_sql = " AND h.rel IN (" . implode(", ", $hierarchy_rels) . ")";
			}

			if($words_cond == false) {
				return Array();
			}

			l_mysql_query("CREATE TEMPORARY TABLE temp_search (rel_id int unsigned, tf float, word varchar(64))");

			$sql = <<<SQL

INSERT INTO temp_search SELECT SQL_SMALL_RESULT HIGH_PRIORITY  s.rel_id, si.tf, siw.word

	FROM    cms3_search_index_words siw,
		cms3_search_index si,
		cms3_search s,
		cms3_hierarchy h
		{$perms_table}

			WHERE    ({$words_cond}) AND
				si.word_id = siw.id AND
				s.rel_id = si.rel_id AND
				s.domain_id = '{$domain_id}' AND
				s.lang_id = '{$lang_id}' AND
				h.id = s.rel_id AND
				h.is_deleted = '0' AND
				h.is_active = '1'
				{$types_sql}
				{$hierarchy_rels_sql}
				{$perms_sql}


SQL;


			$res = Array();

			l_mysql_query($sql);

			if($orMode) {
				$sql = <<<SQL
SELECT rel_id, (SUM(tf) / AVG(tf)) AS x
	FROM temp_search
		GROUP BY rel_id
			ORDER BY x DESC
SQL;

			} else {
				$wordsCount = sizeof($words);

				$sql = <<<SQL
SELECT rel_id, (SUM(tf) / AVG(tf)) AS x, COUNT(word) AS wc
	FROM temp_search
		GROUP BY rel_id
			HAVING wc >= '{$wordsCount}'
				ORDER BY x DESC
SQL;
			}
			$result = l_mysql_query($sql);

			while(list($element_id) = mysql_fetch_row($result)) {
				$res[] = $element_id;
			}

			l_mysql_query("DROP TEMPORARY TABLE IF EXISTS temp_search");

			return $res;
		}

		public function prepareContext($element_id, $uniqueOnly = false) {
			if(!($element = umiHierarchy::getInstance()->getElement($element_id))) {
				return false;
			}

			if($element->getValue("is_unindexed")) return false;

			$context = Array();

			$type_id = $element->getObject()->getTypeId();
			$type = umiObjectTypesCollection::getInstance()->getType($type_id);

			$field_groups = $type->getFieldsGroupsList();
			foreach($field_groups as $field_group_id => $field_group) {
				foreach($field_group->getFields() as $field_id => $field) {
					if($field->getIsInSearch() == false) continue;

					$field_name = $field->getName();
					$data_type = $field->getFieldType()->getDataType();

					$val = $element->getValue($field_name);

					if($data_type == 'relation') {
						if(!is_array($val)) {
							$val = array($val);
						}
						foreach($val as $i => $v) {
							if($item = selector::get('object')->id($v)) {
								$val[$i] = $item->name;
							}
						}
						$val = implode(' ', $val);
					}

					if(is_null($val) || !$val) continue;

					if(is_object($val)) {
						continue;
					}

					$context[] = $val;
				}
			}

			if($uniqueOnly) {
			    $context = array_unique($context);
			}

			$res = "";
			foreach($context as $val) {
				if(is_array($val)) {
					continue;
				}
				$res .= $val . " ";
			}

			$res = preg_replace("/%[A-z0-9_]+ [A-z0-9_]+\([^\)]+\)%/im", "", $res);


			$res = str_replace("%", "&#037", $res);
			return $res;
		}

		/**
			* Получить контекст, в котором употреблены поисковые слова на страние $element_id
			* @param Integer $element_id id страницы
			* @param String $search_string поисковая строка
			* @return String контекст поисковой строки
		*/
		public function getContext($element_id, $search_string) {
			$content = $this->prepareContext($element_id, true);

			$content = preg_replace("/%content redirect\((.*)\)%/im", "::CONTENT_REDIRECT::\\1::", $content);
			$content = preg_replace("/(%|&#037)[A-z0-9]+ [A-z0-9]+\((.*)\)(%|&#037)/im", "", $content);

			$bt = "<b>";
			$et = "</b>";


			$words_arr = explode(" ", $search_string);


			$content = preg_replace("/([A-zА-я0-9])\.([A-zА-я0-9])/im", "\\1&#46;\\2", $content);

			$context = str_replace(">", "> ", $content);
			$context = str_replace("<br>", " ", $context);
			$context = str_replace("&nbsp;", " ", $context);
			$context = str_replace("\n", " ", $context);
			$context = strip_tags($context);


			if(preg_match_all("/::CONTENT_REDIRECT::(.*)::/i", $context, $temp)) {
				$sz = sizeof($temp[1]);

				for($i = 0; $i < $sz; $i++) {
					if(is_numeric($temp[1][$i])) {
						$turl = cmsController::getInstance()->getModule('content')->get_page_url($temp[1][$i]);
						$turl = umiHierarchy::getInstance()->getPathById($temp[1][$i]);
						$turl = trim($turl, "'");
						$res = str_replace($temp[0][$i], "<p>%search_redirect_text% \"<a href='$turl'>$turl</a>\"</p>", $context);
					} else {
						$turl = strip_tags($temp[1][$i]);
						$turl = trim($turl, "'");
						$context = str_replace($temp[0][$i], "<p>%search_redirect_text% <a href=\"" . $turl . "\">" . $turl . "</a></p>", $context);
					}
				}
			}

			$context .= "\n";


			$res_out = "";

			$lines = Array();
			foreach($words_arr as $cword) {
				if(wa_strlen($cword) <= 1)    continue;

				$tres = $context;
				$sword = language_morph::get_word_base($cword);
                $sword = preg_quote($sword);
				$pattern_sentence = "/([^\.^\?^!^<^>.]*)$sword([^\.^\?^!^<^>.]*)[!\.\?\n]/imu";
				$pattern_word = "/([^ ^[\.[ ]*]^!^\?^\(^\).]*)($sword)([^ ^\.^!^\?^\(^\).]*)/imu";

				if (preg_match($pattern_sentence, $tres, $tres)) {
					$lines[] = $tres[0];
				}
			}

			$lines = array_unique($lines);

			$res_out = "";
			foreach($lines as $line) {
				foreach($words_arr as $cword) {
					$sword = language_morph::get_word_base($cword);
					$sword = preg_quote($sword);
					$pattern_word = "/([^ ^.^!^\?.]*)($sword)([^ ^.^!^\?.]*)/imu";
					$line = preg_replace($pattern_word, $bt . "\\1\\2\\3" . $et, $line);
				}

				if($line) {
					$res_out .= "<p>" . $line . "</p>";
				}
			}

			if(!$res_out) {
				preg_match("/([^\.^!^\?.]*)([\.!\?]*)/im", $context, $res_out);
				$res_out = $res_out[0];
				$res_out = "<p></p>";
			}
			return $res_out;
		}

		/**
			* Стереть индекс для страницы $element_id
			* @param Integer $element_id id страницы
		*/
		public function unindex_items($element_id) {
			$element_id = (int) $element_id;

			$sql = "DELETE FROM cms3_search WHERE rel_id = '{$element_id}'";
			l_mysql_query($sql, true);

			$sql = "DELETE FROM cms3_search_index WHERE rel_id = '{$element_id}'";
			l_mysql_query($sql, true);

			return true;
		}

		/**
			* Проиндексировать страницу $element_id и всех ее детей
			* @param Integer $element_id id страницы
		*/
		public function index_items($element_id) {
			$hierarchy = umiHierarchy::getInstance();
			$childs = $hierarchy->getChilds($element_id, true, true, 99);
			$elements = array($element_id);
			$this->expandArray($childs, $elements);

			foreach($elements as $element_id) {
				$this->index_item($element_id);
			}
		}

		/**
			* Посчитать IDF слова $wordId
			* @param Integer $wordId id слова в поисковой базе
		*/
		public function calculateIDF($wordId) {
			static $IDF = false;

			if($IDF === false) {
				$sql = "SELECT COUNT(*) FROM cms3_search";
				$result = l_mysql_query($sql);
				list($d) = mysql_fetch_row($result);

				$sql = "SELECT COUNT(*) FROM cms3_search_index WHERE word_id = {$wordId}";
				$result = l_mysql_query($sql);
				list($dd) = mysql_fetch_row($result);

				$IDF = log($d / $dd);
			}
			return $IDF;
		}

		public function suggestions($string, $limit = 10) {
			$string = trim($string);
			if(!$string) return false;
			$string = wa_strtolower($string);

			$rus = str_split('йцукенгшщзхъфывапролджэячсмитьбю');
			$eng = str_split('qwertyuiop[]asdfghjkl;\'zxcvbnm,.');

			$string_cp1251 = iconv("UTF-8", "CP1251", $string);
			$mirrowed_rus = iconv("CP1251", "UTF-8", str_replace($rus, $eng, $string_cp1251));
			$mirrowed_eng = iconv("CP1251", "UTF-8", str_replace($eng, $rus, $string_cp1251));

			$mirrowed = ($mirrowed_rus != $string) ? $mirrowed_rus : $mirrowed_eng;

			$string = l_mysql_real_escape_string($string);
			$mirrowed = l_mysql_real_escape_string($mirrowed);
			$limit = (int) $limit;

			$sql = <<<SQL
SELECT `siw`.`word` as `word`, COUNT(`si`.`word_id`) AS `cnt`
	FROM
		`cms3_search_index_words` `siw`,
		`cms3_search_index` `si`
	WHERE
		(
			`siw`.`word` LIKE '{$string}%' OR
			`siw`.`word` LIKE '{$mirrowed}%'
		) AND
		`si`.`word_id` = `siw`.`id`
	GROUP BY
		`siw`.`id`
	ORDER BY SUM(`si`.`tf`) DESC
	LIMIT {$limit}
SQL;

			$connection = ConnectionPool::getInstance()->getConnection('search');
			return $connection->queryResult($sql);
		}

		private function expandArray($arr, &$result) {
			if(is_null($result)) $result = array();

			foreach($arr as $id => $childs) {
				$result[] = $id;
				$this->expandArray($childs, $result);
			}
		}
	};
?>