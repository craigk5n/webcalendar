/*
 * Description:
 * This file is used to create all tables used by WebCalendar and
 * initialize some of those tables with the required data.
 *
 * The comments in the table definitions will be parsed to
 * generate a document (in HTML) that describes these tables.
 *
 * History:
 * 21-Oct-2002 Added this file header and additional comments
 *   below.
 */

/*
 * Defines a WebCalendar user.
 */
CREATE TABLE webcal_user (
  /* the unique user login */
  cal_login VARCHAR(25) NOT NULL,
  /* the user's password. (not used for http or ldap authentication) */
  cal_passwd VARCHAR(32),
  /* user's last name */
  cal_lastname VARCHAR(25),
  /* user's first name */
  cal_firstname VARCHAR(25),
  /* is the user a WebCalendar administrator ('Y' = yes, 'N' = no) */
  cal_is_admin CHAR(1) DEFAULT 'N',
  /* user's email address */
  cal_email VARCHAR(75) NULL,
  PRIMARY KEY ( cal_login )
);

# create a default admin user
INSERT INTO webcal_user ( cal_login, cal_passwd, cal_lastname, cal_firstname, cal_is_admin ) VALUES ( 'admin', '21232f297a57a5a743894a0e4a801fc3', 'Administrator', 'Default', 'Y' );

/*
 * Defines a calendar event.  Each event in the system has one entry
 * in this table unless the event starts before midnight and ends
 * after midnight. In that case a secondary event will be created with
 * cal_ext_for_id set to the cal_id of the original entry.
 * The following tables contain additional information about each
 * event:<ul>
 * <li><a href="#webcal_entry_user">webcal_entry_user</a> -
 *  lists participants in the event and specifies the status (accepted,
 *  rejected) and category of each participant.</li>
 * <li><a href="#webcal_entry_repeats">webcal_entry_repeats</a> -
 *  contains information if the event repeats.</li>
 * <li><a href="#webcal_entry_repeats_not">webcal_entry_repeats_not</a> -
 *  specifies which dates the repeating event does not repeat (because
 *  they were deleted or modified for just that date by the user)</li>
 * <li><a href="#webcal_entry_log">webcal_entry_log</a> -
 *  provides a history of changes to this event.</li>
 * <li><a href="#webcal_site_extras">webcal_site_extras</a> -
 *  stores event data as defined in site_extras.php (such as reminders and
 *  other custom event fields).</li>
 * </ul>
 */
CREATE TABLE webcal_entry (
  /* cal_id is unique integer id for event */
  cal_id INT NOT NULL,
  /* cal_group_id: the parent event id if this event is overriding an */
  /* occurrence of a repeating event */
  cal_group_id INT NULL,
  /* used when an event goes past midnight into the */
  /* next day, in which case an additional entry in this table */
  /* will use this field to indicate the original event cal_id */
  cal_ext_for_id INT NULL,
  /* user login of user that created the event */
  cal_create_by VARCHAR(25) NOT NULL,
  /* date of event (in YYYYMMDD format) */
  cal_date INT NOT NULL,
  /* event time (in HHMMSS format) */
  cal_time INT NULL,
  /* date the event was last modified (in YYYYMMDD format) */
  cal_mod_date INT,
  /* time the event was last modified (in HHMMSS format) */
  cal_mod_time INT,
  /* duration of event in minutes */
  cal_duration INT NOT NULL,
  /* event priority: 1=Low, 2=Med, 3=High */
  cal_priority INT DEFAULT 2,
  /* 'E' = Event, 'M' = Repeating event */
  cal_type CHAR(1) DEFAULT 'E',
  /* 'P' = Public, */
  /* 'R' = Confidential (others can see time allocated but not what it is) */
  cal_access CHAR(1) DEFAULT 'P',
  /* brief description of event */
  cal_name VARCHAR(80) NOT NULL,
  /* full description of event */
  cal_description TEXT,
  PRIMARY KEY ( cal_id )
);

/*
 * Defines repeating info about an event.
 * The event is defined in <a href="#webcal_entry">webcal_entry</a>.
 */
