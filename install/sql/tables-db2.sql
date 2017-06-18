CREATE TABLE webcal_user (
  cal_login VARCHAR(25) NOT NULL,
  cal_passwd VARCHAR(32),
  cal_lastname VARCHAR(25),
  cal_firstname VARCHAR(25),
  cal_is_admin CHAR(1) DEFAULT 'N',
  cal_email VARCHAR(75),
  cal_enabled CHAR(1) DEFAULT 'Y',
  cal_telephone VARCHAR(50),
  cal_address VARCHAR(75),
  cal_title VARCHAR(75),
  cal_birthday INT,
  cal_last_login INT,
  PRIMARY KEY ( cal_login )
);
CREATE TABLE webcal_entry (
  cal_id INT NOT NULL,
  cal_group_id INT,
  cal_ext_for_id INT,
  cal_create_by VARCHAR(25) NOT NULL,
  cal_date INT NOT NULL,
  cal_time INT,
  cal_mod_date INT,
  cal_mod_time INT,
  cal_duration INT NOT NULL,
  cal_due_date INT,
  cal_due_time INT,
  cal_priority INT DEFAULT 5,
  cal_type CHAR(1) DEFAULT 'E',
  cal_access CHAR(1) DEFAULT 'P',
  cal_name VARCHAR(80) NOT NULL,
  cal_location VARCHAR(100),
  cal_url VARCHAR(100),
  cal_completed INT,
  cal_description VARCHAR(1024),
  PRIMARY KEY ( cal_id )
);
CREATE TABLE webcal_entry_repeats (
   cal_id INT DEFAULT 0 NOT NULL,
   cal_type VARCHAR(20),
   cal_end INT,
   cal_endtime INT,
   cal_frequency INT DEFAULT 1,
   cal_days CHAR(7),
   cal_bymonth VARCHAR(50),
   cal_bymonthday VARCHAR(100),
   cal_byday VARCHAR(100),
   cal_bysetpos VARCHAR(50),
   cal_byweekno VARCHAR(50),
   cal_byyearday VARCHAR(50),
   cal_wkst CHAR(2) DEFAULT 'MO',
   cal_count INT,
   PRIMARY KEY (cal_id)
);
CREATE TABLE webcal_entry_repeats_not (
  cal_id INT NOT NULL,
  cal_date INT NOT NULL,
  cal_exdate INT DEFAULT 1 NOT NULL,
  PRIMARY KEY ( cal_id, cal_date )
);
CREATE TABLE webcal_entry_user (
  cal_id int DEFAULT 0 NOT NULL,
  cal_login VARCHAR(25) DEFAULT '' NOT NULL,
  cal_status CHAR(1) DEFAULT 'A' NOT NULL,
  cal_category INT DEFAULT NULL,
  cal_percent INT DEFAULT 0 NOT NULL,
  PRIMARY KEY ( cal_id,cal_login )
);
CREATE TABLE webcal_entry_ext_user (
  cal_id INT DEFAULT 0 NOT NULL,
  cal_fullname VARCHAR(50) NOT NULL,
  cal_email VARCHAR(75),
  PRIMARY KEY ( cal_id, cal_fullname )
);
CREATE TABLE webcal_user_pref (
  cal_login VARCHAR(25) NOT NULL,
  cal_setting VARCHAR(25) NOT NULL,
  cal_value VARCHAR(100),
  PRIMARY KEY ( cal_login, cal_setting )
);
CREATE TABLE webcal_user_layers (
  cal_layerid INT DEFAULT 0 NOT NULL,
  cal_login VARCHAR(25) NOT NULL,
  cal_layeruser VARCHAR(25) NOT NULL,
  cal_color VARCHAR(25),
  cal_dups CHAR(1) DEFAULT 'N',
  PRIMARY KEY ( cal_login, cal_layeruser )
);
CREATE TABLE webcal_site_extras (
  cal_id INT DEFAULT 0 NOT NULL,
  cal_name VARCHAR(25) NOT NULL,
  cal_type INT NOT NULL,
  cal_date INT DEFAULT 0,
  cal_remind INT DEFAULT 0,
  cal_data VARCHAR(1024)
);
CREATE TABLE webcal_reminders (
  cal_id INT DEFAULT 0 NOT NULL,
  cal_date INT DEFAULT 0 NOT NULL,
  cal_offset INT DEFAULT 0 NOT NULL,
  cal_related CHAR(1) DEFAULT 'S' NOT NULL,
  cal_before CHAR(1) DEFAULT 'Y' NOT NULL,
  cal_last_sent INT DEFAULT 0 NOT NULL,
  cal_repeats INT DEFAULT 0 NOT NULL,
  cal_duration INT DEFAULT 0 NOT NULL,
  cal_times_sent INT DEFAULT 0 NOT NULL,
  cal_action VARCHAR(12) DEFAULT 'EMAIL' NOT NULL,
  PRIMARY KEY ( cal_id )
);
CREATE TABLE webcal_group (
  cal_group_id INT NOT NULL,
  cal_owner VARCHAR(25),
  cal_name VARCHAR(50) NOT NULL,
  cal_last_update INT NOT NULL,
  PRIMARY KEY ( cal_group_id )
);
CREATE TABLE webcal_group_user (
  cal_group_id INT NOT NULL,
  cal_login VARCHAR(25) NOT NULL,
  PRIMARY KEY ( cal_group_id, cal_login )
);
CREATE TABLE webcal_view (
  cal_view_id INT NOT NULL,
  cal_owner VARCHAR(25) NOT NULL,
  cal_name VARCHAR(50) NOT NULL,
  cal_view_type CHAR(1),
  cal_is_global CHAR(1) DEFAULT 'N' NOT NULL,
  PRIMARY KEY ( cal_view_id )
);
CREATE TABLE webcal_view_user (
  cal_view_id INT NOT NULL,
  cal_login VARCHAR(25) NOT NULL,
  PRIMARY KEY ( cal_view_id, cal_login )
);
CREATE TABLE webcal_config (
  cal_setting VARCHAR(50) NOT NULL,
  cal_value VARCHAR(100),
  PRIMARY KEY ( cal_setting )
);
CREATE TABLE webcal_entry_log (
  cal_log_id INT NOT NULL,
  cal_entry_id INT NOT NULL,
  cal_login VARCHAR(25) NOT NULL,
  cal_user_cal VARCHAR(25),
  cal_type CHAR(1) NOT NULL,
  cal_date INT NOT NULL,
  cal_time INT,
  cal_text VARCHAR(1024),
  PRIMARY KEY ( cal_log_id )
);
CREATE TABLE webcal_categories (
  cat_id INT NOT NULL,
  cat_owner VARCHAR(25),
  cat_name VARCHAR(80) NOT NULL,
  cat_color VARCHAR(8),
  PRIMARY KEY ( cat_id )
);
CREATE TABLE webcal_asst (
  cal_boss VARCHAR(25) NOT NULL,
  cal_assistant VARCHAR(25) NOT NULL,
  PRIMARY KEY ( cal_boss, cal_assistant )
);
CREATE TABLE webcal_nonuser_cals (
  cal_login VARCHAR(25) NOT NULL,
  cal_lastname VARCHAR(25),
  cal_firstname VARCHAR(25),
  cal_admin VARCHAR(25) NOT NULL,
  cal_is_public CHAR(1) DEFAULT 'N' NOT NULL,
  cal_url VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY ( cal_login )
);
CREATE TABLE webcal_import (
  cal_import_id INT NOT NULL,
  cal_name VARCHAR(50),
  cal_date INT NOT NULL,
  cal_type VARCHAR(10) NOT NULL,
  cal_login VARCHAR(25),
  PRIMARY KEY ( cal_import_id )
);
CREATE TABLE webcal_import_data (
  cal_import_id INT NOT NULL,
  cal_id INT NOT NULL,
  cal_login VARCHAR(25) NOT NULL,
  cal_import_type VARCHAR(15) NOT NULL,
  cal_external_id VARCHAR(200),
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
  cal_user VARCHAR(25),
  cal_allow_nav CHAR(1) DEFAULT 'Y',
  cal_cat_id INT,
  cal_include_empty CHAR(1) DEFAULT 'N',
  cal_show_in_trailer CHAR(1) DEFAULT 'N',
  cal_update_date INT NOT NULL,
  PRIMARY KEY ( cal_report_id )
);
CREATE TABLE webcal_report_template (
  cal_report_id INT NOT NULL,
  cal_template_type CHAR(1) NOT NULL,
  cal_template_text VARCHAR(1024),
  PRIMARY KEY ( cal_report_id, cal_template_type )
);
CREATE TABLE webcal_access_user (
  cal_login VARCHAR(25) NOT NULL,
  cal_other_user VARCHAR(25) NOT NULL,
  cal_can_view INT DEFAULT '0' NOT NULL,
  cal_can_edit INT DEFAULT '0' NOT NULL,
  cal_can_approve INT DEFAULT '0' NOT NULL,
  cal_can_invite CHAR(1) DEFAULT 'Y' NOT NULL,
  cal_can_email CHAR(1) DEFAULT 'Y' NOT NULL,
  cal_can_see_times_only CHAR(1) DEFAULT 'N' NOT NULL,
  PRIMARY KEY ( cal_login, cal_other_user )
);
CREATE TABLE webcal_access_function (
  cal_login VARCHAR(25) NOT NULL,
  cal_permissions VARCHAR(64) NOT NULL,
  PRIMARY KEY ( cal_login )
);
CREATE TABLE webcal_user_template (
  cal_login VARCHAR(25) NOT NULL,
  cal_type CHAR(1) NOT NULL,
  cal_template_text VARCHAR(1024),
  PRIMARY KEY ( cal_login, cal_type )
);
CREATE TABLE webcal_entry_categories (
  cal_id INT DEFAULT 0 NOT NULL,
  cat_id INT DEFAULT 0 NOT NULL,
  cat_order INT DEFAULT 0 NOT NULL,
  cat_owner varchar(25)
);
CREATE TABLE webcal_blob (
  cal_blob_id INT NOT NULL,
  cal_id INT,
  cal_login VARCHAR(25),
  cal_name VARCHAR(30),
  cal_description VARCHAR(128),
  cal_size INT,
  cal_mime_type VARCHAR(50),
  cal_type CHAR(1) NOT NULL,
  cal_mod_date INT NOT NULL,
  cal_mod_time INT NOT NULL,
  cal_blob BLOB,
  PRIMARY KEY ( cal_blob_id )
);
CREATE TABLE webcal_timezones (
  tzid VARCHAR(100) DEFAULT ''  NOT NULL,
  dtstart VARCHAR(25) DEFAULT NULL,
  dtend VARCHAR(25) DEFAULT NULL,
  vtimezone VARCHAR(500),
  PRIMARY KEY  ( tzid )
);
