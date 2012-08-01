<?php
	class selectorExecutor {
		protected
			$selector,
			$queryColumns = array(),
			$queryTables = array(),
			$queryJoinTables = array(),
			$queryLimit = array(),
			$queryFields = array(),
			$queryOptions = array(),

			$length = null,
			$skipExecutedCheck = false;

		public function __construct(selector $selector) {
			$this->selector = $selector;
			$this->analyze();
		}

		public function query() {
			return $this->buildQuery('result');
		}

		public function result() {
	 	 	$sql = $this->buildQuery('result');

			if(defined('DEBUG_SQL_SELECTOR')) {
				$buffer = outputBuffer::current();
				$buffer->push($sql . "\n\n\n");
			}

			$connection = ConnectionPool::getInstance()->getConnection();

			$result = $connection->query($sql);
			$return = $this->selector->option('return')->value;

			if(!DISABLE_CALC_FOUND_ROWS) {
				$countResult = l_mysql_query("SELECT FOUND_ROWS()", true);
				list($count) = mysql_fetch_row($countResult);
				mysql_free_result($countResult);
				$this->length = (int) $count;
			}

			if($this->selector->mode == 'objects') {
				$list = array();
				$objects = umiObjectsCollection::getInstance();
				while($row = mysql_fetch_row($result)) {
					list($objectId) = $row;
					if (is_array($return) && sizeof($return)) {
						if (sizeof($return) == 1 && $return[0] == 'id') {
							$list[] = array('id' => $objectId);
						}
						else {
							$object = $objects->getObject($objectId, array_slice($row, 1));
							$list_items = array();
							foreach ($return as $field_name) {
								switch ($field_name) {
									case "id": $list_items[$field_name] = $objectId; break;
									case "name": $list_items[$field_name] = $object->getName(); break;
									case "guid": $list_items[$field_name] = $object->getGUID(); break;
									default :
										$field = $object->getValue($field_name);
										$list_items[$field_name] = $field ? $field : false;
								}
							}
							$list[] = $list_items;
						}
					}
					else {
						$object = $objects->getObject($objectId, array_slice($row, 1));
						if($object instanceof iUmiObject) {
							$list[] = $object;
						}
					}
				}
				return $list;
			} else {
				$ids = array();

				while($row = mysql_fetch_assoc($result)) {
					$id = (int) $row['id'];
					$pid = isset($row['pid']) ? (int) $row['pid'] : 0;
					$ids[$id] = $pid;
				}

				if($this->selector->option('exclude-nested')->value) {
					$listIds = $this->excludeNestedPages($ids);
					$this->length = sizeof($listIds);
					if($this->selector->limit || $this->selector->offset) {
						$listIds = array_slice($listIds, $this->selector->offset, $this->selector->limit);
					}
				} else {
					$listIds = array_keys($ids);
				}

				$list = array();
				if(count($listIds)) {
					$sql = "SELECT h.id, h.rel, h.type_id, h.lang_id, h.domain_id, h.tpl_id, h.obj_id, h.ord, h.alt_name, h.is_active, h.is_visible, h.is_deleted, h.updatetime, h.is_default, o.name FROM cms3_hierarchy h, cms3_objects o WHERE h.id IN (" . implode(',', $listIds) . ") AND o.id = h.obj_id";
					$result = $connection->queryResult($sql);
					$result->setFetchType(IQueryResult::FETCH_ROW);
                    $list = array_flip($listIds);
					$hierarchy = umiHierarchy::getInstance();

					foreach($result as $row) {
						$elementId = array_shift($row);
						if (sizeof($return)) {
							if (sizeof($return) == 1 && $return[0] == 'id') {
								$list[$elementId] = array('id' => $elementId);
							}
							else {
								$element = $hierarchy->getElement($elementId);
								$list_items = array();
								foreach ($return as $field_name) {
									switch ($field_name) {
										case "id": $list_items[$field_name] = $elementId; break;
										case "name": $list_items[$field_name] = $element->getName(); break;
										case "alt_name": $list_items[$field_name] = $element->getAltName(); break;
										default :
											$field = $element->getValue($field_name);
											$list_items[$field_name] = $field ? $field : false;
									}
								}
								$list[$elementId] = $list_items;
							}
						}
						else {
							$element = $hierarchy->getElement($elementId, false, false, $row);
							if($element instanceof iUmiHierarchyElement) {
								$list[$elementId] = $element;
							}
						}
					}
					$list = array_values($list);
				}

				return $list;
			}
		}

		public function length() {
			if(!is_null($this->length)) {
				return $this->length;
			}

			$this->skipExecutedCheck = true;
			$sql = $this->buildQuery('count');
			$this->skipExecutedCheck = false;

			$result = l_mysql_query($sql);
			list($count) = mysql_fetch_row($result);
			return $this->length = (int) $count;
		}

		public static function getContentTableName(selector $selector, $fieldId) {
			if(!is_null($fieldId) && self::getFieldColumn($fieldId) == 'cnt') {
				return 'cms3_object_content_cnt';
			}

			$objectTypes = array();
			$hierarchyTypes = array();

			$types = $selector->types;
			foreach($types as $type) {
				if(is_null($type->objectType) == false) $objectTypes[] = $type->objectType->getId();

				if(is_null($type->hierarchyType) == false) {
					$hierarchyType = $type->hierarchyType;
					if($hierarchyType->getModule() == 'comments') continue;
					$hierarchyTypes[] = $hierarchyType->getId();
				}
			}

			if(sizeof($objectTypes)) {
				return umiBranch::getBranchedTableByTypeId(array_pop($objectTypes));
			}

			if(sizeof($hierarchyTypes)) {
				$hierarchyTypeId = array_pop($hierarchyTypes);
				if(umiBranch::checkIfBranchedByHierarchyTypeId($hierarchyTypeId)) {
					return 'cms3_object_content_' . $hierarchyTypeId;
				}
			}

			return 'cms3_object_content';
		}

		public function getSkipExecutedCheckState() {
			return $this->skipExecutedCheck;
		}

		protected function analyze() {
			$selector = $this->selector;
			switch($selector->mode) {
				case 'objects':
					$this->requireTable('o', 'cms3_objects');
					$this->requireTable('t', 'cms3_object_types');
					break;
				case 'pages':
					$this->requireTable('h', 'cms3_hierarchy');
					break;
			}
			$this->analyzeFields();
			$this->analyzeLimit();
		}

		protected function requireTable($alias, $tableName) {
			$this->queryTables[$alias] = $tableName;
		}

		protected function requireSysProp($propName) {
			$propTable = array();
			$propTable['name'] = array('o.name', 'table' => array('o', 'cms3_objects'));
			$propTable['guid'] = array('o.guid', 'table' => array('o', 'cms3_objects'));
			$propTable['owner'] = array('o.owner_id', 'table' => array('o', 'cms3_objects'));
			$propTable['domain'] = array('h.domain_id');
			$propTable['lang'] = array('h.lang_id');
			$propTable['is_deleted'] = array('h.is_deleted');
			$propTable['is_default'] = array('h.is_default');
			$propTable['is_visible'] = array('h.is_visible');
			$propTable['is_active'] = array('h.is_active');
			$propTable['domain'] = array('h.domain_id');
			$propTable['updatetime'] = array('h.updatetime');
			$propTable['rand'] = array('RAND()');
			$propTable['template_id'] = array('h.tpl_id');

			if($this->selector->mode == 'pages') {
				$propTable['ord'] = array('h.ord', 'table' => array('h', 'cms3_hierarchy'));
			}


			if($propName == 'id') {
				$propTable['id'] = array('o.id', 'table' => array('o', 'cms3_objects'));
			};

			if(isset($propTable[$propName])) {
				$info = $propTable[$propName];
				if(isset($info['table'])) {
					$this->requireTable($info['table'][0], $info['table'][1]);
				}
				return $info[0];
			} else {
				throw new selectorException("Not supported property \"{$propName}\"");
			}
		}

		protected function analyzeFields() {
			$selector = $this->selector;

			$selectorFields = $selector->whereFieldProps;
			$fields = array();
			foreach($selectorFields as $field) {
				$fields[] = $field->fieldId;
			}
			$fields = array_unique($fields);
			foreach($fields as $fieldId) {
				$tableName = self::getContentTableName($selector, $fieldId);

				$this->requireTable('oc_' . $fieldId, $tableName);
				$this->queryFields[] = $fieldId;
			}

			//TODO: Attach tables, required by sys props
			//$selectorSysProps = array_merge($selector->whereSysProps, $selector->orderSysProps);
		}

		protected function analyzeLimit() {
			$selector = $this->selector;

			if($selector->option('exclude-nested')->value) {
				return;
			}

			if($selector->limit || $selector->offset) {
				$this->queryLimit = array((int) $selector->offset, (int) $selector->limit);
			}
		}

		protected function buildQuery($mode) {
			if($mode == 'result') {
				if($this->selector->mode == 'objects') {
					$this->queryColumns = array('o.id as id', 'o.name as name', 'o.type_id as type_id', 'o.is_locked as is_locked', 'o.owner_id as owner_id', 'o.guid as guid', 't.guid as type_guid');
				} else {
					$this->queryColumns = array('h.id as id', 'h.rel as pid');
				}
			} else {
				$this->queryColumns = ($this->selector->mode == 'objects') ? array('COUNT(o.id)') : array('COUNT(h.id)');
			}


			if($this->selector->option('root')->value) {
				return $this->buildRootQuery($mode);
			}

			$columnsSql = $this->buildColumns();
			$limitSql = $this->buildLimit();
			$orderSql = $this->buildOrder();
			$whereSql = $this->buildWhere();
			$ljoinSql = $this->buildLeftJoins();
			$tablesSql = $this->buildTables();
			$optionsSql = $this->buildOptions($mode);

			return <<<SQL
SELECT {$optionsSql} {$columnsSql}
	FROM {$tablesSql}
	{$ljoinSql}
	{$whereSql}
	{$orderSql}
	{$limitSql}
SQL;
		}

		protected function buildOptions($mode) {
			$queryOptions = $this->queryOptions;
			$queryOptions = array_unique($queryOptions);

			if(MAX_SELECTION_TABLE_JOINS > 0 && MAX_SELECTION_TABLE_JOINS < sizeof($this->queryJoinTables)) {
				$queryOptions[] = 'STRAIGHT_JOIN';
			}

			if($mode == 'result') {
				if(!DISABLE_CALC_FOUND_ROWS) {
					$queryOptions[] = 'SQL_CALC_FOUND_ROWS';
				}
			}

			return implode(' ', $queryOptions);
		}

		protected function buildLeftJoins() {
			$joins = array();
			$data_joins = array_merge($this->selector->orderFieldProps, $this->selector->whereFieldProps);
			$fieldsId = array();
			foreach($data_joins as $data_join) {
				$fieldId = $data_join->fieldId;
				if (!in_array($fieldId, $fieldsId)) {
					$this->requireTable('o', 'cms3_objects');
					$tableName = self::getContentTableName($this->selector, $fieldId);
					$join = "LEFT JOIN {$tableName} oc_{$fieldId}_lj ON oc_{$fieldId}_lj.obj_id=o.id AND oc_{$fieldId}_lj.field_id = '{$fieldId}'";
					$joins[] = $join;
					$fieldsId[] = $fieldId;
					$this->queryJoinTables[] = $tableName;
				}
			}

			return empty($joins) ? "" : implode(" ", $joins);
		}

		protected function buildColumns() { return implode(', ', $this->queryColumns); }

		protected function buildTables() {
			$tables = array();
			$joinObjectsTable = false;
			foreach($this->queryTables as $alias => $name) {
				if ($name == 'cms3_objects' && $joinObjectsTable === false) {
					$joinObjectsTable = $alias;
					continue;
				}
				if ($name == 'cms3_object_content' && $alias != 'o_asteriks') continue;
				$tables[] = $name . ' ' . $alias;
			}
			if($joinObjectsTable !== false) {
				$tables[] = $this->queryTables[$joinObjectsTable] . ' ' . $joinObjectsTable;
			}
			return implode(', ', $tables);
		}

		protected function buildLimit() {
			if(sizeof($this->queryLimit)) {
				return " LIMIT {$this->queryLimit[0]}, {$this->queryLimit[1]}";
			} else {
				return "";
			}
		}

		protected function buildWhere() {
			$sql = "";

			$conds = array();
			//Types
			$objectTypes = array(); $hierarchyTypes = array();

			foreach($this->selector->types as $type) {
				if(is_null($type->objectType) == false) $objectTypes[] = $type->objectType->getId();
				if(is_null($type->hierarchyType) == false) $hierarchyTypes[] = $type->hierarchyType->getId();
			}

			if(sizeof($objectTypes)) {
				$this->requireTable('o', 'cms3_objects');
				$this->requireTable('t', 'cms3_object_types');

				$typesCollection = umiObjectTypesCollection::getInstance();
				$subTypes = array();
				foreach($objectTypes as $objectTypeId) {
					$subTypes = array_merge($subTypes, $typesCollection->getChildClasses($objectTypeId));
				}

				$objectTypes = array_unique(array_merge($objectTypes, $subTypes));

				$conds[] = 'o.type_id IN (' . implode(', ', $objectTypes) . ')';
				$conds[] = 't.id = o.type_id';
			}

			if(sizeof($hierarchyTypes)) {
				$hierarchyTypes = array_unique($hierarchyTypes);
				$conds[] = 'h.type_id IN (' . implode(', ', $hierarchyTypes) . ')';
			}

			if(sizeof($this->queryFields)) {
				$this->requireTable('o', 'cms3_objects');
			}

			//Field props
			$fieldsColl     = umiFieldsCollection::getInstance();
			$or_mode        = $this->selector->option('or-mode');
			$where_conds    = array();
			$where_or_conds = array();
			$where_conditions = '';
			foreach($this->queryFields as $fieldId) {
				if (isset($or_mode->value['fields']) && in_array($fieldsColl->getField($fieldId)->getName(), $or_mode->value['fields'])) {
					$where_or_conds[] = $this->buildWhereValue($fieldId);
				}
				else $where_conds[] = $this->buildWhereValue($fieldId);
			}
			if(sizeof($where_conds) || sizeof($where_or_conds)) {
				if (isset($or_mode->value['all'])) {
					$where_conds = array_merge($where_conds, $where_or_conds);
					$where_conditions = implode(' OR ', $where_conds);
				}
				else {
					if (sizeof($where_conds)) {
						$where_conditions .= implode(' AND ', $where_conds);
						if (sizeof($where_or_conds)) $where_conditions .= ' AND ';
					}
					if (sizeof($where_or_conds)) $where_conditions .= implode(' OR ', $where_or_conds);
				}
				$conds[] = '('. $where_conditions . ')';
			}

			//Sys props
			$sysProps = $this->selector->whereSysProps;
			foreach($sysProps as $sysProp) {
				if($cond = $this->buildSysProp($sysProp)) {
					$conds[] = $cond;
				}
			}

			if($this->selector->mode == 'pages') {
				if($permConds = $this->buildPermissions()) {
					$conds[] = $permConds;
				}

				if($hierarchyConds = $this->buildHierarchy()) {
					$conds[] = $hierarchyConds;
				}

				if(isset($this->queryTables['o'])) {
					$conds[] = "h.obj_id = o.id";
				}
			}

			$sql .= implode(' AND ', $conds);
			if($sql) $sql = "WHERE " . $sql;
			return $sql;
		}

		protected function buildWhereValue($fieldId) {
			$wheres = $this->selector->whereFieldProps;
			$current = array();
			foreach($wheres as $where) {
				if($where->fieldId == $fieldId) $current[] = $where;
			}

			$column = self::getFieldColumn($fieldId);

			$sql = ""; $conds = array();
			foreach($current as $where) {
				if($column === false) {
					if(sizeof($where->value) == 1) {
						$keys = array_keys($where->value);
						$column = array_pop($keys) . '_val';
					} else continue;
				}
				$condition = $this->parseValue($where->mode, $where->value, "oc_{$fieldId}_lj.{$column}");
				$conds[] = ($where->mode == 'notequals') ? "(oc_{$fieldId}_lj.{$column}{$condition})" : "oc_{$fieldId}_lj.{$column}{$condition}";
			}
			$field = umiFieldsCollection::getInstance()->getField($fieldId);
			$or_mode = $this->selector->option('or-mode');
			if (isset($or_mode->value['all']) || (isset($or_mode->value['field']) && in_array($field->getName(), $or_mode->value['field']))) {
				$quantificator = ' OR ';
				$this->queryOptions[] = 'DISTINCT';
			} else {
				$quantificator = ' AND ';
			}
			$sql = implode($quantificator, array_unique($conds));
			return $sql ? (sizeof($conds) > 1 ? "(" . $sql . ")" : $sql) : "";
		}

		protected function parseValue($mode, $value, $column = false) {
			switch($mode) {
				case 'equals':
					if(is_array($value) || is_object($value)) {
						$value = $this->escapeValue($value);
						if(sizeof($value)) {
							return ' IN(' . implode(', ', $value) . ')';
						} else {
							return ' = 0 = 1';	//Impossible value to reset query result to zero
						}
					}
					else
						return ' = ' . $this->escapeValue($value);
					break;

				case 'notequals':
					if(is_array($value) || is_object($value)) {
						$value = $this->escapeValue($value);
						if(sizeof($value)) {
							return ' NOT IN(' . implode(', ', $value) . ')' . ($column ? " OR {$column} IS NULL" : "");
						} else {
							return ' = 0 = 1';	//Impossible value to reset query result to zero
						}
					}
					else
						return ' != ' . $this->escapeValue($value) . ($column ? " OR {$column} IS NULL" : "");
					break;


				case 'like':
					if(is_array($value)) throw new selectorException("Method \"{$mode}\" can't accept array");
					else return ' LIKE ' . $this->escapeValue($value);

				case 'ilike':
					if(is_array($value)) throw new selectorException("Method \"{$mode}\" can't accept array");
					else return ' LIKE ' . $this->escapeValue($value);

				case 'more':
					if(is_array($value)) throw new selectorException("Method \"{$mode}\" can't accept array");
					else return ' > ' . $this->escapeValue($value);

				case 'eqmore':
					if(is_array($value)) throw new selectorException("Method \"{$mode}\" can't accept array");
					else return ' >= ' . $this->escapeValue($value);

				case 'less':
					if(is_array($value)) throw new selectorException("Method \"{$mode}\" can't accept array");
					else return ' < ' . $this->escapeValue($value);

				case 'eqless':
					if(is_array($value)) throw new selectorException("Method \"{$mode}\" can't accept array");
					else return ' <= ' . $this->escapeValue($value);

				case 'between':
					return ' BETWEEN ' . $this->escapeValue($value[0]) . ' AND ' . $this->escapeValue($value[1]);

				case 'isnotnull':
					$value != $value;
				case 'isnull':
					return ($value) ? ' IS NULL' : ' IS NOT NULL';

				default:
					throw new selectorException("Unsupported field mode \"{$mode}\"");
			}
		}

		protected function buildSysProp($prop) {
			if($prop->name == 'domain' || $prop->name == 'lang') {
				if($prop->value === false) return false;
			}

			if($prop->name == 'domain'){
				$arr_hierarchy = $this->selector->hierarchy;
				if(sizeof($arr_hierarchy) && $arr_hierarchy[0]->elementId) {
					return false;
				}
			}

			if($prop->name == '*') {
				$this->requireTable('o_asteriks', 'cms3_object_content');
				$this->requireTable('o', 'cms3_objects');

				$alias = self::getContentTableName($this->selector, null);
				$tables = array('o_asteriks');
				if($alias != 'cms3_object_content') {
					$this->requireTable('o_asteriks_branched', $alias);
					$tables[] = 'o_asteriks_branched';
				}
				$this->queryOptions[] = 'DISTINCT';


				$conds = array();
				foreach($tables as $tableName) {


					$values = $prop->value;
					if(!is_array($values)) $values = array($values);
					$sconds = array();
					foreach($values as $value) {
						$evalue = $this->escapeValue('%' . $value . '%');

						$sconds[] = $tableName . '.varchar_val LIKE ' . $evalue;
						$sconds[] = $tableName . '.text_val LIKE ' . $evalue;
						$sconds[] = 'o.name LIKE ' . $evalue;

						if(is_numeric($value)) {
							$sconds[] = $tableName . '.float_val = ' . $evalue;
							$sconds[] = $tableName . '.int_val = ' . $evalue;
						}
					}

					$conds[] = '(' . $tableName . '.obj_id = o.id AND (' . implode(' OR ', $sconds) . '))';
				}

				$sql = '(' . implode(' OR ', $conds) . ')';
				return $sql;

			} else {
				$name = $this->requireSysProp($prop->name);
				$sql = "{$name}" . $this->parseValue($prop->mode, $prop->value, $name);
				return ($prop->mode == 'notequals') ? '('.$sql.')' : $sql;
			}
		}

		protected function buildOrder() {
			$sql = "";
			$conds = array();
			foreach($this->selector->orderFieldProps as $order) {
				$fieldId = $order->fieldId;
				$column = self::getFieldColumn($fieldId);
				$conds[] = "oc_{$fieldId}_lj.{$column} " . ($order->asc ? 'ASC' : 'DESC');
			}

			foreach($this->selector->orderSysProps as $order) {
				$name = $this->requireSysProp($order->name);
				$conds[] = $name . ' ' . ($order->asc ? 'ASC' : 'DESC');
			}

			$sql = implode(', ', $conds);
			return $sql ? "ORDER BY " . $sql : "";
		}

		protected function buildPermissions() {
			$permissions = $this->selector->permissions;
			$owners = $permissions->owners;
			if($permissions && sizeof($owners)) {
				$this->requireTable('p', 'cms3_permissions');
				$this->queryOptions[] = 'DISTINCT';
				$guestId = umiObjectsCollection::getInstance()->getObjectIdByGUID('system-guest');
				if (!in_array($guestId, $owners)) $owners[] = $guestId;
				$owners = implode(', ', $owners);
				return "(p.rel_id = h.id AND p.level & {$permissions->level} AND p.owner_id IN({$owners}))";
			} else return "";
		}

		protected function buildHierarchy() {
			$hierarchy = $this->selector->hierarchy;
			if(sizeof($hierarchy) == 0) return "";

			$this->requireTable('hr', 'cms3_hierarchy_relations');

			$sql = "h.id = hr.child_id AND ";
			$harr = array();
			foreach($hierarchy as $condition) {
				if($condition->elementId > 0)
					$hsql = "(hr.level <= {$condition->level} AND hr.rel_id";
				else
					$hsql = "(hr.level < {$condition->level} AND hr.rel_id";
				$hsql .= ($condition->elementId > 0) ? " = '{$condition->elementId}'" : " IS NULL";
				$hsql .= ")";
				$harr[] = $hsql;
			}
			if (sizeof($harr) > 1) $sql .= "(";
			$sql .= implode(' OR ', $harr);
			if (sizeof($harr) > 1) $sql .= ")";
			return $sql;
		}

		protected static function getFieldColumn($fieldId) {
			static $cache = array();
			if(isset($cache[$fieldId])) return $cache[$fieldId];

			$field = umiFieldsCollection::getInstance()->getField($fieldId);
			switch($field->getDataType()) {
				case 'string':
				case 'password':
				case 'tags':
					return $cache[$fieldId] = 'varchar_val';

				case 'int':
				case 'boolean':
				case 'date':
					return $cache[$fieldId] = 'int_val';

				case 'counter':
					return $cache[$fieldId] = 'cnt';

				case 'price':
				case 'float':
					return $cache[$fieldId] = 'float_val';

				case 'text':
				case 'wysiwyg':
				case 'file':
				case 'img_file':
				case 'swf_file':
					return $cache[$fieldId] = 'text_val';

				case 'relation':
					return $cache[$fieldId] = 'rel_val';

				case 'symlink':
					return $cache[$fieldId] = 'tree_val';

				case 'optioned': return false;

				default:
					throw new selectorException("Unsupported field type \"{$field->getDataType()}\"");
			}
		}

		protected function escapeValue($value) {
			if(is_array($value)) {
				foreach($value as $i => $val) $value[$i] = $this->escapeValue($val);
				return $value;
			} if ($value instanceof selector) {
				return $this->escapeValue($value->result());
			} if ($value instanceof iUmiObject || $value instanceof iUmiHierarchyElement) {
				return $value->id;
			} else {
				return "'" . l_mysql_real_escape_string($value) . "'";
			}
		}

		protected function buildRootQuery($mode) {
			$columnsSql = $this->buildColumns();
			$limitSql = $this->buildLimit();
			$orderSql = $this->buildOrder();
			$whereSql = $this->buildWhere();
			$tablesSql = $this->buildTables();
			$optionsSql = $this->buildOptions($mode);

			$types = array();
			foreach($this->selector->types as $type) {
				if($type->hierarchyType) $types[] = $type->hierarchyType->getId();
			}
			$typesSql = implode(', ', $types);

			$columnsSql = ($mode == 'result') ? 'DISTINCT h.id' : 'COUNT(DISTINCT h.id)';

			$sql = <<<SQL
SELECT $columnsSql
	FROM cms3_hierarchy hp, {$tablesSql}
	{$whereSql}
	AND (h.rel = 0 OR (h.rel = hp.id AND hp.type_id NOT IN ({$typesSql})))
		{$orderSql}
		{$limitSql}
SQL;
			return $sql;
		}

		protected function excludeNestedPages($ids) {
			$arr = array();
			foreach ($ids as $id => $pid) {
				if (!isset($ids[$pid])) {
					$arr[] = $id;
				}
			}

			return $arr;
		}
	};
?>
