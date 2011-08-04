<?php
/**
 * Licensed: MPL 1.1/GPL 2.0/LGPL 2.1
 * @license http://www.mozilla.org/MPL/
 *
 * See included LICENSE for full license information.
 */
	/**
	 * Maps are completely self contained and params may be sent to them
	 * An example of a screen scraping to csv download.
	 */
global $app_base_path;
require_once($app_base_path.'/Cloudshare/CloudshareSearchStats.php');
require_once($app_base_path.'/File/CSVWriter.php');

class MergeAEListCSV{

	
	function __construct($atRun=false)
		{
		if($atRun == true)
			{
			return $this->map();
			}
		}
		
	public function map()
		{
			$this->mapItems = array(
			'name'=>'CloudshareCSV',
			//define all sources-- should be an array of source arrays.
			'src'=> array(
			    array(
				'id'=>'set1', //could be used later for data parsing in the merge function.
				'type'=>'src',
				'handler'=>'CloudshareSearchStats',
				'connect'=>array(
					'startUrl'=> 'http://use.cloudshare.com/Ent/Vendor/Evals.aspx?evalType=Enterprise&evalFilter=Active', //url to the data
					'user'=>'user@example.com', //the cloudshare user
					'password'=>'EP0AAc5rTp', //pass
					'loginUrl'=>'https://use.cloudshare.com/Login.aspx',
					'logoutUrl'=>'http://use.cloudshare.com/Logout.aspx',
					'format'=>'HTML',
					'method'=>'get',
					),
				'params'=>array(
					'out'=>'csv',
					)
				)),
			//define all targets -- should be an array of target arrays.
			'tgt'=>array(
				array(
				'id'=>'set2',
				'type'=>'tgt',
				'handler'=>'CSVWriter',
				'connect'=>array(
					'fileUrl'=>'download://null/?filename=stats.csv&ext=csv',
					),
				'params'=>array(
					'format'=>'CSV',
					'delimiter'=>',',
					),
				),
			),
				
			);
		return $this->mapItems;
		}
	
	/**
	 * This is simple for this map because the tgt is identical to the src only with a new name... Could have used the old name through and through, but I'll stay with good convention and keep unique ids for both tgt and src.
	 * @param $src items
	 * @return tgt items
	 */
	public function merge($src)
	    {
	    $items['set2'] = $src['set1'];
	    
// 	    print_r( $src['set1'] );
// 	    die;
	    return $items;
	    }
}
?>
