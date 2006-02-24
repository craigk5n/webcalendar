CREATE TABLE webcal_user (
  cal_login VARCHAR2(25) NOT NULL,
  cal_passwd VARCHAR2(32),
  cal_lastname VARCHAR2(25),
  cal_firstname VARCHAR2(25),
  cal_is_admin CHAR(1) DEFAULT 'N',
  cal_email VARCHAR2(75) NULL,
  PRIMARY KEY ( cal_login )
);
INSERT INTO webcal_user ( cal_login, cal_passwd, cal_lastname, cal_firstname, cal_is_admin ) VALUES ( 'admin', '21232f297a57a5a743894a0e4a801fc3', 'Administrator', 'Default', 'Y' );
CREATE TABLE webcal_entry (
  cal_id INT NOT NULL,
  cal_group_id INT NULL,
  cal_ext_for_id INT NULL,
  cal_create_by VARCHAR2(25) NOT NULL,
  cal_date INT NOT NULL,
  cal_time INT NULL,
  cal_mod_date INT,
  cal_mod_time INT,
  cal_duration INT NOT NULL,
  cal_due_date INT NULL,
  cal_due_time INT NULL,
  cal_priority INT DEFAULT 2,
  cal_type CHAR(1) DEFAULT 'E',
  cal_access CHAR(1) DEFAULT 'P',
  cal_name VARCHAR2(80) NOT NULL,
  cal_location VARCHAR2(50) NULL,
  cal_url VARCHAR2(100) NULL,
  cal_completed INT NULL,
  cal_description VARCHAR2(1024),
  PRIMARY KEY ( cal_id )
);
CREATE TABLE webcal_entry_repeats (
   cal_id INT DEFAULT 0 NOT NULL,
   cal_type VARCHAR2(20),
   cal_end INT,
   cal_endtime INT NULL,
   cal_frequency INT DEFAULT 1,
   cal_days CHAR(7),
   cal_bymonth VARCHAR2(50) NULL,
   cal_bymonthday VARCHAR2(100) NULL,
   cal_byday VARCHAR2(100) NULL,
   cal_bysetpos VARCHAR2(50) NULL,
   cal_byweekno VARCHAR2(50) NULL,
   cal_byyearday VARCHAR2(50) NULL,
   cal_wkst CHAR(2) DEFAULT 'MO',
   cal_count INT NULL,
   PRIMARY KEY (cal_id)
);
CREATE TABLE webcal_entry_repeats_not (
  cal_id INT NOT NULL,
  cal_date INT NOT NULL,
  cal_exdate INT NOT NULL default 1,
  PRIMARY KEY ( cal_id, cal_date )
);
CREATE TABLE webcal_entry_user (
  cal_id int DEFAULT 0 NOT NULL,
  cal_login VARCHAR2(25) NOT NULL,
  cal_status CHAR(1) DEFAULT 'A',
  cal_category INT NULL,
  cal_percent INT DEFAULT 0 NOT NULL,
  PRIMARY KEY (cal_id,cal_login)
);
CREATE TABLE webcal_entry_ext_user (
  cal_id INT DEFAULT 0 NOT NULL,
  cal_fullname VARCHAR2(50) NOT NULL,
  cal_email VARCHAR2(75) NULL,
  PRIMARY KEY ( cal_id, cal_fullname )
);
CREATE TABLE webcal_user_pref (
  cal_login VARCHAR2(25) NOT NULL,
  cal_setting VARCHAR2(25) NOT NULL,
  cal_value VARCHAR2(100) NULL,
  PRIMARY KEY ( cal_login, cal_setting )
);
CREATE TABLE webcal_user_layers (
  cal_layerid INT DEFAULT 0 NOT NULL,
  cal_login VARCHAR2(25) NOT NULL,
  cal_layeruser VARCHAR2(25) NOT NULL,
  cal_color VARCHAR2(25) NULL,
  cal_dups CHAR(1) DEFAULT 'N',
  PRIMARY KEY ( cal_login, cal_layeruser )
);
CREATE TABLE webcal_site_extras (
  cal_id INT DEFAULT 0 NOT NULL,
  cal_name VARCHAR2(25) NOT NULL,
  cal_type INT NOT NULL,
  cal_date INT DEFAULT 0,
  cal_remind INT DEFAULT 0,
  cal_data LONG
);
CREATE TABLE webcal_reminder_log (
  cal_id INT DEFAULT 0 NOT NULL,
  cal_name VARCHAR2(25) NOT NULL,
  cal_event_date INT DEFAULT 0 NOT NULL,
  cal_last_sent INT DEFAULT 0 NOT NULL,
  PRIMARY KEY ( cal_id, cal_name, cal_event_date )
);
CREATE TABLE webcal_group (
  cal_group_id INT NOT NULL,
  cal_owner VARCHAR2(25) NULL,
  cal_name VARCHAR2(50) NOT NULL,
  cal_last_update INT NOT NULL,
  PRIMARY KEY ( cal_group_id )
);
CREATE TABLE webcal_group_user (
  cal_group_id INT NOT NULL,
  cal_login VARCHAR2(25) NOT NULL,
  PRIMARY KEY ( cal_group_id, cal_login )
);
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
CREATE TABLE webcal_config (
  cal_setting VARCHAR2(50) NOT NULL,
  cal_value VARCHAR2(100) NULL,
  PRIMARY KEY ( cal_setting )
);
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
  cal_is_public CHAR(1) NOT NULL DEFAULT 'N',
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
  cal_login VARCHAR2(25) NOT NULL,
  cal_report_id INT NOT NULL,
  cal_is_global CHAR(1) DEFAULT 'N' NOT NULL,
  cal_report_type VARCHAR2(20) NOT NULL,
  cal_include_header CHAR(1) DEFAULT 'Y' NOT NULL,
  cal_report_name VARCHAR2(50) NOT NULL,
  cal_time_range INT NOT NULL,
  cal_user VARCHAR2(25) NULL,
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
  cal_login VARCHAR2(50) NOT NULL,
  cal_other_user VARCHAR2(50) NOT NULL,
  cal_can_view INT DEFAULT '0' NOT NULL,
  cal_can_edit INT DEFAULT '0' NOT NULL,
  cal_can_approve INT DEFAULT 'O' NOT NULL,
  cal_can_invite CHAR(1) DEFAULT 'Y' NOT NULL,
  cal_can_email CHAR(1) DEFAULT 'Y' NOT NULL,
  cal_see_time_only CHAR(1) DEFAULT 'N' NOT NULL,
  PRIMARY KEY ( cal_login, cal_other_user )
);
CREATE TABLE webcal_access_function (
  cal_login VARCHAR2(50) NOT NULL,
  cal_permissions VARCHAR2(64) NOT NULL,
  PRIMARY KEY ( cal_login )
);
CREATE TABLE webcal_tz_zones (
  zone_name VARCHAR2(50) DEFAULT '' NOT NULL,
  zone_gmtoff INT DEFAULT 0 NOT NULL,
  zone_rules VARCHAR2(50) DEFAULT '' NOT NULL,
  zone_format VARCHAR2(20) DEFAULT '' NOT NULL,
  zone_from bigint DEFAULT 0 NOT NULL,
  zone_until bigint DEFAULT 0 NOT NULL,
  zone_cc CHAR(2) DEFAULT '' NOT NULL,
  zone_coord VARCHAR2(20) DEFAULT '' NOT NULL,
  zone_country VARCHAR2(50) DEFAULT '' NOT NULL
);
CREATE TABLE webcal_tz_rules (
  rule_name VARCHAR2(50) DEFAULT '' NOT NULL,
  rule_from INT DEFAULT 0 NOT NULL,
  rule_to INT DEFAULT 0 NOT NULL,
  rule_type VARCHAR2(20) DEFAULT '' NOT NULL,
  rule_in INT DEFAULT ' NOT NULL,
  rule_on VARCHAR2(20) DEFAULT '' NOT NULL,
  rule_at INT DEFAULT 0 NOT NULL,
  rule_at_suffix CHAR(1) DEFAULT '' NOT NULL,
  rule_save INT DEFAULT 0 NOT NULL,
  rule_letter VARCHAR2(5) DEFAULT '' NOT NULL
);
CREATE TABLE webcal_user_template (
  cal_login VARCHAR2(25) NOT NULL,
  cal_type CHAR(1) NOT NULL,
  cal_template_text VARCHAR2(1024),
  PRIMARY KEY ( cal_login, cal_type )
);
CREATE TABLE webcal_entry_categories (
  cal_id INT DEFAULT 0 NOT NULL,
  cat_id INT DEFAULT 0 NOT NULL,
  cat_order INT DEFAULT 0 NOT NULL,
  cat_owner VARCHAR2(25) NULL
);
CREATE TABLE webcal_blob (
  cal_blob_id INT NOT NULL,
  cal_id INT NULL,
  cal_login VARCHAR2(25) NULL,
  cal_name VARCHAR2(30) NULL,
  cal_description VARCHAR2(128) NULL,
  cal_size INT NULL,
  cal_mime_type VARCHAR(50) NULL,
  cal_type CHAR(1) NOT NULL,
  cal_mod_date INT NOT NULL,
  cal_mod_time INT NOT NULL,
  cal_blob BLOB,
  PRIMARY KEY ( cal_blob_id )
);
