<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Roderick Braun <roderick.braun@ph-freiburg.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 *Query-Class for the 'general_data_display' extension.
 *
 * @author	Roderick Braun <roderick.braun@ph-freiburg.de>
 * @package	TYPO3
 * @subpackage	tx_generaldatadisplay
 */

abstract class tx_generaldatadisplay_pi1_queryList
	{
	# vars
	protected $objArr = array();
	protected $restrictQuery;

	public function __construct()
		{
		$this->restrictQuery = "pid=".PID." AND NOT deleted";
		}

	public function getProperty($property)
		{
		return isset($this->$property) ? $this->$property : null;
		}

	public function setProperty($property,$value)
		{
		$this->$property = $value; 
		return $this->getProperty($property);
		}

	public function delProperty($property)
		{
		if (isset($this->$property)) unset($this->$property);
		} 

	public function addBackTicks($var)
		{
		if (is_array($var)) foreach ($var as $key => $value) $result["`".$key."`"] = $value;
			else $result = "`".$var."`";
		return $result; 
		}

	public function getDS($clause="",$range="")
		{
		$whereClause = $this->restrictQuery.($clause ? " AND ":"").$clause;

		$dataSet=$GLOBALS['TYPO3_DB']->exec_SELECTquery('*',
								$this->table,
								$where=$whereClause,
								$groupBy='',
       								$orderBy=$this->orderField,
        							$limit=$range);

		if ($dataSet) 
			{
			# delete former result	
			foreach ($this->objArr AS $key => $value) unset($this->objArr[$key]); 

			# Content
			while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($dataSet))
				{
				$data = t3lib_div::makeInstance($this->objType);
				$uid = $data->setProperty("uid",$row['uid']);
				$objVars = t3lib_div::makeInstance(PREFIX_ID.'_objVar');
				$data->setProperty("objVars",$objVars->set($row));
				$this->objArr[$uid] = $data;
				}
			}
		return $this->objArr;	
		}

	public function getHash($valueField)
		{
		$hash = array();

		foreach ($this->objArr as $key => $obj)
			$hash[$key] = $obj->getObjVar($valueField);

		return $hash;
		}
	}	

class tx_generaldatadisplay_pi1_dataList extends tx_generaldatadisplay_pi1_queryList
	{
	# vars
	protected $table = "tx_generaldatadisplay_temptable";
	protected $objType = "tx_generaldatadisplay_pi1_data";
	protected $orderField = "category_name,data_title";
	
	public function __construct()
		{
		$this->restrictQuery = "pid=".PID;
		}

	public function getDS($clause="",$range="")
		{
		$this->createTempTable();

		$whereClause = $this->restrictQuery.($clause ? " AND ":"").$clause;

		$dataSet=$GLOBALS['TYPO3_DB']->exec_SELECTquery('*',
								$this->table,
								$where=$whereClause,
								$groupBy='',
								$orderBy=$this->orderField,
								$limit=$range);

		if ($dataSet) 
			{
			# delete former result	
			foreach ($this->objArr AS $key => $value) unset($this->objArr[$key]); 

			# Content
			while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($dataSet))
				{
				$data = t3lib_div::makeInstance($this->objType);
				$uid = $data->setProperty("uid",$row['uid']);
				$objVars = t3lib_div::makeInstance(PREFIX_ID.'_objVar');
				$data->setProperty("objVars",$objVars->set($row));
				$this->objArr[$uid] = $data;
				}
			}

		return $this->objArr;
		}

	private function createTempTable()
		{
		# if temptable is already existing nothing has to be done
		if (tx_generaldatadisplay_pi1_tempdata::tempTableExist()) return true;

		$tempDataClass = PREFIX_ID.'_tempdata';
		$tableColumnHash = self::getColumns();
		foreach($tableColumnHash as $key => $value)
			$fieldArr[] = $this->addBackTicks($key)." ".$value;

		$createFields = implode(",",$fieldArr);

		# create temptable
		$tempData = t3lib_div::makeInstance($tempDataClass);
		$dberror = $tempData->createTable($createFields);

		if (!$dberror)
			{
			 # get all non deleted entrys from page
			$dataList = t3lib_div::makeInstance(PREFIX_ID.'_dataOnlyList');
			$dataObjArr = $dataList->getDS();

			# get categoryList
			$categoryList = t3lib_div::makeInstance(PREFIX_ID.'_categoryList');
			$categoryList->getDS();
			# make category hash
			$categoryHash = $categoryList->getHash('category_name');

			# go and get the data
			foreach ($dataObjArr as $key => $obj)
				{
				# Content	
				# first unset possibly existing datacontent array
				unset($dataContent);
				# get dataContent
				$dataContentSet = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tx_generaldatadisplay_datafields.datafield_name,tx_generaldatadisplay_datacontent.datacontent',
											 'tx_generaldatadisplay_datacontent LEFT JOIN tx_generaldatadisplay_datafields
											  ON tx_generaldatadisplay_datacontent.datafields_uid = tx_generaldatadisplay_datafields.uid',
											 'tx_generaldatadisplay_datacontent.pid='.PID.
											 ' AND tx_generaldatadisplay_datacontent.data_uid='.$obj->getObjVar('uid').
											 ' AND NOT tx_generaldatadisplay_datacontent.deleted AND NOT tx_generaldatadisplay_datafields.deleted'
											 );
				if (! $GLOBALS['TYPO3_DB']->sql_error())
					{
					while ($dataContentRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dataContentSet))
					if ($dataContentRow['datafield_name']) $dataContent[$dataContentRow['datafield_name']] = $dataContentRow['datacontent'];

					# additional fields
					$dataContent['pid'] = PID;
					$dataContent['uid'] = $obj->getObjVar('uid');
					$dataContent['data_title'] = $obj->getObjVar('data_title',true);
					$dataContent['data_category'] = $obj->getObjVar('data_category');
					$dataContent['category_name'] = $categoryHash[$dataContent['data_category']];
					$dataContent = $this->addBackTicks($dataContent);
					# set DS in tempTable
					$objVars = t3lib_div::makeInstance(PREFIX_ID.'_objVar');
					$tempData->setProperty("objVars",$objVars->set($dataContent));

					$tempData->newDS();
					}
				}
			} 
		return $GLOBALS['TYPO3_DB']->sql_error() ? false : true;
		}

	public static function getColumns()
		{
		$tableColumnHash = array('pid' => 'int','uid' => 'int','data_title' => 'tinytext','data_category' => 'int','category_name' => 'tinytext');
		# get list of datafield names
		$dataSet=$GLOBALS['TYPO3_DB']->exec_SELECTquery('datafield_name,datafield_type',
								'tx_generaldatadisplay_datafields',
								'pid='.PID.' AND NOT deleted'
								);

		if ($dataSet) 
			{
			# Content
			while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($dataSet))
				{
				# build column hash from datafields
				$tableColumnHash[$row['datafield_name']] = "text";
				}
			}
		return $tableColumnHash;
		}
	}

