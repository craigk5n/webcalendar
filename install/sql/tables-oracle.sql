
CREATE TABLE webcal_user (
  cal_login VARCHAR(25) NOT NULL,
  cal_passwd VARCHAR(32),
  cal_lastname VARCHAR(25),
  cal_firstname VARCHAR(25),
  cal_is_admin CHAR(1) DEFAULT 'N',
  cal_email VARCHAR(75) NULL,
  PRIMARY KEY ( cal_login )
);

/* create a default admin user */
INSERT INTO webcal_user ( cal_login, cal_passwd, cal_lastname, cal_firstname, cal_is_admin ) VALUES ( 'admin', '21232f297a57a5a743894a0e4a801fc3', 'Administrator', 'Default', 'Y' );


/* Calendar event entry
 * cal_date is an integer of the format YYYYMMDD
 * cal_time is an integer of the format HHMM
 * cal_duration is in minutes
 * cal_priority: 1=Low, 2=Med, 3=High
 * cal_type: E=Event ... and not yet implemented: D=Deadline, R=Reminder
 * cal_access:
 * P=Public
 * C=Confidential (others can see time allocated but not what it is)
 */
CREATE TABLE webcal_entry (
  cal_id INT NOT NULL,
  cal_group_id INT NULL,
  cal_ext_for_id INT NULL,
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
  cal_description VARCHAR2(1024),
  PRIMARY KEY ( cal_id )
);


CREATE TABLE webcal_entry_repeats (
   cal_id INT DEFAULT '0' NOT NULL,
   cal_type VARCHAR2(20),
   cal_end INT,
   cal_frequency INT DEFAULT '1',
   cal_days CHAR(7),
   PRIMARY KEY (cal_id)
);

/* This table specifies which dates in a repeating */
/* event have either been deleted or replaced with */
/* a replacement event for that day.  When replaced, the cal_group_id */
/* (I know... not the best name, but it wasn't being used) column will */
/* be set to the original event.  That way the user can delete the original */
/* event and (at the same time) delete any exception events. */
/*   cal_id: event id of repeating event */
/*   cal_date: date event should not repeat in YYYYMMDD format */
CREATE TABLE webcal_entry_repeats_not (
  cal_id INT NOT NULL,
  cal_date INT NOT NULL,
  PRIMARY KEY ( cal_id, cal_date )
);


/* associates one or more users with an event by its id */
/* cal_status: A=Accepted, R=Rejected, W=Waiting */
CREATE TABLE webcal_entry_user (
  cal_id int DEFAULT 0 NOT NULL,
  cal_login VARCHAR(25) NOT NULL,
  cal_status CHAR(1) DEFAULT 'A',
  cal_category int DEFAULT NULL,
  PRIMARY KEY (cal_id,cal_login)
);


/* external calendar users */
CREATE TABLE webcal_entry_ext_user (
  cal_id INT DEFAULT 0 NOT NULL,
  cal_fullname VARCHAR(50) NOT NULL,
  cal_email VARCHAR(75) NULL,
  PRIMARY KEY ( cal_id, cal_fullname )
);


/* preferences for a user */
CREATE TABLE webcal_user_pref (
  cal_login VARCHAR(25) NOT NULL,
  cal_setting VARCHAR(25) NOT NULL,
  cal_value VARCHAR(100) NULL,
  PRIMARY KEY ( cal_login, cal_setting )
);


/* layers for a user */
CREATE TABLE webcal_user_layers (
  cal_layerid INT DEFAULT '0' NOT NULL,
  cal_login VARCHAR(25) NOT NULL,
  cal_layeruser VARCHAR(25) NOT NULL,
  cal_color VARCHAR(25) NULL,
  cal_dups CHAR(1) DEFAULT 'N',
  PRIMARY KEY ( cal_login, cal_layeruser )
);

