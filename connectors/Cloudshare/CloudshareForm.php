<?php
/**
 * Licensed: MPL 1.1/GPL 2.0/LGPL 2.1
 * @license http://www.mozilla.org/MPL/
 *
 * See included LICENSE for full license information.
 */


/**
 * Class CloudshareForm
 */
class CloudshareForm
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
		
	private $formName;
	
	public function __construct($url,$login='',$logout='')
		{
		$this->conHash = uniqid();
		if($login == '')
			$login = 'https://use.cloudshare.com/Login.aspx';

		if($logout == '')
			$lougout = 'http://use.cloudshare.com/Logout.aspx';

		$this->cookieJar = '/tmp/'.$this->conHash.'cookie';
		$this->session = false;
		$this->loginUrl = $login;
		$this->logoutUrl = $logout;
		$this->url = $url;
		$this->curlStart();
		}
	
	function __destruct()
		{
		$this->logout();
		curl_close($this->crl);
		unlink($this->cookieJar);
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
		$this->FormGet();
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
			'ctl00$ContentPlaceHolder1$UserName'=>$this->user,
			'ctl00$ContentPlaceHolder1$Password'=>$this->pass,
			'ctl00$ContentPlaceHolder1$LoginButton'=>"Log In",
			'__EVENTVALIDATION'=>'/wEWBAL+raDpAgLN49KjCQLo0YK0CQKh5/XjDWz8pGRhuSO/2r5OX4ClWzp7HOlz',
			'__VIEWSTATE'=>'',
			);

		$tmp = $this->url;
		$this->setFormUrl($this->loginUrl);

		$result = $this->FormSubmit($postdata);
		
		$this->setFormUrl($tmp);
		if ( $result['headers']['http_code'] == 200 )
			{
			$this->session = true;
			return true;
			}
		else {$this->session = false; return false; }
		}
	
	/**
	 * Function to set the select form name... Should be the form id="name" attribute of your desired  form.
	 * @param $formName string the name of the form.
	 */
// 	public function setFormName($formName)
// 	    {
// 	    $this->formName = $formName;
// 	    }
	
	/**
	 * Prepare and submit a set of data to a valid  form. If this doesn't work, use the login functions to auth.
	 * @param $data array of form keys to submit
	 * @param $submit string post or get
	 * @return array(data=>rawPage, headers=>rawHeaders) page result including http headers.
	 */
	public function submitForm($data='',$submit='post')
	    {
	    $this->login();
	    $submitData = $data;

		if($submit == 'post')
			$response = $this->FormSubmit($submitData);
		else if($submit == 'get')
			$response = $this->FormGet($submitData);
	    
	    return $response;
	    }
	
	
	/** This function submits the curl form and returns an array of the data and headers
	 * @param array $postdata array of post data key=>value
	 * @return array of results(data => rawPage, headers => httpHeaders)
	 */
	private function FormGet($postdata='')
		{
		curl_setopt($this->crl, CURLOPT_POST, false); 
		if($postdata == '')
			$postdata = array();
		else {
			$url = $this->url.'?';
			$url .= $this->encodePostdata($postdata);
			$this->setFormUrl($url);
			}
		$result['data'] = curl_exec($this->crl);
		$result['headers'] = curl_getinfo($this->crl);
		return $result;
		}
	
	/**
	 * Submit a prepared curl form.
	 * @param array $postdata
	 * @return array(data, headers)
	 */
	private function FormSubmit($postdata)
		{
		curl_setopt($this->crl, CURLOPT_POST, true);
		$postdata = $this->encodePostdata($postdata);

		curl_setopt ($this->crl, CURLOPT_POSTFIELDS, $postdata);
		$result['data'] = curl_exec($this->crl);
		$result['headers'] = curl_getinfo($this->crl);
		return $result;
		}
	private function EncodePostdata($postdata)
		{
		$newpost = '';
		foreach($postdata as $key=>$val)
			{
			$newpost .= urlencode($key).'='.urlencode($val).'&';
			}
			return $newpost;
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
		curl_setopt($this->crl, CURLOPT_AUTOREFERER, true);
		curl_setopt($this->crl, CURLOPT_TIMEOUT, 10);
		curl_setopt($this->crl, CURLINFO_HEADER_OUT, 0);
		curl_setopt($this->crl, CURLOPT_HEADER, 0);
		curl_setopt($this->crl, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; U; Linux x86_64; en-US; rv:1.9.2.6) Gecko/20100628 Ubuntu/10.04 (lucid) Firefox/3.6.6');
		curl_setopt($this->crl, CURLOPT_HTTPHEADER, array(
		'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
		'Accept-Language: en-us,en;q=0.5',
		'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7',
		'Keep-Alive: 115',
		'Connection: keep-alive',
		'Pragma: ',
		));
		curl_setopt($this->crl, CURLOPT_ENCODING, 'gzip,deflate');
		}
	/**
	 * Set the url of the active form.
	 * @param string $url
	 */
	public function setFormUrl($url)
		{
		$this->url = $url;
		curl_setopt($this->crl, CURLOPT_URL, $this->url);
		}
		
	}
	
?>