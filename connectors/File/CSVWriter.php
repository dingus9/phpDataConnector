<?php
/**
 * Licensed: MPL 1.1/GPL 2.0/LGPL 2.1
 * @license http://www.mozilla.org/MPL/
 *
 * See included LICENSE for full license information.
 */
global $app_base_path;
include_once($app_base_path.'/File/FileWriter.php');

/**
 * Examples
 * in the maps directory
'tgt'=>array(
				array(
				'id'=>'set2',
				'type'=>'tgt',
				'handler'=>'CSVWriter',
				'connect'=>array(
					'fileUrl'=>'ftp://anonymous:svc_wsp@mentor.com@ftp.mentor.com/incoming/cloudshare/cloudshareStats_'.date("m-d-Y").'.csv#mode=w;new',
					),
				'params'=>array(
					'format'=>'CSV',
					'delimiter'=>',',
					),
				),

global $app_base_path;

include_once($app_base_path.'/Connector.php');

include_once('FileWriter.php');

/*
/**
 * CSV handler class uses Filehandler.
 * For the most part configs should be happening in the connector definition file and loaded by the Map class. 
 */
class CSVWriter extends FileWriter
	{
	private $delim, $enc; //delimiter and enclosure values single char string.
	
	
	protected function connect()
	    {
	    //csv specific setup.
	    $this->delim = $this->params['delimiter'];
	    $this->enc = '"';

	    parent::connect();
	    }
	
	protected function get_data()
		{
		parent::get_data(); //call the appropriate file open call for the file URL.
		$this->file = $this->fileObject->getReadHandle();
		$this->data = $this->csv_to_array(); //use raw file handle becuase fgetcsv is special.
		}
	
	/**
	 * @param file $file resource to the file... this is special because we need to use the fgetcsv() function.
	 * @return array of csv contents...
	 */
	protected function csv_to_array()
		{
		$array = fgetcsv($this->file, $this->delim,$this->enc);

		return $array;
		}
	
	/**
	 * Send this function an array arrays with values one line per internal array.
	 */
	protected function array_to_csv($data)
		{

		rewind($this->file);
		foreach($data as $lineArray)
			{

			if(!is_bool($lineArray))
					fputcsv($this->file, $lineArray, $this->delim,$this->enc);
			}
		rewind($this->file);
		}
	
	protected function set_data()
		{
		if(is_array($this->data))
			{
			$this->file = $this->fileObject->getHandle();
		    $this->array_to_csv($this->data);
		    $this->data = $this->file;
			}
		
		parent::set_data();
		}
}
?>