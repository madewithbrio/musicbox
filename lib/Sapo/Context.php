<?php

class Sapo_Context 
{
	const USER_TRACKING_COOKIE = "SMUTC";
	const GLOBAL_SESSION_TRACKING_COOKIE = "SMGSTC";
	const HOST_SESSION_TRACKING_COOKIE = "SMHSTC";
	const TRACKING_COOKIE_LEN = 18; // @see random64bitsInteger below

	private static $processedUri;
	private static $supportGZip;
	private static $input;
	private static $ajaxRequest;
	private static $revision;

	private static $haveAllreadyTrackUser = false;
	private static $requestUriHash;

	private static $language;

	public static function init() 
	{
		self::$input = new Sapo_Context_Input();
		if (array_key_exists('HTTP_HOST', $_SERVER)) {
			Sapo_Helpers_Cookie::setCookieParams();
			session_start();
			self::trackUser();

			self::$processedUri = new Sapo_Context_ProcessedUri();
			self::$supportGZip = strpos(@$_SERVER['HTTP_ACCEPT_ENCODING'], "gzip") !== false;
			self::$ajaxRequest = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
		}
	}
	
	//// TRACKER
	protected static function getUniqIdPrefix() 
	{
		if (!self::$requestUriHash) self::$requestUriHash = sha1(Sapo_HTTP_Referer::getRequestUri());
		return self::$requestUriHash;
	}

	// 2^64 = 1*10^19 can be 18 digits safely, 19 if unsigned - 2^32 = 4*10^9 - maxint only supports 9 digits in 32bits architecture
	protected static function random64bitsInteger()
	{
		return 
			str_pad(mt_rand(0, 999999999), 9, '0', STR_PAD_LEFT) . 
			str_pad(mt_rand(0, 999999999), 9, '0', STR_PAD_LEFT);
	}

	protected static function trackUser()
	{
		if (self::$haveAllreadyTrackUser) return;
		
		$isNewUser = $isNewHostSession = $isNewGlobalSession = false;
		try
		{
			$userValue = Sapo_Helpers_Cookie::getCookie(self::USER_TRACKING_COOKIE);
			if ($userValue === null || strlen($userValue) != self::TRACKING_COOKIE_LEN)
			{
				$userValue = self::random64bitsInteger();
				$isNewUser = true;
			}

			$globalSessionValue = Sapo_Helpers_Cookie::getCookie(self::GLOBAL_SESSION_TRACKING_COOKIE);
			if ($globalSessionValue === null || strlen($globalSessionValue) != self::TRACKING_COOKIE_LEN)
			{
				$globalSessionValue = self::random64bitsInteger();
				$isNewGlobalSession = true;
			}

			$hostSessionValue = Sapo_Helpers_Cookie::getCookie(self::HOST_SESSION_TRACKING_COOKIE);
			if ($hostSessionValue === null || strlen($hostSessionValue) != self::TRACKING_COOKIE_LEN)
			{
				$hostSessionValue = self::random64bitsInteger();
				$isNewHostSession = true;
			}

			//$userLifetime = 2419200; // = 60*60*24*28 = 4 weeks
			$domain = Sapo_Helpers_Cookie::getCookieDomain();
			$host = defined('SERVERNAME') ? SERVERNAME : $_SERVER['HTTP_HOST'];
			$sessionLifetime = 1800; //60*30 // 30 m
			$expire = Sapo_HTTP_Server::getCurrentTime() + $sessionLifetime;

			Sapo_Helpers_Cookie::setCookieWithDomain(
				self::USER_TRACKING_COOKIE, $userValue, 
				0, //Sapo_HTTP_Server::getCurrentTime() + $lifetime, // 0 - no expire
				$path = '/', $domain, $httpOnly = true
			);

			Sapo_Helpers_Cookie::setCookieWithDomain(
				self::GLOBAL_SESSION_TRACKING_COOKIE, $globalSessionValue, 
				$expire,
				$path = '/', $domain, $httpOnly = true
			);

			Sapo_Helpers_Cookie::setCookieWithDomain(
				self::HOST_SESSION_TRACKING_COOKIE, $hostSessionValue, 
				$expire,
				$path = '/', $host, $httpOnly = true
			);

			Sapo_Stats_Analytics::init($isNewUser, $isNewHostSession, $isNewGlobalSession, $userValue, $hostSessionValue, $globalSessionValue);
			self::$haveAllreadyTrackUser = true;
		} catch (Exception $e) {Sapo_Log::exc($e); }
	}

