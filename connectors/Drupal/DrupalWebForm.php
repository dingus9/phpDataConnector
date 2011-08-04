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

/**
 * Drupal web form front end class
 */


/**
 * For the most part configs should be happening in the connector definition file and loaded by the Map class. 
 */
class DrupalWebForm extends Connector
	{
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
		$this->data = $this->csv_to_array($results); //an array of results.
		return true;
		}
	
	protected function csv_to_array($data)
		{
		$data['data'] = $this->utf16_to_utf8($data['data']);
		
		$new = tmpfile();
		fwrite($new, $data['data']);
		fflush($new);
		rewind($new);
		while($array[] = fgetcsv($new,4096,$this->params['delimiter'],'"'))
			{}

		fclose($new);

		return $array;
		}
		
	protected function utf16_to_utf8($str) 
		{
		$c0 = ord($str[0]);
		$c1 = ord($str[1]);

		if ($c0 == 0xFE && $c1 == 0xFF) {
		    $be = true;
		} else if ($c0 == 0xFF && $c1 == 0xFE) {
		    $be = false;
		} else {
		    return $str;
		}

		$str = substr($str, 2);
		$len = strlen($str);
		$dec = '';
		for ($i = 0; $i < $len; $i += 2) {
		    $c = ($be) ? ord($str[$i]) << 8 | ord($str[$i + 1]) : 
			    ord($str[$i + 1]) << 8 | ord($str[$i]);
		    if ($c >= 0x0001 && $c <= 0x007F) {
			$dec .= chr($c);
		    } else if ($c > 0x07FF) {
			$dec .= chr(0xE0 | (($c >> 12) & 0x0F));
			$dec .= chr(0x80 | (($c >>  6) & 0x3F));
			$dec .= chr(0x80 | (($c >>  0) & 0x3F));
		    } else {
			$dec .= chr(0xC0 | (($c >>  6) & 0x1F));
			$dec .= chr(0x80 | (($c >>  0) & 0x3F));
		    }
		}
		return $dec;
		}
		
	}
?>