CREATE TABLE webcal_entry_repeats (
  /* event id */
  cal_id INT DEFAULT 0 NOT NULL,
  /* type of repeating:<ul> */
  /* <li>daily - repeats daily</li> */
  /* <li>monthlyByDate - repeats on same day of the month</li> */
  /* <li>monthlyByDayR - repeats on same weekday of the month */
  /*   (counting weeks from the end of the month is in last Monday)</li> */
  /* <li>monthlyByDay - repeats on specified weekday (2nd Monday, for example)</li> */
  /* <li>weekly - repeats every week</li> */
  /* <li>yearly - repeats on same date every year</li> */
  cal_type VARCHAR(20),
  /* end date for repeating event (in YYYYMMDD format) */
  cal_end INT,
  /* frequency of repeat: 1 = every, 2 = every other, 3 = every 3rd, etc. */
  cal_frequency INT DEFAULT 1,
  /* which days of the week does it repeat on (only applies when cal_type = 'weekly' */
  cal_days CHAR(7),
  PRIMARY KEY (cal_id)
);

/*
 * This table specifies which dates in a repeating
 * event have either been deleted or replaced with
 * a replacement event for that day.  When replaced, the cal_group_id
 * (I know... not the best name, but it was not being used) column will
 * be set to the original event.  That way the user can delete the original
 * event and (at the same time) delete any exception events.
 */
CREATE TABLE webcal_entry_repeats_not (
  /* event id of repeating event */
  cal_id INT NOT NULL,
  /* cal_date: date event should not repeat (in YYYYMMDD format) */
  cal_date INT NOT NULL,
  PRIMARY KEY ( cal_id, cal_date )
);

/*
 * This table associates one or more users with an event by the event id.
 * The event can be found in
 * <a href="#webcal_entry">webcal_entry</a>.
 */
CREATE TABLE webcal_entry_user (
  /* event id */
  cal_id INT DEFAULT 0 NOT NULL,
  /* participant in the event */
  cal_login VARCHAR(25) NOT NULL,
  /* status of event for this user: <ul> */
  /* <li>A=Accepted</li> */
  /* <li>R=Rejected</li> */
  /* <li>W=Waiting</li>    </ul>*/
  cal_status CHAR(1) DEFAULT 'A',
  /* category of the event for this user */
  cal_category INT DEFAULT NULL,
  PRIMARY KEY ( cal_id, cal_login )
);

/*
 * This table associates one or more external users (people who do not
 * have a WebCalendar login) with an event by the event id.
 * An event must still have at least one WebCalendar user associated
 * with it.  This table is not used unless external users are enabled
 * in system settings.
 * The event can be found in
 * <a href="#webcal_entry">webcal_entry</a>.
 */
CREATE TABLE webcal_entry_ext_user (
  /* event id */
  cal_id INT DEFAULT 0 NOT NULL,
  /* external user fill name */
  cal_fullname VARCHAR(50) NOT NULL,
  /* external user email (for sending a reminder) */
  cal_email VARCHAR(75) NULL,
  PRIMARY KEY ( cal_id, cal_fullname )
);

/*
 * Specify preferences for a user.
 * Most preferences are set via pref.php.
 * Values in this table are loaded after system settings
 * found in <a href="#webcal_config">webcal_config</a>.
 */
CREATE TABLE webcal_user_pref (
  /* user login */
  cal_login VARCHAR(25) NOT NULL,
  /* setting name */
  cal_setting VARCHAR(25) NOT NULL,
  /* setting value */
  cal_value VARCHAR(100) NULL,
  PRIMARY KEY ( cal_login, cal_setting )
);

/*
 * Define layers for a user.
 */
CREATE TABLE webcal_user_layers (
  /* unique layer id */
  cal_layerid INT DEFAULT 0 NOT NULL,
  /* login of owner of this layer */
  cal_login VARCHAR(25) NOT NULL,
  /* login name of user that this layer represents */
  cal_layeruser VARCHAR(25) NOT NULL,
  /* color to display this layer in */
  cal_color VARCHAR(25) NULL,
  /* show duplicates ('N' or 'Y') */
  cal_dups CHAR(1) DEFAULT 'N',
  PRIMARY KEY ( cal_login, cal_layeruser )
);

