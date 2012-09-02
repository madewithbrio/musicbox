<?php

class Musicbox_Controller_Auth
extends Sapo_Controller
{
	public function getInputFilterRules() {}

	public function __construct() 
	{
		parent::__construct();
	}

	public function run() 
	{
		$input = Sapo_Context::getInput();
		$this->addCondition(!empty($input->username) && !empty($input->password), 'defineCredentials');
		$this->addCondition(true, 'getAuthStatus');
		parent::run();
	}

	protected function getAuthStatus()
	{
		ob_clean();
		printf ("username: %s",Musicbox_Service_Auth::getUsername());
		ob_flush();
		exit();
	}

	protected function defineCredentials()
	{
		$input = Sapo_Context::getInput();
		Musicbox_Service_Auth::setCredentials($input->username, $input->password);
	}
}