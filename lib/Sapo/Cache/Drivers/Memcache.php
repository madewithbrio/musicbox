<?php
class Sapo_Cache_Drivers_Memcache extends Sapo_Cache_Drivers_Abstract implements Sapo_Cache_Drivers
{

	private $_options = array();
	private $_instance = null;
	
	public function __construct(array $options = array())
	{
		$this->setOptions($options);
	}
	
	public function setOptions(array $options = array())
	{
		$this->_options['hosts'] 						= $options['hosts'];
	}
	
	public function getInstance()
	{
		if (null === $this->_instance)
		{
			if (!isset($this->_options['hosts']))
			{
				throw new Exception ("memcache: need hosts");
			}
			$this->_instance = new Memcache();
			foreach (explode('#',$this->_options['hosts']) as $host)
				$this->_instance->addServer($host);
		}
		return $this->_instance;
	}

	public function get($key)
	{
		//Sapo_Log::getLogger()->debug("get cache: ".$key);
		$data = $this->getInstance()->get($key, MEMCACHE_COMPRESSED);
		$this->setLastResult((false === $data) ? Sapo_Cache::NOTFOUND : Sapo_Cache::SUCCESS);
		return (false === $data) ? null : $data;
	}
	
	public function set($key, $value, $ttl = 0)
	{
		//Sapo_Log::getLogger()->debug("set cache: ".$key);
		$value = is_scalar($value) ? (string)$value : $value;
		#$compress = is_bool($value) || is_int($value) || is_float($value) ? false : MEMCACHE_COMPRESSED;
		
		($this->getInstance()->set($key, $value, MEMCACHE_COMPRESSED, $ttl)) 
		? parent::setLastResult(Sapo_Cache::SUCCESS) 
		: parent::setLastResult(Sapo_Cache::FAILURE);
	}
	
	public function delete($key)
	{
		($this->getInstance()->delete($key)) 
		? parent::setLastResult(Sapo_Cache::SUCCESS) 
		: parent::setLastResult(Sapo_Cache::FAILURE);
	}
	
	/**
	public function delete();
	public function isValid();
	public function increment();
	public function decrement();
	public function append();
	**/
}
