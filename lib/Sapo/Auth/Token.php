<?php

// example usage Sapo_Auth_Token::getServiceAuthToken('gis');
// must define in <shareLib>/config/config_<namespace>.ini the following entries
//    service.Credentials.ESBUsername
//    service.Credentials.ESBPassword

class Sapo_Auth_Token_NoCredentialsException extends Exception {}
class Sapo_Auth_Token
{
	private static $tokens = array();

	const CFG_ERR_MSG = "Configurations missing: service.Credentials.ESBUsername, service.Credentials.ESBPassword must be declared on config_%s.ini";

	public static function getServiceAuthToken($namespace)
	{
		try
		{
			$serviceConfig = Sapo_Config::getInstance($namespace)->get('service');
			if (!$serviceConfig) throw new Sapo_Auth_Token_NoCredentialsException("No credentials found in configuration file");
			$credentials = $serviceConfig->get('Credentials');
			if (!$credentials) throw new Sapo_Auth_Token_NoCredentialsException("No credentials found in configuration file");
		}
		catch(Exception $e)
		{
			Sapo_Log::error(sprintf(self::CFG_ERR_MSG, $namespace));
			Sapo_Log::exc($e);
			throw new Sapo_Auth_Token_NoCredentialsException("No credentials found in configuration file");
		}

		return self::getAuthToken($credentials->get('ESBUsername'), $credentials->get('ESBPassword'));
	}

	public static function getAuthToken($username, $password, $forceRefresh = false)
	{
		if (!$forceRefresh && array_key_exists($username, self::$tokens)) return self::$tokens[$username];

		$token = Sapo_Cache_Handler::get(__CLASS__, 'fetchAuthToken',
			array($username, $password),
			5*3600, 11*3600 // 5 h, 11h
		);

		self::$tokens[$username] = $token;
		return $token;
	}

	public static function fetchAuthToken($username, $password)
	{
		$baseUri = Sapo_Config::getInstance('auth')->get('service')->get('getTokenServiceUri');
		$res = file_get_contents($baseUri . '?ESBUsername=' . urlencode($username) . '&ESBPassword=' . urlencode($password));
		return trim(strip_tags($res));
	}

}
