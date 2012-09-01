<?php
//Start a memcache session
//require_once 'HTTP/Session.php'
//SessionHelper::startMemcacheSession('SAPOMeoTVUser');

class Sapo_HTTP_Referer
{
	public static function sanitize_decode($referer)
	{
		return self::sanitize(self::decode($referer));
	}

	public static function decode($refererParam, $quoteStyle = ENT_QUOTES, $charset = 'UTF-8')
	{
		return html_entity_decode(urldecode($refererParam), $quoteStyle, $charset);
	}

	public static function sanitize($referer)
	{
		return str_replace("'", "", strip_tags($referer));
	}

	public static function getRefererInReferer($referer)
	{
		$urlParts = explode('&', $referer);
		for($i = 0; $i < count($urlParts); $i++)
		{
			if ('r=' == substr($urlParts[$i], 0, 2))
			{
				$encodedSecondReferer = substr($urlParts[$i], 2);
				$decodedSecondReferer = urldecode($encodedSecondReferer);
				return $decodedSecondReferer;
			}
		}
		return null;
	}

	public static function isValid($referer)
	{
		if ($referer[0] == '/') return true; // root link 

		// link must be http://{serverName}/...
		$matchingUrl = 'http://' . Sapo_HTTP_Server::getRequestHost() . '/';
		if (substr($link, 0, strlen($matchingUrl)) == $matchingUrl) return true;
		
		return false;
	}

	/**
	 * @Decrapted
	 */
	public static function getRequestHost()
	{
		return Sapo_HTTP_Server::getRequestHost();
	}

	/**
	 * @Decrapted
	 */
	public static function getRequestUri()
	{
		return Sapo_HTTP_Server::getRequestUri();
	}

	public static function getFullRequestUri()
	{
		$host = self::getRequestHost();
		$requestUri = self::getRequestUri();
		return 'http://' . $host . $requestUri;
	}

	public static function getRefererUri()
	{
		$uri = @$_SERVER['HTTP_REFERER'];
		return str_replace(array("'", '<', '>'), "", strip_tags($uri));
	}

	public static function getRefererHost()
	{
		preg_match('/^(http:\/\/)?([^\/:]+)/', Sapo_HTTP_Referer::getRefererUri(), $matches);
		return $matches ? $matches[2] : null;
	}


	public static function getQueryString($pageVar = 'page')
	{
		// Sort out query string to prevent messy urls
		$querystring = array();
		$qs = array();

		$queryVars = array_merge($_GET, $_POST);
		if ($queryVars)
			foreach ($queryVars as $var => $value)
				if ($var != $pageVar) $qs[$var] = $value;

		foreach ($qs as $name => $value)
			$querystring[] = preg_replace('/[^\w\d_-]+/i', '', $name) . '=' . urlencode(stripslashes($value));

		return implode('&amp;', $querystring);
	}

}
