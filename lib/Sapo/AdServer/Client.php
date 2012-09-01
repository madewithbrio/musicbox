<?php

if (!class_exists("SDKCommunication", false))require_once "Sapo/AdServer/SDKCommunication.php"; // new openDisplay
if (!isset($GLOBALS['zones'])) require_once 'Sapo/AdServer/SDKConfiguration.php';

class Sapo_AdServer_Client {

	const ADCOOKIE = "mad";

	protected static function getSessionCache() {
		return Sapo_Cache::getInstance('Session');
	}

	public static function clientCanRequestAd() {
		$userAgent = Sapo_HTTP_Server::getUserAgent();
		if (preg_match('/ISA Server Connectivity Check|Apache|check_http|SmartRateTest|ProxySG Appliance|Slurp|google-sitemap|googlebot|msnbot|yahooseeker|yahoo-blogs/i', $userAgent)) return false;
		
		$clientIpAddress = Sapo_HTTP_Server::getRemoteAddress();
		if (preg_match('/^66\.249\..+/',$clientIpAddress)) return false;

// @todo call DeviceAtlas
		return true;
	}

	public static function rememberAdCookieValue($adCookieValue = null)
	{
		Sapo_Helpers_Cookie::setCookie(self::ADCOOKIE, $adCookieValue, 0, $path = '/', $httpOnly = true);
		self::getSessionCache()->set(self::ADCOOKIE, $adCookieValue);
	}

	public static function getAdCookieValue()
	{
		$value = Sapo_Helpers_Cookie::getCookie(self::ADCOOKIE);
		if ($value) return $value;
		$value = self::getSessionCache()->get(self::ADCOOKIE);
		return $value;
	}

	public static function getPub($pubId, $extraParams = null) 
	{
		if (!self::clientCanRequestAd()) 
			throw new Sapo_AdServer_Client_Exception("cant render pub", Sapo_AdServer_Client_Exception::CANTRENDERPUB);

		$pubTagTemplate = Sapo_Config::getInstance('pub')->get($pubId);
		if (!$pubTagTemplate) 
			throw new Sapo_AdServer_Client_Exception("Zone Not Found: ".$pubId, Sapo_AdServer_Client_Exception::ZONENOTFOUND);

		$pubZone = $pubTagTemplate;
		$userAgent = Sapo_HTTP_Server::getUserAgent();
		$cookieValue = self::getAdCookieValue();
		$ip = Sapo_HTTP_Server::getRemoteAddress();
		$referer = Sapo_HTTP_Referer::getFullRequestUri();

		$contentProvider = '';
		if (is_array($extraParams) && array_key_exists('affiliate', $extraParams))
			$contentProvider = substr(preg_replace('/[^a-z]/', '', strtolower($extraParams['affiliate'])), 0, 10); // allow only lowercase ascii letters (10 char limit)

		$adc = new SDKCommunication();
		$res = $adc->call_OpenDisplay($pubZone, $userAgent, 'simpleadxml', "", $cookieValue, $ip, $referer, $contentProvider, $imp=1);

		if (!$res) throw new Sapo_AdServer_Client_Exception($adc->error, Sapo_AdServer_Client_Exception::CALLERROR);
		if (!$cookieValue) self::rememberAdCookieValue($adc->OAID);
		return new Sapo_DataTypes_Pub($adc->click, $adc->image, $adc->size, $adc->impression, $adc->extimpression);
	}


/**

**/
	public static function getPubObj($pubId, $affiliated = null) 
	{
		if (!self::clientCanRequestAd()) return null;

		$pubTagTemplate = Sapo_Config::getInstance('pub')->get($pubId);
		if (!$pubTagTemplate) throw new Sapo_AdServer_Client_Exception("Zone: ".$pubId, Sapo_AdServer_Client_Exception::ZONENOTFOUND);

		$zonas = array_flip($GLOBALS['zones']);
		$zona = $zonas[$pubTagTemplate];
		return new Sapo_DataTypes_Pub_Obj($zona, null, null, $pubId, $affiliated);
	}

	public static function getGalleryPubObj($pubId, $affiliated = null)
	{
		if (!self::clientCanRequestAd()) return null;

		$pubTagTemplate = null;
		$pubConfig = Sapo_Config::getInstance('pub');
		$galleryPub = $pubConfig->get('gallery');

		if ($galleryPub) $pubTagTemplate = $galleryPub->get($pubId);
		if (!$pubTagTemplate) 
		{
			Sapo_Log::error("Entry: gallery.$pubId not found in local config_pub.ini trying just $pubId");
			return self::getPubObj($pubId, $affiliated);
		}

		$zonas = array_flip($GLOBALS['zones']);
		$zona = $zonas[$pubTagTemplate];
		return new Sapo_DataTypes_Pub_Obj($zona, null, null, $pubId, $affiliated);
	}


	public static function getInteractiveAd($pubZone, $lat, $lon, $keywords = '')
	{
		$userAgent = Sapo_HTTP_Server::getUserAgent();
		$cookieValue = self::getAdCookieValue();
		$ip = Sapo_HTTP_Server::getRemoteAddress();
		$referer = Sapo_HTTP_Referer::getFullRequestUri();
		$contentProvider = '';

		$adc = new SDKCommunication();
		$res = $adc->call_OpenDisplay($pubZone, $userAgent, 'simpleadxml', "", $cookieValue, $ip, $referer, $contentProvider, $imp=1, $keywords, $lat, $lon);
		if (!$res) return Sapo_Log::err($adc->error);

		if (!$cookieValue) self::rememberAdCookieValue($adc->OAID);

		if (null == $adc->image || strstr($adc->image, '1x1.gif')) return ;//Sapo_Log::err("No pub returned for pubId = $pubId : zone = $pubZone");

		$ad = array(
			'image' => $adc->image,
			'click' => $adc->click,
			'impression' => $adc->impression,
			'extimpression' => $adc->extimpression
		);

		return json_encode($ad);
	}
}

class Sapo_AdServer_Client_Exception extends Exception {
	const ZONENOTFOUND = -10;
	const CALLERROR = -20;
	const CANTRENDERPUB = -30;
}
