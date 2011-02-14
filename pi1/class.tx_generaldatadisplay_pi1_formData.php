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
 *formData-Class for the 'general_data_display' extension.
 *
 * @author	Roderick Braun <roderick.braun@ph-freiburg.de>
 * @package	TYPO3
 * @subpackage	tx_generaldatadisplay
 */

abstract class tx_generaldatadisplay_pi1_formData 
	{
	protected $dataArr = array();
	protected $checkHash = array();
	protected $formError = array();

	public function getProperty($property)
		{
		return isset($this->$property) ? $this->$property : null;
		}

	public function setProperty($property,$value)
		{
		$this->$property = $value; 
		return $this->getProperty($property);
		}
	
	public function formError()
		{
		foreach ($this->formError as $key => $value)
			{
			if ($value) return 1;
			}
		return 0;
		}

	protected function importValues($formData,$piVars=array())
		{
		foreach ($formData as $key => $value)
			$dataArr[$key] = (is_scalar($piVars[$key]) && isset($piVars[$key])) ? $piVars[$key] : $value;

		return $dataArr;
		}

	protected function validateData()
		{
		foreach($this->dataArr as $key => $value)
			{
			# check value if it's not already checked
			if ($this->checkHash[$key] && !$this->formError[$key])
				$this->formError[$key] = $this->checkValue($this->dataArr[$key],$this->checkHash[$key]);
			elseif (!$this->checkHash[$key]) unset($this->dataArr[$key]);
			}
		}

	protected function checkValue($value,$check)
		{
		# nonexisting value is ok
		if ($check != 'notEmpty' && !$value) return 0;

		switch ($check)
			{
			case 'notEmpty':
				$error=$value ? 0 : $check;
			break;
		
			case 'isInt':
				$cmpval = $value;
				settype($cmpval,'integer');
				$error=(strcmp($cmpval,$value)) ? $check : 0;
			break;
	
			case 'isBool':
				$error=preg_match('/^(0|1|yes|no)$/',$value) ? 0 : $check;
			break;

			case 'isDate':
				preg_match('/^([0-9]{1,2})\D([0-9]{1,2})\D([0-9]{1,4})$/',$value,$matches);
				$error = checkdate($matches[2],$matches[1],$matches[3]) ? 0 : $check;
			break;

			case 'isTime':
				preg_match('/^([0-9]{1,2}):([0-9]{2})(:([0-9]{2}))?$/',$value,$matches);
				$matches[1] = strlen($matches[1])==1 ? "0".$matches[1] : $matches[1];	
				$matches[1] = $matches[1]=="24" ? "00" : $matches[1];
				$cmpval1 = $matches[1].":".$matches[2].($matches[4] ? ":".$matches[4] : ":00");
				$cmpval2 = date("H:i:s",mktime($matches[1],$matches[2],$matches[4]));
				$error=strcmp($cmpval1,$cmpval2) ? $check : 0;
			break;	

			case 'isEmail':
				$error=t3lib_div::validEmail($value) ? 0 : $check; 
			break;

			case 'isURL':
				preg_match('/^(([\w]+:)?\/\/)?(([\d\w]|%[a-fA-f\d]{2,2})+(:([\d\w]|%[a-fA-f\d]{2,2})+)?@)?([\d\w][-\d\w]{0,253}[\d\w]\.)+[\w]{2,4}(:[\d]+)?(\/([-+_~.\d\w]|%[a-fA-f\d]{2,2})*)*(\?(&amp;?([-+_~.\d\w]|%[a-fA-f\d]{2,2})=?)*)?(#([-+_~.\d\w]|%[a-fA-f\d]{2,2})*)?/',$value,$matches);
				$error=($matches[0] || !$value) ? 0 : $check;	
				break;	

			case 'isType':
				$types = implode('|',tx_generaldatadisplay_pi1_dataFields::getTypes());
				$error=preg_match('/^('.$types.')$/',$value) ? 0 : $check;
			break;

			case 'existing':
				$error = 0;
			break;
			}
		return $error;
		}
	
	public function valueExist($field, $value)
		{
		# get data
		$typeList = t3lib_div::makeInstance(PREFIX_ID.'_'.$this->type.'List');
		$objArr = $typeList->getDS();
		foreach($objArr as $key => $obj)
			if ($obj->getObjVar($field) == $value) return $obj->getObjVar('uid');

		return 0;
		}

	public function validImg($key)
		{
		if (!$_FILES[PREFIX_ID]['tmp_name'][$key]['select']) return false;
		if (!$_FILES[PREFIX_ID]['error'][$key]['select']
			&& $_FILES[PREFIX_ID]['size'][$key]['select'] < MAXIMGSIZE 
			&& preg_match('/^image\//',$_FILES[PREFIX_ID]['type'][$key]['select'])) return $_FILES[PREFIX_ID]['name'][$key]['select'];

		else
			{
			if ($_FILES[PREFIX_ID]['error'][$key]['select']) $this->formError[$key] = "imgUpload";
			if ($_FILES[PREFIX_ID]['size'][$key]['select'] > MAXIMGSIZE) $this->formError[$key] = "imgFilesize";  
			if (!preg_match('/^image\//',$_FILES[PREFIX_ID]['type'][$key]['select'])) $this->formError[$key] = "imgType";

			return false;
			}
		}

	}


