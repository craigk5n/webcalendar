/*upgrade_v0.9.14*/
ALTER TABLE webcal_entry MODIFY cal_time INT NOT NULL DEFAULT -1;
UPDATE webcal_entry SET cal_time = -1 WHERE cal_time IS NULL;
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
UPDATE webcal_config SET cal_value = 'week.php' WHERE cal_setting = 'STARTVIEW';
UPDATE webcal_user_pref SET cal_value = 'day.php' WHERE cal_value = 'day' AND cal_setting = 'STARTVIEW';
UPDATE webcal_user_pref SET cal_value = 'month.php' WHERE cal_value = 'month' AND cal_setting = 'STARTVIEW';
UPDATE webcal_user_pref SET cal_value = 'week.php' WHERE cal_value = 'week' AND cal_setting = 'STARTVIEW';
UPDATE webcal_user_pref SET cal_value = 'year.php' WHERE cal_value = 'year' AND cal_setting = 'STARTVIEW';
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
ALTER TABLE webcal_entry ADD cal_completed int(11) DEFAULT NULL;
ALTER TABLE webcal_entry ADD cal_due_date int(11) DEFAULT NULL;
ALTER TABLE webcal_entry ADD cal_due_time int(11) DEFAULT NULL;
ALTER TABLE webcal_entry ADD cal_location varchar(100) DEFAULT NULL;
ALTER TABLE webcal_entry ADD cal_url varchar(100) DEFAULT NULL;
ALTER TABLE webcal_entry_repeats ADD cal_byday varchar(100) DEFAULT NULL;
ALTER TABLE webcal_entry_repeats ADD cal_bymonth varchar(50) DEFAULT NULL;
ALTER TABLE webcal_entry_repeats ADD cal_bymonthday varchar(100) DEFAULT NULL;
ALTER TABLE webcal_entry_repeats ADD cal_bysetpos varchar(50) DEFAULT NULL;
ALTER TABLE webcal_entry_repeats ADD cal_byweekno varchar(50) DEFAULT NULL;
ALTER TABLE webcal_entry_repeats ADD cal_byyearday varchar(50) DEFAULT NULL;
ALTER TABLE webcal_entry_repeats ADD cal_count int(11) DEFAULT NULL;
ALTER TABLE webcal_entry_repeats ADD cal_endtime int(11) DEFAULT NULL;
ALTER TABLE webcal_entry_repeats ADD cal_wkst char(2) DEFAULT 'MO';
ALTER TABLE webcal_entry_repeats_not ADD cal_exdate int(1) NOT NULL DEFAULT '1';
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
  cal_description VARCHAR(128) NULL,
  cal_id INT NULL,
  cal_login VARCHAR(25) NULL,
  cal_mime_type VARCHAR(50) NULL,
  cal_mod_date INT NOT NULL,
  cal_mod_time INT NOT NULL,
  cal_name VARCHAR(30) NULL,
  cal_size INT NULL,
  cal_type CHAR(1) NOT NULL,
  cal_blob LONGBLOB,
  PRIMARY KEY (cal_blob_id)
);
/*upgrade_v1.1.0d-CVS*/
DROP TABLE IF EXISTS webcal_access_user;
CREATE TABLE webcal_access_user (
  cal_login VARCHAR(25) NOT NULL,
  cal_other_user VARCHAR(25) NOT NULL,
  cal_can_approve INT NOT NULL DEFAULT '0',
  cal_can_edit INT NOT NULL DEFAULT '0',
  cal_can_email CHAR(1) DEFAULT 'Y',
  cal_can_invite CHAR(1) DEFAULT 'Y',
  cal_can_view INT NOT NULL DEFAULT '0',
  cal_see_time_only CHAR(1) DEFAULT 'N',
  PRIMARY KEY (cal_login, cal_other_user)
);
/*upgrade_v1.1.0e-CVS*/
CREATE TABLE webcal_reminders (
  cal_id INT NOT NULL DEFAULT '0',
  cal_action VARCHAR(12) NOT NULL DEFAULT 'EMAIL',
  cal_before CHAR(1) NOT NULL DEFAULT 'Y',
  cal_date INT NOT NULL DEFAULT '0',
  cal_duration INT NOT NULL DEFAULT '0',
  cal_last_sent INT NOT NULL DEFAULT '0',
  cal_offset INT NOT NULL DEFAULT '0',
  cal_related CHAR(1) NOT NULL DEFAULT 'S',
  cal_repeats INT NOT NULL DEFAULT '0',
  cal_times_sent INT NOT NULL DEFAULT '0',
  PRIMARY KEY (cal_id)
);
/*upgrade_v1.1.1*/
ALTER TABLE webcal_nonuser_cals ADD cal_url VARCHAR(255) DEFAULT NULL;
/*upgrade_v1.1.2*/
ALTER TABLE webcal_categories ADD cat_color VARCHAR(8) DEFAULT NULL;
ALTER TABLE webcal_user ADD cal_address VARCHAR(75) DEFAULT NULL;
ALTER TABLE webcal_user ADD cal_birthday INT NULL;
ALTER TABLE webcal_user ADD cal_enabled CHAR(1) DEFAULT 'Y';
ALTER TABLE webcal_user ADD cal_last_login INT NULL;
ALTER TABLE webcal_user ADD cal_telephone VARCHAR(50) DEFAULT NULL;
ALTER TABLE webcal_user ADD cal_title VARCHAR(75) DEFAULT NULL;
/*upgrade_v1.1.3*/
CREATE TABLE webcal_timezones (
  tzid varchar(100) NOT NULL default '',
  dtstart varchar(25) default NULL,
  dtend varchar(25) default NULL,
  vtimezone text,
  PRIMARY KEY (tzid)
);
/*upgrade_v1.2.8*/
ALTER TABLE webcal_access_function ENGINE MyISAM CHARACTER SET utf8 COMMENT '<a name="webcal_access_function">''</a>Specifies what WebCalendar functions a user can access. Each function has a corresponding numeric value (specified in the file includes/access.php). For example, view event is 0, so the very first character in the cal_permissions column is either a "Y" if this user can view events or a "N" if they cannot.';
ALTER TABLE webcal_access_function MODIFY cal_login varchar(25) NOT NULL COMMENT 'User login. From <a href="#webcal_user">webcal_user</a> table.' FIRST;
ALTER TABLE webcal_access_function MODIFY cal_permissions varchar(64) NOT NULL COMMENT 'A string of "Y"s and/or "N"s for the various functions.';

