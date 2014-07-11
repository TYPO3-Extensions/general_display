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
 * export-Class for the 'general_data_display' extension.
 *
 * @author	Roderick Braun <roderick.braun@ph-freiburg.de>
 * @package	TYPO3
 * @subpackage	tx_generaldatadisplay
 */

abstract class tx_generaldatadisplay_pi1_export {
	protected $prefixId='tx_generaldatadisplay_pi1';
	protected $headerContentType;
	protected $headerContent;
	protected $data;

	public function export() {
		// header
		header($this->headerContentType);
		header($this->headerContent);
		
		// content
		echo $this->data;
		exit;
	}
}

class tx_generaldatadisplay_pi1_exportCSV extends tx_generaldatadisplay_pi1_export {
	public function setData($data, $filename) {
		// set headerContent / Type
		$this->headerContentType = 'Content-type: text/csv';
		$this->headerContent = 'Content-Disposition: inline; filename='.$filename.'.csv'; 

		// set data
		foreach ($data as $key => $col) {
			foreach ($col as $key => $value) {
				$value = '"'.str_replace('"', '\'', htmlspecialchars_decode($value)).'"';
				$this->data .= iconv('UTF-8', 'ISO8859-1'.'//IGNORE', $value).';';
			}
		$this->data .= "\n";
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/generaldatadisplay/pi1/class.tx_generaldatadisplay_pi1_export.php'])        {
        include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/generaldatadisplay/pi1/class.tx_generaldatadisplay_pi1_export.php']);
}
?>
