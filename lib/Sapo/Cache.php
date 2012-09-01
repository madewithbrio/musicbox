<?php

class Sapo_Cache
{
	const NOTFOUND 		= 0;
	const SUCCESS 		= 1;
	const FAILURE		= -10;
	const FORCEREFRESH	= -1;
	
	private static $_instances = array();
	
	public static function factory($driver, $options = array())
	{
		$driver = 'Sapo_Cache_Drivers_'.ucfirst(strtolower($driver));
		if (!class_exists($driver, true))
			throw new Exception ("Driver not found");
		
		$reflectionClass = new ReflectionClass($driver);
		if (!$reflectionClass->isSubclassOf("Sapo_Cache_Drivers")) 
			throw new Exception ("it's not a cache driver");
		if (!$reflectionClass->IsInstantiable())
			throw new Exception ("driver is not instantiable");
		return new $driver($options);
	}
	
	public static function getInstance($driver = null, $options = array())
	{
		if ($driver == null) {
			$driver = Sapo_Config::getInstance()->get("cache")->get("driver");
			$options = Sapo_Config::getInstance()->get("cache")->get("params")->toArray();
		}
		$driver = ucfirst(strtolower($driver));
		if (!isset(self::$_instances[$driver]))
		{
			self::$_instances[$driver] = self::factory($driver, $options);
		}
		return self::$_instances[$driver];
	}
}

interface Sapo_Cache_Drivers
{
	public function get($key);
	public function set($key, $value, $ttl = 0);
	public function delete($key);
	public function getLastResult();
}

class Sapo_Cache_StdClass extends Sapo_DataTypes_StdClass
{
	function __construct($key, &$data, $ttl = 0)
	{
		parent::__construct(array('KEY' => $key, 'DATA' => $data, 'TTL' => $ttl, 'CREATE_TIMESTAMP' => time())
						  , array('allowNewProperties' => true, 'throwException' => false));
	}
}

abstract class Sapo_Cache_Drivers_Abstract
{
	private $_lastResult = null;
	
	protected function setLastResult($code)
	{
		$this->_lastResult = $code;
	}
	
	public function getLastResult()
	{
		if (defined("FORCEREFRESH") && FORCEREFRESH) return Sapo_Cache::FORCEREFRESH;
//		if ($this->_lastResult != Sapo_Cache::SUCCESS) Sapo_Log::getLogger()->info("cache not found"); 
		return $this->_lastResult;
	}

	public function get_set($var, $value, $ttl = 0)
	{
		if (null !== $value && '' !== $value)
		{
			$this->set($var, $value, $ttl);
			return $value;
		}
		return $this->get($var);
	}
}
