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
	private $objArr = array();

	public function __construct()
		{
		$this->restrictFields="pid=".PID;
		}
	
	public function __destruct()
		{
		foreach ($this->objArr AS $key => $value) unset($value);
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

	public function getDS($clause="",$tempTable="",$range="")
		{
		$table = $tempTable ? $tempTable : $this->table;
		$whereClause = $this->restrictFields.($this->restrictFields && $clause ? " AND ":"").$clause;

		$dataSet=$GLOBALS['TYPO3_DB']->exec_SELECTquery('*',
								$table,
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
				$data->setProperty("objVars",$row);
				$this->objArr[$uid] = $data;
				}
			}
		return $this->objArr;	
		}
	}	

class tx_generaldatadisplay_pi1_dataList extends tx_generaldatadisplay_pi1_queryList
	{
	# vars
	protected $table = "tx_generaldatadisplay_data";
	protected $objType = "tx_generaldatadisplay_pi1_data";
	protected $orderField = "data_title";

	public function createTempTable()
		{
		$this->tempTable = $this->table."_".PID;
		
		$fieldArr[] = "pid int,uid int,data_title tinytext,data_category int,category_name tinytext";	

		# build create query
		# get list of datafield names
		$dataSet=$GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,datafield_name,datafield_type',
								'tx_generaldatadisplay_datafields',
								$this->restrictFields
								);

		if ($dataSet) 
			{
			# Content
			while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($dataSet))
				{
				# build datafield hash to map names
				$datafieldHash[$row['uid']] = addslashes($row['datafield_name']);
				$fieldArr[] = $this->addBackTicks($datafieldHash[$row['uid']])." text";
				}
			}	
		$createFields = implode(",",$fieldArr);

		$query = "CREATE TEMPORARY TABLE ".$this->tempTable." (".$createFields.")";
		$GLOBALS['TYPO3_DB']->sql_query($query);

		if (! $GLOBALS['TYPO3_DB']->sql_error())
			{
			$dataSet=$GLOBALS['TYPO3_DB']->exec_SELECTquery('tx_generaldatadisplay_data.*,tx_generaldatadisplay_categories.category_name',
									'tx_generaldatadisplay_data LEFT JOIN tx_generaldatadisplay_categories 
									 ON  tx_generaldatadisplay_data.pid = tx_generaldatadisplay_categories.pid
									 AND tx_generaldatadisplay_data.data_category = tx_generaldatadisplay_categories.uid',
									'tx_generaldatadisplay_data.pid='.PID
									);
			
			if ($dataSet) 
				{
				# Content
				while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($dataSet))
					{
					# first unset possibly existing datacontent array
					unset($dataContent);
					# get unmapped data
					$unmappedDataContent = unserialize(base64_decode($row['data_field_content']));
					if ($unmappedDataContent)
						{
						# only defined fields -> map data
						foreach ($unmappedDataContent as $key => $value)
							{
							if ($datafieldHash[$key])
								$dataContent[$datafieldHash[$key]] = $value;
							}
						}
					# additional fields
					$dataContent['pid'] = PID;
					$dataContent['uid'] = $row['uid'];
					$dataContent['data_title'] = $row['data_title'];
					$dataContent['data_category'] = $row['data_category'];
					$dataContent['category_name'] = $row['category_name'];
					$dataContent = $this->addBackTicks($dataContent);
	
					$GLOBALS['TYPO3_DB']->exec_INSERTquery($this->tempTable,$dataContent);
					}
				}
			} 
		return $GLOBALS['TYPO3_DB']->sql_error();
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

		$objArr = $this->getProperty('objArr');
		while($objArr[$dataCategory] && ! $checkLoop[$dataCategory])
			{
			$checkLoop[$dataCategory] = 1;
			$dataCategory = $objArr[$dataCategory]->getObjVar('category_progenitor');
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
	}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/general_data_display/pi1/class.tx_generaldatadisplay_pi1_queryList.php'])        {
        include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/general_data_display/pi1/class.tx_generaldatadisplay_pi1_queryList.php']);
}

?>
