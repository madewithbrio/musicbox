<?php

class Sapo_HTTP_TextToSOAP
{
	public static function getSoapHeader($namespace)
	{
		try {
			$token = Sapo_Auth_Token::getServiceAuthToken($namespace);
		} catch(Sapo_Auth_Token_NoCredentialsException $e) { return ''; }

		return '
			<def:ESBCredentials>
				<def:ESBToken>'. $token . '</def:ESBToken>
			</def:ESBCredentials>';
	}

	protected static function addSoapEnvelope($header, $content, $namespaceDeclarations)
	{
		$namespaceDeclarationsText = '';
		if ($namespaceDeclarations) foreach ($namespaceDeclarations as $namespaceDeclaration) $namespaceDeclarationsText .= " xmlns:$namespaceDeclaration";

		return
			'<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:def="http://services.sapo.pt/definitions" ' . $namespaceDeclarationsText . '>
				<soapenv:Header>' . $header . '</soapenv:Header>
				<soapenv:Body>' . $content . '</soapenv:Body>
			</soapenv:Envelope>';
	}

	public static function soapRequest($namespace, $soapMethod, $request, $timeout = 3)
	{
		$forceRefresh = false;

		$soapConfig = Sapo_Config::getInstance($namespace)->get('SOAP');
		$methodConfig = $soapConfig->get($soapMethod);
		$uri = $soapConfig->get('serviceURI');

		$resultClassName = $methodConfig->get('handler');
		$soapAction = $methodConfig->get('action');

		$stripNamespaces = true;
		try {
			$stripNSConfig = $methodConfig->get('stripNS');
			if ($stripNSConfig === 0 || $stripNSConfig === false || (0 == strcmp($stripNSConfig, 'false')))
				$stripNamespaces = false;
		} catch(Exception $e) {}

		$namespaceDeclarations = explode('#', $soapConfig->get('namespaces'));

		do {
			$header = self::getSoapHeader($namespace, $forceRefresh);
			$envelope = self::addSoapEnvelope($header, $request, $namespaceDeclarations);
			$response = self::post($uri, $envelope, $resultClassName, $soapAction, $stripNamespaces, $timeout);
			if ($response || $forceRefresh) return $response;
			$forceRefresh = true;
		} while(1);
		return null;
	}

	public static function post($uri, $request, $resultClassName, $soapAction = null, $stripNamespaces = false, $timeout = 3)
	{
		$requestLen = strlen ($request);

		$headers = "";
		$headers.= "Connection: close\r\n";
		$headers.= "Content-Length: $requestLen\r\n";
		$headers.= "User-Agent: SapoMobile/1.0\r\n";
		$headers.= "Accept-Charset: utf-8;\r\n";
		$headers.= "Content-type: text/xml;charset=utf-8\r\n";
		if ($soapAction) $headers.= "SOAPAction: \"$soapAction\"\r\n";

		$context = stream_context_create(array(
			'http' => array(
				'method' => 'POST',
				'header' => $headers,
				'content' => $request,
				'timeout' => $timeout,
		)));

		$responseContent = file_get_contents($uri, null, $context);

if (defined('DEBUG_SOAP') && DEBUG_SOAP)
{
	echo "\n\nRequest:\n"; var_dump($headers); echo "\n\n"; var_dump($request);
	echo "\n\nResponse:\n"; var_dump($http_response_header); echo "\n\n"; var_dump($responseContent);
}


		$httpReturn = explode(' ', $http_response_header[0]);
		$httpReturnCode = $httpReturn[1];
		if (200 == $httpReturnCode) return self::processResponse($uri, $responseContent, $resultClassName, $stripNamespaces);

		Sapo_Log::error("Error calling $uri\n$headers\n$request\n\n{$http_response_header[0]}\n$responseContent");
		return null;
	}

	protected static function processResponse($uri, $responseXML, $resultClassName, $stripNamespaces = false)
	{
		if ($stripNamespaces) $responseXML = self::stripNamespaces($responseXML);

		if (defined('DEBUG_XML_ERRORS') && DEBUG_XML_ERRORS) $libXmlInternalErrors = libxml_use_internal_errors(true);

		$xmlElement = @simplexml_load_string($responseXML);
		if (false === $xmlElement)
		{
			if (defined('DEBUG_XML_ERRORS') && DEBUG_XML_ERRORS)
			{
				$log = Sapo_Log::getLogger();
				$log->err("$uri XML Parse Errors");
				foreach (libxml_get_errors() as $error)
					$log->err("  {$error->message} on line {$error->line}, col {$error->column}");

				libxml_clear_errors();
				libxml_use_internal_errors($libXmlInternalErrors);
			}

			throw new UnexpectedResponseException("Failed to load XML from Http Response Body calling $uri");
		}
		if (defined('DEBUG_XML_ERRORS') && DEBUG_XML_ERRORS) libxml_use_internal_errors($libXmlInternalErrors);

		$mapper = new $resultClassName;
		$mapper->fillFromXMLData($xmlElement->Body);

		return $mapper; 
	}

	public static function stripNamespaces($xmlDoc)
	{
		return preg_replace('/(<\/?)([A-Za-z0-9-]+:)/', '$1', $xmlDoc);
	}
}
