#
# Table structure for table 'tx_generaldatadisplay_data'
#
CREATE TABLE tx_generaldatadisplay_data (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(3) unsigned DEFAULT '0' NOT NULL,
	data_title tinytext,
	data_category int(11) DEFAULT '0' NOT NULL,
	
	PRIMARY KEY (uid),
	KEY parent (pid)
);



#
# Table structure for table 'tx_generaldatadisplay_datacontent'
#
CREATE TABLE tx_generaldatadisplay_datacontent (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(3) unsigned DEFAULT '0' NOT NULL,
	data_uid int(11) DEFAULT '0' NOT NULL,
	datafields_uid int(11) DEFAULT '0' NOT NULL,
	datacontent text,

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
	deleted tinyint(3) unsigned DEFAULT '0' NOT NULL,
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
	deleted tinyint(3) unsigned DEFAULT '0' NOT NULL,
	datafield_name tinytext,
	datafield_type enum('tinytext','text','img','int','bool','date','time','email','url') NOT NULL,
	display_sequence int(11) DEFAULT '0' NOT NULL,
	metadata tinytext,

	PRIMARY KEY (uid),
	KEY parent (pid)
);
