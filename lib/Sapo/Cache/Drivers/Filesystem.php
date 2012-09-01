<?php
class Sapo_Cache_Drivers_Filesystem
extends Sapo_Cache_Drivers_Abstract 
implements Sapo_Cache_Drivers
{
	private $_options = array();
	private $_instance = null;
	protected $_level;
	protected $_dir;
	private $_indexKeys = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
								'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l',
								'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'w', 'Y', 'x', 'z'
								); 
								
	public function __construct(array $options = array())
	{
        $this->_level = (isset($options['level']) && is_integer($options['level'])) ? $options['level'] : 2;
        if (!file_exists(_TMP."cache/0"))
        {
                if (!file_exists(_TMP."cache") && !mkdir(_TMP."cache", 0777))
                {
                        throw new Exception("can't create cache dir: " ._TMP."cache");
                }
                $this->_buildStruct(_TMP."cache/", 1);
        }
        $this->_dir = _TMP."cache";
	}
	
	private function _buildStruct($start, $level)
	{
		foreach ($this->_indexKeys as $tmpKey)
		{
			$dir = $start . $tmpKey;
			mkdir($dir);
			if ($level < $this->_level)
			{
				$this->_buildStruct($dir."/".$tmpKey, $level + 1);
			}
		}
	}
	
	private function _getPath($storedKey)
	{
		$storedKey = strtolower($storedKey);
		$tmpDir = $this->_dir."/";
		for($i = 1; $i < $this->_level + 1; $i++)
		{
			$letters = substr($storedKey, 0, $i);
			$tmpDir .= $letters . "/";
		}
		//Sapo_Log::getLogger()->debug("[Cache::Filesystem] Access to file: ".$tmpDir.$storedKey);
		return $tmpDir.$storedKey;
	}
	
	public function get($key)
	{
		$filename = $this->_getPath($key);
		$data = @file_get_contents($filename, LOCK_EX);
		if ($data === false)
		{
			$this->setLastResult(Sapo_Cache::NOTFOUND);
			return null;
		}
		$obj = unserialize($data);
		if ($obj->TTL != 0)
		{ /** check if cache still valid **/
			if ($obj->CREATE_TIMESTAMP + $obj->TTL < time())
			{
				$this->setLastResult(Sapo_Cache::NOTFOUND);
				@unlink($filename);
				return null;
			}
		}
		return $obj->DATA;
	}
	
	public function delete($key)
	{
		$filename = $this->_getPath($key);
		@unlink($filename);
		parent::setLastResult(Sapo_Cache::FAILURE);
	}
	
	public function set($key, $value, $ttl = 0)
	{
		$obj = new Sapo_Cache_StdClass($key, $value, $ttl);
		$filename = $this->_getPath($key);
		$return = @file_put_contents($filename, serialize($obj), LOCK_EX);
		($return === false) ? parent::setLastResult(Sapo_Cache::FAILURE) : parent::setLastResult(Sapo_Cache::SUCCESS);
	}
	
}
