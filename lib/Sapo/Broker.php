<?php
/**
 * SAPO Broker access abstraction class.
 *
 * The Broker class abstracts all the low-level complexity of the
 * SAPO Broker and gives the developers a simple to use high-level API
 * to build consumers and producers.
 *
 * @package Broker
 * @version 0.6.1
 * @author Celso Martinho <celso@co.sapo.pt>
 * @author Bruno Pedro <bpedro@co.sapo.pt>
 * @author Pedro Eugenio <peugenio@co.sapo.pt>
 * @author Pedro Fonseca <pedro.fonseca@co.sapo.pt>
 */
 
/* 
  [CHANGELOG]
  
  0.6.2
    . FIXED (matamouros 2010.06.15): PHP 4 incompatibility regression, invoking
      Sapo_Broker_Tools::xmlentities() on Sapo_Broker::xmlentities(). See the
      later for more details.
  
  0.6.1
    . FIXED (matamouros 2010.05.31): Weird design flaw in the constructor, probably
      not serious in more relaxed PHP default settings. Check the comment within it.
    
    . FIXED (matamouros 2010.05.28): More PHP 5.3 compatibility fixes to avoid
      Strict and Notice level warnings.
  
  0.6
    . ADDED (matamouros 2010.05.20): Option for bypassing the default local
      dropbox when publishing stuff: array('use_dropbox' => false).
     
    . FIXED (matamouros 2010.05.20): PHP 5.3 compatibility fixes to avoid Strict
      and Notice level warnings (most of them, at least).
    
    . CHANGED (matamouros 2010.05.20): Major code whitespace cleanup.
    
  0.5
    . ADDED (matamouros 2010.04.??): Methods for polling and separate ACK
      sending. Added a few helper internal methods.
*/

class Sapo_Broker
{    
  var $parser; // xml parser
  var $net; // sockets handler
  var $debug; // global debug
  var $args; // args defaults

  function Sapo_Broker ($args=array())
  {
    // args defaults 
    $this->args=array_merge(array('port'        => 3322,
                                  'debug'       => FALSE,
                                  'timeout'     => 60*5, // in seconds
                                  'force_expat' => FALSE,
                                  'locale'      => 'pt_PT',
                                  'force_dom'   => FALSE,
                                  'use_dropbox' => true) // Use dropbox if localhost and whenever possible
                                  , $args);

    setlocale(LC_TIME, $this->args['locale']);
    $this->debug = $this->args['debug'];

    // instanciate network handler
    //
    // matamouros 2010.05.28: This used to be inside the initNetwork() call, but
    // by a weird design option it got attributed to something inside initParser(),
    // before it had the chance to exist, on some very specific scenario (we've
    // seen it happening when running our Task_ImageGenerator workers). It
    // probably only is a problem on some very remote scenarios, but it also
    // probably gets triggered more on the more restrictive Notice and Strict
    // reality of PHP 5.3 setups, hence the fix.
    //
    $this->net = new Sapo_Broker_Net($this->debug);
    
    // checks for minimum requirements
    $this->checkRequirements();
              
    // Look for DOM support and use appropriate Parser.
    $this->initParser();

    // post init()
    $this->initNetwork();
  }

