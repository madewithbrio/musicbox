<?php

class Sapo_Stats_Event
{
	public $type = "metric";
	public $timestamp;
	public $domain;
	public $version = 1;

	public $string_attributes = array();	//"string_attributes": {"service": "GIS", "operation": "Search"},
	public $list_attributes; //"list_attributes": {"tags": ["tag1", "tag2"]},
	public $distinct_attributes = array();	//"distinct_attributes": {"client_id": "79054025255fb1a26e4bc422aef54eb4", "ip": "80.142.25.24"}

	public $metrics = array();

/*	"metrics": [
		{ "type": "gauge", "name": "response_time", "cycle": 0, "value": 250.5 },
		{ "type": "absolute", "name": "requests", "cycle": 0, "value": 2 },
		{ "type": "counter", "name": "total_users", "cycle": 0, "value": 14301 }
	]
*/

	// don't go in json
	protected $app;
	protected $section;
	protected $page;

	protected $eventName;
	protected $eventOrigin;

	protected $service;
	protected $operation;
	protected $majorParams;

	public function __construct()
	{
		$this->domain = Sapo_Stats_Kpis::DOMAIN; //Sapo_HTTP_Server::getCanonicalServerName(); 
		$this->timestamp = '%timestamp%'; //Sapo_HTTP_Server::getCurrentTime() . '000';

		if (defined('KPIS_VERSION')) $this->version = KPIS_VERSION;

		$globalSession = Sapo_Helpers_Cookie::getCookie(Sapo_Context::GLOBAL_SESSION_TRACKING_COOKIE);
		if (!$globalSession) $globalSession = session_id();

		$hostSession = Sapo_Helpers_Cookie::getCookie(Sapo_Context::HOST_SESSION_TRACKING_COOKIE);
		if (!$hostSession) $hostSession = session_id();

//		$this->distinct_attributes['ip'] = Sapo_HTTP_Server::getRemoteAddress();
		$this->distinct_attributes['swauv'] = $globalSession;
		$this->distinct_attributes['swahv'] = $hostSession;
		$this->distinct_attributes['visitor_id'] = Sapo_Helpers_Cookie::getCookie(Sapo_Context::USER_TRACKING_COOKIE);

		$platform = Sapo_Context::getPlatform();

		$this->string_attributes['app_url'] = Sapo_HTTP_Server::getCanonicalServerName();
		$this->string_attributes['app'] = Sapo_Context::getAppTitle();
		$this->string_attributes['webapp_platform'] = Sapo_Context::getPlatform();
		$this->string_attributes['webapp_version'] = $platform == 'touch' ? (Sapo_Context::isLightDevice() ? 'light' : 'full') : 'pointer';
		$this->string_attributes['mobile_platform'] = self::getOS();

		$resultsPage = preg_replace('/[^\d]+/', '', Sapo_Context::getInput()->pagina);
		if (!empty($resultsPage)) $this->string_attributes['pagina_resultados'] = $resultsPage;

		$refererHost = Sapo_HTTP_Referer::getRefererHost();
		$requestHost = Sapo_HTTP_Server::getRequestHost();
		if ($refererHost != $requestHost)
		{
			$this->string_attributes['referer_host'] = $refererHost;

			$internalRefererHost = self::getInternalHostName($refererHost);
			if ($internalRefererHost)
			{
				$internalCurrentHost = self::getInternalHostName($requestHost);
				if (!$internalCurrentHost) $internalCurrentHost = 'unknown';

				$this->string_attributes['referer_mobile_host'] = $internalRefererHost;
				$this->string_attributes['site_change'] = $internalRefererHost . ' to ' . $internalCurrentHost;
			}
			else
			{
				$this->string_attributes['external_referer_host'] = $refererHost;
			}
		}

		// TODO: user name
		//$this->string_attributes['user_agent'] = Sapo_HTTP_Server::getUserAgent();
		$this->list_attributes = new StdClass();

		$this->setApp(APP_TITLE);
	}

	public function getInternalHostName($ref)
	{
		// use to get all canonical hosts:
		// > cat */bootstrap.php.prd | grep APP_CANONICAL_HOST | awk -F\" '{print $2}'
		$mobileHosts = array(
			'm.agenda.sapo.pt' => 'agenda',
//			'bancadejornaisatlantico.sapo.ao'
			'm.cinema.sapo.pt' => 'cinema',
			'm.desporto.sapo.pt' => 'desporto',
//			'm.agencia.ecclesia.sapo.pt'
//			'euro2012.m.sapo.pt'
			'm.fama.sapo.pt' => 'fama',
			'm.fotos.sapo.pt' => 'fotos',
			'homepage.m.sapo.pt' => 'HP',
			'm.sapo.pt' => 'HP',
			'm.mapas.sapo.pt' => 'mapas',
			'm.mulher.sapo.pt' => 'mulher',
			'm.noticias.sapo.pt' => 'noticias',
//			'm.ptbluestore.pt'
			'm.sabores.sapo.pt' => 'sabores',
			'm.sol.sapo.pt' => 'sol',
			'm.tempo.sapo.pt' => 'tempo',
			'm.videos.sapo.pt' => 'videos',
		);

		if (array_key_exists($ref, $mobileHosts)) return $mobileHosts[$ref];
		return null;
	}

