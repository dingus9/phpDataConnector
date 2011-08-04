<?php

include_once('Connectors/Map.php');

 
  //Drupal Test
  $map = new Map(__DIR__.'/maps'); //path to maps directory
  $map->setMap('LptRequest');
  $map->runMap();
  echo $map->getLog();
  

?>
