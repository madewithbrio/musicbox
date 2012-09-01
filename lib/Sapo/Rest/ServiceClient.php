<?php

// try to simplify - use getURI, postURI, postXML, getJsonURI, etc...

class Sapo_Rest_ServiceClient
{
	public static function getData($uri, $timeout = 3)
	{
		$opts = array(
			'http'=>array(
				'method' => "GET",
				'header' => "User-Agent: SapoMobile/1.0\r\n" . "Accept-Charset: utf-8;\r\n",
				'timeout' => $timeout,
			)
		);

		$context = stream_context_create($opts);
		//Sapo_Log::getLogger()->debug("Request to: $uri");
		$response = @file_get_contents($uri, null, $context);
		self::httpHeaderReaction($uri, $http_response_header);

		return array('headers' => $http_response_header, 'response' => $response);
	}

	public static function getURI($uri, $resultClassName, $stripNamespaces = false, $timeout = 3)
	{
		$opts = array(
			'http'=>array(
				'method' => "GET",
				'header' => "User-Agent: SapoMobile/1.0\r\n" . "Accept-Charset: utf-8;\r\n",
				'timeout' => $timeout,
			)
		);

		$context = stream_context_create($opts);
		//Sapo_Log::getLogger()->debug("Request to: $uri");
		$responseXML = @file_get_contents($uri, null, $context);
		self::httpHeaderReaction($uri, $http_response_header);

		return self::processResponse($uri, $responseXML, $resultClassName, $stripNamespaces);
	}

	public static function postURI($uri, $data, $resultClassName, $stripNamespaces = false, $timeout = 3)
	{
		$dataUrl = http_build_query ($data);
		$dataLen = strlen ($dataUrl);

		$context = array(
			'http' => array(
				'method' => 'POST',
				'header' => "Connection: close\r\nContent-Length: $dataLen\r\nUser-Agent: SapoMobile/1.0\r\nAccept-Charset: utf-8;\r\nContent-type: application/x-www-form-urlencoded\r\n",
				'content' => $dataUrl,
				'timeout' => $timeout,
		));

		return self::postRequest($uri, $context, $resultClassName, $stripNamespaces);
	}

	public static function postXML($uri, $xmlSprintfTemplate, $dataList, $resultClassName, $stripNamespaces = false)
	{
		$xml = vsprintf($xmlSprintfTemplate, $dataList);
		$dataLen = strlen ($xml);

		$context = array(
			'http' => array(
				'method' => 'POST',
				'header' => "Connection: close\r\nContent-Length: $dataLen\r\nUser-Agent: SapoMobile/1.0\r\nContent-type: text/xml; Accept-Charset: utf-8\r\n",
				'content' => $xml
		));

		return self::postRequest($uri, $context, $resultClassName, $stripNamespaces);
	}


	public static function postRequest($uri, $context, $resultClassName, $stripNamespaces = false)
	{
		//Sapo_Log::getLogger()->debug("Request to: $uri");
		$responseXML = file_get_contents($uri, null, stream_context_create($context));

		self::httpHeaderReaction($uri, $http_response_header);

		return self::processResponse($uri, $responseXML, $resultClassName, $stripNamespaces);
	}

	public static function getJsonURI($uri, $timeout = 3)
	{
		$opts = array(
			'http'=>array(
				'method' => "GET",
				'header' => "User-Agent: SapoMobile/1.0\r\n" . "Accept-Charset: utf-8;\r\n",
				'timeout' => $timeout,
			)
		);
		//Sapo_Log::getLogger()->debug("Request to: $uri");
		$context = stream_context_create($opts);
		$response = file_get_contents($uri, null, $context);

		return json_decode($response);
	}

	public static function existsURI($uri, $timeout = null)
	{
		preg_match("/^https?:\\/\\/([A-Za-z0-9._-]+)/", $uri, $match);
		$domain = $match[1];
		$portno = 80;
		$errno = $errstr = null;
		$request = "HEAD $uri HTTP/1.0\r\n"
				  ."Host: $domain\r\n\r\n";
		$response = '';

		if (!$timeout) $timeout = ini_get("default_socket_timeout");

		$fp = fsockopen($domain, $portno, $errno, $errstr, $timeout);
		if($fp){
			stream_set_timeout($fp, $timeout);
			fputs($fp, $request);
			$response = fgets($fp, 22);
//			while (!feof($fp)) $response .= fgets($fp, 128);
			fclose($fp);
		}
		preg_match('/^[^\s]+\s([0-9]+)\s/', $response, $match);
		$status = $match[1];
		if ($status >= 200 && $status < 400) return true;
		return false;
	}

	public static function stripNamespaces($xmlDoc)
	{
		return preg_replace('/(<\/?)([A-Za-z0-9]+:)/', '$1', $xmlDoc);
	}

	protected static function httpHeaderReaction($uri, $headers)
	{
		$httpReturn = explode(' ', $headers[0]);
		$httpReturnCode = $httpReturn[1];
		if (200 == $httpReturnCode) return;

		if (null == $headers) Sapo_Log::error("URI $uri did not return headers");
		throw new UnexpectedResponseException($uri . ' returned an unexpected response: ' . $httpReturnCode);
	}

	public static function extractHeader($headers, $headerName)
	{
		for ($h = 0, $headersLen = count($headers); $h < $headersLen; $h++)
		{
			$header = $headers[$h];
			if (false !== stripos($header, $headerName))
			{
				$explosion = explode(':', $header, 2);
				return trim($explosion[1]);
			}
		}
	}

	protected static function processResponse($uri, $responseXML, $resultClassName, $stripNamespaces = false)
	{
		if ($stripNamespaces) $responseXML = trim(self::stripNamespaces($responseXML));
		if (defined('DEBUG_XML_ERRORS') && DEBUG_XML_ERRORS) $libXmlInternalErrors = libxml_use_internal_errors(true);

		$xmlElement = @simplexml_load_string($responseXML, null, LIBXML_ERR_WARNING);
		if (false === $xmlElement)
		{
			if (defined('DEBUG_XML_ERRORS') && DEBUG_XML_ERRORS)
			{
				$log = Sapo_Log::getLogger();
				$log->error("$uri XML Parse Errors");
				foreach (libxml_get_errors() as $error)
					$log->error("  {$error->message} on line {$error->line}, col {$error->column}");

				libxml_clear_errors();
				libxml_use_internal_errors($libXmlInternalErrors);
			}

			throw new UnexpectedResponseException("Failed to load XML from Http Response Body calling $uri");
		}
		if (defined('DEBUG_XML_ERRORS') && DEBUG_XML_ERRORS) libxml_use_internal_errors($libXmlInternalErrors);

		$mapper = new $resultClassName;
		$mapper->fillFromXMLData($xmlElement);

		return $mapper; 
	}

}

class UnexpectedResponseException extends Exception//extends CacheHelperRethrowableException
{
	public function __construct($message = null, $code = 0)
	{
		if(null == $message) $message = 'A remote service returned an unexpected response';
		parent::__construct($message, $code);
	}
}
