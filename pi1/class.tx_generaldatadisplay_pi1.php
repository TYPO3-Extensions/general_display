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
require_once('class.tx_generaldatadisplay_pi1_dataFields.php');

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
		
		#t3lib_div::debug($this->piVars,'piVars');

		if (!$this->piVars) $this->piVars = array();
		
		# Init Flex form
		$this->pi_initPIflexForm();

		# define picturePath
		$this->picturePath = t3lib_extMgm::extRelPath($this->extKey).'images/';

		# globalize some values
		# prefixId
		define(PREFIX_ID,$this->prefixId);
		# get PID from current page	
		define(CURRENT_PID,$GLOBALS['TSFE']->id);
		# get pid from FlexForm if one is given
		$pid = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], "pages", "general");
		if (!$pid) $pid = CURRENT_PID;
		define(PID,$pid);
		
		# get & store permission
		define(ADM_PERM,$this->isAdmin());

		define(IMGUPLOADPATH,$this->uploadPath."/".PID);
		define(MAXIMGSIZE,isset($this->conf['maxImageSize']) ? (int)$this->conf['maxImageSize'] : 100000);

		# use configured css, if none is given use standard stylesheet
		$userStyleFile = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], "userStyleSheet","general");
		$cssFile = $userStyleFile ? $this->uploadPath."/".$userStyleFile : t3lib_extMgm::extRelPath($this->extKey).'css/default.css';
		# put stylesheet in Header
		$GLOBALS['TSFE']->additionalHeaderData[$this->extKey] = '<link rel="stylesheet" href="'.$cssFile.'" type="text/css" />';

		# use configured templates, if none is given use standard template
		$userTmplFile = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], "userTemplateFile","general");
		$templateFile = $userTmplFile ? $this->uploadPath."/".$userTmplFile : "EXT:".$this->extKey."/templates/template.html"; 

		# define template
		define(TEMPLATE,$this->cObj->fileResource($templateFile));

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

		# get all datafields & create hash
		$datafields = $this->getTypeListFromTable('datafield');
		$objArr = $datafields->getProperty('objArr');
		foreach($objArr as $key => $obj)
			{
			# create hash
			$metadata = tx_generaldatadisplay_pi1_dataFields::getMetadata($key);
			$datafieldHash[$obj->getObjVar('uid')] = array('name' => $obj->getObjVar('datafield_name'),'searchable' => ($metadata['datafield_searchable']=="yes" ? 'yes' : 'no'));
			}

		# add title to nameHash
		$datafieldHash['data_title'] = array('name' => 'data_title','searchable' => 'yes');

		# set searchClauseArr with selected values
		if ($this->piVars['selected_item']) 
			{
			if ($this->piVars['searchphrase'])
				$searchClause['searchphrase'][]=array($datafieldHash[$this->piVars['selected_item']]['name'] => array('value' =>$this->piVars['searchphrase'],'operator'=> 'rlike'));
			} 
		elseif ($this->piVars['searchphrase']) 
			{
			# search over all searchable fields (depending on their searchable flag)
			foreach ($datafieldHash as $key => $arr)
				if ($arr['searchable']=="yes")
					$searchClause['searchphrase'][]=array($arr['name'] => array('value' => $this->piVars['searchphrase'],'operator'=> 'rlike'));	
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
		
		# check piVars - unset piVar if test fails
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
				$formData = t3lib_div::makeInstance(PREFIX_ID.'_'.$this->piVars['type'].'Form');

				if ($this->piVars['submit'])
					{
					$dataArr = $formData->importValues($this->piVars);
						
					if (!$formData->formError())
						{
						$db = t3lib_div::makeInstance(PREFIX_ID.'_'.$this->piVars['type']);
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
					if ($this->piVars['uid']) # existing DS
						{
						$dataSet = t3lib_div::makeInstance(PREFIX_ID.'_'.$this->piVars['type'].'List');
						$objArr = $dataSet->getDS('uid='.$this->piVars['uid']);
						$formData->importValues($objArr[$this->piVars['uid']]->getProperty('objVars'),$this->piVars);
						} else 
						{
						# new dataset
						$formData->importValues($this->piVars);
						}
					$content = $this->editView($formData);	
					}
				}
			break;

			case 'update-sequence':
				{
				if (ADM_PERM && $this->piVars['uid'])
					{
					$datafields = t3lib_div::makeInstance(PREFIX_ID.'_datafieldList');
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
					$dataSet = t3lib_div::makeInstance(PREFIX_ID.'_'.$this->piVars['type'].'List');
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
		$typeList = $this->getTypeListFromTable($type);

		# get typelist
		$objArr = $typeList->getProperty('objArr');
	
		# Use subpart
		$subpart=$this->cObj->getSubpart(TEMPLATE,'###TABLE-VIEW###');

		# Table entry
		$singlerow=$this->cObj->getSubpart(TEMPLATE,'###TABLE-CONTENT###');

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
				$optionField=$this->cObj->getSubpart(TEMPLATE,'###OPTION_FIELD###');
				$contentArray['###OPTIONS###'] = $this->cObj->substituteMarkerArrayCached($optionField,$commons);

				# use template subpart
				$subpart=$this->cObj->getSubpart(TEMPLATE,'###LIST-DATAVIEW###');
				$subsubpart=$this->cObj->getSubpart(TEMPLATE,'###LIST-DATA###');

				# instantiate typelist
				$typeList = $this->getTypeListFromTable($type,$this->searchClause);
				$objArr = $typeList->getProperty('objArr');

				# count results
				$nrResults = count($objArr);

				$contentArray['###HITS###'] = "(".$nrResults." ".$this->pi_getLL('hits').")";	

				# defined display limit ?
				if ($nrPageResults && $nrPageResults < $nrResults)
					{
					$offset = intval($this->piVars['offset']);
					$range =  $offset ? ($offset.",".($offset + $nrPageResults)) : $nrPageResults;
					$objArr = $typeList->getDS($this->searchClause,$range);

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
					} else $contentArray['###PAGELINKS###'] = "";
					
				# get list of all categories
				$categoryList = $this->getTypeListFromTable('category');
				$catObjArr = $categoryList->getProperty('objArr');	

				# get a list of all used categories and progenitors of this view			
				foreach($objArr as $key => $obj)
						{
						$dataCategory = $obj->getObjVar('data_category');
						$category[$dataCategory] = 1;
						$catProgenitors = $categoryList->getAllProgenitors($dataCategory);
						foreach($catProgenitors as $catProgenitor)
							$category[$catProgenitor] = 1; 
						}

				$categorySortHash[0] = 0; # no category 				
		
				# build sortHash
				foreach (array_keys($category) as $key)
					 {
					 $categoryRanking[$key] = $categorySortHash[$key] = ++$inc;
					 }

				foreach(array_keys($category) as $key)
					{
					$catProgenitors = $categoryList->getAllProgenitors($key);
					foreach($catProgenitors as $catProgenitor)
						$categorySortHash[$key] = $categoryRanking[$catProgenitor].$categorySortHash[$key]; 
					 # get category level
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
				foreach(array_keys($categorySortHash) as $dataCategory)
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
				$subpart=$this->cObj->getSubpart(TEMPLATE,'###DETAIL-DATAVIEW###');
				$subsubpart=$this->cObj->getSubpart(TEMPLATE,'###DETAIL-DATA###');

				# instantiate typelist
				$typeList = $this->getTypeListFromTable($type,'uid='.$uid);

				# get typelist
				$objArr = $typeList->getProperty('objArr');

				# get list of categories
				$categoryList = $this->getTypeListFromTable('category');
				$catObjArr = $categoryList->getProperty('objArr');

				if ($objArr[$uid])
					{
					$objVars =  $objArr[$uid]->getProperty('objVars');
					$contentArray['###ADMINSTUFF###'] = $this->makeAdminStuff($uid);
					$contentArray['###CATEGORY-NAME###'] = is_object($catObjArr[$objVars['data_category']]) ?  $catObjArr[$objVars['data_category']]->getObjVar('category_name') : "";
					$contentArray['###DATA_TITLE###']=$objVars['data_title'];
					$contentArray['###DETAILDATA###'] ="";

					# get data fields ...
					$dataContentList = $this->getTypeListFromTable('datacontent','tx_generaldatadisplay_datacontent.data_uid='.$uid);
					$dataContentObjArr = $dataContentList->getProperty('objArr');
					
					# & fill template
					foreach($dataContentObjArr as $key => $obj) 
						{
						$fieldName = $obj->getObjVar('datafield_name');
						$value = $this->formatContentType($obj);
						# get metadata
						$metadata = tx_generaldatadisplay_pi1_dataFields::getMetadata($obj->getObjVar('datafields_uid'));

						if ($value && $metadata['content_visible']!="no")
							{
							# standard template uses Detaildata - but you can also use your own template & "real" names
							$contentArray['###HEADING_'.strtoupper($fieldName).'###'] = $this->wrapInDiv($fieldName,__FUNCTION__."-dataHeading");
							$contentArray['###'.strtoupper($key).'###'] = $this->wrapInDiv($this->pi_getLL($value) ? $this->pi_getLL($value) : $value,__FUNCTION__."-dataContent");	
							$contentArray['###HEADING_DATACONTENT###'] = $this->wrapInDiv($fieldName,__FUNCTION__."-dataHeading");
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
		$formValues = $formData->getProperty('dataArr');
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
				$contentArray[$templKeyword]=$this->wrapInDiv($this->pi_getLL('error_'.$value),__FUNCTION__.'-formerror');
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
				# set contentArray
				$contentArray['###DATA_TITLE###']=$formValues['data_title'];	
				$categoryOptions = $this->getOptionsFromTable('category','category_name',$formValues['data_category']);
				$contentArray['###DATA_CATEGORY_OPTIONS###'] = $categoryOptions ? $categoryOptions : '<option value="">'.$this->pi_getLL('empty_category').'</option>';
				
				if (! $objArr) $contentArray['###INPUT_DATAFIELDS###']=$this->pi_getLL('no_datafields'); 
				
				foreach($objArr as $key => $obj)
					{
					# get datafield
					$objVars = $obj->getProperty('objVars');
					$dataField = t3lib_div::makeInstance(PREFIX_ID.'_'.$objVars['datafield_type']);
					$dataField->setTmplArr($contentArray);
					$dataField->setTmplVar('###DATAFIELD_NAME###',$objVars['datafield_name']);
					$dataField->setTmplVar('###HEADING_DATAFIELD###',$this->wrapInDiv($objVars['datafield_name'],__FUNCTION__."-dataHeading"));
					$dataField->setTmplVar('###DATAFIELD_CONTENT###',$formValues[$objVars['datafield_name']]);
					$dataField->setTmplVar('###DATAFIELD_CONTENT_ERROR###',$formError[$objVars['datafield_name']] ? $this->wrapInDiv($this->pi_getLL('error_'. $formError[$objVars['datafield_name']]),'editView-Formerror') : "");

					$contentArray['###INPUT_DATAFIELDS###'].= $dataField->HTML();
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
			
				$contentArray['###DATAFIELD_NAME###']=$formValues['datafield_name'];
				$contentArray['###DISPLAY_SEQUENCE###'] = $formValues['display_sequence'] ? $formValues['display_sequence'] : time();
				# get all datafieldtypes 
				$types = tx_generaldatadisplay_pi1_dataFields::getTypes();

				# choose type - if not submitted use first available
				$datafieldType = $formValues['datafield_type'] ? $formValues['datafield_type'] : $types[key($types)];
				$contentArray['###DATAFIELD_TYPE_OPTIONS###'] = $this->getOptionsFromArr($types,$formValues['datafield_type'],true);

				$dataField = t3lib_div::makeInstance(PREFIX_ID.'_'.$datafieldType);
				$dataField->setTmplArr($contentArray);

				# select datafield configuration
				switch ($formValues['datafield_type'])
					{	
					case 'img':
						{
						$dataField->setTmplVar('###HEADING_IMGSIZE###',$this->pi_getLL('img_size'));
						$dataField->setTmplVar('###HEADING_IMGALIGN###',$this->pi_getLL('img_align'));
						$dataField->setTmplVar('###CONTENT_IMGSIZE_X###',$formValues['meta']['img_size_x']);
						$dataField->setTmplVar('###CONTENT_IMGSIZE_Y###',$formValues['meta']['img_size_y']);
						$configArr = $dataField->getProperty('config');
						$dataField->setTmplVar('###IMGALIGN_OPTIONS###',$this->getOptionsFromArr($configArr['imgAlign'],$formValues['meta']['img_align'],true));
						}
					break;
					
					default:
						{
						$dataField->setTmplVar('###HEADING_REQUIRED###',$this->pi_getLL('required'));
						$dataField->setTmplVar('###HEADING_SEARCHABLE###',$this->pi_getLL('searchable'));
						$dataField->setTmplVar('###HEADING_CONTENT_VISIBLE###',$this->pi_getLL('visible'));
						$dataField->setTmplVar('###DATAFIELD_REQUIRED###',$formValues['meta']['datafield_required']=='yes' ? 'checked="checked"' : '');
						$dataField->setTmplVar('###DATAFIELD_SEARCHABLE###',$formValues['meta']['datafield_searchable']=='yes' ? 'checked="checked"' : '');
						$dataField->setTmplVar('###CONTENT_VISIBLE###',$formValues['meta']['content_visible']=='yes' ? 'checked="checked"' : '');
						}
					break;
					}

				$datafieldConf = $this->cObj->getSubpart(TEMPLATE,$datafieldConfPart);
				$contentArray['###DATAFIELD_CONFIG###'] = $this->wrapInDiv($dataField->HTML('config'),__FUNCTION__."-datafieldConfig");
				}
			break;
			}
		# choose subpart of template
		$subpart=$this->cObj->getSubpart(TEMPLATE,$subpart);
		$content = $this->cObj->substituteMarkerArrayCached($subpart,$contentArray);
		return $content;
		}

	private function deleteRequest($type='data')
		{
		$dataSet = t3lib_div::makeInstance(PREFIX_ID.'_'.$type.'List');
		$objArr = $dataSet->getDS('uid='.$this->piVars['uid']);

		$obj = $objArr[$this->piVars['uid']];
			
		# common template subpart
		$subpart=$this->cObj->getSubpart(TEMPLATE,'###DELETE_REQUEST###');

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
				# get dataContent
				$dataContentList = $this->getTypeListFromTable('datacontent','tx_generaldatadisplay_datacontent.data_uid='.$this->piVars['uid']);
				$dataContentObjArr = $dataContentList->getProperty('objArr');
					
				foreach($dataContentObjArr as $key => $obj) $dataContent[$obj->getObjVar('datafield_name')] = $obj->getObjVar('datacontent');

				if ($dataContent)
					{
					# get data subpart
					$dataSub=$this->cObj->getSubpart(TEMPLATE,'###DELETE_REQUEST_DETAILS_DATAROW###');
					foreach($dataContent as $key => $value) 
						{
						$contentDataArr['###HEADING_DATA_CONTENT###'] = $this->wrapInDiv($key,__FUNCTION__."-dataHeading");
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
		$details=$this->cObj->getSubpart(TEMPLATE,$details);
		$contentArrayCSS = $this->wrapTemplateArrayInClass($contentArray,__FUNCTION__);	
		$contentAll = array_merge($contentArrayCSS,$commons);

		$contentAll['###DETAILS###'] = $this->cObj->substituteMarkerArrayCached($details,$contentAll);
		$content = $this->cObj->substituteMarkerArrayCached($subpart,$contentAll);
		return $content;
		}

	private function showError($errorCode)
		{
		# common template subpart
		$subpart=$this->cObj->getSubpart(TEMPLATE,'###ERRORPAGE###');
		
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
		$typeList = t3lib_div::makeInstance(PREFIX_ID.'_'.$type.'List');
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

	private function getOptionsFromArr($optionArr = array(),$selected='',$locale=false)
		{
		$options="";

		foreach ($optionArr as $key => $value)
			{
			$value = $locale ? $this->pi_getLL($value) : $value;
			if ($value) 
				$options.='<option value="'.$key.(($key==$selected) ? '" selected>' : '">').$value.'</option>';
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

	private function formatContentType($obj)
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
			}

		return $content;
		}

	private function makeCommonsArray()
		{
		$commonsArray['###PI_BASE###']= PREFIX_ID;
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
		$headingsArray['###HEADING_DATAFIELD_CONFIG###'] = $this->pi_getLL('datafield_config');
		$headingsArray['###HEADING_DELETE_REQUEST###']=$this->pi_getLL('delete_request');

		return $headingsArray;
		}
		
	private function makeOptionArray()
		{
		# get all searchable items and build option array
		# first elements which are not in table
		$datafieldOptionArr = array('0' => $this->pi_getLL('all_items'),'data_title' => $this->pi_getLL('title'));

		$datafields = $this->getTypeListFromTable('datafield');
		$datafieldArr = $datafields->getProperty('objArr');

		foreach($datafieldArr as $key => $obj)
			{
			# get metadata of datafield
			$metadata = tx_generaldatadisplay_pi1_dataFields::getMetadata($key);
			if ($metadata['datafield_searchable']=="yes") $datafieldOptionArr[$key] = $obj->getObjVar('datafield_name'); 
			}
		$optionArray['###SELECTED_ITEM_OPTIONS###'] = $this->getOptionsFromArr($datafieldOptionArr,$this->piVars['selected_item']);

		# build categories options
		$categoryOptionArr = array('0' => $this->pi_getLL('all_categories')) + $this->getUsedCategoryValues('category_name');
		$optionArray['###CATEGORY_OPTIONS###'] = $this->getOptionsFromArr($categoryOptionArr,$this->piVars['selected_category']);

		$optionArray['###SEARCHPHRASE###'] = $this->piVars['searchphrase']; 

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
			$subpart=$this->cObj->getSubpart(TEMPLATE,'###ADMINLINKS###');

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
			preg_match("/^###(.*)###$/",$key,$result);
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
