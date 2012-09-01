<?php

/**
 * 
 * Enter description here ...
 * @author Bruno Carreira
 * @category Sapo
 * @package Sapo_Config
 * @uses Zend_Config_Ini
 * @uses Zend_Config_Xml
 *
 */
class Sapo_Config
{
	/**
	 * Save config instances
	 * @var array
	 * @todo cache this instance in memory
	 */
	private static $_instance = array();
	
	public static function loadStoredConfig($serializedConfig = null)
	{
		if ($serializedConfig !== null)
			self::$_instance = unserialize($serializedConfig);
	}
	
	public static function store()
	{
		return serialize(self::$_instance);	
	}
	
	private static function getFilePath($resource, $ext)
	{
		$file_resource = _PROJECT_CONFIG."/config".$resource.".".$ext;
		if (file_exists($file_resource) && is_readable($file_resource)) return $file_resource;
//		$file_resource = _PROJECT_LIB_SHARED."/config/config".$resource.".".$ext;
//		if (file_exists($file_resource) && is_readable($file_resource)) return $file_resource;
		throw new Exception("config file [$file_resource] not found or without read permitions");
	}

	private static function factory($resource, $type)
	{
		$resource = ("default" === $resource) ? "" : "_" . $resource ;
		$options = array();
		switch ($type)
		{
			case Sapo_Config_Types::INI:
				$options['sectionSeparator'] = ':';
				$options['nestSeparator'] = '.';
				$file_resource = self::getFilePath($resource, Sapo_Config_Types::INI);	
				return new Zend_Config_Ini($file_resource, ENV, $options);
				break;
			
			case Sapo_Config_Types::XML:
				$file_resource = self::getFilePath($resource, Sapo_Config_Types::XML);
				return new Zend_Config_Xml($file_resource, ENV, $options);
				break;
				
			default:
				throw new Exception("not valid config type [$type]");
				break;
		}
		
	}
	
	public static function getInstance($resource = "default", $type = Sapo_Config_Types::INI)
	{
		if (!defined('_PROJECT')) throw new Exception("not defined PROJECT constant"); 
		if (!defined('ENV')) throw new Exception("not defined ENV constant");
		if (!is_string($resource)) throw new Exception("resource need be a string");
		
		$resource_location = _PROJECT . $resource;
		if (!isset(self::$_instance[$resource_location]))
			self::$_instance[$resource_location] = self::factory($resource, $type);
		return self::$_instance[$resource_location];
	}
	
	private function __clone()
	{
		/** dont allow clone this class **/
	}
}

/**
 * 
 * Enumerator for config types allow
 * @author Bruno Carreira
 * @category Sapo
 * @package Sapo_config
 *
 */
class Sapo_Config_Types
{ 
	const INI = "ini";
	const XML = "xml";
}