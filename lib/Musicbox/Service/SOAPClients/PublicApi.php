<?php

final class Musicbox_Service_SOAPClients_PublicApi extends Musicbox_Service_SOAPClients_AbstractBaseClient {
	
	public function __construct()
	{
		$this->actor = "https://services.bk.sapo.pt/Music/OnDemand/PublicApi";
		$this->wsdl = _PROJECT_LIB . '/Musicbox/Service/Contracts/Music_OnDemand_PublicApi.wsdl'; //@todo

		parent::__construct();
	}
}