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
 *Format-Class for the 'general_data_display' extension.
 *
 * @author	Roderick Braun <roderick.braun@ph-freiburg.de>
 * @package	TYPO3
 * @subpackage	tx_generaldatadisplay
 */
class tx_generaldatadisplay_pi1_HTMLformat
	{
	public function formatContentType(&$obj)
		{
		$content = $obj->getObjVar('datacontent');
		$type = $obj->getObjVar('datafield_type');

		if (! $content) return;

		switch ($type)
			{	
			case 'date':
			$content = preg_replace('/\D/',".",$content);
			break;

			case 'email':
			$mailarr = $this->cObj->getMailTo($content);
			$content = '<a href="'.$mailarr[0].'">'.$mailarr[1].'</a>';
			break;

			case 'url':
			$content = $this->cObj->typolink($content,array('parameter' => $content,'extTarget' => '_blank'));
			break;	

			case 'bool':
			$content = $this->pi_getLL($content);
			break;

			case 'img':
			$metadata = tx_generaldatadisplay_pi1_dataFields::getMetadata($obj->getObjVar('datafields_uid'));
			
			if ($metadata['img_size_x']) $imgSizeArr[] = 'width="'.$metadata['img_size_x'].'"';
			if ($metadata['img_size_y']) $imgSizeArr[] = 'height="'.$metadata['img_size_y'].'"';
			
			$content = '<div '.($metadata['img_align'] ? 'style="text-align:'.$metadata['img_align'].'"' : '').'><img src="'.IMGUPLOADPATH.'/'.$content.'" alt="'.$this->pi_getLL('img').'" '.implode(' ',$imgSizeArr).'></div>';
			break;

			case 'text':
			$this->substituteTags($content);
			break;
			}

		return $content;
		}

	private function substituteTags(&$content)
		{
		$searchRegArr = array(
					'\n',  # linebreak
					'\*\*(.+)\*\*', # bold
					'\/\/(.+)\/\/', # italic
					'__(.+)__', # underline
					'\'\'(.+)\'\'', # teletyper
					'\^\^(.+)\^\^', # superscript
					'\,\,(.+)\,\,', # subscript
					'/\[\[((.+)\|)?(.+)\]\]/' # link
					
					
				     );
		$replaceArr = array(
					'<br>', # linebreak
					'<b>$1</b>', # bold
					'<i>$1</i>', # italic
					'<u>$1</u>', # underline
					'<tt>$1</tt>', # teletyper
					'<sup>$1</sup>', # superscript
					'<sub>$1</sub>', # subscript
					'<a href="$3">$2</a>' # link
					
				   );

		return preg_replace($searchRegArr, $replaceArr, $content);
		}
	}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/general_data_display/pi1/class.tx_generaldatadisplay_pi1_HTMLformat.php'])        {
        include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/general_data_display/pi1/class.tx_generaldatadisplay_pi1_HTMLformat.php']);
}

?>