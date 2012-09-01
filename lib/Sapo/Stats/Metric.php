<?php

class Sapo_Stats_Metric_PageView
{
	public $type = "absolute";
	public $name = "page_view";
	public $cycle = 0;
	public $value = 1;
}

class Sapo_Stats_Metric_Action
{
	public $type = "absolute";
	public $name = "action";
	public $cycle = 0;
	public $value = 1;

	//public function __construct($name) { $this->name = $name; }
}

class Sapo_Stats_Metric_Error
{
	public $type = "absolute";
	public $name = "error";
	public $cycle = 0;
	public $value = 1;
}
