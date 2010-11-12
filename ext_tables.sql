#
# Table structure for table 'tx_generaldatadisplay_data'
#
CREATE TABLE tx_generaldatadisplay_data (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	data_title tinytext,
	data_category int(11) DEFAULT '',
	data_field_content mediumtext,
	
	PRIMARY KEY (uid),
	KEY parent (pid)
);



#
# Table structure for table 'tx_generaldatadisplay_categories'
#
CREATE TABLE tx_generaldatadisplay_categories (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	category_progenitor int(11) DEFAULT '0' NOT NULL,
	category_name tinytext,

	PRIMARY KEY (uid),
	KEY parent (pid)
);



#
# Table structure for table 'tx_generaldatadisplay_datafields'
#
CREATE TABLE tx_generaldatadisplay_datafields (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	datafield_name tinytext,
	datafield_type enum('tinytext','text','int','bool','date','time','email','url') NOT NULL,
	datafield_required enum('yes','no') DEFAULT 'no',
	datafield_searchable enum('yes','no') DEFAULT 'yes',
	content_visible enum('yes','no') DEFAULT 'yes',
	display_sequence int(11) DEFAULT '0' NOT NULL,	

	PRIMARY KEY (uid),
	KEY parent (pid)
);