class tx_generaldatadisplay_pi1_dataForm extends tx_generaldatadisplay_pi1_formData
	{
	# vars
	protected $type='data';

	public function importValues($formData,$piVars=array())
		{
		# first set $this->dataArr with formData
		$this->dataArr = parent::importValues($formData,$piVars);

		$this->checkHash['uid'] = 'isInt';
		$this->checkHash['data_title'] = 'notEmpty';
		$this->checkHash['data_category'] = 'isInt';

		# get list of datafield names
		$typeList = t3lib_div::makeInstance(PREFIX_ID.'_datafieldList');
		$typeList->getDS();
		$objArr = $typeList->getProperty('objArr');

		foreach($objArr as $key => $obj) 
			{
			# first check required flag
			$metadata = tx_generaldatadisplay_pi1_dataFields::getMetadata($obj->getObjVar('uid'));

			if ($metadata['datafield_required'] == "yes")
				$this->formError[$obj->getObjVar('datafield_name')] = $this->checkValue($this->dataArr[$obj->getObjVar('datafield_name')],'notEmpty');

			# now check all datafields by type
			if (!$this->formError[$obj->getObjVar('datafield_name')])  
				{
				switch ($obj->getObjVar('datafield_type'))
					{
					case 'int':
					$checkMethod = 'isInt';
					break;

					case 'bool':
					$checkMethod = 'isBool';# preg_match('/^(.+)SELECT$/',$key,$postVarMatch);
					break;

					case 'date':
					$checkMethod = 'isDate';
					break;

					case 'time':
					$checkMethod = 'isTime';
					break;

					case 'email':
					$checkMethod = 'isEmail';
					break;

					case 'url':
					$checkMethod = 'isURL';
					break;

					default:
					$checkMethod = 'existing';
					break;
					}
				}
			$this->checkHash[$obj->getObjVar('datafield_name')] = $checkMethod;
			}

		# if img datafields existing
		if ($_FILES[PREFIX_ID]['tmp_name'])
			{
			# create imguploaddir if necessary
			if (!is_dir(IMGUPLOADPATH)) mkdir(IMGUPLOADPATH, 0755, true);

			foreach ($_FILES[PREFIX_ID]['tmp_name'] as $key => $value)
				{
				if ($filename = $this->validImg($key)) 
					{
					# get unique filename
					$i=0;
					preg_match('/^(.+)\.([^\.]+)$/',$filename,$fileNamePart);
					$newFilename = $filename;
					while(is_file(IMGUPLOADPATH."/".$newFilename)) $newFilename = $fileNamePart[1].$i++.".".$fileNamePart[2];
					$succMove = move_uploaded_file($_FILES[PREFIX_ID]['tmp_name'][$key]['select'],IMGUPLOADPATH."/".$newFilename);
					if ($succMove)
						{
						# check if value was already set
						# if ($this->dataArr[$key]) unlink(IMGUPLOADPATH."/".$this->dataArr[$key]);
						$this->dataArr[$key] = $newFilename;
						} else $this->formError[$key] = "imgUpload";
					} elseif (is_array($piVars[$key]) && isset($piVars[$key]['delete'])) $this->dataArr[$key] = "";
				
				}
			}
		# validate and save formData
		$this->validateData();
		
		return $this->dataArr;
		}
	}

class tx_generaldatadisplay_pi1_categoryForm extends tx_generaldatadisplay_pi1_formData
	{
	# vars
	protected $type='category';

	public function importValues($formData,$piVars=array())
		{
		$this->dataArr = parent::importValues($formData,$piVars);

		$this->checkHash['uid'] = 'isInt';
		$this->checkHash['category_name'] = 'notEmpty';
		$this->checkHash['category_progenitor'] = 'isInt';
		
		# validate and save formData
		$this->validateData();

		return $this->dataArr;
		}
	}

class tx_generaldatadisplay_pi1_datafieldForm extends tx_generaldatadisplay_pi1_formData
	{
	# vars
	protected $type='datafield';

	public function importValues($formData,$piVars=array())
		{
		$this->dataArr = parent::importValues($formData,$piVars);

		if ($this->dataArr['datafield_name'])
			{
			# convert / remove some special chars
			$searchArr = array("/","\\","\"","'","`","<",">","+");
			$replaceArr = array("|","|");
			$this->dataArr['datafield_name'] = str_replace($searchArr,$replaceArr,$this->dataArr['datafield_name']);

			# now check if datafieldname is unique
			$tableColumnHash = tx_generaldatadisplay_pi1_dataList::getColumns();

			$charEncoding = mb_detect_encoding($this->dataArr['datafield_name']);
			foreach(array_keys($tableColumnHash) as $key) 
				{
				$key = mb_strtolower($key,$charEncoding);
				$dataFieldName = mb_strtolower($this->dataArr['datafield_name'],$charEncoding);
				$datafieldUid = $this->valueExist('datafield_name',$this->dataArr['datafield_name']);
				
				if ($key == $dataFieldName && (!$datafieldUid || $datafieldUid != $this->dataArr['uid']))
					$this->formError['datafield_name'] = 'isUnique';
				}
			}

		$this->checkHash['uid'] = 'isInt';
		$this->checkHash['datafield_name'] = 'notEmpty';
		$this->checkHash['datafield_type'] = 'isType';
		$this->checkHash['display_sequence'] = 'isInt';

		$this->dataArr['meta'] = unserialize($formData['metadata']);
		$datafieldType = $this->dataArr['datafield_type'] ? $this->dataArr['datafield_type'] : "tinytext";
		$dataField = t3lib_div::makeInstance(PREFIX_ID.'_'.$datafieldType);
		$dataField->checkMetadata($this->dataArr['meta']);

		$this->checkHash['meta'] = 'existing';

		# validate and save formData
		$this->validateData();
		
		# serialize metadata
		$this->dataArr['metadata'] = serialize($formData['meta']);

		return $this->dataArr;
		}
	}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/general_data_display/pi1/class.tx_generaldatadisplay_pi1_formData.php'])        {
        include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/general_data_display/pi1/class.tx_generaldatadisplay_pi1_formData.php']);
}

?>
