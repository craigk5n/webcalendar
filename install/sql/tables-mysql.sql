/* $Id: tables-mysql.sql,v 1.24.2.2 2007/08/06 02:28:29 cknudsen Exp $
 *
 * Description:
 * This file is used to create all tables used by WebCalendar and
 * initialize some of those tables with the required data.
 *
 * The comments in the table definitions will be parsed to
 * generate a document (in HTML) that describes these tables.
 */

/*
 * Defines a WebCalendar user.
 */
CREATE TABLE webcal_user (
  /* the unique user login */
  cal_login VARCHAR(25) NOT NULL,
  /* the user's password. (not used for http) */
  cal_passwd VARCHAR(32),
  /* user's last name */
  cal_lastname VARCHAR(25),
  /* user's first name */
  cal_firstname VARCHAR(25),
  /* is the user a WebCalendar administrator ('Y' = yes, 'N' = no) */
  cal_is_admin CHAR(1) DEFAULT 'N',
  /* user's email address */
  cal_email VARCHAR(75) NULL,
  /* allow admin to disable account ('Y' = yes, 'N' = no) */
  cal_enabled CHAR(1) DEFAULT 'Y',
  /* user's telephone */
  cal_telephone VARCHAR(50) NULL,
  /* user's address */
  cal_address VARCHAR(75) NULL,
  /* user's title */
  cal_title VARCHAR(75) NULL,
  /* user's birthday */
  cal_birthday INT NULL,
 /* user's last log in date */
  cal_last_login INT NULL,
  PRIMARY KEY ( cal_login )
);

# create a DEFAULT admin user
INSERT INTO webcal_user
  ( cal_login, cal_passwd, cal_lastname, cal_firstname, cal_is_admin )
  VALUES ( 'admin', '21232f297a57a5a743894a0e4a801fc3', 'Administrator',
  'Default', 'Y' );

/*
 * Defines a calendar event.  Each event in the system has one entry in
 * this table unless the event starts before midnight and ends after
 * midnight. In that case a secondary event will be created with
 * cal_ext_for_id set to the cal_id of the original entry.  The following
 * tables contain additional information about each event:
 * <ul>
 *   <li><a href="#webcal_entry_user">webcal_entry_user</a>
 *     - lists participants in the event and specifies the status
 *       (accepted, rejected) and category of each participant.</li>
 *   <li><a href="#webcal_entry_repeats">webcal_entry_repeats</a>
 *     - contains information if the event repeats.</li>
 *   <li><a href="#webcal_entry_repeats_not">webcal_entry_repeats_not</a>
 *     - specifies which dates the repeating event does not repeat
 *       (because they were deleted or modified for just that date by the
 *       user)</li>
 *   <li><a href="#webcal_entry_log">webcal_entry_log</a>
 *     - provides a history of changes to this event.</li>
 *   <li><a href="#webcal_site_extras">webcal_site_extras</a>
 *     - stores event data as defined in site_extras.php (such as
 *       reminders and other custom event fields).</li>
 * </ul>
 */
CREATE TABLE webcal_entry (
  /* cal_id is unique integer id for event */
  cal_id INT NOT NULL,
  /* cal_group_id: the parent event id if this event is overriding an */
  /* occurrence of a repeating event */
  cal_group_id INT NULL,
  /* used when an event goes past midnight into the next day, */
  /* in which case an additional entry in this table */
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
  /* Task due date */
  cal_due_date INT DEFAULT NULL,
  /* Task due time */
  cal_due_time INT DEFAULT NULL,
  /* event priority: 1=Low, 2=Med, 3=High */
  cal_priority INT DEFAULT 5,
  /* 'E' = Event, 'M' = Repeating event, 'T' = Task */
  cal_type CHAR(1) DEFAULT 'E',
  /* 'P' = Public, */
  /* 'R' = Private (others cannot see the event), */
  /* 'C' = Confidential (others can see time allocated but not what it is) */
  cal_access CHAR(1) DEFAULT 'P',
  /* brief description of event */
  cal_name VARCHAR(80) NOT NULL,
  /* location of event */
  cal_location varchar(100) DEFAULT NULL,
  /* URL of event */
  cal_url varchar(100) DEFAULT NULL,
  /* date task completed */
  cal_completed INT DEFAULT NULL,
  /* full description of event */
  cal_description TEXT,
  PRIMARY KEY ( cal_id )
);

