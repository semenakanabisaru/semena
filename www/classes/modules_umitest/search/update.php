<?php
	if(!defined("DB_DRIVER")) {
		l_mysql_query("SELECT tf FROM cms3_search_index LIMIT 1");
		if(l_mysql_error()) {
		    l_mysql_query("TRUNCATE TABLE cms3_search_index");
		    l_mysql_query("TRUNCATE TABLE cms3_search_index_words");
			l_mysql_query("TRUNCATE TABLE cms3_search");
			
			l_mysql_query("ALTER TABLE cms3_search_index ADD tf float default null");
			l_mysql_query("ALTER TABLE cms3_search_index ADD INDEX(tf)");
    	}
	}
?>