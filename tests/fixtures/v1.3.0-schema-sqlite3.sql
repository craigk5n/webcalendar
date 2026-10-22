-- WebCalendar v1.3.0 Schema (SQLite3)
-- This represents the database state at version 1.3.0
-- Used for testing upgrade functionality

-- Core tables as they existed in v1.3.0

CREATE TABLE webcal_config (
  cal_setting VARCHAR(50) NOT NULL,
  cal_value VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY (cal_setting)
);

CREATE TABLE webcal_user (
  cal_login VARCHAR(25) NOT NULL,
  cal_passwd VARCHAR(25) DEFAULT NULL,
  cal_firstname VARCHAR(25) DEFAULT NULL,
  cal_lastname VARCHAR(25) DEFAULT NULL,
  cal_is_admin CHAR(1) DEFAULT 'N',
  cal_email VARCHAR(75) DEFAULT NULL,
  cal_enabled CHAR(1) DEFAULT 'Y',
  cal_telephone VARCHAR(50) DEFAULT NULL,
  cal_address VARCHAR(75) DEFAULT NULL,
  cal_title VARCHAR(75) DEFAULT NULL,
  cal_birthday VARCHAR(25) DEFAULT NULL,
  cal_last_login INT DEFAULT NULL,
  PRIMARY KEY (cal_login)
);

CREATE TABLE webcal_entry (
  cal_id INT NOT NULL,
  cal_group_id INT DEFAULT NULL,
  cal_duration INT NOT NULL DEFAULT 0,
  cal_due_date INT DEFAULT NULL,
  cal_due_time INT DEFAULT NULL,
  cal_location VARCHAR(100) DEFAULT NULL,
  cal_url VARCHAR(100) DEFAULT NULL,
  cal_created_by VARCHAR(25) NOT NULL,
  cal_create_date INT NOT NULL,
  cal_mod_date INT DEFAULT NULL,
  cal_mod_by VARCHAR(25) DEFAULT NULL,
  cal_access VARCHAR(10) DEFAULT 'P',
  cal_name VARCHAR(80) NOT NULL,
  cal_description TEXT,
  cal_time INT DEFAULT NULL,
  cal_type VARCHAR(25) DEFAULT 'E',
  cal_priority INT DEFAULT 5,
  cal_status INT DEFAULT 0,
  cal_date INT NOT NULL,
  cal_mdate INT DEFAULT NULL,
  PRIMARY KEY (cal_id)
);

CREATE TABLE webcal_entry_repeats (
  cal_id INT NOT NULL,
  cal_days CHAR(7) DEFAULT NULL,
  cal_end INT DEFAULT NULL,
  cal_frequency INT DEFAULT 1,
  cal_type VARCHAR(20) DEFAULT NULL,
  PRIMARY KEY (cal_id)
);

CREATE TABLE webcal_entry_repeats_not (
  cal_id INT NOT NULL,
  cal_date INT NOT NULL,
  PRIMARY KEY (cal_id, cal_date)
);

CREATE TABLE webcal_entry_user (
  cal_id INT NOT NULL,
  cal_login VARCHAR(25) NOT NULL,
  cal_status CHAR(1) DEFAULT 'A',
  cal_category INT DEFAULT NULL,
  PRIMARY KEY (cal_id, cal_login)
);

CREATE TABLE webcal_user_pref (
  cal_login VARCHAR(25) NOT NULL,
  cal_setting VARCHAR(25) NOT NULL,
  cal_value VARCHAR(50) DEFAULT NULL,
  PRIMARY KEY (cal_login, cal_setting)
);

CREATE TABLE webcal_user_layers (
  cal_login VARCHAR(25) NOT NULL,
  cal_layeruser VARCHAR(25) NOT NULL,
  cal_color VARCHAR(25) DEFAULT NULL,
  cal_dups CHAR(1) DEFAULT 'N',
  cal_layerid INT NOT NULL,
  PRIMARY KEY (cal_login, cal_layeruser)
);

CREATE TABLE webcal_categories (
  cat_id INT NOT NULL,
  cat_owner VARCHAR(25) DEFAULT NULL,
  cat_name VARCHAR(50) NOT NULL,
  cat_color VARCHAR(25) DEFAULT NULL,
  PRIMARY KEY (cat_id)
);

