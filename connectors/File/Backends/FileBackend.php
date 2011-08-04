<?php
/**
 * Licensed: MPL 1.1/GPL 2.0/LGPL 2.1
 * @license http://www.mozilla.org/MPL/
 *
 * See included LICENSE for full license information.
 */
//File php class

class FileBackend
{

	private $url, $proto;
	private $contents;
	private $conn; //used for the target location... file,ftp or other destination connection.
	private $handle; //used for the source file... tmpfile or real.
	
	public function __construct($url)
		{
		$this->handle = false;
		$this->build_from_URL($url);
		if(!$this->open())
			return false;
		}
	
	public function __destruct()
		{
		if($this->handle !== false)
			$this->save();
		}
	
	public function write()
		{
		if(!empty($this->contents))
			fwrite($this->handle, $this->contents);
		}
	
	public function save()
		{
		switch($this->proto)
			{
			case 'ftp':
				$this->ftp_put();
				$this->ftp_close();
				break;
			case 'http':
			case 'https':
				return 'http not implemented yet';
				break;
			case 'file':
                if(!$this->file_save()){
					$error = true;
					$errmsg = 'File write error in '.__file__.' on '.__line__.' with mode '.$this->url['mode'];
					}
				break;
			case 'download':
				$this->download_prep();
				$this->download_start();
				break;
			default:
				return 'file not imlemented yet nick!! sheesh';
				break;
			}
			
			fclose($this->handle);
			$this->handle = false;
		}
	
	public function open()
		{
		$error = false;
		switch($this->proto)
			{
			case 'ftp':
				//check to see if the ftp connection url is ok.
				if(!$this->ftp_open())
					{
					$error = true;
					$errmsg = 'ftp open error in '.__file__.' on '.__line__.' with mode '.$this->url['mode'];
					}
				else
					{
					//load an existing file or open a new tmpfile for writing... assuming non exists means to make one.
					$this->ftp_get();
					}
				break;
			case 'http':
			case 'https':
				return 'http not implemented yet';
				break;
			case 'file':
				//Will create a handle to a real file or open an existing one as per the perms in the url string.

				$this->file_get(); 

				if(!$this->handle){
					$error = true;
					$errmsg = 'File open error in '.__file__.' on '.__line__.' with path = '.$this->url['path'].' and mode '.$this->url['mode'];
					}
				break;
			default:
				$this->handle = tmpfile();
				break;
			}
		if($error)
			{
			echo $errmsg;
			return false;
			}

		}
	
	protected function ftp_open()
		{
		$this->conn = ftp_connect($this->url['host'],$this->url['port']);
		if(!ftp_login($this->conn, $this->url['user'], $this->url['pass']))
			return false;

		ftp_pasv($this->conn, true);
		return true;
		}
	
	protected function ftp_get()
		{
		$this->handle = tmpfile();
		if(!$this->url['new'])
			{
			if(@ftp_fget($this->conn, $this->handle, $this->url['path'], FTP_BINARY))
				{
				return true;
				}
			else return false;
			}
		else return true;
		}
	
	protected function ftp_put()
		{
		if(@ftp_fput($this->conn, $this->url['path'],$this->handle, FTP_BINARY))
			{
			return true;
			}
		else return false;
		}
	
	/**
	 * Use this functon if you need to add something to the ftp close function call
	 */
	protected function ftp_close()
		{
		ftp_close($this->conn);
		}
	
	protected function setURL($url)
		{
		$this->url = $this->build_from_URL($url);
		}
	
	public function getHandle()
		{
		return $this->handle;
		
		}
	
	public function setContents($string)
		{
		$this->contents = $string;
		}
		
