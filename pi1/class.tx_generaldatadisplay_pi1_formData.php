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
	protected $prefixId = 'tx_generaldatadisplay_pi1';
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

	protected function validateData()
		{
		foreach($this->dataArr as $key => $value)
			{
			# the safe way			
			$this->dataArr[$key] = htmlspecialchars($this->dataArr[$key]);

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
				$error=preg_match('/^(tinytext|text|int|bool|date|time|email|url|img)$/',$value) ? 0 : $check;
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
		$typeList = t3lib_div::makeInstance($this->prefixId.'_'.$this->type.'List');
		$objArr = $typeList->getDS();
		foreach($objArr as $key => $obj)

			{
			# get objVars
			$objVars = $obj->getProperty('objVars');
			if ($objVars[$field] == $value) return $objVars['uid'];
			}
		return 0;
		}

	public function validImg($key)
		{
		if (!$_FILES[$this->prefixId]['tmp_name'][$key]) return false;
		if (!$_FILES[$this->prefixId]['error'][$key]

			&& $_FILES[$this->prefixId]['size'][$key] < MAXIMGSIZE 
			&& preg_match('/^image\//',$_FILES[$this->prefixId]['type'][$key])) return $_FILES[$this->prefixId]['name'][$key];

		else
			{
			if ($_FILES[$this->prefixId]['error'][$key]) $this->formError[$key] = "imgUpload";
			if ($_FILES[$this->prefixId]['size'][$key] > MAXIMGSIZE) $this->formError[$key] = "imgFilesize";  
			if (!preg_match('/^image\//',$_FILES[$this->prefixId]['type'][$key])) $this->formError[$key] = "imgType";

			return false;
			}
		}

	}


class tx_generaldatadisplay_pi1_dataForm extends tx_generaldatadisplay_pi1_formData
	{
	# vars
	protected $type='data';

	public function importValues($formData)
		{
		# first set $this->dataArr with formData
		$this->dataArr = $formData;

		$this->checkHash['uid'] = 'isInt';
		$this->checkHash['data_title'] = 'notEmpty';
		$this->checkHash['data_category'] = 'isInt';

		# get list of datafield names
		$typeList = t3lib_div::makeInstance($this->prefixId.'_datafieldList');
		$typeList->getDS();
		$objArr = $typeList->getProperty('objArr');

		foreach($objArr as $key => $obj) 
			{
			$objVars = $obj->getProperty('objVars');

			# build datafield_name hash
			$datafieldHash[$key]=$objVars['datafield_name'];

			# build required hash
			$requiredHash[$key]=$objVars['datafield_required'];
			
			# check all datafields
			switch ($objVars['datafield_type'])
				{
				case 'int':
				$checkMethod = 'isInt';
				break;

				case 'bool':
				$checkMethod = 'isBool';
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
			$this->checkHash[$datafieldHash[$key]] = $checkMethod;
			}

		# unserialize data_field_content to fill formfields
		$dataContent = unserialize(base64_decode($formData['data_field_content']));
		
		# save content in dataArr
		if ($dataContent) foreach($dataContent as $key => $value)
			{ 
			$this->dataArr[$datafieldHash[$key]] = $value;
			}

		# if img is uploaded
		if ($_FILES[$this->prefixId]['tmp_name'])
			{
			# create imguploaddir if necessary
			if (!is_dir(IMGUPLOADPATH)) mkdir(IMGUPLOADPATH, 0750, true);

			foreach ($_FILES[$this->prefixId]['tmp_name'] as $key => $value)
				{
				preg_match('/^(.+)SELECT$/',$key,$postVarMatch);

				if ($filename = $this->validImg($key)) 
					{
					# get unique filename
					$i=0;
					preg_match('/^(.+)\.([^\.]+)$/',$filename,$fileNamePart);
					$newFilename = $filename;
					while(is_file(IMGUPLOADPATH."/".$newFilename)) $newFilename = $fileNamePart[1].$i++.".".$fileNamePart[2];
					$succMove = move_uploaded_file($_FILES[$this->prefixId]['tmp_name'][$key],IMGUPLOADPATH."/".$newFilename);
					if ($succMove)
						{
						# check if value was already set
						if ($this->dataArr[$postVarMatch[1]]) unlink(IMGUPLOADPATH."/".$this->dataArr[$postVarMatch[1]]);
						$this->dataArr[$postVarMatch[1]] = $newFilename;
						}
					else $this->formError[$postVarMatch[1]] = "imgMove";
					}
				}
			}

		# validate and save formData
		$this->validateData();

		# now serialize data fields and save it in $this->dataArr['data_field_content']
		foreach($objArr as $key => $obj) 
			{
			$objVars = $obj->getProperty('objVars');
			$dataContent[$key] = $this->dataArr[$objVars['datafield_name']];
			# check required fields
			if (!$this->formError[$datafieldHash[$key]] && $requiredHash[$key]=='yes') $this->formError[$datafieldHash[$key]] = $this->checkValue($dataContent[$key],'notEmpty');
			}
		$this->dataArr['data_field_content'] = base64_encode(serialize($dataContent)); 

		return $this->dataArr;
		}
	}

class tx_generaldatadisplay_pi1_categoryForm extends tx_generaldatadisplay_pi1_formData
	{
	# vars
	protected $type='category';

	public function importValues($formData)
		{
		$this->dataArr = $formData;

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

	public function importValues($formData)
		{
		$this->dataArr = $formData;

		$this->checkHash['uid'] = 'isInt';
		$this->checkHash['datafield_name'] = 'notEmpty';
		$this->formError['datafield_name'] = $this->checkValue($this->dataArr['datafield_name'],$this->checkHash['datafield_name']);
	
		# convert backticks to ticks
		if (! $this->formError['datafield_name']) 
			{
			$this->dataArr['datafield_name'] = str_replace("`","'",$this->dataArr['datafield_name']);
			$this->dataArr['datafield_name'] = str_replace("/","|",$this->dataArr['datafield_name']);
			}

		# now check if datafieldname is unique
		$uid = $this->valueExist('datafield_name',$this->dataArr['datafield_name']);
		if ((!$this->dataArr['uid'] && $uid) || ($this->dataArr['uid'] && $uid && $this->dataArr['uid'] != $uid ))
			$this->formError['datafield_name'] = 'isUnique';

		$this->checkHash['datafield_type'] = 'isType';
		if (!$this->dataArr['datafield_required']) $this->dataArr['datafield_required']='no'; 
		$this->checkHash['datafield_required'] = 'isBool';
		if (!$this->dataArr['datafield_searchable']) $this->dataArr['datafield_searchable']='no'; 
		$this->checkHash['datafield_searchable'] = 'isBool';
		if (!$this->dataArr['content_visible']) $this->dataArr['content_visible']='no'; 
		$this->checkHash['content_visible'] = 'isBool';
		$this->checkHash['display_sequence'] = 'isInt';		

		# validate and save formData
		$this->validateData();

		return $this->dataArr;
		}
	}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/general_data_display/pi1/class.tx_generaldatadisplay_pi1_formData.php'])        {
        include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/general_data_display/pi1/class.tx_generaldatadisplay_pi1_formData.php']);
}

?>