CREATE TABLE webcal_group (
  cal_group_id INT NOT NULL,
  cal_owner VARCHAR(25) DEFAULT NULL,
  cal_name VARCHAR(50) NOT NULL,
  cal_last_update INT DEFAULT NULL,
  PRIMARY KEY (cal_group_id)
);

CREATE TABLE webcal_group_user (
  cal_group_id INT NOT NULL,
  cal_login VARCHAR(25) NOT NULL,
  PRIMARY KEY (cal_group_id, cal_login)
);

CREATE TABLE webcal_view (
  cal_view_id INT NOT NULL,
  cal_owner VARCHAR(25) DEFAULT NULL,
  cal_name VARCHAR(50) NOT NULL,
  cal_view_type CHAR(1) NOT NULL,
  cal_is_global CHAR(1) NOT NULL DEFAULT 'N',
  cal_last_update INT DEFAULT NULL,
  PRIMARY KEY (cal_view_id)
);

CREATE TABLE webcal_view_user (
  cal_view_id INT NOT NULL,
  cal_login VARCHAR(25) NOT NULL,
  PRIMARY KEY (cal_view_id, cal_login)
);

CREATE TABLE webcal_site_extras (
  cal_id INT NOT NULL,
  cal_name VARCHAR(25) NOT NULL,
  cal_type INT NOT NULL,
  cal_date INT DEFAULT NULL,
  cal_remind INT DEFAULT NULL,
  cal_data TEXT,
  PRIMARY KEY (cal_id, cal_name)
);

CREATE TABLE webcal_reminders (
  cal_id INT NOT NULL,
  cal_date INT NOT NULL,
  cal_offset INT DEFAULT 0,
  cal_related CHAR(1) DEFAULT 'S',
  cal_before CHAR(1) DEFAULT 'Y',
  cal_last_sent INT DEFAULT NULL,
  cal_times_sent INT DEFAULT 0,
  cal_action VARCHAR(25) DEFAULT 'EMAIL',
  cal_template TEXT DEFAULT NULL,
  PRIMARY KEY (cal_id, cal_date)
);

CREATE TABLE webcal_timezones (
  tzid VARCHAR(100) NOT NULL DEFAULT '',
  dtstart VARCHAR(25) DEFAULT NULL,
  dtend VARCHAR(25) DEFAULT NULL,
  vtimezone TEXT,
  PRIMARY KEY (tzid)
);

CREATE TABLE webcal_import (
  cal_import_id INT NOT NULL,
  cal_date INT NOT NULL,
  cal_login VARCHAR(25) DEFAULT NULL,
  cal_name VARCHAR(50) DEFAULT NULL,
  cal_type VARCHAR(10) NOT NULL,
  PRIMARY KEY (cal_import_id)
);

CREATE TABLE webcal_import_data (
  cal_id INT NOT NULL,
  cal_login VARCHAR(25) NOT NULL,
  cal_external_id VARCHAR(200) DEFAULT NULL,
  cal_import_id INT NOT NULL,
  cal_import_type VARCHAR(15) NOT NULL,
  PRIMARY KEY (cal_id, cal_login)
);

CREATE TABLE webcal_entry_categories (
  cal_id INT NOT NULL DEFAULT 0,
  cat_id INT NOT NULL DEFAULT 0,
  cat_order INT NOT NULL DEFAULT 0,
  cat_owner VARCHAR(25) DEFAULT NULL,
  PRIMARY KEY (cal_id, cat_id, cat_order, cat_owner)
);

-- Insert version marker for v1.3.0
INSERT INTO webcal_config (cal_setting, cal_value) VALUES ('WEBCAL_PROGRAM_VERSION', 'v1.3.0');

-- Insert a test admin user (password: admin123)
INSERT INTO webcal_user (cal_login, cal_passwd, cal_firstname, cal_lastname, cal_is_admin, cal_email, cal_enabled) 
VALUES ('admin', '202cb962ac59075b964b07152d234b70', 'Admin', 'User', 'Y', 'admin@example.com', 'Y');

-- Insert some test configuration
INSERT INTO webcal_config (cal_setting, cal_value) VALUES ('LANGUAGE', 'English');
INSERT INTO webcal_config (cal_setting, cal_value) VALUES ('STARTVIEW', 'month');
