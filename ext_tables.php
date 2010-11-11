<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

t3lib_extMgm::allowTableOnStandardPages('tx_generaldatadisplay_data');

$TCA['tx_generaldatadisplay_data'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:general_data_display/locallang_db.xml:tx_generaldatadisplay_data',		
		'label'     => 'data_title',	
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY crdate',	
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_generaldatadisplay_data.gif',
	),
);


t3lib_extMgm::allowTableOnStandardPages('tx_generaldatadisplay_categories');

$TCA['tx_generaldatadisplay_categories'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:general_data_display/locallang_db.xml:tx_generaldatadisplay_categories',		
		'label'     => 'category_name',	
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY crdate',	
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_generaldatadisplay_categories.gif',
	),
);


t3lib_extMgm::allowTableOnStandardPages('tx_generaldatadisplay_datafields');

$TCA['tx_generaldatadisplay_datafields'] = array (
	'ctrl' => array (
		'title'     => 'LLL:EXT:general_data_display/locallang_db.xml:tx_generaldatadisplay_datafields',		
		'label'     => 'datafield_name',	
		'tstamp'    => 'tstamp',
		'crdate'    => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY crdate',	
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
		'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_generaldatadisplay_datafields.gif',
	),
);


t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1']='layout,select_key,pages';

# add pi_flexform to be renderd when the plugin is shown
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY.'_pi1']='pi_flexform';

t3lib_extMgm::addPlugin(array(
	'LLL:EXT:general_data_display/locallang_db.xml:tt_content.list_type_pi1',
	$_EXTKEY . '_pi1',
	t3lib_extMgm::extRelPath($_EXTKEY) . 'ext_icon.gif'
),'list_type');

# the flexform XML file
t3lib_extMgm::addPiFlexFormValue($_EXTKEY.'_pi1', 'FILE:EXT:general_data_display/flexform_ds_pi1.xml');



if (TYPO3_MODE == 'BE') {
	$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['tx_generaldatadisplay_pi1_wizicon'] = t3lib_extMgm::extPath($_EXTKEY).'pi1/class.tx_generaldatadisplay_pi1_wizicon.php';
}
?>