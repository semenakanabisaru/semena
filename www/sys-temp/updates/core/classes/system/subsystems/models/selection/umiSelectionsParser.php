<?php
/**
	* Производит выборки по параметрам, переданным через класс umiSelection.
	* Содержит только статические публичные методы, сами по себе экземпляры этого класса бесполезны.
*/
	class umiSelectionsParser implements iUmiSelectionsParser {
		/*
		public static function runSelection(umiSelection $selection)
		public static function runSelectionCounts(umiSelection $selection)
		public static function parseSelection(umiSelection $selection)
		*/
		private function __construct() {}

		/**
		* @desc Выбирает id объектов (umiObject) или елементов иерархии (umiHierarchyElement), соответсвующих указаным критериям
		* @param umiSelection $selection Критерии выборки
		* @return Array id элементов иерархии или объектов
		*/
		public static function runSelection(umiSelection $selection) {
			static $permissions;
			if ($selection->result !== false) return $selection->result; // RETURN

			$sqls = self::parseSelection($selection);

			if (!$sqls['result']) return false; // RETURN

			// ====
			$result = l_mysql_query($sqls['result']);

			$res = Array();
			while ($row = mysql_fetch_row($result)) {
				list($element_id) = $row;
				if(isset($row[1])) {
					if(!$permissions) {
						$permissions = permissionsCollection::getInstance();
					}
					$permissions->pushElementPermissions($element_id, $row[1]);
				}
				$element_id = intval($element_id);
				if(in_array($element_id, $res) == false) {
					$res[] = $element_id;
				}
			}

			if($selection->excludeNestedPages) {
				$res = self::excludeNestedPages($res);
			}

			$selection->result = $res;

			if(defined("DISABLE_CALC_FOUND_ROWS")) {
				if(DISABLE_CALC_FOUND_ROWS) {
					$sql = "SELECT FOUND_ROWS()";
					$result = l_mysql_query($sql, true);

					list($count) = mysql_fetch_row($result);
					$selection->count = $count;
				}
			}
			if ($selection->optimize_root_search_query) {
				$selection->count = false;
			}
			// RETURN
			return $selection->result;
		}

		/**
		* @desc Выполняет подсчет элементов/объктов, соответствующих критериям выборки
		* @param umiSelection $selection Критерии выборки
		* @return Int количество выбранных объектов или элементов
		*/
		public static function runSelectionCounts(umiSelection $selection) {
			if ($selection->count !== false) return $selection->count; // RETURN

			$sqls = self::parseSelection($selection);

			if (!$sqls['count']) return false; // RETURN


			if ($count = cacheFrontend::getInstance()->loadSql($sqls['count'])) {
				// RETURN
				return $count;
			}

			// ====

			$result = l_mysql_query($sqls['count']);

			if (list($count) = mysql_fetch_row($result)) {
				$selection->count = intval($count);
				// RETURN
				cacheFrontend::getInstance()->saveSql($sqls['count'], $selection->count);
				return $selection->count;
			} else {
				// RETURN
				return false;
			}
		}

		/**
		* @desc Производит подготовку запросов к выборке
		* @param umiSelection $selection Критерии выборки
		* @return Array ID объектов (umiObject) или элементов иерархии (umiHierarchyElement)
		*/
		public static function parseSelection(umiSelection $selection) {

			/*

			Метод формирует запросы для использования методами
			runSelection и runSelectionCounts
			на основании данных из $selection

			Вот что мы должны получить в итоге :

			SELECT
				[ALL | DISTINCT | DISTINCTROW ]
					[HIGH_PRIORITY]
					[STRAIGHT_JOIN]
					[SQL_SMALL_RESULT] [SQL_BIG_RESULT] [SQL_BUFFER_RESULT]
					[SQL_CACHE | SQL_NO_CACHE] [SQL_CALC_FOUND_ROWS]
				select_expr, ...
				[FROM table_references
				[WHERE where_condition]
				[GROUP BY {col_name | expr | position}
					[ASC | DESC], ... [WITH ROLLUP]]
				[HAVING where_condition]
				[ORDER BY {col_name | expr | position}
					[ASC | DESC], ...]
				[LIMIT {[offset,] row_count | row_count OFFSET offset}]
				[PROCEDURE procedure_name(argument_list)]
				[INTO OUTFILE 'file_name' export_options
					| INTO DUMPFILE 'file_name'
					| INTO var_name [, var_name]]
				[FOR UPDATE | LOCK IN SHARE MODE]]

			*/

			if(!defined('MAX_SELECTION_TABLE_JOINS')) {
				define('MAX_SELECTION_TABLE_JOINS', 10);
			}

			/*
			I. Хранить промежуточные результаты мы будем в $selection'е (так как сами мы static),
			а перед работой их обдефолтим :
			*/

			// это промежуточные строки и триггеры, на основании которых соберутся итоговые запросы

			$selection->sql_cond__need_content = false;
			$selection->sql_cond__need_hierarchy = $selection->getForceCond();
			$selection->sql_cond__domain_ignored = $selection->getIsDomainIgnored();
			$selection->sql_cond__lang_ignored = $selection->getIsLangIgnored();
			$selection->sql_cond__total_joins = 0;
			$selection->sql_cond__content_tables_loaded = 0; // for tables naming in the query
			$selection->sql_arr_for_mark_used_fields = array();
			$selection->sql_arr_for_and_or_part = array();

			$selection->sql_part__hierarchy = "";			// условие на родителя элемента иерархии
			$selection->sql_part__element_type = "";		// условие на тип элементов иерархии
			$selection->sql_part__object_type = "";			// условие на тип объектов данных
			$selection->sql_part__owner = "";				// условие на владельца
			$selection->sql_part__objects = "";				// условие на конкретные объекты данных
			$selection->sql_part__elements = "";			// условие на конкретные элементы иерархии
			$selection->sql_part__perms = "";				// учесть разрешения
			$selection->sql_part__props_and_names = "";		// условия на значения свойств и имена
			$selection->sql_part__lang_cond = "";			// условие на языки
			$selection->sql_part__domain_cond = "";			// условие на домены
			$selection->sql_part__unactive_cond = "";		// условие на активность элемента

			$selection->sql_part__perms_tables = "";
			$selection->sql_part__content_tables = "";

			// это основные части итоговых запросов

			$selection->sql_kwd_distinct = "";
			$selection->sql_kwd_distinct_count = "";
			$selection->sql_kwd_straight_join = "";

			$selection->sql_select_expr = "";
			$selection->sql_table_references = "";
			$selection->sql_where_condition_required = "";
			$selection->sql_where_condition_common = "";
			$selection->sql_where_condition_additional = "";

			$selection->sql_order_by = "";
			$selection->sql_limit = ""; // ограничение на количество рядов в выборке

			/*
			II. Далее формируем промежуточные результаты
			!!! порядок вызова важен, не меняй не думая !!! // WARNING
			*/

			/*
				---- makeLimitPart ----

				формирует "ограничение на количество рядов в выборке", т.е.
				limit-offset часть запроса, согласно $selection->getLimitConds()

				условия задаются в umiSelection последовательным вызовом
				setLimitFilter и addLimit
				(или просто addLimit, т.к. он сам вызывает setLimitFilter)

				----

				Влияет на:
				- $selection->sql_limit
			*/
			self::makeLimitPart($selection);

			/*
				---- makeHierarchyPart ----

				формирует часть запроса "условие на родителя", то есть
				"выбрать только такие элементы иерархии,
				которые являются непосредственными потомками указанных элементов"

				получить "указанные элементы" - $selection->getHierarchyConds()

				задаются "указанные элементы" в umiSelection последовательным вызовом
				setHierarchyFilter и addHierarchyFilter
				(или просто addHierarchyFilter, т.к. он сам вызывает setHierarchyFilter)

				NOTE: в addHierarchyFilter указывается глубина, но в запросе, генерируемом self::makeHierarchyPart
				глубина не участвует, так как addHierarchyFilter сам пробегается по всей глубине,
				и включает всех потомков в массив, возвращаемый $selection->getHierarchyConds()
				(глубина и не может участвовать, так как таблица предполагает связь только с непосредственным родителем (rel))

				----

				Влияет на:
				- $selection->sql_part__hierarchy
				- $selection->sql_cond__domain_ignored
				- $selection->sql_cond__need_hierarchy
			*/
			self::makeHierarchyPart($selection);


			/*
				---- makeElementTypePart ----

				формирует часть запроса "условие на тип элементов иерархии", то есть
				"выбрать только те элементы иерархии, которые имеют один из указанных типов"

				получить "указанные типы элементов" - $selection->getElementTypeConds()

				задаются "указанные типы элементов" в umiSelection последовательным вызовом
				setElementTypeFilter и addElementType
				(или просто addElementType, т.к. он сам вызывает setElementTypeFilter)

				----

				Влияет на:
				$selection->sql_part__element_type
				$selection->sql_cond__need_hierarchy
			*/
			self::makeElementTypePart($selection);


			/*
				---- makeOwnerPart ----

				формирует часть запроса "условие на владельца", то есть
				"выбрать только те объекты данных / элементы иерархии,
				которые имеют одного из указанных владельцев"

				получить "указанных владельцев" - $selection->getOwnerConds()

				задаются "указанные владельцы" в umiSelection последовательным вызовом
				setOwnerFilter и addOwnerFilter
				(или просто addOwnerFilter, т.к. он сам вызывает setOwnerFilter)

				----

				Влияет на:
				$selection->sql_part__owner
			*/
			self::makeOwnerPart($selection);

			/*
				---- makeObjectsPart ----

				формирует часть запроса "условие на конкретные объекты данных", то есть
				"выбрать только те объекты данных,
				которые имеют один из указанных идентификаторов"
				(например, получены предыдущим запросом umiSelection'а)

				получить "указанные идентификаторы" - $selection->getObjectsConds()

				задаются "указанные идентификаторы" в umiSelection последовательным вызовом
				setObjectsFilter и addObjectsFilter
				(или просто addObjectsFilter, т.к. он сам вызывает setObjectsFilter)

				----

				Влияет на:
				$selection->sql_part__objects
			*/
			self::makeObjectsPart($selection);

			/*
				---- makeElementsPart ----

				формирует часть запроса "условие на конкретные элементы иерархии", то есть
				"выбрать только те элементы иерархии,
				которые имеют один из указанных идентификаторов"
				(например, получены предыдущим запросом umiSelection'а)

				получить "указанные идентификаторы" - $selection->getElementsConds()

				задаются "указанные идентификаторы" в umiSelection последовательным вызовом
				setElementsFilter и addElementsFilter
				(или просто addElementsFilter, т.к. он сам вызывает setElementsFilter)

				----

				Влияет на:
				$selection->sql_part__elements
				$selection->sql_cond__need_hierarchy
			*/
			self::makeElementsPart($selection);

			/*
				---- makePermsParts ----

				формирует часть запроса "учесть разрешения", то есть
				"выбрать только те элементы иерархии, на которые у указанного пользователя
				и групп, в которые он входит, есть разрешения на чтение"

				получить "указанного пользователя и его группы" - $selection->getPermissionsConds()

				задаются "указанный пользователь и его группы" в umiSelection последовательным вызовом
				setPermissionsFilter и addPermissions
				(или просто addPermissions, т.к. он сам вызывает setPermissionsFilter)

				----

				Влияет на:
				$selection->sql_part__perms
				$selection->sql_part__perms_tables
				$selection->sql_cond__need_hierarchy
			*/
			self::makePermsParts($selection);

			/*
				---- makePropPart ----

				формирует условия в зависимости от
				заданных "фильтров по полям объектов/элементов"

				получить "фильтры по полям объектов/элементов" - $selection->getPropertyConds()

				задаются "фильтры по полям объектов/элементов" в umiSelection последовательным вызовом
				setPropertyFilter и методов
					- addPropertyFilterBetween
					- addPropertyFilterEqual
					- addPropertyFilterNotEqual
					- addPropertyFilterLike
					- addPropertyFilterMore
					- addPropertyFilterLess
					- addPropertyFilterIsNull
				(или просто этими методами, т.к. они сами вызывают setPropertyFilter)

				----

				Влияет на:
				$selection->sql_part__props_and_names
				$selection->sql_part__content_tables
				$selection->sql_cond__need_content
				$selection->sql_cond__total_joins
			*/
			self::makePropPart($selection);

			/*
				---- makeObjectTypePart ----

				формирует часть запроса "условие на тип объектов данных", то есть
				"выбрать только те объектов (соотнесенные с ними элементы иерархии),
				которые имеют один из указанных типов"

				получить "указанные типы объектов" - $selection->getObjectTypeConds()

				задаются "указанные типы объектов" в umiSelection последовательным вызовом
				setObjectTypeFilter и addObjectType
				(или просто addObjectType, т.к. он сам вызывает setObjectTypeFilter)

				----

				Влияет на:
				$selection->sql_part__object_type
			*/
			self::makeObjectTypePart($selection);

			/*
				---- makeOrderPart ----

				формирует часть запроса "ORDER BY"

				получить "ORDER BY" - $selection->getOrderConds()

				задаются "ORDER BY" в umiSelection последовательным вызовом
				setOrderFilter и методов
					- setOrderByProperty
					- setOrderByOrd
					- setOrderByRand
					- setOrderByName
					- setOrderByObjectId
				(или просто этими методами, т.к. они сами вызывают setOrderFilter)

				----

				Влияет на:
				$selection->sql_order_by
				$selection->sql_cond__need_content
				$selection->sql_cond__content_tables_loaded
				$selection->sql_part__content_tables
				$selection->sql_arr_for_and_or_part
				$selection->sql_cond__total_joins
			*/
			self::makeOrderPart($selection);

			/*
				---- makeNamesPart ----

				формирует часть запроса
				"взять только объекты с указанными/похожими именами"

				получить "указанные имена" - $selection->getNameConds

				задаются "указанные имена" в umiSelection последовательным вызовом
				setNamesFilter и методов
					- addNameFilterEquals
					- addNameFilterLike
				(или просто этими методами, т.к. они сами вызывают setNamesFilter)

				----

				Влияет на:
				$selection->sql_arr_for_and_or_part
			*/
			self::makeNamesPart($selection);

			/*
				---- makePropsAndNames ----

				Сводит массив sql_arr_for_and_or_part в подстроку запроса

				----

				Влияет на:
				$selection->sql_part__props_and_names
			*/
			self::makePropsAndNames($selection);

			/*
				---- makeHierarchySpecificConds ----

				вводит в запрос условия, специфичные для
				элементов иерархии (в отличие от объектов данных)

				----

				Влияет на:
				$selection->sql_part__lang_cond
				$selection->sql_part__domain_cond
				$selection->sql_part__unactive_cond
			*/
			self::makeHierarchySpecificConds($selection);

			// ==== если получилось слишком много таблиц - уходим
			// RETURN

			if ($selection->sql_cond__total_joins >= 59) {
				return Array("result" => false, "count" => false);
			}

			/*
			III. Теперь формируем основные части запроса

			Эти вызовы уже ни на что не влияют,
			просто собирают основные части запроса
			из строк и в соответствии с условиями,
			сформированными на предыдущем этапе
			*/

			self::makeDistinctKeywords($selection);

			self::makeStraitJoinKeyword($selection);

			self::makeSelectExpr($selection);

			self::makeTables($selection);

			self::makeWhereConditions($selection);

			$sql_join_content_tables = "";
			$sz = sizeof($selection->usedContentTables);
			if($sz > 1) {
				for($i = 0; $i < $sz - 1; $i++) {
					$current = $selection->usedContentTables[$i];
					$next = $selection->usedContentTables[$i + 1];
					$sql_join_content_tables .= " AND {$current}.obj_id = {$next}.obj_id";
				}

			}


			/*
			IV. Формируем и возвращаем запросы
			*/

			// RETURN :
			$sql_calc_found_rows = "";
			if(defined("DISABLE_CALC_FOUND_ROWS")) {
				if(DISABLE_CALC_FOUND_ROWS) {
					$sql_calc_found_rows = "SQL_CALC_FOUND_ROWS";
				}
			}

			if($selection->sql_part__perms_tables) {
				$selection->sql_kwd_distinct = "";
				$selection->sql_group_by = " GROUP BY h.id";
			} else {
				$selection->sql_group_by = "";
			}

			$sql = <<<SQL
				SELECT {$selection->sql_kwd_straight_join} {$sql_calc_found_rows}  {$selection->sql_kwd_distinct}
					{$selection->sql_select_expr}
				FROM
					{$selection->sql_table_references}
				WHERE
					{$selection->sql_where_condition_required}
					{$selection->sql_where_condition_common}
					{$selection->sql_where_condition_additional}
					{$sql_join_content_tables}
				{$selection->sql_group_by}
				{$selection->sql_order_by}
				{$selection->sql_limit}
SQL;

			$sql_count = <<<SQL
				SELECT {$selection->sql_kwd_straight_join} 
					COUNT({$selection->sql_kwd_distinct_count}{$selection->sql_select_count_expr})
				FROM
					{$selection->sql_table_references}
				WHERE
					{$selection->sql_where_condition_required}
					{$selection->sql_where_condition_common}
					{$selection->sql_where_condition_additional}
					{$sql_join_content_tables}
SQL;

			if($selection->optimize_root_search_query
			&& sizeof($selection->getElementTypeConds())
			&& !sizeof(array_positive_values($selection->getHierarchyConds()))) {
				$types_in_clause = implode(", ", $selection->getElementTypeConds());

				if($selection->sql_table_references) {
					$selection->sql_table_references = "," . $selection->sql_table_references;
				}

				if($selection->sql_where_condition_required) {
					$selection->sql_where_condition_required = " AND " . $selection->sql_where_condition_required;
				}

				$sql = <<<SQL
SELECT DISTINCT h.id
	FROM cms3_hierarchy hp
		{$selection->sql_table_references}
		WHERE h.type_id IN ({$types_in_clause})
			AND (h.rel = 0 OR (h.rel = hp.id AND hp.type_id NOT IN ({$types_in_clause}))) {$selection->sql_part__domain_cond} {$selection->sql_part__lang_cond}
			AND h.is_deleted = '0'
			{$selection->sql_where_condition_required}
			{$selection->sql_where_condition_common}
			{$selection->sql_where_condition_additional}
				{$selection->sql_order_by}
				{$selection->sql_limit}
SQL;

				$sql_count = <<<SQL
SELECT COUNT(DISTINCT h.id) FROM cms3_hierarchy h, cms3_hierarchy hp WHERE h.type_id IN ({$types_in_clause}) AND (h.rel = 0 OR (h.rel = hp.id AND hp.type_id NOT IN ({$types_in_clause}))) {$selection->sql_part__domain_cond} {$selection->sql_part__lang_cond} AND h.is_deleted = '0'
SQL;
			}

			return array(
				'result' => $sql,
				'count' => $sql_count
			);

		}

		// ====================================================================
		// ====================================================================
		// ====================================================================
		/*
		Методы для формирования основных частей запроса
		- makeDistinctKeywords
		- makeStraitJoinKeyword
		- makeSelectExpr
		- makeTables
		- makeWhereConditions
		*/

		private static function makeDistinctKeywords(umiSelection $selection) {
			$selection->sql_kwd_distinct = '';
			$selection->sql_kwd_distinct_count = '';
			if ($selection->sql_cond__need_content || ($selection->sql_cond__need_hierarchy && $selection->sql_part__perms)) {
				$selection->sql_kwd_distinct = ' DISTINCT';
				$selection->sql_kwd_distinct_count = 'DISTINCT ';
			}
		}

		private static function makeStraitJoinKeyword(umiSelection $selection) {
			if (MAX_SELECTION_TABLE_JOINS > 0 && $selection->sql_cond__total_joins > MAX_SELECTION_TABLE_JOINS) {
				$selection->sql_kwd_straight_join = "STRAIGHT_JOIN";
			} else {
				$selection->sql_kwd_straight_join = "";
			}
		}

		private static function makeSelectExpr(umiSelection $selection) {
			if ($selection->sql_cond__need_hierarchy) {
				if($selection->sql_part__perms_tables) {
					$selection->sql_select_expr = "h.id, MAX(c3p.level)";
				} else {
					$selection->sql_select_expr = "h.id";
				}
				$selection->sql_select_count_expr = "h.id";
			} else {
				$selection->sql_select_expr = "o.id";
				$selection->sql_select_count_expr = "o.id";
			}
		}

		private static function makeTables(umiSelection $selection) {
			$s_other_tables = '';
			if ($selection->sql_cond__need_content) {
				$s_other_tables = $selection->sql_part__content_tables;
			}

			if ($selection->sql_cond__need_hierarchy) {
				if($selection->sql_part__content_tables || $selection->objectTableIsRequired) {
					$objects_table = "cms3_objects o,";
				} else {
					$objects_table = "";
				}

				$selection->sql_table_references .= <<<SQL
					{$objects_table}
					cms3_hierarchy h
					{$s_other_tables}
					{$selection->sql_part__perms_tables}
SQL;
			} else {
				$selection->sql_table_references .= <<<SQL
					cms3_objects o
					{$s_other_tables}
SQL;
			}
		}

		private static function makeWhereConditions(umiSelection $selection) {
			if($selection->sql_part__owner) {
				$selection->sql_part__owner = " AND " . $selection->sql_part__owner;
			}

			// common
			$selection->sql_where_condition_common = <<<SQL
					{$selection->sql_part__object_type}
					{$selection->sql_part__props_and_names}
					{$selection->sql_part__owner}
					{$selection->sql_part__objects}
					{$selection->sql_part__elements}
SQL;

			// required and additional
			if ($selection->sql_cond__need_hierarchy) {

				if($selection->sql_cond__need_hierarchy
				 && !$selection->sql_part__content_tables
				 && !$selection->objectTableIsRequired) {
					$objectsToHierarchyRelation = "";
				} else {
					$objectsToHierarchyRelation = "h.obj_id = o.id AND ";
				}

				$selection->sql_where_condition_required = <<<SQL
					{$objectsToHierarchyRelation}
					h.is_deleted = '0'
SQL;
				$selection->sql_where_condition_additional = <<<SQL
					{$selection->sql_part__hierarchy}
					{$selection->sql_part__unactive_cond}
					{$selection->sql_part__element_type}
					{$selection->sql_part__perms}
					{$selection->sql_part__lang_cond}
					{$selection->sql_part__domain_cond}
SQL;
			} else {
				$selection->sql_where_condition_required = "1";
				$selection->sql_where_condition_additional = "";
			}
		}

		// ====================================================================
		// ====================================================================
		// ====================================================================
		/*
		Методы для формирования промежуточных частей запроса
		и условий их сборки
		*/

		private static function makeLimitPart(umiSelection $selection) {
			$selection->sql_limit = "";
			//
			$limit_cond = $selection->getLimitConds();
			if ($limit_cond !== false) {
				if (is_array($limit_cond) && count($limit_cond) > 1 && is_numeric($limit_cond[0]) && is_numeric($limit_cond[1])) {

					$i_limit = intval($limit_cond[0]);
					$i_page = intval($limit_cond[1]);

					$i_offset = $i_page * $i_limit;

					// [LIMIT {[offset,] row_count | row_count OFFSET offset}]
					$selection->sql_limit = " LIMIT ".$i_offset.", ".$i_limit;

				}
			}
			//
			return $selection->sql_limit;
		}

		private static function makeHierarchyPart(umiSelection $selection) {
			$hierarchy_cond = $selection->getHierarchyConds();

			if (!empty($hierarchy_cond)) {
				$HierarchyRootCounter = 0;
				$HierarchyRelationsCounter = 0;
				$HierarchyRelationsConds = Array();
				foreach($hierarchy_cond as $parentFilter) {
					list($parentId, $depth) = $parentFilter;

					if($parentId == 0) {
						$HierarchyRootCounter++;
						--$depth;
					}

					$sql = "SELECT level FROM cms3_hierarchy_relations WHERE child_id = {$parentId}";
					$result = l_mysql_query($sql);

					list($level) = mysql_fetch_row($result);

					$sqlRelPart = ($parentId > 0) ? "hr.rel_id = '{$parentId}'" : "hr.rel_id IS NULL";
					if($HierarchyRelationsCounter == 0) {
						$selection->sql_table_references .= "cms3_hierarchy_relations hr, ";
						$HierarchyRelationsCounter++;
					}
					$seekDepth = $depth + $level + 1;

					$HierarchyRelationsConds[] = <<<SQL
({$sqlRelPart} AND hr.level <= '{$seekDepth}') AND hr.child_id = h.id
SQL;
				}

				if(sizeof($HierarchyRelationsConds) > 0) {
					$selection->sql_part__hierarchy .= " AND ((" . implode(") OR (", $HierarchyRelationsConds) . ")) ";
				}

				$selection->sql_cond__domain_ignored = ($HierarchyRootCounter) ? false : true; // ?
				$selection->sql_cond__lang_ignored = ($HierarchyRootCounter) ? false : true; // ?

				$selection->sql_cond__need_hierarchy = true;

				if(sizeof($hierarchy_cond) == 1) {
					if($hierarchy_cond[0] != 0) {
						$selection->sql_cond_auto_domain = true;
					}
				} else {
					$selection->sql_cond_auto_domain = true;
				}
			}
		}

		private static function makeElementTypePart(umiSelection $selection) {
			$element_type_cond = $selection->getElementTypeConds();
			if ($element_type_cond && count($element_type_cond)) {
				$selection->sql_part__element_type = " AND h.type_id IN ('".implode("', '", $element_type_cond)."')";
				$selection->sql_cond__need_hierarchy = true;
			}
		}

		private static function makeOwnerPart(umiSelection $selection) {
			$owner_cond = $selection->getOwnerConds();
			if (is_array($owner_cond) && count($owner_cond)) {
				$selection->sql_part__owner = " o.owner_id IN ('".implode("', '", $owner_cond)."')";
				$selection->objectTableIsRequired = true;
			}
		}

		private static function makeObjectsPart(umiSelection $selection) {
			$objects_cond = $selection->getObjectsConds();
			if (is_array($objects_cond) && count($objects_cond)) {
				$selection->sql_part__objects = " AND o.id IN ('".implode("', '", $objects_cond)."')";
			}
		}

		private static function makeElementsPart(umiSelection $selection) {
			$elements_cond = $selection->getElementsConds();
			if (is_array($elements_cond) && count($elements_cond)) {
				$selection->sql_part__elements = " AND h.id IN ('".implode("', '", $elements_cond)."')";
				$selection->sql_cond__need_hierarchy = true;
			}
		}

		private static function makePermsParts(umiSelection $selection) {
			if ($perms_cond = $selection->getPermissionsConds()) {
				$guestId = umiObjectsCollection::getInstance()->getObjectIdByGUID('system-guest');
				$perms_cond[] = $guestId;
				if ($sz = sizeof($perms_cond)) {
					$selection->sql_part__perms_tables = ",cms3_permissions c3p";
					$selection->sql_cond__need_hierarchy = true;

					$permissionsLevel = $selection->getRequiredPermissionsLevel();

					for ($i = 0; $i < $sz; $i++) {

						$selection->sql_part__perms .= ($i === 0 ? " AND (" : "");
						$selection->sql_part__perms .= "(c3p.owner_id = '".$perms_cond[$i]."' AND c3p.rel_id = h.id AND c3p.level & '{$permissionsLevel}')";
						$selection->sql_part__perms .= ($i === ($sz - 1) ? ")" : " OR ");

					}
				}
			}
		}

		private static function makePropPart(umiSelection $selection) {
			if($searchStrings = $selection->getSearchStrings()) {
				$tableName = "cms3_object_content";

				$elements = $selection->getHierarchyConds();
				$elements = array_extract_values($elements);
				if(sizeof($elements)) {
					$objectTypeId = umiHierarchy::getInstance()->getDominantTypeId(array_pop($elements));
					$tableName = umiBranch::getBranchedTableByTypeId($objectTypeId);
				} else {
					$types = $selection->getElementTypeConds();
					if(is_array($types) && sizeof($types)) {
						$hierarchyTypeId = array_pop($types);
						if($hierarchyTypeId == 21 && sizeof($types)) {
							$hierarchyTypeId = array_pop($types);
						}

						if(umiBranch::checkIfBranchedByHierarchyTypeId($hierarchyTypeId)) {
							$tableName .= "_" . $hierarchyTypeId;
						}
					}
				}


				$cname = "ct";

				$selection->sql_part__content_tables .= ", {$tableName} {$cname}";
				$selection->usedContentTables[] = $cname;

				$fileFields = self::getFileFields();
				if(sizeof($fileFields) > 0) {
					$fileFieldsCond = " AND ct.field_id NOT IN (" . implode(", ", $fileFields) . ")";
				} else {
					$fileFieldsCond = "";
				}

				$searchConds = Array();
				foreach($searchStrings as $searchString) {
					$searchString = l_mysql_real_escape_string($searchString);
					$intCond = (is_numeric($searchString)) ? " OR ct.float_val = '{$searchString}' OR ct.int_val = '{$searchString}'" : "";
					$searchConds[] = "o.name LIKE '%{$searchString}%' OR ct.varchar_val LIKE '%{$searchString}%' OR ct.text_val LIKE '%{$searchString}%' {$intCond}" . $fileFieldsCond;
				}

				$selection->sql_arr_for_and_or_part['where'][] = "ct.obj_id = o.id AND (" . implode(" OR ", $searchConds) . ")";
				$selection->sql_cond__need_content = true;
			}

			if ($arr_propconds = $selection->getPropertyConds()) {

				$prop_cond = array();
				foreach ($arr_propconds as $arr_cond) {
					if ($arr_cond['type'] !== false) $prop_cond[] = $arr_cond;
				}
				unset($arr_propconds);

				if ($sz = sizeof($prop_cond)) {

					$i = 0;
					for ($i = 0; $i < $sz; $i++) {
						$arr_next_cond = $prop_cond[$i];
						$s_filter_type = (isset($arr_next_cond['filter_type']) ? $arr_next_cond['filter_type'] : '');
						$v_value = (isset($arr_next_cond['value']) ? $arr_next_cond['value'] : null);
						$i_field_id = (isset($arr_next_cond['field_id']) ? $arr_next_cond['field_id'] : 0);
						$s_type = (isset($arr_next_cond['type']) ? $arr_next_cond['type'] : '');



						if($s_type == 'optioned') {
							if(!is_array($v_value)) continue;
							$keys = array_keys($v_value);
							if(sizeof($keys) == 0) continue;
							list($s_type) = $keys;

							if(in_array($s_type, array('int', 'float', 'varchar', 'tree', 'rel')) == false) continue;
							$s_type .= "_val";
						}

						/* ? */
						if (!$selection->getConditionModeOr() || $selection->sql_cond__content_tables_loaded == 0) {
							$cname = "c" . (++$selection->sql_cond__content_tables_loaded); // имя очередной таблицы

							$tableName = self::chooseContentTableName($selection, $prop_cond[$i]['field_id']);
							$selection->sql_part__content_tables .= ", {$tableName} {$cname}";
							$selection->usedContentTables[] = $cname;
						}

						$s_common = $cname.".obj_id = o.id AND ".$cname.".field_id = '".$i_field_id."'";
						if($s_type != 'optioned') {
							$s_field = $cname.".".$s_type;
						}

						switch ($s_filter_type) {

							case 'equal':
									if($v_value) {
										if (!is_array($v_value)) $v_value = array($v_value);
										$s_values = "'".(implode("', '", array_map('l_mysql_real_escape_string', $v_value)))."'";
										$s_next_cond = "(".$s_common." AND (".$s_field." IN (".$s_values.")))";
									} else {
										$v_value = l_mysql_real_escape_string($v_value);
										$s_next_cond = "({$s_common} AND ({$s_field} = '{$v_value}' OR {$s_field} IS NULL))";
									}

								break;

							case 'not_equal':
									if (!is_array($v_value)) $v_value = array($v_value);
									$s_values = "'".(implode("', '", array_map('l_mysql_real_escape_string', $v_value)))."'";

								$s_next_cond = "(".$s_common." AND ((".$s_field." IS NULL) OR (".$s_field." NOT IN (".$s_values."))))";
								break;

							case 'like':
									$b_need_percents = true;
									if (substr($v_value, 0, 1) === '%' || substr($v_value, -1) === '%') $b_need_percents = false;

									$s_value = l_mysql_real_escape_string($v_value);

								$s_next_cond = "(".$s_common." AND ".$s_field." LIKE '".($b_need_percents ? "%" : "").$s_value.($b_need_percents ? "%" : "")."')";
								break;

							case 'between':
									$f_min = (isset($arr_next_cond['min']) ? floatval($arr_next_cond['min']) : 0);
									$f_max = (isset($arr_next_cond['max']) ? floatval($arr_next_cond['max']) : 0);

								$s_next_cond = "(".$s_common." AND ".$s_field." BETWEEN '".$f_min."' AND '".$f_max."')";
								break;

							case 'more':
									$f_value = floatval($v_value);

								$s_next_cond = "(".$s_common." AND ".$s_field." >= '".$f_value."')";
								break;

							case 'less':
									$f_value = floatval($v_value);

								$s_next_cond = "(".$s_common." AND ".$s_field." <= '".$f_value."')";
								break;

							case 'null':
								$s_next_cond = "(".$s_common." AND ".$s_field." IS NULL)";
								break;

							case 'notnull':
								$s_next_cond = "(".$s_common." AND ".$s_field." IS NOT NULL)";
								break;

							default:
								$s_next_cond = "";
								break;
						}

						if (strlen($s_next_cond)) {
							$selection->sql_arr_for_and_or_part['where'][] = $s_next_cond;
						}

					}

					if (count($selection->sql_arr_for_and_or_part)) {
						$selection->sql_cond__need_content = true;
						$selection->sql_cond__total_joins += $i;
					}
				}
			}
		}

		private static function makeObjectTypePart(umiSelection $selection) {
			$object_type_cond = $selection->getObjectTypeConds();
			if ($object_type_cond && count($object_type_cond)) {
				$selection->sql_part__object_type = " AND o.type_id IN ('".implode("', '", $object_type_cond)."')";
				$selection->objectTableIsRequired = true;
			}
		}

		private static function makeOrderPart(umiSelection $selection) {
			$order_cond = $selection->getOrderConds();
			if ($order_cond) {
				$i = 0;
				$selection->sql_order_by = " ORDER BY ";
				$sz = sizeof($order_cond);

				for ($i = 0; $i < $sz; $i++) {
					if ($native_field = $order_cond[$i]['native_field']) {
						switch($native_field) {
							case "name": {
								$selection->sql_order_by .= "o.name " . (($order_cond[$i]['asc']) ? "ASC" : "DESC");
								$selection->sql_need_object_table = true;
								$selection->objectTableIsRequired = true;
								break;
							}

							case "object_id": {
								$selection->sql_order_by .= "o.id " . (($order_cond[$i]['asc']) ? "ASC" : "DESC");
								$selection->objectTableIsRequired = true;
								break;
							}

							case "rand": {
								$selection->sql_order_by .= "RAND()";
								break;
							}

							case "ord": {
								if($selection->objectTableIsRequired && !$selection->sql_cond__need_hierarchy) {
									$selection->sql_order_by .= "o.ord " . (($order_cond[$i]['asc']) ? "ASC" : "DESC");
								} else {
									$selection->sql_order_by .= "h.ord " . (($order_cond[$i]['asc']) ? "ASC" : "DESC");
								}
								break;
							}
						}

						if ($i !== ($sz - 1)) {
							$selection->sql_order_by .= ", ";
						}

					} else {

						$selection->sql_cond__need_content = true;

						$cname = "c" . (++$selection->sql_cond__content_tables_loaded);

						$tableName = self::chooseContentTableName($selection, $order_cond[$i]['field_id']);
						$selection->sql_part__content_tables .= ", {$tableName} {$cname}";
						$selection->usedContentTables[] = $cname;

						$selection->sql_arr_for_and_or_part['order'][] = "{$cname}.obj_id = o.id AND {$cname}.field_id = '{$order_cond[$i]['field_id']}'";


						$selection->sql_order_by .= "{$cname}.{$order_cond[$i]['type']} " . (($order_cond[$i]['asc']) ? "ASC" : "DESC");
						if ($i == ($sz - 1)) {
						} else {
							$selection->sql_order_by .= ", ";
						}
					}
				}

				if ($selection->sql_order_by == " ORDER BY ") {
					$selection->sql_order_by = "";
				}
				$selection->sql_cond__total_joins += $i;
			} elseif ($selection->sql_cond__need_hierarchy == true) {
				$selection->sql_order_by = " ORDER BY h.ord";
			}
		}

		private static function makeNamesPart(umiSelection $selection) {
			$arr_names_parts = array();
			if (($names_cond = $selection->getNameConds()) && count($names_cond)) {
				foreach ($names_cond as $arr_next_name) {

					$cname = $arr_next_name['value'];

					$b_need_percents = true;
					if (substr($cname, 0, 1) === '%' || substr($cname, -1) === '%') $b_need_percents = false;

					$cname = l_mysql_real_escape_string($cname);

					if ($arr_next_name['type'] == 'exact') {
						$arr_names_parts[] = "o.name = '".$cname."'";
					} else {
						$arr_names_parts[] = "o.name LIKE '".($b_need_percents ? "%" : "").$cname.($b_need_percents ? "%" : "")."'";
					}
					$selection->objectTableIsRequired = true;
				}
			}
			if (count($arr_names_parts)) {
				$selection->sql_arr_for_and_or_part['where'][] = "(".implode(' OR ', $arr_names_parts).")";
			}
		}

		private static function makePropsAndNames(umiSelection $selection) {
			$selection->sql_part__props_and_names = "";
			if($selection->sql_part__owner) {
				$selection->sql_arr_for_and_or_part['where'][] = $selection->sql_part__owner;
				$selection->sql_part__owner = "";
			}

			if (isset($selection->sql_arr_for_and_or_part['where'])) {
				$s_concat_mode = ($selection->getConditionModeOr() ? ' OR ' : ' AND ');
				$selection->sql_part__props_and_names = " AND (" . implode($s_concat_mode, $selection->sql_arr_for_and_or_part['where']) . ")";
			}

			if(isset($selection->sql_arr_for_and_or_part['order'])) {
					$selection->sql_part__props_and_names .= " AND (" . implode(" AND ", $selection->sql_arr_for_and_or_part['order']) . ")";
			}
		}

		private static function makeHierarchySpecificConds(umiSelection $selection) {
			if ($selection->sql_cond__need_hierarchy == true) {
				{
					if(!$selection->sql_cond__lang_ignored) {
						if(($langId = $selection->getLangId()) == false) {
							$langId = (int) cmsController::getInstance()->getCurrentLang()->getId();
						}
						$selection->sql_part__lang_cond = " AND h.lang_id = '" . $langId . "' ";
					}
				}

				if (!$selection->sql_cond__domain_ignored) {

					if(($domainId = $selection->getDomainId()) == false) {
						$domainId = cmsController::getInstance()->getCurrentDomain()->getId();
					}
					$selection->sql_part__domain_cond = " AND h.domain_id = '" . (int) $domainId . "' ";
				} else {
					$selection->sql_part__domain_cond = "";
				}

				if ($active_cond = $selection->getActiveConds()) {
					$is_active = (isset($active_cond[0]) && (bool) $active_cond[0])? 1 : 0;
					$selection->sql_part__unactive_cond = " AND h.is_active = '".$is_active."' ";
				} else {
					$selection->sql_part__unactive_cond = (cmsController::getInstance()->getCurrentMode() != "admin") ? " AND h.is_active = '1' " : "";
				}
			}
		}


		protected static function chooseContentTableName(umiSelection $selection, $fieldId) {
			$hierarchyTypes = $selection->getElementTypeConds();
			$objectTypes = $selection->getObjectTypeConds();

			if(!is_array($hierarchyTypes)) {
				$hierarchyTypes = Array();
			} else {
				$hierarchyTypes = array_extract_values($hierarchyTypes);
			}

			if(!is_array($objectTypes)) {
				$objectTypes = Array();
			} else {
				$objectTypes = array_extract_values($objectTypes);
			}

			if(sizeof($hierarchyTypes) == 1) {
				reset($hierarchyTypes);
				$hierarchyTypeId = current($hierarchyTypes);
				$isBranched = umiBranch::checkIfBranchedByHierarchyTypeId($hierarchyTypeId);
				return $isBranched ? "cms3_object_content_{$hierarchyTypeId}" : "cms3_object_content";
			}

			if(sizeof($hierarchyTypes) > 1) {
				$objectTypeId = self::getObjectTypeByFieldId($fieldId);
				return umiBranch::getBranchedTableByTypeId($objectTypeId);
			}

			if(sizeof($hierarchyTypes) == 0) {
				if(sizeof($objectTypes) == 1) {
					reset($objectTypes);
					$objectTypeId = current($objectTypes);
				} else {
					$objectTypeId = self::getObjectTypeByFieldId($fieldId);
				}
				return umiBranch::getBranchedTableByTypeId($objectTypeId);
			}

			return "cms3_object_content";
		}


		public static function getObjectTypeByFieldId($fieldId) {
			static $cache = Array();
			$fieldId = (int) $fieldId;

			if(isset($cache[$fieldId])) {
				return $cache[$fieldId];
			}

			$sql = <<<SQL
SELECT  MIN(fg.type_id)
	FROM cms3_fields_controller fc, cms3_object_field_groups fg
	WHERE fc.field_id = {$fieldId} AND fg.id = fc.group_id
SQL;
			if($objectTypeId = cacheFrontend::getInstance()->loadSql($sql)) {
				return $cache[$fieldId] = $objectTypeId;
			}

			$result = l_mysql_query($sql);

			if(mysql_num_rows($result)) {
				list($objectTypeId) = mysql_fetch_row($result);
			} else {
				$objectTypeId = false;
			}
			$cache[$fieldId] = $objectTypeId;
			cacheFrontend::getInstance()->saveSql($sql, $objectTypeId, 60);
			return $objectTypeId;
		}

		protected static function excludeNestedPages($arr) {
			$hierarchy = umiHierarchy::getInstance();

			$result = Array();
			foreach($arr as $elementId) {
				$element = $hierarchy->getElement($elementId);
				if($element instanceof umiHierarchyElement) {
					if(in_array($element->getRel(), $arr)) {
						continue;
					} else {
						$result[] = $elementId;
					}
				}
			}
			return $result;
		}


		protected static function getFileFields() {
			static $cache = false;
			if($cache) return $cache;

			$sql = <<<SQL
SELECT of.id
	FROM cms3_object_fields of, cms3_object_field_types oft
		WHERE of.field_type_id = oft.id
		AND oft.data_type IN ('file', 'img_file', 'swf_file')
SQL;
			$result = l_mysql_query($sql);

			$res = array();
			while(list($fieldId) = mysql_fetch_row($result)) {
				$res[] = $fieldId;
			}

			return $cache = $res;
		}
	};
?>
