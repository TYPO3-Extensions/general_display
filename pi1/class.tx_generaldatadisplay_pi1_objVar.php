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
 *objVar-Class for the 'general_data_display' extension.
 *
 * @author	Roderick Braun <roderick.braun@ph-freiburg.de>
 * @package	TYPO3
 * @subpackage	tx_generaldatadisplay
 */

class tx_generaldatadisplay_pi1_objVar
	{
	# vars
	protected $data = null;

	public function set($property)
		{
		$this->data = $this->htmlspecialcharsDecode($property);

		return $this;
		}

	public function setValue($key,$value)
		{
		if (is_array($this->data) || !$this->data) $this->data[$key] = $this->htmlspecialcharsDecode($value);

		return $this;
		}

	public function get($key="")
		{
		$data =  $this->data;

		if (is_array($data))
			return $key ? $this->htmlspecialcharsEncode($data[$key]) : $this->htmlspecialcharsEncode($data);
				
		elseif (is_scalar($this->data)) return $this->htmlspecialcharsEncode($data);

		return null;
		}

	public function getplain($key="")
		{
		$data = null;

		if (is_array($this->data))
			return ($key) ? $this->data[$key] : $this->data;
		elseif (is_scalar($this->data)) return $this->data;

		return null;
		}

	public function delKey($key)
		{
		if (is_array($this->data)) unset($this->data[$key]);

		return $this;
		}

	private function htmlspecialcharsEncode(&$item)
		{
		if (is_array($item))
			{
			foreach ($item AS $key => &$value) 
				{
				if (is_array($value)) $this->htmlspecialcharsEncode($value);
				elseif(is_scalar($value)) $value = htmlspecialchars($value,ENT_QUOTES);
				}
			} 
		elseif (is_scalar($item)) $item = htmlspecialchars($item,ENT_QUOTES);

		return $item;
		}

	private function htmlspecialcharsDecode(&$item)
		{
		if (is_array($item)) 
			{
			foreach ($item AS $key => &$value) 
				{
				if (is_array($value)) $this->htmlspecialcharsDecode($value);
				elseif (is_scalar($value)) $value = htmlspecialchars_decode($value,ENT_QUOTES);
				}
			} 
		elseif (is_scalar($item)) $item = htmlspecialchars_decode($item,ENT_QUOTES);

		return $item;
		}
	
	}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/general_data_display/pi1/class.tx_generaldatadisplay_pi1_objVar.php'])        {
        include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/general_data_display/pi1/class.tx_generaldatadisplay_pi1_objVar.php']);
}

?>