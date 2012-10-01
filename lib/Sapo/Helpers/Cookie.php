<?php
# Get a cookie value
# CookieHelper::getCookie(CookieHelper::TRACKING_COOKIE)

class Sapo_Helpers_Cookie
{
	/**
	 * Sets cookie value to value, with an infinite expiration-time 
	 */
	public static function setCookie($name, $value, $expire = 0, $path = '/', $httpOnly = false) 
	{
		$_v = self::getCookie($name);
		if (null !== $_v && $_v !== $value) {
			self::removeCookie($name,$path,$httpOnly);
		}
		if ($_v !== $value) {
			setcookie($name, $value, $expire, $path, $domain = null, $secure=false, $httpOnly);
		}
	}

	public static function setCookieWithDomain($name, $value, $expire = 0, $path = '/', $domain = null, $httpOnly = false) 
	{
		$_v = self::getCookie($name);
		if (null !== $_v && $_v !== $value) {
			self::removeCookieWithDomain($name,$path,$domain,$httpOnly);
		}
		if ($_v !== $value) {
			setcookie($name, $value, $expire, $path, $domain, $secure=false, $httpOnly);
		}
		}

	public static function removeCookie($name, $path = '/', $httpOnly = false)
	{
		
		return setcookie($name, null, time()-100000, $path, $domain = null, $secure=false, $httpOnly);
	}

	public static function removeCookieWithDomain($name, $path = '/', $domain = null, $httpOnly = false)
	{
		return setcookie($name, null, time()-100000, $path, $domain, $secure=false, $httpOnly);
	}

	public static function getCookieDomain()
	{
		if (defined('COOKIE_DOMAIN')) return COOKIE_DOMAIN;
		else return defined('SERVERNAME') ? SERVERNAME : $_SERVER['HTTP_HOST'];
	}

	public static function setCookieParams()
	{
		session_name('Musicbox');
		session_set_cookie_params(0, '/', self::getCookieDomain(), false, true);
	}

	/**
	 * Gets cookie value or null if cookie does not exist 
	 * @todo Beware Cookie value is not escaped!
	 */
	public static function getCookie($name)
	{
		if (!array_key_exists($name, $_COOKIE)) return null;
		return $_COOKIE[$name];
	}
}
