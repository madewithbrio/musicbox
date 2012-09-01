<?php
class Sapo_Cache_Drivers_Session extends Sapo_Cache_Drivers_Abstract implements Sapo_Cache_Drivers
{
	private $_options = array();
	private $_instance = null;

	public function __construct(array $options = array()){}

	public function get($key)
	{
		Sapo_Log::getLogger()->debug("get cache: ".$key);
		$data = @$_SESSION['cacheStorage'][$key];
		$this->setLastResult((!isset($_SESSION['cacheStorage'][$key])) ? Sapo_Cache::NOTFOUND : Sapo_Cache::SUCCESS);
		return ($this->getLastResult() == Sapo_Cache::NOTFOUND) ? null : $data;
	}

	public function set($key, $value, $ttl = 0)
	{
		# ttl not used it's session cache expire end session finish
		//Sapo_Log::getLogger()->debug("set cache: ".$key);
		if (!isset($_SESSION['cacheStorage'])) $_SESSION['cacheStorage'] = array();
		$_SESSION['cacheStorage'][$key] = $value;

		parent::setLastResult(Sapo_Cache::SUCCESS);
	}

	public function delete($key)
	{
		//Sapo_Log::getLogger()->info("delete cache: ".$key);
		if (!isset($_SESSION['cacheStorage'])) $_SESSION['cacheStorage'] = array();
		if(isset($_SESSION['cacheStorage'][$key])) {
			unset($_SESSION['cacheStorage'][$key]);
			parent::setLastResult(Sapo_Cache::SUCCESS);
		} else {
			parent::setLastResult(Sapo_Cache::NOTFOUND);
		}
	}
}