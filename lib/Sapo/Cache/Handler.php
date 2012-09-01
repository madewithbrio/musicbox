<?php

class Sapo_Cache_Handler
{
	const TTL_NO_EXPIRE = 0; // no expiration
	const TTL_MIN = 30; // 30 second
	const TTL_1M = 60; // 1 minute
	const TTL_5M = 300; // 5 minute
	const TTL_10M = 600; // 10 minute
	const TTL_QUARTER = 900; // 15 minute
	const TTL_HOUR = 3600; // 1 hour
//	const TTL_HALFDAY = 43200; // 12 hour
	const TTL_DAY = 86400;// 1 day
	const TTL_WEEK = 604800; // 1 week = 7 day
	const TTL_MAX = 2419200; // max = 4 week = 28 day (memcached allows 30 day)

	public static function cacheKeyBuilder($class, $method, $args)
	{
		return sprintf("%s_%s_%s_%s", 
			Sapo_Context::getEnvironment(), 
			Sapo_Context::getAppName(),
			$class,
			md5($method . serialize($args))
		);
	}

	public static function remove($class, $method, $args) {
		$cacheKey = self::cacheKeyBuilder($class, $method, $args);
		$cacheEngine = Sapo_Cache::getInstance();
		$cacheEngine->delete($cacheKey);
	}

	public static function get($class, $method, $args, $refreshTTL, $expireTTL, $forceRefresh = false, $allowEmpty = false)
	{
		$className = (is_object($class)) ? get_class($class) : $class;

		$cacheKey = self::cacheKeyBuilder($className, $method, $args);
//		Sapo_Log::getLogger()->debug("Try get cache for: ".$cacheKey);
		$cacheEngine = Sapo_Cache::getInstance();
		$cachedContent = $cacheEngine->get($cacheKey);
		$currentTimestamp = Sapo_HTTP_Server::getCurrentTime();
		$forceRefresh = $forceRefresh || defined('FORCEREFRESH') && FORCEREFRESH;
		if (@$cacheEngine->getLastResult() != Sapo_Cache::SUCCESS || @$cachedContent->expireTimestamp < $currentTimestamp || $forceRefresh)
		{
//			Sapo_Log::getLogger()->debug("Cache not found for: ".$cacheKey);
			try
			{
				$newData = call_user_func_array(array($class, $method), $args);
				if (!$allowEmpty && empty($newData))
				{
					Sapo_Log::error("Failed fetching data from service {$className}::{$method}(" . print_r($args, 1) . ")");
					if ($cachedContent) return $cachedContent->data;
					throw new Exception("Failed fetching data from service");
				}
			}
			catch(Exception $e)
			{ 
				Sapo_Log::exc($e); 
				$newData = null;

				$cacheStatus = 'not cached';
				if ($cacheEngine->getLastResult() == Sapo_Cache::SUCCESS)
				{
					$cacheStatus = 'cached';
					if ($cachedContent->expireTimestamp < $currentTimestamp)
						$cacheStatus = 'outdated cache';
				}
				Sapo_Stats_Kpis::alert('ServiceError', $cacheStatus, $className, $method, serialize($args));
				Sapo_Log::getLogger()->info("Send notification alert: $cacheStatus -> $className::$method");
			}

			if ($newData)
			{
				if ($newData instanceOf Sapo_Cache_Data) {
					$content = $newData;
				} else {
					$content = new Sapo_Cache_Data($newData, $currentTimestamp + $refreshTTL);
				}
				$cacheEngine->set($cacheKey, $content, $expireTTL);
				return $content->data;
			}
		}

		if (!$cachedContent) return null;
		return $cachedContent->data;
	}

}

//private
class Sapo_Cache_Data
{
	public $data;
	public $expireTimestamp;

	public function __construct($data, $expireTimestamp)
	{
		$this->data = $data;
		$this->expireTimestamp = $expireTimestamp;
	}
}
