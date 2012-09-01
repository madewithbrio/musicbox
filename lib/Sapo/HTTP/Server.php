<?php

class Sapo_HTTP_Server
{
	static public function getRemoteAddress()
	{
		if (array_key_exists('HTTP_X_REAL_FORWARDED_FOR', $_SERVER))
		{
			$ipAddressList = $_SERVER['HTTP_X_REAL_FORWARDED_FOR']; //this value is usually a csv list, we're interested in the last value
			$auxList = @explode(',', $ipAddressList);
			return $auxList[count($auxList) - 1];
		}
		if (array_key_exists('HTTP_X_REAL_IP', $_SERVER)) return $_SERVER['HTTP_X_REAL_IP'];
		if (array_key_exists('REMOTE_ADDR', $_SERVER)) return $_SERVER['REMOTE_ADDR'];
		return '127.0.0.1';
	}

	static public function getUserAgent()
	{
		$userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : (array_key_exists('argv', $_SERVER) ? "Command line script" : "unknown");
		if (key_exists('HTTP_X_OPERAMINI_PHONE_UA', $_SERVER))
			$userAgent = $_SERVER['HTTP_X_OPERAMINI_PHONE_UA'];
		return $userAgent;
	}

	static public function getCurrentTime()
	{
		return @$_SERVER['REQUEST_TIME'] ? $_SERVER['REQUEST_TIME'] : time();
	}

	public static function requestQueryString()
	{
		// Sort out query string to prevent messy urls, XSS attacks, etc
		$querystring = array();

		$queryVars = array_merge($_GET, $_POST);
		foreach ($queryVars as $name => $value)
			$querystring[] = $name . '=' . urlencode(stripslashes($value));

		return '?' . implode('&amp;', $querystring);
	}

	public static function getRequestHost()
	{
		if (defined('SERVERNAME') && SERVERNAME) return SERVERNAME;
		return $_SERVER['HTTP_HOST'];
	}

	public static function getRequestUri()
	{
		$uri = @$_SERVER['REQUEST_URI'];
		return str_replace(array("'", '<', '>'), "", strip_tags($uri));
	}
	
	public static function requestPath()
	{
		$uri = $_SERVER['REQUEST_URI'];
		$parsedUri = str_replace(array("'", '<', '>'), "", strip_tags($uri));
		return preg_replace('/\?.*$/', '', $parsedUri);
	}

	public static function getAbsoluteRequestUrl()
	{
		return self::requestPath() . self::requestQueryString();
	}


	public static function fakeInput($inputHandler, $name, $value)
	{
		$inputHandler->$name = $value;
		$_GET[$name] = $value;
	}

	public static function innerRedirectUrl()
	{
		$request = $_SERVER['REQUEST_URI'];
		$parts = explode('?', $request);
		$path = $parts[0];
		$queryString = @$parts[1];

		$slashPos = strrpos($path, '/') or -1;
		$file = substr($path, $slashPos+1);
		return "$file|$queryString";
	}

	public static function getCanonicalServerName()
	{
		return defined('APP_CANONICAL_HOST') ? APP_CANONICAL_HOST : (array_key_exists('HTTP_HOST', $_SERVER) ? $_SERVER['HTTP_HOST'] : gethostname());
	}
}