	/**
	 * Builds a file url and sets appropriate file IO defaults
	 * Form "scheme://path?params#internal opts IE: (mode=rwx)
	 * Example $url = "ftp://path/to/file#mode=w;new
	 *         $ulr = "/some/file/path#mode=r"
	 *         opts include:
	 *                      mode= [r,r+,w,w+,a,a+,x,x+,c,c+]
	 *                      new = [true,false]   default = false #force creation of new file or turncate file on writing used with c and c+
	 *                      trans = [true,false] default = false #translate endlines
	 */
	protected function build_from_URL($url)
		{
		$params = parse_url($url);


		if(isset($params['scheme']))
			$this->proto = $params['scheme'];
		else {
			$this->proto = 'file';
			$params['scheme'] = 'file';
			}

		$this->url = $params;
		
		//build the file params string... extra params like file mode rwx w+ etc.
		if(isset($params['fragment']) && strlen($params['fragment']) > 0)
			{
			$extras = explode(';',$params['fragment']);
			$opts= array();
			foreach($extras as $item)
				{
				$stuff = explode('=',$item);
				if(isset($stuff[1]))
					$opts[$stuff[0]] = $stuff[1];
				else if(isset($stuff[0]))
					$opts[$stuff[0]]=true;
				}
			if(array_key_exists('mode',$opts ))
				{
				$this->url['mode'] = $opts['mode'];
				}
			else $this->url['mode'] = 'r';
			
			if(isset($opts['new']))
				$this->url['new'] = $opts['new'];
			else $this->url['new'] = false;
			
			if(isset($opts['trans']))
				$this->url['trans'] = true;
			else $this->url['trans'] = false;
			
			if(isset($opts['trunc']))
				$this->url['trunc'] = true;
			else $this->url['trunc'] = false;
			}
			
			//parse the query into a set of params
			if(isset($this->url['query']))
						{
						foreach(explode('&',$this->url['query'] ) as $valset)
							{
							$pair = explode('=',$valset);
							
							if(!isset($pair[1])) //check for bool flag
								$pair[1] = true;
							
							$array = explode('[',$pair[0]);
							if(count($array) > 1)
								{
								$array[1] = rtrim($array[1],']');
								$this->url['params'][$array[0]][$array[1]] = $pair[1];
								}
							else $this->url['params'][$pair[0]] = $pair[1];
							
							}
						}
					
			//setup scheme unlisted defaults.
			switch($params['scheme'])
				{
				case 'ftp':
					if(!isset($this->url['port']))
						$this->url['port'] = '21';
					if(!isset($this->url['user']))
						{
						$this->url['user'] = 'anonymous';
						$this->url['pass'] = '';
						}
				
				break;
				case 'download': //set params array for download
					{
					
					}
				default: 
				}
		}
		
	/**
	 * This function prepairs php to output to a download file by setting appropriate headers.
	 * This means no output should be prior or after this point.
	 */
	protected function download_prep()
		{
		if(isset($this->url['params']['filename']))
			$filename = $this->url['params']['filename'];
		else $filename = 'download';
		
		if(isset($this->url['params']['ext']))
			$ext = $this->url['params']['ext'];
		else $ext = '';

		// Write extra headers.
		if(isset($this->url['params']['header']))
			foreach($this->url['params']['header'] as $key => $val)
				header("$key=$val");
		
		$fstat = fstat($this->handle);
		header("Content-Length: ".$fstat['size']);
		
			// Determine Content Type 
		switch ($ext) { 
			case "pdf": $ctype="application/pdf"; break; 
			case "exe": $ctype="application/octet-stream"; break; 
			case "zip": $ctype="application/zip"; break; 
			case "doc": $ctype="application/msword"; break; 
			case "xls": $ctype="application/vnd.ms-excel"; break; 
			case "ppt": $ctype="application/vnd.ms-powerpoint"; break; 
			case "gif": $ctype="image/gif"; break; 
			case "png": $ctype="image/png"; break; 
			case "jpeg": 
			case "jpg": $ctype="image/jpg"; break;
			case 'csv': $ctype='text/csv'; break;
			default: $ctype="application/force-download"; 
			} 
		
		header("Pragma: public"); // required 
		header("Expires: 0"); 
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
		header("Cache-Control: private",false); // required for certain browsers 
		header("Content-Type: $ctype"); 
		header("Content-Disposition: attachment; filename=\"$filename\";" ); 
		header("Content-Transfer-Encoding: binary");
		}
	protected function download_start()
		{
		@ob_clean();
		@flush();
		rewind($this->handle);
		fpassthru($this->handle);
		}
		
	protected function file_save()
		{
		if(!fflush($this->handle))
			return false;
		}
	
	protected function file_get()
		{
		$this->handle = fopen($this->url['path'],$this->url['mode'].'b');

		if(!$this->handle)
			return false;
		if(isset($this->url['new']) && ($this->url['mode'] == 'c' || $this->url['mode'] == 'c+'))
			ftruncate($this->handle,0);
		return true;
		}
}

?>