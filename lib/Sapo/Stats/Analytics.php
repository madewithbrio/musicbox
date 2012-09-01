<?php

class Sapo_Stats_Analytics
{
	// removed params: 
	//   bsr=1680x1050&bsc=24-bit&bul=pt-pt&bje=1&bfl=&swasite=312239
	//   swasubsection_grp=Saude%20312239
	//   r=0.447690111970814
	//const BASE_URI = "http://wa.sl.pt/wa.gif?s={hostname}&etype={event_type}&pg={page_view_url}&ref={referer}&swav={global_session_id}&swauv={visitor_id}&swahv={host_session_id}&isnv={is_new_global_session}&isnhv={is_new_host_session}&isnuv={is_new_visitor}&bcs=UTF-8&swasection={section}&swasubsection={sub_section}&swasection_grp={app_name}&swacontent={content}&swachannel={channel}&dc={app_title}&swakt=D76EA79A-8865-4161-BD02-A58E66FF3C19";
	  const BASE_URI = "http://wa.sl.pt/wa.gif?s={hostname}&etype={event_type}&pg={page_view_url}&ref={referer}&swauv={global_session_id}&swahuv={visitor_id}&swahv={host_session_id}&bcs=UTF-8&bsr=320x480&bsc=24-bit&bul=pt-pt&bje=1&bfl=11.2%20r202&swasite=&swasection={section}&swasubsection={sub_section}&swasectiongrp={app_name}&swasubsectiongrp=&swacontent={content}&swachannel={channel}&dc={app_title}&swakt=D76EA79A-8865-4161-BD02-A58E66FF3C19&swagoal=(none)&r=0.8875476527027786&v=2.4"; // what is r? &r=0.8875476527027786

	protected static $isNewVisitor;
	protected static $isNewHostSession;
	protected static $isNewGlobalSession;
	protected static $visitorId;
	protected static $hostSessionId;
	protected static $globalSessionId;


	public static function init($isNewVisitor, $isNewHostSession, $isNewGlobalSession, $visitorId, $hostSessionId, $globalSessionId)
	{
		self::$isNewVisitor = $isNewVisitor;
		self::$isNewHostSession = $isNewHostSession;
		self::$isNewGlobalSession = $isNewGlobalSession;
		self::$visitorId = $visitorId;
		self::$hostSessionId = $hostSessionId;
		self::$globalSessionId = $globalSessionId;
	}

	public static function publish($event)
	{
		$objectId = $objectDesc = null;

		switch(get_class($event))
		{
			case 'Sapo_Stats_Event_PageView':
				$objectId = $event->getObjectId();
				$objectDesc = $event->getObjectDescription();
				break;

			case 'Sapo_Stats_Event_Action':
return; // TODO?
				$objectId = $event->getActionOrigin() . '_' . $event->getActionName();
				$objectDesc = $event->getActionName() . ' em ' . $event->getActionOrigin();
				break;

			case 'Sapo_Stats_Event_Error':
return; // TODO?
				$objectId = $event->getService() . '_' . $event->getOperation();
				$objectDesc = 'erro em ' . $event->getService() . '::' . $event->getOperation();
				break;
		}

		$time = microtime(true);
		$seconds = floor($time);
		$miliseconds = floor(($time - $seconds) * 1000);
		$timestamp = sprintf("%d%d", $seconds, $miliseconds);

		$isReferral = false;
		$referer = Sapo_HTTP_Referer::getRefererUri();

		if ($referer) $isReferral = !preg_match('/^http:\/\/(m\.[.a-z0-9-]+|[.a-z0-9-]+.m|m)\.sapo\.pt\//i', $referer);
		$ereferer = urlencode($referer);

//preg_match('/^http:\/\/(m\.[.a-z0-9-]+|[.a-z0-9-]+.m|m)\.sapo\.pt\//i', $referer, $matches);
//Sapo_Log::error("referrer: $referer - $isReferral - " . print_r($matches,1));

		$url = str_replace(array(
				'{hostname}', '{event_type}', 
				'{page_view_url}', '{referer}', 
				'{global_session_id}', 
				'{visitor_id}', 
				'{host_session_id}', 
//				'{is_new_global_session}', '{is_new_host_session}', '{is_new_visitor}', 
				'{section}', '{sub_section}', '{app_name}', 
				'{content}', '{channel}', '{app_title}'
			), array(
				Sapo_HTTP_Server::getCanonicalServerName(), str_replace('_', '-', $event->metrics[0]->name),
				urlencode(Sapo_HTTP_Server::getAbsoluteRequestUrl()), $ereferer,

//				Sapo_Helpers_String::toIntegerHash(self::$globalSessionId),
//				Sapo_Helpers_String::toIntegerHash(self::$visitorId),
//				Sapo_Helpers_String::toIntegerHash(self::$hostSessionId),
				self::$globalSessionId, self::$visitorId,

				self::$hostSessionId . '|' . $timestamp . '|' . 
					(self::$isNewHostSession ? 'new' : 'returning') . '|' .
					($isReferral ? 'referral' : 'direct') . '|(none)|' . '(none)', //. $ereferer,  // TODO rever martelanços

//				self::$isNewGlobalSession ? 1 : 0, self::$isNewHostSession ? 1 : 0, self::$isNewVisitor ? 1 : 0, 
				urlencode($event->getSection()), urlencode($event->getPage()), urlencode($event->getApp()), 
				urlencode($objectDesc), urlencode($objectId), urlencode(APP_TITLE)
			), self::BASE_URI
		);

		$userAgent = Sapo_HTTP_Server::getUserAgent();
		$remoteAddress = Sapo_HTTP_Server::getRemoteAddress();
		$opts = array(
			'http'=>array(
				'method' => "GET",
				'header' => 
					"User-Agent: $userAgent\r\n" . 
					"X-Forwarded-For: $remoteAddress\r\n" . 
					"Accept-Charset: utf-8;\r\n",
				'timeout' => 3,
			)
		);

Sapo_Log::error(sprintf("Sapo_Stats_Analytics::publish\n\nheaders:\n%s\nURL: %s\n", $opts['http']['header'], $url));

		$context = stream_context_create($opts);
		$response = @file_get_contents($url, null, $context);
	}
}