/* site extra fields (customized in site_extra.php) */
/* cal_id is event id */
/* cal_name is the brief name of this type (first field in $site_extra array) */
/* cal_type is EXTRA_URL, EXTRA_DATE, etc. */
/* cal_date is only used for EXTRA_DATE type fields */
/* cal_remind is many minutes before event should a reminder be sent */
/* cal_last_remind_date is the last event date (YYYYMMMDD) that a reminder */
/* was sent.  This is not necessarily the date the msg was sent.  It is the */
/* date of the event we are sending a reminder for. */
/* cal_data is used to store text data */
CREATE TABLE webcal_site_extras (
  cal_id INT DEFAULT '0' NOT NULL,
  cal_name VARCHAR(25) NOT NULL,
  cal_type INT NOT NULL,
  cal_date INT DEFAULT '0',
  cal_remind INT DEFAULT '0',
  cal_data LONG,
  PRIMARY KEY ( cal_id, cal_name, cal_type )
);

/* Keep a history of when reminders get sent */
/* cal_id is event id */
/* cal_name is extra type (see site_extras.php) */
/* cal_event_date is the event date we are sending reminder for */
/*   (in YYYYMMDD format) */
/* cal_last_sent is the date/time we last sent a reminder */
/*   (in UNIX time format) */
CREATE TABLE webcal_reminder_log (
  cal_id INT DEFAULT '0' NOT NULL,
  cal_name VARCHAR(25) NOT NULL,
  cal_event_date INT DEFAULT '0' NOT NULL,
  cal_last_sent INT DEFAULT '0' NOT NULL,
  PRIMARY KEY ( cal_id, cal_name, cal_event_date )
);

/* Group support */
/* cal_owner is the login of the creator of the group. */
CREATE TABLE webcal_group (
  cal_group_id INT NOT NULL,
  cal_owner VARCHAR2(25) NULL,
  cal_name VARCHAR2(50) NOT NULL,
  cal_last_update INT NOT NULL,
  PRIMARY KEY ( cal_group_id )
);

/* Assign users to groups */
CREATE TABLE webcal_group_user (
  cal_group_id INT NOT NULL,
  cal_login VARCHAR2(25) NOT NULL,
  PRIMARY KEY ( cal_group_id, cal_login )
);

/* A "view" allows a user to put the calendars of multiple users all on */
/* one page.  A "view" is valid only for the owner (cal_owner) of the */
/* view. */
/* cal_view_type is "W" for week view, "D" for day view, "M" for month view */
CREATE TABLE webcal_view (
  cal_view_id INT NOT NULL,
  cal_owner VARCHAR2(25) NOT NULL,
  cal_name VARCHAR2(50) NOT NULL,
  cal_view_type CHAR(1),
  cal_is_global CHAR(1) DEFAULT 'N' NOT NULL,
  PRIMARY KEY ( cal_view_id )
);

CREATE TABLE webcal_view_user (
  cal_view_id INT NOT NULL,
  cal_login VARCHAR2(25) NOT NULL,
  PRIMARY KEY ( cal_view_id, cal_login )
);

/* System settings (set by the admin interface in admin.php) */
CREATE TABLE webcal_config (
  cal_setting VARCHAR2(50) NOT NULL,
  cal_value VARCHAR2(100) NULL,
  PRIMARY KEY ( cal_setting )
);


/* activity log for an event */
/* log types (cal_type): */
/*   C: Created */
/*   A: Approved/Confirmed by user */
/*   R: Rejected by user */
/*   U: Updated by user */
/*   M: Mail Notification sent */
/*   E: Reminder sent */
CREATE TABLE webcal_entry_log (
  cal_log_id INT NOT NULL,
  cal_entry_id INT NOT NULL,
  cal_login VARCHAR2(25) NOT NULL,
  cal_user_cal VARCHAR2(25) NULL,
  cal_type CHAR(1) NOT NULL,
  cal_date INT NOT NULL,
  cal_time INT NULL,
  cal_text VARCHAR2(1024),
  PRIMARY KEY ( cal_log_id )
);

/* user categories */
CREATE TABLE webcal_categories (
  cat_id INT NOT NULL,
  cat_owner VARCHAR2(25),
  cat_name VARCHAR2(80) NOT NULL,
  PRIMARY KEY ( cat_id )
);

CREATE TABLE webcal_asst (
  cal_boss VARCHAR2(25) NOT NULL,
  cal_assistant VARCHAR2(25) NOT NULL,
  PRIMARY KEY ( cal_boss, cal_assistant )
);

