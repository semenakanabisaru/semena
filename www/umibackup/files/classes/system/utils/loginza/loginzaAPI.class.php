<?php
/**
 * Класса работы с Loginza API (http://loginza.ru/api-overview).
 * 
 * Данный класс - это рабочий пример, который можно использовать как есть, 
 * а так же заимствовать в собственном коде или расширять текущую версию под свои задачи.
 * 
 * Требуется PHP 5, а так же CURL или разрешение работы c ресурсами http:// для file_get_contents.
 * 
 * @link http://loginza.ru/api-overview
 * @author Sergey Arsenichev, PRO-Technologies Ltd.
 * @version 1.0
 */
class loginzaAPI {
	/**
	 * Версия класса
	 *
	 */
	const VERSION = '1.0';
	/**
	 * URL для взаимодействия с API loginza
	 *
	 */
	const API_URL = 'http://loginza.ru/api/%method%';
	/**
	 * URL виджета Loginza
	 *
	 */
	const WIDGET_URL = 'https://loginza.ru/api/widget';
	
	public $providers = array(
		'yandex'	=> array('title'=>'Яндекс', 'enabled'=>true), 
		'vkontakte'	=> array('title'=>'Вконтакте', 'enabled'=>true), 
		'facebook'	=> array('title'=>'Facebook', 'enabled'=>true), 
		'google'	=> array('title'=>'Google Accounts', 'enabled'=>true), 
		'openid'	=> array('title'=>'OpenID', 'enabled'=>true), 
		'myopenid'	=> array('title'=>'MyOpenID', 'enabled'=>true), 
		'twitter'	=> array('title'=>'Twitter', 'enabled'=>true), 
		'rambler'	=> array('title'=>'Rambler', 'enabled'=>true), 
		'mailru'	=> array('title'=>'Mail.ru', 'enabled'=>true), 
		'mailruapi'	=> array('title'=>'Mail.ru', 'enabled'=>false), 
		'loginza'	=> array('title'=>'Loginza', 'enabled'=>true), 
		'webmoney'	=> array('title'=>'WebMoney', 'enabled'=>true), 
		'flickr'	=> array('title'=>'flickr', 'enabled'=>true), 
		'lastfm'	=> array('title'=>'lastfm', 'enabled'=>true), 
		'verisign'	=> array('title'=>'verisign', 'enabled'=>false), 
		'aol'		=> array('title'=>'aol', 'enabled'=>false), 
	);
	
	/**
	 * Получить информацию профиля авторизованного пользователя
	 *
	 * @param string $token Токен ключ авторизованного пользователя
	 * @return mixed
	 */
	public function getAuthInfo ($token) {
		return $this->apiRequert('authinfo', array('token' => $token));
	}
	
	/**
	 * Получает адрес ссылки виджета Loginza
	 *
	 * @param string $return_url Ссылка возврата, куда будет возвращен пользователя после авторизации
	 * @param string $provider Провайдер по умолчанию из списка: google, yandex, mailru, vkontakte, facebook, twitter, loginza, myopenid, webmoney, rambler, mailruapi:, flickr, verisign, aol
	 * @param string $overlay Тип встраивания виджета: true, wp_plugin, loginza
	 * @return string
	 */
	public function getWidgetUrl ($return_url=null, $provider=null, $overlay='') {
		$params = array();
		
		if (!$return_url) {
			$params['token_url'] = $this->currentUrl();
		} else {
			$params['token_url'] = $return_url;
		}
		
		if ($provider) {
			$params['provider'] = $provider;
		}
		
		if ($overlay) {
			$params['overlay'] = $overlay;
		}
		
		return self::WIDGET_URL.'?'.http_build_query($params, '', '&');
	}
	
	/**
	 * Возвращает ссылку на текущую страницу
	 *
	 * @return string
	 */
	private function currentUrl () {
		$url = array();
		// проверка https
		if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') {
			$url['sheme'] = "https";
			$url['port'] = '443';
		} else {
			$url['sheme'] = 'http';
			$url['port'] = '80';
		}
		// хост
		$url['host'] = $_SERVER['HTTP_HOST'];
		// если не стандартный порт
		if (strpos($url['host'], ':') === false && $_SERVER['SERVER_PORT'] != $url['port']) {
			$url['host'] .= ':'.$_SERVER['SERVER_PORT'];
		}
		// строка запроса
		if (isset($_SERVER['REQUEST_URI'])) {
			$url['request'] = $_SERVER['REQUEST_URI'];
		} else {
			$url['request'] = $_SERVER['SCRIPT_NAME'] . $_SERVER['PATH_INFO'];
			$query = $_SERVER['QUERY_STRING'];
			if (isset($query)) {
			  $url['request'] .= '?'.$query;
			}
		}
		
		return $url['sheme'].'://'.$url['host']."/users/loginza/?from_page=".urlencode($url['sheme'].'://'.$url['host'].$url['request']);
	}
	
	/**
	 * Делает запрос на API loginza
	 *
	 * @param string $method
	 * @param array $params
	 * @return string
	 */
	private function apiRequert($method, $params) {
		// url запрос
		$url = str_replace('%method%', $method, self::API_URL).'?'.http_build_query($params, '', '&');
		
		if ( function_exists('curl_init') ) {
			$curl = curl_init($url);
			$user_agent = 'LoginzaAPI'.self::VERSION.'/php'.phpversion();
			
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_HEADER, false);
			curl_setopt($curl, CURLOPT_USERAGENT, $user_agent);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			$raw_data = curl_exec($curl);
			curl_close($curl);
			$responce = $raw_data;
		} else {
			$responce = file_get_contents($url);
		}
		
		// обработка JSON ответа API
		return $this->decodeJSON($responce);
	}
	
	/**
	 * Парсим JSON данные
	 *
	 * @param string $data
	 * @return object
	 */
	private function decodeJSON ($data) {
		if ( function_exists('json_decode') ) {
			return json_decode ($data);
		}
		
		// загружаем библиотеку работы с JSON если она необходима
		if (!class_exists('Services_JSON')) {
			require_once( dirname( __FILE__ ) . '/json.php' );
		}
		
		$json = new Services_JSON();	
		return $json->decode($data);
	}
	
	public function debugPrint ($responceData, $recursive=false) {
		if (!$recursive){
			echo "<h3>Debug print:</h3>";
		}
		echo "<table border>";
		foreach ($responceData as $key => $value) {
			if (!is_array($value) && !is_object($value)) {
				echo "<tr><td>$key</td> <td><b>$value</b></td></tr>";
			} else {
				echo "<tr><td>$key</td> <td>";
				$this->debugPrint($value, true);
				echo "</td></tr>";
			}
		}
		echo "</table>";
	}
	
	public function getProvider() {
		$result = array();
		
		foreach( $this->providers as $k=>$v) {
			if($v['enabled'] ) $result[$k] = $v['title'];
		}
		
		return $result;
	}	
}

?>