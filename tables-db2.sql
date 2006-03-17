CREATE TABLE webcal_user (
  cal_login VARCHAR(25) NOT NULL,
  cal_passwd VARCHAR(32),
  cal_lastname VARCHAR(25),
  cal_firstname VARCHAR(25),
  cal_is_admin CHAR(1) DEFAULT 'N',
  cal_email VARCHAR(75),
  PRIMARY KEY ( cal_login )
);

INSERT INTO webcal_user 
 (cal_login, cal_passwd, cal_lastname, cal_firstname, cal_is_admin) 
 VALUES ( 'admin', '21232f297a57a5a743894a0e4a801fc3', 'Administrator', 'Default', 'Y' );


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
  cal_priority INT DEFAULT 2,
  cal_type CHAR(1) DEFAULT 'E',
  cal_access CHAR(1) DEFAULT 'P',
  cal_name VARCHAR(80) NOT NULL,
  cal_description varchar(1024),
  PRIMARY KEY ( cal_id )
);


CREATE TABLE webcal_entry_repeats (
   cal_id INT DEFAULT 0 NOT NULL,
   cal_type VARCHAR(20),
   cal_end INT,
   cal_frequency INT DEFAULT 1,
   cal_days CHAR(7),
   PRIMARY KEY (cal_id)
);


CREATE TABLE webcal_entry_repeats_not (
  cal_id INT NOT NULL,
  cal_date INT NOT NULL,
  PRIMARY KEY ( cal_id, cal_date )
);



CREATE TABLE webcal_entry_user (
  cal_id int DEFAULT 0 NOT NULL,
  cal_login varchar(25) DEFAULT '' NOT NULL, 
  cal_status char(1) DEFAULT 'A' NOT NULL,
  cal_category INT DEFAULT NULL,
  PRIMARY KEY ( cal_id,cal_login )
);


CREATE TABLE webcal_entry_ext_user (
  cal_id INT DEFAULT 0 NOT NULL,
  cal_fullname VARCHAR(50) NOT NULL,
  cal_email VARCHAR(75) DEFAULT NULL,
  PRIMARY KEY ( cal_id, cal_fullname )
);



CREATE TABLE webcal_user_pref (
  cal_login varchar(25) NOT NULL,
  cal_setting varchar(25) NOT NULL,
  cal_value varchar(100),
  PRIMARY KEY ( cal_login, cal_setting )
);



CREATE TABLE webcal_user_layers (
  cal_layerid INT DEFAULT 0 NOT NULL,
  cal_login varchar(25) NOT NULL,
  cal_layeruser varchar(25) NOT NULL,
  cal_color varchar(25),
  cal_dups CHAR(1) DEFAULT 'N',
  PRIMARY KEY ( cal_login, cal_layeruser )
);



CREATE TABLE webcal_site_extras (
  cal_id INT DEFAULT 0 NOT NULL,
  cal_name VARCHAR(25) NOT NULL,
  cal_type INT NOT NULL,
  cal_date INT DEFAULT 0,
  cal_remind INT DEFAULT 0,
  cal_data varchar(1024),
  PRIMARY KEY ( cal_id, cal_name, cal_type )
);