CREATE TABLE webcal_nonuser_cals (
  cal_login VARCHAR2(25) NOT NULL,
  cal_lastname VARCHAR2(25) NULL,
  cal_firstname VARCHAR2(25) NULL,
  cal_admin VARCHAR2(25) NOT NULL,
  PRIMARY KEY ( cal_login )
);

CREATE TABLE webcal_import (
  cal_import_id INT NOT NULL,
  cal_name VARCHAR2(50) NULL,
  cal_date INT NOT NULL,
  cal_type VARCHAR2(10) NOT NULL,
  cal_login VARCHAR2(25) NULL,
  PRIMARY KEY ( cal_import_id )
);

CREATE TABLE webcal_import_data (
  cal_import_id INT NOT NULL,
  cal_id INT NOT NULL,
  cal_login VARCHAR2(25) NOT NULL,
  cal_import_type VARCHAR2(15) NOT NULL,
  cal_external_id VARCHAR2(200) NULL,
  PRIMARY KEY  ( cal_id, cal_login )
);

CREATE TABLE webcal_report (
  cal_login VARCHAR(25) NOT NULL,
  cal_report_id INT NOT NULL,
  cal_is_global CHAR(1) DEFAULT 'N' NOT NULL,
  cal_report_type VARCHAR(20) NOT NULL,
  cal_include_header CHAR(1) DEFAULT 'Y' NOT NULL,
  cal_report_name VARCHAR(50) NOT NULL,
  cal_time_range INT NOT NULL,
  cal_user VARCHAR(25) NULL,
  cal_allow_nav CHAR(1) DEFAULT 'Y',
  cal_cat_id INT NULL,
  cal_include_empty CHAR(1) DEFAULT 'N',
  cal_show_in_trailer CHAR(1) DEFAULT 'N',
  cal_update_date INT NOT NULL,
  PRIMARY KEY ( cal_report_id )
);

CREATE TABLE webcal_report_template (
  cal_report_id INT NOT NULL,
  cal_template_type CHAR(1) NOT NULL,
  cal_template_text LONG,
  PRIMARY KEY ( cal_report_id, cal_template_type )
);

CREATE TABLE webcal_access_user (
  cal_login VARCHAR(50) NOT NULL,
  cal_other_user VARCHAR(50) NOT NULL,
  cal_can_view CHAR(1) NOT NULL DEFAULT 'N',
  cal_can_edit CHAR(1) NOT NULL DEFAULT 'N',
  cal_can_delete CHAR(1) NOT NULL DEFAULT 'N',
  cal_can_approve CHAR(1) NOT NULL DEFAULT 'N',
  PRIMARY KEY ( cal_login, cal_other_user )
);

CREATE TABLE webcal_access_function (
  cal_login VARCHAR(50) NOT NULL,
  cal_permissions VARCHAR(64) NOT NULL,
  PRIMARY KEY ( cal_login )
);

CREATE TABLE webcal_tz_zones (
  zone_name VARCHAR(50) NOT NULL default '',
  zone_gmtoff INT  NOT NULL default '0',
  zone_rules VARCHAR(50) NOT NULL default '',
  zone_format VARCHAR(20) NOT NULL default '',
  zone_from bigint NOT NULL default '0',
  zone_until bigint NOT NULL default '0',
  zone_cc CHAR(2) NOT NULL default '',
  zone_coord VARCHAR(20) NOT NULL default '',
  zone_country VARCHAR(50) NOT NULL default ''
);

CREATE TABLE webcal_tz_rules (
  rule_name VARCHAR(50) NOT NULL default '',
  rule_from INT NOT NULL default '0',
  rule_to INT NOT NULL default '0',
  rule_type VARCHAR(20) NOT NULL default '',
  rule_in INT NOT NULL default '0',
  rule_on VARCHAR(20) NOT NULL default '',
  rule_at INT NOT NULL default '0',
  rule_at_suffix CHAR(1) NOT NULL default '',
  rule_save INT NOT NULL default '0',
  rule_letter VARCHAR(5) NOT NULL default ''
);
