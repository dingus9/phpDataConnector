<?php
/**
 * Licensed: MPL 1.1/GPL 2.0/LGPL 2.1
 * @license http://www.mozilla.org/MPL/
 *
 * See included LICENSE for full license information.
 */
 
global $app_base_path;
include_once($app_base_path.'/Connector.php');

include_once($app_base_path.'/Cloudshare/CloudshareForm.php');

/**
 * Drupal web form front end class
 */


/**
 * For the most part configs should be happening in the connector definition file and loaded by the Map class. 
 */
class CloudshareWebForm extends Connector
	{
	private $formObject;
	
	protected function connect()
	    {
	    $this->formObject = new CloudshareForm($this->conParams['targetUrl']);
	    $this->formObject->setLogin($this->conParams['user'],$this->conParams['password']);
	    return true;
	    }
	
	protected function get_data()
		{
		if(!$results = $this->formObject->submitForm($this->params,$this->conParams['method']))
		    {
		    $this->logger('Error submitting form or retreiving csv '.__LINE__);
			$this->error = true;
		    return false;
		    }

		$this->data = $this->csv_to_array($results); //an array of results.
		return true;
		}
	
	protected function csv_to_array($data)
		{
// 		$data['data'] = $this->utf16_to_utf8($data['data']);
		foreach (explode("\n",$data['data']) as $line)
			$array[] = str_getcsv($line, $this->conParams['delimiter'],'"');
		
		//remove trailing array entry if it is null
		for($i = 0; $i < count($array);$i++)
			if(count($array[$i]) != count($array[0]))
				unset($array[$i]);
		
		return $array;
		}
		
	}
?>