  function initNetwork()
  { 
    // inerith timer from parent class
    $this->net->timer=$this->timer;

    // setting default timeouts - usefull for low traffic topics (higher these to avoid disconnects)
    $this->net->rcv_to=$this->args['timeout']*1000000;
    $this->net->snd_to=$this->args['timeout']*1000000;

    if (!isset($this->args['server']) || !$this->args['server']) {
      $this->dodebug("No server defined. Doing auto-discovery.");
      if (getenv('SAPO_BROKER_SERVER')) {
        $this->dodebug("Trying to use SAPO_BROKER_SERVER");
        if ($this->net->tryConnect(getenv('SAPO_BROKER_SERVER'),$this->args['port'])) {
          $this->args['server']=getenv('SAPO_BROKER_SERVER');
        } else {
          $this->dodebug("Couldn't connect to SAPO_BROKER_SERVER: ".getenv('SAPO_BROKER_SERVER'));
        }
      }
      if ((!isset($this->args['server']) || !$this->args['server']) && $this->net->tryConnect('127.0.0.1',$this->args['port'])) {
        $this->dodebug("Using 127.0.0.1. Local agent seems present.");
        $this->args['server']='127.0.0.1';
      }
      if ((!isset($this->args['server']) || !$this->args['server']) && @file_exists('/etc/world_map.xml')) {
        $this->dodebug("Picking random agent from /etc/world_map.xml.");
        $i=0;
        while($i<3) {
          $server=$this->parser->worldmapPush('/etc/world_map.xml');
          $this->dodebug("Picked ".$server." (".($i+1)."). Testing connection.");
          if ($this->net->tryConnect($server,$this->args['port'])) {
            $this->args['server']=$server;
            break;
          }
          $i++;
        }
        if ($this->args['server']) {
          $this->dodebug("Will use ".$this->args['server']);
        }
      }
      if (!@$this->args['server']) {
        $this->dodebug("Usign last resort round-robin DNS broker.bk.sapo.pt");
        $this->args['server']='broker.bk.sapo.pt';
      }
    }
    $this->dodebug("Initializing network.");
    $this->net->init($this->args['server'], $this->args['port']);
    $this->net->latest_status_message='';
    $this->net->latest_status_timestamp_received=0;
    $this->net->latest_status_timestamp_sent=0;
    $this->add_callback(array("sec"=>5),array('Sapo_Broker_Net','sendKeepalive'));
  }

  function initParser()
  {
    if ((!extension_loaded('dom') || $this->args['force_expat']) && $this->args['force_dom']==FALSE) {
      $this->dodebug("Using Sapo_Broker_Parser() aka expat");
      $this->parser = new Sapo_Broker_Parser($this->debug,$this->net);
    } else {
      $this->dodebug("Using Sapo_Broker_Parser_DOM() aka native DOM support");
      $this->parser = new Sapo_Broker_Parser_DOM($this->debug,$this->net);
    }
  }

  function checkRequirements()
  {
    // Check for Multibyte functions
    if (extension_loaded('mbstring')==FALSE) {
      die ("Sapo_Broker requires Multibyte String Functions support.\nPlease upgrade your php installation...\nSee http://pt2.php.net/manual/en/mbstring.installation.php\n\n");
    }
    // Check for supported PHP version.
    if (version_compare(phpversion(), '4.3.0', '<')) {
      die ("Sapo_Broker needs at least PHP 4.3.0 to run properly.\nPlease upgrade...\n\n");
    }
    if (version_compare(phpversion(), '5.0.0', '>')) {
      $this->dodebug("Using PHP5 timers");
      $this->timer = new Sapo_Broker_Tools_Timer_PHP5;
    } else {
      $this->dodebug("Using PHP4 timers");
      $this->timer = new Sapo_Broker_Tools_Timer_PHP4;
    }
  }

  function dodebug($msg)
  {
    if($this->debug) {
      echo $msg."\n";
    }
  }

  /**
   * This is a facade to Sapo_Broker_Tools::xmlentities()
   *
   * @return string
   * @author Bruno Pedro
   **/
  function xmlentities($string, $quote_style = ENT_QUOTES, $charset = 'UTF-8')
  {
    //
    // return Sapo_Broker_Tools::xmlentities($string, $quote_style, $charset);
    //
    // This static call isn't possible in PHP 5 without the static qualifier in
    // the Sapo_Broker_Tools::xmlentities method. Since that is not allowed in
    // PHP 4, the lesser evil should be to create the object and invoke its
    // instance. Beats doing a version check here, or repeating the xmlentities
    // definition.
    //
    $tools = new Sapo_Broker_Tools();
    return $tools->xmlentities($string, $quote_style, $charset);
  }

  /**
   * This is a facade to Sapo_Broker_Net::init()
   *
   * @return void
   * @author Bruno Pedro
   **/
  function init($server = '127.0.0.1', $port = 3322)
  {
    //
    // Initialize network access.
    //
    $this->net->init($server, $port);
  }

  function debug ($debug)
  {
    //
    // Set this object's debug property.
    //
    $this->debug = $debug;
    
    //
    // Propagate through all used objects.
    //
    $this->net->debug = $debug;
    $this->parser->debug = $debug;
  }