ALTER TABLE webcal_access_user ENGINE MyISAM CHARACTER SET utf8 COMMENT '<a name="webcal_access_user">''</a>Specifies which users can access another user''s calendar.';
ALTER TABLE webcal_access_user MODIFY cal_see_time_only ENUM('N','Y') NOT NULL DEFAULT 'N' COMMENT 'Can current user can only see time blocks of other user?';
ALTER TABLE webcal_access_user MODIFY cal_can_view smallint UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Can current user view events on the other user''s calendar?' FIRST;
ALTER TABLE webcal_access_user MODIFY cal_can_invite ENUM('N','Y') NOT NULL DEFAULT 'Y' COMMENT 'Can current user see other user in Participant lists?' FIRST;
ALTER TABLE webcal_access_user MODIFY cal_can_email ENUM('N','Y') DEFAULT 'Y' COMMENT 'Can current user send emails to other users?' FIRST;
ALTER TABLE webcal_access_user MODIFY cal_can_edit smallint UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Can current user edit events on the other user''s calendar?' FIRST;
ALTER TABLE webcal_access_user MODIFY cal_can_approve smallint UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Can current user approve events on the other user''s calendar?' FIRST;
ALTER TABLE webcal_access_user MODIFY cal_other_user varchar(25) NOT NULL COMMENT 'The login of the other user whose calendar the current user wants to access. Also, from <a href="#webcal_user">webcal_user</a> table.' FIRST;
ALTER TABLE webcal_access_user MODIFY cal_login varchar(25) NOT NULL COMMENT 'The current user who is attempting to look at another user''s calendar. From <a href="#webcal_user">webcal_user</a> table.' FIRST;

ALTER TABLE webcal_asst ENGINE MyISAM CHARACTER SET utf8 COMMENT '<a name="webcal_asst">''</a>Define assistant/boss relationship.';
ALTER TABLE webcal_asst MODIFY cal_assistant varchar(25) NOT NULL COMMENT 'Assistant login. Also from <a href="#webcal_user">webcal_user</a> table.';
ALTER TABLE webcal_asst MODIFY cal_boss varchar(25) NOT NULL COMMENT 'Boss login. From <a href="#webcal_user">webcal_user</a> table.' FIRST;

ALTER TABLE webcal_blob ENGINE MyISAM CHARACTER SET utf8 COMMENT '<a name="webcal_blob">''</a>This table stores event attachments and comments.';
ALTER TABLE webcal_blob MODIFY cal_type ENUM('A','C') NOT NULL DEFAULT 'C' COMMENT 'Type of object: C=Comment, A=Attachment.' FIRST;
ALTER TABLE webcal_blob MODIFY cal_size int UNSIGNED DEFAULT NULL COMMENT 'Size of object (not used for comments).' FIRST;
ALTER TABLE webcal_blob MODIFY cal_name varchar(30) DEFAULT NULL COMMENT 'Filename of object (not used for comments).' FIRST;
ALTER TABLE webcal_blob MODIFY cal_mod_time time NOT NULL COMMENT 'Time added.' FIRST;
ALTER TABLE webcal_blob MODIFY cal_mod_date date NOT NULL COMMENT 'Date added.' FIRST;
ALTER TABLE webcal_blob ADD cal_mod TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When was this added / changed?' FIRST;
ALTER TABLE webcal_blob MODIFY cal_mime_type varchar(50) DEFAULT NULL COMMENT 'MIME type of object (as specified by browser during upload (not used for comment).' FIRST;
ALTER TABLE webcal_blob MODIFY cal_login varchar(25) DEFAULT NULL COMMENT 'Login of user who created. From <a href="#webcal_user">webcal_user</a> table.' FIRST;
ALTER TABLE webcal_blob MODIFY cal_id int UNSIGNED DEFAULT NULL COMMENT 'Event id (if applicable). From <a href="#webcal_entry">webcal_entry</a> table.' FIRST;
ALTER TABLE webcal_blob MODIFY cal_description varchar(128) DEFAULT NULL COMMENT 'Description of what the object is (subject for comment).' FIRST;
ALTER TABLE webcal_blob MODIFY cal_blob_id int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Unique identifier for this object.' FIRST;
ALTER TABLE webcal_blob MODIFY cal_blob longblob COMMENT 'Binary data for object.';
ALTER TABLE webcal_blob ADD UNIQUE KEY wb_cl_cd (cal_login,cal_description);

ALTER TABLE webcal_categories ENGINE MyISAM CHARACTER SET utf8 COMMENT '<a name="webcal_categories">''</a>Defines user categories. Categories can be specific to a user or global. When a category is global, the cat_owner field will be NULL. (Only an admin user can create a global category.)';
ALTER TABLE webcal_categories MODIFY cat_name varchar(80) NOT NULL COMMENT 'Category name.' FIRST;
ALTER TABLE webcal_categories MODIFY cat_color varchar(7) DEFAULT NULL COMMENT 'RGB color for category.' FIRST;
ALTER TABLE webcal_categories MODIFY cat_id int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Unique category id.' FIRST;
ALTER TABLE webcal_categories MODIFY cat_owner varchar(25) DEFAULT NULL COMMENT 'User login of category owner. From <a href="#webcal_user">webcal_user</a> table, if applicable. If this is NULL, then it is a global category.';

ALTER TABLE webcal_config ENGINE MyISAM CHARACTER SET utf8 COMMENT '<a name="webcal_config">''</a>System settings (set by the admin interface in admin.php).';
ALTER TABLE webcal_config MODIFY cal_setting varchar(50) NOT NULL COMMENT 'System setting.' FIRST;
ALTER TABLE webcal_config MODIFY cal_value varchar(100) NULL COMMENT 'System setting value.';

