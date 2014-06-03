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
*  the Free Software Foundation; either version 2 of the License,  or
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
 * dataFields-Class for the 'general_data_display' extension.
 * provides helper methods for the different datafields 
 *
 * @author	Roderick Braun <roderick.braun@ph-freiburg.de>
 * @package	TYPO3
 * @subpackage	tx_generaldatadisplay
 */

abstract class tx_generaldatadisplay_pi1_dataFields extends tslib_pibase
	{
	public $scriptRelPath = 'pi1/class.tx_generaldatadisplay_pi1_dataFields.php';
	public $extKey        = 'general_data_display';

	protected static $table = "tx_generaldatadisplay_datafields";
	protected static $fieldTypeHash  = array();
	protected static $metaDataHash = array();
	protected $tmplArr = array();
	protected $config = array();

	public function __construct() 
		{
		parent::__construct();
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');
		$this->pi_loadLL();
		}

	public function getProperty($property)
		{
		return isset($this->$property) ? $this->$property : NULL;
		}

	public function setTmplArr(array &$tmplArr)
		{
		$this->tmplArr = $tmplArr;
		}

	public function getTmplVar($property)
		{
		return isset($this->tmplArr[$property]) ? $this->tmplArr[$property] : NULL;
		}

	public function setTmplVar($property, $value)
		{
		$this->tmplArr[$property] = $value;
		return isset($this->tmplArr[$property]) ? $this->tmplArr[$property] : NULL;
		}

	public function HTML($type='edit')
		{ 
		if (isset($this->config['subpartType'][$type]))
			{
			$subpart = $this->cObj->getSubpart(TEMPLATE, $this->config['subpartType'][$type]);
			return $this->cObj->substituteMarkerArrayCached($subpart, $this->tmplArr);
			}
		else return;
		}

	public static function getMetadata($uid)
		{
		if (empty(self::$metaDataHash)) 
			{
			// get metadata from datafield table
			$datafieldList = t3lib_div::makeInstance(PREFIX_ID.'_datafieldList');
			$datafieldList->getDS();
			self::$metaDataHash = $datafieldList->getHash('metadata', 'uid', TRUE);
			}

		if (self::$metaDataHash[$uid])
			$tmplArr = tx_generaldatadisplay_pi1_objVar::specialchars(unserialize(self::$metaDataHash[$uid]));

		return $tmplArr ? $tmplArr : array();
		}

	public static function getFieldType($key)
		{
		if (empty(self::$fieldTypeHash))
			{
			$datafieldList = t3lib_div::makeInstance(PREFIX_ID.'_datafieldList');
			$datafieldList->getDS();
			self::$fieldTypeHash = $datafieldList->getHash('datafield_type', 'datafield_name', TRUE);
			}
		
		return array_key_exists($key,self::$fieldTypeHash) ? self::$fieldTypeHash[$key] : NULL;
		}

	public static function getTypes()
		{
		t3lib_div::loadTCA(self::$table);
		$items = $GLOBALS['TCA'][self::$table]['columns']['datafield_type']['config']['items'];
		foreach ($items as $item => $arr) $typeArr[$arr[1]] = $arr[1];

		return $typeArr;
		}

	public function cleanMetadata(array &$metadata)
		{
		switch ($this->type)
			{
			case 'img':
				$keyArr = array('img_size_x' => 's', 'img_size_y' => 'int', 'img_align' => 'string');

				if ($metadata['img_size_x']) $metadata['img_size_x'] = (int)$metadata['img_size_x'];
				if ($metadata['img_size_y']) $metadata['img_size_y'] = (int)$metadata['img_size_y'];
				if (!preg_match('/^(left|right|center)$/', $metadata['img_align'])) unset($metadata['img_align']);
			break;

			case 'date':
				$keyArr = array('datafield_searchable' => 'bool', 'datafield_required' => 'bool', 'content_visible' => 'bool', 'date_defaultvalue' => 'bool');
			break;

			case 'time':
				$keyArr = array('datafield_searchable' => 'bool', 'datafield_required' => 'bool', 'content_visible' => 'bool', 'time_defaultvalue' => 'bool');
			break;

			case 'currency':
				$keyArr = array('datafield_searchable' => 'bool', 'datafield_required' => 'bool', 'content_visible' => 'bool', 'default_value_prefix' => 'int', 'default_value_suffix' => 'int');
			break;

			default:
				$keyArr = array('datafield_searchable' => 'bool', 'datafield_required' => 'bool', 'content_visible' => 'bool', 'default_value' => 'string');
			break;
			}
		// check types and set defaults
		foreach ($keyArr as $key => $value)
			{
			switch ($value)
				{
				case 'bool':
					(!isset($metadata[$key]) || $metadata[$key] == "no") ? $metadata[$key]="no" : $metadata[$key]="yes";
				break;
				default: 
					if ($metadata[$key]) settype($metadata[$key], $value);	
				}
			}

		// clear all non defined metadata
		foreach ($metadata as $key => $value) 
			if (!$keyArr[$key]) unset($metadata[$key]);
		}

	}