  function publish($payload = '', $args = array())
  {
    $args=array_merge(array('destination_type'=>'TOPIC'),$args);

    $msg='<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:mq="http://services.sapo.pt/broker" xmlns:wsa="http://www.w3.org/2005/08/addressing">';
    $msg.='<soap:Body>';

    switch (strtoupper($args['destination_type'])) {
      case 'QUEUE':
        $msg.='<mq:Enqueue>';
        break;
      case 'TOPIC':
      default:
        $msg.='<mq:Publish>';
        break;
    }

    $msg.='<mq:BrokerMessage>';

    if (isset($args['persistent']) && $args['persistent']) {
      $msg .= '<mq:DeliveryMode>PERSISTENT</mq:DeliveryMode>';
    } elseif (isset($args['transient']) && $args['transient']) {
      $msg .= '<mq:DeliveryMode>TRANSIENT</mq:DeliveryMode>';
    }
    if (isset($args['priority']) && $args['priority']) {
      $msg .= '<mq:Priority>'.$args['priority'].'</mq:Priority>';
    }
    if (isset($args['message_id']) && $args['message_id']) {
      $msg .= '<mq:MessageId>'.$args['message_id'].'</mq:MessageId>';
    }
    if (isset($args['correlation_id']) && $args['correlation_id']) {
      $msg .= '<mq:CorrelationId>'.$args['correlation_id'].'</mq:CorrelationId>';
    }
    if (isset($args['timestamp']) && $args['timestamp']) {
      $msg .= '<mq:Timestamp>'.$args['timestamp'].'</mq:Timestamp>';
    }
    if (isset($args['expiration']) && $args['expiration']) {
      $msg .= '<mq:Expiration>'.$args['expiration'].'</mq:Expiration>';
    }

    $msg .= '<mq:DestinationName>'.$args['topic'].'</mq:DestinationName>';
    $msg .= '<mq:TextPayload>'.htmlspecialchars($payload).'</mq:TextPayload>';

    $msg .= '</mq:BrokerMessage>';

    switch (strtoupper($args['destination_type'])) {
      case 'QUEUE':
        $msg.='</mq:Enqueue>';
        break;
      case 'TOPIC':
      default:
        $msg.='</mq:Publish>';
        break;
    }

    $msg .= '</soap:Body>';
    $msg .= '</soap:Envelope>';
    $this->dodebug("Publishing $msg");
    if ($this->net->server=='127.0.0.1' && $this->args['use_dropbox']) {
      $this->dodebug("Using local dropbox");
      umask(0);
      $filename = '/servers/broker/dropbox/' . md5(microtime() . mt_rand() . getmypid());
      $fd = fopen($filename, 'x');
      if ($fd) {
        fwrite($fd, $msg);
        fclose($fd);
        rename($filename, $filename . '.good');
      } else {
        return false;
      }
      return true;
    } else {
      return $this->net->write($msg);
    }
  }

  function subscribe($topic, $args, $callback)
  {
    array_push($this->net->subscriptions,array('topic'=>$topic,'topic_reg'=>"/".str_replace('/','\/',$topic)."/",'args'=>$args,'callback'=>$callback));
  }

  function unsubscribe($topic)
  {
    $unsub_item=false;
    $subscriptions=array();
    foreach ($this->net->subscriptions as $subscription) {
      if ($subscription['topic']!=$topic) {
        array_push($subscriptions,$subscription);
      } else {
        $unsub_item=$subscription;
      }
    }
    $this->net->subscriptions=$subscriptions;
    if ($unsub_item) {  
      $this->dodebug("unsubscribe() unsubscribing ".$subscription['topic']);
      $msg = '<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:wsa="http://www.w3.org/2005/08/addressing" xmlns:mq="http://services.sapo.pt/broker">';
      $msg .= '<soap:Body>';
      $msg .= '<mq:Unsubscribe>';
      $msg .= '<mq:DestinationName>'.$unsub_item['topic'].'</mq:DestinationName>';    
      if ($subscription['args']['destination_type']) {
        $msg.='<mq:DestinationType>'.strtoupper($unsub_item['args']['destination_type']).'</mq:DestinationType>';
      } else {
        $msg.='<mq:DestinationType>TOPIC</mq:DestinationType>';
      }
      $msg .= '</mq:Unsubscribe>';
      $msg .= '</soap:Body>';
      $msg .= '</soap:Envelope>';
      return $this->net->write($msg);
    } else {
      $this->dodebug("unsubscribe() no such topic is subscribed ".$topic);
    }
    return false;
  }

