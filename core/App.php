<?php
/**
 * Licensed: MPL 1.1/GPL 2.0/LGPL 2.1
 * @license http://www.mozilla.org/MPL/
 *
 * See included LICENSE for full license information.
 */

class App{
  
  /** @var array an array of settings */
  $settings = array();
  
  /** @var string */
  $map = 'dummy';

  /**
   * This method initializes the app class and sets the internal active map class. 
   * ALERT: Never send this a map from user input!
   * @param string $map The map name to be loaded... Mapnames are the map classnames map classes are easy loaded.
   */  
  public function __construct($map,$settings=''){
    include(__DIR__.'settings.php');
    
    $this->settings = $settings;
    $this->map = $map;
    $this->mapFile = $this->settings['mapsdir'].'/'.$map.".php";
    try{
      include($this->mapfile);
      }
    catch(Exception $e){
      echo 'No map named '.$map;
      echo $e->getMessage();
      }
      
  }



}
?>