ALTER TABLE webcal_entry ENGINE MyISAM CHARACTER SET utf8 COMMENT '<a name="webcal_entry">''</a>Defines a calendar event. Each event in the system has one entry in this table unless the event starts before midnight and ends after midnight. In that case a secondary event will be created with cal_ext_for_id set to the cal_id of the original entry. The following tables contain additional information about each event:<ul><li><a href="#webcal_entry_user">webcal_entry_user</a> table - lists participants in the event and specifies the status (accepted, rejected) and category of each participant.</li><li><a href="#webcal_entry_repeats">webcal_entry_repeats</a> table - contains information if the event repeats.</li><li><a href="#webcal_entry_repeats_not">webcal_entry_repeats_not</a> table - specifies which dates the repeating event does not repeat (because they were deleted or modified for just that date by the user.)</li><li><a href="#webcal_entry_log">webcal_entry_log</a> table - provides a history of changes to this event.</li><li><a href="#webcal_site_extras">webcal_site_extras</a> table - stores event data as defined in site_extras.php (such as reminders and other custom event fields).</li></ul>';
ALTER TABLE webcal_entry MODIFY cal_url varchar(100) DEFAULT NULL COMMENT 'URL of event.';
ALTER TABLE webcal_entry MODIFY cal_type ENUM('E','M','T') DEFAULT 'E' COMMENT '"E" = Event, "M" = Repeating event, "T" = Task.' FIRST;
ALTER TABLE webcal_entry MODIFY cal_time time DEFAULT NULL COMMENT 'Event start time.' FIRST;
ALTER TABLE webcal_entry MODIFY cal_priority tinyint UNSIGNED DEFAULT '5' COMMENT 'Event priority: 1=High, 9=Low.' FIRST;
ALTER TABLE webcal_entry MODIFY cal_name varchar(80) NOT NULL COMMENT 'Brief description of event.' FIRST;
ALTER TABLE webcal_entry MODIFY cal_mod_time time DEFAULT NULL COMMENT 'Time the event was last modified.' FIRST;
ALTER TABLE webcal_entry MODIFY cal_mod_date date DEFAULT NULL COMMENT 'Date the event was last modified.' FIRST;
ALTER TABLE webcal_entry ADD cal_mod TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When was this added / changed?' FIRST;
ALTER TABLE webcal_entry MODIFY cal_location varchar(100) DEFAULT NULL COMMENT 'Location of event.' FIRST;
ALTER TABLE webcal_entry MODIFY cal_group_id int UNSIGNED DEFAULT NULL COMMENT 'The parent event id if this event is overriding an occurrence of a repeating event. From this table.' FIRST;
ALTER TABLE webcal_entry MODIFY cal_ext_for_id int UNSIGNED DEFAULT NULL COMMENT 'Used when an event goes past midnight into the next day, in which case an additional entry in this table will use this field to indicate the original event cal_id.' FIRST;
ALTER TABLE webcal_entry MODIFY cal_duration int UNSIGNED NOT NULL COMMENT 'Duration of event in minutes.' FIRST;
ALTER TABLE webcal_entry MODIFY cal_due_time time DEFAULT NULL COMMENT 'Task due time.' FIRST;
ALTER TABLE webcal_entry MODIFY cal_due_date date DEFAULT NULL COMMENT 'Task due date.' FIRST;
ALTER TABLE webcal_entry ADD cal_due datetime DEFAULT NULL COMMENT 'When is this task due?' FIRST;
ALTER TABLE webcal_entry MODIFY cal_description text COMMENT 'Full description of event.';
ALTER TABLE webcal_entry ADD cal_datetime datetime NOT NULL COMMENT 'When is this event scheduled to start?' FIRST;
ALTER TABLE webcal_entry MODIFY cal_date date NOT NULL COMMENT 'Date of event.' FIRST;
ALTER TABLE webcal_entry MODIFY cal_create_by varchar(25) NOT NULL COMMENT 'Login of user that created the event. From <a href="#webcal_user">webcal_user</a> table.' FIRST;
ALTER TABLE webcal_entry MODIFY cal_completed date DEFAULT NULL COMMENT 'Date task completed.' FIRST;
ALTER TABLE webcal_entry MODIFY cal_access ENUM('C','P','R') DEFAULT 'P' COMMENT '"P" = Public, "R" = Private (others cannot see the event), "C" = Confidential (others can see time allocated but not what it is).' FIRST;
ALTER TABLE webcal_entry MODIFY cal_id int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Unique integer id for event.' FIRST;
UPDATE webcal_entry SET cal_datetime = CONCAT(cal_date,cal_time);
UPDATE webcal_entry SET cal_due = CONCAT(cal_due_date,cal_due_time);

ALTER TABLE webcal_entry_categories ENGINE MyISAM CHARACTER SET utf8 COMMENT '<a name="webcal_entry_categories">''</a>Contains category foreign keys to enable multiple categories for each event or task.';
ALTER TABLE webcal_entry_categories MODIFY cat_owner varchar(25) DEFAULT NULL COMMENT 'User that owns this record. Global categories will be NULL. From <a href="#webcal_user">webcal_user</a> table.' FIRST;
ALTER TABLE webcal_entry_categories MODIFY cat_order int UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Order that user requests their categories appear. Globals are always last.';
ALTER TABLE webcal_entry_categories MODIFY cat_id int UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Id of category from <a href="#webcal_categories">webcal_categories</a> table.' FIRST;
ALTER TABLE webcal_entry_categories MODIFY cal_id int UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Id of event from <a href="#webcal_entry">webcal_entry</a> table.' FIRST;

ALTER TABLE webcal_entry_ext_user ENGINE MyISAM CHARACTER SET utf8 COMMENT '<a name="webcal_entry_ext_user">''</a>External user (no login) for an event.';
ALTER TABLE webcal_entry_ext_user MODIFY cal_fullname varchar(50) NOT NULL COMMENT 'external user full name';
ALTER TABLE webcal_entry_ext_user MODIFY cal_email varchar(75) NULL COMMENT 'external user email (for sending a reminder)' FIRST;
ALTER TABLE webcal_entry_ext_user MODIFY cal_id int UNSIGNED NOT NULL DEFAULT '0' COMMENT 'From <a href="#webcal_entry">webcal_entry</a> table.' FIRST;

ALTER TABLE webcal_entry_log ENGINE MyISAM CHARACTER SET utf8 COMMENT '<a name="webcal_entry_log">''</a>Activity log for an event.';
ALTER TABLE webcal_entry_log MODIFY cal_user_cal varchar(25) DEFAULT NULL COMMENT 'User of calendar affected. Also from <a href="#webcal_user">webcal_user</a> table.' FIRST;
ALTER TABLE webcal_entry_log MODIFY cal_type ENUM('A','C','E','M','R','U') NOT NULL DEFAULT 'C' COMMENT 'Log types:<ul><li>C: Created</li><li>A: Approved/Confirmed by user</li><li>R: Rejected by user</li><li>U: Updated by user</li><li>M: Mail Notification sent</li><li>E: Reminder sent</li></ul>' FIRST;
ALTER TABLE webcal_entry_log MODIFY cal_time time DEFAULT NULL FIRST;
ALTER TABLE webcal_entry_log MODIFY cal_text text COMMENT 'Optional text.';
ALTER TABLE webcal_entry_log ADD cal_mod TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When was this added / changed?' FIRST;
ALTER TABLE webcal_entry_log MODIFY cal_login varchar(25) NOT NULL COMMENT 'User who performed this action. From <a href="#webcal_user">webcal_user</a> table.' FIRST;
ALTER TABLE webcal_entry_log MODIFY cal_entry_id int UNSIGNED NOT NULL COMMENT 'Event id. From <a href="#webcal_entry">webcal_entry</a> table.' FIRST;
ALTER TABLE webcal_entry_log MODIFY cal_date date NOT NULL FIRST;
ALTER TABLE webcal_entry_log MODIFY cal_log_id int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Unique id of this log entry.' FIRST;

