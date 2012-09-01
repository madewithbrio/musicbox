<?php

class Sapo_Helpers_Netscope {
	public static function getTrackerObj($content = "") {
		$json = Sapo_Config::getInstance()->netscope;
		if (empty($json)) return null;
		return str_replace('%content%', $content, $json);
	}

	public static function assingTracker($content = null) {
		if (null === $content) $content = Sapo_HTTP_Server::requestPath();
		$obj = self::getTrackerObj($content);
		if ($obj === null) return;
		Sapo_Controller::getEngineInstance()->assign('netscopeTracker', $obj);
	}
}