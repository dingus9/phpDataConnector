<?php
/**
 * Licensed: MPL 1.1/GPL 2.0/LGPL 2.1
 * @license http://www.mozilla.org/MPL/
 *
 * See included LICENSE for full license information.
 */
global $app_base_path;

include_once($app_base_path.'/Connector.php');

include_once('Backends/FileBackend.php');

/**
 * Drupal web form front end class
 */


/**
 * For the most part configs should be happening in the connector definition file and loaded by the Map class. 
 */
class FileWriter extends Connector
	{
	protected $fileObject;
	
	protected function connect()
	    {
	    if(!$this->fileObject = new FileBackend($this->conParams['fileUrl']))
			{
			$this->logger('Error in FileBackend connection or path setup');
			$this->error = true;
			}
	    }
	
	protected function setContents()
		{

		}
	
	//this function assumes string formatted data to be writen to a file... use unix \n to add line breaks. etc.
	protected function set_data()
		{
		if(is_string($this->data))
			{
			$this->fileObject->write($this->data);
			$this->data = '';
			$this->logger('Additional data in text buffer written out and reset');
			}
		else if(is_resource($this->data))
			$this->logger('All data already writen to raw file resource skipping additional write');
		else
			$this->logger('writing empty data to file: Try using createEmpty()');
		//finalize with a save
		$this->fileObject->save();
		}
		

}
?>