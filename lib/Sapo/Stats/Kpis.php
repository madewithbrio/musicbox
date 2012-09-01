<?php

class Sapo_Stats_Kpis
{
	const DOMAIN = 'm.sapo.pt';
	const TOPIC_BASE = '/sapo/event-agg-kpi/';

	private static $pageview = null;
	private static $actions = array();
	private static $errors = array();

	public static function setBaseParams(&$event, $section, $page, $extraInfo = null)
	{
		$event->setSection($section);
		$event->setPage($page);

		if ($extraInfo) 
		{
			if (!is_array($extraInfo)) $extraInfo = array('unnamedExtraInfo' => $extraInfo);
			foreach($extraInfo as $name => $value)
				$event->setStringAttribute($name, $value);
		}
	}

	public static function pageView($section, $page, $objectId = '', $objectDesc = '', $extraInfo = null)
	{
		$event = new Sapo_Stats_Event_PageView();
		self::setBaseParams($event, $section, $page, $extraInfo);

		if ($objectId) $event->setObjectId($objectId);
		if ($objectDesc) $event->setObjectDescription($objectDesc);

		$event->addMetric(new Sapo_Stats_Metric_PageView());

		self::$pageview = $event;
	}

	public static function pageError($error = 'page') 
	{
		if (!self::$pageview) return;
//		self::$pageview->setStringAttribute('error', $error);
		self::$pageview->setPageErrorType($error);
	}

	public static function action($section, $page, $action, $origin, $extraInfo = null)
	{
		$event = new Sapo_Stats_Event_Action();
		self::setBaseParams($event, $section, $page, $extraInfo);

		$event->setActionName($action);
		$event->setActionOrigin($origin);

		$event->addMetric(new Sapo_Stats_Metric_Action());
		array_push(self::$actions, $event);
	}

// TODO
	// hpId: HP / Self
	// targetType: List, ItemDetail
	// move: Next, Previous
	public static function moreAction($section, $page, $action, $origin, $extraInfo = null)
	{
/*
		$event = new Sapo_Stats_Event_Action();
		self::setBaseParams($event, $section, $page, $extraInfo);

		$event->setActionName($action);
		$event->setActionOrigin($origin);

		$event->addMetric(new Sapo_Stats_Metric_Action());
		array_push(self::$actions, $event);
*/
	}

	public static function filterAction($section, $page, $action, $origin, $extraInfo = null)
	{
	}

	public static function tabAction($section, $page, $action, $origin, $extraInfo = null)
	{
	}

	public static function photoswipeAction($section, $page, $action, $origin, $extraInfo = null)
	{
	}


	public static function hpAction($hpId, $page, $action, $origin, $extraInfo = null)
	{
	}

//END TODO


	public static function alert($section, $page, $service, $operation, $params = null, $extraInfo = null)
	{
		$event = new Sapo_Stats_Event_Error();
		self::setBaseParams($event, $section, $page, $extraInfo);

		$event->setService($service);
		$event->setOperation($operation);

		if ($params) $event->setParams($params);

		$event->addMetric(new Sapo_Stats_Metric_Error());
		array_push(self::$errors, $event);
	}

	public static function publishToKPICentral($event)
	{
		$brokerProducer = new Sapo_Broker();

		$publishArgs['priority'] = 1;
		$publishArgs['message_id'] = md5(uniqid(rand(), true));
		$publishArgs['persistent'] = 1;
		$publishArgs['topic'] =  self::TOPIC_BASE . self::DOMAIN;

		$jsonEvent = str_replace('"%timestamp%"', Sapo_HTTP_Server::getCurrentTime() . '000', json_encode($event));
		if (ENV == 'local') Sapo_Log::error($jsonEvent);
		if (!$brokerProducer->publish($jsonEvent, $publishArgs))
		{
			Sapo_Log::error('Broker publishing error.');
			Sapo_Log::error($brokerProducer->net->lat_err);
		}
	}

	public static function publish($event)
	{
		if (self::isUserAgentBot()) return; // Log:: identified Bot : $UA
		self::publishToKPICentral($event);
		Sapo_Stats_Mobile_Publisher::publish($event);
		Sapo_Stats_Analytics::publish($event); // also publish to analytics
	}

	public static function isUserAgentBot()
	{
		$ua = Sapo_HTTP_Server::getUserAgent();
		$botStrings = array_unique(explode("\n", 
'Googlebot
spider
Slurp
/robot
crawler
crawling
/bot
HttpClient
Apache
SAPO::Thumbs
SapoMobile
Google Desktop
WebDAV-MiniRedir
metauri.com
/mlbot
ichiro/mobile
flamingosearch.com/bot
search.msn.com/msnbot.htm
news.me
facebookexternalhit
bitlybot
UnwindFetchor
Twitterbot/1.0
libwww-perl
PycURL
Python-urllib
postrank.com
butterfly
google.com/appengine
FirePHP
lab.ntt.co.jp
discobot
bingbot
TweetmemeBot
TweetedTimes Bot
ProCogBot
PaperLiBot
Openindex
Scripting
MJ12bot
ezooms.bot
flipboardproxy'));

		$regexps = array(
			'/^Wget\//i',
			'/^curl\//i',
			'/^Java\/[\d._]+$/i',
			'/^PEAR/i',
		);

		for ($i = 0, $s = count($botStrings); $i < $s; $i++)
			if (false !== stripos($ua, $botStrings[$i])) return true;

		for ($i = 0, $s = count($regexps); $i < $s; $i++)
			if (preg_match($regexps[$i], $ua)) return true;

		return false;
	}
}
