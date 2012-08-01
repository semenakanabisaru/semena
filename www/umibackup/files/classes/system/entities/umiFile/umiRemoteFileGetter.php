<?php
/**
 * Класс для получения содержимого удаленного файла любыми доступными средствами
 * @author Игнат "Leeb" Толчанов <ignat@umi-cms.ru>
 * @version 1.0
 */
class umiRemoteFileGetter {
	/**
	* @desc Размер "пакета"
	*/
	const READ_PIECE_SIZE = 4096;
	/**
	* @desc Возвращает содержимое удаленного файла или записывает его на диск
	* @param string $remoteName URL удаленного файла
	* @param string $localName  Путь для сохранения содержимого
	* @param array  $addHeaders Дополнительные HTTP-заголовки
	* @param array  $postVars   Переменные, передаваемые методом POST
	* @param string $method  Метод запроса
	* @param int $timeout  Количество секунд ожидания при попытке соединения
	* @return string|umiFile
	*/
	public static function get($remoteName, $localName = false, $addHeaders = false, $postVars = false, $returnHeaders = false, $method = false, $timeout = false) {
		$resultException = null;

		if(strpos($remoteName, '://') === false) {
			$content = @file_get_contents($remoteName, false, $context);
			if($content === false) throw new umiRemoteFileGetterException('Local file does not exists or can not be opened');
			if($localName === false) {
				return $content;
			} else {
				@file_put_contents($localName, $content);
				return true;
			}
		}

		try {
			$result = self::basicGet($remoteName, $localName, $addHeaders, $postVars, $returnHeaders, $method, $timeout);
			return $result;
		} catch(umiRemoteFileGetterException $e) {
			$resultException = $e;
		}
		try {
			$result = self::curlGet($remoteName, $localName, $addHeaders, $postVars, $returnHeaders, $method, $timeout);
			return $result;
		} catch(umiRemoteFileGetterException $e) {
			$resultException = $e;
		}
		try {
			$result = self::socketGet($remoteName, $localName, $addHeaders, $postVars);
			return $result;
		} catch(umiRemoteFileGetterException $e) {
			$resultException = $e;
		}

		if($resultException != null) throw $resultException;
		return null;
	}
	private static function basicGet($remoteName, $localName, $addHeaders = false, $postVars = false, $returnHeaders = false, $method = false, $timeout = false) {
		if(!intval(ini_get('allow_url_fopen'))) {
			throw new umiRemoteFileGetterException('Not allowed');
		}
		$context = stream_context_create(array());
		if(!empty($addHeaders) || !empty($postVars) || $method || $timeout) {

			$contextOptions = array('http' => array());

			if(!empty($addHeaders)) {
				$contextOptions['http']['header'] = self::buildHeaderString($addHeaders);
			}
			if(!empty($postVars)) {
				$contextOptions['http']['method']  = 'POST';
				$contextOptions['http']['content'] = is_array($postVars) ? http_build_query($postVars, '', '&') : $postVars;;
			}
			if ($method) $contextOptions['http']['method'] = $method;
			if ($timeout) $contextOptions['http']['timeout'] = (int) $timeout;

			$context = stream_context_create($contextOptions);
		}

		if($localName === false) {
			$fp  = fopen($remoteName, 'r', false, $context);
			if(!$fp) {
				throw new umiRemoteFileGetterException('Can not open');
			}
			$content = stream_get_contents($fp);
			if ($returnHeaders) {
				$meta = stream_get_meta_data($fp);
				if (isset($meta['wrapper_data']) && is_array($meta['wrapper_data'])) {
					$content = implode($meta['wrapper_data'], "\r\n") . "\r\n\r\n" . $content;
				}
			}

			if($content === false) throw new umiRemoteFileGetterException('Failure');
			return $content;
		}

		$tmp = basename($localName);

		if( in_array($tmp, array('.htaccess', '.htpasswd', "config.ini") ) )  {
			throw new umiRemoteFileGetterException('Can not open');
		}

		$in  = @fopen($remoteName, 'r', false, $context);
		$out = @fopen($localName, 'w');

		if(!$in || !$out) {
			throw new umiRemoteFileGetterException('Can not open');
		}
		while(!feof($in)) {
			$buffer = fread($in, umiRemoteFileGetter::READ_PIECE_SIZE);
			fwrite($out, $buffer);
		}
		fflush($out);
		fclose($out);
		fclose($in);
		return new umiFile($localName);
	}
	private static function curlGet($remoteName, $localName, $addHeaders = false, $postVars = false, $returnHeaders = false, $method = false, $timeout = false) {
		if(!function_exists('curl_init')) {
			throw new umiRemoteFileGetterException('CURL not supported');
		}
		$result = '';
		$ch     = curl_init($remoteName);
		if(!$ch) {
			throw new umiRemoteFileGetterException('CURL init failed');
		}
		if($localName === false) {
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		} else {
			$fp = fopen($localName, "w");
			if(!$fp) {
				throw new umiRemoteFileGetterException('Can not open target file');
			}
			curl_setopt($ch, CURLOPT_FILE, $fp);
		}
		if ($returnHeaders) {
			curl_setopt($ch, CURLOPT_HEADER, 1);
		}
		if(!empty($postVars)) {

			curl_setopt($ch, CURLOPT_POST,		 count($postVars));
			$content = is_array($postVars) ? http_build_query($postVars, '', '&') : $postVars;
			curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
		}

		if (is_array($addHeaders)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, explode("\r\n", self::buildHeaderString($addHeaders)));
		}

