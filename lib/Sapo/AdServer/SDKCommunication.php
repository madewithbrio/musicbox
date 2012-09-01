<?php

require_once 'Sapo/AdServer/SDKConfiguration.php';

class SDKCommunication {
	public $error;
	public $code;
	public $OAID;
	public $size;
    public $click;
    public $image;
    public $impression;
    
	function __construct() {
	}
		
	function __destruct() {
	}
/**
 * This function calls openDisplay and gets a banner
 *
 * @param 	$zonename	The name of the zone were we are going to post the banner
 * 			$useragent	The UserAgent of the device
 * 			$format		The format of the response (iphoneplist, minijson an simpleadxml)
 * 			$size		The maximum size of the banner formated as follow :   withxheight
 * 			$cookie		The cookie that identifies the user.
 *
 * @return true/false		if it executed correctly / an error has ocurred
 * Object also has:
 * 				error		response string with why it fail
 * 				code		the response with the banner and formated as requested
 * 				OAID		cookie returned
 * 				size		size of the banner returned				
 *				click		click URL of the banner returned		
 *				image		image URL of the banner returned
 *				impression	impression URL of the banner returned ( 3rd party or OpenDisplay if 3rd party dosen't exists)
 */
	function call_OpenDisplay($zonename, $useragent, $format, $size, $cookie, $ip, $referer, $cpid, $imp, $keywords = null, $lat = null, $lon = null)
	{		
		$defaults = array(
	        CURLOPT_HEADER => 1,
	        CURLOPT_RETURNTRANSFER => 1,
	        CURLOPT_FORBID_REUSE => 1,
	        //CURLOPT_TIMEOUT_MS => TIMEOUT);
	        CURLOPT_TIMEOUT => TIMEOUT/1000);
	    //ZONA    
		if (!empty($zonename)){
			$zonas=array_flip ($GLOBALS['zones']);
			$zona=$zonas[$zonename];
			if (empty($zonename)){
				$this->error='Invalid Zone Name, check the configuration file';
				return false;
			}
		}else{
			$zona=108;
		}
		//USERAGENT
		if (empty($useragent)){
			$defaults[CURLOPT_USERAGENT]= USER_AGENT;
		}else{
			$defaults[CURLOPT_USERAGENT]= $useragent;
		}
		//SIZE
		if (empty($size)){
			$defaults[CURLOPT_URL] = URL.'?zoneid='.$zona."&format=".$format;
		}else{
			$defaults[CURLOPT_URL] = URL.'?zoneid='.$zona."&format=".$format."&size=".$size;
		}
		//COOKIE
		if (!empty($cookie)){
			if (strpbrk($cookie,'=')){
				$this->error='Its only the value of the cookie, do not include the name';
				return false;
			}else{
				$defaults[CURLOPT_COOKIE]= 'OAID='.$cookie;
			}
		}
		//IP
		if (!empty($ip)){    
	    	$defaults[CURLOPT_HTTPHEADER]=array('X-Forwarded-For: '.$ip);  
	    }
	    //REFERER
		if (!empty($referer)){   
	    	$defaults[CURLOPT_REFERER]= $referer;
		}
		//Content Provider ID
		if (!empty($cpid)){ 
			$defaults[CURLOPT_URL] .="&cpid=".$cpid;
		}
		//Impression
		if (!empty($imp)){ 
			$defaults[CURLOPT_URL] .="&imp=1";
		}
			//Impression
		if (!empty($keywords)){ 
			$defaults[CURLOPT_URL] .="&kw=".$keywords;
		}
		//Impression
		if (!empty($lat)){ 
			$defaults[CURLOPT_URL] .="&lat=".$lat;
		}
		//Impression
		if (!empty($lon)){ 
			$defaults[CURLOPT_URL] .="&lon=".$lon;
		}
	    $ch = curl_init();
	    curl_setopt_array($ch, $defaults);
	    if( ! $result = curl_exec($ch)){
	        $this->error='Erro na chamada OpenDisplay:'.curl_error($ch);
	    }else{ 
	    	$result = explode("\n", $result);
	    	$this->code=array_pop($result);
	    	$i=array_pop($result);
	    	while (!is_null($i)){
	    		$aux=strpos($i,'OAID=');
		    	if (!empty($i) && $aux){
		    		$this->OAID=substr($i, ($aux+5), 32); // +5 from 'OAID='
		    		break;
		    	}
		       	$i=array_pop($result);    
	    	}
	    }
	    switch ($format){
        	case "iphoneplist":
        		//iphoneplist parsed has simpleadxml....hack
        		$aux=simplexml_load_string($this->code);
        		$this->size=(string) $aux->dict->string[0];
        		$this->click=(string) $aux->dict->string[2];
        		$this->image=(string) $aux->dict->string[3];
        		$this->impression=(string) $aux->dict->string[4];
        		return true;
        	case "simpleadxml":
        		$aux=simplexml_load_string($this->code);
        		$this->size=(string) @$aux->AdName;
        		$this->click=(string) @$aux->ClickURL;
        		$this->image=(string) @$aux->ImageURL;
        		$this->impression=(string) @$aux->ViewURL; 
        		$this->extimpression=(string) @$aux->ExtImpression;
        		return true;
        	case "minijson":
        	default:
        		$aux=json_decode($this->code);
        		$this->size=(string) $aux->ad->size;
        		$this->click=(string) $aux->ad->targetURL;
        		$this->image=(string) $aux->ad->imageURL;
        		$this->impression=(string) $aux->ad->viewURL; 
        		return true;				     		
        }
        $this->error='Unknown error';
		return false;	    	
	}	
	
}

