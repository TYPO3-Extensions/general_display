<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA['tx_generaldatadisplay_data'] = array (
	'ctrl' => $TCA['tx_generaldatadisplay_data']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'data_title,data_category'
	),
	'feInterface' => $TCA['tx_generaldatadisplay_data']['feInterface'],
	'columns' => array (
		'data_title' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:general_data_display/locallang_db.xml:tx_generaldatadisplay_data.title',
			'config' => array (
				'type' => 'input',	
				'size' => '30',	
				'eval' => 'required',
			)
		),
		'data_category' => Array (
                        'exclude'     => 0,
                        'label' => 'LLL:EXT:general_data_display/locallang_db.xml:tx_generaldatadisplay_categories.name',
                        'config'=> array (
                                'type'  => 'select',
                                'foreign_table'       => 'tx_generaldatadisplay_categories',
                                'foreign_table_where' => 'AND tx_generaldatadisplay_categories.pid=###CURRENT_PID### ORDER by category_name',
                                'size' => 1,
                                'minitems' => 0,
                                'maxitems' => 1,
                        )
                ),
	),
	'types' => array (
		'0' => array('showitem' => 'data_title;;;;2-2-2, data_category;;;;3-3-3')
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);

$TCA['tx_generaldatadisplay_datacontent'] = array (
	'ctrl' => $TCA['tx_generaldatadisplay_datacontent']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'datacontent'
	),
	'feInterface' => $TCA['tx_generaldatadisplay_datacontent']['feInterface'],
	'columns' => array (
		'data_uid' => Array (
                        'exclude'     => 0,
                        'label' => 'LLL:EXT:general_data_display/locallang_db.xml:tx_generaldatadisplay_data.title',
                        'config'=> array (
                                'type'  => 'select',
				'items' => array (array('','null')),
                                'foreign_table'       => 'tx_generaldatadisplay_data',
                                'foreign_table_where' => 'AND tx_generaldatadisplay_data.pid=###CURRENT_PID### ORDER by data_title',
                                'size' => 1,
                                'minitems' => 0,
                                'maxitems' => 1,
                        )
                ),
		'datafields_uid' => Array (
                        'exclude'     => 0,
                        'label' => 'LLL:EXT:general_data_display/locallang_db.xml:tx_generaldatadisplay_datafields.name',
                        'config'=> array (
                                'type'  => 'select',
				'items' => array (array('','null')),
                                'foreign_table'       => 'tx_generaldatadisplay_datafields',
                                'foreign_table_where' => 'AND tx_generaldatadisplay_datafields.pid=###CURRENT_PID### ORDER by datafield_name',
                                'size' => 1,
                                'minitems' => 0,
                                'maxitems' => 1,
                        )
                ),
		'datacontent' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:general_data_display/locallang_db.xml:tx_generaldatadisplay_datacontent.datacontent',		
			'config' => array (
				'type' => 'text',
				'cols' => '40',
				'rows' => '3'
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'data_uid;;;;1-1-1, datafields_uid;;;;2-2-2, datacontent;;;;3-3-3')
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);

$TCA['tx_generaldatadisplay_categories'] = array (
	'ctrl' => $TCA['tx_generaldatadisplay_categories']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'category_name'
	),
	'feInterface' => $TCA['tx_generaldatadisplay_categories']['feInterface'],
	'columns' => array (
		'category_name' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:general_data_display/locallang_db.xml:tx_generaldatadisplay_categories.name',		
			'config' => array (
				'type' => 'input',	
				'size' => '30',	
				'eval' => 'required',
			)
		),
		'category_progenitor' => Array (
                        'exclude'     => 0,
                        'label' => 'LLL:EXT:general_data_display/locallang_db.xml:tx_generaldatadisplay_categories.progenitor',
                        'config'=> array (
                                'type'  => 'select',
				'items' => array (array('','null')),
                                'foreign_table'       => 'tx_generaldatadisplay_categories',
                                'foreign_table_where' => 'AND tx_generaldatadisplay_categories.pid=###CURRENT_PID### ORDER by category_name',
                                'size' => 1,
                                'minitems' => 0,
                                'maxitems' => 1,
                        )
                ),	
	),
	'types' => array (
		'0' => array('showitem' => 'category_name;;;;1-1-1, category_progenitor')
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);



$TCA['tx_generaldatadisplay_datafields'] = array (
	'ctrl' => $TCA['tx_generaldatadisplay_datafields']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'datafield_name,datafield_type'
	),
	'feInterface' => $TCA['tx_generaldatadisplay_datafields']['feInterface'],
	'columns' => array (
		'datafield_name' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:general_data_display/locallang_db.xml:tx_generaldatadisplay_datafields.name',		
			'config' => array (
				'type' => 'input',	
				'size' => '30',	
				'eval' => 'required',
			)
		),
		'datafield_type' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:general_data_display/locallang_db.xml:tx_generaldatadisplay_datafields.type',		
			'config' => array (
				'type' => 'select',
				'items' => array (
					array('LLL:EXT:general_data_display/locallang_db.xml:tx_generaldatadisplay_datafields.type.I.0', 'tinytext'),
					array('LLL:EXT:general_data_display/locallang_db.xml:tx_generaldatadisplay_datafields.type.I.1', 'text'),
					array('LLL:EXT:general_data_display/locallang_db.xml:tx_generaldatadisplay_datafields.type.I.2', 'int'),
					array('LLL:EXT:general_data_display/locallang_db.xml:tx_generaldatadisplay_datafields.type.I.3', 'bool'),
					array('LLL:EXT:general_data_display/locallang_db.xml:tx_generaldatadisplay_datafields.type.I.4', 'currency'),
					array('LLL:EXT:general_data_display/locallang_db.xml:tx_generaldatadisplay_datafields.type.I.5', 'date'),
					array('LLL:EXT:general_data_display/locallang_db.xml:tx_generaldatadisplay_datafields.type.I.6', 'time'),
					array('LLL:EXT:general_data_display/locallang_db.xml:tx_generaldatadisplay_datafields.type.I.7', 'email'),
					array('LLL:EXT:general_data_display/locallang_db.xml:tx_generaldatadisplay_datafields.type.I.8', 'url'),
					array('LLL:EXT:general_data_display/locallang_db.xml:tx_generaldatadisplay_datafields.type.I.9', 'img'),
					array('LLL:EXT:general_data_display/locallang_db.xml:tx_generaldatadisplay_datafields.type.I.10', 'file'),
				),
				'size' => 1,	
				'maxitems' => 1,
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'datafield_name;;;;1-1-1, datafield_type')
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);
?>
