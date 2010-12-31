<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA['tx_generaldatadisplay_data'] = array (
	'ctrl' => $TCA['tx_generaldatadisplay_data']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'data_title,data_category,data_field_content'
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
		'data_field_content' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:general_data_display/locallang_db.xml:tx_generaldatadisplay_data.data_field_content',		
			'config' => array (
				'type' => 'none',
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
		'showRecordFieldList' => 'datafield_name,datafield_type,datafield_searchable, content_visible'
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
					array('LLL:EXT:general_data_display/locallang_db.xml:tx_generaldatadisplay_datafields.type.I.4', 'date'),
					array('LLL:EXT:general_data_display/locallang_db.xml:tx_generaldatadisplay_datafields.type.I.5', 'time'),
					array('LLL:EXT:general_data_display/locallang_db.xml:tx_generaldatadisplay_datafields.type.I.6', 'email'),
					array('LLL:EXT:general_data_display/locallang_db.xml:tx_generaldatadisplay_datafields.type.I.7', 'url'),
					array('LLL:EXT:general_data_display/locallang_db.xml:tx_generaldatadisplay_datafields.type.I.7', 'img'),
				),
				'size' => 1,	
				'maxitems' => 1,
			)
		),
		'datafield_required' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:general_data_display/locallang_db.xml:tx_generaldatadisplay_datafields.required',		
			'config' => array (
				'type' => 'select',
				'items' => array (
					array('LLL:EXT:general_data_display/locallang_db.xml:tx_generaldatadisplay_datafields.answer.no', 'no'),
					array('LLL:EXT:general_data_display/locallang_db.xml:tx_generaldatadisplay_datafields.answer.yes', 'yes'),
				),
				'size' => 1,	
				'maxitems' => 1,
			)
		),
		'datafield_searchable' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:general_data_display/locallang_db.xml:tx_generaldatadisplay_datafields.searchable',		
			'config' => array (
				'type' => 'select',
				'items' => array (
					array('LLL:EXT:general_data_display/locallang_db.xml:tx_generaldatadisplay_datafields.answer.yes', 'yes'),
					array('LLL:EXT:general_data_display/locallang_db.xml:tx_generaldatadisplay_datafields.answer.no', 'no'),
				),
				'size' => 1,	
				'maxitems' => 1,
			)
		),
		'content_visible' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:general_data_display/locallang_db.xml:tx_generaldatadisplay_datafields.displayable',		
			'config' => array (
				'type' => 'select',
				'items' => array (
					array('LLL:EXT:general_data_display/locallang_db.xml:tx_generaldatadisplay_datafields.answer.yes', 'yes'),
					array('LLL:EXT:general_data_display/locallang_db.xml:tx_generaldatadisplay_datafields.answer.no', 'no'),
				),
				'size' => 1,	
				'maxitems' => 1,
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'datafield_name;;;;1-1-1, datafield_type, datafield_required, datafield_searchable, content_visible')
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);
?>