  function add_callback($args,$callback)
  {
    $period_float=0;
    $period=0;
    if(isset($args['sec'])) $period+=$args['sec']*1000000;
    if(isset($args['usec'])) $period+=$args['usec'];

    $this->net->callbacks_count++;
/**
    $this->dodebug("Adding Callback function #".@$this->net->callbacks_count." '".@$callback."' periodicity ".@$period);
**/
    array_push($this->net->callbacks,array('id'=>$this->net->callbacks_count,'period'=>(float)$period/1000000,'name'=>$callback));
    if (($period<$this->net->rcv_to || $period<$this->net->snd_to) && $period>0) {
      $this->net->rcv_to=$period;
      $this->net->snd_to=$period;
      $this->net->timeouts();
    }
    $this->net->callbacks_ts[$this->net->callbacks_count]=$this->timer->utime();
  }

  function consumer()
  {
    do {
      $tmp=$this->net->netread(4);
      $len=(double)(ord($tmp[3])+(ord($tmp[2])<<8)+(ord($tmp[1])<<16)+(ord($tmp[0])<<24)); // unpack("N");
      if ($len==0) {
        $this->dodebug("consumer() WARNING: packet length is 0!");
      } else {
        $this->dodebug("consumer() I'm about to read ".$len." bytes");
        $tmp=$this->net->netread($len);
        $this->dodebug("consumer() got this xml: ".$tmp."");
        list($dtype,$dname,$mid)=$this->parser->handlePackets($tmp, $this->net->subscriptions);
        switch($dtype) {
          case "QUEUE":
          case "TOPIC_AS_QUEUE":
            $this->dodebug("consumer() Got QUEUE message. Acknowledging $dname with id $mid");
            $msg="<soap:Envelope xmlns:soap='http://www.w3.org/2003/05/soap-envelope' xmlns:mq='http://services.sapo.pt/broker'><soap:Body><mq:Acknowledge><mq:DestinationName>".$dname."</mq:DestinationName><mq:MessageId>".$mid."</mq:MessageId></mq:Acknowledge></soap:Body></soap:Envelope>";
            $this->net->write($msg);
            break;
        }
      }
    } while ($this->net->con_retry_count<10);
  }

  /**
   * Builds a poll message type
   * 
   * @param $topicName
   * @return string a message
   */
  function buildPollMessage($topicName)
  {
    return "<soap:Envelope xmlns:soap='http://www.w3.org/2003/05/soap-envelope' xmlns:mq='http://services.sapo.pt/broker'>"
         . "<soap:Body>"
         . "<mq:Poll>"
         . "<mq:DestinationName>{$topicName}</mq:DestinationName>"
         . "</mq:Poll>"
         . "</soap:Body>"
         . "</soap:Envelope>";
  }
  
  /**
   * Sends a poll message
   * 
   * @param string $topicName the topic name
   * @return Array (destinationName,MessageId,Payload)
   */
  function poll($topicName)
  {
    $res = $this->net->write($this->buildPollMessage($topicName));
    if (!empty($res)) {
      $res = $this->net->readPoll();
      if (!empty($res)) {
        return $this->parser->parsePackets($res);
      }
    }
    return array(false, false, false);
  }
  
  /**
   * Sends an ack message
   * 
   * @param string $destName the destination name
   * @param string $msgId a unique message id
   */
  function sendAcknowledge($destName, $msgId)
  {
    $this->dodebug("consumer() Got QUEUE message. Acknowledging $destName with id $msgId");
    $msg = "<soap:Envelope xmlns:soap='http://www.w3.org/2003/05/soap-envelope' xmlns:mq='http://services.sapo.pt/broker'><soap:Body><mq:Acknowledge><mq:DestinationName>" . $destName . "</mq:DestinationName><mq:MessageId>" . $msgId . "</mq:MessageId></mq:Acknowledge></soap:Body></soap:Envelope>";
    $res = $this->net->write($msg);
  }
}



