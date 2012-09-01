<?php
class Sapo_Log
{
	private static $_dissabled = false;
	private static $_instance;
	
	/** TODO implement this class **/
	
	/**
	 * Dont let thos class be instanced out 
	 */
	private function __construct()
	{
		
	}
	
	/**
	 * Dont let this class be cloned
	 */
	private function __clone()
	{
		
	}
	
	public static function factory($driver, $options = array())
	{
		$driver = "Sapo_Log_Writers_".ucfirst($driver);
		if (!class_exists($driver))
		{
			throw new Exception ("Writer for log not found");
		}
		return new $driver($options);
	}
	
	public static function setDissabled()
	{
		self::$_dissabled = true;
	}
	
	/**
	 * Sngleton class, this method return instance
	 * @return Sapo_Log
	 */
	public static function getLogger()
	{
		if (self::$_dissabled === TRUE)
		{
			return self::factory('Dummy');
		} 
		
		if (null === self::$_instance)
		{
			$driver = Sapo_Config::getInstance()->get("log")->get("driver");
			$options = Sapo_Config::getInstance()->get("log")->get("params")->toArray();
			self::$_instance = self::factory($driver, $options);
		}
		return self::$_instance;
	}

	public static function error($message)
	{
		$logger = self::getLogger();
		$logger->error($message);
	}

	public static function err($message)
	{
		$logger = self::getLogger();
		$logger->error($message);
	}

	public static function exc($exception)
	{
		$logger = self::getLogger();
		$logger->error($exception->getMessage() . "\n" . $exception->getTraceAsString());
	}
} 

interface Sapo_Log_Writers
{
	public function set($level, $msg, $exception = null);
}
