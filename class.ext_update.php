<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Roderick Braun <roderick.braun@ph-freiburg.de>
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
 * Extension update 
 * convert all content to base64 to prevent data corruption through charset change
 * 
 */
class ext_update 
	{
	public function main() 
		{
		/* If the update button hasn't been clicked */
		if (!t3lib_div::_GP('do_update')) 
			{
			$onClick = "document.location='".t3lib_div::linkThisScript(array('do_update' => 1))."'; return false;";
			$content = '<form action=""><input type="submit" value="Update database content" onclick="'.htmlspecialchars($onClick).'"></form>';
			} else
			{
			$mysqlError = false;

			# get list of datafield names
			$dataSet=$GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,data_field_content',
								'tx_generaldatadisplay_data'
								);
			if ($dataSet) 
				{
				# Content
				while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($dataSet))
					{
					if ($row['uid'])
						{
						# convert content if it is not converted yet
						$base64_content['data_field_content'] = base64_decode($row['data_field_content'],true) ? $row['data_field_content'] : base64_encode($row['data_field_content']);
						$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_generaldatadisplay_data','uid='.$row['uid'],$base64_content);
						$mysqlError = $mysqlError || $GLOBALS['TYPO3_DB']->sql_error();
						} else $mysqlError =true;
					}
				}
			$content = $mysqlError ? '<p>Something went wrong during the database content update!</p>' : '<p>Database content update successful!</p>';
			}
		return $content;
		}
	
	public function access()
		{
		# check if convertion ist necessary
		$dataSet=$GLOBALS['TYPO3_DB']->exec_SELECTquery('data_field_content',
								'tx_generaldatadisplay_data'
								);

		while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($dataSet))
			if (! base64_decode($row['data_field_content'],true)) return true;
	
		return false;
		}
	}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/general_data_display/class.ext_update.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/timtab/class.ext_update.php']);
}
?>