class Sapo_Broker_Net
{
  var $server = '127.0.0.1';
  var $port = 3322;
  var $connected = false;
  var $socket;
  var $sokbuf = '';
  var $sokbuflen = 0;
  var $rcv_to = 0; // time in microseconds to timeout on receiving data
  var $snd_to = 0; // time in microseconds to timeout on sending data
  var $snd_to_sec;
  var $rcv_to_sec;
  var $snd_to_usec;
  var $rcv_to_usec;
  var $con_retry_count = 0;
  var $initted = false;
  var $debug = false;
  var $last_err = "none";
  var $callbacks_ts = array();
  var $callbacks = array();
  var $callbacks_count = 0;
  var $subscriptions = array();

  function Sapo_Broker_Net ($debug = false)
  {
    $this->debug = $debug;
  }
  
  function init($server = '127.0.0.1', $port = 3322)
  {
    $this->server = $server;
    $this->port = $port;
    $this->connected = false;
    $this->timeouts();
    $this->initted = true;
  }
  
  function dodebug($msg)
  {
    if ($this->debug) {
      echo $msg."\n";
    }
  }

  function timeouts()
  {
    list($this->rcv_to_sec, $this->rcv_to_usec, $this->rcv_to_float) = $this->timesplit($this->rcv_to);
    list($this->snd_to_sec, $this->snd_to_usec, $this->snd_to_float) = $this->timesplit($this->snd_to);
    $this->dodebug("Sapo_Broker_Net::Adjusting timmers because of lower periodic Callback. New timers:");
    $this->dodebug("  rcv_to_sec: ".$this->rcv_to_sec."");
    $this->dodebug("  rcv_to_usec: ".$this->rcv_to_usec."");
    $this->dodebug("  rcv_to_float: ".$this->rcv_to_float."");
    $this->dodebug("  snd_to_sec: ".$this->snd_to_sec."");
    $this->dodebug("  snd_to_usec: ".$this->snd_to_usec."");
    $this->dodebug("  snd_to_float: ".$this->snd_to_float."");
  }

  function timesplit($microseconds)
  {
    $secs = floor($microseconds / 1000000);
    $usecs = $microseconds % 1000000;
    return array($secs, $usecs, (float) ($microseconds / 1000000));
  }

  function sendSubscriptions()
  {
    $this->dodebug("Sapo_Broker_Net::entering sendSubscriptions()");
    foreach ($this->subscriptions as $subscription) {
      $this->dodebug("Sapo_Broker_Net::sendSubscriptions() subscribing ".$subscription['topic']);
      $msg = '<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope" xmlns:wsa="http://www.w3.org/2005/08/addressing" xmlns:mq="http://services.sapo.pt/broker">';
      $msg .= '<soap:Body>';
      $msg .= '<mq:Notify>';
      if ($subscription['topic']) {
        $msg .= '<mq:DestinationName>'.$subscription['topic'].'</mq:DestinationName>';    
      }
      if ($subscription['args']['destination_type']) {
        $msg.='<mq:DestinationType>'.strtoupper($subscription['args']['destination_type']).'</mq:DestinationType>';
      } else {
        $msg.='<mq:DestinationType>TOPIC</mq:DestinationType>';
      }
      if ($subscription['args']['acknowledge_mode']) {
        $msg .= '<mq:AcknowledgeMode>'.$subscription['args']['acknowledge_mode'].'</mq:AcknowledgeMode>';
      }
      $msg .= '</mq:Notify>';
      $msg .= '</soap:Body>';
      $msg .= '</soap:Envelope>';
      $ret = $this->write($msg);
      if ($ret==false) {
        return false;
      }
    }
  }

  function tryConnect($server,$port,$timeout=5)
  {
    $address = gethostbyname($server);
    $socket = @fsockopen($address, $port, $errno, $errstr, $timeout);
    if (!$socket) {
      return false;
    }
    @fclose($socket);
    return true;
  }

