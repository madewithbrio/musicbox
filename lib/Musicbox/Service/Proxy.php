<?php

class Musicbox_Service_Proxy
implements Sapo_Rest_Server_Interface 
{
	private $soapClients 		= array();
	private static $instance;

	public function __construct(){}
	
	private static function getInstance()
	{
		if(null === self::$instance)
			self::$instance = new self();
	
		return self::$instance;
	}
	
	protected static function getSOAPClient($className)
	{
		$className = 'Musicbox_Service_SOAPClients_' . $className;

		if(!isset(self::getInstance()->soapClients[$className]))
			self::getInstance()->soapClients[$className] = new $className();
			
		return self::getInstance()->soapClients[$className];
	}

	private static function getMBCredentials() {
		$credentials = Musicbox_Service_Auth::getCredentials();
		if ($credentials->isEmpty()) throw new Exception("Credentials not set yet", 1);
		return $credentials;
	}

	public function GetNewAlbums()
	{
		$request = new stdClass;
		$request->MBCredentials = self::getMBCredentials();
		$response = self::getSOAPClient('PublicApi')->GetNewAlbums($request);

		// @todo process type of exception
		if (empty($response) || !isset($response->GetNewAlbumsResult)) {
			throw new Exception("Error Processing Request", 1);
		}
		return $response->GetNewAlbumsResult;
	}

	public function GetAlbumById($AlbumId)
	{
		$request = new stdClass;
		$request->MBCredentials = self::getMBCredentials();
		$request->AlbumId = $AlbumId;
		$response = self::getSOAPClient('PublicApi')->GetAlbumById	($request);

		// @todo process type of exception
		if (empty($response) || !isset($response->GetAlbumByIdResult)) {
			throw new Exception("Error Processing Request", 1);
		}
		return $response->GetAlbumByIdResult;
	}

	public function GetTrackById($TrackId)
	{
		$request = new stdClass;
		$request->MBCredentials = self::getMBCredentials();
		$request->TrackId = $TrackId;
		$response = self::getSOAPClient('PublicApi')->GetTrackById($request);

		// @todo process type of exception
		if (empty($response) || !isset($response->GetTrackByIdResult)) {
			throw new Exception("Error Processing Request", 1);
		}
		return $response->GetTrackByIdResult;
	}

	public function Status()
	{
		print "all ok";
	}
}