CREATE TABLE webcal_reminder_log (
  cal_id INT DEFAULT 0 NOT NULL,
  cal_name VARCHAR(25) NOT NULL,
  cal_event_date INT NOT NULL DEFAULT 0,
  cal_last_sent INT NOT NULL DEFAULT 0,
  PRIMARY KEY ( cal_id, cal_name, cal_event_date )
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


INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ( 'application_name', 'WebCalendar' );
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ( 'LANGUAGE', 'Browser-defined' );
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ( 'demo_mode', 'N' );
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ( 'require_approvals', 'Y' );
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ( 'groups_enabled', 'N' );
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ( 'user_sees_only_his_groups', 'N' );
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ( 'categories_enabled', 'N' );
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ( 'allow_conflicts', 'N' );
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ( 'conflict_repeat_months', '6' );
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ( 'disable_priority_field', 'N' );
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ( 'disable_access_field', 'N' );
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ( 'disable_participants_field', 'N' );
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ( 'disable_repeating_field', 'N' );
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ( 'allow_view_other', 'Y' );
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ( 'email_fallback_from', 'youremailhere' );
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ( 'remember_last_login', 'Y' );
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ( 'allow_color_customization', 'Y' );
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('BGCOLOR','#FFFFFF');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('TEXTCOLOR','#000000');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('H2COLOR','#000000');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('CELLBG','#C0C0C0');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('WEEKENDBG','#D0D0D0');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('TABLEBG','#000000');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('THBG','#FFFFFF');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('THFG','#000000');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('POPUP_FG','#000000');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('POPUP_BG','#FFFFFF');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('TODAYCELLBG','#FFFF33');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ( 'STARTVIEW', 'week.php' );
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ( 'WEEK_START', '0' );
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ( 'TIME_FORMAT', '12' );
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ( 'DISPLAY_UNAPPROVED', 'Y' );
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ( 'DISPLAY_WEEKNUMBER', 'Y' );
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ( 'WORK_DAY_START_HOUR', '8' );
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ( 'WORK_DAY_END_HOUR', '17' );
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ( 'send_email', 'N' );
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ( 'EMAIL_REMINDER', 'Y' );
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ( 'EMAIL_EVENT_ADDED', 'Y' );
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ( 'EMAIL_EVENT_UPDATED', 'Y' );
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ( 'EMAIL_EVENT_DELETED', 'Y' );
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ( 'EMAIL_EVENT_REJECTED', 'Y' );
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('auto_refresh', 'N');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('nonuser_enabled', 'N');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('allow_html_description', 'N');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('reports_enabled', 'N');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('DISPLAY_WEEKENDS', 'Y');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('DISPLAY_DESC_PRINT_DAY', 'N');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('DATE_FORMAT', '__month__ __dd__, __yyyy__');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('TIME_SLOTS', '12');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('TIMED_EVT_LEN', 'D');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('PUBLISH_ENABLED', 'N');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('DATE_FORMAT_MY', '__month__ __yyyy__');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('DATE_FORMAT_MD', '__month__ __dd__');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('CUSTOM_SCRIPT', 'N');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('CUSTOM_HEADER', 'N');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('CUSTOM_TRAILER', 'N');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('bold_days_in_year', 'Y');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('site_extras_in_popup', 'N');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('add_link_in_views', 'Y');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('allow_conflict_override', 'Y');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('limit_appts', 'N');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('limit_appts_number', '6');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('public_access', 'N');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('public_access_default_visible', 'N');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('public_access_default_selected', 'N');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('public_access_others', 'N');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('public_access_can_add', 'N');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('public_access_add_needs_approval', 'Y');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('public_access_view_part', 'N');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('nonuser_at_top', 'Y');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('allow_external_users', 'N');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('external_notifications', 'N');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('external_reminders', 'N');
INSERT INTO webcal_config ( cal_setting, cal_value )
  VALUES ('enable_gradients', 'N');



CREATE TABLE webcal_entry_log (
  cal_log_id INT NOT NULL,
  cal_entry_id INT NOT NULL,
  cal_login VARCHAR(25) NOT NULL,
  cal_user_cal VARCHAR(25),
  cal_type CHAR(1) NOT NULL,
  cal_date INT NOT NULL,
  cal_time INT,
  cal_text varchar(1024),
  PRIMARY KEY ( cal_log_id )
);


CREATE TABLE webcal_categories (
  cat_id INT NOT NULL,
  cat_owner VARCHAR(25),
  cat_name VARCHAR(80) NOT NULL,
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
  PRIMARY KEY ( cal_login )
);

CREATE TABLE webcal_import (
  cal_import_id INT NOT NULL,
  cal_name VARCHAR(50) DEFAULT NULL,
  cal_date INT NOT NULL,
  cal_type VARCHAR(10) NOT NULL,
  cal_login VARCHAR(25),
  PRIMARY KEY ( cal_import_id )
);

CREATE TABLE webcal_import_data (
  cal_import_id INT NOT NULL,
  cal_id int NOT NULL,
  cal_login VARCHAR(25) NOT NULL,
  cal_import_type VARCHAR(15) NOT NULL,
  cal_external_id VARCHAR(200) DEFAULT NULL,
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
  cal_user VARCHAR(25) DEFAULT NULL,
  cal_allow_nav CHAR(1) DEFAULT 'Y',
  cal_cat_id INT DEFAULT NULL,
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