  function connect()
  {
    if (!$this->initted) {
      $this->init();
    }
    $this->con_retry_count++;
    $this->dodebug("Sapo_Broker_Net::Entering connect(".$this->server.":".$this->port.") ".$this->con_retry_count."");
    
    $address = gethostbyname($this->server);
    $this->socket = fsockopen($address, $this->port, $errno, $errstr);

    if (!$this->socket) {
      $this->dodebug($errstr);
      $this->last_err = $errstr;
      $this->connected = false;    
    } else {
      stream_set_timeout($this->socket, $this->snd_to_sec, $this->snd_to_usec);
      
      //
      // Set stream to blocking mode.
      //
      stream_set_blocking($this->socket, 1);
      
      $this->dodebug("Sapo_Broker_Net::Connected to server");
      $this->connected=true;
      $this->con_retry_count=0;
      $this->sendSubscriptions();
    }
    return $this->connected;
  }
  
  function write($msg)
  {
    if ($this->connected==false) {
      $this->dodebug("Sapo_Broker_Net::write() ups, we're not connected, let's go for ir");
      if ($this->connect()==false) {
        return false;
      }
    }
    $this->dodebug("Sapo_Broker_Net::write() socket_writing: ".$msg."\n");
    if (fwrite($this->socket, pack('N',mb_strlen($msg,'latin1')).$msg, mb_strlen($msg,'latin1') + 4)===false) {
      $this->connected = false;
      return false;
    }
    return true;
  }

  function readPoll()
  {
    if ($this->connected == false) {
      $this->dodebug("Sapo_Broker_Net::write() ups, we're not connected, let's go for ir");
      
      if ($this->connect() == false) {
        return false;
      }
    }
    
    $tmp = $this->netread(4);
    $len = (double)(ord((isset($tmp[3])?$tmp[3]:'')) + (ord((isset($tmp[2])?$tmp[2]:'')) << 8) + (ord((isset($tmp[1])?$tmp[1]:'')) << 16) + (ord((isset($tmp[0])?$tmp[0]:'')) << 24)); // unpack("N");
    //$len = unpack('N', $tmp); //returns array
    //$len = $len[1];
    
    if ($len == 0) {
      return false;
    } else {
      $res = $this->netread($len);
      return $res;
    }
  }

  function sendKeepalive($net)
  {
    $m="<?xml version='1.0' encoding='UTF-8'?>\n<soap:Envelope xmlns:soap='http://www.w3.org/2003/05/soap-envelope' xmlns:mq='http://services.sapo.pt/broker'><soap:Body>
<mq:CheckStatus /></soap:Body></soap:Envelope>";
    $net->write($m);
    $net->latest_status_timestamp_sent=date("Y-m-d\TH:m:s\Z");
  }

  function netread($len)
  {
    $this->dodebug("netread(".$len.") entering sokbuflen is ".$this->sokbuflen."");
    if ($this->connected==false) {
      $this->dodebug("Sapo_Broker_Net::netread() ups, we're not connected, let's go for ir");
      if ($this->connect()==false) {
        $this->dodebug("Sapo_Broker_Net::netread() couldn't reconnect");
        return '';
      }
    }
    $i=$this->sokbuflen;
    if ($this->debug) {
      if(function_exists('memory_get_usage')) {
        echo "Sapo_Broker_Net::PHP process memory: ".memory_get_usage()." bytes\n";
      } else {
        switch(php_uname('s')) {
          case "Darwin":
            $pid=getmypid();
            echo 'USER       PID %CPU %MEM      VSZ    RSS  TT  STAT STARTED      TIME COMMAND'."\n";
            ob_start();
            passthru('ps axu|grep '.$pid.'|grep -v grep');
            $var = ob_get_contents();
            ob_end_clean(); 
            echo $var;
            break;
        }
      }
    } // end this->debug
    while ($i<$len) { // read just about enough. do i hate sockets...
      $start=$this->timer->utime();
      $tmp=fread($this->socket, 1024); // read socket with timeout from stream-set-timeout. safer with fread.
      @ob_flush();
      flush(); 
      $end=$this->timer->utime(); // there's a problem with php's microtime function and the tcp timeout. This 0.1 offset fixes this.
      // Execute callbacks on subscribed topics
      foreach ($this->callbacks as $callback) { // periodic callbacks here, if any
        if (($this->callbacks_ts[$callback['id']]+$callback['period'])<=$this->timer->utime()) {
          $this->dodebug("Sapo_Broker_Net::Callbacking #".$callback['id']." ".$callback['name'].". Next in ".$callback['period']." seconds");
          $this->callbacks_ts[$callback['id']]=$this->timer->utime();
          call_user_func($callback['name'],$this);
        }
      } // end callbacks
      $l=mb_strlen($tmp,'latin1');
      $this->dodebug("Sapo_Broker_Net::Doing socket_read() inside netread()");
      $this->dodebug("end-start: ".($end-$start)."\nthis->rcv_to_float: ".$this->rcv_to_float."\nl: ".$l."\n");
      if ((($end-$start)<((float)($this->rcv_to_float)))&&$l==0) {
        $this->connected=false; return('');
      }
      $this->sokbuf.=$tmp;
      $this->sokbuflen+=$l;
      $i+=$l;
    }
    $this->sokbuflen-=$len;
    $r=substr($this->sokbuf,0,$len);
    $this->sokbuf=substr($this->sokbuf,$len); // cut
    $this->dodebug("Sapo_Broker_Net::netread(".$len.") leaving sokbuflen is ".$this->sokbuflen."");
    return $r;
  }