/*
 * Contains category foreign keys
 * to enable multiple categories for each event or task
 */
CREATE TABLE webcal_entry_categories (
  /* id of event. Not unique*/
  cal_id INT DEFAULT 0 NOT NULL,
  /* id of category. Not unique */
  cat_id INT DEFAULT 0 NOT NULL,
  /* order that user requests their categories appear. */
  /* Globals are always last */
  cat_order INT DEFAULT 0 NOT NULL,
  /* user that owns this record. Global categories will be NULL */
  cat_owner varchar(25) DEFAULT NULL
);

/*
 * Defines repeating info about an event.
 * The event is defined in <a href="#webcal_entry">webcal_entry</a>.
 */
CREATE TABLE webcal_entry_repeats (
  /* event id */
  cal_id INT DEFAULT 0 NOT NULL,
  /* type of repeating: */
  /* <ul> */
  /* <li>daily - repeats daily</li> */
  /* <li>monthlyByDate - repeats on same day of the month</li> */
  /*   <li>monthlyBySetPos */
  /*     - repeats based on position within other ByXXX values</li> */
  /*   <li>monthlyByDay */
  /*     - repeats on specified weekday (2nd Monday, for example)</li> */
  /* <li>weekly - repeats every week</li> */
  /*   <li>yearly - repeats on same date every year</li> */
  /* </ul> */
  cal_type VARCHAR(20),
  /* end date for repeating event (in YYYYMMDD format) */
  cal_end INT,
  cal_endtime INT DEFAULT NULL,
  /* frequency of repeat: 1 = every, 2 = every other, 3 = every 3rd, etc. */
  cal_frequency INT DEFAULT 1,
  /* NO LONGER USED. We'll leave it in for now */
  cal_days CHAR(7),
  /* the following columns are values as specified in RFC2445 */
  cal_bymonth varchar(50) DEFAULT NULL,
  cal_bymonthday varchar(100) DEFAULT NULL,
  cal_byday varchar(100) DEFAULT NULL,
  cal_bysetpos varchar(50) DEFAULT NULL,
  cal_byweekno varchar(50) DEFAULT NULL,
  cal_byyearday varchar(50) DEFAULT NULL,
  cal_wkst char(2) DEFAULT 'MO',
  cal_count INT DEFAULT NULL,
  PRIMARY KEY (cal_id)
);

/*
 * This table specifies which dates in a repeating event have either been
 * deleted, included, or replaced with a replacement event for that day.
 * When replaced, the cal_group_id (I know... not the best name, but it
 * was not being used) column will be set to the original event.
 * That way the user can delete the original event and (at the same time)
 * delete any exception events.
 */
CREATE TABLE webcal_entry_repeats_not (
  /* event id of repeating event */
  cal_id INT NOT NULL,
  /* cal_date: date event should not repeat (in YYYYMMDD format) */
  cal_date INT NOT NULL,
  /* indicates whether this record is an exclusion (1) or inclusion (0) */
  cal_exdate int(1) NOT NULL DEFAULT 1,
  PRIMARY KEY ( cal_id, cal_date )
);

/*
 * This table associates one or more users with an event by the event id.
 * The event can be found in <a href="#webcal_entry">webcal_entry</a>.
 */
CREATE TABLE webcal_entry_user (
  /* event id */
  cal_id INT DEFAULT 0 NOT NULL,
  /* participant in the event */
  cal_login VARCHAR(25) NOT NULL,
  /* status of event for this user: */
  /* <ul> */
  /* <li>A=Accepted</li> */
  /* <li>C=Completed</li> */
  /* <li>D=Deleted</li> */
  /* <li>P=In-Progress</li> */
  /* <li>R=Rejected/Declined</li> */
  /*   <li>W=Waiting</li> */
  /* </ul>*/
  cal_status CHAR(1) DEFAULT 'A',
  /* category of the event for this user */
  cal_category INT DEFAULT NULL,
  /* Task percentage of completion for this user's task */
  cal_percent INT DEFAULT 0 NOT NULL,
  PRIMARY KEY ( cal_id, cal_login )
);

