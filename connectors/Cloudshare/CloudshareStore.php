<?php
/**
 * Licensed: MPL 1.1/GPL 2.0/LGPL 2.1
 * @license http://www.mozilla.org/MPL/
 *
 * See included LICENSE for full license information.
 */
global $app_base_path;

include_once($app_base_path.'/Connector.php');


/**
 * A cloudshare database specific storage class
 */


/**
 * For the most part configs should be happening in the connector definition file and loaded by the Map class. 
 */
class CloudshareStore extends Connector
	{
	protected $dbObject;
	private $tables, $currTable, $primKey, $primTwo;
	
	/**
	 * Setup the database connection and table storage
	 */
	protected function connect()
	    {
		$this->dbObject = mysql_connect($this->conParams['server'], $this->conParams['user'], $this->conParams['password']);

		if(!$this->dbObject)
			{
			$this->logger('Failed to connect to database server in'.__FILE__.' on line '.__LINE__.' : because '.mysql_error());
			return false;
			}
		if(!mysql_select_db($this->conParams['database'],$this->dbObject))
			{
			$this->logger('Database does not exist');
			return false;
			}
		
		$this->currTable = $this->params['tables'][0]['name'];
		$this->primKey = $this->params['tables'][0]['primKey'];
		$this->primTwo = $this->params['tables'][0]['primTwo'];
		
		return true;
	    }
	
	protected function get_data()
		{
		$query = $this->params['query'];
		$results = array();
		
		if(!$result = mysql_query($query, $this->dbObject))
		    {
		    $this->logger('Error running query'.__LINE__);
		    return false;
		    }
		else {
			while($row = mysql_fetch_assoc($result))
				{
				$results[] = $row;
				}
			}

		$this->data = $results;
		return true;
		}
	
	protected function set_data()
		{
		$activeHash = array();
		
		//find and remove duplicates only use entries with latest time stamp
		$workList = array();
		foreach($this->data[$this->currTable] as $key=>$entry)
			{
			//get most recent time stamp
			if(array_key_exists('ExpirationTime',$entry))
				$timeVal = $entry['ExpirationTime'];
			else if(array_key_exists('ArchiveTime',$entry))
				$timeVal = $entry['ArchiveTime'];
			else if(array_key_exists('CreateTime',$entry))
				$timeVal = $entry['CreateTime'];
			$timeVal = explode('(',$timeVal);
			
			$timeStamp = strtotime($timeVal[0]);
			$hash = $entry[$this->primKey].'-'.$entry[$this->primTwo];
			if(array_key_exists($hash,$workList))
				{
				if($workList[$hash]['stamp'] < $timeStamp)
					$workList[$hash] = array('stamp' => strtotime($timeVal[0]), 'index'=>$key);
				}
			else $workList[$hash] = array('stamp' => strtotime($timeVal[0]), 'index'=>$key);
			}
		
		
		//build working hash array(activeHash) list without dups and set data to new master list
		foreach($workList as $key=>$entry)
			{
			$activeHash[] = array($this->primKey=> $this->data[$this->currTable][$entry['index']][$this->primKey],
								  $this->primTwo=> $this->data[$this->currTable][$entry['index']][$this->primTwo]);
			$newMaster[] = $this->data[$this->currTable][$entry['index']];
			}
		
		$this->data[$this->currTable] = $newMaster;

		
		$statusList = $this->get_status_list('Removed',false);
				
		$removed = $this->diff_multi_arrays($statusList,$activeHash,$this->primKey,$this->primTwo);
		
		if(count($removed))
			$this->mark_removed($removed);
		
		$existing = $this->get_existing($activeHash); //returns an array of emails that need to be updated.

		$insert = $this->diff_multi_arrays($activeHash, $existing,$this->primKey,$this->primTwo); //subtract current set from update set to get new insert entries set

		if(count($existing))
			$this->update_current($existing);
		if(count($insert))
			$this->insert_current($insert);
		
		}

	private function diff_multi_arrays($a, $b,$key1,$key2)
		{
		$matches = array_fill(0,count($a), false);
		$retlist = array();

		foreach($a as $akey=>$aval)
			{
			foreach($b as $bkey=>$bval)
				{
				if($aval[$key1] === $bval[$key1] && $aval[$key2] === $bval[$key2])
					$matches[$akey] = true;
				}
			}

		foreach($matches as $mkey=>$match)
			if(!$match)
				$retlist[] = $a[$mkey];
		return $retlist;
		}
		
	
	//get a list of the status field = to $status or inverted.
	private function get_status_list($status, $noInvert=true)
		{
		$data = array();
		if(!$noInvert)
			$op = '!=';
		else $op = '=';
		
		$query = "select {$this->primKey},{$this->primTwo} from {$this->currTable} where status ".$op." '$status';";
		$result = mysql_query($query, $this->dbObject);
		
		if(!$result)
			{
			$this->logger('No data results '.__FILE__.' at line '.__LINE__);
			return $data;
			}

		while($row = mysql_fetch_assoc($result))
			{
			$data[] = $row;
			}
		return $data;
		}
	
	private function mark_removed($list)
		{
		$query = "update {$this->currTable} set status = 'Removed' where ";
// 		print_r($list);
// 		die;
		foreach($list as $item)
			{
			$query .= "{$this->primKey} = '{$item[$this->primKey]}' and {$this->primTwo} = '{$item[$this->primTwo]}' or ";	
			}

		$query .= '0;';

		$result = mysql_query($query, $this->dbObject);
		if(!$result)
			{
			$this->logger('No data results '.__FILE__.' at line '.__LINE__);
			return array();
			}
		}
	
	private function update_current($list)
		{

		foreach($this->data[$this->currTable] as $item)
			{
			if($this->in_sub_array($item[$this->primKey],$list,$this->primKey))
				{
				$query = "update {$this->currTable} set ";
				$keylist = '';
				foreach($item as $key=>$val)
					$query .= "$key = '$val',";

				$query = rtrim($query,',');

				$query .= " where {$this->primKey} = '{$item[$this->primKey]}' and {$this->primTwo} = '{$item[$this->primTwo]}';";

				if(!$result = mysql_query($query,$this->dbObject))
					{
					$this->logger('Failed to update records in '.__FILE__.' at '.__LINE__);
					$this->logger(mysql_error($this->dbObject));
					}
				}
			}
		}
	
	private function in_sub_array($needle, $list,$sub_key)
		{
		foreach($list as  $val)
			{
			if($val[$sub_key] == $needle)
				{
				return 1;
				}
			}
		return 0;
		}
	
	private function insert_current($list)
		{
		$data = $this->data[$this->currTable];
		$keysStr = '';
		$keys = array();
		
		foreach($data[0] as $key=>$item)
			{
			$keysStr .= "$key,";
			$keys[$key] = $key;
			}
			$keys['LastUpdate'] = 'LastUpdate';
		$keysStr = rtrim($keysStr,',');
		
		$query = "insert into {$this->currTable} ($keysStr) values";
		
		foreach($data as $item)
			{
			$query .= '(';
			foreach($keys as $key)
				$query .= '\''.$item[$key].'\',';
			$query = rtrim($query,',');
			$query .= '),';
			}
		$query = rtrim($query,',');
		if(!$result = mysql_query($query,$this->dbObject))
			{
			$this->logger('Failed to insert records in '.__FILE__.' at '.__LINE__);
			$this->logger(mysql_error($this->dbObject));
			}
		else $this->logger('Inserted new records in '.__FILE__.' at '.__LINE__);
		}
	
	//send it the array and it will return the existing rows for updates.
	private function get_existing($list)
		{
		//get existing entries
		$query = "select {$this->primKey},{$this->primTwo} from {$this->currTable} where ";
		
		foreach($list as $item)
			{
			$query .= "{$this->primKey} = '{$item[$this->primKey]}' and {$this->primTwo} = '{$item[$this->primTwo]}' or ";	
			}
		$query .= '0;';
		
		$result = mysql_query($query, $this->dbObject);
		if(!$result)
			{
			$this->logger('No data results '.__FILE__.' at line '.__LINE__);
			return array();
			}
		
		$existing = array();
		while($row = mysql_fetch_assoc($result))
			{
			$existing[] = $row;
			}
		return $existing;
		}
	

	}
?>