  function disconnect()
  {
    fclose($this->socket);
  }  
}



class Sapo_Broker_Parser
{
  var $pelements=array('DestinationName','TextPayload','Message','Status','Timestamp','MessageId');
        
  function Sapo_Broker_Parser ($debug = false,$instance)
  {
    $this->debug = $debug;
    $this->instance = $instance;
  }
 
  function worldmapPush($file)
  {
    $parser = xml_parser_create();
    if (!($fp = fopen($file, "r"))) {
      return false;
    }
    $data = fread($fp, filesize($file));
    fclose($fp);
    $vals = array();
    $index = array();
    xml_parse_into_struct($parser, $data, $vals, $index);
    xml_parser_free($parser);
    $ips=array();
    foreach ($index['IP'] as $i) {
      array_push($ips,$vals[$i]['value']);
    }
    return($ips[rand(0,count($ips)-1)]);
  }
 
  function getElements ($msg, $namespace = null)
  {
    //
    // Create a parser and set the namespace identifier.
    //
    if (!empty($namespace)) {
      $nsIdentifier = $namespace . ':';
      $xml = xml_parser_create_ns();
    } else {
      $nsIdentifier = null;
      $xml = xml_parser_create();
    }

    //
    // Set parser options.
    //
    xml_parser_set_option($xml, XML_OPTION_CASE_FOLDING, 0);
    xml_parser_set_option($xml, XML_OPTION_SKIP_WHITE, 1);

    //
    // Get elements from the XML document.
    //
    xml_parse_into_struct($xml, $msg, $values, $tags);
    foreach ($this->pelements as $eln) {
      $elements[$eln] = $values[$tags[$nsIdentifier . $eln][0]]['value'];
    }

    xml_parser_free($xml);
    
    return $elements;
  }

  function handlePackets ($msg, $subscriptions = array())
  {
    //
    // Get XML elements needed to handle subscriptions.
    //
    $elements = $this->getElements($msg, 'http://services.sapo.pt/broker');

    // It's a status message
    if (!empty($elements['Status'])) {
      $this->instance->latest_status_message=$elements['Message'];
      $this->instance->latest_status_timestamp_received=$elements['Timestamp'];          
    } else { // It's a payload
      //
      // If the destination name wasn't found try to find it
      // without using a namespace.
      //
      if (empty($elements['DestinationName'])) {
        $elements = $this->getElements($msg);
      }
      //
      // If a destination name was found, handle its associated callback.
      //
      if (!empty($elements['DestinationName'])) {
        foreach ($subscriptions as $subscription) {
          //if ($subscription['topic'] == $elements['DestinationName']) {
          if (preg_match($subscription['topic_reg'],$elements['DestinationName'])) {
            call_user_func($subscription['callback'], $elements['TextPayload'],$elements['DestinationName'],$this->instance);
            // only one callback per subscribed topic, only one subscription per topic
            return(array($subscription['args']['destination_type'],$elements['DestinationName'],$elements['MessageId']));
          }
        }
      }
    }
    return array(null,null,null);
  } // end handlePackets()
    