	public function getOS()
	{
		$ua = Sapo_HTTP_Server::getUserAgent();
		if (false !== strpos($ua, 'Android'))
			if (false !== strpos($ua, 'Mobile')) return 'Android Mobile';
			else return 'Android Tablet';

		if (preg_match('/(iPhone|iPad|iPod|Kindle|Windows Phone OS|webOS|Symbian|Blackberry)/i', $ua, $osMatch)) return $osMatch[1];
		if (preg_match('/Tablet/i', $ua, $osMatch)) return 'Other Tablet';
		if (preg_match('/Mobile/i', $ua, $osMatch)) return 'Other Mobile';

		// desktop browsers
		if (preg_match('/Chrome|Chromium/i', $ua, $osMatch)) return 'Chrome';
		if (preg_match('/Firefox/i', $ua, $osMatch)) return 'Firefox';
		if (preg_match('/Opera/', $ua, $osMatch)) return 'Opera';
		if (preg_match('/MSIE/', $ua, $osMatch)) return 'Internet Explorer';
		if (preg_match('/Safari/', $ua, $osMatch)) return 'Safari';

		return 'Other';
	}

	public function setStringAttribute($name, $value)
	{
		$this->string_attributes[$name] = $value;
	}

	public function addMetric($metric)
	{
		array_push($this->metrics, $metric);
	}

	public function __destruct()
	{
		Sapo_Stats_Kpis::publish($this);
	}

	public function getSection() { return $this->section; }
	public function getPage() { return $this->page; }
	public function getApp() { return $this->app; }

	public function setApp($app) { $this->app = $app; }
	public function setSection($section) 
	{
		$this->section = $section; 
		$this->string_attributes['section'] = $section;
	}
	public function setPage($page) 
	{
		$this->page = $page;
		$this->string_attributes['subsection'] = $page;
	}
}



class Sapo_Stats_Event_PageView extends Sapo_Stats_Event
{
	protected $objectId;
	protected $objectDescription;
	protected $pageErrorType;

	public function getObjectId() { return $this->objectId; }
	public function getObjectDescription() { return $this->objectDescription; }
	public function getPageErrorType() { return $this->pageErrorType; }

	public function setObjectId($objectId) 
	{
		$this->objectId = $objectId; 
		$this->string_attributes['object_id'] = $objectId;
	}
	public function setObjectDescription($description) 
	{
		$this->objectDescription = $description;
		$this->string_attributes['object_description'] = $description;
	}
	public function setPageErrorType($pageErrorType) 
	{
		$this->pageErrorType = $pageErrorType; 
		$this->string_attributes['page_error_type'] = $pageErrorType;
	}
}

class Sapo_Stats_Event_Action extends Sapo_Stats_Event
{
	protected $actionName;
	protected $actionOrigin;

	public function getActionName() { return $this->actionName; }
	public function getActionOrigin() { return $this->actionOrigin; }

	public function setActionName($actionName) 
	{
		$this->actionName = $actionName; 
		$this->string_attributes['action_name'] = $actionName;
	}
	public function setActionOrigin($actionOrigin) 
	{
		$this->actionOrigin = $actionOrigin; 
		$this->string_attributes['action_origin'] = $actionOrigin;
	}
}

class Sapo_Stats_Event_Error extends Sapo_Stats_Event
{
	protected $service;
	protected $operation;
	protected $mainParams; // params as string list. use ';' as separator

	public function getService() { return $this->service; }
	public function getOperation() { return $this->operation; }
	public function getParams() { return $this->mainParams; }

	public function setService($service) 
	{
		$this->service = $service; 
		$this->string_attributes['error_service'] = $service;
	}
	public function setOperation($operation) 
	{ 
		$this->operation = $operation;
		$this->string_attributes['error_operation'] = $operation;
	}
	public function setParams($params)
	{ 
		try {
			if (!is_string($params)) $params = serialize($params);
			$this->mainParams = $params;
		} catch (Exception $e) { 
		}
	}
}

