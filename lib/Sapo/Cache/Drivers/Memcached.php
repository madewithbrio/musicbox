<?php
class Sapo_Cache_Drivers_Memcached extends Sapo_Cache_Drivers_Abstract implements Sapo_Cache_Drivers
{

	private $_options = array();
	private $_instance = null;
	
	public function __construct(array $options = array())
	{
		$this->setOptions($options);
	}
	
	public function setOptions(array $options = array())
	{
		$this->_options['hosts'] 				= explode("#", $options['hosts']);
		#$this->_options['OPT_SERIALIZER']		= (isset($options['serializer'])) 	? $options['serializer'] 		: ((Memcached::HAVE_JSON) 
		#																														? Memcached::SERIALIZER_JSON 
		#																														: Memcached::SERIALIZER_PHP);
		$this->_options['OPT_PREFIX_KEY']		= (isset($options['prefix']))		? $options['prefix']		 	: 'SAPO_';
		#$this->_options['OPT_HASH']				= (isset($options['hash']))			? $options['hash']			 	: Memcached::HASH_DEFAULT;
		#$this->_options['OPT_DISTRIBUTION'] 	= (isset($options['distribution']))	? $options['distribution']		: Memcached::OPT_LIBKETAMA_COMPATIBLE;
	}
	
	public function getInstance()
	{
		if (null === $this->_instance)
		{
			if (!isset($this->_options['hosts']) || !is_array($this->_options['hosts']))
			{
				throw new Exception ("memcached: need hosts");
			}
			$hosts = array();
			foreach ($this->_options['hosts'] as $host)
			{
				$hosts[] = explode(':', $host);
			}
			
			$this->_instance = new Memcached();
			$this->_instance->addServers($hosts);
			foreach ($this->_options as $key => $value)
			{
				if (strpos('OPT_', $key))
				{
					$this->_instance->setOption(Memcached::$key, $value);
				}
			}
		}
		return $this->_instance;
	}

	public function get($key)
	{
		//Sapo_Log::getLogger()->info("get cache: ".$key);
		
		$data = $this->getInstance()->get($key);

		//$size = Sapo_Helpers_String::utf8_strlen($data) / 1024;
		//Sapo_Log::getLogger()->info("get cache: ".$key." with size: ".$size);
		
		$this->setLastResult($this->getInstance()->getResultCode());
		return ($this->getInstance()->getResultCode() != Memcached::RES_SUCCESS) ? null : unserialize($data);
	}
	
	public function set($key, $value, $ttl = 0)
	{
		$value = serialize($value);
		//$size = Sapo_Helpers_String::utf8_strlen($value) / 1024;

		//Sapo_Log::getLogger()->info("set cache: ".$key." size: ".$size);
		($this->getInstance()->set($key, $value, $ttl)) 
			? parent::setLastResult(Sapo_Cache::SUCCESS) 
			: parent::setLastResult(Sapo_Cache::FAILURE);
	}
	
	public function delete($key)
	{
		($this->getInstance()->delete($key)) 
		? parent::setLastResult(Sapo_Cache::SUCCESS) 
		: parent::setLastResult(Sapo_Cache::FAILURE);
	}
	
	protected function setLastResult($code)
	{
		switch ($code)
		{
			case Memcached::RES_NOTFOUND:
				parent::setLastResult(Sapo_Cache::NOTFOUND);
				break;
			case Memcached::RES_SUCCESS:
				parent::setLastResult(Sapo_Cache::SUCCESS);
				break;
			default:
				parent::setLastResult(Sapo_Cache::FAILURE);
				break; 
		}
	}
}