ALTER TABLE webcal_entry_repeats ENGINE MyISAM CHARACTER SET utf8 COMMENT '<a name="webcal_entry_repeats">''</a>Defines repeating info about an event. The event is defined in <a href="#webcal_entry">webcal_entry</a> table.';
ALTER TABLE webcal_entry_repeats MODIFY cal_wkst ENUM('MO','TU','WE','TH','FR','SA','SU') DEFAULT 'MO' COMMENT 'Week starts on...';
ALTER TABLE webcal_entry_repeats MODIFY cal_days ENUM('N','Y') DEFAULT NULL COMMENT 'NO LONGER USED. We''ll leave it in for now.' FIRST;
ALTER TABLE webcal_entry_repeats MODIFY cal_count int UNSIGNED DEFAULT NULL FIRST;
ALTER TABLE webcal_entry_repeats MODIFY cal_byyearday varchar(50) DEFAULT NULL FIRST;
ALTER TABLE webcal_entry_repeats MODIFY cal_byweekno varchar(50) DEFAULT NULL FIRST;
ALTER TABLE webcal_entry_repeats MODIFY cal_bysetpos varchar(50) DEFAULT NULL FIRST;
ALTER TABLE webcal_entry_repeats MODIFY cal_bymonthday varchar(100) DEFAULT NULL FIRST;
ALTER TABLE webcal_entry_repeats MODIFY cal_bymonth varchar(50) DEFAULT NULL FIRST;
ALTER TABLE webcal_entry_repeats MODIFY cal_byday varchar(100) DEFAULT NULL COMMENT 'The following columns are values as specified in RFC2445.' FIRST;
ALTER TABLE webcal_entry_repeats MODIFY cal_type varchar(20) DEFAULT NULL COMMENT 'Type of repeating:<ul><li>daily - repeats daily</li><li>monthlyByDate - repeats on same day of the month</li><li>monthlyBySetPos - repeats based on position within other ByXXX values</li><li>monthlyByDay - repeats on specified weekday, (2nd Monday, for example)</li><li>weekly - repeats every week</li><li>yearly - repeats on same date every year</li></ul>' FIRST;
ALTER TABLE webcal_entry_repeats MODIFY cal_frequency int UNSIGNED DEFAULT '1' COMMENT 'Frequency of repeat: 1 = every, 2 = every other, 3 = every 3rd, etc.' FIRST;
ALTER TABLE webcal_entry_repeats MODIFY cal_endtime time DEFAULT NULL COMMENT 'End time for repeating event.' FIRST;
ALTER TABLE webcal_entry_repeats ADD cal_enddt datetime DEFAULT NULL COMMENT 'End date and time for repeating event.' FIRST;
ALTER TABLE webcal_entry_repeats MODIFY cal_end date DEFAULT NULL COMMENT 'End date for repeating event.' FIRST;
ALTER TABLE webcal_entry_repeats MODIFY cal_id int UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Event id. From <a href="#webcal_entry">webcal_entry</a> table.' FIRST;
UPDATE webcal_entry_repeats SET cal_enddt = CONCAT(cal_end,cal_endtime);

ALTER TABLE webcal_entry_repeats_not ENGINE MyISAM CHARACTER SET utf8 COMMENT '<a name="webcal_entry_repeats_not">''</a>This table specifies which dates in a repeating event have either been deleted, included, or replaced with another event for that day. When replaced, the cal_group_id (I know... not the best name, but it was not being used) column will be set to the original event. That way the user can delete the original event and (at the same time) delete any exception events.';
ALTER TABLE webcal_entry_repeats_not MODIFY cal_date DATE NOT NULL COMMENT 'Date event should not repeat.' FIRST;
ALTER TABLE webcal_entry_repeats_not MODIFY cal_id int UNSIGNED NOT NULL COMMENT 'Event id of repeating event. From <a href="#webcal_entry">webcal_entry</a> table.' FIRST;
ALTER TABLE webcal_entry_repeats_not MODIFY cal_exdate tinyint UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Indicates whether this record is an exclusion1) or inclusion0).';

ALTER TABLE webcal_entry_user ENGINE MyISAM CHARACTER SET utf8 COMMENT '<a name="webcal_entry_user">''</a>This table associates one or more users with an event by the event id. The event can be found in <a href="#webcal_entry">webcal_entry</a> table.';
ALTER TABLE webcal_entry_user MODIFY cal_status ENUM('A','C','D','P','R','W') DEFAULT 'A' COMMENT 'Status of event for this user:<ul><li>A=Accepted</li><li>C=Completed</li><li>D=Deleted</li><li>P=In-Progress</li><li>R=Rejected/Declined</li><li>W=Waiting</li></ul>';
ALTER TABLE webcal_entry_user MODIFY cal_percent tinyint UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Task percentage of completion for this user''s task.' FIRST;
ALTER TABLE webcal_entry_user MODIFY cal_category int UNSIGNED DEFAULT NULL COMMENT 'Category of the event for this user.' FIRST;
ALTER TABLE webcal_entry_user MODIFY cal_login varchar(25) NOT NULL COMMENT 'Participant in the event. From <a href="#webcal_user">webcal_user</a> table.' FIRST;
ALTER TABLE webcal_entry_user MODIFY cal_id int UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Event id. From <a href="#webcal_entry">webcal_entry</a> table.' FIRST;

ALTER TABLE webcal_group ENGINE MyISAM CHARACTER SET utf8 COMMENT '<a name="webcal_group">''</a>Define a group. Group members can be found in <a href="#webcal_group_user">webcal_group_user</a> table.';
ALTER TABLE webcal_group MODIFY cal_owner varchar(25) DEFAULT NULL COMMENT 'User login of user that created this group. From <a href="#webcal_user">webcal_user</a> table.';
ALTER TABLE webcal_group MODIFY cal_name varchar(50) NOT NULL COMMENT 'Name of the group.' FIRST;
ALTER TABLE webcal_group MODIFY cal_last_update TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Date last updated.' FIRST;
ALTER TABLE webcal_group MODIFY cal_group_id int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Unique group id.' FIRST;

ALTER TABLE webcal_group_user ENGINE MyISAM CHARACTER SET utf8 COMMENT '<a name="webcal_group_user">''</a>Group members.';
ALTER TABLE webcal_group_user MODIFY cal_login varchar(25) NOT NULL COMMENT 'From <a href="#webcal_user">webcal_user</a>';
ALTER TABLE webcal_group_user MODIFY cal_group_id int UNSIGNED NOT NULL COMMENT 'From <a href="#webcal_group">webcal_group</a>' FIRST;

ALTER TABLE webcal_import ENGINE MyISAM CHARACTER SET utf8 COMMENT '<a name="webcal_import">''</a>Used to track import data (one row per import).';
ALTER TABLE webcal_import MODIFY cal_type varchar(10) NOT NULL COMMENT 'Type of import (ical, vcal, palm, outlookcsv).';
ALTER TABLE webcal_import MODIFY cal_name varchar(50) DEFAULT NULL COMMENT 'Name of import (optional).' FIRST;
ALTER TABLE webcal_import MODIFY cal_login varchar(25) DEFAULT NULL COMMENT 'User who performed the import.' FIRST;
ALTER TABLE webcal_import MODIFY cal_date TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Datetime of import.' FIRST;
ALTER TABLE webcal_import MODIFY cal_import_id int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Unique id for import.' FIRST;

