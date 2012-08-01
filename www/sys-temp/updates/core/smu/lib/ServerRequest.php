<?php
function DebugPrintResult($string) {
	echo "<pre><b style=\"color:blue;\">Returned by server</b><br />".htmlentities($string)."</pre>";
}
/**
* @desc Общий интерфейс для всех реализаций HTTP запроса
*/
interface IServerHTTPRequest {
	/**
	* @desc Устанавливает значение указаного заголовка
	* @param string $name  Имя заголовка
	* @param string $value Новое значение заголовка
	*/
	public function setHeader($name, $value);
	/**
	* @desc Очищает значения всех заголовков	
	*/
	public function clearHeaders();
	/**
	* @desc Устанавливает значение указаного POST-параметра. 
	* 	    Если параметр уже существует, то новое значение 
	* 		будет отправлено вместе с уже существующим в виде массива.
	* @param string $name  Имя параметра
	* @param mixed  $value Значение параметра
	*/
	public function addPostVariable($name, $value);
	/**
	* @desc Очищает все добавленые POST-параметры
	*/
	public function clearPostVariables();
	/**
	* @desc Выполняет запрос
	* @param string $url назначение запроса
	* @return boolean true - вслучае успешного завершения запроса, иначе false
	*/
	public function execute($url);
};
/**
* @desc Реалилзует базовые методы IServerHTTPRequest
*/
class BaseServerHTTPRequest implements IServerHTTPRequest {	
	protected $postVariables     = array();
	protected $additionalHeaders = array();	
	protected function __construct() {
		// Nothig to do. Just prevent instantiating
	}
		
	public function setHeader($name, $value) {
		$this->additionalHeaders[$name] = (string) $value;
	}	
	public function clearHeaders() {
		$this->additionalHeaders = array();
	}	
	public function addPostVariable($name, $value) {
		if(isset($this->postVariables[$name])) {
			if(!is_array($this->postVariables[$name])) {
				$this->postVariables[$name] = array($this->postVariables[$name]);
			}
			$this->postVariables[$name] = array_merge($this->postVariables[$name], is_array($value) ? $value : array($value));
		} else {
			$this->postVariables[$name] = $value;
		}
	}	
	public function clearPostVariables() {
		$this->postVariables = array();
	}
	public function execute($url) {
		return true;
	}
	protected function buildHeaders() {
		if(empty($this->additionalHeaders)) return '';
		$headerLines = array();
		foreach($this->additionalHeaders as $headerName => $headerValue) {
			$headerLines[] = $headerName . ': ' . $headerValue;
		}
		return implode("\r\n", $headerLines );
	}	
};
/**
* @desc Выполняет запрос средствами Client URL Library (cURL)
*/
class CurlServerHTTPRequest extends BaseServerHTTPRequest {
	public function __construct() {}
	public function execute($url) {
		$handle = curl_init($url);
		if(!$handle) throw new Exception('CURL init failed', 9101);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
		if(!empty($this->postVariables)) {
			curl_setopt($handle, CURLOPT_POST,		 count($this->postVariables));  
			curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($this->postVariables, '', '&'));
		}
		curl_setopt($handle, CURLOPT_HEADER, empty($this->additionalHeaders) ? 0 : $this->buildHeaders() );
		$result = curl_exec($handle);
		$info   = curl_getinfo($handle);
		if($result === false || $info['http_code'] != 200) {
			throw new Exception('Curl error: ' . curl_error($handle), 9102);
		}
		curl_close($handle);
		//DebugPrintResult($result);
		return $result;
	}	
};
/**
* @desc Выполняет запрос при помощи TCP/IP сокетов
*/
class SocketServerHTTPRequest extends BaseServerHTTPRequest {
	const READ_PACKET_SIZE = 4096;
	public function __construct() {}
	public function execute($url) {
		$urlPieces = parse_url($url);
		$port      = isset($urlPieces['port']) ? $urlPieces['port'] : getservbyname('www', 'tcp');
		$address   = gethostbyname($urlPieces['host']);	
		$socket    = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if(!socket_connect($socket, $address, $port)) {
			throw new Exception('Can not connect to host ' . $urlPieces['host'], 9201);
		}
		$postData     = (!empty($this->postVariables)) ? http_build_query($this->postVariables, '', '&') : null;
		$headerString = (empty($this->postVariables)?"GET ":"POST ").$urlPieces['path']." HTTP/1.1\r\n".							
			            "Host: ".$urlPieces['host']."\r\n".
			            "User-Agent: smu v1.0\r\n".
			            (empty($this->postVariables) ? "" : "Content-Type: application/x-www-form-urlencoded\r\nContent-Length: ".strlen($postData)."\r\n") .
			            (strlen($tmp = $this->buildHeaders()) ? ($tmp . "\r\n") : "") .
			            "Connection: close\r\n\r\n" . $postData;
		if(socket_send($socket, $headerString, strlen($headerString), 0) < 0) {
			throw new Exception('Request send failed', 9202);
		}
		$buffer = '';
		$result = '';
		$recvdHeaders = false;
		do{
			$bytesRead = socket_recv($socket, $buffer, SocketServerHTTPRequest::READ_PACKET_SIZE, 0);				
			if(!$recvdHeaders) {
				$headers = substr($buffer, 0, strpos($buffer, "\r\n\r\n"));
				$buffer  = substr($buffer, strpos($buffer, "\r\n\r\n")+4);
				$bytesRead = $bytesRead - strlen($headers) - 4;
				$recvdHeaders = true;
				$headers = explode("\r\n", $headers);
				$status  = explode(" ", $headers[0]);
				if($status[1] != '200') {
					throw new Exception('Remote file does not exists', 9203);
				}
			}			
			$result .= $buffer;			
		} while($bytesRead != 0);
		socket_close($socket);		
		return $result;
	}
};
/**
* @desc Выполняет запрос при помощи потоковых функций
*/
class StreamServerHTTPRequest extends BaseServerHTTPRequest {
	public function __construct() {}
	public function execute($url) {
		$context = NULL;
		if(!empty($this->additionalHeaders) || !empty($this->postVariables)) {
			$contextOptions = array('http'=>array());
			if(!empty($addHeaders)) {
				$contextOptions['http']['header'] = $this->buildHeaders();
			}
			if(!empty($this->postVariables)) {
				$contextOptions['http']['method']  = 'POST';
				$contextOptions['http']['content'] = http_build_query($this->postVariables, '', '&');
			}
			$context = stream_context_create($contextOptions);
		}
		$result = file_get_contents($url, false, $context);
		if(!$result) {
			throw new Exception('Remote file does not exists', 9301);			
		}
		return $result;
	}
};
/**
* @desc Возвращаяет экземпляр с доступным механизмом проведения запроса
* @return IServerHTTPRequest экземпляр
*/
function getServerRequest() {	
	if(function_exists('curl_init')) {
		return new CurlServerHTTPRequest();
	}
	if(intval(ini_get('allow_url_fopen'))) {
		return new StreamServerHTTPRequest();
	}
	if(function_exists('socket_create')) {
		return new SocketServerHTTPRequest();
	}		
	throw new Exception('No suitable method found for request', 9001);
}
?>