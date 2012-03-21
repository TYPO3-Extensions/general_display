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
 * formData-Class for the 'general_data_display' extension.
 * provides methods to import/validate form data 
 *
 * @author	Roderick Braun <roderick.braun@ph-freiburg.de>
 * @package	TYPO3
 * @subpackage	tx_generaldatadisplay
 */



abstract class tx_generaldatadisplay_pi1_formData 
	{
	protected $formData;
	protected $checkHash=array();
	protected $formError=array();

	public function __construct()
		{
		$this->formData = t3lib_div::makeInstance(PREFIX_ID.'_objVar');
		}

	public function __destruct()
		{
		unset($this->formData);
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
	
	public function getFormValue($key,$plain=false)
		{
		return $plain ? $this->formData->getplain($key) : $this->formData->get($key);
		}

	public function setFormValue($key,$value)
		{
		return $this->formData->setValue($key,$value);
		}

	public function formError()
		{
		foreach ($this->formError as $key => $hash)
			{
			foreach ($hash as $check => $value)
				if ($value) return 1;
			}
		return 0;
		}

	protected function importValues(tx_generaldatadisplay_pi1_objVar $formData,tx_generaldatadisplay_pi1_objVar $secPiVars=null)
		{
		$piVars = $secPiVars ? $secPiVars->get() : array();
		$data = $formData->getplain();

		foreach ($data as $key => $value)
			$dataArr[$key] = (is_scalar($piVars[$key]) && isset($piVars[$key])) ? $piVars[$key] : $value;
	
		return $this->formData->set($dataArr);
		}

	protected function validateData()
		{
		foreach($this->formData->get() as $key => $value)
			{
			# check value if it's not already checked
			if ($this->checkHash[$key] && !$this->formError[$key])
				$this->formError[$key] = $this->checkValue($this->getFormValue($key,true),$this->checkHash[$key]);
			elseif (!$this->checkHash[$key]) $this->formData->delKey($key);
			}
		}

	static public function checkValue($value,$checkarr)
		{
		if (is_scalar($checkarr)) $checkarr = array($checkarr);

		foreach ($checkarr as $key => $check)
			{
			switch ($check)
				{
				case 'notEmpty': 
				$error[$check] = $value ? 0 : $check;
				break;
		
				case 'isInt':
				$cmpval = $value;
				settype($cmpval,'integer');
				$error[$check]=(strcmp($cmpval,$value)) ? $check : 0;
				break;
	
				case 'isBool':
				$error[$check] = preg_match('/^(0|1|yes|no)$/',$value) ? 0 : $check;
				break;

				case 'isDate':
				preg_match('/^([0-9]{1,2})\D([0-9]{1,2})\D([0-9]{1,4})$/',$value,$matches);
				$error[$check] = checkdate($matches[2],$matches[1],$matches[3]) ? 0 : $check;
				break;

				case 'isTime':
				preg_match('/^([0-9]{1,2}):([0-9]{2})(:([0-9]{2}))?$/',$value,$matches);
				$matches[1] = strlen($matches[1])==1 ? "0".$matches[1] : $matches[1];	
				$matches[1] = $matches[1]=="24" ? "00" : $matches[1];
				$cmpval1 = $matches[1].":".$matches[2].($matches[4] ? ":".$matches[4] : ":00");
				$cmpval2 = date("H:i:s",mktime($matches[1],$matches[2],$matches[4]));
				$error[$check] = strcmp($cmpval1,$cmpval2) ? $check : 0;
				break;	

				case 'isEmail':
				$error[$check] = t3lib_div::validEmail($value) ? 0 : $check; 
				break;

				case 'isURL':
				preg_match('/^(([\w]+:)?\/\/)?(([\d\w]|%[a-fA-f\d]{2,2})+(:([\d\w]|%[a-fA-f\d]{2,2})+)?@)?([\d\w][-\d\w]{0,253}[\d\w]\.)+[\w]{2,4}(:[\d]+)?(\/([-+_~.\d\w]|%[a-fA-f\d]{2,2})*)*(\?(&amp;?([-+_~.\d\w]|%[a-fA-f\d]{2,2})=?)*)?(#([-+_~.\d\w]|%[a-fA-f\d]{2,2})*)?/',$value,$matches);
				$error[$check] = ($matches[0] || !$value) ? 0 : $check;	
				break;	

				case 'isType':
				$types = implode('|',tx_generaldatadisplay_pi1_dataFields::getTypes());
				$error[$check] = preg_match('/^('.$types.')$/',$value) ? 0 : $check;
				break;

				case 'plainColumn':
				$charset = mb_detect_encoding($value) ? mb_detect_encoding($value) : 'UTF-8';
				$error[$check] = preg_match('/^[\wÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÑÒÓÔÕÖØŒÙÚÛÜŸàáâãäåæçèéêëìíîïñòóôõöøœùúûüÿ\ _\-\(\)\:\?\=\%§!@\+|\d]*$/u',iconv($charset,'UTF-8',$value)) ? 0 : $check;
				break;

				case 'existing':
				$error[$check] = 0;
				break;

				# all others are treated as regular expressions defined like array('regexname' => '[regex]')
				default:
				if (is_array($check)) $error[key($check)] = preg_match($check[key($check)],$value) ? 0 : key($check);
				break;
				}
			# if notEmpty is not set empty values are ok
			if (!$value && $check != 'notEmpty') $error[$check] = 0;
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
			if ($_FILES[PREFIX_ID]['error'][$key]['select']) $this->formError[$key] = array('imgUpload');
			if ($_FILES[PREFIX_ID]['size'][$key]['select'] > MAXIMGSIZE) $this->formError[$key] = array('imgFilesize');  
			if (!preg_match('/^image\//',$_FILES[PREFIX_ID]['type'][$key]['select'])) $this->formError[$key] = array('imgType');

			return false;
			}
		}

	}


class tx_generaldatadisplay_pi1_dataForm extends tx_generaldatadisplay_pi1_formData
	{
	# vars
	protected $type='data';

	public function importValues(tx_generaldatadisplay_pi1_objVar $formData,tx_generaldatadisplay_pi1_objVar $secPiVars=null)
		{
		# first set $this->formData with formData
		$this->formData = parent::importValues($formData,$secPiVars);

		$this->checkHash['uid'] = 'isInt';
		$this->checkHash['data_title'] = 'notEmpty';
		$this->checkHash['data_category'] = 'isInt';

		# get list of datafield names
		$typeList = t3lib_div::makeInstance(PREFIX_ID.'_datafieldList');
		$objArr = $typeList->getDS();

		foreach($objArr as $key => $obj) 
			{
			# first check required flag
			$metadata = tx_generaldatadisplay_pi1_dataFields::getMetadata($obj->getObjVar('uid'));

			if ($metadata['datafield_required'] == "yes")
				$checkMethod[] = 'notEmpty';

			# now check all datafields by type
			if (!$this->formError[$obj->getObjVar('datafield_name')])  
				{
				switch ($obj->getObjVar('datafield_type'))
					{
					case 'int':
					$checkMethod[] = 'isInt';
					break;

					case 'bool':
					$checkMethod[] = 'isBool';

					break;

					case 'date':
					$checkMethod[] = 'isDate';
					break;

					case 'time':
					$checkMethod[] = 'isTime';
					break;

					case 'email':
					$checkMethod[] = 'isEmail';
					break;

					case 'url':
					$checkMethod[] = 'isURL';
					break;

					default:
					$checkMethod[] = 'existing';
					}
				}
			$this->checkHash[$obj->getObjVar('datafield_name')] = $checkMethod;
			unset($checkMethod);
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
						# if ($this->formData[$key]) unlink(IMGUPLOADPATH."/".$this->formData[$key]);
						$this->setFormValue($key,$newFilename);
						} else $this->formError[$key] = "imgUpload";
					}
				else 
					{
					$imgvalue = $secPiVars ? $secPiVars->getplain($key) : null;
					if (is_array($imgvalue) && isset($imgvalue['delete'])) $this->formData->delKey($key);
					}
				}
			}
		# validate and save formData
		$this->validateData();
		
		return $this->formData;
		}
	}

