<?php
/**
 * Licensed: MPL 1.1/GPL 2.0/LGPL 2.1
 * @license http://www.mozilla.org/MPL/
 *
 * See included LICENSE for full license information.
 */
global $app_base_path;

include_once($app_base_path.'/Connector.php');


/**
 * A cloudshare database specific storage class
 */


/**
 * For the most part configs should be happening in the connector definition file and loaded by the Map class. 
 */
class WriteOut extends Connector
	{
	
	private $output;
	private $type, $format;
	/**
	 * Setup the type and output format...
	 * types: array, buffer, string
	 * formats: raw(printed or raw structure), json, xml
	 */
	protected function connect()
	    {
		if(isset($this->conParams['type']))
			$this->type = $this->conParams['type'];
		else $this->type = 'string';
		
		if(isset($this->conParams['format']))
			$this->format = $this->conParams['format'];
		else $this->format = 'raw';
		
		return true;
	    }
	
	protected function get_data()
		{

		}
	
	protected function set_data()
		{
		switch($this->format)
			{
			case 'raw':
				$this->output = $this->raw_data();
				break;
			case 'json':
			case 'JSON':
			case 'Json':
				$this->output = $this->json_data();
				break;
			case 'xml':
			case 'XML':
			case 'Xml':
				$this->output = $this->xml_data();
				break;
			}
			
		}
	
	private function json_data()
		{
		$results = $this->data;
		$results = array('result'=>count($results),'data'=>$results);
		$ret = json_encode($results);
			
		if($this->type == 'buffer')
			{
			echo $ret;
			}
		else return $ret;
		}
		
	private function xml_data()
		{
			
		}
		
	private function raw_data()
		{
			
		}
		
	}
?>