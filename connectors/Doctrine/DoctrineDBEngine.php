<?php
/**
 * Licensed: MPL 1.1/GPL 2.0/LGPL 2.1
 * @license http://www.mozilla.org/MPL/
 *
 * See included LICENSE for full license information.
 */
global $app_base_path;
include_once($app_base_path.'/Connector.php');

include_once($app_base_path.'/Drupal/DrupalForm.php');

class DoctrineDBEngine extends Connector
	{

/**
 * Doctrine dal/orm connector class web form front end class
 */

	private $formObject;
	
	protected function connect()
	    {
	    $this->formObject = new DrupalForm($this->conParams['formUrl']);
	    $this->formObject->setLogin($this->conParams['user'],$this->conParams['password']);
	    $this->formObject->setFormName($this->conParams['form']); //note name of parent form in form tag, not hidden input tag.
	    }
	
	protected function get_data()
		{
		if(!$results = $this->formObject->submitForm($this->params))
		    {
		    $this->logger('Error submitting form or retreiving csv '.__LINE__);
		    return false;
		    }

		return $this->csv_to_array($results); //an array of results.
		}
	

	}
?>