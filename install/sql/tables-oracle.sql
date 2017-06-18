CREATE TABLE webcal_user (
  cal_login VARCHAR2(25) NOT NULL,
  cal_address VARCHAR2(75) NULL,
  cal_birthday INT NULL,
  cal_email VARCHAR2(75) NULL,
  cal_enabled CHAR(1) DEFAULT 'Y',
  cal_firstname VARCHAR2(25),
  cal_lastname VARCHAR2(25),
  cal_is_admin CHAR(1) DEFAULT 'N',
  cal_last_login INT NULL,
  cal_passwd VARCHAR2(32),
  cal_telephone VARCHAR2(50) NULL,
  cal_title VARCHAR2(75) NULL,
  PRIMARY KEY ( cal_login )
);
INSERT INTO webcal_user ( cal_login, cal_passwd, cal_lastname, cal_firstname,
  cal_is_admin ) VALUES ( 'admin', '21232f297a57a5a743894a0e4a801fc3',
  'Administrator', 'Default', 'Y' );
CREATE TABLE webcal_entry (
  cal_id INT NOT NULL,
  cal_access CHAR(1) DEFAULT 'P',
  cal_completed INT NULL,
  cal_create_by VARCHAR2(25) NOT NULL,
  cal_date INT NOT NULL,
  cal_description VARCHAR2(1024),
  cal_due_date INT NULL,
  cal_due_time INT NULL,
  cal_duration INT NOT NULL,
  cal_ext_for_id INT NULL,
  cal_group_id INT NULL,
  cal_location VARCHAR2(100) NULL,
  cal_mod_date INT,
  cal_mod_time INT,
  cal_name VARCHAR2(80) NOT NULL,
  cal_priority INT DEFAULT 5,
  cal_time INT NULL,
  cal_type CHAR(1) DEFAULT 'E',
  cal_url VARCHAR2(100) NULL,
  PRIMARY KEY ( cal_id )
);
CREATE TABLE webcal_entry_repeats (
  cal_id INT DEFAULT 0 NOT NULL,
  cal_days CHAR(7),
  cal_end INT,
  cal_endtime INT NULL,
  cal_frequency INT DEFAULT 1,
  cal_type VARCHAR2(20),
  cal_byday VARCHAR2(100) NULL,
  cal_bymonth VARCHAR2(50) NULL,
  cal_bymonthday VARCHAR2(100) NULL,
  cal_bysetpos VARCHAR2(50) NULL,
  cal_byweekno VARCHAR2(50) NULL,
  cal_byyearday VARCHAR2(50) NULL,
  cal_count INT NULL,
  cal_wkst CHAR(2) DEFAULT 'MO',
  PRIMARY KEY ( cal_id )
);
CREATE TABLE webcal_entry_repeats_not (
  cal_id INT NOT NULL,
  cal_date INT NOT NULL,
  cal_exdate INT DEFAULT 1 NOT NULL,
  PRIMARY KEY ( cal_id, cal_date )
);
CREATE TABLE webcal_entry_user (
  cal_id INT DEFAULT 0 NOT NULL,
  cal_login VARCHAR2(25) NOT NULL,
  cal_category INT NULL,
  cal_percent INT DEFAULT 0 NOT NULL,
  cal_status CHAR(1) DEFAULT 'A',
  PRIMARY KEY ( cal_id,cal_login )
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
  cal_login VARCHAR2(25) NOT NULL,
  cal_layeruser VARCHAR2(25) NOT NULL,
  cal_color VARCHAR2(25) NULL,
  cal_dups CHAR(1) DEFAULT 'N',
  cal_layerid INT DEFAULT 0 NOT NULL,
  PRIMARY KEY ( cal_login, cal_layeruser )
);
CREATE TABLE webcal_site_extras (
  cal_date INT DEFAULT 0,
  cal_id INT DEFAULT 0 NOT NULL,
  cal_name VARCHAR2(25) NOT NULL,
  cal_remind INT DEFAULT 0,
  cal_type INT NOT NULL,
  cal_data LONG
);
CREATE TABLE webcal_reminders (
  cal_id INT DEFAULT 0 NOT NULL,
  cal_action VARCHAR2(12) DEFAULT 'EMAIL' NOT NULL,
  cal_before CHAR(1) DEFAULT 'Y' NOT NULL,
  cal_date INT DEFAULT 0 NOT NULL,
  cal_duration INT DEFAULT 0 NOT NULL,
  cal_last_sent INT DEFAULT 0 NOT NULL,
  cal_offset INT DEFAULT 0 NOT NULL,
  cal_related CHAR(1) DEFAULT 'S' NOT NULL,
  cal_repeats INT DEFAULT 0 NOT NULL,
  cal_times_sent INT DEFAULT 0 NOT NULL,
  PRIMARY KEY ( cal_id )
);
CREATE TABLE webcal_group (
  cal_group_id INT NOT NULL,
  cal_last_update INT NOT NULL,
  cal_name VARCHAR2(50) NOT NULL,
  cal_owner VARCHAR2(25) NULL,
  PRIMARY KEY ( cal_group_id )
);
CREATE TABLE webcal_group_user (
  cal_group_id INT NOT NULL,
  cal_login VARCHAR2(25) NOT NULL,
  PRIMARY KEY ( cal_group_id, cal_login )
);
CREATE TABLE webcal_view (
  cal_view_id INT NOT NULL,
  cal_is_global CHAR(1) DEFAULT 'N' NOT NULL,
  cal_name VARCHAR2(50) NOT NULL,
  cal_owner VARCHAR2(25) NOT NULL,
  cal_view_type CHAR(1),
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
  cal_date INT NOT NULL,
  cal_entry_id INT NOT NULL,
  cal_login VARCHAR2(25) NOT NULL,
  cal_time INT NULL,
  cal_type CHAR(1) NOT NULL,
  cal_user_cal VARCHAR2(25) NULL,
  cal_text VARCHAR2(1024),
  PRIMARY KEY ( cal_log_id )
);
CREATE TABLE webcal_categories (
  cat_id INT NOT NULL,
  cat_color VARCHAR2(8) NULL,
  cat_name VARCHAR2(80) NOT NULL,
  cat_owner VARCHAR2(25),
  PRIMARY KEY ( cat_id )
);
CREATE TABLE webcal_asst (
  cal_boss VARCHAR2(25) NOT NULL,
  cal_assistant VARCHAR2(25) NOT NULL,
  PRIMARY KEY ( cal_boss, cal_assistant )
);
CREATE TABLE webcal_nonuser_cals (
  cal_login VARCHAR2(25) NOT NULL,
  cal_admin VARCHAR2(25) NOT NULL,
  cal_firstname VARCHAR2(25) NULL,
  cal_lastname VARCHAR2(25) NULL,
  cal_is_public CHAR(1) DEFAULT 'N' NOT NULL,
  cal_url VARCHAR2(255) DEFAULT NULL,
  PRIMARY KEY ( cal_login )
);
CREATE TABLE webcal_import (
  cal_import_id INT NOT NULL,
  cal_date INT NOT NULL,
  cal_login VARCHAR2(25) NULL,
  cal_name VARCHAR2(50) NULL,
  cal_type VARCHAR2(10) NOT NULL,
  PRIMARY KEY ( cal_import_id )
);
CREATE TABLE webcal_import_data (
  cal_id INT NOT NULL,
  cal_login VARCHAR2(25) NOT NULL,
  cal_external_id VARCHAR2(200) NULL,
  cal_import_id INT NOT NULL,
  cal_import_type VARCHAR2(15) NOT NULL,
  PRIMARY KEY ( cal_id, cal_login )
);
CREATE TABLE webcal_report (
  cal_report_id INT NOT NULL,
  cal_allow_nav CHAR(1) DEFAULT 'Y',
  cal_cat_id INT NULL,
  cal_include_empty CHAR(1) DEFAULT 'N',
  cal_include_header CHAR(1) DEFAULT 'Y' NOT NULL,
  cal_is_global CHAR(1) DEFAULT 'N' NOT NULL,
  cal_login VARCHAR2(25) NOT NULL,
  cal_report_name VARCHAR2(50) NOT NULL,
  cal_report_type VARCHAR2(20) NOT NULL,
  cal_show_in_trailer CHAR(1) DEFAULT 'N',
  cal_time_range INT NOT NULL,
  cal_update_date INT NOT NULL,
  cal_user VARCHAR2(25) NULL,
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
  cal_can_approve INT DEFAULT 0 NOT NULL,
  cal_can_edit INT DEFAULT 0 NOT NULL,
  cal_can_email CHAR(1) DEFAULT 'Y' NOT NULL,
  cal_can_invite CHAR(1) DEFAULT 'Y' NOT NULL,
  cal_can_view INT DEFAULT 0 NOT NULL,
  cal_see_time_only CHAR(1) DEFAULT 'N' NOT NULL,
  PRIMARY KEY ( cal_login, cal_other_user )
);
CREATE TABLE webcal_access_function (
  cal_login VARCHAR2(50) NOT NULL,
  cal_permissions VARCHAR2(64) NOT NULL,
  PRIMARY KEY ( cal_login )
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
  cal_description VARCHAR2(128) NULL,
  cal_id INT NULL,
  cal_login VARCHAR2(25) NULL,
  cal_mime_type VARCHAR2(50) NULL,
  cal_mod_date INT NOT NULL,
  cal_mod_time INT NOT NULL,
  cal_name VARCHAR2(30) NULL,
  cal_size INT NULL,
  cal_type CHAR(1) NOT NULL,
  cal_blob BLOB,
  PRIMARY KEY ( cal_blob_id )
);
CREATE TABLE webcal_timezones (
  tzid VARCHAR2(100) DEFAULT '' NOT NULL,
  dtstart VARCHAR2(25) DEFAULT NULL,
  dtend VARCHAR2(25) DEFAULT NULL,
  vtimezone VARCHAR2(4000),
  PRIMARY KEY ( tzid )
);
