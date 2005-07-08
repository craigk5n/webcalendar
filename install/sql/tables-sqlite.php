<?php
/*
 * Description:
 * This file will create a SQLite database file
 * 
 */
 function populate_sqlite_db ( $database, $db ) {
		 sqlite_query($db, "CREATE TABLE webcal_user (cal_login VARCHAR(25) NOT NULL, cal_passwd VARCHAR(32), cal_lastname VARCHAR(25), cal_firstname VARCHAR(25), cal_is_admin CHAR(1) DEFAULT 'N',cal_email VARCHAR(75) NULL, PRIMARY KEY ( cal_login ))");
		 sqlite_query($db, "INSERT INTO webcal_user ( cal_login, cal_passwd, cal_lastname, cal_firstname, cal_is_admin ) VALUES ( 'admin', '21232f297a57a5a743894a0e4a801fc3', 'Administrator', 'Default', 'Y' );");
		 sqlite_query($db, "CREATE TABLE webcal_entry ( cal_id INT NOT NULL, cal_group_id INT NULL, cal_ext_for_id INT NULL,  cal_create_by VARCHAR(25) NOT NULL, cal_date INT NOT NULL, cal_time INT NULL, cal_mod_date INT, cal_mod_time INT, cal_duration INT NOT NULL, cal_priority INT DEFAULT 2, cal_type CHAR(1) DEFAULT 'E', cal_access CHAR(1) DEFAULT 'P', cal_name VARCHAR(80) NOT NULL, cal_description TEXT, PRIMARY KEY ( cal_id ))");
		 sqlite_query($db, "CREATE TABLE webcal_entry_repeats ( cal_id INT DEFAULT 0 NOT NULL, cal_type VARCHAR(20), cal_end INT, cal_frequency INT DEFAULT 1, cal_days CHAR(7), PRIMARY KEY (cal_id))");
		 sqlite_query($db, "CREATE TABLE webcal_entry_repeats_not ( cal_id INT NOT NULL, cal_date INT NOT NULL, PRIMARY KEY ( cal_id, cal_date ))");
		 sqlite_query($db, "CREATE TABLE webcal_entry_user ( cal_id INT DEFAULT 0 NOT NULL, cal_login VARCHAR(25) NOT NULL, cal_status CHAR(1) DEFAULT 'A', cal_category INT DEFAULT NULL, PRIMARY KEY ( cal_id, cal_login ))");
		 sqlite_query($db, "CREATE TABLE webcal_entry_ext_user ( cal_id INT DEFAULT 0 NOT NULL, cal_fullname VARCHAR(50) NOT NULL, cal_email VARCHAR(75) NULL, PRIMARY KEY ( cal_id, cal_fullname ))");
		 sqlite_query($db, "CREATE TABLE webcal_user_pref ( cal_login VARCHAR(25) NOT NULL, cal_setting VARCHAR(25) NOT NULL, cal_value VARCHAR(100) NULL, PRIMARY KEY ( cal_login, cal_setting ))");
		 sqlite_query($db, "CREATE TABLE webcal_user_layers ( cal_layerid INT DEFAULT 0 NOT NULL, cal_login VARCHAR(25) NOT NULL, cal_layeruser VARCHAR(25) NOT NULL, cal_color VARCHAR(25) NULL, cal_dups CHAR(1) DEFAULT 'N', PRIMARY KEY ( cal_login, cal_layeruser ))");
		 sqlite_query($db, "CREATE TABLE webcal_site_extras ( cal_id INT DEFAULT 0 NOT NULL, cal_name VARCHAR(25) NOT NULL, cal_type INT NOT NULL, cal_date INT DEFAULT 0, cal_remind INT DEFAULT 0, cal_data TEXT, PRIMARY KEY ( cal_id, cal_name, cal_type ))");
		 sqlite_query($db, "CREATE TABLE webcal_reminder_log ( cal_id INT DEFAULT 0 NOT NULL, cal_name VARCHAR(25) NOT NULL, cal_event_date INT NOT NULL DEFAULT 0, cal_last_sent INT NOT NULL DEFAULT 0, PRIMARY KEY ( cal_id, cal_name, cal_event_date ))");
		 sqlite_query($db, "CREATE TABLE webcal_group ( cal_group_id INT NOT NULL, cal_owner VARCHAR(25) NULL, cal_name VARCHAR(50) NOT NULL, cal_last_update INT NOT NULL, PRIMARY KEY ( cal_group_id ))");
		 sqlite_query($db, "CREATE TABLE webcal_group_user ( cal_group_id INT NOT NULL, cal_login VARCHAR(25) NOT NULL, PRIMARY KEY ( cal_group_id, cal_login ))");
		 sqlite_query($db, "CREATE TABLE webcal_view ( cal_view_id INT NOT NULL, cal_owner VARCHAR(25) NOT NULL, cal_name VARCHAR(50) NOT NULL, cal_view_type CHAR(1), cal_is_global CHAR(1) DEFAULT 'N' NOT NULL, PRIMARY KEY ( cal_view_id ))");
		 sqlite_query($db, "CREATE TABLE webcal_view_user ( cal_view_id INT NOT NULL, cal_login VARCHAR(25) NOT NULL, PRIMARY KEY ( cal_view_id, cal_login ))");
		 sqlite_query($db, "CREATE TABLE webcal_config ( cal_setting VARCHAR(50) NOT NULL, cal_value VARCHAR(100) NULL, PRIMARY KEY ( cal_setting ))");
		 sqlite_query($db, "CREATE TABLE webcal_entry_log ( cal_log_id INT NOT NULL, cal_entry_id INT NOT NULL, cal_login VARCHAR(25) NOT NULL, cal_user_cal VARCHAR(25) NULL, cal_type CHAR(1) NOT NULL, cal_date INT NOT NULL, cal_time INT NULL, cal_text TEXT, PRIMARY KEY ( cal_log_id ))");
		 sqlite_query($db, "CREATE TABLE webcal_categories ( cat_id INT NOT NULL, cat_owner VARCHAR(25) NULL, cat_name VARCHAR(80) NOT NULL, PRIMARY KEY ( cat_id ))");
		 sqlite_query($db, "CREATE TABLE webcal_asst ( cal_boss VARCHAR(25) NOT NULL, cal_assistant VARCHAR(25) NOT NULL, PRIMARY KEY ( cal_boss, cal_assistant ))");
		 sqlite_query($db, "CREATE TABLE webcal_nonuser_cals ( cal_login VARCHAR(25) NOT NULL, cal_lastname VARCHAR(25) NULL, cal_firstname VARCHAR(25) NULL, cal_admin VARCHAR(25) NOT NULL, PRIMARY KEY ( cal_login ))");
		 sqlite_query($db, "CREATE TABLE webcal_import ( cal_import_id INT NOT NULL, cal_name VARCHAR(50) NULL, cal_date INT NOT NULL, cal_type VARCHAR(10) NOT NULL, cal_login VARCHAR(25) NULL, PRIMARY KEY ( cal_import_id ))");
		 sqlite_query($db, "CREATE TABLE webcal_import_data ( cal_import_id INT NOT NULL, cal_id INT NOT NULL, cal_login VARCHAR(25) NOT NULL, cal_import_type VARCHAR(15) NOT NULL, cal_external_id VARCHAR(200) NULL, PRIMARY KEY  ( cal_id, cal_login ))");
		 sqlite_query($db, "CREATE TABLE webcal_report ( cal_login VARCHAR(25) NOT NULL, cal_report_id INT NOT NULL, cal_is_global CHAR(1) DEFAULT 'N' NOT NULL, cal_report_type VARCHAR(20) NOT NULL, cal_include_header CHAR(1) DEFAULT 'Y' NOT NULL, cal_report_name VARCHAR(50) NOT NULL, cal_time_range INT NOT NULL, cal_user VARCHAR(25) NULL, cal_allow_nav CHAR(1) DEFAULT 'Y', cal_cat_id INT NULL, cal_include_empty CHAR(1) DEFAULT 'N', cal_show_in_trailer CHAR(1) DEFAULT 'N', cal_update_date INT NOT NULL, PRIMARY KEY ( cal_report_id ))");
		 sqlite_query($db, "CREATE TABLE webcal_report_template ( cal_report_id INT NOT NULL, cal_template_type CHAR(1) NOT NULL, cal_template_text TEXT, PRIMARY KEY ( cal_report_id, cal_template_type ))");
		 sqlite_query($db, "CREATE TABLE webcal_access_user ( cal_login VARCHAR(50) NOT NULL, cal_other_user VARCHAR(50) NOT NULL, cal_can_view CHAR(1) NOT NULL DEFAULT 'N', cal_can_edit CHAR(1) NOT NULL DEFAULT 'N', cal_can_delete CHAR(1) NOT NULL DEFAULT 'N', cal_can_approve CHAR(1) NOT NULL DEFAULT 'N', PRIMARY KEY ( cal_login, cal_other_user ))");
		 sqlite_query($db, "CREATE TABLE webcal_access_function ( cal_login VARCHAR(50) NOT NULL, cal_permissions VARCHAR(64) NOT NULL, PRIMARY KEY ( cal_login ))");
		 sqlite_query($db, "CREATE TABLE webcal_tz_zones ( zone_name VARCHAR(50) NOT NULL default '', zone_gmtoff INT NOT NULL default '0', zone_rules VARCHAR(50) NOT NULL default '', zone_format VARCHAR(20) NOT NULL default '', zone_from BIGINT NOT NULL default '0', zone_until BIGINT NOT NULL default '0', zone_cc CHAR(2) NOT NULL default '', zone_coord VARCHAR(20) NOT NULL default '', zone_country VARCHAR(50) NOT NULL default '')");
		 sqlite_query($db, "CREATE TABLE webcal_tz_rules ( rule_name VARCHAR(50) NOT NULL default '', rule_from INT NOT NULL default '0', rule_to INT NOT NULL default '0', rule_type VARCHAR(20) NOT NULL default '', rule_in INT NOT NULL default '0', rule_on VARCHAR(20) NOT NULL default '', rule_at INT NOT NULL default '0', rule_at_suffix CHAR(1) NOT NULL default '', rule_save INT NOT NULL default '0', rule_letter VARCHAR(5) NOT NULL default '')");
		 sqlite_query($db, "CREATE TABLE webcal_tz_list ( tz_list_id INT  NOT NULL default '0', tz_list_name VARCHAR(50) NOT NULL default '', tz_list_text VARCHAR(75) NOT NULL default '')");

# default settings
/*
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'application_name', 'WebCalendar' )");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'LANGUAGE', 'Browser-defined' )");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'demo_mode', 'N' )");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'require_approvals', 'Y' )");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'groups_enabled', 'N' )");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'user_sees_only_his_groups', 'N' )");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'categories_enabled', 'N' )");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'allow_conflicts', 'N' )");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'conflict_repeat_months', '6' )");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'disable_priority_field', 'N' )");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'disable_access_field', 'N' )");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'disable_participants_field', 'N' )");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'disable_repeating_field', 'N' )");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'allow_view_other', 'Y' )");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'email_fallback_from', 'youremailhere' )");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'remember_last_login', 'Y' )");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'allow_color_customization', 'Y' )");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'BGCOLOR','#FFFFFF')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'TEXTCOLOR','#000000')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'H2COLOR','#000000')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'CELLBG','#C0C0C0')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'WEEKENDBG','#D0D0D0')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'TABLEBG','#000000')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'THBG','#FFFFFF')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ('THFG','#000000')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'POPUP_FG','#000000')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'POPUP_BG','#FFFFFF')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'TODAYCELLBG','#FFFF33')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'STARTVIEW', 'week.php' )");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'WEEK_START', '0' )");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'TIME_FORMAT', '12' )");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'DISPLAY_UNAPPROVED', 'Y' )");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'DISPLAY_WEEKNUMBER', 'Y' )");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'WORK_DAY_START_HOUR', '8' )");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'WORK_DAY_END_HOUR', '17' )");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'send_email', 'N' )");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'EMAIL_REMINDER', 'Y' )");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'EMAIL_EVENT_ADDED', 'Y' )");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'EMAIL_EVENT_UPDATED', 'Y' )");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'EMAIL_EVENT_DELETED', 'Y' )");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'EMAIL_EVENT_REJECTED', 'Y' )");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'auto_refresh', 'N')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'nonuser_enabled', 'N')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'allow_html_description', 'N')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'reports_enabled', 'N')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'DISPLAY_WEEKENDS', 'Y')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'DISPLAY_DESC_PRINT_DAY', 'N')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'DATE_FORMAT', '__month__ __dd__, __yyyy__')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'TIME_SLOTS', '24')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'TIMED_EVT_LEN', 'D')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'PUBLISH_ENABLED', 'N')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'DATE_FORMAT_MY', '__month__ __yyyy__')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'DATE_FORMAT_MD', '__month__ __dd__')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'CUSTOM_SCRIPT', 'N')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'CUSTOM_HEADER', 'N')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'CUSTOM_TRAILER', 'N')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'bold_days_in_year', 'Y')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'site_extras_in_popup', 'N')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'add_link_in_views', 'Y')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'allow_conflict_override', 'Y')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'limit_appts', 'N')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'limit_appts_number', '6')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'public_access', 'N')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'public_access_default_visible', 'N')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'public_access_default_selected', 'N')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'public_access_others', 'N')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'public_access_can_add', 'N')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'public_access_add_needs_approval', 'Y')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'public_access_view_part', 'N')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'nonuser_at_top', 'Y')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'allow_external_users', 'N')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'external_notifications', 'N')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'external_reminders', 'N')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'enable_gradients', 'N')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'TIMEZONE', 'America/New_York')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'webcal_program_version', 'v1.1.0-CVS')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'FONTS', 'Arial, Helvetica, sans-serif')");
   sqlite_query($db, "INSERT INTO webcal_config ( cal_setting, cal_value ) VALUES ( 'uac_enabled', 'N')");
 */
}
?>