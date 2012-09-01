<?php
class Sapo_Log_Writers_Null
extends Sapo_Log_Writers_Abstract
implements Sapo_Log_Writers
{

	public function set($level, $msg, $exception = null)
	{
		throw new Exception("not defined method");
	}

	public function getLevel()
	{
		return "WARN";
	}
}