/*
 * This table associates one or more external users (people who do not
 * have a WebCalendar login) with an event by the event id.  An event must
 * still have at least one WebCalendar user associated with it.  This table
 * is not used unless external users are enabled* in system settings.  The
 * event can be found in <a href="#webcal_entry">webcal_entry</a>.
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
  /* EXTRA_URL, EXTRA_DATE, etc. */
  cal_type INT NOT NULL,
  /* only used for EXTRA_DATE type fields (in YYYYMMDD format) */
  cal_date INT DEFAULT 0,
  /* how many minutes before event should a reminder be sent */
  cal_remind INT DEFAULT 0,
  /* used to store text data */
  cal_data TEXT
);

/*
 * Stores information about reminders
 */
CREATE TABLE webcal_reminders (
  cal_id INT  NOT NULL default '0',
  /* timestamp that specifies send datetime. */
  /* Use this or cal_offset, but not both */
  cal_date INT NOT NULL default '0',
  /* offset in minutes from the selected edge */
  cal_offset INT NOT NULL default '0',
  /* S=Start, E=End. Specifies which edge of entry this reminder applies to */
  cal_related CHAR(1) NOT NULL default 'S',
  /* specifies whether reminder is sent before or after selected edge */
  cal_before CHAR(1) NOT NULL default 'Y',
  /* timestamp of last sent reminder */
  cal_last_sent INT NOT NULL default '0',
  /* number of times to repeat in addition to original occurance */
  cal_repeats INT NOT NULL default '0',
  /* time in ISO 8601 format that specifies time between repeated reminders */
  cal_duration INT NOT NULL default '0',
  /* number of times this reminder has been sent */
  cal_times_sent INT NOT NULL default '0',
  /* action as imported, may be used in the future */
  cal_action VARCHAR(12) NOT NULL default 'EMAIL',
  PRIMARY KEY ( cal_id )
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
 * A "view" allows a user to put the calendars of multiple users all on one
 * page.  A "view" is valid only for the owner (cal_owner) of the view.
 * Users for the view are in
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
  /* log types: */
  /* <ul> */
  /* <li>C: Created</li>  */
  /* <li>A: Approved/Confirmed by user</li>  */
  /* <li>R: Rejected by user</li>  */
  /* <li>U: Updated by user</li>  */
  /* <li>M: Mail Notification sent</li>  */
  /*   <li>E: Reminder sent</li> */
  /* </ul> */
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
 * Defines user categories.  Categories can be specific to a user or global.
 * When a category is global, the cat_owner field will be NULL.
 * (Only an admin user can create a global category.)
 */
CREATE TABLE webcal_categories (
  /* unique category id */
  cat_id INT NOT NULL,
  /* user login of category owner. */
  /* If this is NULL, then it is a global category */
  cat_owner VARCHAR(25) NULL,
  /* category name */
  cat_name VARCHAR(80) NOT NULL,
  /* RGB color for category */
  cat_color VARCHAR(8) NULL,
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
  /* can this nonuser calendar be a public calendar (no login required) */
  cal_is_public CHAR(1) NOT NULL DEFAULT 'N',
  /* url of the remote calendar */
  cal_url VARCHAR(255) DEFAULT NULL,
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
  /* type of import (ical, vcal, palm, outlookcsv) */
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
  /* type of import: 'palm', 'vcal', 'ical' or 'outlookcsv' */
  cal_import_type VARCHAR(15) NOT NULL,
  /* external id used in external calendar system */
  /* (for example, UID in iCal) */
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
  /* is this a global report (can it be accessed by other users) */
  /* ('Y' or 'N') */
  cal_is_global CHAR(1) DEFAULT 'N' NOT NULL,
  /* format of report (html, plain or csv) */
  cal_report_type VARCHAR(20) NOT NULL,
  /* if cal_report_type is 'html', should the DEFAULT HTML header and */
  /* trailer be included? ('Y' or 'N') */
  cal_include_header CHAR(1) DEFAULT 'Y' NOT NULL,
  /* name of the report */
  cal_report_name VARCHAR(50) NOT NULL,
  /* time range for report: */
  /* <ul> */
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
  /* allow user to navigate to different dates with next/previous */
  /* ('Y' or 'N') */
  cal_allow_nav CHAR(1) DEFAULT 'Y',
  /* category to filter on (optional) */
  cal_cat_id INT NULL,
  /* include empty dates in report ('Y' or 'N') */
  cal_include_empty CHAR(1) DEFAULT 'N',
  /* include a link for this report in the "Go to" section of the */
  /* navigation in the page trailer ('Y' or 'N') */
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
 *     <li>${days}<sup>*</sup>
 *       - the HTML of all dates (generated from the Date template)</li>
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

/*
 * Specifies which users can access another user's calendar.
 */
CREATE TABLE webcal_access_user (
  /* the current user who is attempting to look at another user's calendar */
  cal_login VARCHAR(25) NOT NULL,
  /* the login of the other user whose calendar the current user */
  /* wants to access */
  cal_other_user VARCHAR(25) NOT NULL,
  /* can current user view events on the other user's calendar? */
  cal_can_view INT NOT NULL DEFAULT '0',
  /* can current user edit events on the other user's calendar?  */
  cal_can_edit INT NOT NULL DEFAULT '0',
  /* can current user approve events on the other user's calendar? */
  cal_can_approve INT NOT NULL DEFAULT '0',
  /* can current user see other user in Participant lists? */
  cal_can_invite CHAR(1) DEFAULT 'Y',
  /* can current user send emails to other user? */
  cal_can_email CHAR(1) DEFAULT 'Y',
  /* can current user can only see time of other user? */
  cal_see_time_only CHAR(1) DEFAULT 'N',
  PRIMARY KEY ( cal_login, cal_other_user )
);

/*
 * Specifies what WebCalendar functions a user can access.  Each function
 * has a corresponding numeric value (specified in the file
 * includes/access.php).  For example, view event is 0, so the very first
 * character in the cal_permissions column is either a "Y" if this user
 * can view events or a "N" if they cannot.
 */
CREATE TABLE webcal_access_function (
  /* user login */
  cal_login VARCHAR(25) NOT NULL,
  /* a string of 'Y' or 'N' for the various functions */
  cal_permissions VARCHAR(64) NOT NULL,
  PRIMARY KEY ( cal_login )
);

/*
 * This table stores the custom header/stylesheet/trailer.  If configured
 * properly, each user (or nonuser cal) can have their own custom
 * header/trailer.
 */
CREATE TABLE webcal_user_template (
  /* user login (or nonuser cal name), the DEFAULT for all users is stored */
  /* with the username '__system__' */
  cal_login VARCHAR(25) NOT NULL,
  /* type ('H' = header, 'S' = stylesheet/script, 'T' = trailer) */
  cal_type CHAR(1) NOT NULL,
  /* text of template */
  cal_template_text TEXT,
  PRIMARY KEY ( cal_login, cal_type )
);

/*
 * This table stores event attachments and comments.
 */
CREATE TABLE webcal_blob (
  /* Unique identifier for this object */
  cal_blob_id INT NOT NULL,
  /* event id (if applicable) */
  cal_id INT NULL,
  /* login of user who created */
  cal_login VARCHAR(25) NULL,
  /* filename of object (not used for comments) */
  cal_name VARCHAR(30) NULL,
  /* description of what the object is (subject for comment) */
  cal_description VARCHAR(128) NULL,
  /* size of object (not used for comment) */
  cal_size INT NULL,
  /* MIME type of object (as specified by browser during upload) */
  /* (not used for comment) */
  cal_mime_type VARCHAR(50) NULL,
  /* type of object: C=Comment, A=Attachment */
  cal_type CHAR(1) NOT NULL,
  /* date added (in YYYYMMDD format) */
  cal_mod_date INT NOT NULL,
  /* time added in HHMMSS format */
  cal_mod_time INT NOT NULL,
  /* binary data for object */
  cal_blob LONGBLOB,
  PRIMARY KEY ( cal_blob_id )
);
CREATE TABLE webcal_timezones (
  /* Unique name of timezone, try to use Olsen naming conventions */
  tzid varchar(100) NOT NULL default '',
  /* earliest date this timezone represents YYYYMMDDTHHMMSSZ format */
  dtstart varchar(25) default NULL,
  /* last date this timezone represents YYYYMMDDTHHMMSSZ format */
  dtend varchar(25) default NULL,
  /* Complete VTIMEZONE text gleaned from imported ics files */
  vtimezone text,
  PRIMARY KEY  ( tzid )
);
