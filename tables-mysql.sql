
CREATE TABLE webcal_user (
  cal_login VARCHAR(25) NOT NULL,
  cal_passwd VARCHAR(25),
  cal_lastname VARCHAR(25),
  cal_firstname VARCHAR(25),
  cal_is_admin CHAR(1) DEFAULT 'N',
  cal_email VARCHAR(75) NULL,
  PRIMARY KEY ( cal_login )
);

# create a default admin user
INSERT INTO webcal_user ( cal_login, cal_passwd, cal_lastname, cal_firstname, cal_is_admin ) VALUES ( 'admin', 'admin', 'Administrator', 'Default', 'Y' );


# Calendar event entry
# cal_date is an integer of the format YYYYMMDD
# cal_time is an integer of the format HHMM
# cal_duration is in minutes
# cal_priority: 1=Low, 2=Med, 3=High
# cal_type: E=Event, M=Repeating event
# cal_access:
#	P=Public
#	R=Confidential (others can see time allocated but not what it is)
CREATE TABLE webcal_entry (
  cal_id INT NOT NULL,
  cal_group_id INT NULL,
  cal_create_by VARCHAR(25) NOT NULL,
  cal_date INT NOT NULL,
  cal_time INT NULL,
  cal_mod_date INT,
  cal_mod_time INT,
  cal_duration INT NOT NULL,
  cal_priority INT DEFAULT 2,
  cal_type CHAR(1) DEFAULT 'E',
  cal_access CHAR(1) DEFAULT 'P',
  cal_name VARCHAR(80) NOT NULL,
  cal_description TEXT,
  PRIMARY KEY ( cal_id )
);


CREATE TABLE webcal_entry_repeats (
   cal_id INT DEFAULT '0' NOT NULL,
   cal_type VARCHAR(20),
   cal_end INT,
   cal_frequency INT DEFAULT '1',
   cal_days CHAR(7),
   PRIMARY KEY (cal_id)
);




# associates one or more users with an event by its id
# cal_status: A=Accepted, R=Rejected, W=Waiting
CREATE TABLE webcal_entry_user (
  cal_id INT(11) DEFAULT '0' NOT NULL,
  cal_login VARCHAR(25) DEFAULT '' NOT NULL,
  cal_status CHAR(1) DEFAULT 'A',
  PRIMARY KEY (cal_id,cal_login)
);



# preferences for a user
CREATE TABLE webcal_user_pref (
  cal_login VARCHAR(25) NOT NULL,
  cal_setting VARCHAR(25) NOT NULL,
  cal_value VARCHAR(50) NULL,
  PRIMARY KEY ( cal_login, cal_setting )
);


# layers for a user
CREATE TABLE webcal_user_layers (
  cal_layerid INT DEFAULT '0' NOT NULL,
  cal_login VARCHAR(25) NOT NULL,
  cal_layeruser VARCHAR(25) NOT NULL,
  cal_color VARCHAR(25) NULL,
  cal_dups CHAR(1) DEFAULT 'N',
  PRIMARY KEY ( cal_login, cal_layeruser )
);

# site extra fields (customized in site_extra.inc)
# cal_id is event id
# cal_name is the brief name of this type (first field in $site_extra array)
# cal_type is $EXTRA_URL, $EXTRA_DATE, etc.
# cal_date is only used for $EXTRA_DATE type fields
# cal_remind is many minutes before event should a reminder be sent
# cal_last_remind_date is the last event date (YYYYMMMDD) that a reminder
# was sent.  This is not necessarily the date the msg was sent.  It is the
# date of the event we are sending a reminder for.
# cal_data is used to store text data
CREATE TABLE webcal_site_extras (
  cal_id INT DEFAULT '0' NOT NULL,
  cal_name VARCHAR(25) NOT NULL,
  cal_type INT NOT NULL,
  cal_date INT DEFAULT '0',
  cal_remind INT DEFAULT '0',
  cal_data TEXT,
  PRIMARY KEY ( cal_id, cal_name, cal_type )
);

# Keep a history of when reminders get sent
# cal_id is event id
# cal_name is extra type (see site_extras.inc)
# cal_event_date is the event date we are sending reminder for
#   (in YYYYMMDD format)
# cal_last_sent is the date/time we last sent a reminder
#   (in UNIX time format)
CREATE TABLE webcal_reminder_log (
  cal_id INT DEFAULT '0' NOT NULL,
  cal_name VARCHAR(25) NOT NULL,
  cal_event_date INT NOT NULL DEFAULT 0,
  cal_last_sent INT NOT NULL DEFAULT 0,
  PRIMARY KEY ( cal_id, cal_name, cal_event_date )
);

