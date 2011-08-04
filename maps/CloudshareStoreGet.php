<?php
/**
 * Licensed: MPL 1.1/GPL 2.0/LGPL 2.1
 * @license http://www.mozilla.org/MPL/
 *
 * See included LICENSE for full license information.
 */
	/**
	 * Maps are completely self contained and params may be sent to them
	 * A crude example of a custom database to JSON http map
	 */
global $app_base_path;
require_once($app_base_path.'/Output/WriteOut.php');
require_once($app_base_path.'/Cloudshare/CloudshareStore.php');

class CloudshareStoreGet{

	
	function __construct($atRun=false)
		{
		if($atRun == true)
			{
			return $this->map();
			}
		}
		
	public function map()
		{
			//get php input values
			$input = $this->get_php_input();
			
			if($input['user'] == '*')
				{
				$user = '1';
				}
			else $user = "OwnerEmail = '$input[user]'";
			
			if($input['prototype'] == '*')
				{
				$proto = '';
				}
			else $proto = "Prototype = '$input[prototype]' and ";
			
			$query = "select * from client_log where $proto $user";
			
			$this->mapItems = array(
			
			//define all targets and sources -- should be an array of target arrays.
			'src'=>array(
				
				array(
				'id' => 'set1',
				'type' => 'src',
				'handler' => 'CloudshareStore',
				'connect' => array(
					'server'=>'localhost',
					'user'=>'user',
					'password' => 'pass',
					'database'=>'cloudshare_stats',
					),
				'params'=>array(
					'tables'=>array( array('name'=>'client_log','primKey'=>'OwnerEmail', 'primTwo'=>'Prototype'),
						),
					'updateTable' => false,
					'query' => $query,
					),
				),
			),
			'tgt' => array(
			
				array('id' 	=> 'set2',
				'type' 	=> 'tgt',
				'handler' => 'WriteOut',
				'connect' => array(
					'type' 		=> 'buffer',
					'format' 	=> 'JSON',
					),
				'params' => array(),
				),
			),
				 );

		return $this->mapItems;
		}
	
	private function get_php_input()
		{
		$user = '';
		$proto = '';
		if(isset($_REQUEST['user']))
			{
			$user = $this->clean_str($_REQUEST['user']);
			}
		
		if(isset($_REQUEST['prototype']))
			{
			$proto = $this->clean_str($_REQUEST['prototype']);
			}
			
		return array('prototype'=>$proto, 'user'=>$user);
		}
	
	private function clean_str($str)
		{
		$str = htmlentities($str, ENT_QUOTES, 'UTF-8');
		return $str;
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
		
		$items['set2'] = $src['set1'];
	    return $items;
	    }
}
?>
