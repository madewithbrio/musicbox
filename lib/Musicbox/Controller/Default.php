<?php
class Musicbox_Controller_Default
extends Sapo_Controller
{
	public function getInputFilterRules() {}

	public function __construct() 
	{
		parent::__construct();
	}

	public function run() 
	{
		$this->addCondition(true, 'renderDefault');
		parent::run();
	}

	protected function renderDefault()
	{
		header("location: index.html");
		exit;
	}
}