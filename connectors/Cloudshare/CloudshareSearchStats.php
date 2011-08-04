<?php
/**
 * Licensed: MPL 1.1/GPL 2.0/LGPL 2.1
 * @license http://www.mozilla.org/MPL/
 *
 * See included LICENSE for full license information.
 */
 
global $lib_base_path, $app_base_path;
include_once($app_base_path.'/Connector.php');

include_once($app_base_path.'/Cloudshare/CloudshareForm.php');

include_once($lib_base_path.'/simplehtmldom/simple_html_dom.php');


/**
 * Drupal web form front end class
 */

/**
 * For the most part configs should be happening in the connector definition file and loaded by the Map class. 
 */
class CloudshareSearchStats extends Connector
    {
    private $formObject;
    
    protected function connect()
        {
        $this->formObject = new CloudshareForm($this->conParams['startUrl'],$this->conParams['loginUrl'],$this->conParams['logoutUrl']);
        $this->formObject->setLogin($this->conParams['user'],$this->conParams['password']);
        return true;
        }
    
    protected function get_data()
        {
        if(!$results = $this->formObject->submitForm($this->params,$this->conParams['method']))
            {
            $this->logger('Error submitting form or retreiving csv '.__LINE__);
            $this->error = true;
            return false;
            }
		$this->data = $this->getUserStats(); //an array of results.
        return true;
        }
        
    /**
     * This function is hard coded due to the complexity of the HTML dome element, if you want to extend or modify it's form
     * copy and create a new one.
     */
    private function getUserStats()
		{
		$page = $this->conParams['startUrl'];
		$run = true;

		$dom = $this->getPageDom($page); //get pages
		$table = $dom->find('[id=ctl00_ctl00_MainContentPlaceHolder_MainContentPlaceHolder_AutoGrid1]',0);
		if(count($table) < 1)
			{
			$this->logger('Error parsing page'.$page);
			return false;
			}

		$indexRow =  $table->find('tr',0);
		$thArr = $indexRow->find('th');

		//build a form index from the th elements... notes position
		foreach($thArr as $th)
			{
			if($th->plaintext == '&nbsp;')
				$index[] = 'Link';
			else if(trim($th->plaintext) == '')
				$index[] = 'Blank';
			else{
				$index[] = str_replace(" ",'',$th->plaintext);
				}
			}

		$rows = $table->find('tr[class=gridRow]');
		$entries = array();
		$rowC = 0; //allows for a row offset incase a header row is desired.
		foreach($rows as $row)
			{
			$entries[$rowC] = array();
			
			for( $i=0; $i < count($index); $i++)
				{
				if($index[$i] == 'Link')
					{
					$url = parse_url($this->conParams['startUrl']);
					$href = $row->find('td',$i)->find('a',0)->href;
					$entries[$rowC][$index[$i]] = $url['scheme']."://".$url['host'].$href;
					}
				else $entries[$rowC][$index[$i]] = trim($row->find('td',$i)->plaintext);
				}
			// get stats from individual stats page.
			$data = $this->getUserStatsPage($entries[$rowC]['Link']);

			foreach($data as $key => $val)
				{
				$k = str_replace(" ",'',$key);
				if($k == 'Owner')
					{
					$matches = array();
					if(preg_match("/\(.*\)/", $val,$matches))
						$entries[$rowC]['OwnerEmail'] = trim($matches[0],')(');
					else $entries[$rowC]['OwnerEmail'] = 'unknown@null.com';
					
					$entries[$rowC][$k] = $val;
					}
				else
					$entries[$rowC][$k] = $val;
				}
			$rowC++;
			}

		return $entries;
		}
    
    /**
     * Another one off section of code unique to the cloudshare html document structure
     * @param $page string the page url
     */
    private function getUserStatsPage($page)
		{
		
		//get to first rows in table via dom
		$dom = $this->getPageDom($page);

		$rows = $dom->find('table[id=ctl00_ctl00_MainContentPlaceHolder_MainContentPlaceHolder_AutoDetails1]',0)->find('tr');
		
		// load td data into array
		$vals = array();
		foreach($rows as $row)
			{
			$vals[$row->find('td',0)->plaintext] = trim($row->find('td',1)->plaintext);
			}
		return $vals;
		}
    
    private function getPageDom($url='')
        {
        $this->formObject->setFormUrl($url);
        $page = $this->formObject->submitForm('','get');
        $dom = str_get_html($page['data']);
        return $dom;
        }
        
    }
?>