/*
 * This table holds data for site extra fields
 * (customized in site_extra.php).
 */
CREATE TABLE webcal_site_extras (
  /* event id */
  cal_id INT DEFAULT 0 NOT NULL,
  /* the brief name of this type (first field in $site_extra array) */
  cal_name VARCHAR(25) NOT NULL,
  /* $EXTRA_URL, $EXTRA_DATE, etc. */
  cal_type INT NOT NULL,
  /* only used for $EXTRA_DATE type fields (in YYYYMMDD format) */
  cal_date INT DEFAULT 0,
  /* how many minutes before event should a reminder be sent */
  cal_remind INT DEFAULT 0,
  /* used to store text data */
  cal_data TEXT,
  PRIMARY KEY ( cal_id, cal_name, cal_type )
);

/*
 * This table keeps a history of when reminders get sent.
 */
CREATE TABLE webcal_reminder_log (
  /* event id */
  cal_id INT DEFAULT 0 NOT NULL,
  /* extra type (see site_extras.php) */
  cal_name VARCHAR(25) NOT NULL,
  /* the event date we are sending reminder for (in YYYYMMDD format) */
  cal_event_date INT NOT NULL DEFAULT 0,
  /* the date/time we last sent a reminder (in UNIX time format) */
  cal_last_sent INT NOT NULL DEFAULT 0,
  PRIMARY KEY ( cal_id, cal_name, cal_event_date )
);

/*
 * Define a group.  Group members can be found in
 * <a href="#webcal_group_user">webcal_group_user</a>.
 */
CREATE TABLE webcal_group (
  /* unique group id */
  cal_group_id INT NOT NULL,
  /* user login of user that created this group */
  cal_owner VARCHAR(25) NULL,
  /* name of the group */
  cal_name VARCHAR(50) NOT NULL,
  /* date last updated (in YYYYMMDD format) */
  cal_last_update INT NOT NULL,
  PRIMARY KEY ( cal_group_id )
);

/*
 * Specify users in a group.  The group is defined in
 * <a href="#webcal_group">webcal_group</a>.
 */
CREATE TABLE webcal_group_user (
  /* group id */
  cal_group_id INT NOT NULL,
  /* user login */
  cal_login VARCHAR(25) NOT NULL,
  PRIMARY KEY ( cal_group_id, cal_login )
);

/*
 * A "view" allows a user to put the calendars of multiple users all on
 * one page.  A "view" is valid only for the owner (cal_owner) of the
 * view.  Users for the view are in
 * <a href="#webcal_view_user">webcal_view_user</a>.
 */
CREATE TABLE webcal_view (
  /* unique view id */
  cal_view_id INT NOT NULL,
  /* login name of owner of this view */
  cal_owner VARCHAR(25) NOT NULL,
  /* name of view */
  cal_name VARCHAR(50) NOT NULL,
  /* "W" for week view, "D" for day view, "M" for month view */
  cal_view_type CHAR(1),
  /* is this a global view (can it be accessed by other users) ('Y' or 'N') */
  cal_is_global CHAR(1) DEFAULT 'N' NOT NULL,
  PRIMARY KEY ( cal_view_id )
);

/*
 * Specify users in a view. See <a href="#webcal_view">webcal_view</a>.
 */
CREATE TABLE webcal_view_user (
  /* view id */
  cal_view_id INT NOT NULL,
  /* a user in the view */
  cal_login VARCHAR(25) NOT NULL,
  PRIMARY KEY ( cal_view_id, cal_login )
);

/*
 * System settings (set by the admin interface in admin.php)
 */
CREATE TABLE webcal_config (
  /* setting name */
  cal_setting VARCHAR(50) NOT NULL,
  /* setting value */
  cal_value VARCHAR(100) NULL,
  PRIMARY KEY ( cal_setting )
);

# default settings
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


/*
 * Activity log for an event.
 */
