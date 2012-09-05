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

	private function doRequest($method, $requestParameters)
	{
		$response = self::getSOAPClient('PublicApi')->$method($request);
		$returnProp = $method . "Result";

		// @todo process type of exception
		if (empty($response) || !isset($response->{$returnProp})) {
			throw new Exception("Error Processing Request", 1);
		}
		return $response->{$returnProp};
	}

	public function AddAlbumToFavorites($AlbumId)
	{
		$request = new stdClass;
		$request->MBCredentials = self::getMBCredentials();
		$request->AlbumId = $AlbumId;
		return $this->doRequest('AddAlbumToFavorites', $request);
	}

	public function AddAlbumTracksToPlaylist($PlaylistId, $AlbumId)
	{
		$request = new stdClass;
		$request->MBCredentials = self::getMBCredentials();
		$request->PlaylistId = $PlaylistId;
		$request->AlbumId = $AlbumId;
		return $this->doRequest('AddAlbumTracksToPlaylist', $request);
	}

	public function AddArtistToFavorites($ArtistId)
	{
		$request = new stdClass;
		$request->MBCredentials = self::getMBCredentials();
		$request->ArtistId = $ArtistId;
		return $this->doRequest('AddArtistToFavorites', $request);
	}

	public function AddPlaylist($PlaylistTitle)
	{
		$request = new stdClass;
		$request->MBCredentials = self::getMBCredentials();
		$request->PlaylistTitle = $PlaylistTitle;
		return $this->doRequest('AddPlaylist', $request);
	}

	public function AddPlaylistToFavorites($PlaylistId)
	{
		$request = new stdClass;
		$request->MBCredentials = self::getMBCredentials();
		$request->PlaylistId = $PlaylistId;
		return $this->doRequest('AddPlaylistToFavorites', $request);
	}

	public function AddTracksToPlaylist($PlaylistId, array $TrackIdList)
	{
		if (sizeof($TrackIdList) < 1) throw new Exception("need at less 1 track id");

		$request = new stdClass;
		$request->MBCredentials = self::getMBCredentials();
		$request->PlaylistId = $PlaylistId;
		$request->TrackIdList = array();
		for($j = sizeof($TrackIdList); $j >= 0; --$j) {
			$track = new stdClass;
			$track->TrackId = $TrackIdList[$j];
			$request->TrackIdList[] = $track;
		}
		return $this->doRequest('AddTracksToPlaylist', $request);
	}
	
	public function AddTrackToFavorites($TrackId)
	{
		$request = new stdClass;
		$request->MBCredentials = self::getMBCredentials();
		$request->TrackId = $TrackId;
		return $this->doRequest('AddTrackToFavorites', $request);
	}

	public function GetNewAlbums()
	{
		$request = new stdClass;
		$request->MBCredentials = self::getMBCredentials();
		return $this->doRequest('GetNewAlbums', $request);
	}

	public function GetAlbumsByArtistId($ArtistId)
	{
		$request = new stdClass;
		$request->MBCredentials = self::getMBCredentials();
		$request->ArtistId = $ArtistId;
		$response = self::getSOAPClient('PublicApi')->GetAlbumsByArtistId($request);

		// @todo process type of exception
		if (empty($response) || !isset($response->GetAlbumsByArtistIdResult)) {
			throw new Exception("Error Processing Request", 1);
		}
		return $response->GetAlbumsByArtistIdResult;
	}

	public function GetAlbumsByCategoryId($CategoryId)
	{
		$request = new stdClass;
		$request->MBCredentials = self::getMBCredentials();
		$request->CategoryId = $CategoryId;
		$response = self::getSOAPClient('PublicApi')->GetAlbumsByCategoryId($request);

		// @todo process type of exception
		if (empty($response) || !isset($response->GetAlbumsByCategoryIdResult)) {
			throw new Exception("Error Processing Request", 1);
		}
		return $response->GetAlbumsByCategoryIdResult;
	}

	public function GetAlbumsByCollectionId($CollectionId)
	{
		$request = new stdClass;
		$request->MBCredentials = self::getMBCredentials();
		$request->CollectionId = $CollectionId;
		$response = self::getSOAPClient('PublicApi')->GetAlbumsByCollectionId($request);

		// @todo process type of exception
		if (empty($response) || !isset($response->GetAlbumsByCollectionIdResult)) {
			throw new Exception("Error Processing Request", 1);
		}
		return $response->GetAlbumsByCollectionIdResult;		
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

	public function GetEditorialPlaylists()
	{
		$request = new stdClass;
		$request->MBCredentials = self::getMBCredentials();
		$response = self::getSOAPClient('PublicApi')->GetEditorialPlaylists	($request);

		// @todo process type of exception
		if (empty($response) || !isset($response->GetEditorialPlaylistsResult)) {
			throw new Exception("Error Processing Request", 1);
		}
		return $response->GetEditorialPlaylistsResult;
	}

	public function GetFavoriteAlbums()
	{
		$request = new stdClass;
		$request->MBCredentials = self::getMBCredentials();
		$response = self::getSOAPClient('PublicApi')->GetFavoriteAlbums	($request);

		// @todo process type of exception
		if (empty($response) || !isset($response->GetFavoriteAlbumsResult)) {
			throw new Exception("Error Processing Request", 1);
		}
		return $response->GetFavoriteAlbumsResult;
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
