<?php

class Sapo_Xml_ObjectMapper
{
	public static function stringListFromXmlElementList($xmlElementList, $listNodeName)
	{
		$elementList = array();
		if (null == $xmlElementList || null == $xmlElementList->$listNodeName) return;

		foreach ($xmlElementList->$listNodeName as $xmlElement)
			array_push($elementList, (string) $xmlElement);

		return $elementList;
	}

	public static function buildFromXmlElementList($xmlElementList, $listNodeName, $elementClassName, $params = null)
	{
		$elementList = array();
		if (null == $xmlElementList || null == $xmlElementList->$listNodeName) return;
		foreach ($xmlElementList->$listNodeName as $xmlElement) 
		{
			$element = new $elementClassName($params);
			$element->fillFromXmlData($xmlElement);
			array_push($elementList, $element);
		}
		return $elementList;
	}

	public static function booleanFromBooleanString($xmlElement) // do not enforce SimpleXMLElement
	{
		$str = (string) $xmlElement;
		switch($str) {
			case 'true': return true;
			case 'false': return false;
		}
		return null;
	}

	public static function timestampFromDateTimeString($xmlElement)  // do not enforce SimpleXMLElement
	{
		$dateStr = (string) $xmlElement;
		$timeArray = strptime($dateStr, '%F %T');
		$time = mktime($timeArray['tm_hour'], $timeArray['tm_min'], $timeArray['tm_sec'],
			$timeArray['tm_mon'] + 1, $timeArray['tm_mday'], $timeArray['tm_year']-100);

		return $time; 
	}
}

interface Sapo_Xml_ObjectMapper_Interface
{
	function fillFromXmlData($xmlElement); // SimpleXMLElement
}

abstract class Sapo_Xml_ObjectMapper_Entity implements Sapo_Xml_ObjectMapper_Interface
{
	public static function createFromXmlData($xmlElement) // SimpleXMLElement
	{
		$obj = new static();
		$obj->fillFromXMLData($xmlElement);
		return $obj;
	}

	public function __get($prop) {
		return $this->$prop;
	}

	public function __call($name, $args) {
		if (preg_match('/^get(\w+)$/', $name, $match)) {
		# getter
			$prop = lcfirst($match[1]);
			return $this->$prop;
		} else {
		# throw exception
			throw new Exception("method not defined");
		}
	}
}

