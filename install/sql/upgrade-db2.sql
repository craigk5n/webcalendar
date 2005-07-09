/*upgrade_v0.9.13*/
UPDATE webcal_entry SET cal_time = -1 WHERE cal_time is null;

CREATE TABLE webcal_entry_repeats (
  cal_id INT DEFAULT '0' NOT NULL,
  cal_type VARCHAR(20),
  cal_end INT,
  cal_frequency INT DEFAULT '1',
  cal_days CHAR(7),
  PRIMARY KEY (cal_id)
);

/*upgrade_v0.9.22*/
CREATE TABLE webcal_user_layers (
  cal_layerid INT DEFAULT '0' NOT NULL,
  cal_login varchar(25) NOT NULL,
  cal_layeruser varchar(25) NOT NULL,
  cal_color varchar(25) NULL,
  cal_dups CHAR(1) DEFAULT 'N',
  PRIMARY KEY ( cal_login, cal_layeruser )
);

/*upgrade_v0.9.27*/
CREATE TABLE webcal_site_extras (
  cal_id INT DEFAULT '0' NOT NULL,
  cal_name VARCHAR(25) NOT NULL,
  cal_type INT NOT NULL,
  cal_date INT DEFAULT '0',
  cal_remind INT DEFAULT '0',
  cal_data TEXT,
  PRIMARY KEY ( cal_id, cal_name, cal_type )
);

CREATE TABLE webcal_reminder_log (
  cal_id INT DEFAULT '0' NOT NULL,
  cal_name VARCHAR(25) NOT NULL,
  cal_event_date INT NOT NULL DEFAULT 0,
  cal_last_sent INT NOT NULL DEFAULT 0,
  PRIMARY KEY ( cal_id, cal_name, cal_event_date )
);

/*upgrade_v0.9.35*/
CREATE TABLE webcal_group (
  cal_group_id INT NOT NULL,
  cal_owner VARCHAR(25) NULL,
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
  PRIMARY KEY ( cal_view_id )
);

CREATE TABLE webcal_view_user (
  cal_view_id INT NOT NULL,
  cal_login VARCHAR(25) NOT NULL,
  PRIMARY KEY ( cal_view_id, cal_login )
);

CREATE TABLE webcal_config (
  cal_setting VARCHAR(50) NOT NULL,
  cal_value VARCHAR(50) NULL,
  PRIMARY KEY ( cal_setting )
);

CREATE TABLE webcal_entry_log (
  cal_log_id INT NOT NULL,
  cal_entry_id INT NOT NULL,
  cal_login VARCHAR(25) NOT NULL,
  cal_type CHAR(1) NOT NULL,
  cal_date INT NOT NULL,
  cal_time INT NULL,
  cal_text TEXT,
  PRIMARY KEY ( cal_log_id )
);

/*upgrade_v0.9.37*/
ALTER TABLE webcal_entry_log ADD cal_user_cal VARCHAR(25);

CREATE TABLE webcal_entry_repeats_not (
  cal_id INT NOT NULL,
  cal_date INT NOT NULL,
  PRIMARY KEY ( cal_id, cal_date )
);

/*upgrade_v0.9.38*/
ALTER TABLE webcal_entry_user ADD cal_category INT DEFAULT NULL;

CREATE TABLE webcal_categories (
  cat_id INT NOT NULL,
  cat_owner VARCHAR(25),
  cat_name VARCHAR(80) NOT NULL,
  PRIMARY KEY ( cat_id )
);

/*upgrade_v0.9.39*/
DELETE FROM webcal_config WHERE cal_setting LIKE 'DATE_FORMAT%';

DELETE FROM webcal_user_pref WHERE cal_setting LIKE 'DATE_FORMAT%';

/*upgrade_v0.9.40*/
CREATE TABLE webcal_asst (
  cal_boss VARCHAR(25) NOT NULL,
  cal_assistant VARCHAR(25) NOT NULL,
  PRIMARY KEY ( cal_boss, cal_assistant )
);

