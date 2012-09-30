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
		$response = self::getSOAPClient('PublicApi')->$method($requestParameters);
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

	public function GetRecommendedAlbums()
	{
		$request = new stdClass;
		$request->MBCredentials = self::getMBCredentials();
		return $this->doRequest('GetRecommendedAlbums', $request);
	}
	
	public function GetAlbumsByArtistId($ArtistId)
	{
		$request = new stdClass;
		$request->MBCredentials = self::getMBCredentials();
		$request->ArtistId = $ArtistId;
		return $this->doRequest('GetAlbumsByArtistId', $request);
	}

	public function GetAlbumsByCategoryId($CategoryId)
	{
		$request = new stdClass;
		$request->MBCredentials = self::getMBCredentials();
		$request->CategoryId = $CategoryId;
		return $this->doRequest('GetAlbumsByCategoryId', $request);
	}

	public function GetAlbumsByCollectionId($CollectionId)
	{
		$request = new stdClass;
		$request->MBCredentials = self::getMBCredentials();
		$request->CollectionId = $CollectionId;
		return $this->doRequest('GetAlbumsByCollectionId', $request);		
	}

	public function GetAlbumById($AlbumId)
	{
		$request = new stdClass;
		$request->MBCredentials = self::getMBCredentials();
		$request->AlbumId = $AlbumId;
		return $this->doRequest('GetAlbumById', $request);
	}

	public function GetEditorialPlaylists()
	{
		$request = new stdClass;
		$request->MBCredentials = self::getMBCredentials();
		return $this->doRequest('GetEditorialPlaylists', $request);
	}

	public function GetFavoriteAlbums()
	{
		$request = new stdClass;
		$request->MBCredentials = self::getMBCredentials();
		return $this->doRequest('GetFavoriteAlbums', $request);
	}

	public function GetTrackById($TrackId)
	{
		$request = new stdClass;
		$request->MBCredentials = self::getMBCredentials();
		$request->TrackId = $TrackId;
		return $this->doRequest('GetTrackById', $request);
	}

	public function GetTracksByAlbumId($AlbumId)
	{
		$request = new stdClass;
		$request->MBCredentials = self::getMBCredentials();
		$request->AlbumId = $AlbumId;
		return $this->doRequest('GetTracksByAlbumId', $request);
	}

	public function Status()
	{
		print "all ok";
	}
}
