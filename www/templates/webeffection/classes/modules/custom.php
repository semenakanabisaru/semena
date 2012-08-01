<?php
	class custom extends def_module {
		public function cms_callMethod($method_name, $args) {
			return call_user_func_array(Array($this, $method_name), $args);
		}
		
		public function __call($method, $args) {
			throw new publicException("Method " . get_class($this) . "::" . $method . " doesn't exists");
		}
		//TODO: Write your own macroses here
		 public function dateru($time, $month = 0) {
            $day = date('d', $time);
            $month = date('n', $time);
            $year = date('Y', $time);
 
            // Проверка существования месяца
            if (!checkdate($month, 1, $year)){
                throw new publicException("Проверьте порядок ввода даты.");
            }
 
            $months_ru = array(1 => 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря');
//здесь делаем формат даты
            $date_ru = $day . ' ' . $months_ru[$month] . ' ' . $year . '';
            if ($month == 1) {return $months_ru[$month];} else {return $date_ru;};
            
          }
	};
?>