CREATE TABLE webcal_entry_log (
  /* unique id of this log entry */
  cal_log_id INT NOT NULL,
  /* event id */
  cal_entry_id INT NOT NULL,
  /* user who performed this action */
  cal_login VARCHAR(25) NOT NULL,
  /* user of calendar affected */
  cal_user_cal VARCHAR(25) NULL,
  /* log types:  <ul> */
  /* <li>C: Created</li>  */
  /* <li>A: Approved/Confirmed by user</li>  */
  /* <li>R: Rejected by user</li>  */
  /* <li>U: Updated by user</li>  */
  /* <li>M: Mail Notification sent</li>  */
  /* <li>E: Reminder sent</li>     </ul>*/
  cal_type CHAR(1) NOT NULL,
  /* date in YYYYMMDD format */
  cal_date INT NOT NULL,
  /* time in HHMMSS format */
  cal_time INT NULL,
  /* optional text */
  cal_text TEXT,
  PRIMARY KEY ( cal_log_id )
);

/*
 * Defines user categories.
 * Categories can be specific to a user or global.  When a category is global,
 * the cat_owner field will be NULL.  (Only an admin user can create
 * a global category.)
 */
CREATE TABLE webcal_categories (
  /* unique category id */
  cat_id INT NOT NULL,
  /* user login of category owner. */
  /* If this is NULL, then it is a global category */
  cat_owner VARCHAR(25) NULL,
  /* category name */
  cat_name VARCHAR(80) NOT NULL,
  PRIMARY KEY ( cat_id )
);

/*
 * Define assitant/boss relationship.
 */
CREATE TABLE webcal_asst (
  /* user login of boss */
  cal_boss VARCHAR(25) NOT NULL,
  /* user login of assistant */
  cal_assistant VARCHAR(25) NOT NULL,
  PRIMARY KEY ( cal_boss, cal_assistant )
);

/*
 * Defines non-user calendars.
 */
CREATE TABLE webcal_nonuser_cals (
  /* the unique id for the calendar */
  cal_login VARCHAR(25) NOT NULL,
  /* calendar's last name */
  cal_lastname VARCHAR(25) NULL,
  /* calendar's first name */
  cal_firstname VARCHAR(25) NULL,
  /* who is the calendar administrator */
  cal_admin VARCHAR(25) NOT NULL,
  PRIMARY KEY ( cal_login )
);

/*
 * Used to track import data (one row per import)
 */
CREATE TABLE webcal_import (
  /* unique id for import */
  cal_import_id INT NOT NULL,
  /* name of import (optional) */
  cal_name VARCHAR(50) NULL,
  /* date of import (YYYYMMDD format) */
  cal_date INT NOT NULL,
  /* type of import (ical, vcal, palm) */
  cal_type VARCHAR(10) NOT NULL,
  /* user who performed the import */
  cal_login VARCHAR(25) NULL,
  PRIMARY KEY ( cal_import_id )
);

/*
 * Used to track import data (one row per event)
 */
CREATE TABLE webcal_import_data (
  /* import id (from webcal_import table) */
  cal_import_id INT NOT NULL,
  /* event id in WebCalendar */
  cal_id INT NOT NULL,
  /* user login */
  cal_login VARCHAR(25) NOT NULL,
  /* type of import: 'palm', 'vcal', 'ical' or 'publish' */
  cal_import_type VARCHAR(15) NOT NULL,
  /* external id used in external calendar system (for example, UID in iCal) */
  cal_external_id VARCHAR(200) NULL,
  PRIMARY KEY  ( cal_id, cal_login )
);


/*
 * Defines a custom report created by a user.
 */
