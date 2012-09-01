<?php
abstract class Sapo_Log_Writers_Abstract
implements Sapo_Log_Writers
{
	protected static $levelsAllowed = array (
		'DEBUG'		=> 1,
		'INFO'		=> 2,
		'WARN'		=> 4,
		'ERROR'		=> 8,
		'ERR'		=> 8,
		'FATAL'		=> 16,
		'CRIT'		=> 32,
	);

	public function set($level, $msg, $exception = null)
	{
		throw new Exception("not defined method");
	}

	abstract protected function getLevel();

	public static function isValidLevel($level)
	{
		return key_exists($level, self::$levelsAllowed);
	}

	public function isMessageToDiscard($level)
	{
		$_levelCode = self::$levelsAllowed[$level];
		$_minLevelCode = self::$levelsAllowed[$this->getLevel()];
		return !($_levelCode >= $_minLevelCode);
	}

	public function __call($function, $arg)
	{
		try 
		{
			if (isset($arg[0]))
				$this->set(strtoupper($function), $arg[0], @$arg[1]);
		}
		catch (Exception $ex)
		{
			/** if fail save in disk **/
			if (!$this->isMessageToDiscard(strtoupper($function)))
			{
				$fh = fopen(_TMP_LOG, "a+");
				fwrite($fh,date("F j, Y, g:i a").": [".strtoupper($function)."] ".implode(" # ", $arg) . "\r\n");
				fclose($fh);
			}
		}
	}
}
