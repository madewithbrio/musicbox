<?php

abstract class Musicbox_Service_SOAPClients_AbstractBaseClient extends SoapClient 
{
	protected $header 	= array();
	protected $classmap = array();
	protected $actor;
	protected $wsdl;
	protected $ESBCredentials;

	public function __construct()
	{
		$classMapCommon = Sapo_SDB_Definitions::getClassMap();
		$this->classmap = array_merge($classMapCommon, $this->classmap);
	
		parent::__construct($this->wsdl, array('classmap' => $this->classmap, 'trace' => true, 'cache_wsdl' => WSDL_CACHE_NONE, 'features' => SOAP_SINGLE_ELEMENT_ARRAYS));
	}
	
	public function __call($method, $parameters)
	{
		$requestHeader = array();

		if($this->ESBCredentials != null)
			$requestHeader[] = new SoapHeader('http://services.sapo.pt/definitions', 'ESBCredentials', $this->ESBCredentials);

		try
		{
			Sapo_Log::getLogger()->debug(get_called_class() . '::' . $method);
			$response = $this->__soapCall($method, $parameters, null, $requestHeader);
			Sapo_Log::getLogger()->debug($this->__getLastRequest());
			Sapo_Log::getLogger()->debug('RESPONSE:');
			Sapo_Log::getLogger()->debug($this->__getLastResponse());
			Sapo_Log::getLogger()->debug("\n\n");

			return $response;
		}
		catch(SoapFault $e)
		{
			// TODO: remove this
			Sapo_Log::getLogger()->debug('SOAP FAULT:' . $e->getMessage());
			Sapo_Log::getLogger()->debug($this->__getLastRequest());
			Sapo_Log::getLogger()->debug('RESPONSE:');
			Sapo_Log::getLogger()->debug($this->__getLastResponse());
			Sapo_Log::getLogger()->debug("\n\n");
			
			$text = (!$e->getMessage()) ? $e->faultstring : $e->getMessage();
			throw new SoapFault('SoapFault', $text, $this->actor);
		}
	}

    public function addTokenESBCredentials()
    {
    	$this->ESBCredentials = new Sapo_SDB_Definitions_ESBCredentials_t;
    	$this->ESBCredentials->ESBToken = Sapo_Auth_Token::getServiceAuthToken('musicbox');
    }
}