ALTER TABLE webcal_import_data ENGINE MyISAM CHARACTER SET utf8 COMMENT '<a name="webcal_import_data">''</a>Used to track import data (one row per event).';
ALTER TABLE webcal_import_data MODIFY cal_import_type varchar(15) NOT NULL COMMENT 'Type of import: "palm", "vcal", "ical" or "outlookcsv".';
ALTER TABLE webcal_import_data MODIFY cal_import_id int UNSIGNED NOT NULL COMMENT 'Import id (From <a href="#webcal_import">webcal_import</a> table.' FIRST;
ALTER TABLE webcal_import_data MODIFY cal_external_id varchar(200) DEFAULT NULL COMMENT 'External id used in external calendar system (for example, UID in iCal).' FIRST;
ALTER TABLE webcal_import_data MODIFY cal_login varchar(25) NOT NULL COMMENT 'User login. From <a href="#webcal_user">webcal_user</a> table.' FIRST;
ALTER TABLE webcal_import_data MODIFY cal_id int UNSIGNED NOT NULL COMMENT 'Event id in WebCalendar. From <a href="#webcal_entry">webcal_entry</a> table.' FIRST;

ALTER TABLE webcal_nonuser_cals ENGINE MyISAM CHARACTER SET utf8 COMMENT '<a name="webcal_nonuser_cals">''</a>Defines non-user calendars.';
ALTER TABLE webcal_nonuser_cals MODIFY cal_url varchar(255) DEFAULT NULL COMMENT 'URL of the remote calendar.';
ALTER TABLE webcal_nonuser_cals MODIFY cal_lastname varchar(25) DEFAULT NULL COMMENT 'Calendar''s last name.' FIRST;
ALTER TABLE webcal_nonuser_cals MODIFY cal_is_public ENUM('N','Y') NOT NULL DEFAULT 'N' COMMENT 'Can this nonuser calendar be a public calendar (no login required)?' FIRST;
ALTER TABLE webcal_nonuser_cals MODIFY cal_firstname varchar(25) DEFAULT NULL COMMENT 'Calendar'' first name.' FIRST;
ALTER TABLE webcal_nonuser_cals ADD cal_displayname varchar(50) DEFAULT NULL COMMENT 'Name to diplay on public or other user''s calendars.' FIRST;
ALTER TABLE webcal_nonuser_cals MODIFY cal_admin varchar(25) NOT NULL COMMENT 'The calendar administrator. From <a href="#webcal_user">webcal_user</a> table.' FIRST;
ALTER TABLE webcal_nonuser_cals MODIFY cal_login varchar(25) NOT NULL COMMENT 'Unique id for the calendar.' FIRST;
UPDATE webcal_nonuser_cals SET cal_displayname = CONCAT_WS(' ',cal_firstname,cal_lastname);

ALTER TABLE webcal_reminders ENGINE MyISAM CHARACTER SET utf8 COMMENT '<a name="webcal_reminders">''</a>Stores information about reminders.';
ALTER TABLE webcal_reminders MODIFY cal_times_sent int UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Number of times this reminder has been sent.';
ALTER TABLE webcal_reminders MODIFY cal_repeats int UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Number of times to repeat in addition to original occurrence.' FIRST;
ALTER TABLE webcal_reminders MODIFY cal_related ENUM('E','S') NOT NULL DEFAULT 'S' COMMENT 'S=Start, E=End. Specifies which edge of entry this reminder applies to.' FIRST;
ALTER TABLE webcal_reminders MODIFY cal_offset int UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Offset in minutes from the selected edge.' FIRST;
ALTER TABLE webcal_reminders MODIFY cal_last_sent datetime NOT NULL COMMENT 'Timestamp of last sent reminder.' FIRST;
ALTER TABLE webcal_reminders MODIFY cal_duration int UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Time in ISO 8601 format that specifies time between repeated reminders.' FIRST;
ALTER TABLE webcal_reminders MODIFY cal_date datetime NOT NULL COMMENT 'When to send? Use this or cal_offset, but not both.' FIRST;
ALTER TABLE webcal_reminders MODIFY cal_before ENUM('N','Y') NOT NULL DEFAULT 'Y' COMMENT 'Specifies whether reminder is sent before or after selected edge.' FIRST;
ALTER TABLE webcal_reminders MODIFY cal_action varchar(12) NOT NULL DEFAULT 'EMAIL' COMMENT 'Action as imported, may be used in the future.' FIRST;
ALTER TABLE webcal_reminders MODIFY cal_id int UNSIGNED NOT NULL DEFAULT '0' COMMENT 'From <a href="#webcal_entry">webcal_entry</a>' FIRST;

ALTER TABLE webcal_report ENGINE MyISAM CHARACTER SET utf8 COMMENT '<a name="webcal_report">''</a>Defines a custom report created by a user.';
ALTER TABLE webcal_report MODIFY cal_user varchar(25) DEFAULT NULL COMMENT 'User calendar to display (NULL indicates current user).';
ALTER TABLE webcal_report MODIFY cal_update_date date NOT NULL COMMENT 'Date created or last updated.' FIRST;
ALTER TABLE webcal_report MODIFY cal_time_range int UNSIGNED NOT NULL COMMENT 'Time range for report:<ul><li>0 = tomorrow</li><li>1 = today</li><li>2 = yesterday</li><li>3 = day before yesterday</li><li>10 = next week</li><li>11 = current week</li><li>12 = last week</li><li>13 = week before last</li><li>20 = next week and week after</li><li>21 = current week and next week</li><li>22 = last week and this week</li><li>23 = last two weeks</li><li>30 = next month</li><li>31 = current month</li><li>32 = last month</li><li>33 = month before last</li><li>40 = next year</li><li>41 = current year</li><li>42 = last year</li><li>43 = year before last</li></ul>' FIRST;
ALTER TABLE webcal_report MODIFY cal_show_in_trailer ENUM('N','Y') DEFAULT 'N' COMMENT 'Include a link for this report in the "Go to" section of the navigation in the page trailer? ("Y" or "N")' FIRST;
ALTER TABLE webcal_report MODIFY cal_report_type varchar(20) NOT NULL COMMENT 'Format of report (html, plain or csv).' FIRST;
ALTER TABLE webcal_report MODIFY cal_report_name varchar(50) NOT NULL COMMENT 'Name of the report.' FIRST;
ALTER TABLE webcal_report ADD cal_mod TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Created or last updated.' FIRST;
ALTER TABLE webcal_report MODIFY cal_login varchar(25) NOT NULL COMMENT 'Creator of report.' FIRST;
ALTER TABLE webcal_report MODIFY cal_is_global ENUM('N','Y') NOT NULL DEFAULT 'N' COMMENT 'Is this a global report (can it be accessed by other users - "Y" or "N")' FIRST;
ALTER TABLE webcal_report MODIFY cal_include_header ENUM('N','Y') NOT NULL DEFAULT 'Y' COMMENT 'If cal_report_type is "html", should the default HTML header and trailer be included? ("Y" or "N")' FIRST;
ALTER TABLE webcal_report MODIFY cal_include_empty ENUM('N','Y') DEFAULT 'N' COMMENT 'Include empty dates in report ("Y" or "N").' FIRST;
ALTER TABLE webcal_report MODIFY cal_cat_id int UNSIGNED DEFAULT NULL COMMENT 'Category to filter on (optional).' FIRST;
ALTER TABLE webcal_report MODIFY cal_allow_nav ENUM('N','Y') DEFAULT 'Y' COMMENT 'Allow user to navigate to different dates with next/previous? ("Y" or "N")' FIRST;
ALTER TABLE webcal_report MODIFY cal_report_id int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Unique id of this report.' FIRST;

