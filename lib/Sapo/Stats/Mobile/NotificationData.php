<?php

class Sapo_Stats_Mobile_NotificationData
{
	private $target_identifier;
	private $target_description;
	private $extra_info = array();

	const DISCARD_PAGEVIEW = 'FLAG_DiscardPageView';

	public function __construct($identifier = '', $description = '')
	{
		$this->setTargetIdentifier($identifier);
		$this->setTargetDescription($description);
	}

	public function addExtraInfo($info)
	{
		$this->extra_info[] = preg_replace('/(#|\|)/', ' ', $info);
	}

	public function setTargetIdentifier($identifier)
	{
		$this->target_identifier = str_replace('|', ' ', $identifier);
	}

	public function getTargetIdentifier()
	{
		return $this->target_identifier;
	}

	public function setTargetDescription($description)
	{
		$this->target_description = str_replace('|', ' ', $description);
	}

	public function getTargetDescription()
	{
		return $this->target_description;
	}

	public function marshall()
	{
		return '<data>'
			  .'<target_identifier><![CDATA['.$this->target_identifier.']]></target_identifier>'
			  .'<target_description><![CDATA['.$this->target_description.']]></target_description>'
			  .'<extra_info><![CDATA['.implode('#', $this->extra_info).']]></extra_info>'
			  .'</data>';
	}
}
