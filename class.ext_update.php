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
 * new content table makes base64 encoding superfluous 
 * 
 */
class ext_update 
	{
	private $sqlError = FALSE;

	public function main() 
		{
		# If the update button hasn't been clicked
		if (!t3lib_div::_GP('do_update')) 
			{
			$button = '<input name="update" value="Update" type="submit" />';
			$content = '<form action="'.htmlspecialchars(t3lib_div::linkThisScript(array('do_update' => 1))).'" method="post">'.$button.'</form>';
			} else
			{
			# convert data content
			# get list of datafield names
			$dataSet=$GLOBALS['TYPO3_DB']->exec_SELECTquery('*',
									'tx_generaldatadisplay_data',
									'data_field_content !=""'
									);

			if (!$GLOBALS['TYPO3_DB']->sql_error() && $dataSet) 
				{
				# Content
				while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($dataSet))
					{
					# convert content if it is not converted yet
					$dataContent = base64_decode($row['data_field_content'],TRUE) ? base64_decode($row['data_field_content']) : $row['data_field_content'];
					$unserializedData = unserialize($dataContent);
					# go through hash
					if (is_array($unserializedData))
						{
						foreach($unserializedData as $key => $value)
							{
							$insertData['pid'] = $row['pid'];
							$insertData['tstamp'] = $row['tstamp'];
							$insertData['crdate'] = $row['crdate'];
							$insertData['cruser_id'] = $row['cruser_id'];
							$insertData['data_uid'] = $row['uid'];
							$insertData['datafields_uid'] = $key;
							$insertData['datacontent'] = $value;
							$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_generaldatadisplay_datacontent',$insertData);
							$this->dbError();
						
							if (!$this->sqlError)
								{
								$GLOBALS['TYPO3_DB']->exec_UPDATEquery( 'tx_generaldatadisplay_data',
													'uid='.$row['uid'],
													array('data_field_content' => NULL)
													);

								$this->dbError();
								}
							}
						} 
						
					}
				}
			
			# convert datafields
			$dataSet=$GLOBALS['TYPO3_DB']->exec_SELECTquery('*',
									'tx_generaldatadisplay_datafields',
									'metadata is null'
									);

			if (!$GLOBALS['TYPO3_DB']->sql_error() && $dataSet) 
				{
				while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($dataSet))
					{
					$metadata['datafield_searchable'] = $row['datafield_searchable'];
					$metadata['content_visible'] = $row['content_visible'];
					$metadata['datafield_required'] = $row['datafield_required'];

					$serializedData['metadata'] = serialize($metadata);
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_generaldatadisplay_datafields','uid='.$row['uid'],$serializedData);
					$this->dbError();
					}
				}

			# now drop all non used table fields silently
			if (!$this->sqlError)
				{
				$obsoleteTableFields = array('tx_generaldatadisplay_data' => array('data_field_content'),
							     'tx_generaldatadisplay_datafields' => array('datafield_searchable','content_visible','datafield_required')
							    );

				foreach ($obsoleteTableFields as $table => $fieldArr)
					{
					foreach ($fieldArr as $field)
						{
						$sql = "ALTER TABLE ".$table." DROP ".$field;
						$GLOBALS['TYPO3_DB']->admin_query($sql);
						}
					}
					
				}

			# convert old time & date fields
			$dataSet=$GLOBALS['TYPO3_DB']->exec_SELECTquery('tx_generaldatadisplay_datacontent.uid AS uid, tx_generaldatadisplay_datacontent.datacontent AS datacontent, tx_generaldatadisplay_datafields.datafield_type AS datafield_type',
								'tx_generaldatadisplay_datacontent LEFT JOIN tx_generaldatadisplay_datafields
								 ON tx_generaldatadisplay_datacontent.datafields_uid = tx_generaldatadisplay_datafields.uid',
								'datafield_type IN ("date","time") AND datacontent !="" AND datacontent not like "a:3:{%"'
								);

			if (!$GLOBALS['TYPO3_DB']->sql_error() && $dataSet) 
				{
				# Content
				while ($row=$GLOBALS['TYPO3_DB']->sql_fetch_assoc($dataSet))
					{
					switch($row['datafield_type']) 
						{
						case 'time':
						preg_match('/^([0-9]{1,2}):([0-9]{2})(:([0-9]{2}))?$/', $row['datacontent'], $matches);
						$data = array('HOUR' => $matches[1], 'MINUTE' => $matches[2], 'SECOND' => $matches[4]);
						break;

						case 'date':
						preg_match('/^([0-9]{1,2})\D([0-9]{1,2})\D([0-9]{1,4})$/', $row['datacontent'], $matches);
						$data = array('MONTH' => $matches[1], 'DAY' => $matches[2], 'YEAR' => $matches[3]);
						break;
						}
					
					if ($matches[0])
						{
						$data = serialize($data);
						$GLOBALS['TYPO3_DB']->exec_UPDATEquery( 'tx_generaldatadisplay_datacontent',
											'uid='.$row['uid'],
											array('datacontent' => $data)
											);

						$this->dbError();
						}
					}
				}

			$content = $this->sqlError ? "<p>Something went wrong during the database content update!</p>" : "<p>Database content update successful!</p>";
			}
		return $content;
		}
	
	public function access()
		{
		$return = FALSE;
		# check if there is old data_field_content
		$dataSet=$GLOBALS['TYPO3_DB']->exec_SELECTquery('uid',
					 			'tx_generaldatadisplay_data',
								'data_field_content !=""'
								);

		
		if ($dataSet) $return = $GLOBALS['TYPO3_DB']->sql_num_rows($dataSet) ? TRUE : FALSE;

		# check if metadata is already extracted
		$dataSet=$GLOBALS['TYPO3_DB']->exec_SELECTquery('uid',
								'tx_generaldatadisplay_datafields',
								'metadata is null'
								);

		if ($dataSet) $return = $return || $GLOBALS['TYPO3_DB']->sql_num_rows($dataSet) ? TRUE : FALSE;

		# check for old date or time fieldtypes
		$dataSet=$GLOBALS['TYPO3_DB']->exec_SELECTquery('tx_generaldatadisplay_datacontent.uid AS uid, tx_generaldatadisplay_datacontent.datacontent AS datacontent, tx_generaldatadisplay_datafields.datafield_type AS datafield_type',
								'tx_generaldatadisplay_datacontent LEFT JOIN tx_generaldatadisplay_datafields
								 ON tx_generaldatadisplay_datacontent.datafields_uid = tx_generaldatadisplay_datafields.uid',
								'datafield_type IN ("date","time") AND datacontent !="" AND datacontent not like "a:3:{%"'
								);

		if ($dataSet) $return = $return || $GLOBALS['TYPO3_DB']->sql_num_rows($dataSet) ? TRUE : FALSE;

		return $return;
		}

	private function dbError()
		{
		$this->sqlError = $this->sqlError || $GLOBALS['TYPO3_DB']->sql_error();
		return $this->sqlError;
		}
	}
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/general_data_display/class.ext_update.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/general_data_display/class.ext_update.php']);
}
?>