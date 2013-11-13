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
 * objVar-Class for the 'general_data_display' extension.
 * should be used for all displayed vars
 *
 * @author	Roderick Braun <roderick.braun@ph-freiburg.de>
 * @package	TYPO3
 * @subpackage	tx_generaldatadisplay
 */

class tx_generaldatadisplay_pi1_objVar
	{
	// vars
	protected $data = null;

	public function set($property)
		{
		$this->data = $this->specialchars($property, FALSE);

		return $this;
		}

	public function setValue($key, $value)
		{
		if (is_array($this->data) || !$this->data) $this->data[$key] = $this->specialchars($value, FALSE);

		return $this;
		}

	
	public function get($key="", $plain=FALSE)
		{
		$item = (is_array($this->data) && $key) ? $this->data[$key]: $this->data;

		return $plain ? $item : $this->specialchars($item);
		}

	public function delKey($key)
		{
		if (is_array($this->data)) unset($this->data[$key]);

		return $this;
		}

	public function reset()
	        {
		if (is_array($this->data)) $this->data = array();
		else unset($this->data);
		}

	private function specialchars(&$item, $encode=TRUE)
		{
		if (is_array($item))
			{
			foreach ($item AS $key => &$value) 
				$this->specialchars($value, $encode);
			} 
		elseif (is_scalar($item)) $item = trim($encode ? htmlspecialchars($item, ENT_QUOTES) : htmlspecialchars_decode($item, ENT_QUOTES));

		return $item;
		}
	}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/general_data_display/pi1/class.tx_generaldatadisplay_pi1_objVar.php'])        {
        include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/general_data_display/pi1/class.tx_generaldatadisplay_pi1_objVar.php']);
}

?>