	//// GETTERS
	public static function getProcessedUri()
	{
		if (null === self::$processedUri) throw new Exception("Context not initialized");
		return self::$processedUri;
	}
	
	public static function isAjaxRequest()
	{
		return self::$ajaxRequest;
	}
	
	public static function isGZipSupported()
	{
		return self::$supportGZip;
	}
	
	public static function getInput()
	{
		return self::$input;
	}
	
	public static function getEnvironment()
	{
		return ENV;
	}
	
	public static function getAppName()
	{
		return _PROJECT;
	}
	
	public static function getAppTitle()
	{
		if (defined('APP_TITLE') && (bool) APP_TITLE) return APP_TITLE;
		return self::getAppName();
	}

	public static function getLanguage()
	{
		if (null === self::$language) {
			$languages_allow = array('pt','en','es'); // @todo
			$language = Sapo_Helpers_Cookie::getCookie('lang');
			if (empty($language))
			{
				list($language,) = explode(",", $_SERVER['HTTP_ACCEPT_LANGUAGE']);
			}

			$language = strtolower(substr($language, 0,2));
			if (in_array($language, $languages_allow)){ 
				self::$language = $language;
			} else {
				self::$language = array_shift($languages_allow);
			}
		}
		return self::$language;
	}

	public static function setLanguage($language)
	{
		$languages_allow = array('pt','en','es'); // @todo
		if (in_array($language, $languages_allow)){ 
			Sapo_Helpers_Cookie::setCookie('lang', $language);
		}
	}

	public static function getTheme()
	{
		try {
			$rc  = new ReflectionMethod('Helpers_Context', 'getTheme');
			if ($rc->isStatic()) {
				return $rc->invoke(null);
			}
		} catch (Exception $e) {}
		if (defined('_THEME') && _THEME) return _THEME;
	}

	public static function getUseBSU()
	{
		try {
			$rc  = new ReflectionMethod('Helpers_Context', 'getUseBSU');
			if ($rc->isStatic()) {
				return $rc->invoke(null);
			}
		} catch (Exception $e) {}
		return (!defined('HAS_BSU') || HAS_BSU);
	}

	public static function isDevelEnvironment() 
	{
		return (self::getEnvironment() == 'local' || self::getEnvironment() == 'development');
	}

	public static function getBaseProject() 
	{
		if (defined("_BASE_PROJECT") && _BASE_PROJECT) {
			return _BASE_PROJECT;
		}
		return null;
	}

	public static function setGeoLocation($locationObj)
	{
		$_SESSION['_location'] = $locationObj;
	}

	public static function getGeoLocation()
	{
		if (isset($_SESSION['_location']))
			return $_SESSION['_location'];
		return null;
	}

	public static function getRequestHostUri()
	{
		
		$port = $_SERVER['SERVER_PORT'] !== 80 ? ":" . $_SERVER['SERVER_PORT'] : "";
		$protocol = (isset($_SERVER['HTTPS'])) ? "https://" : "http://";
		return 	$protocol . $_SERVER['HTTP_HOST'] . $port;

	}

	public static function getRevision()
	{
		if (self::$revision === null) {
			self::$revision = trim(file_get_contents(sprintf("%s/Revision", _PROJECT_LIB_SHARED)));
		}
		return self::$revision;
	}

