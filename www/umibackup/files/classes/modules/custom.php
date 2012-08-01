<?php
	class custom extends def_module {
		public function cms_callMethod($method_name, $args) {
			return call_user_func_array(Array($this, $method_name), $args);
		}
		
		public function __call($method, $args) {
			throw new publicException("Method " . get_class($this) . "::" . $method . " doesn't exists");
		}
		//TODO: Write your own macroses here

		public function order_by_new($fieldName, $typeId, $template = "default") {
			$from = Array('%5B', '%5D');
			$to = Array('[', ']');
			$result = self::generateOrderBy_new($fieldName, $typeId, $template);
			$result = str_replace($from, $to, $result);
			return $result;
		}

		public static function generateOrderBy_new($fieldName, $type_id, $template = "default") {
			if (!$template) { $template = "default"; }
			
			list($template_block, $template_block_a1, $template_block_a2) 
			= def_module::loadTemplates("tpls/numpages/{$template}.tpl", "order_by", "order_by_a", "order_by_b");
			
			if( !($type = umiObjectTypesCollection::getInstance()->getType($type_id)) ) {
				return "";
			}
		 	
			$block_arr = Array();
			if( ($field_id = $type->getFieldId($fieldName)) || ($fieldName == "name") ) {
				$params = $_GET;
				unset($params['path']);
				$order_filter = getArrayKey($params, 'order_filter');
				
				$tpl = 0;      
				if(is_array($order_filter)) {
					if (array_key_exists($fieldName, $order_filter)) {
						if ($order_filter[$fieldName] == 1) {
							$tpl = $template_block_a1;
							unset($params['order_filter']);
							$params['order_filter'][$fieldName] = 0;
							$block_arr['dir'] = 'desc';
						} else {
							$tpl = $template_block_a2;
							unset($params['order_filter']);
							$params['order_filter'][$fieldName] = 1;
							$block_arr['dir'] = 'asc';
						}
					} else {
						unset($params['order_filter']);
						$params['order_filter'][$fieldName] = 1;
						$tpl = $template_block;
					}
				} else {
					unset($params['order_filter']);
					$params['order_filter'][$fieldName] = 1;
					$tpl = $template_block;
				}
		 
				$params = self::protectParams($params);
				$q = (sizeof($params)) ? "&" . http_build_query($params) : ""; 
				$q = urldecode($q);
				$q = str_replace("%", "&#037;", $q);
		 
				$block_arr['link'] = "?" . $q;
		 		
				if($fieldName == "name") {
					$block_arr['title'] = getLabel('field-name');
				} else {
					$block_arr['title'] = umiFieldsCollection::getInstance()->getField($field_id)->getTitle();
				}    
				
				return def_module::parseTemplate($tpl, $block_arr);
			}
			return "";
		}


		protected static function protectParams($params) {
			foreach($params as $i => $v) {
				if(is_array($v)) {
					$params[$i] = self::protectParams($v);
				} else {
					$v = htmlspecialchars($v);
					$params[$i] = str_replace("%", "&#037;", $v);
				}
			}
			return $params;
		}



		public function mySelection() {
			$selection = new umiSelection();

			// Установим тип объектов "пользователи"
			$type_id = umiObjectTypesCollection::getInstance()->getBaseType('catalog', 'object');
			$selection->addObjectType($type_id);

			// Установим фильтрацию по значению поля e-mail
			$oType    = umiObjectTypesCollection::getInstance()->getType($type_id);
			$field_id = $oType->getFieldId('greenhouse');
			$selection->addPropertyFilterEqual($field_id, '1');

			// Наконец произведем выборку
			$results = umiSelectionsParser::runSelection($selection);

			$block_arr = Array();
			$total = umiSelectionsParser::runSelectionCounts($selection);
			$lines = Array();

			$oHierarchy = umiHierarchy::getInstance();
			// Обработаем результат
			foreach($results as $result) {

				$element = $oHierarchy->getElement($result);
				$line_arr = Array(); 
				$line_arr['attribute:id'] = $result;
				// $line_arr['attribute:id'] = $result;
				// $line_arr['attribute:link'] = umiHierarchy::getInstance()->getPathById($result);
				// $line_arr['attribute:title'] = $element->getName();
				$lines[] = self::parseTemplate($template_line, $line_arr, $result);
			}

			$block_arr['subnodes:items'] = $lines;
			return self::parseTemplate($template_block, $block_arr);

		}
		public function mailmy() {
 
 $mailContent = '
<p>
 Уважаемые пользователи UMI.CMS! Сегодня настал тот день, который мы все так долго ждали: 
 Мировая Империя Юмисофт достигла мирового господства на рынке CMS и захватила весь мир.
</p>';
 
 $mail = new umiMail;
 //Выставляем получателей письма
 $mail->addRecipient("basurovav@yandex.ru", "Dear Mr. Somebody");
 $mail->addRecipient("basurovav@yandex.ru", "Dear Mrs. Somebody");
 
 //Указываем, от чьего имени придет письмо
 $mail->setFrom("basurovav@yandex.ru", "umisoft world wide empire");
 
 //Устанавливаем заголовок письма
 $mail->setSubject("Мы достигли мирового господства на рынке CMS!");
 
 //Укажем, что это очень важное письмо
 $mail->setPriorityLevel('highest');
 
 //Устанавливаем содержание письма
 $mail->setContent($mailContent);
 
 //Подтверждаем отправку письма
 $mail->commit();
 
 //Отправляем письмо. Если не выполнить send(), то письмо все равно отправится. Но где-то во время завершения работы скрипта.
 $mail->send();
 echo 'hi';
 return 'ho';
		}







	// --------------------------------------------- //
	//                                               //
	//    функция расширенного поиска по каталогу    //
	//                                               //
	// --------------------------------------------- //
	// ищет по условию OR внутри группы и по условию AND между группами
	public function myAdvancedSearch($per_page) { 

		if(def_module::breakMe()) return;
		list($template_block, $template_block_empty, $template_block_search_empty, $template_line) = def_module::loadTemplates("tpls/catalog/{$template}.tpl", "objects_block", "objects_block_empty", "objects_block_search_empty", "objects_block_line");

		// ///////////////////////////////////////////////////////////////////////////////////////////////////// //
		//      	   настраиваем, формируем и выполняем sql запрос. полностью свой кусок 					     //
		// ///////////////////////////////////////////////////////////////////////////////////////////////////// //

		// берем $per_page из аргумента, либо из реестра каталога (настройка модуля каталог)
    	$per_page_base = regedit::getInstance()->getVal("//modules/catalog/per_page");
		$per_page = ($per_page) ? $per_page : $per_page_base;

		// $curr_page - текущая страница, параметр нужен для выборки данных для нужной страницы  
		$curr_page = intval(getRequest('p'));

		// id шаблона данных сортов
		$type_id = 116;
		// экземпляр это типа 
    	$objType = umiObjectTypesCollection::getInstance()->getType($type_id);

		// ограничение по производителям
		$producers = explode(',', getRequest('producers'));
		$producers_good = array();
		foreach( $producers as $producer) {
			$producer = (int)$producer;
			if ( $producer > 0 ) {
				$producers_good[] = $producer;
			}
		}
		$producers_term = '';
		if ( count($producers_good) > 0 ) {
			$producers_term = "AND h.rel IN (".implode(',', $producers_good).")";
		}

		// ограничение по категории
		$cats = explode(',', getRequest('cats'));
		$cats_good = array();
		foreach( $cats as $cat ) {
			$cat_id = $objType->getFieldId($cat);
			if ( is_numeric($cat_id) ) {
				$cats_good[] = $cat_id;
			}
		}
		$cats_str_1 = $cats_str_2 = '';
		if ( count($cats_good) > 0 ) {
			$cats_str_1 = ", cms3_object_content AS c1 ";
			$cats_str_2 = "AND objs.id=c1.obj_id AND c1.field_id IN (".implode(',', $cats_good).") AND c1.int_val = 1 ";
		}

		// ограничение по типу
		$types = explode(',', getRequest('types'));
		$types_good = array();
		foreach( $types as $type ) {
			$ftype_id = $objType->getFieldId($type);
			if ( is_numeric($ftype_id) ) {
				$types_good[] = $ftype_id;
			}
		}
		$types_str_1 = $types_str_2 = '';
		if ( count($types_good) > 0 ) {
			$types_str_1 = ", cms3_object_content AS c2 ";
			$types_str_2 = "AND objs.id=c2.obj_id AND c2.field_id IN (".implode(',', $types_good).") AND c2.int_val = 1 ";
		}

		// ограничение по цене
		$price = getRequest('price');
		$price_str_1 = $price_str_2 = '';
		if ( is_array($price) ) {

			$price_from = (int)$price[0];
			$price_to = (int)$price[1];

			$price_str_1 = ", cms3_object_content AS c3 ";
			$price_id = $objType->getFieldId('price');

			if ( $price_to == 0 ) { 
				// поиск только от
				$price_str_2 = "AND objs.id=c3.obj_id AND c3.field_id=".$price_id." AND c3.float_val >= ".$price_from."";
			} else {
				// поиск от и до
				$price_str_2 = 	" AND objs.id=c3.obj_id AND c3.field_id=".$price_id.
								" AND c3.float_val >= ".$price_from." AND c3.float_val <= ".$price_to." ";
			}

		}
		
		// ограничение по тгк
		$tgk = getRequest('tgk');
		$tgk_str_1 = $tgk_str_2 = '';
		if ( is_array($tgk) ) {

			$tgk_from = (int)$tgk[0];
			$tgk_to = (int)$tgk[1];

			$tgk_str_1 = ", cms3_object_content AS c4 ";
			$tgk_id = $objType->getFieldId('percent_tgk');

			if ( $tgk_to == 0 ) { 
				// поиск только от
				$tgk_str_2 = "AND objs.id=c4.obj_id AND c4.field_id=".$tgk_id." AND c4.int_val >= ".$tgk_from."";
			} else {
				// поиск от и до
				$tgk_str_2 = 	" AND objs.id=c4.obj_id AND c4.field_id=".$tgk_id.
								" AND c4.int_val >= ".$tgk_from." AND c4.int_val <= ".$tgk_to." ";
			}

		}

		// условия сортировки
		$order_str_1 = $order_str_2 = $order_str_3 = '';
		$order_filter = getRequest('order_filter');
		if ( is_array($order_filter) ) {

			list($key, $value) = each($order_filter);
			$order_by_id = $objType->getFieldId($key);

			switch($key) {
				case 'price':
					$order_str_1 = ", cms3_object_content AS c5";
					$order_str_2 = "AND objs.id=c5.obj_id AND c5.field_id=".$order_by_id;
					$order_str_3 = "ORDER BY c5.float_val ";
				break;
				case 'percent_tgk': 
					$order_str_1 = ", cms3_object_content AS c5";
					$order_str_2 = "AND objs.id=c5.obj_id AND c5.field_id=".$order_by_id;
					$order_str_3 = "ORDER BY c5.int_val ";
				break;
				case 'name': 
					$order_str_3 = "ORDER BY objs.name ";
				break;
			}
			
			$order_str_3 = ( $value == 1 ) ? $order_str_3." ASC" : $order_str_3." DESC";

		}

		// готовим текст запроса
		$sqls = "	SELECT DISTINCT h.id FROM 	
						cms3_objects AS objs, 
						cms3_hierarchy AS h 
						".$cats_str_1."
						".$types_str_1 ."
						".$price_str_1."
						".$tgk_str_1."
						".$order_str_1."
					WHERE objs.type_id = ".$type_id." AND objs.id=h.obj_id ".$producers_term." AND h.is_active = 1 AND h.is_deleted = 0 
						".$cats_str_2."
						".$types_str_2."
						".$price_str_2."
						".$tgk_str_2."
						".$order_str_2."
					".$order_str_3."
				";

		// делаем запрос и перегоняем в массив $res
		// для работы с результатами как с массивом
		$result = l_mysql_query($sqls);
		$res = array();
		while ( $row = mysql_fetch_row($result) ) {
			$element_id = intval($row[0]);
			if( in_array($element_id, $res) == false ) {
				$res[] = $element_id;
			}
		}

	  	// отбираем данные с учетом лимитов
		$result = array_slice($res, $per_page*$curr_page, $per_page);
		$total = count($res);

		// ///////////////////////////////////////////////////////////////////////////////////////////////////// //
		//           передаем отобранные данные оставшейся части кода, которая осталась без изменений            //
		// ///////////////////////////////////////////////////////////////////////////////////////////////////// //
		if(($sz = sizeof($result)) > 0) {
			$block_arr = Array();
			$lines = Array();
			for($i = 0; $i < $sz; $i++) {
				$element_id = $result[$i];
				$element = umiHierarchy::getInstance()->getElement($element_id);
				if(!$element) continue;
				$line_arr = Array();
				$line_arr['attribute:id'] = $element_id;
				$line_arr['attribute:alt_name'] = $element->getAltName();
				$line_arr['attribute:link'] = umiHierarchy::getInstance()->getPathById($element_id);
				$line_arr['xlink:href'] = "upage://" . $element_id;
				$line_arr['node:text'] = $element->getName();
				$lines[] = def_module::parseTemplate($template_line, $line_arr, $element_id);
				templater::pushEditable("catalog", "object", $element_id);
				umiHierarchy::getInstance()->unloadElement($element_id);
			}
			$block_arr['subnodes:lines'] = $lines;
			$block_arr['numpages'] = umiPagenum::generateNumPage($total, $per_page);
			$block_arr['total'] = $total;
			$block_arr['per_page'] = $per_page;
			$block_arr['category_id'] = $category_id;
 
			if($type_id) {
				$block_arr['type_id'] = $type_id;
			}
			return def_module::parseTemplate($template_block, $block_arr, $category_id);
		} else {
			$block_arr['numpages'] = umiPagenum::generateNumPage(0, 0);
			$block_arr['lines'] = "";
			$block_arr['total'] = 0;
			$block_arr['per_page'] = 0;
			$block_arr['category_id'] = $category_id;
 
			return def_module::parseTemplate($template_block_empty, $block_arr, $category_id);
		}
    
    }


	public function orderinfo($order_id, $ide){
	  if(!$order_id) return false; 
	  $inst = umiObjectsCollection::getInstance();
	  $object = $inst->getObject($order_id);
	  $id_value = $object->getValue($ide);
	  if($ide == 'delivery_id') return $inst->getObject($id_value)->getName();
	  if($ide == 'order_discount_id'){
	    if(!$id_value) return "";
	    // Получаем значение поля "Описание" в скидке
	    $description_discount = $inst->getObject($id_value)->getValue('description');
	    $value_disc = $object->total_price - $object->total_original_price;
	    return "Скидка на заказ: {$name_disc} ({$value_disc} руб.)";	
	  }	
	  return "%data getEditForm({$id_value},'order')%";  
	}



	public function makeReadableDate($datetime) {
		$date = mb_substr($datetime, 8, 2); if ( preg_match("/^0\d$/", $date) ) { $date = mb_substr($date, 1); }
		$month = mb_substr($datetime, 5, 2); 
		$year = mb_substr($datetime, 0, 4);
		switch ($month) {
			case '01': $month = "января"; break;
			case '02': $month = "февраля"; break;
			case '03': $month = "марта"; break;
			case '04': $month = "апреля"; break;
			case '05': $month = "мая"; break;
			case '06': $month = "июня"; break;
			case '07': $month = "июля"; break;
			case '08': $month = "августа"; break;
			case '09': $month = "сентября"; break;
			case '10': $month = "октября"; break;
			case '11': $month = "ноября"; break;
			case '12': $month = "декабря"; break;
		}
		$readable = $date." ".$month." ".$year;
		return $readable." в ".mb_substr($datetime, 11, 5);
	}


	// получает самую большую персональную скидку
	// для указанного пользователя
	public function getPersonalDiscount($user_id = false) {
		
		if(def_module::breakMe()) { return; }

		$user_id = (int)$user_id;
		if ( !$user_id ) { return; }

		// id шаблона скидок
		$type_id = 73;
		// экземпляр это типа 
    	$objType = umiObjectTypesCollection::getInstance()->getType($type_id);
    	// id полей
    	$f_is_active = $objType->getFieldId('is_active');
    	$f_discount_rules_id = $objType->getFieldId('discount_rules_id');
    	$f_discount_modificator_id = $objType->getFieldId('discount_modificator_id');

    	// id шаблона Процент от суммы заказа
		$type_id = 42;
		// экземпляр это типа 
    	$objType = umiObjectTypesCollection::getInstance()->getType($type_id);
    	// id полей
    	$f_proc = $objType->getFieldId('proc');

		$sqls = "SELECT t4.float_val FROM cms3_object_content AS t1,
										cms3_object_content AS t2, 
										cms3_object_content AS t3, 
										cms3_object_content AS t4, 
										cms3_object_content AS t5  
				WHERE t1.field_id=133 AND t1.rel_val = ".$user_id." AND t1.obj_id = t2.rel_val AND t2.field_id = ".$f_discount_rules_id." 
										AND t2.obj_id = t3.obj_id AND t3.field_id = ".$f_discount_modificator_id." 
										AND t4.obj_id = t3.rel_val AND t4.field_id = ".$f_proc." 
										AND t3.obj_id = t5.obj_id AND t5.field_id = ".$f_is_active." AND t5.int_val = 1 
				ORDER BY  t4.float_val DESC LIMIT 1";

		$result = l_mysql_query($sqls);
		if ( mysql_num_rows($result) ) {
			$result_row = mysql_fetch_row($result);
			$proc_value = $result_row[0];
			return $proc_value;
		}

	}
 
	// шлет письмо о необходимости 
	// удаления пользователя
	public function sendRequestForAccDeletion() {

		$permissions = permissionsCollection::getInstance();
		$user_id  = $permissions->getUserId();

		if ( $user_id ) {
			$objectsCollection = umiObjectsCollection::getInstance();
			 $user_object = $objectsCollection->getObject($user_id);

		    $old = (string) htmlspecialchars(getRequest('oldpass'));
            if ($user_object->getValue("password") != md5($old)) {
            	return 'bad';
            }
			
			
			// имя пользователя
			$user_name = $user_object->getValue('login');

			// время генерации письма
			$send_time = date("F j, Y, H:i ");

			// получатель и отправитель		
			$regedit = regedit::getInstance();
			$umi_from = $regedit->getVal("//settings/email_from");
			$umi_to = $regedit->getVal("//settings/admin_email");
			$site_name = $regedit->getVal("//settings/site_name");

 			// отправка письма
 			$headers  = "MIME-Version: 1.0\r\n"
				   ."Content-type: text/html; charset=utf-8\r\n"
				   ."From:=?utf-8?b?".base64_encode($site_name)."?= <".$umi_from.">\r\n";
			$subject = "Запрос на удаление аккаунта";
			$message = "<html>
							<head>
								<title>".$subject."</title>
							</head>
							<body>
								Пользователь ".$user_name." запросил удаление аккаунта.<br />".$send_time."
							</body>
						</html>";
			$to = "=?utf-8?b?".base64_encode('')."?= <".$umi_to.">";
			$subject = "=?utf-8?b?".base64_encode($subject)."?=";
			mail($to, $subject, $message, $headers);
			return 'good';
		} else {
			return 'bad';
		}

		// return ( $result ) ? 'good' : 'bad'; 

	}
	



	};
?>