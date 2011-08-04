<?php
/**
 * Licensed: MPL 1.1/GPL 2.0/LGPL 2.1
 * @license http://www.mozilla.org/MPL/
 *
 * See included LICENSE for full license information.
 */
    /**
    * Maps are completely self contained and params may be sent to them
    */
global $app_base_path;
require_once($app_base_path.'/Drupal/DrupalWebForm.php');
require_once($app_base_path.'/File/CSVWriter.php');

class FileDev{

    
    function __construct($atRun=false)
        {
        if($atRun == true)
            {
            return $this->map();
            }
        }
        
    public function map()
        {
            return array(
            'name'=>'LaptopRequest',
            //define all sources-- should be an array of source arrays.
            'src'=> array(
                array(
                'id'=>'set1', //could be used later for data parsing in the merge function.
                'type'=>'src',
                'handler'=>'DrupalWebForm',
                'connect'=>array(
                    'formUrl'=>'http://drupal.example.com/it/node/1284/webform-results/download', //url to the form
                    'form'=>'webform-results-download-form', //get this out of the page source as <form id="">
                    'user'=>'username', //the drupal user
                    'password'=>'drupalpass', //drupal pass
                    'format'=>'CSV',
                    ),
                'params'=>array(
                    'format'=>'delimited',
                    'delimiter'=>',',
                    'op'=>'Download',
                    )
                )),
            //define all targets -- should be an array of target arrays.
            'tgt'=>array(array(
                'id'=>'set2',
                'type'=>'tgt',
                'handler'=>'CSVWriter',
                'connect'=>array(
          'fileUrl'=> '/var/www/dataConnector-dev/somefile.csv#mode=c+', // ?filename=stats.csv&ext=csv
          ),
            'params'=>array(
              'format'=>'CSV',
              'delimiter'=>',',
              ),
                )),
                
            );
        }
    
    public function merge($src)
        {

        $src['set2'] = $src['set1'];
        return $src;
        }
}
?>