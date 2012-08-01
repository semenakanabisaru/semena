<?php
	function checkMysqlError() {
		if($err = l_mysql_error()) {
			echo $err, "<br />";
		}
	}

	function getAllParents($id) {
		$parents = Array();
		
		while($id) {
			$sql = "SELECT rel FROM cms3_hierarchy WHERE id = '{$id}'";
			$result = l_mysql_query($sql);
			checkMysqlError();
			if(mysql_num_rows($result)) {
				list($id) = mysql_fetch_row($result);
				if(!$id) continue;
			
				if(in_array($id, $parents)) {
					break;	//Infinity recursion
				}
				
				$parents[] = $id;
			} else {
				return false;
			}
		}
		return array_reverse($parents);
	}

	function makeHierarchyRelationsTable($id) {
		$parents = getAllParents($id);
		
		$level = sizeof($parents);
		
		//First-level for every element required
		$sql = "INSERT INTO cms3_hierarchy_relations (rel_id, child_id, level) VALUES (NULL, '{$id}', '{$level}')";
		l_mysql_query($sql);
		checkMysqlError();
		
		foreach($parents as $parent_id) {
			$sql = "INSERT INTO cms3_hierarchy_relations (rel_id, child_id, level) VALUES ('{$parent_id}', '{$id}', '{$level}')";
			l_mysql_query($sql);
			checkMysqlError();
		}
	}


	if(!defined("DB_DRIVER")) {
		$result = l_mysql_query("SELECT rel_id, child_id, level FROM cms3_hierarchy_relations LIMIT 1");
		if($err = l_mysql_error()) {
			l_mysql_query("ALTER TABLE cms3_object_fields ADD is_required tinyint(1) default NULL");
	
			$sql = <<<SQL
DROP TABLE IF EXISTS cms3_hierarchy_relations
SQL;
			l_mysql_query($sql);
			checkMysqlError();
		
			$sql = <<<SQL
CREATE TABLE `cms3_hierarchy_relations` (
	`rel_id` INT UNSIGNED DEFAULT NULL,
	`child_id` INT UNSIGNED,
	`level` INT UNSIGNED,
	
	KEY `rel_id` (`rel_id`),
	KEY `child_id` (`child_id`),
	KEY `level` (`level`),
	
	CONSTRAINT `Hierarchy relation by rel_id` FOREIGN KEY (`rel_id`) REFERENCES `cms3_hierarchy` (`id`) ON UPDATE CASCADE,
	CONSTRAINT `Hierarchy relation by child_id` FOREIGN KEY (`child_id`) REFERENCES `cms3_hierarchy` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8
SQL;
			l_mysql_query($sql);
			checkMysqlError();
			
			$sql = "SELECT id FROM cms3_hierarchy";
			$result = l_mysql_query($sql);
			checkMysqlError();
			while(list($id) = mysql_fetch_row($result)) {
				makeHierarchyRelationsTable($id);
			}
		}
		
	}
?>