ALTER TABLE webcal_report_template ENGINE MyISAM CHARACTER SET utf8 COMMENT '<a name="webcal_report_template">''</a>Defines one of the templates used for a report. Each report has three templates: <ol><li>Page template - Defines the entire page (except for header and footer). The following variables can be defined:<ul><li>${days}<sup>*</sup> - the HTML of all dates (generated from the Date template)</li></ul></li><li>Date template - Defines events for one day. If the report is for a week or month, then the results of each day will be concatenated and used as the ${days} variable in the Page template. The following variables can be defined:<ul><li>${events}<sup>*</sup> - the HTML of all events for the data (generated from the Event template)</li><li>${date} - the date</li><li>${fulldate} - date (includes weekday)</li></ul></li><li>Event template - Defines a single event. The following variables can be defined: <ul><li>${name}<sup>*</sup> - Brief Description of event</li><li>${description} - Full Description of event</li><li>${date} - Date of event</li><li>${fulldate} - Date of event (includes weekday)</li><li>${time} - Time of event (4:00pm - 4:30pm)</li><li>${starttime} - Start time of event</li><li>${endtime} - End time of event</li><li>${duration} - Duration of event (in minutes)</li><li>${priority} - Priority of event</li><li>${href} - URL to view event details</li></ul></li></ol><sup>*</sup> denotes a required template variable.';
ALTER TABLE webcal_report_template MODIFY cal_template_type ENUM('D','E','P') NOT NULL DEFAULT "P" COMMENT 'Type of template:<ul><li>"P": page template represents entire document</li><li>"D": date template represents a single day of events</li><li>"E": event template represents a single event</li></ul>' FIRST;
ALTER TABLE webcal_report_template MODIFY cal_template_text text COMMENT 'Text of template.';
ALTER TABLE webcal_report_template MODIFY cal_report_id int UNSIGNED NOT NULL COMMENT 'Report id from <a href="#webcal_report table">webcal_report</a> table.' FIRST;

ALTER TABLE webcal_site_extras ENGINE MyISAM CHARACTER SET utf8 COMMENT '<a name="webcal_site_extras">''</a>This table holds data for site extra fields (customized in site_extra.php).';
ALTER TABLE webcal_site_extras MODIFY cal_type int UNSIGNED NOT NULL COMMENT 'EXTRA_URL, EXTRA_DATE, etc.' FIRST;
ALTER TABLE webcal_site_extras MODIFY cal_remind int UNSIGNED DEFAULT '0' COMMENT 'How many minutes before event should a reminder be sent.' FIRST;
ALTER TABLE webcal_site_extras MODIFY cal_name varchar(25) NOT NULL COMMENT 'The brief name of this type (first field in $site_extra array).' FIRST;
ALTER TABLE webcal_site_extras MODIFY cal_id int UNSIGNED NOT NULL DEFAULT '0' COMMENT 'Event id from <a href="#webcal_entry">webcal_entry</a> table.' FIRST;
ALTER TABLE webcal_site_extras MODIFY cal_date DATE NULL DEFAULT NULL COMMENT 'Only used for EXTRA_DATE type fields.' FIRST;
ALTER TABLE webcal_site_extras MODIFY cal_data text COMMENT 'Used to store text data.';

ALTER TABLE webcal_timezones ENGINE MyISAM CHARACTER SET utf8 COMMENT '<a name="webcal_timezones">''</a>This table stores timezones of the world.';
ALTER TABLE webcal_timezones MODIFY vtimezone text COMMENT 'Complete VTIMEZONE text gleaned from imported ics files.';
ALTER TABLE webcal_timezones MODIFY tzid varchar(100) NOT NULL DEFAULT '' COMMENT 'Unique name of timezone, try to use Olsen naming conventions.' FIRST;
ALTER TABLE webcal_timezones MODIFY dtstart varchar(16) DEFAULT NULL COMMENT 'Earliest date this timezone represents in YYYYMMDDTHHMMSSZ format.' FIRST;
ALTER TABLE webcal_timezones MODIFY dtend varchar(16) DEFAULT NULL COMMENT 'Last date this timezone represents in YYYYMMDDTHHMMSSZ format.' FIRST;

