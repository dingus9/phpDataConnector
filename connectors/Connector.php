<?php
/**
* Licensed: MPL 1.1/GPL 2.0/LGPL 2.1
* @license http://www.mozilla.org/MPL/
*
* See included LICENSE for full license information.
*/

/**
* Connector base class
*/
  
class Connector{
  protected $conParams;
  protected $params;
  protected $error;
  protected $log;
  protected $data;
    
  function __construct($connect,$params='')
    {
    $this->error = false;
    $this->log = "Connector Start\n";
    $this->conParams = $connect;
    $this->params = $params;
    $this->connect();
    if($this->error)
      $this->logger('Connector: Error after connect()');
    }
  
  public function getError()
    {
    return $this->error;
    }
  
  /**
  * @return string
  */
  public function getLog()
    {
    return $this->log;
    }
  /**
  * @return array
  public function getMap()
    {
    return $this->MapArray;
    }
  
  /**
  * This classes logger.
  * @param array $message
  */
  protected function logger($message)
    {
    $this->log .= $message."\n";
    }
      
  /**
  * @param mixed $params an array or string that should be treated as config parameters to get data from a source.
  */
  public function getData($params='')
    {
    if($params != '')
        $this->params = $params;
    $this->get_data();
    return $this->data;
    }
  
  public function setData($data,$params='')
    {
    if($params != '')
        $this->params = $params;
    $this->data = $data;
    $this->set_data();
    return true;
    }

  }
?>