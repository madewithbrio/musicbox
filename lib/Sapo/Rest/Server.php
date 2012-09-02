<?php

class Sapo_Rest_Server {
	
	private $_class;
	private $_classInstance;
	
	public function setClass($class)
	{
		try {
			if (!class_exists($class, true))
			{
				throw new Exception ("Class not Found");
			}
			
			$reflection = new ReflectionClass($class);
			if (!$reflection->isSubclassOf('Sapo_Rest_Server_Interface'))
			{
				throw new Exception("Not implement Sapo Services Rest Server");
			}
			
			if (!$reflection->isInstantiable())
			{
				throw new Exception("Not instantiable");
			}
			
			$this->_class = $class;
			$this->_classInstance = $reflection->newInstance();
		}
		catch (Exception $ex)
		{
			/** process ex **/
			$this->renderException($ex);
		}
	}
	
	public function getClass()
	{
		return $this->_class;
	}
	
	public function getClassInstance()
	{
		return $this->_classInstance;
	}
	
	
	public function handle() {
		try {
			
			if (empty(Sapo_Context::getInput()->method))
			{
				Sapo_Context::getInput()->method = "Status";
			}
			
			/** dont allow method that start with __ (this is framework internal methods) **/
			if (preg_match('/^__/', Sapo_Context::getInput()->method))
			{
				throw new Exception ("operation ".Sapo_Context::getInput()->method." not found");
			}
			
			$reflectionClass = new ReflectionClass($this->getClass());
			if (!$reflectionClass->hasMethod(Sapo_Context::getInput()->method))
			{
				throw new Exception ("operation ".Sapo_Context::getInput()->method." not found");
			}
			
			$reflectionMethod = new ReflectionMethod($this->getClass(), Sapo_Context::getInput()->method);
			if (!$reflectionMethod->isPublic() || $reflectionMethod->isAbstract())
			{
				throw new Exception("operation ".Sapo_Context::getInput()->method." not found");
			}
			
			$params = $reflectionMethod->getParameters();
			
			$args = array();
			foreach ($params as $_param)
			{
				if (!$_param->isOptional())
				{
					if (is_empty(Sapo_Context::getInput()->{$_param->getName()}))
					{
						throw new Exception ( "need ".$_param->getName()." parameter" );
					}
					$args[$_param->getPosition()] = Sapo_Context::getInput()->{$_param->getName()};
				} elseif (isset(Sapo_Context::getInput()->{$_param->getName()})) {
					$args[$_param->getPosition()] = Sapo_Context::getInput()->{$_param->getName()};
				} else {
					$args[$_param->getPosition()] = $_param->getDefaultValue();
				}
			}
			$result = $reflectionMethod->invokeArgs($this->getClassInstance(), $args);
			ob_clean();
			print json_encode($result);
			ob_flush();
		}
		catch (Exception $ex) 
		{
			$this->renderException($ex);
		}	
	}

	public function renderException(Exception $ex)
	{
        if(Sapo_Context::isDevelEnvironment() || ini_get('display_errors') == 1)
        {             
        	$returnEx = new stdClass();
        	$returnEx->message = $ex->getMessage();

    		ob_clean();
            header("Status: 500 Internal Server Error");
            print json_encode($returnEx);
            ob_flush();
            exit(0);
        }              
        
        ob_end_clean();                 
        exit(0);
	}
}

interface Sapo_Rest_Server_Interface
{
	public function Status();
}