class tx_generaldatadisplay_pi1_dataOnlyList extends tx_generaldatadisplay_pi1_queryList
	{
	# vars
	protected $table = "tx_generaldatadisplay_data";
	protected $objType = "tx_generaldatadisplay_pi1_data";
	protected $orderField = "data_title";
	}

class tx_generaldatadisplay_pi1_datacontentList extends tx_generaldatadisplay_pi1_queryList
	{
	# vars
	protected $table = "tx_generaldatadisplay_datacontent";
	protected $objType = "tx_generaldatadisplay_pi1_datacontent";
	protected $orderField = "tx_generaldatadisplay_datafields.display_sequence";

	public function __construct()
		{
		$this->restrictQuery = "pid=".PID." AND NOT tx_generaldatadisplay_datacontent.deleted AND NOT tx_generaldatadisplay_datafields.deleted";
		}

	public function getDS($clause="")
		{
		$table = $this->table;

		$whereClause = $this->restrictQuery.($clause ? " AND ":"").$clause;

		$dataSet = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tx_generaldatadisplay_datafields.datafield_name,tx_generaldatadisplay_datafields.datafield_type,tx_generaldatadisplay_datacontent.uid,tx_generaldatadisplay_datacontent.datacontent,tx_generaldatadisplay_datacontent.datafields_uid',
								'tx_generaldatadisplay_datacontent LEFT JOIN tx_generaldatadisplay_datafields
								ON tx_generaldatadisplay_datacontent.datafields_uid = tx_generaldatadisplay_datafields.uid',
								'tx_generaldatadisplay_datacontent.'.$whereClause,
								'',
       								$this->orderField
								);

		if ($dataSet) 
			{
			# delete former result	
			foreach ($this->objArr AS $key => $value) unset($this->objArr[$key]); 

			# Content
			while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($dataSet))
				{ 
				$data = t3lib_div::makeInstance($this->objType);
				$uid = $data->setProperty("uid",$row['uid']);
				$objVars = t3lib_div::makeInstance(PREFIX_ID.'_objVar');
				$data->setProperty("objVars",$objVars->set($row));
				$this->objArr[$uid] = $data;
				}
			}
		return $this->objArr;	
		}
	}

class tx_generaldatadisplay_pi1_categoryList extends tx_generaldatadisplay_pi1_queryList
	{
	# vars
	protected $table = "tx_generaldatadisplay_categories";
	protected $objType = "tx_generaldatadisplay_pi1_category";
	protected $orderField = "category_name";

	public function getAllProgenitors($dataCategory)
		{
		$allProgenitors = array();

		while($this->objArr[$dataCategory] && ! $checkLoop[$dataCategory])
			{
			$checkLoop[$dataCategory] = 1;
			$dataCategory = $this->objArr[$dataCategory]->getObjVar('category_progenitor');
			if ($dataCategory) $allProgenitors[] = $dataCategory;
			}

		return $allProgenitors;
		}
	}

class tx_generaldatadisplay_pi1_datafieldList extends tx_generaldatadisplay_pi1_queryList
	{
	# vars
	protected $table = "tx_generaldatadisplay_datafields";
	protected $objType = "tx_generaldatadisplay_pi1_datafield";
	protected $orderField = "display_sequence";

	public function getUidFromDatafield($datafieldName)
		{
		foreach($this->objArr as $key => $obj)
			{
			$objVars = $obj->getProperty('objVars');
			if ($objVars->get('datafield_name') == $datafieldName) return $objVars->get('uid');
			}
		return false;
		}
	}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/general_data_display/pi1/class.tx_generaldatadisplay_pi1_queryList.php'])        {
        include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/general_data_display/pi1/class.tx_generaldatadisplay_pi1_queryList.php']);
}

?>
