<?php

class Musicbox_Controller_Content
extends Sapo_Controller
{
	public function getInputFilterRules() {}

	public function __construct() 
	{
		parent::__construct();
	}

	public function run() 
	{
		$this->addCondition(true, 'handleService');
		parent::run();
	}

	protected function handleService()
	{
		ob_start();
		$server = new Sapo_Rest_Server();
		$server->setClass('Musicbox_Service_Proxy');
		$server->handle();
		ob_flush();
	}
}