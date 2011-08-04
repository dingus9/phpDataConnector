<?php
/**
 * Licensed: MPL 1.1/GPL 2.0/LGPL 2.1
 * @license http://www.mozilla.org/MPL/
 *
 * See included LICENSE for full license information.
 */
	/**
	 * Maps are completely self contained and params may be sent to them
	 * A screen scaping to custom dbstore.
	 */
global $app_base_path;
require_once($app_base_path.'/Cloudshare/CloudshareSearchStats.php');
require_once($app_base_path.'/Cloudshare/CloudshareStore.php');

class CloudshareWebToDb{

	
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
					//'http://use.cloudshare.com/Ent/Vendor/Evals.aspx?evalType=Enterprise&evalFilter=All&daysBack=0',
					'user'=>'user@example.com', //the cloudshare user
					'password'=>'testPass', //pass
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
				'id' => 'set2',
				'type' => 'tgt',
				'handler' => 'CloudshareStore',
				'connect' => array(
					'server'=>'localhost',
					'user'=>'testUser',
					'password' => 'TESTPASS2',
					'database'=>'cloudshare_stats',
					),
				'params'=>array(
					'tables'=>array( array('name'=>'client_log','primKey'=>'OwnerEmail', 'primTwo'=>'Prototype'),
						),
					'updateTable' => false,
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
		$entries = array();
		$items = array();
		
		$merge = array(
		'Blank' => '',
		'Organization' => 'Organization',
		'Prototype' => 'Prototype',
		'UserName' => 'UserName',
		'OwnerEmail'=> 'OwnerEmail',
		'Status' => 'Status',
		'Type' => 'Type',
		'Link' => 'Link',
		'OwningOrganization' => 'OwningOrganization',
		'OwningVendorUser' => 'OwningVendorUser',
		'CurrentPackage' => 'CurrentPackage',
		'Campaign' => 'Campaign',
		'Owner' => 'Owner',
		'CreateTime' => 'CreateTime',
		'SuspendTime' => 'SuspendTime',
		'RemainingRunTime' => 'RemainingRunTime',
		'ExpirationTime' => 'ExpirationTime',
 		'ArchiveTime' => 'ArchiveTime',
		'BasedOnSnapshot' => 'BasedOnSnapshot',
		'Application' => 'Application',
		'ResourceConsumption' => 'ResourceConsumption',
		'LastUpdate'=>'LastUpdate',
		);
		
		$i=0;
		foreach($src['set1'] as $entry)
			{
			
			foreach($entry as $name=>$val)
				{
				if(isset($merge[$name]) && $merge[$name] != '')
					$entries[$merge[$name]] = $val;
				}
			$entries['LastUpdate'] = date('Y-m-d H:i:s',strtotime('+8 hours'));
			$items['set2']['client_log'][$i++] = $entries;
			}
	    return $items;
	    }
}
?>