DROP TABLE IF EXISTS webcal_translations;
CREATE TABLE IF NOT EXISTS webcal_translations (
 phrase varchar(300) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL COMMENT 'The translate / tooltip (phrases) from the code. And the __phrase__ from version 2.0.0. ("latin1" is currently the only choice that is case sensitive.)',
 on_page varchar(50) NOT NULL COMMENT 'which code page (sorted alphabetically) has the first occurance of the above phrase.',
 English_US varchar(300) NOT NULL COMMENT 'The full English text.',
 Afrikaans varchar(300) NOT NULL,
 Albanian varchar(300) NOT NULL,
 Arabic varchar(300) NOT NULL,
 Armenian varchar(300) NOT NULL,
 Azerbaijan varchar(300) NOT NULL,
 Basque varchar(300) NOT NULL,
 Belarusian varchar(300) NOT NULL,
 Bulgarian varchar(300) NOT NULL,
 Catalan varchar(300) NOT NULL,
 Chamorro varchar(300) NOT NULL,
 Chinese_Big5 varchar(300) NOT NULL,
 Chinese_GB2312 varchar(300) NOT NULL,
 Croatian varchar(300) CHARACTER SET utf8 COLLATE utf8_croatian_ci NOT NULL,
 Czech varchar(300) CHARACTER SET utf8 COLLATE utf8_czech_ci NOT NULL,
 Danish varchar(300) CHARACTER SET utf8 COLLATE utf8_danish_ci NOT NULL,
 Dutch varchar(300) NOT NULL,
 Elven varchar(300) NOT NULL COMMENT 'From JRR Tolkien "Hobbit" and "Lord of the Rings".',
 Esperanto varchar(300) CHARACTER SET utf8 COLLATE utf8_esperanto_ci NOT NULL,
 Estonian varchar(300) CHARACTER SET utf8 COLLATE utf8_estonian_ci NOT NULL,
 Faroese varchar(300) NOT NULL,
 Farsi varchar(300) NOT NULL,
 Finnish varchar(300) NOT NULL,
 French varchar(300) NOT NULL,
 Galician varchar(300) NOT NULL,
 Georgian varchar(300) NOT NULL,
 German varchar(300) CHARACTER SET utf8 COLLATE utf8_german2_ci NOT NULL,
 Greek varchar(300) NOT NULL,
 Hebrew varchar(300) NOT NULL,
 Hungarian varchar(300) CHARACTER SET utf8 COLLATE utf8_hungarian_ci NOT NULL,
 Icelandic varchar(300) CHARACTER SET utf8 COLLATE utf8_icelandic_ci NOT NULL,
 Indonesian varchar(300) NOT NULL,
 Italian varchar(300) NOT NULL,
 Japanese varchar(300) NOT NULL,
 Klingon varchar(300) NOT NULL,
 Korean varchar(300) NOT NULL,
 Latvian varchar(300) CHARACTER SET utf8 COLLATE utf8_latvian_ci NOT NULL,
 Lithuanian varchar(300) CHARACTER SET utf8 COLLATE utf8_lithuanian_ci NOT NULL,
 Malaysian varchar(300) NOT NULL,
 Myanmar varchar(300) CHARACTER SET utf8 COLLATE utf8_myanmar_ci NOT NULL,
 Norwegian varchar(300) NOT NULL,
 Persian varchar(300) CHARACTER SET utf8 COLLATE utf8_persian_ci NOT NULL,
 Polish varchar(300) CHARACTER SET utf8 COLLATE utf8_polish_ci NOT NULL,
 Portuguese varchar(300) NOT NULL,
 Portuguese_BR varchar(300) NOT NULL,
 Romanian varchar(300) CHARACTER SET utf8 COLLATE utf8_romanian_ci NOT NULL,
 Russian varchar(300) NOT NULL,
 Serbian varchar(300) NOT NULL,
 Sinhala varchar(300) CHARACTER SET utf8 COLLATE utf8_sinhala_ci NOT NULL,
 Slovakian varchar(300) CHARACTER SET utf8 COLLATE utf8_slovak_ci NOT NULL,
 Slovenian varchar(300) CHARACTER SET utf8 COLLATE utf8_slovenian_ci NOT NULL,
 Spanish varchar(300) CHARACTER SET utf8 COLLATE utf8_spanish_ci NOT NULL,
 Swedish varchar(300) NOT NULL,
 Taiwanese varchar(300) NOT NULL,
 Turkish varchar(300) CHARACTER SET utf8 COLLATE utf8_turkish_ci NOT NULL,
 Ukrainian varchar(300) NOT NULL,
 Vietnamese varchar(300) CHARACTER SET utf8 COLLATE utf8_vietnamese_ci NOT NULL,
 Welsh varchar(300) NOT NULL,
 PRIMARY KEY (phrase),
 KEY wt_op (on_page)
) ENGINE MyISAM DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC COMMENT='<a name="webcal_translations">''</a>Various language translations.';

ALTER TABLE webcal_user ENGINE MyISAM CHARACTER SET utf8 COMMENT '<a name="webcal_user">''</a>Defines a WebCalendar user.';
ALTER TABLE webcal_user ADD cal_type ENUM('A','N','S','U') DEFAULT 'U' COMMENT 'Is this an A)dmin, N)on_user, S)ystem, or ordinary U)ser? Will evevtually replace is_admin.';
ALTER TABLE webcal_user MODIFY cal_passwd varchar(32) DEFAULT NULL COMMENT 'The user''s password (not used for http.)' FIRST;
ALTER TABLE webcal_user MODIFY cal_last_login TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Date user last logged in.' FIRST;
ALTER TABLE webcal_user MODIFY cal_is_admin ENUM('N','Y') DEFAULT 'N' COMMENT 'Is the user a WebCalendar administrator? (Y or N)' FIRST;
ALTER TABLE webcal_user MODIFY cal_enabled ENUM('N','Y') DEFAULT 'Y' COMMENT 'Allow admin to disable account? (Y or N)' FIRST;
ALTER TABLE webcal_user MODIFY cal_login varchar(25) NOT NULL COMMENT 'Unique user login.' FIRST;
UPDATE webcal_user SET cal_type = 'U' WHERE cal_is_admin = 'N';
UPDATE webcal_user SET cal_type = 'A' WHERE cal_is_admin = 'Y';

ALTER TABLE webcal_user_layers ENGINE MyISAM CHARACTER SET utf8 COMMENT '<a name="webcal_user_layers">''</a>Define layers for a user.';
ALTER TABLE webcal_user_layers MODIFY cal_layerid int UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE COMMENT 'Unique layer id.' FIRST;
ALTER TABLE webcal_user_layers MODIFY cal_dups ENUM('N','Y') DEFAULT 'N' COMMENT 'Show duplicates? (Y or N)';
ALTER TABLE webcal_user_layers MODIFY cal_color varchar(25) DEFAULT NULL COMMENT 'Color (preferably in hex) of this layer.' FIRST;
ALTER TABLE webcal_user_layers MODIFY cal_layeruser varchar(25) NOT NULL COMMENT 'Login name of user that this layer represents. Also from <a href="#webcal_user">webcal_user</a> table.' FIRST;
ALTER TABLE webcal_user_layers MODIFY cal_login varchar(25) NOT NULL COMMENT 'Login of owner of this layer from <a href="#webcal_user">webcal_user</a> table.' FIRST;

/*Consolodate some fields / tables.*/
DELETE FROM webcal_config WHERE cal_value IS NULL;
DELETE FROM webcal_user_pref WHERE cal_value IS NULL;

ALTER TABLE webcal_user_pref DROP PRIMARY KEY;
ALTER TABLE webcal_user_pref ENGINE MyISAM CHARACTER SET utf8 COMMENT '<a name="webcal_user_pref">''</a>Specify preferences for a user. Most preferences are set via pref.php. Values in this table are loaded after system settings found in <a href="#webcal_config">webcal_config</a> table.';
ALTER TABLE webcal_user_pref MODIFY cal_value varchar(100) NOT NULL COMMENT 'Value of setting.';
ALTER TABLE webcal_user_pref ADD PRIMARY KEY (cal_login,cal_setting,cal_value);

ALTER TABLE webcal_user_pref MODIFY cal_login varchar(25) DEFAULT '__webcal__sys__';
INSERT INTO webcal_user_pref (cal_setting, cal_value) SELECT cal_setting, cal_value FROM webcal_config;