CREATE TABLE webcal_entry_ext_user (
  cal_id INT DEFAULT 0 NOT NULL,
  cal_fullname VARCHAR(50) NOT NULL,
  cal_email VARCHAR(75) NULL,
  PRIMARY KEY ( cal_id, cal_fullname )
);

ALTER TABLE webcal_entry ADD cal_ext_for_id INT NULL;

/*upgrade_v0.9.41*/
CREATE TABLE webcal_nonuser_cals (
  cal_login VARCHAR(25) NOT NULL,
  cal_lastname VARCHAR(25),
  cal_firstname VARCHAR(25),
  cal_admin VARCHAR(25) NOT NULL,
  PRIMARY KEY ( cal_login )
);

/*upgrade_v0.9.42*/
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
  cal_template_text TEXT,
  PRIMARY KEY ( cal_report_id, cal_template_type )
);
CREATE TABLE webcal_import_data (
  cal_id int NOT NULL,
  cal_login VARCHAR(25) NOT NULL,
  cal_import_type VARCHAR(15) NOT NULL,
  cal_external_id VARCHAR(200) NULL,
  PRIMARY KEY  ( cal_id, cal_login )
);

/*upgrade_v0.9.43*/
ALTER TABLE webcal_user MODIFY cal_passwd VARCHAR(32) NULL;

DROP TABLE webcal_import_data;

CREATE TABLE webcal_import (
  cal_import_id INT NOT NULL,
  cal_name VARCHAR(50) NULL,
  cal_date INT NOT NULL,
  cal_type VARCHAR(10) NOT NULL,
  cal_login VARCHAR(25) NULL,
  PRIMARY KEY ( cal_import_id )
);

CREATE TABLE webcal_import_data (
  cal_import_id INT NOT NULL,
  cal_id INT NOT NULL,
  cal_login VARCHAR(25) NOT NULL,
  cal_import_type VARCHAR(15) NOT NULL,
  cal_external_id VARCHAR(200) NULL,
  PRIMARY KEY  ( cal_id, cal_login )
);

/*upgrade_v1.0RC3*/
ALTER TABLE webcal_view ADD cal_is_global CHAR(1) DEFAULT 'N' NOT NULL;

UPDATE webcal_user_pref SET cal_value = 'day.php'
  WHERE cal_value = 'day' AND cal_setting = 'STARTVIEW';

UPDATE webcal_user_pref SET cal_value = 'week.php'
  WHERE cal_value = 'week' AND cal_setting = 'STARTVIEW';

UPDATE webcal_user_pref SET cal_value = 'month.php'
  WHERE cal_value = 'month' AND cal_setting = 'STARTVIEW'
;
UPDATE webcal_user_pref SET cal_value = 'year.php'
  WHERE cal_value = 'year' AND cal_setting = 'STARTVIEW';

UPDATE webcal_config SET cal_value = 'week.php'
  WHERE cal_setting = 'STARTVIEW';

/*upgrade_v1.1.0*/
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

/*upgrade_v1.1.0-CVS*/

CREATE TABLE webcal_tz_zones (
  zone_name VARCHAR(50) NOT NULL default '',
  zone_gmtoff INT NOT NULL default '0',
  zone_rules VARCHAR(50) NOT NULL default '',
  zone_format VARCHAR(20) NOT NULL default '',
  zone_from BIGINT NOT NULL default '0',
  zone_until BIGINT NOT NULL default '0',
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

CREATE TABLE webcal_tz_list (
  tz_list_id INT NOT NULL default '0',
  tz_list_name VARCHAR(50) NOT NULL default '',
  tz_list_text VARCHAR(75) NOT NULL default ''
);

ALTER TABLE webcal_nonuser_cals
  ADD cal_is_global CHAR(1) DEFAULT 'N' NOT NULL;

CREATE TABLE webcal_user_template (
  cal_login VARCHAR(25) NOT NULL,
  cal_type CHAR(1) NOT NULL,
  cal_template_text TEXT,
  PRIMARY KEY ( cal_login, cal_type )
);


