<?php
/**
	* Наименование в заказе для цифровых товаров
*/
	class digitalOrderItem extends orderItem {
		/**
			* Инициировать немедленное скачивание (umiFile::download())
		*/
		//public function download();


		/**
			* Получить ссылку для скачивания.
			* @return String ссылка для скачивания файла
		*/
		//public function getDownloadLink();


		/**
			* Узнать, является ли товар цифровым.
			* @return Boolean в классе digitalOrderItem всегда возвращает true. Может быть перегружен ниже.
		*/
		//public function getIsDigital();
	};
?>