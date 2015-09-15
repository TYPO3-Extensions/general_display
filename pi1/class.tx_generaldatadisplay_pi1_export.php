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
		$this->headerContent = 'Content-Disposition: inline; filename="'.$filename.'.csv"'; 

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

class tx_generaldatadisplay_pi1_downloadFile extends tx_generaldatadisplay_pi1_export {
	public function setData($filename) {
		// known mime types
		$mimeTypes = array(
			'xls' => 'application/msexcel',
			'xla' => 'application/msexcel',
			'ppt' => 'application/mspowerpoint',
			'ppz' => 'application/mspowerpoint',
			'pps' => 'application/mspowerpoint',
			'pot' => 'application/mspowerpoint',
			'doc' => 'application/msword',
			'docx' => 'application/msword',
			'pdf' => 'application/pdf',
			'ps' => 'application/postscript',
			'eps' => 'application/postscript',
			'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'mp3' => 'audio/x-mpeg',
			'wav' => 'audio/x-wav',
			'mid' => 'audio/x-midi',
			'midi' => 'audio/x-midi',
			'csv' => 'text/csv',
			'htm' => 'text/html',
			'html' => 'text/html',
			'shtml' => 'text/html',
			'txt' => 'text/plain',
			'rtf' => 'text/rtf',
			'xml' => 'text/xml',
			'mpeg' => 'video/mpeg',
			'mpg' => 'video/mpeg',
			'mpe' => 'video/mpeg',
			'qt' => 'video/quicktime',
			'mov' => 'video/quicktime',
			'avi' => 'video/x-msvideo'
		);
		
		// extract file extension
		preg_match('/^(.+)\.([^\.]+)$/', $filename, $fileNamePart);
		$type = isset($mimeTypes[$fileNamePart[2]]) ? $mimeTypes[$fileNamePart[2]] : 'application/'.$fileNamePart[2];
		
		// set headerContent / Type
		$this->headerContentType = 'Content-type: application/'.$type;
		$this->headerContent = 'Content-Disposition: inline; filename="'.$filename.'"';
		
		// set data
		$this->data = file_get_contents(FILEUPLOADPATH."/".md5($filename));
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/generaldatadisplay/pi1/class.tx_generaldatadisplay_pi1_export.php'])        {
        include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/generaldatadisplay/pi1/class.tx_generaldatadisplay_pi1_export.php']);
}
?>
