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
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */

require_once(PATH_tslib.'class.tslib_pibase.php');
require_once('class.tx_generaldatadisplay_pi1_dataStructs.php');
require_once('class.tx_generaldatadisplay_pi1_queryList.php');
require_once('class.tx_generaldatadisplay_pi1_formData.php');

/**
 * Plugin 'General data display'
 *
 * @author	Roderick Braun <roderick.braun@ph-freiburg.de>
 * @package	TYPO3
 * @subpackage	tx_generaldatadisplay
 */
class tx_generaldatadisplay_pi1 extends tslib_pibase {
	public $prefixId      = 'tx_generaldatadisplay_pi1';			// Same as class name
	public $scriptRelPath = 'pi1/class.tx_generaldatadisplay_pi1.php';	// Path to this script relative to the extension dir.
	public $extKey        = 'general_data_display';				// The extension key
	public $uploadPath    = 'uploads/tx_generaldatadisplay';
	public $pi_checkCHash = true;
	
	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	public function main($content, $conf) {
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		
		if (!$this->piVars) $this->piVars = array();

		# t3lib_div::debug($this->piVars,'piVars');

		# Init Flex form
		$this->pi_initPIflexForm();

		# define picturePath
		$this->picturePath = t3lib_extMgm::extRelPath($this->extKey).'images/';

		# get PID from current page	
		define("CURRENT_PID",$GLOBALS['TSFE']->id);
		# get pid from FlexForm if one is given
		$pid = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], "pages", "general");
		if (!$pid) $pid = CURRENT_PID;
		define("PID",$pid);
		
		# get & store permission
		define("ADM_PERM",$this->isAdmin());

		# use configured css, if none is given use standard stylesheet
		$userStyleFile = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], "userStyleSheet","general");
		$cssFile = $userStyleFile ? $this->uploadPath."/".$userStyleFile : t3lib_extMgm::extRelPath($this->extKey).'css/default.css';
		# put stylesheet in Header
		$GLOBALS['TSFE']->additionalHeaderData[$this->extKey] = '<link rel="stylesheet" href="'.$cssFile.'" type="text/css" />';

		# use configured templates, if none is given use standard template
		$userTmplFile = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], "userTemplateFile","general");
		$templateFile = $userTmplFile ? $this->uploadPath."/".$userTmplFile : "EXT:".$this->extKey."/templates/template.html"; 

		# load template
		$this->template = $this->cObj->fileResource($templateFile);

		# get scope
		if ($this->piVars['scope']) $this->scopeArr = unserialize(base64_decode($this->piVars['scope']));
		


		# get values from scope if no search is given
		if ($this->piVars['action']!='search') 
			{
			$this->piVars['selected_item'] = $this->scopeArr['selected_item'];
			$this->piVars['selected_category'] = $this->scopeArr['selected_category'];
			$this->piVars['searchphrase'] = $this->scopeArr['searchphrase'];
			}
		if (!isset($this->piVars['offset'])) $this->piVars['offset'] = $this->scopeArr['offset'];
		
		# set scopeArray from piVars
		$this->scopeArr['selected_item'] = $this->piVars['selected_item'];
		$this->scopeArr['selected_category'] = $this->piVars['selected_category'];
		$this->scopeArr['searchphrase'] = $this->piVars['searchphrase'];
		$this->scopeArr['offset'] = $this->piVars['offset'];

		# make necessary hashes
		$datafieldNameHash = $this->getHashFromTable("datafield","datafield_name");
		$datafieldSearchableHash = $this->getHashFromTable("datafield","datafield_searchable");

		# always include title
		$datafieldNameHash['data_title'] = "data_title";
		$datafieldSearchableHash['data_title'] = "yes";

		# set searchClauseArr with selected values
		if ($this->piVars['selected_item']) 
			{
			if ($this->piVars['searchphrase'])
				$searchClause['searchphrase'][]=array($datafieldNameHash[$this->piVars['selected_item']] => array('value' =>$this->piVars['searchphrase'],'operator'=> 'rlike')); 
			} 
		elseif ($this->piVars['searchphrase']) 
			{
			# search over all searchable fields
			foreach ($datafieldNameHash as $key => $value)
				if ($datafieldSearchableHash[$key]=="yes")
					$searchClause['searchphrase'][]=array($value => array('value' =>$this->piVars['searchphrase'],'operator'=> 'rlike'));	
			}

		if ($this->piVars['selected_category']) 
			{
			# first add this category
			$searchClause['category'][]=array('data_category' => array('value' =>$this->piVars['selected_category'],'operator'=> '='));
			# find all dependend categories
			$categoryList = $this->getTypeListFromTable('category');
			$usedCategoryProgenitor = $this->getUsedCategoryValues('category_progenitor');
			
			foreach($usedCategoryProgenitor as $key => $value)
				{
				# get all progenitors from category
				$allProgenitors = $categoryList->getAllProgenitors($key);
				foreach($allProgenitors as $progenitor)
					if ($progenitor == $this->piVars['selected_category'])
						$searchClause['category'][]=array('data_category' => array('value' =>$key,'operator'=> '='));	
				}
			
			}
		# now build searchclause
		if ($searchClause['searchphrase']) $searchPhraseClause = $this->createSearchClause($searchClause['searchphrase'],'OR');
		if ($searchClause['category']) $categoryClause = $this->createSearchClause($searchClause['category'],'OR');
		$this->searchClause = $searchPhraseClause.($searchPhraseClause && $categoryClause ? " AND " : "").($categoryClause ? $categoryClause : "");  

		# unset action if cancel is pressed
		if ($this->piVars['cancel']) unset($this->piVars['action']);
		
		# check piVars - unset piVar if test fail
		foreach ($this->piVars as $key => $value)
			{ 
			switch ($key)
				{
				case 'type':
					{
					switch ($value)
						{
						case 'data': break;
						case 'category': break;
						case 'datafield': break;
						default: unset($this->piVars[$key]);
						}
					}
				break;

				case 'uid':
					{
					if (is_numeric($value)) $this->piVars[$key] = intval($this->piVars[$key]);
						else unset($this->piVars[$key]);
					}
				break;
				}
			}

		# choose action
		switch ($this->piVars['action'])
			{
			case 'update':
				{
				$formData = t3lib_div::makeInstance($this->prefixId.'_'.$this->piVars['type'].'Form');

				if ($this->piVars['submit'])
					{
					$dataArr = $formData->importValues($this->piVars);
						
					if (!$formData->formError())
						{
						$db = t3lib_div::makeInstance($this->prefixId.'_'.$this->piVars['type']);
						$db->setProperty("objVars",$dataArr);
			
						if ($this->piVars['uid'])
							{
							$db->setProperty("uid",$this->piVars['uid']);
							$result = $db->updateDS($dataArr);
							} else $result = $db->newDS($dataArr);
						$content = $result ? $this->view() : $this->showError('dberror_permission');
						} else $content = $this->editView($formData);
					} else 
					{
					if ($this->piVars['uid']) # get formdata from database
						{
						$dataSet = t3lib_div::makeInstance($this->prefixId.'_'.$this->piVars['type'].'List');
						$objArr = $dataSet->getDS('uid='.$this->piVars['uid']);
						$formData->importValues($objArr[$this->piVars['uid']]->getProperty('objVars'));
						}
					$content = $this->editView($formData);	
					}
				}
			break;

			case 'update-sequence':
				{
				if (ADM_PERM && $this->piVars['uid'])
					{
					$datafields = t3lib_div::makeInstance($this->prefixId.'_datafieldList');
					$objArr = $datafields->getDS();

					if ($this->piVars['direction'] == 'up')
						{
						# get predecessor uid
						foreach($objArr as $key => $obj)
							{
							# get objVars
							$objVars = $obj->getProperty('objVars');
							if ($this->piVars['uid'] == $objVars['uid']) break;
							$savedUid = $objVars['uid'];
							}
						} else {
						# get successor uid
						foreach($objArr as $key => $obj)
							{
							# get objVars
							$objVars = $obj->getProperty('objVars');
							if ($savedUid)
								{
								$savedUid = $objVars['uid']; 
								break;
								}
							if ($this->piVars['uid'] == $objVars['uid']) $savedUid = $objVars['uid'];
							}
						}
					if ($savedUid && $savedUid != $this->piVars['uid'])
						{
						$objVars1 = $objArr[$this->piVars['uid']]->getProperty('objVars');
						$objVars2 = $objArr[$savedUid]->getProperty('objVars');

						# swap sequence
						$saveValue = $objVars1['display_sequence'];
						$objVars1['display_sequence'] = $objVars2['display_sequence'];
						$objVars2['display_sequence'] = $saveValue;
						$objArr[$this->piVars['uid']]->setProperty('objVars',$objVars1);
						$objArr[$savedUid]->setProperty('objVars',$objVars2);

						# update
						$objArr[$this->piVars['uid']]->updateDS();
						$objArr[$savedUid]->updateDS();
						}
					}
				$content = $this->tableView($this->piVars['type']);
				}
			break;
		
			case 'delete-request':
				if ($this->piVars['uid'])
				$content = $this->deleteRequest($this->piVars['type']);
				else $content = $this->showError('error_missing_uid');
			break;

			case 'delete':
				{
				if ($this->piVars['uid'])
					{
					$dataSet = t3lib_div::makeInstance($this->prefixId.'_'.$this->piVars['type'].'List');
					$objArr = $dataSet->getDS('uid='.$this->piVars['uid']);

					if ($objArr)
						{
						$obj = $objArr[$this->piVars['uid']];
						$content = $obj->deleteDS() ? $this->view() : $this->showError('dberror_permission');
						} else $content=$this->showError('dberror_no_dataset');
					} else $content = $this->showError('error_missing_uid');
				}
			break;

			default:
				$content = $this->view();
			break;
			}
		return $this->pi_wrapInBaseClass($content);
		}

	

	private function view()
		{
		# get view from piVars
		switch ($this->piVars['view'])
			{
			case 1: # listview
			$content = $this->listView($this->piVars['type']);
			break;

			case 2: # singleview
			$content = $this->singleView($this->piVars['uid']);
			break;

			case 3: # tableview
			$content = $this->tableView($this->piVars['type']);
			break;

			default:
			$content = $this->listView();
			break;
			}
		return $content;
		}

	private function tableView($type='data')
		{	
		# commons
		$commonsArray = $this->makeCommonsArray();
		$optionArray = $this->makeOptionArray();
		$commons = array_merge($commonsArray,$optionArray);

		$headingsArrayCSS = $this->wrapTemplateArrayInClass($this->makeHeadingsArray(),__FUNCTION__);	
		$commons = array_merge($commons,$headingsArrayCSS);
		
		# instantiate typelist
		$typeList = t3lib_div::makeInstance($this->prefixId.'_'.$type.'List');

		# get typelist
		$objArr = $typeList->getDS();
	
		# Use subpart
		$subpart=$this->cObj->getSubpart($this->template,'###TABLE-VIEW###');

		# Table entry
		$singlerow=$this->cObj->getSubpart($this->template,'###TABLE-CONTENT###');

		$head = $this->cObj->substituteMarkerArrayCached($heading,$commons);
		
		switch ($type)
			{	
			case 'category':
				{
				if ($objArr)
					{
					# content
					foreach($objArr as $key => $obj)
						{
						# get objVars
						$objVars = $obj->getProperty('objVars');
						$contentArray['###TABLE_ROW###'] = $objVars['category_name'];
						$contentArray['###ADMINSTUFF###'] = $this->makeAdminStuff($key,$type);
						$contentArrayCSS = $this->wrapTemplateArrayInClass($contentArray,__FUNCTION__);
						$contentAll['###TABLE_CONTENT###'].= $this->cObj->substituteMarkerArrayCached($singlerow,$contentArrayCSS);
						}
					} else $contentAll['###TABLE_CONTENT###']="";
				$contentAll['###TABLE_HEADING###'] = $this->pi_getLL('all_categories');
				}
			break;

			case 'datafield':
				{
				if ($objArr)
					{
					# content
					foreach($objArr as $key => $obj)
						{
						# get objVars
						$objVars = $obj->getProperty('objVars');
						$contentArray['###TABLE_ROW###'] = $objVars['datafield_name'];
						$contentArray['###ADMINSTUFF###'] = $this->makeAdminStuff($key,$type);
						$contentArrayCSS = $this->wrapTemplateArrayInClass($contentArray,__FUNCTION__);
						$contentAll['###TABLE_CONTENT###'].= $this->cObj->substituteMarkerArrayCached($singlerow,$contentArrayCSS);
						}
					} else $contentAll['###TABLE_CONTENT###']="";
				$contentAll['###TABLE_HEADING###'] = $this->pi_getLL('all_items');		
				}
			break;
			}
		$contentAll = array_merge($contentAll,$commons);
		$content = $this->cObj->substituteMarkerArrayCached($subpart,$contentAll);
		return $content;	
		}		

	private function listView($type='data')
		{
		# commons
		$commonsArray = $this->makeCommonsArray();
		$optionArray = $this->makeOptionArray();
		$commons = array_merge($commonsArray,$optionArray);

		$headingsArrayCSS = $this->wrapTemplateArrayInClass($this->makeHeadingsArray(),__FUNCTION__);
		$commonsArray = array_merge($commonsArray,$headingsArrayCSS);

		$nrPageResults = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], "nrPageResults","general");
		$nrMaxPages = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], "nrMaxPages","general");

		switch ($type)
			{	
			case 'data':
				{
				# build option menu
				$optionField=$this->cObj->getSubpart($this->template,'###OPTION_FIELD###');
				$contentArray['###OPTIONS###'] = $this->cObj->substituteMarkerArrayCached($optionField,$commons);

				# use template subpart
				$subpart=$this->cObj->getSubpart($this->template,'###LIST-DATAVIEW###');
				$subsubpart=$this->cObj->getSubpart($this->template,'###LIST-DATA###');
				
				# instantiate typelist
				$typeList = t3lib_div::makeInstance($this->prefixId.'_dataList');

				# create tempTable
				$error = $typeList->createTempTable();
				if (! $error)
					{
					$tempTable = $typeList->getProperty('tempTable');			

					# set new order
					$typeList->setProperty('orderField','data_category,category_name,data_title');

					# get datalist from tempTable
					$objArr = $typeList->getDS($this->searchClause,$tempTable);
					$nrResults = count($objArr);

					$contentArray['###HITS###'] = "(".$nrResults." ".$this->pi_getLL('hits').")";	

					# defined display limit ?
					if ($nrPageResults && $nrPageResults < $nrResults)
						{
						$offset = intval($this->piVars['offset']);
						$range =  $offset ? ($offset.",".($offset + $nrPageResults)) : $nrPageResults;
						$objArr = $typeList->getDS($this->searchClause,$tempTable,$range);
						$index = intval($offset / ($nrPageResults * $nrMaxPages));
						$from = $index*$nrMaxPages*$nrPageResults;
						$to = ($from + $nrMaxPages*$nrPageResults) > $nrResults ? $nrResults : $from + $nrMaxPages*$nrPageResults;
						if ($from > 0) $contentArray['###PAGELINKS###'] = $this->wrapInSpan($this->pi_linkTP_keepPIvars("<<",array('offset' => $from-$nrPageResults, 'type' =>'data','scope' => base64_encode(serialize($this->scopeArr))),'0','1'),__FUNCTION__."-pageLink");
						if ($offset) $contentArray['###PAGELINKS###'] .= $this->wrapInSpan($this->pi_linkTP_keepPIvars("<",array('offset' => $offset - $nrPageResults, 'type' =>'data','scope' => base64_encode(serialize($this->scopeArr))),'0','1'),__FUNCTION__."-pageLink");
						for ($i=$from; $i < $to; $i=$i+$nrPageResults)
							{
							$index1 = $i;
							$index2 = ($i+$nrPageResults-1) >= $nrResults ? $nrResults-1 : $i+$nrPageResults-1;
							$contentArray['###PAGELINKS###'] .= $this->wrapInSpan($this->pi_linkTP_keepPIvars("[".($index1==$index2 ? ($index1+1) : ($index1+1)."-".($index2+1))."]",array('offset' => $index1, 'type' =>'data','scope' => base64_encode(serialize($this->scopeArr))),'0','1'),__FUNCTION__.($this->piVars['offset']==$index1 ? "-pageLinkActive" : "-pageLink"));
							}
						if (($offset + $nrPageResults) < $nrResults) $contentArray['###PAGELINKS###'] .= $this->wrapInSpan($this->pi_linkTP_keepPIvars(">",array('offset' => $offset + $nrPageResults, 'type' =>'data','scope' => base64_encode(serialize($this->scopeArr))),'0','1'),__FUNCTION__."-pageLink");
						if ($index2 < $nrResults-1) $contentArray['###PAGELINKS###'] .= $this->wrapInSpan($this->pi_linkTP_keepPIvars(">>",array('offset' => $index2+1, 'type' =>'data','scope' => base64_encode(serialize($this->scopeArr))),'0','1'),__FUNCTION__."-pageLink"); 
						} else {
						$contentArray['###PAGELINKS###'] = "";
						}

					# get list of categories
					$categoryList = $this->getTypeListFromTable('category');
					$catObjArr = $categoryList->getProperty('objArr');	
				
					$categorySortHash[0] = 0; # no category 

					# build sortHash
					foreach($catObjArr as $key => $obj) 
						{
						$categoryRanking[$key] = $categorySortHash[$key] = ++$inc;
						}
	
					foreach($catObjArr as $key => $obj)
						{
						$catProgenitors = $categoryList->getAllProgenitors($key);
						foreach($catProgenitors as $catProgenitor)
							$categorySortHash[$key] = $categoryRanking[$catProgenitor].$categorySortHash[$key]; 
	
						$categoryLvlHash[$key] = count($catProgenitors);
						}
					asort($categorySortHash,SORT_STRING);

					# build result list
					foreach($objArr as $key => $obj)
							{
							$dataCategory = $catObjArr[$obj->getObjVar('data_category')] ? $obj->getObjVar('data_category') : 0;
							$orderedList[$dataCategory] .=  $this->wrapInDiv($this->pi_linkTP_keepPIvars($obj->getObjVar('data_title'),array('uid' => $key, 'view' => '2', 'type' =>'data','scope' => base64_encode(serialize($this->scopeArr))),'0','1'),__FUNCTION__."-title");	
							# set all progenitors if nescessary
							foreach($categoryList->getAllProgenitors($dataCategory) as $catProgenitor) 
								$progenitorList[$catProgenitor] = 1;
							}
					
					# go through categorySortHash and fill template
					foreach($categorySortHash as $dataCategory => $value)
						{
						if ($orderedList[$dataCategory] || $progenitorList[$dataCategory])
							{
							$contentArray['###CATEGORY-NAME###'] = $this->wrapInDiv($catObjArr[$dataCategory] ? $catObjArr[$dataCategory]->getObjVar('category_name') : $this->pi_getLL('no_category'),__FUNCTION__."-category-name");
							$contentArray['###DATA-TITLE###'] = $orderedList[$dataCategory];
							$contentArray['###LISTDATA###'] .= $this->wrapInDiv($this->cObj->substituteMarkerArrayCached($subsubpart,$contentArray),__FUNCTION__."-categorylvl".$categoryLvlHash[$dataCategory]);
							}
						}
					
					if (! $contentArray['###LISTDATA###'])
						{ 
						$contentArray['###CATEGORY-NAME###'] = "";
						$contentArray['###LISTDATA###']=$this->pi_getLL('no_data');
						}
					$contentArrayCSS = $this->wrapTemplateArrayInClass($contentArray,__FUNCTION__);	
					$contentAll = array_merge($contentArrayCSS,$commonsArray);
					$content = $this->cObj->substituteMarkerArrayCached($subpart,$contentAll);
					} else $content =  $this->showError($error);
				}
				break;	
			}
		return $content;	
		}

	private function singleView($uid,$type='data')
		{
		if (!$uid) return;

		# commons
		$commonsArray = $this->makeCommonsArray();
		$headingsArrayCSS = $this->wrapTemplateArrayInClass($this->makeHeadingsArray(),__FUNCTION__);
		$commonsArray = array_merge($commonsArray,$headingsArrayCSS);		

		switch ($type)
			{	
			case 'data':
				{
				# use template subpart
				$subpart=$this->cObj->getSubpart($this->template,'###DETAIL-DATAVIEW###');
				$subsubpart=$this->cObj->getSubpart($this->template,'###DETAIL-DATA###');

				# instantiate typelist
				$typeList = t3lib_div::makeInstance($this->prefixId.'_dataList');
			
				# get data 
				$objArr = $typeList->getDS('uid='.$uid);

				# get list of categories
				$categoryList = $this->getTypeListFromTable('category');
				$catObjArr = $categoryList->getProperty('objArr');

				# make necessary hashes
				$datafieldNameHash = $this->getHashFromTable('datafield','datafield_name','uid','content_visible="yes"');
				$datafieldTypeHash = $this->getHashFromTable('datafield','datafield_type','uid','content_visible="yes"');

				if ($objArr[$uid])
					{
					$objVars =  $objArr[$uid]->getProperty('objVars');
					$contentArray['###ADMINSTUFF###'] = $this->makeAdminStuff($uid);
					$contentArray['###CATEGORY-NAME###'] = is_object($catObjArr[$objVars['data_category']]) ?  $catObjArr[$objVars['data_category']]->getObjVar('category_name') : "";
					$contentArray['###DATA_TITLE###']=$objVars['data_title'];
					$contentArray['###DETAILDATA###'] ="";

					# get data fields
					$dataContent = $objArr[$uid]->getDatafieldContent();
					# sort fields
					$orderedDataContent = array();
					foreach($datafieldNameHash as $key => $value) $orderedDataContent[$key] = $dataContent[$key];	
					
					foreach($orderedDataContent as $key => $value) 
						{
						# format value according to type
						$value = $this->formatContentType($value,$datafieldTypeHash[$key]);

						if ($value && $datafieldNameHash[$key])
							{
							# standard template uses Detaildata - but you can also use your own template & "real" names
							$contentArray['###HEADING_'.strtoupper($datafieldNameHash[$key]).'###'] = $this->wrapInDiv($key,__FUNCTION__."-dataHeading");
							$contentArray['###'.strtoupper($datafieldNameHash[$key]).'###'] = $this->wrapInDiv($this->pi_getLL($value) ? $this->pi_getLL($value) : $value,__FUNCTION__."-dataContent");	
							$contentArray['###HEADING_DATACONTENT###'] = $this->wrapInDiv($datafieldNameHash[$key],__FUNCTION__."-dataHeading");
							$contentArray['###DATACONTENT###'] = $this->wrapInDiv($value,__FUNCTION__."-dataContent");
							$contentArray['###DETAILDATA###'].= $this->cObj->substituteMarkerArrayCached($subsubpart,$contentArray);
							}
						}
					} 
				$contentArrayCSS = $this->wrapTemplateArrayInClass($contentArray,__FUNCTION__);	
				$contentAll = array_merge($contentArrayCSS,$commonsArray);
				$content = $this->cObj->substituteMarkerArrayCached($subpart,$contentAll);
				}
			break;
			}
		return $content;
		}

	private function editView($formData)
		{
		# commons
		$commonsArray = $this->makeCommonsArray();
		$headingsArrayCSS = $this->wrapTemplateArrayInClass($this->makeHeadingsArray(),__FUNCTION__);
		$contentArray = array_merge($commonsArray,$headingsArrayCSS);

		# get stored formValues
		$formValues = t3lib_div::htmlspecialchars_decode($formData->getProperty('dataArr'));
		
		$formError = $formData->getProperty('formError');
		$type = $formData->getProperty('type');
		
		# UID
		$contentArray['###UID###']=$formValues['uid'];
		
		# build formError Template
		$contentArray['###DATA_TITLE_ERROR###']="";
		$contentArray['###DATA_CATEGORY_ERROR###']="";
		$contentArray['###CATEGORY_NAME_ERROR###']="";
		$contentArray['###DATAFIELD_NAME_ERROR###']="";
		$contentArray['###DATAFIELD_TYPE_ERROR###']="";

		foreach ($formError as $key => $value)
 			{
			$templKeyword="###".strtoupper($key)."_ERROR###";
			if ($value) 
				{ 
				$contentArray[$templKeyword]=$this->wrapInDiv($this->pi_getLL('error_'.$value),'editView-Formerror');
				} else $contentArray[$templKeyword]="";	
			}

		switch ($type)
			{	
			case 'data':
				{
				# get datafields
				$typeList = $this->getTypeListFromTable('datafield');
				$objArr = $typeList->getProperty('objArr');

				$subpart = "###EDIT_DATA###";				
				# set contentArray either from formValues or from table
				$contentArray['###DATA_TITLE###']=$formValues['data_title'];	
				$categoryOptions = $this->getOptionsFromTable('category','category_name',$formValues['data_category']);
				$contentArray['###DATA_CATEGORY_OPTIONS###'] = $categoryOptions ? $categoryOptions : '<option value="">'.$this->pi_getLL('empty_category').'</option>';
				
				if (! $objArr) $contentArray['###INPUT_DATAFIELDS###']=$this->pi_getLL('no_datafields'); 
				
				foreach($objArr as $key => $obj)
					{
					$objVars = $obj->getProperty('objVars');
					$inputArray['###HEADING_DATAFIELD###'] = $this->wrapInDiv($objVars['datafield_name'],__FUNCTION__."-dataHeading");
					$inputArray['###DATAFIELD_NAME###'] = $objVars['datafield_name'];
					$inputArray['###DATAFIELD_CONTENT###'] = $formValues[$objVars['datafield_name']];
					$inputArray['###DATAFIELD_CONTENT_ERROR###'] = $formError[$objVars['datafield_name']] ? $this->wrapInDiv($this->pi_getLL('error_'. $formError[$objVars['datafield_name']]),'editView-Formerror') : "";
					$inputArray['###PI_BASE###'] = $this->prefixId;

					switch ($objVars['datafield_type'])
						{
						case 'tinytext':
						$subpartType = $this->cObj->getSubpart($this->template,"###TINYTEXT_INPUT###");
						break;

						case 'text':
						$subpartType=$this->cObj->getSubpart($this->template,"###TEXTAREA_INPUT###");
						break;

						case 'int':
						$subpartType=$this->cObj->getSubpart($this->template,"###INT_INPUT###");
						break;

						case 'bool':
						$subpartType=$this->cObj->getSubpart($this->template,"###BOOL_INPUT###");
						$inputArray['###YES###'] = $contentArray['###YES###'];
						$inputArray['###NO###'] = $contentArray['###NO###'];
						$inputArray['###VALUE_DATAFIELD_NO###'] = 'no';
						$inputArray['###VALUE_DATAFIELD_YES###'] = 'yes';
						if ($inputArray['###DATAFIELD_CONTENT###']=='no') $inputArray['###DATAFIELD_SELECTED_NO###'] = 'selected';
						else $inputArray['###DATAFIELD_SELECTED_YES###'] = 'selected';
						break;

						case 'date':
						$subpartType=$this->cObj->getSubpart($this->template,"###DATE_INPUT###");
						break;

						case 'time':
						$subpartType=$this->cObj->getSubpart($this->template,"###TIME_INPUT###");
						break;

						case 'email':
						$subpartType=$this->cObj->getSubpart($this->template,"###EMAIL_INPUT###");
						break;

						case 'url':
						$subpartType=$this->cObj->getSubpart($this->template,"###URL_INPUT###");
						break;
						}
					$contentArray['###INPUT_DATAFIELDS###'].= $this->cObj->substituteMarkerArrayCached($subpartType,$inputArray);
					}
				}
			break;
			
			case 'category':
				{
				$subpart = "###EDIT_CATEGORY###";
				$contentArray['###CATEGORY_NAME###']=$formValues['category_name'];
				$contentArray['###DATA_SUBCATEGORY_OPTIONS###']="<option value='0'></option>".$this->getOptionsFromTable('category','category_name',$formValues['category_progenitor']);

				}
			break;
			
			case 'datafield':
				{
				$subpart = "###EDIT_DATAFIELD###";
 
				$contentArray['###HEADING_REQUIRED###']=$this->pi_getLL('required');
				$contentArray['###HEADING_SEARCHABLE###']=$this->pi_getLL('searchable');
				$contentArray['###HEADING_CONTENT_VISIBLE###']=$this->pi_getLL('visible');
				$contentArray['###DATAFIELD_NAME###']=$formValues['datafield_name'];
				$contentArray['###DISPLAY_SEQUENCE###']=$formValues['display_sequence'] ? $formValues['display_sequence'] : time();
				# types
				$types = array('tinytext','text','int','bool','date','time','email','url');
				foreach ($types as $type) 
					$options .= "<option value=".$type.(($type==$formValues['datafield_type']) ? " selected>" : ">").$this->pi_getLL($type)."</option>";
				$contentArray['###DATAFIELD_TYPE_OPTIONS###'] = $options;
				$contentArray['###DATAFIELD_REQUIRED###']=$formValues['datafield_required']=='yes' ? 'checked="checked"' : '';
				$contentArray['###DATAFIELD_SEARCHABLE###']=$formValues['datafield_searchable']=='no' ? '' : 'checked="checked"';
				$contentArray['###CONTENT_VISIBLE###']=$formValues['content_visible']=='no' ? '' : 'checked="checked"';
				}
			break;
			}
		# choose subpart of template
		$subpart=$this->cObj->getSubpart($this->template,$subpart);
		$content = $this->cObj->substituteMarkerArrayCached($subpart,$contentArray);
		return $content;
		}

	private function deleteRequest($type='data')
		{
		$dataSet = t3lib_div::makeInstance($this->prefixId.'_'.$type.'List');
		$objArr = $dataSet->getDS('uid='.$this->piVars['uid']);

		$obj = $objArr[$this->piVars['uid']];
			
		# common template subpart
		$subpart=$this->cObj->getSubpart($this->template,'###DELETE_REQUEST###');

		# commons
		$commonsArray = $this->makeCommonsArray();
		$commonsArray['###UID###']=$this->piVars['uid'];
		$commonsArray['###TYPE###']=$type;

		$headingsArrayCSS = $this->wrapTemplateArrayInClass($this->makeHeadingsArray(),__FUNCTION__);
		$commons = array_merge($commonsArray,$headingsArrayCSS);
		
		# get objVars	
		$objVars = $obj->getProperty('objVars');

		switch ($type)
			{	
			case 'data':
				{
				$details='###DELETE_REQUEST_DETAILS_DATA###';
				$contentArray['###TITLE###'] = $objVars['data_title'];
			
				# get datafield hash
				$datafieldHash = $this->getHashFromTable("datafield","datafield_name");
				$dataContent = $obj->getDatafieldContent();
				if ($dataContent)
					{
					# get data subpart
					$dataSub=$this->cObj->getSubpart($this->template,'###DELETE_REQUEST_DETAILS_DATAROW###');
					foreach($dataContent as $key => $value) 
						{
						$contentDataArr['###HEADING_DATA_CONTENT###'] = $this->wrapInDiv($datafieldHash[$key],__FUNCTION__."-dataHeading");
						$contentDataArr['###DATA_CONTENT###'] = $value;
						$contentArray['###DATAROWS###'] .= $this->cObj->substituteMarkerArrayCached($dataSub,$contentDataArr);
						}
					} else $contentArray['###DATAROWS###'] = "";
				}
			break;
			
			case 'category':
				{
				$details='###DELETE_REQUEST_DETAILS_CATEGORY###';
				$contentArray['###CATEGORY###'] = $objVars['category_name'];
				}
			break;

			case 'datafield':
				{
				$details='###DELETE_REQUEST_DETAILS_DATAFIELD###';
				$contentArray['###DATAFIELD###'] = $objVars['datafield_name'];
				$contentArray['###DATAFIELD_TYPE###'] = $this->pi_getLL($objVars['datafield_type']);
				}
			break;
			}
		$details=$this->cObj->getSubpart($this->template,$details);
		$contentArrayCSS = $this->wrapTemplateArrayInClass($contentArray,__FUNCTION__);	
		$contentAll = array_merge($contentArrayCSS,$commons);

		$contentAll['###DETAILS###'] = $this->cObj->substituteMarkerArrayCached($details,$contentAll);
		$content = $this->cObj->substituteMarkerArrayCached($subpart,$contentAll);
		return $content;
		}

	private function showError($errorCode)
		{
		# common template subpart
		$subpart=$this->cObj->getSubpart($this->template,'###ERRORPAGE###');
		
		$commonsArray = $this->makeCommonsArray();

		$contentArray['###HEADING_ERROR###'] = $this->wrapInDiv($this->pi_getLL('error'),__FUNCTION__.'-heading');
		$contentArray['###ERRORTEXT###'] = $this->wrapInDiv($this->pi_getLL($errorCode) ? $this->pi_getLL($errorCode) : $errorCode,__FUNCTION__.'-text');
		
		$contentAll = array_merge($commonsArray,$contentArray);
		$content = $this->cObj->substituteMarkerArrayCached($subpart,$contentAll);
		return $content;			
		}

	private function isAdmin()
		{
		# if permission is already defined return
		if (defined(ADM_PERM)) return ADM_PERM;
		
		# check BE permission
		if ($GLOBALS['BE_USER']->user)
			{
			# if user is logged in as a BE-Admin grand access
			if ($GLOBALS['BE_USER']->user['admin']) return 1;
			
			# check if BE user have permission to edit the datapage
			$dataArray = t3lib_BEfunc::getRecord('pages', PID);
			if ($GLOBALS['BE_USER']->doesUserHaveAccess($dataArray,2)) return 1;
			}

		# check FE permission
		# normal user has to be logged in and on the right page
		if ($GLOBALS['TSFE']->loginUser && (PID==CURRENT_PID)) 
			{
			# get flexform data users & groups
			$flexUsers = $this->pi_getFFvalue($this->cObj->data['pi_flexform'],"users","administration");
			$flexGroups = $this->pi_getFFvalue($this->cObj->data['pi_flexform'],"groups","administration");

			# if users & groups 
			if ($flexUsers || $flexGroups)
 				{
				# array from flexform users & groups
				$flexUsersArr = explode(",",$flexUsers);
				$flexGroupsArr = explode(",",$flexGroups);

				# uid from session
				$sessUser=$GLOBALS['TSFE']->fe_user->user['uid'];
				# array aus session users & groups erstellen
				$sessGroupArr=explode(",",$GLOBALS['TSFE']->fe_user->user['usergroup']);

				# Check permission
				# is user in userlist
				foreach ($flexUsersArr as $adminUser) if ($sessUser==$adminUser) return 1;
				# is user in grouplist
				foreach ($flexGroupsArr as $adminGroup)
					{
					foreach ($sessGroupArr as $sessGroup) if ($sessGroup==$adminGroup) return 1;
					}
				}
			}
		return 0;
		}

	private function createSearchClause($searchClauseArr = array(),$concat='AND')
		{
		foreach($searchClauseArr as $index => $hashArr)
			{
			if ($hashArr[key($hashArr)]['value'])
				$searchClause.=($searchClause ? " ".$concat." ":"")."`".addslashes(key($hashArr))."` ".$hashArr[key($hashArr)]['operator']."'".addslashes($hashArr[key($hashArr)]['value'])."'";
			}
		return $searchClause ? "(".$searchClause.")" : "";	
		}

	private function getTypeListFromTable($type,$whereClause='')
		{
		$typeList = t3lib_div::makeInstance($this->prefixId.'_'.$type.'List');
		$typeList->getDS($whereClause);

		return $typeList;
		}

	private function getHashFromTable($type,$value,$key='uid',$whereClause='')
		{
		$hash = array();

		$typeList = $this->getTypeListFromTable($type,$whereClause);
		$objArr = $typeList->getProperty('objArr');
		# build hash
		foreach ($objArr as $key => $obj)
			$hash[$key] = $obj->getObjVar($value);
			
		return $hash;
		}

	private function getOptionsFromTable($type,$field,$selected='',$checkfield='uid',$whereClause='')
		{
		$options="";

		$typeList = $this->getTypeListFromTable($type,$whereClause);
		# get objArr
		$objArr = $typeList->getProperty('objArr');

		# Get options
		foreach($objArr as $key => $obj)
			{
			$objVars = $obj->getObjVar($checkfield);
			$options.='<option value="'.$obj->getObjVar($checkfield).(($obj->getObjVar($checkfield)==$selected) ? '" selected>' : '">').$obj->getObjVar($field).'</option>';
			}
		return $options;
		}

	private function getOptionsFromArr($optArr = array(),$selected='')
		{
		$options="";

		foreach ($optArr as $key => $value)
			{
			if ($value) $options.='<option value="'.$key.(($key==$selected) ? '" selected>' : '">').$value.'</option>';
			}
		return $options;
		}

	private function getUsedCategoryValues($valueField)
		{
		# returns an array of used values of the category table
		$usedHashArr = array();
		
		# get all relevant data
		$dataList = $this->getTypeListFromTable('data');
		$dataObjArr = $dataList->getProperty('objArr');

		$catList = $this->getTypeListFromTable('category');		
		$catObjArr = $catList->getProperty('objArr');

		foreach($dataObjArr as $key => $obj)
			{
			$dataCategory = $obj->getObjVar('data_category');
			if (! $usedHashArr[$dataCategory])
				{
				if ($catObjArr[$dataCategory]) $usedHashArr[$dataCategory] = $catObjArr[$dataCategory]->getObjVar($valueField);
				# get all progenitors of this category
				$categoryProgenitors = $catList->getAllProgenitors($dataCategory);
				foreach($categoryProgenitors as $progenitor)
					if ($catObjArr[$progenitor]) $usedHashArr[$progenitor] = $catObjArr[$progenitor]->getObjVar($valueField);
				}
			}
		asort($usedHashArr);

		return $usedHashArr; 
		}

	private function formatContentType($value,$type)
		{
		if (! $value) return;

		switch ($type)
			{	
			case 'date':
			$value = preg_replace('/\D/',".",$value);
			break;

			case 'email':
			$mailarr = $this->cObj->getMailTo($value);
			$value = '<a href="'.$mailarr[0].'">'.$mailarr[1].'</a>';
			break;

			case 'url':
			$value = $this->cObj->typolink($value,array('parameter' => $value,'extTarget' => '_blank'));
			break;	

			case 'bool':
			$value = $this->pi_getLL($value);
			break;
			}

		return $value;
		}	

	private function makeCommonsArray()
		{
		$commonsArray['###PI_BASE###']=$this->prefixId;
		$commonsArray['###PLUGINNAME###'] = $this->pi_getClassName('');
		$commonsArray['###SCOPE###']=base64_encode(serialize($this->scopeArr));
		$commonsArray['###ACTION_URL###']=$this->pi_getPageLink($GLOBALS['TSFE']->id);
		$commonsArray['###BACK###']=$this->pi_linkTP_keepPIvars('<img src="'.$this->picturePath.'return.png" align="top" title="'.$this->pi_getLL('back').'" alt="['.$this->pi_getLL('back').']">',array('scope' => $this->piVars['scope']),'0','1');
		$commonsArray['###CANCEL###']=$this->pi_getLL('cancel');
		$commonsArray['###SUBMIT###']=$this->pi_getLL('submit');
		$commonsArray['###YES###']=$this->pi_getLL('yes');
		$commonsArray['###NO###']=$this->pi_getLL('no');
		$commonsArray['###OK###'] = $this->pi_getLL('ok');
			
		return $commonsArray;
		}	

	private function makeHeadingsArray()
		{
		$headingsArray['###HEADING_DATA###']=$this->pi_getLL('data');
		$headingsArray['###HEADING_TITLE###']=$this->pi_getLL('title');
		$headingsArray['###HEADING_NAME###']=$this->pi_getLL('name');
		$headingsArray['###HEADING_CATEGORY###']=$this->pi_getLL('category');
		$headingsArray['###HEADING_SUBCATEGORY###']=$this->pi_getLL('subcategory');
		$headingsArray['###HEADING_DATAFIELD###']=$this->pi_getLL('datafield');
		$headingsArray['###HEADING_INPUT_DATAFIELDS###']=$this->pi_getLL('datafields');
		$headingsArray['###HEADING_FIELDNAME###']=$this->pi_getLL('fieldname');
		$headingsArray['###HEADING_TYPE###']=$this->pi_getLL('type');
		$headingsArray['###HEADING_DELETE_REQUEST###']=$this->pi_getLL('delete_request');

		return $headingsArray;
		}
		
	private function makeOptionArray()
		{
		$optionArray['###SEARCHPHRASE###'] = $this->piVars['searchphrase']; 
		$optionArray['###CATEGORY_OPTIONS###'] = "<option value=0>".$this->pi_getLL('all_categories')."</option>". $this->getOptionsFromArr($this->getUsedCategoryValues('category_name'),$this->piVars['selected_category'] ? $this->piVars['selected_category'] : 0);

		# get all searchable items
		$optionArray['###SELECTED_ITEM_OPTIONS###'] = "<option value=0>".$this->pi_getLL('all_items')."</option>"."<option value=data_title". ($this->piVars['selected_item']=='data_title' ? " selected" : "").">".$this->pi_getLL('title')."</option>". $this->getOptionsFromTable("datafield","datafield_name",$this->piVars['selected_item'],"uid","datafield_searchable='yes'");

		$optionArray['###FE-ADMINLINKS###']=$this->wrapInDiv($this->makeAdminLinks(),'optionField-adminLinks');

		$optionArray['###SUBMIT_SEARCH###']=$this->pi_getLL('show');
	
		return $optionArray;
		}

	private function makeAdminStuff($uid,$type='data')
		{
		if (ADM_PERM && $uid)
			{		
			$stuff =$this->pi_linkTP_keepPIvars('<img src="'.$this->picturePath.'edit.png" align="top" title="'.$this->pi_getLL('modify').'" alt="['.$this->pi_getLL('modify').']">',array('uid' => $uid, 'action' => 'update', 'type' => $type, 'scope' => base64_encode(serialize($this->scopeArr))),'0','1');
			
			if ($type=='datafield')
				{
				$stuff.= $this->pi_linkTP_keepPIvars('<img src="'.$this->picturePath.'button_down.gif" align="top" title="'.$this->pi_getLL('entry_down').'" alt="['.$this->pi_getLL('entry_down').']">',array('uid' => $uid, 'action' => 'update-sequence', 'type' => $type, 'direction' => 'down', 'scope' => base64_encode(serialize($this->scopeArr))),'0','1');
				$stuff.= $this->pi_linkTP_keepPIvars('<img src="'.$this->picturePath.'button_up.gif" align="top" title="'.$this->pi_getLL('entry_up').'" alt="['.$this->pi_getLL('entry_up').']">',array('uid' => $uid, 'action' => 'update-sequence', 'type' => $type, 'direction' => 'up', 'scope' => base64_encode(serialize($this->scopeArr))),'0','1');
				}	
			
			$stuff.=$this->pi_linkTP_keepPIvars('<img src="'.$this->picturePath.'trash.png" align="top" title="'.$this->pi_getLL('delete').'" alt="['.$this->pi_getLL('delete').']">',array('uid' => $uid,'action' => 'delete-request', 'type' => $type, 'scope' => base64_encode(serialize($this->scopeArr))),'0','1');

			return $stuff;
			} else return;
		}

	private function makeAdminLinks()
		{
		if (ADM_PERM)
			{
			$subpart=$this->cObj->getSubpart($this->template,'###ADMINLINKS###');

			$contentArray['###NEW_DATA###']=$this->pi_linkTP_keepPIvars('['.$this->pi_getLL('new_data').']',array('action' => 'update', 'type' => 'data', 'scope' => base64_encode(serialize($this->scopeArr))),'1','1');
			$contentArray['###NEW_CATEGORY###']=$this->pi_linkTP_keepPIvars('['.$this->pi_getLL('new_category').']',array('action' => 'update', 'type' => 'category','scope' => base64_encode(serialize($this->scopeArr))),'1','1');
			$contentArray['###NEW_DATAFIELD###']=$this->pi_linkTP_keepPIvars('['.$this->pi_getLL('new_datafield').']',array('action' => 'update', 'type' => 'datafield','scope' => base64_encode(serialize($this->scopeArr))),'1','1');
			$contentArray['###SHOW_CATEGORIES###']=$this->pi_linkTP_keepPIvars('['.$this->pi_getLL('show_categories').']',array('type' => 'category','view'=>'3','scope' => base64_encode(serialize($this->scopeArr))),'1','1');
			$contentArray['###SHOW_DATAFIELDS###']=$this->pi_linkTP_keepPIvars('['.$this->pi_getLL('show_datafields').']',array('type' => 'datafield','view'=>'3','scope' => base64_encode(serialize($this->scopeArr))),'1','1');

			return $this->cObj->substituteMarkerArrayCached($subpart,$contentArray);
			} else return;
		}	

	private function wrapTemplateArrayInClass($arr = array(),$callingFunction='')
		{
		foreach($arr as $key => $value)
			{
			eregi("^###(.*)###$",$key,$result);
			$arr[$key] = $this->wrapInDiv($value,$callingFunction."-".strtolower($result[1]));
			}
		return $arr;
		}

	private function wrapInDiv($str,$class)
		{
		return $str ? "<div".$this->pi_classParam($class).">".$str."</div>" : $str;
		}

	private function wrapInSpan($str,$class)
		{
		return $str ? "<span".$this->pi_classParam($class).">".$str."</span>" : $str;
		}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/general_data_display/pi1/class.tx_generaldatadisplay_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/general_data_display/pi1/class.tx_generaldatadisplay_pi1.php']);
}

?>
