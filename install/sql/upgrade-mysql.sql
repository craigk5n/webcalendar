/* $Id: upgrade-mysql.sql,v 1.28 2009/10/30 11:47:16 bbannon Exp $ */
/*upgrade_v0.9.14*/
UPDATE webcal_entry SET cal_time = -1 WHERE cal_time IS NULL;
ALTER TABLE webcal_entry MODIFY cal_time INT NOT NULL DEFAULT -1;
CREATE TABLE webcal_entry_repeats (
  cal_id INT NOT NULL,
  cal_days CHAR(7),
  cal_end INT,
  cal_frequency INT DEFAULT 1,
  cal_type VARCHAR(20),
  PRIMARY KEY (cal_id)
);
/*upgrade_v0.9.22*/
CREATE TABLE webcal_user_layers (
  cal_login VARCHAR(25) NOT NULL,
  cal_layeruser VARCHAR(25) NOT NULL,
  cal_color VARCHAR(25),
  cal_dups CHAR(1) NOT NULL DEFAULT 'N',
  cal_layerid INT NOT NULL,
  PRIMARY KEY (cal_login,cal_layeruser)
);
/*upgrade_v0.9.27*/
CREATE TABLE webcal_site_extras (
  cal_id INT NOT NULL,
  cal_name VARCHAR(25) NOT NULL,
  cal_type INT NOT NULL,
  cal_date INT,
  cal_remind INT,
  cal_data TEXT,
  PRIMARY KEY (cal_id,cal_name,cal_type)
);
/*upgrade_v0.9.35*/
CREATE TABLE webcal_config (
  cal_setting VARCHAR(50) NOT NULL,
  cal_value VARCHAR(50),
  PRIMARY KEY (cal_setting)
);
CREATE TABLE webcal_entry_log (
  cal_log_id INT NOT NULL,
  cal_date INT NOT NULL,
  cal_entry_id INT NOT NULL,
  cal_login VARCHAR(25) NOT NULL,
  cal_time INT,
  cal_type CHAR(1) NOT NULL,
  cal_text TEXT,
  PRIMARY KEY (cal_log_id)
);
CREATE TABLE webcal_group (
  cal_group_id INT NOT NULL,
  cal_last_update INT NOT NULL,
  cal_name VARCHAR(50) NOT NULL,
  cal_owner VARCHAR(25),
  PRIMARY KEY (cal_group_id)
);
CREATE TABLE webcal_group_user (
  cal_group_id INT NOT NULL,
  cal_login VARCHAR(25) NOT NULL,
  PRIMARY KEY (cal_group_id,cal_login)
);
CREATE TABLE webcal_view (
  cal_view_id INT NOT NULL,
  cal_name VARCHAR(50) NOT NULL,
  cal_owner VARCHAR(25) NOT NULL,
  cal_view_type CHAR(1),
  PRIMARY KEY (cal_view_id)
);
CREATE TABLE webcal_view_user (
  cal_view_id INT NOT NULL,
  cal_login VARCHAR(25) NOT NULL,
  PRIMARY KEY (cal_view_id,cal_login)
);
/*upgrade_v0.9.37*/
ALTER TABLE webcal_entry_log ADD cal_user_cal VARCHAR(25);
CREATE TABLE webcal_entry_repeats_not (
  cal_id INT NOT NULL,
  cal_date INT NOT NULL,
  PRIMARY KEY (cal_id,cal_date)
);
/*upgrade_v0.9.38*/
ALTER TABLE webcal_entry_user ADD cal_category INT;
CREATE TABLE webcal_categories (
  cat_id INT NOT NULL,
  cat_name VARCHAR(80) NOT NULL,
  cat_owner VARCHAR(25),
  PRIMARY KEY (cat_id)
);
/*upgrade_v0.9.40*/
DELETE FROM webcal_config WHERE cal_setting LIKE 'DATE_FORMAT%';
DELETE FROM webcal_user_pref WHERE cal_setting LIKE 'DATE_FORMAT%';
ALTER TABLE webcal_entry ADD cal_ext_for_id INT;
CREATE TABLE webcal_asst (
  cal_boss VARCHAR(25) NOT NULL,
  cal_assistant VARCHAR(25) NOT NULL,
  PRIMARY KEY (cal_boss,cal_assistant)
);
CREATE TABLE webcal_entry_ext_user (
  cal_id INT NOT NULL,
  cal_fullname VARCHAR(50) NOT NULL,
  cal_email VARCHAR(75),
  PRIMARY KEY (cal_id,cal_fullname)
);
/*upgrade_v0.9.41*/
CREATE TABLE webcal_nonuser_cals (
  cal_login VARCHAR(25) NOT NULL,
  cal_admin VARCHAR(25) NOT NULL,
  cal_firstname VARCHAR(25),
  cal_lastname VARCHAR(25),
  PRIMARY KEY (cal_login)
);
/*upgrade_v0.9.42*/
CREATE TABLE webcal_report (
  cal_report_id INT NOT NULL,
  cal_allow_nav CHAR(1) NOT NULL DEFAULT 'Y',
  cal_cat_id INT,
  cal_include_empty CHAR(1) NOT NULL DEFAULT 'N',
  cal_include_header CHAR(1) NOT NULL DEFAULT 'Y',
  cal_is_global CHAR(1) NOT NULL DEFAULT 'N',
  cal_login VARCHAR(25) NOT NULL,
  cal_report_name VARCHAR(50) NOT NULL,
  cal_report_type VARCHAR(20) NOT NULL,
  cal_show_in_trailer CHAR(1) NOT NULL DEFAULT 'N',
  cal_time_range INT NOT NULL,
  cal_update_date INT NOT NULL,
  cal_user VARCHAR(25),
  PRIMARY KEY (cal_report_id)
);
CREATE TABLE webcal_report_template (
  cal_report_id INT NOT NULL,
  cal_template_type CHAR(1) NOT NULL,
  cal_template_text TEXT,
  PRIMARY KEY (cal_report_id,cal_template_type)
);
/*upgrade_v0.9.43*/
ALTER TABLE webcal_user MODIFY cal_passwd VARCHAR(32);
DROP TABLE IF EXISTS webcal_import_data;
CREATE TABLE webcal_import (
  cal_import_id INT NOT NULL,
  cal_date INT NOT NULL,
  cal_login VARCHAR(25),
  cal_name VARCHAR(50),
  cal_type VARCHAR(10) NOT NULL,
  PRIMARY KEY (cal_import_id)
);
CREATE TABLE webcal_import_data (
  cal_id INT NOT NULL,
  cal_login VARCHAR(25) NOT NULL,
  cal_external_id VARCHAR(200),
  cal_import_id INT NOT NULL,
  cal_import_type VARCHAR(15) NOT NULL,
  PRIMARY KEY (cal_id,cal_login)
);
/*upgrade_v1.0RC3*/
ALTER TABLE webcal_view ADD cal_is_global CHAR(1) NOT NULL DEFAULT 'N';
UPDATE webcal_config SET cal_value = 'week.php'  WHERE cal_setting = 'STARTVIEW';
UPDATE webcal_user_pref SET cal_value = 'day.php'  WHERE cal_value = 'day' AND cal_setting = 'STARTVIEW';
UPDATE webcal_user_pref SET cal_value = 'month.php'  WHERE cal_value = 'month' AND cal_setting = 'STARTVIEW';
UPDATE webcal_user_pref SET cal_value = 'week.php'  WHERE cal_value = 'week' AND cal_setting = 'STARTVIEW';
UPDATE webcal_user_pref SET cal_value = 'year.php'  WHERE cal_value = 'year' AND cal_setting = 'STARTVIEW';
UPDATE webcal_view SET cal_is_global = 'N';
/*upgrade_v1.1.0-CVS*/
CREATE TABLE webcal_access_function (
  cal_login VARCHAR(25) NOT NULL,
  cal_permissions VARCHAR(64) NOT NULL,
  PRIMARY KEY (cal_login)
);
ALTER TABLE webcal_nonuser_cals ADD cal_is_public CHAR(1) NOT NULL DEFAULT 'N';
/*upgrade_v1.1.0a-CVS*/
CREATE TABLE webcal_user_template (
  cal_login VARCHAR(25) NOT NULL,
  cal_type CHAR(1) NOT NULL,
  cal_template_text TEXT,
  PRIMARY KEY (cal_login,cal_type)
);
ALTER TABLE webcal_entry_repeats ADD cal_endtime int(11) DEFAULT NULL;
ALTER TABLE webcal_entry_repeats ADD cal_bymonth varchar(50) DEFAULT NULL;
ALTER TABLE webcal_entry_repeats ADD cal_bymonthday varchar(100) DEFAULT NULL;
ALTER TABLE webcal_entry_repeats ADD cal_byday varchar(100) DEFAULT NULL;
ALTER TABLE webcal_entry_repeats ADD cal_bysetpos varchar(50) DEFAULT NULL;
ALTER TABLE webcal_entry_repeats ADD cal_byweekno varchar(50) DEFAULT NULL;
ALTER TABLE webcal_entry_repeats ADD cal_byyearday varchar(50) DEFAULT NULL;
ALTER TABLE webcal_entry_repeats ADD cal_wkst char(2) DEFAULT 'MO';
ALTER TABLE webcal_entry_repeats ADD cal_count int(11) DEFAULT NULL;
ALTER TABLE webcal_entry_repeats_not ADD cal_exdate int(1) NOT NULL DEFAULT '1';
ALTER TABLE webcal_entry ADD cal_due_date int(11) DEFAULT NULL;
ALTER TABLE webcal_entry ADD cal_due_time int(11) DEFAULT NULL;
ALTER TABLE webcal_entry ADD cal_location varchar(100) DEFAULT NULL;
ALTER TABLE webcal_entry ADD cal_url varchar(100) DEFAULT NULL;
ALTER TABLE webcal_entry ADD cal_completed int(11) DEFAULT NULL;
ALTER TABLE webcal_entry_user ADD cal_percent int(11) NOT NULL DEFAULT '0';
ALTER TABLE webcal_site_extras DROP PRIMARY KEY;
/*upgrade_v1.1.0b-CVS*/
CREATE TABLE webcal_entry_categories (
  cal_id int(11) NOT NULL DEFAULT '0',
  cat_id int(11) NOT NULL DEFAULT '0',
  cat_order int(11) NOT NULL DEFAULT '0',
  cat_owner varchar(25) DEFAULT NULL
);
/*upgrade_v1.1.0c-CVS*/
CREATE TABLE webcal_blob (
  cal_blob_id INT NOT NULL,
  cal_id INT NULL,
  cal_login VARCHAR(25) NULL,
  cal_name VARCHAR(30) NULL,
  cal_description VARCHAR(128) NULL,
  cal_size INT NULL,
  cal_mime_type VARCHAR(50) NULL,
  cal_type CHAR(1) NOT NULL,
  cal_mod_date INT NOT NULL,
  cal_mod_time INT NOT NULL,
  cal_blob LONGBLOB,
  PRIMARY KEY ( cal_blob_id )
);
/*upgrade_v1.1.0d-CVS*/
DROP TABLE IF EXISTS webcal_access_user;
CREATE TABLE webcal_access_user (
  cal_login VARCHAR(25) NOT NULL,
  cal_other_user VARCHAR(25) NOT NULL,
  cal_can_view INT NOT NULL DEFAULT '0',
  cal_can_edit INT NOT NULL DEFAULT '0',
  cal_can_approve INT NOT NULL DEFAULT '0',
  cal_can_invite CHAR(1) DEFAULT 'Y',
  cal_can_email CHAR(1) DEFAULT 'Y',
  cal_see_time_only CHAR(1) DEFAULT 'N',
  PRIMARY KEY ( cal_login, cal_other_user )
);
/*upgrade_v1.1.0e-CVS*/
CREATE TABLE webcal_reminders (
  cal_id INT NOT NULL DEFAULT '0',
  cal_date INT NOT NULL DEFAULT '0',
  cal_offset INT NOT NULL DEFAULT '0',
  cal_related CHAR(1) NOT NULL DEFAULT 'S',
  cal_before CHAR(1) NOT NULL DEFAULT 'Y',
  cal_last_sent INT NOT NULL DEFAULT '0',
  cal_repeats INT NOT NULL DEFAULT '0',
  cal_duration INT NOT NULL DEFAULT '0',
  cal_times_sent INT NOT NULL DEFAULT '0',
  cal_action VARCHAR(12) NOT NULL DEFAULT 'EMAIL',
  PRIMARY KEY ( cal_id )
);
/*upgrade_v1.1.1*/
ALTER TABLE webcal_nonuser_cals ADD cal_url VARCHAR(255) DEFAULT NULL;
/*upgrade_v1.1.2*/
ALTER TABLE webcal_categories ADD cat_color VARCHAR(8) DEFAULT NULL;
ALTER TABLE webcal_user ADD cal_enabled CHAR(1) DEFAULT 'Y';
ALTER TABLE webcal_user ADD cal_telephone VARCHAR(50) DEFAULT NULL;
ALTER TABLE webcal_user ADD cal_address VARCHAR(75) DEFAULT NULL;
ALTER TABLE webcal_user ADD cal_title VARCHAR(75) DEFAULT NULL;
ALTER TABLE webcal_user ADD cal_birthday INT NULL;
ALTER TABLE webcal_user ADD cal_last_login INT NULL;
/*upgrade_v1.1.3*/
CREATE TABLE webcal_timezones (
  tzid varchar(100) NOT NULL default '',
  dtstart varchar(25) default NULL,
  dtend varchar(25) default NULL,
  vtimezone text,
  PRIMARY KEY  ( tzid )
);
/*upgrade_v1.3.0*/

