<?php
/**
 * Класс LoginzaUserProfile предназначен для генерации некоторых полей профиля пользователя сайта, 
 * на основе полученного профиля от Loginza API (http://loginza.ru/api-overview).
 * 
 * При генерации используются несколько полей данных, что позволяет сгенерировать непереданные 
 * данные профиля, на основе имеющихся.
 * 
 * Например: Если в профиле пользователя не передано значение nickname, то это значение может быть
 * сгенерированно на основе email или full_name полей.
 * 
 * Данный класс - это рабочий пример, который можно использовать как есть, 
 * а так же заимствовать в собственном коде или расширять текущую версию под свои задачи.
 * 
 * @link http://loginza.ru/api-overview
 * @author Sergey Arsenichev, PRO-Technologies Ltd.
 * @version 1.0
 */
class loginzaUserProfile {
	/**
	 * Профиль
	 *
	 * @var unknown_type
	 */
	private $profile;
	
	/**
	 * Данные для транслита
	 *
	 * @var unknown_type
	 */
	private $translate = array(
	'а'=>'a', 'б'=>'b', 'в'=>'v', 'г'=>'g', 'д'=>'d', 'е'=>'e', 'ж'=>'g', 'з'=>'z',
	'и'=>'i', 'й'=>'y', 'к'=>'k', 'л'=>'l', 'м'=>'m', 'н'=>'n', 'о'=>'o', 'п'=>'p',
	'р'=>'r', 'с'=>'s', 'т'=>'t', 'у'=>'u', 'ф'=>'f', 'ы'=>'i', 'э'=>'e', 'А'=>'A',
	'Б'=>'B', 'В'=>'V', 'Г'=>'G', 'Д'=>'D', 'Е'=>'E', 'Ж'=>'G', 'З'=>'Z', 'И'=>'I',
	'Й'=>'Y', 'К'=>'K', 'Л'=>'L', 'М'=>'M', 'Н'=>'N', 'О'=>'O', 'П'=>'P', 'Р'=>'R',
	'С'=>'S', 'Т'=>'T', 'У'=>'U', 'Ф'=>'F', 'Ы'=>'I', 'Э'=>'E', 'ё'=>"yo", 'х'=>"h",
	'ц'=>"ts", 'ч'=>"ch", 'ш'=>"sh", 'щ'=>"shch", 'ъ'=>"", 'ь'=>"", 'ю'=>"yu", 'я'=>"ya",
	'Ё'=>"YO", 'Х'=>"H", 'Ц'=>"TS", 'Ч'=>"CH", 'Ш'=>"SH", 'Щ'=>"SHCH", 'Ъ'=>"", 'Ь'=>"",
	'Ю'=>"YU", 'Я'=>"YA"
	);
	
	function __construct($profile) {
		$this->profile = $profile;//print_R($profile);
	}
	
	public function genNickname () {
		if (!empty($this->profile->nickname)) {
			return $this->profile->nickname;
		} elseif (!empty($this->profile->email) && preg_match('/^(.+)\@/i', $this->profile->email, $nickname)) {
			return $nickname[1];
		} 
		
		if ( ($fullname = $this->genFullName()) ) {
			
			return $this->normalize($fullname, '.');
		}
		// шаблоны по которым выцепляем ник из identity
		$patterns = array(
			'([^\.]+)\.ya\.ru',
			'([^\.]+)\.livejournal\.com',
			'openid\.mail\.ru\/[^\/]+\/([^\/?]+)',
			'openid\.yandex\.ru\/([^\/?]+)',
			'([^\.]+)\.myopenid\.com'
		);
		foreach ($patterns as $pattern) {
			if (preg_match('/^https?\:\/\/'.$pattern.'/i', $this->profile->identity, $result)) {
				return $result[1];
			}
		}
		
		return false;
	}
	
	public function genUserEmail () {
		if (!empty($this->profile->email)) 
		{
			return $this->profile->email;
		}
		
		return '';
	}
	
	public function genProvider () {
		if (!empty($this->profile->provider)) 
		{
			return $this->profile->provider;
		}
		
		return '';
	}
	
	public function genUserSite () {
		if (!empty($this->profile->web->blog)) {
			return $this->profile->web->blog;
		} elseif (!empty($this->profile->web->default)) {
			return $this->profile->web->default;
		}
		
		return $this->profile->identity;
	}
	
	public function genDisplayName () {
	 	if ( ($fullname = $this->genFullName()) ) {
			return $fullname;
		} elseif ( ($nickname = $this->genNickname()) ) {
			return $nickname;
		}
		
		$identity_component = parse_url($this->profile->identity);
		
		$result = $identity_component['host'];
		if ($identity_component['path'] != '/') {
			$result .= $identity_component['path'];
		}
		
		return $result.$identity_component['query'];
		
	}
	
	public function getFname() {
		if ( !empty($this->profile->name->first_name))  return $this->profile->name->first_name;
		
		return false;
	}
	
	public function getLname() {
		if ( !empty($this->profile->name->last_name))  return $this->profile->name->last_name;
		
		return false;
	}
	
	public function genFullName () {
		if ( !empty($this->profile->name->full_name)) {
			return $this->profile->name->full_name;
		} elseif ( !empty($this->profile->name->first_name) || !empty($this->profile->name->last_name) ) {
			$fn = !empty($this->profile->name->first_name)?$this->profile->name->first_name:'';
			$ln = !empty($this->profile->name->last_name)?$this->profile->name->last_name:'';
			
			return trim($fn.' '.$ln);
		}
		
		return '';
	}
	/**
	 * Генератор случайных паролей
	 *
	 * @param unknown_type $len Длина пароля
	 * @param unknown_type $char_list Список наборов символов, используемых для генерации, через запятую. Например: a-z,0-9,~
	 * @return unknown
	 */
	public function genRandomPassword ($len=6, $char_list='a-z,0-9') {
		$chars = array();
		// предустановленные наборы символов
		$chars['a-z'] = 'qwertyuiopasdfghjklzxcvbnm';
		$chars['A-Z'] = strtoupper($chars['a-z']);
		$chars['0-9'] = '0123456789';
		$chars['~'] = '~!@#$%^&*()_+=-:";\'/\\?><,.|{}[]';
		
		// набор символов для генерации
		$charset = '';
		// пароль
		$password = '';
		
		if (!empty($char_list)) {
			$char_types = explode(',', $char_list);
			
			foreach ($char_types as $type) {
				if (array_key_exists($type, $chars)) {
					$charset .= $chars[$type];
				} else {
					$charset .= $type;
				}
			}
		}
		
		for ($i=0; $i<$len; $i++) {
			$password .= $charset[ rand(0, strlen($charset)-1) ];
		}
		
		return $password;
	}
	
	/**
	 * Транслит + убирает все лишние символы заменяя на символ $delimer
	 *
	 * @param unknown_type $string
	 * @param unknown_type $delimer
	 * @return unknown
	 */
	private function normalize ($string, $delimer='-') {
		$string = strtr($string, $this->translate);
	    return substr(trim(preg_replace('/[^\w]+/i', $delimer, $string), $delimer), 0, 40);
	}
}

?>