class tx_generaldatadisplay_pi1_categoryForm extends tx_generaldatadisplay_pi1_formData
	{
	# vars
	protected $type='category';

	public function importValues(tx_generaldatadisplay_pi1_objVar $formData,tx_generaldatadisplay_pi1_objVar $secPiVars=null)
		{
		$this->formData = parent::importValues($formData,$piVars);

		$this->checkHash['uid'] = 'isInt';
		$this->checkHash['category_name'] = 'notEmpty';
		$this->checkHash['category_progenitor'] = 'isInt';
		
		# validate and save formData
		$this->validateData();

		return $this->formData;
		}
	}

class tx_generaldatadisplay_pi1_datafieldForm extends tx_generaldatadisplay_pi1_formData
	{
	# vars
	protected $type='datafield';

	public function importValues(tx_generaldatadisplay_pi1_objVar $formData,tx_generaldatadisplay_pi1_objVar $secPiVars=null)
		{
		$this->formData = parent::importValues($formData,$secPiVars);

		if ($datafieldName = $this->getFormValue('datafield_name',true))
			{
			# restrict length to max 64 chars
			$this->setFormValue('datafield_name',substr($datafieldName,0,63));

			# now check if datafieldname is unique
			$tableColumnHash = tx_generaldatadisplay_pi1_dataList::getColumns();

			$charEncoding = mb_detect_encoding($this->getFormValue('datafield_name'));
			foreach(array_keys($tableColumnHash) as $key) 
				{
				$key = mb_strtolower($key,$charEncoding);
				$dataFieldName = mb_strtolower($this->getFormValue('datafield_name'),$charEncoding);
				$datafieldUid = $this->valueExist('datafield_name',$this->getFormValue('datafield_name'));
				
				if ($key == $dataFieldName && (!$datafieldUid || $datafieldUid != $this->getFormValue('uid')))
					$this->formError['datafield_name'][] = 'isUnique';
				}
			}

		$this->checkHash['uid'] = 'isInt';
		$this->checkHash['datafield_name'] = array('notEmpty','plainColumn');
		$this->checkHash['datafield_type'] = 'isType';
		$this->checkHash['display_sequence'] = 'isInt';

		$datafieldType = $this->getFormValue('datafield_type') ? $this->getFormValue('datafield_type') : "tinytext";
		$dataField = t3lib_div::makeInstance(PREFIX_ID.'_'.$datafieldType);

		$metadata = $this->getMetaData();
		$dataField->cleanMetadata($metadata);

		# validate and save formData
		$this->validateData();

		# serialize metadata
		$this->setMetadata($metadata);

		return $this->formData;
		}

	public function getMetadata($key='')
		{
		$meta = $this->getFormValue('meta',true) ? 
			$this->getFormValue('meta',true) : unserialize($this->formData->getplain('metadata'));

		if (!$meta) $meta = array();

		return $key ? $meta[$key] : $meta;
		}

	public function setMetadata($metadata)
		{
		$this->formData->setValue('metadata',serialize($metadata));
		return $this->getMetaData();
		}
	}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/general_data_display/pi1/class.tx_generaldatadisplay_pi1_formData.php'])        {
        include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/general_data_display/pi1/class.tx_generaldatadisplay_pi1_formData.php']);
}

?>
