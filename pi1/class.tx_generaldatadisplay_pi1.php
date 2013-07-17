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
require_once('class.tx_generaldatadisplay_pi1_objVar.php');
require_once('class.tx_generaldatadisplay_pi1_objClause.php');
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
		
		# t3lib_utility_Debug::debug($this->piVars,'piVars'); 
		
		# Init Flex form
		$this->pi_initPIflexForm();

		# globalize some values
		define(PREFIX_ID,$this->prefixId);
		# define PID of current page	
		define(CURRENT_PID,$GLOBALS['TSFE']->id);
		define(DATA_PID,$this->getConfigValue('dataPID','int',CURRENT_PID,'general'));
		define(LIST_PID,$this->getConfigValue('listViewPID','int',CURRENT_PID,'general'));
		define(DETAIL_PID,$this->getConfigValue('detailViewPID','int',CURRENT_PID,'general'));

		# get & store permission
		define(ADM_PERM,$this->isAdmin());

		# use configured templates, if none is given use standard template
		$userTmplFile = $this->getConfigValue('userTemplateFile','string','','general');
		$templateFile = $userTmplFile ? $this->uploadPath."/".$userTmplFile : "EXT:".$this->extKey."/templates/template.html"; 

		# define template
		define(TEMPLATE,$this->cObj->fileResource($templateFile));

		# set img upload path
		define(IMGUPLOADPATH,$this->uploadPath."/".DATA_PID);

		# max upload image size
		define(MAXIMGSIZE,$this->getConfigValue('maxImageSize','int',500000));

		# define picturePath
		define(PICTURE_PATH,t3lib_extMgm::siteRelPath($this->extKey).'images/');

		# set some other config vars
		$this->conf['viewMode'] = $this->getConfigValue('viewMode','int',1,'general');
		$this->conf['initialNoResults'] = $this->getConfigValue('initialNoResults','bool',false,'listview');
		$this->conf['withoutBrowseList'] = $this->getConfigValue('withoutBrowseList','bool',false,'listview');

		# use configured css, if none is given use standard stylesheet
		$userStyleFile = $this->getConfigValue('userStyleSheet','string','','general');
		$cssFile = $userStyleFile ? $this->uploadPath."/".$userStyleFile : t3lib_extMgm::siteRelPath($this->extKey).'css/default.css';

		# put stylesheet in Header
		$GLOBALS['TSFE']->additionalHeaderData[$this->extKey] = '<link rel="stylesheet" href="'.$cssFile.'" type="text/css" />';

		# if no piVars set -> set to empty array
		if (!$this->piVars) $this->piVars = array();

		# save piVars as secureable objVars
		$this->secPiVars = t3lib_div::makeInstance(PREFIX_ID.'_objVar');
		$this->secPiVars->set($this->piVars);
		# get scope
		$scopeArr = t3lib_div::makeInstance(PREFIX_ID.'_objVar');
		$scopeArr->set(unserialize($this->sessionData('scope')));

		# get values from scope if no search is given
		if ($this->secPiVars->get('action') != 'search')
			{
			$this->secPiVars->setValue('selected_item',$scopeArr->get('selected_item'));
			$this->secPiVars->setValue('searchphrase',$scopeArr->get('searchphrase'));
			$this->secPiVars->setValue('selected_category',$scopeArr->get('selected_category'));
			}
		if ($this->secPiVars->get('offset') == null) $this->secPiVars->setValue('offset',$scopeArr->get('offset'));
		if ($this->secPiVars->get('selected_letter') == null) $this->secPiVars->setValue('selected_letter',$scopeArr->get('selected_letter'));

		# set scopeArray
		$scopeArr->setValue('selected_item', $this->secPiVars->get('selected_item'));
		$scopeArr->setValue('selected_category', $this->secPiVars->get('selected_category'));
		$scopeArr->setValue('selected_letter', $this->secPiVars->get('selected_letter'));
		$scopeArr->setValue('searchphrase', $this->secPiVars->getplain('searchphrase'));
		$scopeArr->setValue('offset', $this->secPiVars->get('offset'));
		# get all datafields & create hash
		$datafieldList = t3lib_div::makeInstance(PREFIX_ID.'_datafieldList');
		$objArr = $datafieldList->getDS();

		foreach($objArr as $key => $obj)
			{
			# create hash
			$metadata = tx_generaldatadisplay_pi1_dataFields::getMetadata($key);
			$datafieldHash[$obj->getObjVar('uid')] = array('name' => $obj->getObjVar('datafield_name',true),'searchable' => ($metadata['datafield_searchable']=="yes" ? 'yes' : 'no'));
			}

		# add title to nameHash
		$datafieldHash['data_title'] = array('name' => 'data_title','searchable' => 'yes');

		# set searchClauseArr with selected values
		# instantiate objClause
		$this->searchClause = t3lib_div::makeInstance(PREFIX_ID.'_objClause');

		if ($this->secPiVars->get('selected_item')) 
			{
			if ($this->secPiVars->get('searchphrase'))
				{
				# explode searchstring to get AND terms
				$searchphraseArr = preg_split('/\s+/',$this->secPiVars->getplain('searchphrase'));
			
				foreach ($searchphraseArr as $key => $searchphrase)
					if ($searchphrase)
						$this->searchClause->addOR($datafieldHash[$this->secPiVars->get('selected_item')]['name'],$searchphrase,'rlike');
				}
			}
		elseif ($this->secPiVars->get('searchphrase')) 
			{
			# explode searchstring to get AND terms
			$searchphraseArr = preg_split('/\s+/',$this->secPiVars->getplain('searchphrase'));
			
			# search over all searchable fields (depending   on their searchable flag)
			foreach ($datafieldHash as $key => $arr)
				if ($arr['searchable']=="yes")
					{
					foreach ($searchphraseArr as $key => $searchphrase)
						$this->searchClause->addOR($arr['name'],$searchphrase,'rlike');
					}
			}

		if ($this->secPiVars->get('selected_category')) 
			{
			# first add this category
			$this->searchClause->addAND('data_category',$this->secPiVars->get('selected_category'),'=','OR');
			# find all dependend categories
			$categoryList = t3lib_div::makeInstance(PREFIX_ID.'_categoryList');
			$categoryList->getDS();

			$usedCategoryProgenitor = $categoryList->getUsedCategoryValues('category_progenitor');
			
			foreach($usedCategoryProgenitor as $key => $value)
				{
				# get all progenitors from category
				$allProgenitors = $categoryList->getAllProgenitors($key);
				foreach($allProgenitors as $progenitor)
					{
					if ($progenitor == $this->secPiVars->get('selected_category')) 
						$this->searchClause->addAND('data_category',$key,'=','OR');
					}
				}
			
			}

		if ($this->secPiVars->get('selected_letter'))
			{
			switch($this->secPiVars->get('selected_letter'))
				{
				case 'all':
				break;

				case '0-9':
				$this->searchClause->addAND('data_title','^[0-9].*','rlike','AND');
				break;

				default:
				$this->searchClause->addAND('data_title','^'.$this->secPiVars->get('selected_letter').'.*','rlike','AND');
				}
			}

		if ($this->secPiVars->get('search_reset'))
			{
			$this->secPiVars->reset();
			$scopeArr->reset();
			$this->searchClause->reset();
			}

		# save scopeArray in session
		$this->sessionData('scope',serialize($scopeArr->get()),'true');


		# unset action if cancel is pressed
		if ($this->secPiVars->get('cancel')) $this->secPiVars->delKey('action');

		# check some piVars
		foreach ($this->secPiVars->get() as $key => $value)
			{
			switch (true)
				{
				case $key=='type':
					{
					if (! preg_match('/^(data|category|datafield)$/',$this->secPiVars->get($key)))
						$this->secPiVars->setValue($key,'data');
					}
				break;

				case $key=='selected_item':
					{
					if (is_numeric($value) || $value == 'data_title') 
						$this->secPiVars->setValue($key,is_numeric($value) ? intval($this->secPiVars->get($key)) : $this->secPiVars->get($key));
					else $this->secPiVars->delKey($key);
					}
				break;

				case $key=='selected_letter':
					{
					if (!preg_match('/^([A-Z]|0\-9|all|)$/',$this->secPiVars->get($key)))
						$this->searchClause->reset();
					}
				break;

				case preg_match('/^(uid|selected_category|offset)$/',$key):
					{
					if (is_numeric($value)) $this->secPiVars->setValue($key,intval($this->secPiVars->get($key)));
					else $this->secPiVars->delKey($key);
					}
				break;
				}
			}

		# choose action
		switch ($this->secPiVars->get('action'))
			{
			case 'update':
				{
				$formData = t3lib_div::makeInstance(PREFIX_ID.'_'.$this->secPiVars->get('type').'Form');

				if ($this->secPiVars->get('submit'))
					{
					$data = $formData->importValues($this->secPiVars);
					
					if (!$formData->formError())
						{
						$db = t3lib_div::makeInstance(PREFIX_ID.'_'.$this->secPiVars->get('type'));
						$db->setProperty("objVars",$data);
			
						if ($this->secPiVars->get('uid'))
							{
							$db->setProperty("uid",$this->secPiVars->get('uid'));
							$result = $db->updateDS();
							} else $result = $db->newDS();
						$content = $result ? $this->view() : $this->showError('dberror_permission');
						} else $content = $this->editView($formData);
					} else 
					{
					if ($this->secPiVars->get('uid')) # existing DS
						{
						# instantiate data list
						$dataSet = t3lib_div::makeInstance(PREFIX_ID.'_'.$this->secPiVars->get('type').'List');
						# instantiate an set clauseObj
						$clauseObj = t3lib_div::makeInstance(PREFIX_ID.'_objClause');
						$clauseObj->addAND('uid',$this->secPiVars->get('uid'),'=');
						$objArr = $dataSet->getDS($clauseObj);

						if (count($objArr))
							$formData->importValues($objArr[$this->secPiVars->get('uid')]->getProperty('objVars'),$this->secPiVars);
						} else 
						{
						# new dataset
						$formData->importValues($this->secPiVars);
						}
					$content = $this->editView($formData);	
					}
				}
			break;

			case 'update-sequence':
				{
				if ($this->secPiVars->get('uid'))
					{
					$datafields = t3lib_div::makeInstance(PREFIX_ID.'_datafieldList');
					$objArr = $datafields->getDS();

					if ($this->secPiVars->get('direction') == 'up')
						{
						# get predecessor uid
						foreach($objArr as $key => $obj)
							{
							# get objVars
							$objVars = $obj->getProperty('objVars');
							if ($this->secPiVars->get('uid') == $objVars->get('uid')) break;
							$savedUid = $objVars->get('uid');
							}
						} else {
						# get successor uid
						foreach($objArr as $key => $obj)
							{
							# get objVars
							$objVars = $obj->getProperty('objVars');
							if ($savedUid)
								{
								$savedUid = $objVars->get('uid'); 
								break;
								}
							if ($this->secPiVars->get('uid') == $objVars->get('uid')) $savedUid = $objVars->get('uid');
							}
						}
					if ($savedUid && $savedUid != $this->secPiVars->get('uid'))
						{
						$objVars1 = $objArr[$this->secPiVars->get('uid')]->getProperty('objVars');
						$objVars2 = $objArr[$savedUid]->getProperty('objVars');

						# swap sequence
						$saveValue = $objVars1->get('display_sequence');
						$objVars1->setValue('display_sequence',$objVars2->get('display_sequence'));
						$objVars2->setValue('display_sequence',$saveValue);
						$objArr[$this->secPiVars->get('uid')]->setProperty('objVars',$objVars1);
						$objArr[$savedUid]->setProperty('objVars',$objVars2);

						# update
						$objArr[$this->secPiVars->get('uid')]->updateDS();
						$objArr[$savedUid]->updateDS();
						}
					}
				$content = $this->tableView($this->secPiVars->get('type'));
				}
			break;
		
			case 'delete-request':
				if ($this->secPiVars->get('uid'))
				$content = $this->deleteRequest($this->secPiVars->get('type'));
				else $content = $this->showError('error_missing_uid');
			break;

			case 'delete':
				{
				if ($this->secPiVars->get('uid'))
					{
					$dataSet = t3lib_div::makeInstance(PREFIX_ID.'_'.$this->secPiVars->get('type').'List');
					# instantiate an set clauseObj
					$clauseObj = t3lib_div::makeInstance(PREFIX_ID.'_objClause');
					$clauseObj->addAND('uid',$this->secPiVars->get('uid'),'=');
					$objArr = $dataSet->getDS($clauseObj);

					if ($objArr)
						{
						$obj = $objArr[$this->secPiVars->get('uid')];
						$content = $obj->deleteDS() ? $this->view() : $this->showError('dberror_permission');
						} else $content=$this->showError('dberror_no_dataset');
					} else $content = $this->showError('error_missing_uid');
				}
			break;

			default:
				$content = $this->view();
			}
		return $this->pi_wrapInBaseClass($content);
		}

	

	private function view()
		{
		if (!$this->secPiVars->get('view') && $this->conf['viewMode'] <= 2) $this->secPiVars->setValue('view',$this->conf['viewMode']);
		# get view
		switch ($this->secPiVars->get('view'))
			{
			case 1: # listview
			$content = $this->listView($this->secPiVars->get('type'));
			break;

			case 2: # singleview
			$content = $this->singleView($this->secPiVars->get('uid'));
			break;

			case 3: # tableview
			$content = $this->tableView($this->secPiVars->get('type'));
			break;

			default:
			$content = $this->listView();
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

		# get data from typelist
		$typeList = t3lib_div::makeInstance(PREFIX_ID.'_'.$type.'List');
		$objArr = $typeList->getDS();
	
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
						$contentArray['###TABLE_ROW###'] = $this->wrapInTag($obj->getObjVar('category_name'),__FUNCTION__."-categorylvl".$obj->getObjVar('level'),'span');
						$contentArray['###ADMINSTUFF###'] = $this->makeAdminStuff($key,$type);
						$contentArrayCSS = $this->wrapTemplateArrayInClass($contentArray,__FUNCTION__);
						$contentAll['###TABLE_CONTENT###'].= $this->cObj->substituteMarkerArrayCached($singlerow,$contentArrayCSS);
						}
					} else $contentAll['###TABLE_CONTENT###']="";
				$contentAll['###TABLE_HEADING###'] = $this->wrapInTag($this->getLL('categories'),__FUNCTION__.'-heading');
				$createLink = $this->wrapInTag($this->pi_linkTP_keepPIvars('<img src="'.PICTURE_PATH.'create.png" title="'.$this->getLL('new_category').'" alt="['.$this->getLL('new_category').']" />',array('action' => 'update', 'type' => 'category'),'1','1'),__FUNCTION__.'-admincreate');
				$contentAll['###ADMIN_CREATE###'] = ADM_PERM ? $createLink : '';
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
						$contentArray['###TABLE_ROW###'] = $obj->getObjVar('datafield_name');
						$contentArray['###ADMINSTUFF###'] = $this->makeAdminStuff($key,$type);
						$contentArrayCSS = $this->wrapTemplateArrayInClass($contentArray,__FUNCTION__);
						$contentAll['###TABLE_CONTENT###'].= $this->cObj->substituteMarkerArrayCached($singlerow,$contentArrayCSS);
						}
					} else $contentAll['###TABLE_CONTENT###']="";
				$contentAll['###TABLE_HEADING###'] = $this->wrapInTag($this->getLL('datafields'),__FUNCTION__.'-heading');	
				$createLink = $this->wrapInTag($this->pi_linkTP_keepPIvars('<img src="'.PICTURE_PATH.'create.png" title="'.$this->getLL('new_datafield').'" alt="['.$this->getLL('new_datafield').']" />',array('action' => 'update', 'type' => 'datafield'),'1','1'),__FUNCTION__.'-admincreate');
				$contentAll['###ADMIN_CREATE###'] = ADM_PERM ? $createLink : '';
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

		$nrPageResults = $this->getConfigValue('nrPageResults','int',0,'listview');
		$nrMaxPages = $this->getConfigValue('nrMaxPages','int',5,'listview');

		switch ($type)
			{	
			default:
				{
				# build option menu
				$optionField=$this->cObj->getSubpart(TEMPLATE,'###OPTION_FIELD###');

				# include browselist
				$browseList = $this->makeBrowseList(array('0-9','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','all'),'selected_letter',__FUNCTION__,'|',$this->secPiVars->get('selected_letter'));
				$commons['###BROWSELIST###'] = !$this->conf['withoutBrowseList'] ? $this->wrapInTag($browseList,"optionfield-browselist") : '';
				$commons['###HEADING_SEARCH###'] = $this->wrapInTag($this->getLL('search'),'heading_search');
				$commons['###SEARCH_RESET###'] = $this->wrapInTag($this->pi_linkTP_keepPIvars('<img src="'.PICTURE_PATH.'reset.png" title="'.$this->getLL('reset').'" alt="['.$this->getLL('reset').']" />',array('search_reset' => 1),'1','1',LIST_PID),'optionfield-reset','');
				$contentArray['###OPTIONFIELD###'] = $this->cObj->substituteMarkerArrayCached($optionField,$commons);

				# no initial data display ?
				if 	($this->conf['initialNoResults'] == 1 && 
					!$this->secPiVars->get('action') && 
					!$this->secPiVars->get('back') &&
					!$this->secPiVars->get('selected_letter') &&
					$this->secPiVars->get('offset') == ''
					) 
					{
					# use template subpart
					$subpart=$this->cObj->getSubpart(TEMPLATE,'###SEARCHONLY###');
					return $this->cObj->substituteMarkerArrayCached($subpart,$contentArray);
					}

				# use template subpart
				$subpart=$this->cObj->getSubpart(TEMPLATE,'###LIST-DATAVIEW###');
				$subsubpart=$this->cObj->getSubpart(TEMPLATE,'###LIST-DATA###');

				# instantiate datalist
				$dataList = t3lib_div::makeInstance(PREFIX_ID.'_dataList');
				
				$objArr = $dataList->getDS($this->searchClause);

				# count results
				$nrResults = count($objArr);

				$contentArray['###HITS###'] = "(".$nrResults." ".$this->getLL('hits').")";	

				# defined display limit ?
				if ($nrPageResults && $nrPageResults < $nrResults)
					{
					$offset = intval($this->secPiVars->get('offset'));
					$range =  $offset ? ($offset.",".$nrPageResults) : $nrPageResults;
					$objArr = $dataList->getDS($this->searchClause,$range);

					$index = intval($offset / ($nrPageResults * $nrMaxPages));
					$from = $index*$nrMaxPages*$nrPageResults;
					$to = ($from + $nrMaxPages*$nrPageResults) > $nrResults ? $nrResults : $from + $nrMaxPages*$nrPageResults;
					if ($from > 0) $contentArray['###PAGELINKS###'] = $this->wrapInTag($this->pi_linkTP_keepPIvars("<<",array('offset' => $from-$nrPageResults, 'type' =>'data'),'1','1'),__FUNCTION__."-pageLink","");
					if ($offset) $contentArray['###PAGELINKS###'] .= $this->wrapInTag($this->pi_linkTP_keepPIvars("<",array('offset' => $offset - $nrPageResults, 'type' =>'data'),'1','1'),__FUNCTION__."-pageLink","");
					for ($i=$from; $i < $to; $i=$i+$nrPageResults)
						{
						$index1 = $i;
						$index2 = ($i+$nrPageResults-1) >= $nrResults ? $nrResults-1 : $i+$nrPageResults-1;
						$contentArray['###PAGELINKS###'] .= $this->secPiVars->get('offset')!=$index1 ? 
							$this->wrapInTag($this->pi_linkTP_keepPIvars("[".($index1==$index2 ? ($index1+1) : ($index1+1)."-".($index2+1))."]",array('offset' => $index1, 'type' =>'data'),'1','1'),__FUNCTION__."-pageLink","") :
							$this->wrapInTag("[".($index1==$index2 ? ($index1+1) : ($index1+1)."-".($index2+1))."]",__FUNCTION__."-pageLink-active","span");
						}
					if (($offset + $nrPageResults) < $nrResults) $contentArray['###PAGELINKS###'] .= $this->wrapInTag($this->pi_linkTP_keepPIvars(">",array('offset' => $offset + $nrPageResults, 'type' =>'data'),'1','1'),__FUNCTION__."-pageLink","");
					if ($index2 < $nrResults-1) $contentArray['###PAGELINKS###'] .= $this->wrapInTag($this->pi_linkTP_keepPIvars(">>",array('offset' => $index2+1, 'type' =>'data'),'1','1'),__FUNCTION__."-pageLink",""); 
					} else $contentArray['###PAGELINKS###'] = "";
					
				# get list of all categories
				$categoryList = t3lib_div::makeInstance(PREFIX_ID.'_categoryList');
				$catObjArr = $categoryList->getDS();

				# get a list of all used categories and progenitors of this view
				$category = array();
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
					}
				asort($categorySortHash,SORT_STRING);

				# build result list
				foreach($objArr as $key => $obj)
						{
						$dataCategory = $catObjArr[$obj->getObjVar('data_category')] ? $obj->getObjVar('data_category') : 0;
						$orderedList[$dataCategory] .=  $this->wrapInTag($this->pi_linkTP_keepPIvars($obj->getObjVar('data_title'),array('uid' => $key, 'view' => '2', 'type' =>'data'),'1','1',DETAIL_PID),__FUNCTION__."-title");	
						# set all progenitors if nescessary
						foreach($categoryList->getAllProgenitors($dataCategory) as $catProgenitor) 
							$progenitorList[$catProgenitor] = 1;
						}
					
				# go through categorySortHash and fill template
				foreach(array_keys($categorySortHash) as $dataCategory)
					{
					if ($orderedList[$dataCategory] || $progenitorList[$dataCategory])
						{
						$contentArray['###CATEGORY-NAME###'] = $this->wrapInTag($catObjArr[$dataCategory] ? $catObjArr[$dataCategory]->getObjVar('category_name') : $this->getLL('no_category'),__FUNCTION__."-category-name");
						$contentArray['###DATA-TITLE###'] = $orderedList[$dataCategory];
						$contentArray['###LISTDATA###'] .= $this->wrapInTag($this->cObj->substituteMarkerArrayCached($subsubpart,$contentArray),__FUNCTION__."-categorylvl".$catObjArr[$dataCategory]->getObjVar('level'));
						}
					}

				if (! $contentArray['###LISTDATA###'])
					{ 
					$contentArray['###CATEGORY-NAME###'] = "";
					$contentArray['###LISTDATA###']=$this->getLL('no_data');
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
		if (!$uid) return $this->showError('error_missing_uid');

		# commons
		$commonsArray = $this->makeCommonsArray();
		$headingsArrayCSS = $this->wrapTemplateArrayInClass($this->makeHeadingsArray(),__FUNCTION__);
		$commonsArray = array_merge($commonsArray,$headingsArrayCSS);		
		$contentArray = array();

		switch ($type)
			{	
			case 'data':
				{
				# use template subpart
				$subpart=$this->cObj->getSubpart(TEMPLATE,'###DETAIL-DATAVIEW###');
				$subsubpart=$this->cObj->getSubpart(TEMPLATE,'###DETAIL-DATA###');

				# instantiate datalist and get DS
				$dataList = t3lib_div::makeInstance(PREFIX_ID.'_dataList');

				# instantiate and set clauseObj
				$clauseObj = t3lib_div::makeInstance(PREFIX_ID.'_objClause');
				$clauseObj->addAND('uid',$uid,'=');
				$objArr = $dataList->getDS($clauseObj);

				# get list of all categories
				$categoryList = t3lib_div::makeInstance(PREFIX_ID.'_categoryList');
				$catObjArr = $categoryList->getDS();

				if ($objArr[$uid])
					{
					$objVars =  $objArr[$uid]->getProperty('objVars');
					$contentArray['###ADMINSTUFF###'] = $this->makeAdminStuff($uid);
					$contentArray['###CATEGORY-NAME###'] = is_object($catObjArr[$objVars->get('data_category')]) ?  $catObjArr[$objVars->get('data_category')]->getObjVar('category_name') : "";
					$contentArray['###DATA_TITLE###']=$objVars->get('data_title');
					$contentArray['###DETAILDATA###'] ="";

					# get data fields ...
					$dataContentList = t3lib_div::makeInstance(PREFIX_ID.'_datacontentList');
					# instantiate and set clauseObj
					$clauseObj = t3lib_div::makeInstance(PREFIX_ID.'_objClause');
					$clauseObj->addAND('data_uid',$uid,'=');
					$dataContentObjArr = $dataContentList->getDS($clauseObj);

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
							$contentArray['###HEADING_'.strtoupper($fieldName).'###'] = $this->wrapInTag($fieldName,__FUNCTION__."-dataHeading");
							$contentArray['###'.strtoupper($key).'###'] = $this->wrapInTag($this->getLL($value) ? $this->getLL($value) : $value,__FUNCTION__."-dataContent");	
							$contentArray['###HEADING_DATACONTENT###'] = $this->wrapInTag($fieldName,__FUNCTION__."-dataHeading");
							$contentArray['###DATACONTENT###'] = $this->wrapInTag($value,__FUNCTION__."-dataContent");
							$contentArray['###DETAILDATA###'].= $this->cObj->substituteMarkerArrayCached($subsubpart,$contentArray);
							}

						}

					}  else return $this->showError('dberror_no_dataset');
				$contentArrayCSS = $this->wrapTemplateArrayInClass($contentArray,__FUNCTION__);	
				$contentAll = array_merge($contentArrayCSS,$commonsArray);
				$content = $this->cObj->substituteMarkerArrayCached($subpart,$contentAll);
				}
			break;
			}
		return $content;
		}

	private function editView(tx_generaldatadisplay_pi1_formData $formData)
		{
		# commons
		$commonsArray = $this->makeCommonsArray();
		$headingsArrayCSS = $this->wrapTemplateArrayInClass($this->makeHeadingsArray(),__FUNCTION__);
		$contentArray = array_merge($commonsArray,$headingsArrayCSS);

		# get stored formValues
		$formError = $formData->getProperty('formError');
		$type = $formData->getProperty('type');

		# UID
		$contentArray['###UID###']=$formData->getFormValue('uid');
		
		# build formError Template
		$contentArray['###DATA_TITLE_ERROR###']="";
		$contentArray['###DATA_CATEGORY_ERROR###']="";
		$contentArray['###CATEGORY_NAME_ERROR###']="";
		$contentArray['###DATAFIELD_NAME_ERROR###']="";
		$contentArray['###DATAFIELD_TYPE_ERROR###']="";

		foreach ($formError as $key => $hash)
 			{
			foreach ($hash as $check => $value)
				{
				$templKeyword="###".strtoupper($key)."_ERROR###";
				if ($value && array_key_exists($templKeyword,$contentArray)) 
					{ 
					$contentArray[$templKeyword].=
						$this->wrapInTag($this->getLL('error_'.$value),__FUNCTION__.'-formError');
					}
				}
			}

		switch ($type)
			{	
			case 'data':
				{
				# get datafields
				$datafieldList = t3lib_div::makeInstance(PREFIX_ID.'_datafieldList');
				$objArr = $datafieldList->getDS();
				$datafieldTypesArr = tx_generaldatadisplay_pi1_dataFields::getTypes();

				$subpart = "###EDIT_DATA###";				
				# set contentArray
				$contentArray['###DATA_TITLE###']=$formData->getFormValue('data_title');

				$categoryList = $datafieldList = t3lib_div::makeInstance(PREFIX_ID.'_categoryList');
				$categoryList->getDS();
				$categoryOptions = $categoryList->getOptionSelect('category_name',$formData->getFormValue('data_category'));
				$contentArray['###DATA_CATEGORY_OPTIONS###'] = $categoryOptions ? $categoryOptions : '<option value="">'.$this->getLL('empty_category').'</option>';
				
				if (! $objArr) $contentArray['###INPUT_DATAFIELDS###']=$this->getLL('no_datafields'); 
				
				foreach($objArr as $key => $obj)
					{
					# get datafield
					$objVars = $obj->getProperty('objVars');
					# check if datafield type is defined
					if ($datafieldTypesArr[$objVars->get('datafield_type')])
						{
						$dataField = t3lib_div::makeInstance(PREFIX_ID.'_'.$objVars->get('datafield_type'));
						$dataField->setTmplArr($contentArray);
						$dataField->setTmplVar('###DATAFIELD_NAME###',$objVars->get('datafield_name'));
						$dataField->setTmplVar('###HEADING_DATAFIELD###',$this->wrapInTag($objVars->get('datafield_name'),__FUNCTION__."-dataHeading"));
						$dataField->setTmplVar('###DATAFIELD_CONTENT###',$formData->getFormValue($objVars->get('datafield_name')));

						# get errors
						$formErrorDatafield="";
						$ferror = $formError[$objVars->get('datafield_name')] ? $formError[$objVars->get('datafield_name')] : array();
						foreach ($ferror as $key => $value)
							{
							if ($value) 
								{
								$formErrorDatafield.=
									$this->wrapInTag(($this->getLL('error_'.$value) ? $this->getLL('error_'.$value) : 'error_'.$value),__FUNCTION__.'-formError');
								}
							}
						$dataField->setTmplVar('###DATAFIELD_CONTENT_ERROR###',$formErrorDatafield ? $formErrorDatafield : "");

						$contentArray['###INPUT_DATAFIELDS###'].= $dataField->HTML();
						}
					}
				}
			break;
			
			case 'category':
				{

				$subpart = "###EDIT_CATEGORY###";
				$contentArray['###CATEGORY_NAME###']=$formData->getFormValue('category_name');
				$restrictClause = t3lib_div::makeInstance(PREFIX_ID.'_objClause');
				$restrictClause->addAND('category_name',$formData->getFormValue('category_name'),'!=');
				# build subcategories option select
				$categoryList = $datafieldList = t3lib_div::makeInstance(PREFIX_ID.'_categoryList');
				$categoryList->getDS($restrictClause);
				$contentArray['###DATA_SUBCATEGORY_OPTIONS###']='<option value="0"></option>'.$categoryList->getOptionSelect('category_name',$formData->getFormValue('category_progenitor'),false,'uid');
				}
			break;
			
			case 'datafield':
				{
				$subpart = "###EDIT_DATAFIELD###";
			
				$contentArray['###DATAFIELD_NAME###']=$formData->getFormValue('datafield_name');
				$contentArray['###DISPLAY_SEQUENCE###'] = $formData->getFormValue('display_sequence') ? $formData->getFormValue('display_sequence') : time();
				# get all datafieldtypes 
				$datafieldTypesArr = tx_generaldatadisplay_pi1_dataFields::getTypes();

				# choose type - if not submitted use first available
				$datafieldType = $datafieldTypesArr[$formData->getFormValue('datafield_type')] ? $formData->getFormValue('datafield_type') : $datafieldTypesArr[key($datafieldTypesArr)];

				$contentArray['###DATAFIELD_TYPE_OPTIONS###'] = $this->getOptionsFromArr($datafieldTypesArr,$formData->getFormValue('datafield_type'),true);

				$dataField = t3lib_div::makeInstance(PREFIX_ID.'_'.$datafieldType);
				$dataField->setTmplArr($contentArray);

				# select datafield configuration
				switch ($formData->getFormValue('datafield_type'))
					{	
					case 'img':
						{
						$dataField->setTmplVar('###HEADING_IMGSIZE###',$this->getLL('img_size'));
						$dataField->setTmplVar('###HEADING_IMGALIGN###',$this->getLL('img_align'));
						$dataField->setTmplVar('###CONTENT_IMGSIZE_X###',$formData->getMetadata('img_size_x'));
						$dataField->setTmplVar('###CONTENT_IMGSIZE_Y###',$formData->getMetadata('img_size_y'));
						$configArr = $dataField->getProperty('config');
						$dataField->setTmplVar('###IMGALIGN_OPTIONS###',$this->getOptionsFromArr($configArr['imgAlign'],$formData->getMetaData('img_align'),true));
						}
					break;
					
					default:
						{
						$dataField->setTmplVar('###HEADING_REQUIRED###',$this->getLL('required'));
						$dataField->setTmplVar('###HEADING_SEARCHABLE###',$this->getLL('searchable'));
						$dataField->setTmplVar('###HEADING_CONTENT_VISIBLE###',$this->getLL('visible'));
						$dataField->setTmplVar('###DATAFIELD_REQUIRED###',$formData->getMetadata('datafield_required')=='yes' ? 'checked="checked"' : '');
						$dataField->setTmplVar('###DATAFIELD_SEARCHABLE###',$formData->getMetadata('datafield_searchable')=='yes' ? 'checked="checked"' : '');
						$dataField->setTmplVar('###CONTENT_VISIBLE###',$formData->getMetadata('content_visible')=='yes' ? 'checked="checked"' : '');
						}
					}

				$contentArray['###DATAFIELD_CONFIG###'] = $this->wrapInTag($dataField->HTML('config'),__FUNCTION__."-datafieldConfig");
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
		# instantiate and set clauseObj
		$clauseObj = t3lib_div::makeInstance(PREFIX_ID.'_objClause');
		$clauseObj->addAND('uid',$this->secPiVars->get('uid'),'=');
		$objArr = $dataSet->getDS($clauseObj);

		$obj = $objArr[$this->secPiVars->get('uid')];
			
		# common template subpart
		$subpart=$this->cObj->getSubpart(TEMPLATE,'###DELETE_REQUEST###');

		# commons
		$commonsArray = $this->makeCommonsArray();
		$commonsArray['###UID###']=$this->secPiVars->get('uid');
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
				$contentArray['###TITLE###'] = $objVars->get('data_title');

				# get dataContent
				$dataContentList = t3lib_div::makeInstance(PREFIX_ID.'_datacontentList');
				# instantiate and set clauseObj
				$clauseObj = t3lib_div::makeInstance(PREFIX_ID.'_objClause');
				$clauseObj->addAND('data_uid',$this->secPiVars->get('uid'),'=');
				$dataContentObjArr = $dataContentList->getDS($clauseObj);
					
				foreach($dataContentObjArr as $key => $obj) $dataContent[$obj->getObjVar('datafield_name')] = $obj->getObjVar('datacontent');

				if ($dataContent)
					{
					# get data subpart
					$dataSub=$this->cObj->getSubpart(TEMPLATE,'###DELETE_REQUEST_DETAILS_DATAROW###');
					foreach($dataContent as $key => $value) 
						{
						$contentDataArr['###HEADING_DATA_CONTENT###'] = $this->wrapInTag($key,__FUNCTION__."-dataHeading");
						$contentDataArr['###DATA_CONTENT###'] = $value;
						$contentArray['###DATAROWS###'] .= $this->cObj->substituteMarkerArrayCached($dataSub,$contentDataArr);
						}
					} else $contentArray['###DATAROWS###'] = "";
				}
			break;
			
			case 'category':
				{
				$details='###DELETE_REQUEST_DETAILS_CATEGORY###';
				$contentArray['###CATEGORY###'] = $objVars->get('category_name');
				}
			break;

			case 'datafield':
				{
				$details='###DELETE_REQUEST_DETAILS_DATAFIELD###';
				$contentArray['###DATAFIELD###'] = $objVars->get('datafield_name');
				$contentArray['###DATAFIELD_TYPE###'] = $this->getLL($objVars->get('datafield_type'));
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

		$contentArray['###HEADING_ERROR###'] = $this->wrapInTag($this->getLL('error'),__FUNCTION__.'-heading');
		$contentArray['###ERRORTEXT###'] = $this->wrapInTag($this->getLL($errorCode) ? $this->getLL($errorCode) : $errorCode,__FUNCTION__.'-text');
		
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
			$dataArray = t3lib_BEfunc::getRecord('pages', DATA_PID);
			if ($GLOBALS['BE_USER']->doesUserHaveAccess($dataArray,2)) return 1;
			}

		# check FE permission
		# FE user has to be logged in
		if ($GLOBALS['TSFE']->loginUser) 
			{
			# get flexform data users & groups
			$flexUsers = $this->getConfigValue('feAdminUsers','string','','administration');
			$flexGroups = $this->getConfigValue('feAdminGroups','string','','administration');

			# if users & groups 
			if ($flexUsers || $flexGroups)
 				{
				# array from flexform users & groups
				$flexUsersArr = explode(',',$flexUsers);
				$flexGroupsArr = explode(',',$flexGroups);

				# uid from session
				$sessUser=$GLOBALS['TSFE']->fe_user->user['uid'];
				# create array from user & groups
				$sessGroupArr=explode(',',$GLOBALS['TSFE']->fe_user->user['usergroup']);

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
	
	private function sessionData($varName,$varContent='',$set=false)
		{
		if (!$varName) return false;

		if ($set && $varContent)
			{
			$GLOBALS['TSFE']->fe_user->setKey('ses',$this->extKey.'-'.DATA_PID.'-'.$varName,$varContent);
			$GLOBALS['TSFE']->fe_user->sesData_change = true;
			$GLOBALS['TSFE']->fe_user->storeSessionData();	
			return true;
			} else return $GLOBALS['TSFE']->fe_user->getKey('ses',$this->extKey.'-'.DATA_PID.'-'.$varName);
		}

	private function getOptionsFromArr(array $optionArr,$selected='',$locale=false)
		{
		$options="";

		foreach ($optionArr as $key => $value)
			{
			$value = $locale ? $this->getLL($value) : $value;
			if ($value)
				{
				$optionEntry = '<option value="'.$key.(($key==$selected) ? '" selected>' : '">').$value.'</option>';
				# add level class
				$optionEntry = $this->cObj->addParams($optionEntry,array('class' => $this->pi_getClassName().'optionfield'));
				
				$options .= $optionEntry;
				}
			}

		return $options;
		}

	private function formatContentType(tx_generaldatadisplay_pi1_dataStructs &$obj)
		{
		$content = $obj->getObjVar('datacontent');
		$type = $obj->getObjVar('datafield_type');

		if (!$content) return;

		switch ($type)
			{
			case 'date':
			$content = preg_replace('/\D/','.',$content);
			break;

			case 'email':
			$mailarr = $this->cObj->getMailTo($content);
			$content = '<a href="'.$mailarr[0].'">'.$mailarr[1].'</a>';
			break;

			case 'url':
			$content = $this->cObj->typoLink($content,array('parameter' => $content,'extTarget' => '_blank'));
			break;	

			case 'bool':
			$content = $this->getLL($content);
			break;

			case 'img':
			$metadata = tx_generaldatadisplay_pi1_dataFields::getMetadata($obj->getObjVar('datafields_uid'));
			
			if ($metadata['img_size_x']) $imgSizeArr[] = 'width="'.$metadata['img_size_x'].'"';
			if ($metadata['img_size_y']) $imgSizeArr[] = 'height="'.$metadata['img_size_y'].'"';
			
			$content = '<div '.($metadata['img_align'] ? 'style="text-align:'.$metadata['img_align'].'"' : '').'><img src="'.IMGUPLOADPATH.'/'.$content.'" alt="'.$this->getLL('img').'" '.implode(' ',$imgSizeArr).' /></div>';
			break;

			case 'text':
			$regArr = array			(
							      '/\n/' => '<br />', # linebreak
							      '/\*{2}(([^\*]|\*[^\*])*)\*{2}/' => '<b>$1</b>', # bold
							      '/\/{2}(([^\/]|\/[^\/])*)\/{2}/' => '<i>$1</i>', # italic
							      '/_{2}(([^_]|_[^_])*)_{2}/' => '<u>$1</u>' , # underline
							      '/%{2}(([^%]|%[^%])*)%{2}/' => '<tt>$1</tt>', # teletyper
							      '/\^{2}(([^\^]|\^[^\^])*)\^{2}/' => '<sup>$1</sup>', # superscript
							      '/\,{2}(([^\,]|\,[^\,])*)\,{2}/' => '<sub>$1</sub>', # subscript
							      '/\{{2}([^\{\}]*)\}{2}/' => 'list', # list
							      '/\[{2}(([^\[]|\[[^\[])*)\|(([^\[]|\[[^\[])*)\]{2}/' => 'link', # normal link
							      '/\[{2}(([^\[]|\[[^\[])*)\]{2}/' =>'link' # short link
							);

			foreach($regArr as $regex => $replace)
				{
				while (preg_match($regex,$content,$matches))
					{
					switch ($regex)
						{
						case '/\{{2}([^\{\}]*)\}{2}/':
						# remove linebreaks
						$listItems = preg_replace('/<br \/>/','',$matches[1]);

						if (preg_match('/\*([^\*]*)/',$listItems))
							{
							while(preg_match('/\*([^\*]*)/',$listItems))
								{
								$listItems = preg_replace('/\*([^\*]*)/','<li>$1</li>',$listItems);
								}
							$replace = '<ul>'.$listItems.'</ul>';
							} else { 
							while(preg_match('/\-([^\-]*)/',$listItems))
								{
								$listItems = preg_replace('/\-([^\-]*)/','<li>$1</li>',$listItems);
								}
							$replace = '<ol>'.$listItems.'</ol>';
							}

						break;
						case '/\[{2}(([^\[]|\[[^\[])*)\|(([^\[]|\[[^\[])*)\]{2}/':
						$replace = $this->cObj->typoLink($matches[3],array('parameter' => $matches[1],'extTarget' => '_blank'));
						break;

						case '/\[{2}(([^\[]|\[[^\[])*)\]{2}/':
						$replace = $this->cObj->typoLink($matches[1],array('parameter' => $matches[1],'extTarget' => '_blank'));
						break;
						}
					$content = preg_replace($regex,$replace,$content,1);
					}
				}
			break;
			}

		return $content;
		}

	private function makeCommonsArray()
		{
		$commonsArray['###PI_BASE###']= PREFIX_ID;
		$commonsArray['###PLUGINNAME###'] = $this->pi_getClassName('');
		$commonsArray['###ACTION_URL###']=$this->pi_getPageLink(LIST_PID);
		$commonsArray['###BACK###']=$this->wrapInTag($this->pi_linkTP_keepPIvars('<img src="'.PICTURE_PATH.'return.png" title="'.$this->getLL('back').'" alt="['.$this->getLL('back').']" />',array('back' => 1),'1','1',LIST_PID),'backLink');
		$commonsArray['###CANCEL###']=$this->getLL('cancel');
		$commonsArray['###SUBMIT###']=$this->getLL('submit');
		$commonsArray['###YES###']=$this->getLL('yes');
		$commonsArray['###NO###']=$this->getLL('no');
		$commonsArray['###OK###'] = $this->getLL('ok');
			
		return $commonsArray;
		}	

	private function makeHeadingsArray()
		{
		$headingsArray['###HEADING_DATA###']=$this->getLL('data');
		$headingsArray['###HEADING_TITLE###']=$this->getLL('title');
		$headingsArray['###HEADING_NAME###']=$this->getLL('name');
		$headingsArray['###HEADING_CATEGORY###']=$this->getLL('category');
		$headingsArray['###HEADING_SUBCATEGORY###']=$this->getLL('subcategory');
		$headingsArray['###HEADING_DATAFIELD###']=$this->getLL('datafield');
		$headingsArray['###HEADING_INPUT_DATAFIELDS###']=$this->getLL('datafields');
		$headingsArray['###HEADING_FIELDNAME###']=$this->getLL('fieldname');
		$headingsArray['###HEADING_TYPE###']=$this->getLL('type');
		$headingsArray['###HEADING_DATAFIELD_CONFIG###'] = $this->getLL('datafield_config');
		$headingsArray['###HEADING_DELETE_REQUEST###']=$this->getLL('delete_request');

		return $headingsArray;
		}
		
	private function makeOptionArray()
		{
		# get all searchable items and build option array
		$datafieldList = t3lib_div::makeInstance(PREFIX_ID.'_datafieldList');
		$datafieldList->getDS();

		$optionArray['###SELECTED_ITEM_OPTIONS###'] = 	 '<option value="0">'.$this->getLL('all_items').'</option>
								  <option value="data_title"'.($this->secPiVars->get('selected_item') == 'data_title' ? ' selected="selected"' : '').'>'.$this->pi_getLL('title').'</option>'
								.$datafieldList->getOptionSelect('datafield_name',$this->secPiVars->get('selected_item'));

		# build categories options
		$categoryList = $datafieldList = t3lib_div::makeInstance(PREFIX_ID.'_categoryList');
		$categoryList->getDS();

		$optionArray['###CATEGORY_OPTIONS###'] = '<option value="0">'.$this->getLL('all_categories').'</option>'.$categoryList->getOptionSelect('category_name',$this->secPiVars->get('selected_category'),true);
		$optionArray['###SEARCHPHRASE###'] = $this->secPiVars->get('searchphrase'); 

		$optionArray['###FE-ADMINLINKS###']=$this->wrapInTag($this->makeAdminLinks(),'optionfield-adminLinks');
		$optionArray['###SUBMIT_SEARCH###']=$this->getLL('show');
	
		return $optionArray;
		}

	private function makeBrowseList($array,$getVar,$class,$separator = '|',$active='')
		{
		$linkArray = array();
		foreach ($array as $l) 
			{
			 $linkArray[] = ($l == $active) ? 
				$this->wrapInTag($this->getLL($l),$class.'-browselist-link-active','span') : 
				$this->wrapInTag($this->pi_linkTP_keepPIvars($this->getLL($l),array($getVar => $l),'1','1',LIST_PID),$class.'-browselist-link','');
			}
		
		return implode($separator,$linkArray);
		}

	private function makeAdminStuff($uid,$type='data')
		{
		if (ADM_PERM && $uid)
			{		
			$stuff =$this->pi_linkTP_keepPIvars('<img src="'.PICTURE_PATH.'edit.png" title="'.$this->getLL('modify').'" alt="['.$this->getLL('modify').']" />',array('uid' => $uid, 'action' => 'update', 'type' => $type),'1','1');
			
			if ($type=='datafield')
				{
				$stuff.= $this->pi_linkTP_keepPIvars('<img src="'.PICTURE_PATH.'button_down.gif" title="'.$this->getLL('entry_down').'" alt="['.$this->getLL('entry_down').']" />',array('uid' => $uid, 'action' => 'update-sequence', 'type' => $type, 'direction' => 'down'),'1','1');
				$stuff.= $this->pi_linkTP_keepPIvars('<img src="'.PICTURE_PATH.'button_up.gif" title="'.$this->getLL('entry_up').'" alt="['.$this->getLL('entry_up').']" />',array('uid' => $uid, 'action' => 'update-sequence', 'type' => $type, 'direction' => 'up'),'1','1');
				}	
			
			$stuff.=$this->pi_linkTP_keepPIvars('<img src="'.PICTURE_PATH.'trash.png" title="'.$this->getLL('delete').'" alt="['.$this->getLL('delete').']" />',array('uid' => $uid,'action' => 'delete-request', 'type' => $type),'1','1');

			return $stuff;
			} else return;
		}

	private function makeAdminLinks()
		{
		if (ADM_PERM)
			{
			$subpart=$this->cObj->getSubpart(TEMPLATE,'###ADMINLINKS###');

			$contentArray['###NEW_DATA###']=$this->pi_linkTP_keepPIvars('['.$this->getLL('new_data').']',array('action' => 'update', 'type' => 'data'),'1','1');
			$contentArray['###SHOW_CATEGORIES###']=$this->pi_linkTP_keepPIvars('['.$this->getLL('categories').']',array('type' => 'category','view'=>'3'),'1','1');
			$contentArray['###SHOW_DATAFIELDS###']=$this->pi_linkTP_keepPIvars('['.$this->getLL('datafields').']',array('type' => 'datafield','view'=>'3'),'1','1');

			return $this->cObj->substituteMarkerArrayCached($subpart,$contentArray);
			} else return;
		}	

	private function getConfigValue($configVar,$type='string',$defaultValue='',$flexformSection=false)
		{
		$configValue = $flexformSection ? 
			$this->pi_getFFvalue($this->cObj->data['pi_flexform'], $configVar, $flexformSection) :
			$this->conf[$configVar];

		if ($flexformSection)
			{
			$configValue = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], $configVar, $flexformSection);

			if ($configValue != '')
				{
				settype($configValue,$type);
				return $configValue;
				}
			}
		if ($this->conf[$configVar] != '')
			{
			settype($this->conf[$configVar],$type);
			return $this->conf[$configVar];
			}

		return $defaultValue;
		}

	private function wrapTemplateArrayInClass(array $arr,$callingFunction='')
		{
		foreach($arr as $key => $value)
			{
			preg_match("/^###(.*)###$/",$key,$result);
			$arr[$key] = $this->wrapInTag($value,$callingFunction."-".strtolower($result[1]));
			}
		return $arr;
		}

	private function wrapInTag($str,$class,$tag='div')
		{
		if ($str) 
			{
			$str = $tag ? '<'.$tag.'>'.$str.'</'.$tag.'>' : $str;
			return $this->cObj->addParams($str,array('class' => $this->pi_getClassName().$class));
			} else return '';
		}

	private function getLL($item) 
		{
		return $this->pi_getLL($item) ? $this->pi_getLL($item) : $item;
		}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/general_data_display/pi1/class.tx_generaldatadisplay_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/general_data_display/pi1/class.tx_generaldatadisplay_pi1.php']);
}

?>