CREATE TABLE webcal_report (
  /* creator of report */
  cal_login VARCHAR(25) NOT NULL,
  /* unique id of this report */
  cal_report_id INT NOT NULL,
  /* is this a global report (can it be accessed by other users) ('Y' or 'N') */
  cal_is_global CHAR(1) DEFAULT 'N' NOT NULL,
  /* format of report (html, plain or csv) */
  cal_report_type VARCHAR(20) NOT NULL,
  /* if cal_report_type is 'html', should the default HTML header and */
  /* trailer be included? ('Y' or 'N') */
  cal_include_header CHAR(1) DEFAULT 'Y' NOT NULL,
  /* name of the report */
  cal_report_name VARCHAR(50) NOT NULL,
  /* time range for report:  <ul> */
  /* <li>0 = tomorrow</li> */
  /* <li>1 = today</li> */
  /* <li>2 = yesterday</li> */
  /* <li>3 = day before yesterday</li> */
  /* <li>10 = next week</li> */
  /* <li>11 = current week</li> */
  /* <li>12 = last week</li> */
  /* <li>13 = week before last</li> */
  /* <li>20 = next week and week after</li> */
  /* <li>21 = current week and next week</li> */
  /* <li>22 = last week and this week</li> */
  /* <li>23 = last two weeks</li> */
  /* <li>30 = next month</li> */
  /* <li>31 = current month</li> */
  /* <li>32 = last month</li> */
  /* <li>33 = month before last</li> */
  /* <li>40 = next year</li> */
  /* <li>41 = current year</li> */
  /* <li>42 = last year</li> */
  /* <li>43 = year before last</li> */
  /* </ul> */
  cal_time_range INT NOT NULL,
  /* user calendar to display (NULL indicates current user) */
  cal_user VARCHAR(25) NULL,
  /* allow user to navigate to different dates with next/previous ('Y' or 'N') */
  cal_allow_nav CHAR(1) DEFAULT 'Y',
  /* category to filter on (optional) */
  cal_cat_id INT NULL,
  /* include empty dates in report ('Y' or 'N') */
  cal_include_empty CHAR(1) DEFAULT 'N',
  /* include a link for this report in the "Go to" section of the navigation */
  /* in the page trailer ('Y' or 'N') */
  cal_show_in_trailer CHAR(1) DEFAULT 'N',
  /* date created or last updated (in YYYYMMDD format) */
  cal_update_date INT NOT NULL,
  PRIMARY KEY ( cal_report_id )
);

/*
 * Defines one of the templates used for a report.
 * Each report has three templates:
 * <ol>
 * <li>Page template - Defines the entire page (except for header and
 *   footer).  The following variables can be defined:
 *   <ul>
 *     <li>${days}<sup>*</sup> - the HTML of all dates (generated from the Date template)</li>
 *   </ul></li>
 * <li>Date template - Defines events for one day.  If the report
 *   is for a week or month, then the results of each day will be
 *   concatenated and used as the ${days} variable in the Page template.
 *   The following variables can be defined:
 *   <ul>
 *     <li>${events}<sup>*</sup> - the HTML of all events
 *          for the data (generated from the Event template)</li>
 *     <li>${date} - the date</li>
 *     <li>${fulldate} - date (includes weekday)</li>
 *   </ul></li>
 * <li>Event template - Defines a single event.
 *      The following variables can be defined:
 *   <ul>
 *     <li>${name}<sup>*</sup> - Brief Description of event</li>
 *     <li>${description} - Full Description of event</li>
 *     <li>${date} - Date of event</li>
 *     <li>${fulldate} - Date of event (includes weekday)</li>
 *     <li>${time} - Time of event (4:00pm - 4:30pm)</li>
 *     <li>${starttime} - Start time of event</li>
 *     <li>${endtime} - End time of event</li>
 *     <li>${duration} - Duration of event (in minutes)</li>
 *     <li>${priority} - Priority of event</li>
 *     <li>${href} - URL to view event details</li>
 *   </ul></li>
 * </ol>
 * <sup>*</sup> denotes a required template variable
 */
CREATE TABLE webcal_report_template (
  /* report id (in webcal_report table) */
  cal_report_id INT NOT NULL,
  /* type of template: <ul> */
  /* <li>'P': page template represents entire document</li> */
  /* <li>'D': date template represents a single day of events</li> */
  /* <li>'E': event template represents a single event</li> */
  /* </ul> */
  cal_template_type CHAR(1) NOT NULL,
  /* text of template */
  cal_template_text TEXT,
  PRIMARY KEY ( cal_report_id, cal_template_type )
);