  /**
   * Parse the packet and return the payload
   * 
   * @var string $msg Broker message
   * 
   * @return array with DestinationName, MessageId, TextPayload
   */
  function parsePackets($msg)
  {
    //
    // Get XML elements needed to handle subscriptions.
    //
    $elements = $this->getElements($msg, 'http://services.sapo.pt/broker');
    
    // It's a status message
    if (!empty($elements['Status'])) {
      $this->instance->latest_status_message = $elements['Message'];
      $this->instance->latest_status_timestamp_received = $elements['Timestamp'];
    } else { // It's a payload
      //
      // If the destination name wasn't found try to find it
      // without using a namespace.
      //
      if (empty($elements['DestinationName'])) {
        $elements = $this->getElements($msg);
      }
      //
      // If a destination name was found, handle its associated callback.
      //
      if (!empty($elements['DestinationName'])) {
        return array($elements['DestinationName'], $elements['MessageId'], $elements['TextPayload']);
      }
    }
    return array(null, null, null);
  } // end parsePackets()
}



class Sapo_Broker_Parser_DOM extends Sapo_Broker_Parser
{
  function Sapo_Broker_Parser_DOM ($debug = false,$instance)
  {
    $this->debug = $debug;
    $this->instance = $instance;
  }

  function worldmapPush($file)
  {
    $parser = xml_parser_create();
    if (!($fp = fopen($file, "r"))) {
      return false;
    }
    $data = fread($fp, filesize($file));
    fclose($fp);
    $xml= new DOMDocument();
    $xml->preserveWhiteSpace=true;
    $xml->loadXML($data);
    $xpath = new DOMXpath($xml);
    $ips=array();
    foreach ($xpath->query('/world/domain/peer/transport/ip') as $nc) {
      array_push($ips,$nc->nodeValue);
    }
    return($ips[rand(0,count($ips)-1)]);
  }

  function getElements ($msg, $namespace = null)
  {
    // Create a new DOM document.
    $dom = new DOMDocument();
    $dom->loadXML($msg);
    
    //
    // Obtain the node lists, with or without namespaces.
    //
    if (!empty($namespace)) {
      foreach ($this->pelements as $eln) {
        $nodeLists[$eln] = $dom->getElementsByTagNameNS($namespace, $eln);
      }
    } else {
      foreach ($this->pelements as $eln) {
        $nodeLists[$eln] = $dom->getElementsByTagName($eln);
      }
    }

    //
    // Obtain the elements.
    //
    $elements = array();
    foreach ($nodeLists as $tagName => $nodeList) {
      $node = $nodeList->item(0);
      $elements[$tagName] = (isset($node->nodeValue)?$node->nodeValue:null);
    }
    return $elements;        
  }
}



class Sapo_Broker_Tools_Timer_PHP4
{
  function utime()
  {
    list($usec, $sec) = explode(" ", microtime());
    return (((float)$usec + (float)$sec)+0.001); // php bug with microtime. see http://www.rohitab.com/discuss/lofiversion/index.php/t25344.html
  }
}



class Sapo_Broker_Tools_Timer_PHP5
{
  function utime()
  {
    return ((float)microtime(true));
  }
}



class Sapo_Broker_Tools
{
  function xmlentities($string, $quote_style = ENT_QUOTES, $charset = 'UTF-8')
  {
    static $trans;
    if (!isset($trans)) {
      $trans = get_html_translation_table(HTML_SPECIALCHARS, $quote_style);
      foreach ($trans as $key => $value)
      $trans[$key] = '&#'.ord($key).';';
      // dont translate the '&' in case it is part of &xxx;
      $trans[chr(38)] = '&';
    }
    // after the initial translation, _do_ map standalone '&' into '&#38;'
    return preg_replace("/&(?![A-Za-z]{0,4}\w{2,3};|#[0-9]{2,3};)/","&#38;" , strtr(html_entity_decode($string), $trans));
  }
}

