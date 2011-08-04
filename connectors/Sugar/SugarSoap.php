<?php
/**
* Licensed: MPL 1.1/GPL 2.0/LGPL 2.1
* @license http://www.mozilla.org/MPL/
*
* See included LICENSE for full license information.
*/

  /**
  * Sugar Connector
  */


class SugarSoap {
  protected $soapClient;
  
  private $loginParams, $authSession;
  
  
  public function __construct() 
    {
    global $lib_base_path;
    require_once($lib_base_path.'/nusoap/lib/nusoap.php');
    $proxyhost = isset($_POST['proxyhost']) ? $_POST['proxyhost'] : '';
    $proxyport = isset($_POST['proxyport']) ? $_POST['proxyport'] : '';
    $proxyusername = isset($_POST['proxyusername']) ? $_POST['proxyusername'] : '';
    $proxypassword = isset($_POST['proxypassword']) ? $_POST['proxypassword'] : '';
    
    $this->soapClient = new nusoap_client('http://wsp/soap.php?wsdl', 'wsdl',$proxyhost, $proxyport, $proxyusername, $proxypassword);

    if ($err = $this->soapClient->getError())
      $this->logger('<h2>Constructor error</h2><pre>' . $err . '</pre>');
    }

// Doc/lit parameters get wrapped
protected function login($user, $password) 
  {
  $this->loginParams =  array(
      'user_auth'=>array(
      'user_name'=>$user,
      'password'=>md5($password),
      'version'=>'.01',
      ),
    'application_name'=>'?',
    );
  $result = $this->soapClient->call('login', $param, '', '', false, true);
  if($this->soapClient->fault)
    $this->logger('Soap client fault after login. Dump:');
  else if($err = $this->soapClient->getError())
      {
      $this->logger('Soap client error while logging in'.$err);
      $this->authSession = false;
      }
  else $this->authSession = $result['id'];
  }

}

?>