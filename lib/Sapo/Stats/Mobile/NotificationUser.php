<?php
class Sapo_Stats_Mobile_NotificationUser
{
	private $identifier;
	private $sessionId;
	private $ipAddress;
	private $userAgent;
	private $httpReferrer;
	private $trackingCookie;
	private $partner;

	public function __construct()
	{
		$this->setHttpReferrer();
		$this->setIdentifier();
		$this->setIpAddress();
		$this->setPartner();
		$this->setSessionId();
		$this->setTrackingCookie();
		$this->setUserAgent();
	}

	public function setIdentifier($identifier = null)
	{
		return @$_SERVER['SSOMAIL'];
	}

	public function setSessionId($session = null)
	{
		if (is_null($session))
			$cookie = Sapo_Helpers_Cookie::getCookie(Sapo_Context::HOST_SESSION_TRACKING_COOKIE);
			$session = $cookie ? $cookie : session_id();

		$this->globalSessionId = Sapo_Helpers_Cookie::getCookie(Sapo_Context::GLOBAL_SESSION_TRACKING_COOKIE);
		$this->sessionId = $session;
	}

	public function setIpAddress($ipAddress = null)
	{
		if (is_null($ipAddress))
			$ipAddress = Sapo_HTTP_Server::getRemoteAddress();
		$this->ipAddress = $ipAddress;
	}

	public function setUserAgent($userAgent = null)
	{
		if (is_null($userAgent))
			$userAgent = Sapo_HTTP_Server::getUserAgent();
		$this->userAgent = str_replace('|', ' ', $userAgent);
	}

	public function setHttpReferrer($httpReferrer = null)
	{
		if (is_null($httpReferrer))
			$httpReferrer = @$_SERVER['HTTP_REFERER'];
		$this->httpReferrer = str_replace('|', ' ', $httpReferrer);
	}

	public function setTrackingCookie($cookie = null)
	{
		if (is_null($cookie))
			$cookie = Sapo_Helpers_Cookie::getCookie(Sapo_Context::USER_TRACKING_COOKIE);
		$this->trackingCookie = $cookie;
	}

	public function setPartner($partner = null)
	{
		if (is_null($partner))
			$partner =
				(Sapo_Partner_TMN_ClientHandler::isFromI9() ? 'i9' :
				(Sapo_Partner_TMN_ClientHandler::isFromIT() ? 'it' : ''));
		$this->partner = $partner;
	}

	public function __toString()
	{
		return $this->marshall();
	}

	public function marshall()
	{
		return '<user>'
			. '<identifier><![CDATA['.$this->identifier.']]></identifier>'
			. '<session_id><![CDATA['.$this->sessionId.']]></session_id>'
			. '<global_session_id><![CDATA['.$this->globalSessionId.']]></global_session_id>'
			. '<ip_address><![CDATA['.$this->ipAddress.']]></ip_address>'
			. '<user_agent><![CDATA['.$this->userAgent.']]></user_agent>'
			. '<http_referrer><![CDATA['.$this->httpReferrer.']]></http_referrer>'
			. '<tracking_cookie><![CDATA['.$this->trackingCookie.']]></tracking_cookie>'
			. '<partner><![CDATA['.$this->partner.']]></partner>'
			. '</user>';
	}
}