class tx_generaldatadisplay_pi1_tinytext extends tx_generaldatadisplay_pi1_dataFields
	{
	// vars
	protected $type = "tinytext";
	protected $config = array('subpartType' => array('edit' => '###TINYTEXT_INPUT###',  'config' => '###METADATA_INPUT###'));
	}

class tx_generaldatadisplay_pi1_text extends tx_generaldatadisplay_pi1_dataFields
	{
	// vars
	protected $type = "text";
	protected $config = array('subpartType' => array('edit' => '###TEXTAREA_INPUT###',  'config' => '###METADATA_TEXT###'));

	public function HTML($type='edit')
		{
		$tooltip = $this->pi_linkTP_keepPIvars('
			<img src="'.PICTURE_PATH.'tooltip.png" alt="tooltip" />
			<span class="tooltip-info">'.$this->pi_getLL('HTMLsubstitutionTip').'</span>
		', array(), 1, 0);

		$this->tmplArr['###TOOLTIP###'] = $this->cObj->addParams($tooltip, array('class' => 'tooltip'));

		return parent::HTML($type);
		}
	}

class tx_generaldatadisplay_pi1_img extends tx_generaldatadisplay_pi1_dataFields
	{
	// vars
	protected $type = "img";
	protected $config = array('subpartType' => array('edit' => '###IMAGE_INPUT###',  'config' => '###METADATA_IMAGE###'), 
				  'imgAlign' => array('left' => 'left', 'center' => 'center', 'right' => 'right')
				 );
	}

class tx_generaldatadisplay_pi1_int extends tx_generaldatadisplay_pi1_dataFields
	{
	// vars
	protected $type = "int";
	protected $config = array('subpartType' => array('edit' => '###INT_INPUT###',  'config' => '###METADATA_INPUT###'));
	}

class tx_generaldatadisplay_pi1_bool extends tx_generaldatadisplay_pi1_dataFields
	{
	// vars
	protected $type = "bool";
	protected $config = array('subpartType' => array('edit' => '###BOOL_INPUT###',  'config' => '###METADATA_BOOL###'));

	public function HTML($type='edit')
		{
		$this->tmplArr['###VALUE_DATAFIELD_NO###'] = 'no';
		$this->tmplArr['###VALUE_DATAFIELD_YES###'] = 'yes';
		if ($type == 'edit')
			{
			$this->tmplArr['###DATAFIELD_SELECTED_YES###'] = $this->tmplArr['###DATAFIELD_CONTENT###']=='yes' ? 'selected="selected"' : '';
			$this->tmplArr['###DATAFIELD_SELECTED_NO###'] = $this->tmplArr['###DATAFIELD_CONTENT###']=='no' ? 'selected="selected"' : '';
			}
			
		return parent::HTML($type);
		}
	}

class tx_generaldatadisplay_pi1_currency extends tx_generaldatadisplay_pi1_dataFields
	{
	// vars
	protected $type = "currency";
	protected $config = array('subpartType' => array('edit' => '###CURRENCY_INPUT###',  'config' => '###METADATA_CURRENCY###'));
	}

class tx_generaldatadisplay_pi1_date extends tx_generaldatadisplay_pi1_dataFields
	{
	// vars
	protected $type = "date";
	protected $config = array('subpartType' => array('edit' => '###DATE_INPUT###',  'config' => '###METADATA_DATE###', 'default' => '###DATE_INPUTFIELD###'));
	}

class tx_generaldatadisplay_pi1_time extends tx_generaldatadisplay_pi1_dataFields
	{
	// vars
	protected $type = "time";
	protected $config = array('subpartType' => array('edit' => '###TIME_INPUT###',  'config' => '###METADATA_TIME###'));
	}

class tx_generaldatadisplay_pi1_email extends tx_generaldatadisplay_pi1_dataFields
	{
	// vars
	protected $type = "email";
	protected $config = array('subpartType' => array('edit' => '###EMAIL_INPUT###',  'config' => '###METADATA_INPUT###'));
	}

class tx_generaldatadisplay_pi1_url extends tx_generaldatadisplay_pi1_dataFields
	{
	// vars
	protected $type = "url";
	protected $config = array('subpartType' => array('edit' => '###URL_INPUT###',  'config' => '###METADATA_INPUT###'));
	}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/general_data_display/pi1/class.tx_generaldatadisplay_pi1_dataFields.php'])        {
        include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/general_data_display/pi1/class.tx_generaldatadisplay_pi1_dataFields.php']);
}
?>
