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
 * dataFields-Class for the 'general_data_display' extension.
 * provides helper methods for the different datafields 
 *
 * @author	Roderick Braun <roderick.braun@ph-freiburg.de>
 * @package	TYPO3
 * @subpackage	tx_generaldatadisplay
 */
abstract class tx_generaldatadisplay_pi1_dataFields
	{
	protected static $table = "tx_generaldatadisplay_datafields";
	protected static $metaDataHash = array();
	protected $tmplArr = array();
	protected $config = array();

	public function getProperty($property)
		{
		return isset($this->$property) ? $this->$property : null;
		}

	public function setTmplArr(array &$tmplArr)
		{
		$this->tmplArr = $tmplArr;
		}

	public function getTmplVar($property)
		{
		return isset($this->tmplArr[$property]) ? $this->tmplArr[$property] : null;
		}

	public function setTmplVar($property,$value)
		{
		$this->tmplArr[$property] = $value;
		return isset($this->tmplArr[$property]) ? $this->tmplArr[$property] : null;
		}

	public function HTML($type='edit')
		{
		$cObj = t3lib_div::makeInstance('tslib_cObj');
		$subpart = $cObj->getSubpart(TEMPLATE,$this->config['subpartType'][$type]);

		return $cObj->substituteMarkerArrayCached($subpart,$this->tmplArr);
		}

	public static function getMetadata($uid)
		{
		if (empty(self::$metaDataHash)) 
			{
			# get metadata from datafield table
			$datafieldList = t3lib_div::makeInstance(PREFIX_ID.'_datafieldList');
			$datafieldList->getDS();
			self::$metaDataHash = $datafieldList->getHash('metadata',true);
			}

		if (self::$metaDataHash[$uid])
			$tmplArr = unserialize(self::$metaDataHash[$uid]);

		return $tmplArr ? $tmplArr : array();
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
				$keyArr = array('img_size_x' => 1,'img_size_y' => 1,'img_align' => 1);
				# clear all non defined metadata
				foreach ($metadata as $key => $value) 
					if (!$keyArr[$key]) unset($metadata[$key]);

				if ($metadata['img_size_x']) $metadata['img_size_x'] = (int)$metadata['img_size_x'];
				if ($metadata['img_size_y']) $metadata['img_size_y'] = (int)$metadata['img_size_y'];
				if (!preg_match('/^(left|right|center)$/',$metadata['img_align'])) unset($metadata['img_align']);
			break;

			default:
				$keyArr = array('datafield_searchable' => 1,'datafield_required' => 1,'content_visible' => 1);
				# clear all non defined metadata
				foreach ($metadata as $key => $value) 
					if (!$keyArr[$key]) unset($metadata[$key]);

				foreach ($keyArr as $key => $value)
					if (!isset($metadata[$key]) || $metadata[$key] == "no") $metadata[$key]="no";
			break;
			}
		}

	}

class tx_generaldatadisplay_pi1_tinytext extends tx_generaldatadisplay_pi1_dataFields
	{
	# vars
	protected $type = "tinytext";
	protected $config = array('subpartType' => array('edit' => '###TINYTEXT_INPUT###', 'config' => '###METADATA_INPUT###'));
	}

class tx_generaldatadisplay_pi1_text extends tx_generaldatadisplay_pi1_dataFields
	{
	# vars
	protected $type = "text";
	protected $config = array('subpartType' => array('edit' => '###TEXTAREA_INPUT###', 'config' => '###METADATA_INPUT###'));
	}

class tx_generaldatadisplay_pi1_img extends tx_generaldatadisplay_pi1_dataFields
	{
	# vars
	protected $type = "img";
	protected $config = array('subpartType' => array('edit' => '###IMAGE_INPUT###', 'config' => '###METADATA_IMAGE###'),
				  'imgAlign' => array('left' => 'left','center' => 'center','right' => 'right')
				 );
	}

class tx_generaldatadisplay_pi1_int extends tx_generaldatadisplay_pi1_dataFields
	{
	# vars
	protected $type = "int";
	protected $config = array('subpartType' => array('edit' => '###INT_INPUT###', 'config' => '###METADATA_INPUT###'));
	}

class tx_generaldatadisplay_pi1_bool extends tx_generaldatadisplay_pi1_dataFields
	{
	# vars
	protected $type = "bool";
	protected $config = array('subpartType' => array('edit' => '###BOOL_INPUT###', 'config' => '###METADATA_INPUT###'));

	public function HTML($type='edit')
		{
		$this->tmplArr['###VALUE_DATAFIELD_NO###'] = 'no';
		$this->tmplArr['###VALUE_DATAFIELD_YES###'] = 'yes';
		$this->tmplArr['###DATAFIELD_SELECTED_YES###'] = $this->tmplArr['###DATAFIELD_CONTENT###']=='yes' ? "selected" : "";
		$this->tmplArr['###DATAFIELD_SELECTED_NO###'] = $this->tmplArr['###DATAFIELD_CONTENT###']=='no' ? "selected" : "";
	
		return parent::HTML($type);
		}
	}

class tx_generaldatadisplay_pi1_date extends tx_generaldatadisplay_pi1_dataFields
	{
	# vars
	protected $type = "date";
	protected $config = array('subpartType' => array('edit' => '###DATE_INPUT###', 'config' => '###METADATA_INPUT###'));
	}

class tx_generaldatadisplay_pi1_time extends tx_generaldatadisplay_pi1_dataFields
	{
	# vars
	protected $type = "time";
	protected $config = array('subpartType' => array('edit' => '###TIME_INPUT###', 'config' => '###METADATA_INPUT###'));
	}

class tx_generaldatadisplay_pi1_email extends tx_generaldatadisplay_pi1_dataFields
	{
	# vars
	protected $type = "email";
	protected $config = array('subpartType' => array('edit' => '###EMAIL_INPUT###', 'config' => '###METADATA_INPUT###'));
	}

class tx_generaldatadisplay_pi1_url extends tx_generaldatadisplay_pi1_dataFields
	{
	# vars
	protected $type = "url";
	protected $config = array('subpartType' => array('edit' => '###URL_INPUT###', 'config' => '###METADATA_INPUT###'));
	}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/general_data_display/pi1/class.tx_generaldatadisplay_pi1_dataFields.php'])        {
        include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/general_data_display/pi1/class.tx_generaldatadisplay_pi1_dataFields.php']);
}
?>
