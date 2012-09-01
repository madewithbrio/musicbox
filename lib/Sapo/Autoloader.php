<?php
class Sapo_Autoloader
{
	protected static $_instance;
	private $_autoLoaders = array();
	private $_defaultLoaderClazz = 'Sapo_Autoloader_Default';
	private $_defaultAutoLoaderInstance;
	
	public static function isReadable($file) 
	{
		if (is_readable($file)) return true;
		if ((preg_match('/^win/i', PHP_OS)) && preg_match('/^[a-z]:/i', $file)) return false; # OS Windows and absolute path
		foreach (self::explodeIncludePath() as $path) {
			$_f = sprintf("%s%s%s", $path, DIRECTORY_SEPARATOR, $file);
			if (is_readable($_f)) return true;
		}
		return false;
	}
	
	public static function explodeIncludePath($path = null)
	{
		if (null === $path) $path = get_include_path();
		return preg_split(sprintf('#%s(?!//)#', PATH_SEPARATOR), $path);
	}
	
	public static function autoload($class)
	{
		$autoLoaderInstance = self::getInstance()->_getAutoLoaderForClass($class);
		$autoLoaderInstance->autoload($class);
	}
	
	public static function registerAutoLoaderForNamespace(Sapo_Autoloader_Interface $autoLoaderInstance, $namespace)
	{
		self::getInstance()->_registerAutoLoader($autoloaderInstance, $namespace);
	}
	
	public static function unRegisterAutoLoaderForNamespace($namespace)
	{
		self::getInstance()->_unregisterAutoLoader($namespace);
	}
	
	public static function setDefaultAutoLoader(Sapo_Autoloader_Interface $class)
	{
		self::getInstance()->_setDefaultAutoLoaderInstance($class);
	}
	
	public static function init()
	{
		self::getInstance();
	}
	
	protected static function getInstance()
	{
		if (null === self::$_instance) self::$_instance = new Sapo_Autoloader();
		return self::$_instance;
	}
	
	private function __clone() {}
	
	# dont let instance it out of this scope
	private function __construct()
	{
		spl_autoload_register(array(__CLASS__, 'autoload'));
	}
	
	public function _registerAutoLoader($autoloaderInstance, $namespace)
	{
		$this->_autoLoaders[$namespace] = $autoloaderInstance;
	}
	
	public function _getAutoLoaders()
	{
		return $this->_autoLoaders;
	}
	
	public function _unregisterAutoLoader($namespace)
	{
		unset($this->_autoLoaders[$namespace]);
	}
	
	public function _setDefaultAutoLoaderInstance($instance)
	{
		$this->_defaultAutoLoaderInstance = $instance;
	}
	
	public function _getDefaultAutoLoader()
	{
		if (null === $this->_defaultAutoLoaderInstance) {
			$ref = new ReflectionClass($this->_defaultLoaderClazz);
			if (!$ref->implementsInterface('Sapo_Autoloader_Interface')) throw new Exception ("Default AutoLoader dont implement interface");
			if (!$ref->isInstantiable()) throw new Exception ("AutoLoader can't be instantiable");
			$this->_defaultAutoLoaderInstance = $ref->newInstance();
		}
		return $this->_defaultAutoLoaderInstance;
	}
	
	public function _getAutoLoaderForClass($class)
	{
		$namespaces = array_keys($this->_autoLoaders);
		foreach ($namespaces as $namespace) {
			if (preg_match('/^'+$namespace+'/', $class)) return $this->_autoLoaders[$namespace];
		}
		return $this->_getDefaultAutoLoader();
	}
}

interface Sapo_Autoloader_Interface 
{
	public function getClassPath($class);
	public function autoload($class);
}

class Sapo_Autoloader_Default implements  Sapo_Autoloader_Interface
{
	public function autoload($originalClassName, $partialClassName = null)
	{
		if (!strncmp(strtolower($originalClassName), 'smarty_', 7)) return false; // use smarty autoloader 
		if ($partialClassName && class_exists($partialClassName, false)) throw new Exception("Sapo_Autoloader_Default: class not found: $originalClassName and class already loaded $partialClassName");
		$class = $partialClassName ? $partialClassName : $originalClassName;
		if (strpos($class, "_") === false) throw new Exception("Sapo_Autoloader_Default: class not found: $originalClassName");
		$classPath = $this->getClassPath($class);
		if (false !== $classPath) return require_once $classPath;

		//if (ENV == 'local') Sapo_Log::error("autoload($originalClassName, $partialClassName)");
		return $this->autoload($originalClassName, preg_replace('/_([^_]+)$/', '', $class));
	}

    public function getClassPath($class)
    {
        $segments          = explode('_', $class);
        if (count($segments) < 2) return false;

        $final     	= array_pop($segments);
        $path 		= implode('/', $segments);
        $classFile = $path . '/' . $final . '.php';

        if (Sapo_Autoloader::isReadable($classFile)) {
            return $classFile;
        }

        return false;
    }
	
}