ALTER TABLE webcal_user_pref MODIFY cal_setting varchar(50) DEFAULT 'ACCESS_FUNCTIONS' FIRST;
ALTER TABLE webcal_user_pref MODIFY cal_login varchar(25) NOT NULL COMMENT 'User login from <a href="#webcal_user">webcal_user</a> table.' FIRST;
INSERT INTO webcal_user_pref (cal_login, cal_value) SELECT cal_login, cal_permissions FROM webcal_access_function;

ALTER TABLE webcal_user_pref MODIFY cal_setting varchar(50) DEFAULT 'ASSISTANT';
INSERT INTO webcal_user_pref (cal_login, cal_value) SELECT cal_boss, cal_assistant FROM webcal_asst;

ALTER TABLE webcal_user_pref MODIFY cal_setting varchar(50) DEFAULT 'GROUP_ID';
INSERT INTO webcal_user_pref (cal_login, cal_value) SELECT cal_login, cal_group_id FROM webcal_group_user;

ALTER TABLE webcal_user_pref MODIFY cal_setting varchar(50) DEFAULT 'ADDRESS';
INSERT INTO webcal_user_pref (cal_login, cal_value) SELECT cal_login, cal_address FROM webcal_user WHERE cal_address IS NOT NULL;

ALTER TABLE webcal_user_pref MODIFY cal_setting varchar(50) DEFAULT 'BIRTHDAY';
INSERT INTO webcal_user_pref (cal_login, cal_value) SELECT cal_login, cal_birthday FROM webcal_user WHERE cal_birthday IS NOT NULL;

ALTER TABLE webcal_user_pref MODIFY cal_setting varchar(50) DEFAULT 'DISPLAY_NAME';
INSERT INTO webcal_user_pref (cal_login, cal_value) SELECT cal_login, TRIM( CONCAT_WS( ' ',cal_firstname,cal_lastname ) ) FROM webcal_user;

ALTER TABLE webcal_user_pref MODIFY cal_setting varchar(50) DEFAULT 'EMAIL';
INSERT INTO webcal_user_pref (cal_login, cal_value) SELECT cal_login, cal_email FROM webcal_user WHERE cal_email IS NOT NULL;

ALTER TABLE webcal_user_pref MODIFY cal_setting varchar(50) DEFAULT 'FIRSTNAME';
INSERT INTO webcal_user_pref (cal_login, cal_value) SELECT cal_login, cal_firstname FROM webcal_user;

ALTER TABLE webcal_user_pref MODIFY cal_setting varchar(50) DEFAULT 'LASTNAME';
INSERT INTO webcal_user_pref (cal_login, cal_value) SELECT cal_login, cal_lastname FROM webcal_user;

ALTER TABLE webcal_user_pref MODIFY cal_setting varchar(50) DEFAULT 'PHONE';
INSERT INTO webcal_user_pref (cal_login, cal_value) SELECT cal_login, cal_telephone FROM webcal_user WHERE cal_telephone IS NOT NULL;

ALTER TABLE webcal_user_pref MODIFY cal_setting varchar(50) DEFAULT 'TITLE';
INSERT INTO webcal_user_pref (cal_login, cal_value) SELECT cal_login, cal_title FROM webcal_user WHERE cal_title IS NOT NULL;

ALTER TABLE webcal_user_pref MODIFY cal_setting varchar(50) DEFAULT 'VIEW_ID';
INSERT INTO webcal_user_pref (cal_login, cal_value) SELECT cal_login, cal_view_id FROM webcal_view_user;

ALTER TABLE webcal_user_pref MODIFY cal_setting varchar(50) NOT NULL COMMENT 'Setting name.';

UPDATE webcal_user_template SET cal_login = '__webcal__sys__' WHERE cal_login = '__system_';
ALTER TABLE webcal_user_template ENGINE MyISAM CHARACTER SET utf8 COMMENT '<a name="webcal_user_template">''</a>This table stores the custom header/stylesheet/trailer. If configured properly, each user (or nonuser cal) can have their own custom header/trailer.';
ALTER TABLE webcal_user_template MODIFY cal_type ENUM('H','S','T') NOT NULL COMMENT 'H)eader, S)tylesheet/script, T)railer.' FIRST;
ALTER TABLE webcal_user_template MODIFY cal_template_text text COMMENT 'Text of template.';
ALTER TABLE webcal_user_template MODIFY cal_login varchar(25) NOT NULL COMMENT 'User login (or nonuser cal name), the default for all users is stored with the username "__webcal__sys__".' FIRST;

ALTER TABLE webcal_view ENGINE MyISAM CHARACTER SET utf8 COMMENT '<a name="webcal_view">''</a>A "view" allows a user to put the calendars of multiple users all on one page. A "view" is valid only for the owner (cal_owner) of the view. Users for the view are in <a href="#webcal_view_user">webcal_view_user</a> table.';
ALTER TABLE webcal_view MODIFY cal_view_type ENUM('D','M','W') DEFAULT NULL COMMENT '"W" for week view, "D" for day view, "M" for month view.';
ALTER TABLE webcal_view MODIFY cal_owner varchar(25) NOT NULL COMMENT 'Login name of owner of this view. From <a href="#webcal_user">webcal_user</a> table.' FIRST;
ALTER TABLE webcal_view MODIFY cal_name varchar(50) NOT NULL COMMENT 'Name of view.' FIRST;
ALTER TABLE webcal_view MODIFY cal_is_global ENUM('N','Y') NOT NULL DEFAULT 'N' COMMENT 'Is this a global view? (can it be accessed by other users - "Y" or "N")' FIRST;
ALTER TABLE webcal_view MODIFY cal_view_id int UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Unique view id.' FIRST;

ALTER TABLE webcal_view_user ENGINE MyISAM CHARACTER SET utf8 COMMENT '<a name="webcal_view_user">''</a>Specify users in a view.';
ALTER TABLE webcal_view_user MODIFY cal_login varchar(25) NOT NULL COMMENT 'A user in the view. From <a href="#webcal_user">webcal_user</a> table.';
ALTER TABLE webcal_view_user MODIFY cal_view_id int UNSIGNED NOT NULL COMMENT 'view id from <a href="#webcal_view">webcal_view</a> table.' FIRST;
/*upgrade_v1.9.0*/
ALTER TABLE webcal_import ADD cal_check_date INT NULL;
ALTER TABLE webcal_import ADD cal_md5 VARCHAR(32) NULL DEFAULT NULL;
CREATE INDEX webcal_import_data_type ON webcal_import_data(cal_import_type);
CREATE INDEX webcal_import_data_ext_id ON webcal_import_data(cal_external_id);
ALTER TABLE webcal_user MODIFY cal_passwd VARCHAR(255);
/*upgrade_v1.9.1*/
