<?php

abstract class Sapo_Controller
{
	private $conditionsTable = array();
	private static $templateEngine = null;

	/**
	 *
	 * Gui Controller factory
	 * @param Sapo_CommonGuiControllor $controller
	 */
	public static function factory($guiClass)
	{
		/** assumed autoload class running **/
		$reflectionClass = new ReflectionClass($guiClass);
		if (!$reflectionClass->isSubclassOf("Sapo_Controller")) throw new Exception("class ".$guiClass." is not Sapo_Controller", -1);
		if (!$reflectionClass->IsInstantiable()) throw new Exception("class ".$guiClass." is not instantiable", -1);
		return new $guiClass();
	}

	protected function __construct() {
		Sapo_Context::getInput()->setFilterRules($this->getInputFilterRules()); # assign input filter rules
	}

	public function addCondition($condition, $callMethod)
	{
		if(key_exists($callMethod, $this->conditionsTable))
		{
			throw new Exception('Duplicate condition exception.', -1);
		}

		if (!method_exists($this, $callMethod))
		{
			throw new Exception('CallMethod not defined.', -1);
		}

		$this->conditionsTable[(string)$callMethod] = $condition;
	}

	public function run()
	{
		if(count($this->conditionsTable) < 1)
		{
			throw new Exception('No conditions to evaluate exception.', -1);
		}

		Sapo_Context::getInput()->setFilterRules($this->getInputFilterRules());
		foreach($this->conditionsTable as $method => $condition)
		{
			if($condition)
			{
				return call_user_func(array($this, $method));
			}
		}

		throw new Exception("No condition evaluated to true Exception", -1);
	}

	protected abstract function getInputFilterRules();

	protected function getInput()
	{
		return Sapo_Context::getInput();
	}

	public static function forward($link)
	{
		header("HTTP/1.0 301 Moved Permanently", null, 301);
		header("location: ".$link);
		exit;
	}
}

