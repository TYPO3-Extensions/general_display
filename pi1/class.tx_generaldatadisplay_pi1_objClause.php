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
 * objClause-Class for the 'general_data_display' extension.
 * provides methods to assemble search clauses
 *
 * @author	Roderick Braun <roderick.braun@ph-freiburg.de>
 * @package	TYPO3
 * @subpackage	tx_generaldatadisplay
 */

class tx_generaldatadisplay_pi1_objClause
	{
	protected $ruleArr = array();

	public function addAND($key,$value,$operator,$concat='AND')
		{
		$this->ruleArr['AND'][$key][] = array('value' => $value, 'operator' => $operator, 'concat' => $concat);
		}

	public function addOR($key,$value,$operator,$concat='AND')
		{
		$this->ruleArr['OR'][$key][] = array('value' => $value, 'operator' => $operator, 'concat' => $concat);
		}

	public function get($table)
		{
		# derive and/or inner clauses
		foreach($this->ruleArr['AND'] as $key => $value)
			$andClause[$key] = $this->innerExpression($this->ruleArr['AND'],$key,$table);

		foreach($this->ruleArr['OR'] as $key => $value)
			$orClause[$key] = $this->innerExpression($this->ruleArr['OR'],$key,$table);

		if ($andClause) $clauses[] = '('.implode(' AND ',$andClause).')';
		if ($orClause) $clauses[] = '('.implode(' OR ',$orClause).')';
		
		# now merge all clauses
		return implode(' AND ',$clauses);
		}
	
	public function notEmpty()
		{
		return empty($this->ruleArr) ? false : true;
		}

	private function innerExpression(array &$ruleArr,$key,$table)
		{
		foreach($ruleArr[$key] as $index => $rule)
			{
			# check columname against whitelist
			$error = tx_generaldatadisplay_pi1_formData::checkValue($key,'plainColumn');

			if (!$error['plainColumn'])
				{
				$clause .= 
					($clause ? " ".$rule['concat']." " : "")
					."`".$key."`"
					." ".$rule['operator']." "
					.$GLOBALS['TYPO3_DB']->fullQuoteStr($rule['value'],$table);
				}
			}
		return '('.$clause.')';
		}
	}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/general_data_display/pi1/class.tx_generaldatadisplay_pi1_objClause.php'])        {
        include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/general_data_display/pi1/class.tx_generaldatadisplay_pi1_objClause.php']);
}

?>