	public static function getRequestCacheKey()
	{
		$keyFormat = sprintf("%s_%s_%s_%s_%s_%s" , 
							$_SERVER['REQUEST_URI'],
							_PROJECT, ENV , 
							Sapo_Context::getPlatform(), (int) Sapo_Context::isAjaxRequest(), (int) Sapo_Context::isLightDevice());
		return md5($keyFormat);
	}
}


class Sapo_Context_Environment
{
	const DEV_LOCAL = "local";
	const DEV_LAB 	= "development";
	const PROD 		= "production";
	const STG 		= "staging";

	private function __construct(){}
	public static function valueof($string) {
		$const = strtoupper($string);
		if (!defined('self::'.$const)) { throw new Exception("environment not defined"); }
		return constant ('self::'.$const);
	}
}

class Sapo_Context_ProcessedUri
{
	private $section;
	private $filters;
	private $query;
	public function __construct()
	{
		if (preg_match('@^\/([^\/\?\!]*)\/?([^\?\!]*)[\/\?\!]*(.*)$@', @$_SERVER['REQUEST_URI'], $parseRequestURI)) {
			$this->section = $parseRequestURI[1];
			$this->filters = @explode("/", $parseRequestURI[2]);
			$this->query = @explode("&", $parseRequestURI[3]);
			Sapo_Context_ProcessedUri_Path::setInstance($_SERVER['REQUEST_URI']);
		}
	}
	
	public function getSection()
	{
		return $this->section;
	}
	
	public function getFilter()
	{
		return $this->filters;
	}
	
	public static function getPath()
	{
		return Sapo_Context_ProcessedUri_Path::getInstance();
	}

	public function getQuery()
	{
		return $this->query;
	}
}

class Sapo_Context_ProcessedUri_Path {
	private static $instance = null;
	public $path = array();

	private function __construct($path = null) {
		if (null === $path) $path = @$_SERVER['REQUEST_URI'];
		$this->path = @explode("/", preg_replace('@^\/([^\?\!]*)[\?\!]?(.*)$@', '$1', $path));
	}

	public static function getInstance() {
		if(self::$instance == null){
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function setInstance($path) {
		self::$instance = new Sapo_Context_ProcessedUri_Path($path);
	}

	public function findPath($value)
	{
		for($i = 0, $j = sizeof($this->path); $i < $j; $i ++) {
			if ($this->path[$i] == $value){
				$i++;
				return (isset($this->path[$i])) ? $this->path[$i] : null;
			} 
		}
		return null;
	}

	public function __isset($value)
	{
		return in_array($value, $this->path);
	}

	public function __get($value)
	{
		return $this->findPath($value);
	}
}

class Sapo_Context_Input {
	private $rules = array();
	
	public function setFilterRules($array) {
		$this->rules = $array;
	}
	
	public function getFilterRules() {
		return $this->rules;
	}
	
	public function setFilterRuleForInput($input, $rules) {
		$this->rules[$input] = $rules;
	}
	
	public function getFilterRuleForInput($input) {
		if (!isset($this->rules[$input])) return null;
		return $this->rules[$input];
	}

	public function __get($variable_name) {
		$rules = $this->getFilterRuleForInput($variable_name);
		
		$filterOptions = null;

		if (null === $rules) { #not found rules
			$filter = FILTER_DEFAULT;
		} else {
			if (is_int($rules)) { # only have filter
				$filter = $rules;
			} else { #complex rules
				$filter = (isset($rules['filter'])) ? $rules['filter'] : FILTER_DEFAULT;
				if (!empty($rules['options']) || !empty($rules['flags'])) {
					$filterOptions = array();
					if (!empty($rules['options'])) { $filterOptions['options'] = $rules['options']; }
					if (!empty($rules['flags'])) { $filterOptions['flags'] = $rules['flags']; }
				}

			}
		}
		$type = (filter_has_var(INPUT_POST, $variable_name)) ? INPUT_POST : INPUT_GET;

		return filter_input($type, $variable_name, $filter, $filterOptions);
	}

	public function __isset($variable_name) { return isset($_GET[$variable_name]) || isset($_POST[$variable_name]); }
}