		if ($method && $method != "GET" && $method != "POST") {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $content);
		}

		if ($timeout) {
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, (int) $timeout);
		}

		$result = curl_exec($ch);
		curl_close($ch);
		if($localName === false) {
			return $result;
		} else {
			fclose($fp);
			return new umiFile($localName);
		}
	}
	private static function socketGet($remoteName, $localName, $addHeaders = false, $postVars = false) {
		if(!function_exists('socket_create')) {
			throw new umiRemoteFileGetterException('Sockets not supported');
		}
		$result    = '';
		$urlPieces = parse_url($remoteName);
		$port    = isset($urlPieces['port']) ? $urlPieces['port'] : getservbyname('www', 'tcp');
		$address = gethostbyname($urlPieces['host']);
		$socket  = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if(socket_connect($socket, $address, $port)) {
			if($localName !== false) {
				$fp = fopen($localName, 'w');
			}
			$postData     = (!empty($postVars)) ? http_build_query($postVars, '', '&') : null;
			$headerString = (empty($postVars)?"GET ":"POST ").$urlPieces['path']." HTTP/1.1\r\n".
			                "Host: ".$urlPieces['host']."\r\n".
			                "User-Agent: umiRemoteFileGetter v1.0\r\n".
			                (empty($postVars) ? "" : "Content-Type: application/x-www-form-urlencoded\r\nContent-Length: ".bytes_strlen($postData)."\r\n") .
			                self::buildHeaderString($addHeaders) .
			                "Connection: close\r\n\r\n" . $postData;
			if(socket_send($socket, $headerString, strlen($headerString), 0) < 0) {
				throw new umiRemoteFileGetterException('Request send failed');
			}
			$buffer = '';
			$recvdHeaders = false;
			do{
				$bytesRead = socket_recv($socket, $buffer, umiRemoteFileGetter::READ_PIECE_SIZE, 0);
				if(!$recvdHeaders) {
					$headers = substr($buffer, 0, strpos($buffer, "\r\n\r\n"));
					$buffer  = substr($buffer, strpos($buffer, "\r\n\r\n")+4);
					$bytesRead = $bytesRead - bytes_strlen($headers) - 4;
					$recvdHeaders = true;
					$headers = explode("\r\n", $headers);
					$status  = explode(" ", $headers[0]);
					if($status[1] != '200') {
						throw new umiRemoteFileGetterException('Remote file does not exists');
					}
				}
				if($localName === false) {
					$result .= $buffer;
				} else {
					fwrite($fp, $buffer, $bytesRead);
				}
			} while($bytesRead != 0/*umiRemoteFileGetter::READ_PIECE_SIZE*/);
			if($localName !== false) {
				fclose($fp);
			}
		} else {
			throw new umiRemoteFileGetterException('Cant connect to remote host');
		}
		socket_close($socket);
		if($localName === false) {
			return $result;
		} else {
			return new umiFile($localName);
		}
	}
	private static function getTemporaryName() {
		return self::getTemporaryDirectory() . '/' . md5( str_repeat(rand(0,10000).time(), 5) ) . '.tmp';
	}
	private static function getTemporaryDirectory() {
		static $path = false;
		if($path === false) {
		    $path = ini_get('upload_tmp_dir');
		    // если директива upload_tmp_dir не задана в php.ini, то
		    if (!is_dir($path) || $path=='') {
		        // искуссвенным путем определяем временную директорию в системе
		        $path = dirname(tempnam('127631782631827', 'foo'));
		        if (!is_dir($path)) {
		            $path = CURRENT_WORKING_DIR . '/cache';
		        }
		    }
		}
		return $path;
	}
	private static function buildHeaderString($addHeaders) {
		$headerLines = array();
		if(!empty($addHeaders))
		foreach($addHeaders as $name => $value) {
			$headerLines[] = $name . ': ' . $value;
		}
		return empty($headerLines) ? '' : (implode("\r\n", $headerLines) . "\r\n");
	}
};
?>
