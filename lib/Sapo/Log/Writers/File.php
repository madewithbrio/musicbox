<?php
class Sapo_Log_Writers_File
extends Sapo_Log_Writers_Abstract
implements Sapo_Log_Writers
{
	protected $_file;
	protected $_level;
	
	public function __construct($options = array())
	{
		foreach ($options as $_key => $_value)
		{
			switch ($_key)
			{
				case 'file':
					$this->_file = $_value;
					break;
				case 'level':
					$this->_level = $_value;
					break;
				default:
					break;
			}
		}
		
		if (NULL === $this->_file)
		{
			$this->_file = _TMP_LOG;
		}
	}
	
	public function set($level, $msg, $exception = null)
	{
		if ((NULL !== $this->_file) && $this->isValidLevel($level))
		{	
			if (!$this->isMessageToDiscard($level))
			{
				$fh = fopen($this->getFile(), "a+");
				fwrite($fh,date("F j, Y, g:i a").": [".$level."] ". $msg . "\r\n");
				
				if ((NULL !== $exception) && ($exception instanceof Exception)) {
					fwrite($fh,date("F j, Y, g:i a").": [".$level."] ". $exception->getTraceAsString() . "\r\n");
				} elseif (is_string($exception)) {
					fwrite($fh,date("F j, Y, g:i a").": [".$level."] ". $exception . "\r\n");
				}
				fclose($fh);
			}
		}
	}
	
	protected  function getFile()
	{
		return $this->_file;
	}
	
	protected function getLevel()
	{
		return $this->_level;
	}
}