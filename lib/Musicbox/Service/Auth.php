<?php
final class Musicbox_Service_Auth
{
	private static $instance;
	private $credential;
	const KEY = 'musicbox_credentials';

	private function __construct(){
		$this->loadSavedCredential();
		//register_shutdown_function(array($this, '__destruct')); // not need with call magic __destruct
	}
	private function __clone() {}

	private static function getInstance()
	{
		if (null === self::$instance) {
			self::$instance = new self;
		}
		return self::$instance;
	}
	
	public function __destruct()
	{
		// sessions can be saved at third server's like memcache so store encrypted
		if (null !== $this->credential) {
			$_SESSION[self::KEY] = Sapo_Helpers_Encoder::encode($this->credential);
		}
	}

	private function loadSavedCredential()
	{
		if (isset($_SESSION[self::KEY]) && !empty($_SESSION[self::KEY])) {
			try {
				$this->credential = Sapo_Helpers_Encoder::decode($_SESSION[self::KEY]);
			} catch (Exception $e) {
				Sapo_Log::error("fail load credential from storage");
			}
		}
		if (null === $this->credential) {
			$this->credential = new Musicbox_Service_Auth_Credentials;
		}
	}

	private function setCredential($credential)
	{
		$this->credential = $credential;
	}

	private function &getCredential()
	{
		return $this->credential;
	}

	public static function setCredentials($username, $password)
	{
		$credential = self::getInstance()->getCredential();
		$credential->setUsername($username);
		$credential->setPassword($password);
	}

	public static function getCredentials()
	{
		return clone self::getInstance()->getCredential();
	}

	public static function setUsername($username)
	{
		self::getInstance()->getCredential()->setUsername($username);
	}

	public static function setPassword($password)
	{
		self::getInstance()->getCredential()->getPassword($password);
	}

	public static function getUsername()
	{
		return self::getInstance()->getCredential()->getUsername();
	}

	public static function getPassword()
	{
		return self::getInstance()->getCredential()->getPassword();
	}
}

final class Musicbox_Service_Auth_Credentials
{
	public $MBUsername;
	public $MBPassword;

	public function __construct($username = null, $password = null) {
		$this->MBUsername = $username;
		$this->MBPassword = $password;
	}

	public function getUsername() { return $this->MBUsername; }
	public function getPassword() { return $this->MBPassword; }
	public function setUsername($username) { $this->MBUsername = $username; }
	public function setPassword($password) { $this->MBPassword = $password; }
	public function isEmpty() { return empty($this->MBUsername) || empty($this->MBPassword); }
}