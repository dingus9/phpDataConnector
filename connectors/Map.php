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
  
class Map{
  protected $mapDir;
  protected $error;
  protected $MapObj;
  protected $MapName;
  protected $log;
    
  function __construct($mapDir)
    {
    $this->mapDir = $mapDir;
    $this->log = "Map Start\n";

    
    global $lib_base_path, $app_base_path;
    $app_base_path = __DIR__;
    $lib_base_path = __DIR__.'../lib';
    $this->basePath = $app_base_path;
    $this->libPath = $lib_base_path;
    }
  
  /**
  * @param mixed $map (an config map object or map name)
  */

  public function setMap($map)
    {
    if(is_string($map))
        if(!$this->load_Map($map))
      {
      $this->logger("Error occured in setMap".__LINE__);
      return false;
      }
    else if(is_object($map))
        {
        $this->MapObj = $map;
        }
    }

  /**
  * @return string
  */
  public function getLog()
    {
    return $this->log;
    }
  
  public function getMap()
    {
    return $this->MapObj;
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
  * Load the map.
  */
  protected function load_Map($mapName)
    {
    if(file_exists($this->mapDir.'/'.$mapName.'.php'))
      {
      include_once($this->mapDir.'/'.$mapName.'.php');
      $this->MapObj = new $mapName();
      $this->Map = $mapName;
      //this is the naming convention for this module... override this function to change!
      return true;
      }
    else {
      $this->logger("Map $mapName does not exist ".__LINE__);
      return false;
      }
    }
  
  /**
  * This is the workhorse function that loads and runs all of the maps
  */
  public function runMap()
    {
    $src = array();
    $tgt = array();
    $connectors = array();
    $maps = $this->MapObj->map();
    foreach($maps['src'] as $item)
        {
        //load the connector
        $conn = new $item['handler']($item['connect'],$item['params']);
        if($conn->getError())
        {
        $this->logger('Fatal error in connector '. $item['handler']."\nMessages:\n");
        $this->logger($conn->getLog());
        }
      else
        {
        $src[$item['id']] = $conn->getData();
        $this->logger('Logs for connector: '.$item['handler']."\n".'set: src '.$item['id']);
        $this->logger($conn->getLog());
        }
        unset($conn);
        }

    //merged items should reflect the desired end point field map... the id field defines which fields match which tgt connection. IE: item[id] matches merged[id]=>array(values)
    $merged = $this->MapObj->merge($src);
    
    $rslt = array();
    foreach($maps['tgt'] as $item)
        {
        //load the connector
        $conn = new $item['handler']($item['connect'],$item['params']);
      if($conn->getError())
        {
        $this->logger('Fatal error in connector '. $item['handler']."\nMessages:\n");
        $this->logger($conn->getLog());
        }
        else
        {
        if(array_key_exists($item['id'],$merged)) //make sure there is data to be written to this handler
          {
          $rslt[$item['id']] = $conn->setData($merged[$item['id']]);
          $this->logger('Logs for connector: '.$item['handler']."\n".'set: tgt '.$item['id']);
          $this->logger($conn->getLog());
          }
        }
        unset($conn);
        }
    }

  }
?>