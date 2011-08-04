<?php
/**
 * Licensed: MPL 1.1/GPL 2.0/LGPL 2.1
 * @license http://www.mozilla.org/MPL/
 *
 * See included LICENSE for full license information.
 */
global $lib_base_path;
include_once($lib_base_path.'/simplehtmldom/simple_html_dom.php');

/**
 * Class DrupalForm
 */
class DrupalForm
	{
	
	/**
	 * @var object Holds the curl session object.
	 */
	private $crl;
	/**
	 * @var bool Session flag.
	 */
	private $session;
	
	/**
	 * @var string
	 */
	private $url;
	
	/**
	 * @var string
	 */
	private $loginUrl;
	
	/**
	 * @var string
	 */
	private $logoutUrl;
	
	/**
	 * @var string holds the link to the cookiefile, hardcoded in the constructor.
	 * See __construct for more info.
	 */
	private $cookieJar;
	
	/**
	 * @var string
	 */
	private $user, $pass;
	
	/**
	 * @var array the map of drupal element names to class names
	 * See setDrupalFieldMap for usage.
	 */
	private $map;
	
	private $formName;
	
	public function __construct($url,$login='',$logout='')
		{
		if($login == '')
			$login = 'https://inside.mentorg.com/it/login?destination=mentor_it_home';

		if($logout == '')
			$lougout = 'http://inside.mentorg.com/it/logout';
		$this->setDrupalFieldMap();
		$this->cookieJar = '/tmp/cookie.txt';
		$this->session = false;
		$this->loginUrl = $login;
		$this->logoutUrl = $logout;
		$this->url = $url;
		$this->curlStart();
// 		register_shutdown_function(array($this,"destruct"));
		}
	
	function __destruct()
		{
		$this->logout();
		curl_close($this->crl);
		unlink($this->cookieJar);
		}
	
	public function setDrupalFieldMap($newMap='')
	    {
	    if(is_array($newMap))
		foreach ($newMap as $key=>$val)
		    $this->map[$key] = $val;
	    else {
		//set default drupal form map
		$this->map['token'] = 'form_token';
		$this->map['id'] = 'form_id';
		$this->map['build'] = 'form_build_id';
		}
	    }
	
	public function getDrupalFieldMap()
	    {
	    return $this->map;
	    }
	
	public function setLogin($user,$pass)
		{
		$this->user = $user;
		$this->pass = $pass;
		}
	/**
	 * Logout current user.
	 */
	public function logout()
		{
		if(!$this->session)
			{
			//no valid login
			return false;
			}
		$this->setFormUrl($this->logoutUrl);
		$this->drupalFormGet();
// 		unlink($this->cookieJar);
		$this->session = false;
		return true;
		}
	
	/**
	 * Logs in a user... cal setLogin to set the credentials.
	 * @var $this->session  bool
	 * @return bool
	 */
	
	public function login()
		{
		if($this->session == true)
			return true;
			
		$postdata=array(
			"name"=>$this->user,
			"pass"=>$this->pass,
			"form_id"=>"user_login",
			"op"=>"Log in"
			);
		$tmp = $this->url;
		$this->setFormUrl($this->loginUrl);

		$result = $this->drupalFormSubmit($postdata);

		$this->setFormUrl($tmp);
		if ( $result['headers']['http_code'] == 200 )
			{
			$this->session = true;
			return 1;
			}
		else {$this->session = false; return 0; }
		}
	
	/**
	 * Function to set the select form name... Should be the form id="name" attribute of your desired drupal form.
	 * @param $formName string the name of the form.
	 */
	public function setFormName($formName)
	    {
	    $this->formName = $formName;
	    }
	
	/**
	 * Prepare and submit a set of data to a valid Drupal form. If this doesn't work, use the login functions to auth.
	 * @param $data array of form keys to submit
	 * @param $submit string post or get
	 * @return array(data=>rawPage, headers=>rawHeaders) page result including http headers.
	 */
	public function submitForm($data,$submit='post')
	    {
	    $keys = $this->drupalGetValidForm();
	    $authFields = array(
		$this->map['token']=>$keys['token'],
		$this->map['id']=>$keys['id'],
		$this->map['build']=>$keys['build'],
		);
		
	    $submitData = array_merge($authFields, $data);

		if($submit == 'post')
			$response = $this->drupalFormSubmit($submitData);
		else if($submit == 'get')
			$response = $this->drupalFormGet($submitData);
	    
	    return $response;
	    }
	
	
	/** This function submits the curl form and returns an array of the data and headers
	 * @param array $postdata array of post data key=>value
	 * @return array of results(data => rawPage, headers => httpHeaders)
	 */
	private function drupalFormGet($postdata='')
		{
		if($postdata == '')
			$postdata = array();
		//curl_setopt ($this->crl, CURLOPT_POSTFIELDS, $postdata);
		$url = $this->url.'?';
		foreach($postdata as $key=>$val)
			{
			$url .= $key.'='.$val.'&';
			}
		$this->setFormUrl($url);
		$result['data'] = curl_exec($this->crl);
		$result['headers'] = curl_getinfo($this->crl);

		return $result;
		}
	
	/**
	 * Submit a prepared curl form.
	 * @param array $postdata
	 * @return array(data, headers)
	 */
	private function drupalFormSubmit($postdata)
		{

		curl_setopt ($this->crl, CURLOPT_POSTFIELDS, $postdata);
		$result['data'] = curl_exec($this->crl);
		$result['headers'] = curl_getinfo($this->crl);
		return $result;
		}
	/**
	 * Instantiate the curl session
	 */
	private function curlStart()
		{
		$this->crl = curl_init();
		curl_setopt($this->crl, CURLOPT_URL, $this->url);
		curl_setopt($this->crl, CURLOPT_COOKIEFILE, $this->cookieJar);
		curl_setopt($this->crl, CURLOPT_COOKIEJAR, $this->cookieJar);
		curl_setopt($this->crl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($this->crl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->crl, CURLOPT_POST, 1);
		}
	/**
	 * Set the url of the active form.
	 * @param string $url
	 */
	private function setFormUrl($url)
		{
		$this->url = $url;
		curl_setopt($this->crl, CURLOPT_URL, $this->url);
		}
		
	/**Gets valid form key elements for named form
	 * @param $formName the form name.
	 * @return array(token, id, build)
	 */
	private function drupalGetValidForm()
		{
		$this->login();

		$result = $this->drupalFormGet(array());
		// find form token with PHP DOM
		
		$html = str_get_html($result['data']);
		
		//find parent form element.
		
		$forms = $html->find("form[id={$this->formName}]");
		
		$form = current($forms);
		
		//Get correct token value
		$token = $form->find("input[name={$this->map['token']}]");
		$token = current($token);
		
		$build = $form->find("input[name={$this->map['build']}]");
		$build = current($build);
		
		$id = $form->find("input[name={$this->map['id']}]");
		$id = current($id);
		
		return array('token'=>$token->value, 'id'=>$id->value, 'build'=>$build->value);
		}
	}
	
?>