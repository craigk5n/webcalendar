# $Id$
#
# Description:
# This file is used to create all tables used by WebCalendar
# and initialize some of those tables with the required data.
#
# The stream comments and COMMENT field definitions are parsed with
# "/docs/sql2html.pl" to generate an HTML document that describes these tables.
#
# NOTE: Except for this header section and the stream comments,
#       this is the output from PHPMyAdmin Export. COMMENTs and all.
#
# NOTE2: MySQL field COMMENT has a 255 character limit.
#        So, the 2 "/*(phrase)*/" in the field definitions
#        are the overflow from the preceeding lines' COMMENT.
/**
 * Specifies what WebCalendar functions a user can access.
 * Each function has a corresponding numeric value (specified in the file
 * includes/access.php). For example, view event is 0, so the very first character
 * in the cal_permissions column is either a "Y" if this user can view events
 * or an "N" if they cannot.
 */
CREATE TABLE webcal_access_function (
  cal_login varchar(25) NOT NULL COMMENT 'user login',
  cal_permissions varchar(64) NOT NULL COMMENT 'a string of Y or N for the various functions',
  PRIMARY KEY (cal_login)
) DEFAULT CHARSET=utf8;
/**
 * Specifies which users can access another user's calendar.
 */
CREATE TABLE webcal_access_user (
  cal_login varchar(25) NOT NULL COMMENT 'the current user who is attempting to look at another user''s calendar',
  cal_other_user varchar(25) NOT NULL COMMENT 'the login of the other user whose calendar the current user wants to access',
  cal_can_approve int(3) unsigned NOT NULL COMMENT 'can current user approve events on the other user''s calendar?',
  cal_can_edit int(3) unsigned NOT NULL COMMENT 'can current user edit events on the other user''s calendar?',
  cal_can_view int(3) unsigned NOT NULL COMMENT 'can current user view events on the other user''s calendar?',
  cal_can_email char(1) DEFAULT 'Y' COMMENT 'can current user send emails to other user?',
  cal_can_invite char(1) DEFAULT 'Y' COMMENT 'can current user see other user in Participant lists?',
  cal_see_time_only char(1) DEFAULT 'N' COMMENT 'can current user only see time of other user?',
  PRIMARY KEY (cal_login,cal_other_user)
) DEFAULT CHARSET=utf8;
/**
 * Define assitant/boss relationship.
 */
CREATE TABLE webcal_asst (
  cal_boss varchar(25) NOT NULL COMMENT 'user login of boss',
  cal_assistant varchar(25) NOT NULL COMMENT 'user login of assistant',
  PRIMARY KEY (cal_boss,cal_assistant)
) DEFAULT CHARSET=utf8;
/**
 * This table stores event attachments and comments.
 */
CREATE TABLE webcal_blob (
  cal_blob_id int(10) unsigned NOT NULL COMMENT 'Unique identifier for this object',
  cal_id int(10) unsigned DEFAULT NULL COMMENT 'event id (if applicable)',
  cal_login varchar(25) DEFAULT NULL COMMENT 'login of creator',
  cal_description varchar(128) DEFAULT NULL COMMENT 'description of what the object is (subject for comment)',
  cal_mime_type varchar(50) DEFAULT NULL COMMENT 'MIME type of object (as specified by browser during upload) (not used for comment)',
  cal_mod_date int(8) NOT NULL COMMENT 'date added (in YYYYMMDD format)',
  cal_mod_time int(6) unsigned NOT NULL COMMENT 'time added in HHMMSS format',
  cal_name varchar(30) DEFAULT NULL COMMENT 'filename of object (not used for comments)',
  cal_size int(10) unsigned DEFAULT NULL COMMENT 'size of object (not used for comment)',
  cal_type char(1) NOT NULL DEFAULT 'C' COMMENT 'type of object: C=Comment, A=Attachment',
  cal_blob longblob COMMENT 'binary data for object',
  PRIMARY KEY (cal_blob_id)
) DEFAULT CHARSET=utf8;
/**
 * Defines user categories. Categories can be specific to a user or global.
 * When a category is global, the cat_owner field will be NULL.
 * (Only admin users can create a global category.)
 */
CREATE TABLE webcal_categories (
  cat_id int(10) unsigned NOT NULL COMMENT 'unique category id',
  cat_color varchar(8) DEFAULT NULL COMMENT 'RGB color for category',
  cat_name varchar(80) NOT NULL COMMENT 'category name',
  cat_owner varchar(25) DEFAULT NULL COMMENT 'user login of category owner. If this is NULL, then it is a global category',
  PRIMARY KEY (cat_id)
) DEFAULT CHARSET=utf8;
/**
 * System settings (set by the admin interface in admin.php)
 */
CREATE TABLE webcal_config (
  cal_setting varchar(50) NOT NULL COMMENT 'setting name',
  cal_value varchar(100) DEFAULT NULL COMMENT 'setting value',
  PRIMARY KEY (cal_setting)
) DEFAULT CHARSET=utf8;
/**
 * Defines a calendar event. Each event in the system has one entry in this table
 * unless the event starts before midnight and ends after midnight. In that case
 * a secondary event will be created with cal_ext_for_id set to the cal_id of the
 * original entry. The following tables contain additional information about each event:
 * <ul>
 * <li><a href="#webcal_entry_user">webcal_entry_user</a>
 *   - lists participants in the event and specifies the status
 *     (accepted, rejected) and category of each participant.</li>
 * <li><a href="#webcal_entry_repeats">webcal_entry_repeats</a>
 *   - contains information if the event repeats.</li>
 * <li><a href="#webcal_entry_repeats_not">webcal_entry_repeats_not</a>
 *   - specifies which dates the repeating event does not repeat
 *     (because they were deleted or modified for just that date by the user)</li>
 * <li><a href="#webcal_entry_log">webcal_entry_log</a>
 *   - provides a history of changes to this event.</li>
 * <li><a href="#webcal_site_extras">webcal_site_extras</a>
 *   - stores event data as defined in site_extras.php (such as
 *     reminders and other custom event fields).</li>
 * </ul>
 */
CREATE TABLE webcal_entry (
  cal_id int(10) unsigned NOT NULL COMMENT 'unique integer id for event',
  cal_access char(1) DEFAULT 'P' COMMENT 'P=Public, R=pRivate (others cannot see the event), C=Confidential (others can see time allocated but not what it is)',
  cal_completed int(8) DEFAULT NULL COMMENT 'date task completed',
  cal_create_by varchar(25) NOT NULL COMMENT 'user login of event creator',
  cal_date int(8) NOT NULL COMMENT 'date of event (in YYYYMMDD format)',
  cal_due_date int(8) DEFAULT NULL COMMENT 'Task due date',
  cal_due_time int(6) unsigned DEFAULT NULL COMMENT 'Task due time',
  cal_duration int(10) unsigned NOT NULL COMMENT 'duration of event in minutes',
  cal_ext_for_id int(10) unsigned DEFAULT NULL COMMENT 'used when an event goes past midnight into the next day, in which case an additional entry in this table will use this field to indicate the original event cal_id',
  cal_group_id int(10) unsigned DEFAULT NULL COMMENT 'the parent event id if this event is overriding an occurrence of a repeating event',
  cal_location varchar(100) DEFAULT NULL COMMENT 'location of event',
  cal_mod_date int(8) unsigned DEFAULT NULL COMMENT 'date the event was last modified (in YYYYMMDD format)',
  cal_mod_time int(6) unsigned DEFAULT NULL COMMENT 'time the event was last modified (in HHMMSS format)',
  cal_name varchar(80) NOT NULL COMMENT 'brief description of event',
  cal_priority char(1) DEFAULT '5' COMMENT 'event priority: 1=Highest, 9=Lowest',
  cal_time int(8) unsigned DEFAULT NULL COMMENT 'event time (in HHMMSS format)',
  cal_type char(1) DEFAULT 'E' COMMENT 'E=Event, M=Repeating event, T=Task',
  cal_url varchar(100) DEFAULT NULL COMMENT 'URL of event',  cal_description text COMMENT 'full description of event',
  PRIMARY KEY (cal_id)
) DEFAULT CHARSET=utf8;
/**
 * Contains category foreign keys to enable multiple categories for each event or task
 */
CREATE TABLE webcal_entry_categories (
  cal_id int(10) unsigned NOT NULL COMMENT 'id of event. Not unique',
  cat_id int(10) unsigned NOT NULL COMMENT 'id of category. Not unique',
  cat_order int(3) unsigned NOT NULL COMMENT 'order that user requests their categories appear. Globals are always last',
  cat_owner varchar(25) DEFAULT NULL COMMENT 'user that owns this record. Global categories will be NULL'
) DEFAULT CHARSET=utf8;
/**
 * This table associates one or more external users (people who do not have a
 * WebCalendar login) with an event by the event id. An event must still have at
 * least one WebCalendar user associated with it. This table is not used unless
 * external users are enabled in system settings.
 * The event can be found in <a href="#webcal_entry">webcal_entry</a>.
 */
CREATE TABLE webcal_entry_ext_user (
  cal_id int(10) unsigned NOT NULL COMMENT 'event id',
  cal_fullname varchar(50) NOT NULL COMMENT 'external user full name',
  cal_email varchar(75) DEFAULT NULL COMMENT 'external user email (for sending a reminder)',
  PRIMARY KEY (cal_id,cal_fullname)
) DEFAULT CHARSET=utf8;
/**
 * Activity log for an event.
 */
CREATE TABLE webcal_entry_log (
  cal_log_id int(10) unsigned NOT NULL COMMENT 'unique id of this log entry',
  cal_date int(8) unsigned NOT NULL COMMENT 'date in YYYYMMDD format',
  cal_entry_id int(10) unsigned NOT NULL COMMENT 'event id',
  cal_login varchar(25) NOT NULL COMMENT 'user who performed this action',
  cal_time int(6) unsigned DEFAULT NULL COMMENT 'time in HHMMSS format',
  cal_type char(1) NOT NULL DEFAULT 'C' COMMENT 'log types:<ul><li>Created</li><li>Approved/Confirmed by user</li><li>Rejected by user</li><li>Updated by user</li><li>Mail Notification sent</li><li>rEminder sent</li></ul>',
  cal_user_cal varchar(25) DEFAULT NULL COMMENT 'user of calendar affected',
  cal_text text COMMENT 'optional text',
  PRIMARY KEY (cal_log_id)
) DEFAULT CHARSET=utf8;
/**
 * Defines repeating info about an event.
 * The event is defined in <a href="#webcal_entry">webcal_entry</a>.
 */
CREATE TABLE webcal_entry_repeats (
  cal_id int(10) unsigned NOT NULL COMMENT 'event id',
  cal_end int(8) DEFAULT NULL COMMENT 'end date for repeating event (in YYYYMMDD format)',
  cal_endtime int(6) unsigned DEFAULT NULL COMMENT 'end time for repeating event',
  cal_frequency int(3) unsigned DEFAULT '1' COMMENT 'frequency of repeat: 1: every, 2: every other, 3: every 3rd, etc.',
  cal_type varchar(16) DEFAULT NULL COMMENT 'repeat type:<ul><li>daily - every day</li><li>monthlyByDate - repeat on same day of the month</li><li>monthlyBySetPos - repeat based on position within other ByXXX values</li>',
  /*<li>monthlyByDay - repeat on specified weekday<br>(2nd Monday, for example)</li><li>weekly - same day every week</li><li>yearly - same date every year</li></ul>*/
  cal_byday varchar(100) DEFAULT NULL COMMENT ' this and the following columns are values as specified in RFC2445',
  cal_bymonth varchar(50) DEFAULT NULL,
  cal_bymonthday varchar(100) DEFAULT NULL,
  cal_bysetpos varchar(50) DEFAULT NULL,
  cal_byweekno varchar(50) DEFAULT NULL,
  cal_byyearday varchar(50) DEFAULT NULL,
  cal_count int(10) unsigned DEFAULT NULL,
  cal_wkst char(2) DEFAULT 'MO',
  PRIMARY KEY (cal_id)
) DEFAULT CHARSET=utf8;
/**
 * This table specifies which dates in a repeating event have either been added,
 * deleted, or overwritten with a replacement event for that day. When replaced,
 * the webcal_entry.cal_group_id, (I know... not the best name, but it was not
 * being used) field will be set to the original event. That way the user can
 * delete the original event and (at the same time) delete any exception events.
 */
CREATE TABLE webcal_entry_repeats_not (
  cal_id int(10) unsigned NOT NULL COMMENT 'event id of repeating event',
  cal_date int(8) NOT NULL COMMENT 'date event should not repeat (in YYYYMMDD format)',
  cal_exdate char(1) NOT NULL DEFAULT '1' COMMENT 'indicates whether this record is an exclusion (1) or inclusion (0)',
  PRIMARY KEY (cal_id,cal_date)
) DEFAULT CHARSET=utf8;
/**
 * This table associates one or more users with an event by the event id.
 * The event can be found in <a href="#webcal_entry">webcal_entry</a>.
 */
CREATE TABLE webcal_entry_user (
  cal_id int(10) unsigned NOT NULL COMMENT 'event id',
  cal_login varchar(25) NOT NULL COMMENT 'participant in the event',
  cal_category int(10) unsigned DEFAULT NULL COMMENT 'category of the event for this user',
  cal_percent int(3) unsigned NOT NULL COMMENT 'Task percentage of completion for this user''s task',
  cal_status char(1) DEFAULT 'A' COMMENT 'status of event for this user:<ul><li>A=Accepted</li><li>C=Completed</li><li>D=Deleted</li><li>P=In-Progress</li><li>R=Rejected/Declined</li><li>W=Waiting</li></ul>',
  PRIMARY KEY (cal_id,cal_login)
) DEFAULT CHARSET=utf8;
/**
 * Define a group.
 * Group members can be found in <a href="#webcal_group_user">webcal_group_user</a>.
 */
CREATE TABLE webcal_group (
  cal_group_id int(10) unsigned NOT NULL COMMENT 'unique group id',
  cal_last_update int(8) NOT NULL COMMENT 'date last updated (in YYYYMMDD format)',
  cal_name varchar(50) NOT NULL COMMENT 'name of the group',
  cal_owner varchar(25) DEFAULT NULL COMMENT 'user login of user that created this group',
  PRIMARY KEY (cal_group_id)
) DEFAULT CHARSET=utf8;
/**
 * Specify users in a group.
 * The group is defined in <a href="#webcal_group">webcal_group</a>.
 */
CREATE TABLE webcal_group_user (
  cal_group_id int(10) unsigned NOT NULL COMMENT 'group id',
  cal_login varchar(25) NOT NULL COMMENT 'user login',
  PRIMARY KEY (cal_group_id,cal_login)
) DEFAULT CHARSET=utf8;
/**
 * Used to track import data (one row per import)
 */
CREATE TABLE webcal_import (
  cal_import_id int(10) unsigned NOT NULL COMMENT 'unique id for import',
  cal_date int(8) NOT NULL COMMENT 'date of import (YYYYMMDD format)',
  cal_login varchar(25) DEFAULT NULL COMMENT 'user who performed the import',
  cal_name varchar(50) DEFAULT NULL COMMENT 'name of import (optional)',
  cal_type varchar(10) NOT NULL COMMENT 'type of import (ical, vcal, palm, outlookcsv)',
  PRIMARY KEY (cal_import_id)
) DEFAULT CHARSET=utf8;
/**
 * Used to track import data (one row per event)
 */
CREATE TABLE webcal_import_data (
  cal_id int(10) unsigned NOT NULL COMMENT 'event id in WebCalendar',
  cal_login varchar(25) NOT NULL COMMENT 'user login',
  cal_external_id varchar(200) DEFAULT NULL COMMENT 'external id used in external calendar system (for example, UID in iCal)',
  cal_import_id int(10) unsigned NOT NULL COMMENT 'import id (from webcal_import table)',
  cal_import_type varchar(10) NOT NULL COMMENT 'type of import: ''palm'', ''vcal'', ''ical'' or ''outlookcsv''',
  PRIMARY KEY (cal_id,cal_login)
) DEFAULT CHARSET=utf8;
/**
 * Defines non-user calendars.
 */
CREATE TABLE webcal_nonuser_cals (
  cal_login varchar(25) NOT NULL COMMENT 'unique id for the calendar',
  cal_admin varchar(25) NOT NULL COMMENT 'who is the calendar administrator',
  cal_firstname varchar(25) DEFAULT NULL COMMENT 'calendar''s first name',
  cal_lastname varchar(25) DEFAULT NULL COMMENT 'calendar''s last name',
  cal_is_public char(1) NOT NULL DEFAULT 'N' COMMENT 'can this nonuser calendar be a public calendar (no login required)',
  cal_url varchar(255) DEFAULT NULL COMMENT 'url of the remote calendar',
  PRIMARY KEY (cal_login)
) DEFAULT CHARSET=utf8;
/**
 * Stores information about reminders
 */
CREATE TABLE webcal_reminders (
  cal_id int(10) unsigned NOT NULL,
  cal_action varchar(12) NOT NULL DEFAULT 'EMAIL' COMMENT 'action as imported, may be used in the future',
  cal_before char(1) NOT NULL DEFAULT 'Y' COMMENT 'specifies whether reminder is sent before or after selected edge',
  cal_date int(14) NOT NULL COMMENT 'timestamp that specifies send datetime. Use this or cal_offset, but not both',
  cal_duration int(11) NOT NULL COMMENT 'time in ISO 8601 format that specifies time between repeated reminders',
  cal_last_sent int(14) NOT NULL COMMENT 'timestamp of last sent reminder',
  cal_offset int(11) NOT NULL COMMENT 'offset in minutes from the selected edge',
  cal_related char(1) NOT NULL DEFAULT 'S' COMMENT 'S=Start, E=End. Specifies which edge of entry this reminder applies to',
  cal_repeats int(3) unsigned NOT NULL COMMENT 'number of times to repeat in addition to original occurance',
  cal_times_sent int(3) unsigned NOT NULL COMMENT 'number of times this reminder has been sent',
  PRIMARY KEY (cal_id)
) DEFAULT CHARSET=utf8;
/**
 * Defines a custom report created by a user.
 */
CREATE TABLE webcal_report (
  cal_report_id int(10) unsigned NOT NULL COMMENT 'unique id of this report',
  cal_allow_nav char(1) DEFAULT 'Y' COMMENT 'allow user to navigate to different dates with next/previous (Y or N)',
  cal_cat_id int(10) unsigned DEFAULT NULL COMMENT 'category to filter on (optional)',
  cal_include_empty char(1) DEFAULT 'N' COMMENT 'include empty dates in report (Y or N)',
  cal_include_header char(1) NOT NULL DEFAULT 'Y' COMMENT 'if cal_report_type is ''html'', should the DEFAULT HTML header and trailer be included? (Y or N)',
  cal_is_global char(1) NOT NULL DEFAULT 'N' COMMENT 'is this a global report (can it be accessed by other users) (Y or N)',
  cal_login varchar(25) NOT NULL COMMENT 'creator of report',
  cal_report_name varchar(50) NOT NULL COMMENT 'name of the report',
  cal_report_type varchar(20) NOT NULL COMMENT 'format of report (html, plain or csv)',
  cal_show_in_trailer char(1) DEFAULT 'N' COMMENT 'include a link for this report in the ''Go to'' section of the navigation in the page trailer (Y or N)',
  cal_time_range int(2) unsigned NOT NULL COMMENT 'report time range:<ul><li>0: tomorrow</li><li>1: today</li><li>2: yesterday</li><li>3: day before yesterday</li><li>10: next week</li><li>11: current week</li><li>12: last week</li><li>13: week before last</li><li>20: next week and week after</li>',
  /*<li>21: current week and next week</li><li>22: last week and this week</li><li>23: last two weeks</li><li>30: next month</li><li>31: current month</li><li>32: last month</li><li>33: month before last</li><li>40: next year</li><li>41: current year</li><li>42: last year</li><li>43: year before last</li></ul>*/
  cal_update_date int(8) NOT NULL COMMENT 'date created or last updated (in YYYYMMDD format)',
  cal_user varchar(25) DEFAULT NULL COMMENT 'user calendar to display (NULL indicates current user)',
  PRIMARY KEY (cal_report_id)
) DEFAULT CHARSET=utf8;
/**
 * Defines one of the templates used for a report.
 * Each report has three templates:
 * <ol>
 * <li>Page template - Defines the entire page (except for header and footer).
 * The following variables can be defined:
 * <ul>
 * <li>${days}<sup>*</sup> - the HTML of all dates (generated from the Date template)</li>
 * </ul></li>
 * <li>Date template - Defines events for one day. If the report 
 *   is for a week or month, then the results of each day will be
 *   concatenated and used as the ${days} variable in the Page template.
 *   The following variables can be defined:
 * <ul>
 * <li>${events}<sup>*</sup> - the HTML of all events for the data (generated from the Event template)</li>
 * <li>${date} - the date</li>
 * <li>${fulldate} - date (includes weekday)</li></ul></li>
 * <li>Event template - Defines a single event.
 *   The following variables can be defined:
 * <ul>
 * <li>${name}<sup>*</sup> - Brief Description of event</li>
 * <li>${description} - Full Description of event</li>
 * <li>${date} - Date of event</li>
 * <li>${fulldate} - Date of event (includes weekday)</li>
 * <li>${time} - Time of event (4:00pm - 4:30pm)</li>
 * <li>${starttime} - Start time of event</li>
 * <li>${endtime} - End time of event</li>
 * <li>${duration} - Duration of event (in minutes)</li>
 * <li>${priority} - Priority of event</li>
 * <li>${href} - URL to view event details</li>
 * </ul></li>
 * </ol>
 * <sup>*</sup> denotes a required template variable
 */
CREATE TABLE webcal_report_template (
  cal_report_id int(10) unsigned NOT NULL COMMENT 'report id (in webcal_report table)',
  cal_template_type char(1) NOT NULL COMMENT 'type of template:<ul><li>P: page template represents entire document</li><li>D: date template represents a single day of events</li><li>E: event template represents a single event</li></ul>',
  cal_template_text text COMMENT 'text of template',
  PRIMARY KEY (cal_report_id,cal_template_type)
) DEFAULT CHARSET=utf8;
/**
 * This table holds data for site extra fields (customized in site_extra.php).
 */
CREATE TABLE webcal_site_extras (
  cal_id int(10) unsigned NOT NULL COMMENT 'event id',
  cal_date int(8) DEFAULT NULL COMMENT 'only used for EXTRA_DATE type fields (in YYYYMMDD format)',
  cal_name varchar(25) NOT NULL COMMENT 'the brief name of this type (first field in $site_extra array)',
  cal_remind int(10) unsigned DEFAULT NULL COMMENT 'how many minutes before event should a reminder be sent',
  cal_type int(11) NOT NULL COMMENT 'EXTRA_URL, EXTRA_DATE, etc.',
  cal_data text COMMENT 'used to store text data'
) DEFAULT CHARSET=utf8;
/**
 * This table stores timezones of the world.
 */
CREATE TABLE webcal_timezones (
  tzid varchar(100) NOT NULL COMMENT 'Unique name of timezone, try to use Olsen naming conventions',
  dtstart varchar(16) DEFAULT NULL COMMENT 'earliest date this timezone represents YYYYMMDDTHHMMSSZ format',
  dtend varchar(16) DEFAULT NULL COMMENT 'last date this timezone represents YYYYMMDDTHHMMSSZ format',
  vtimezone text COMMENT 'Complete VTIMEZONE text gleaned from imported ics files',
  PRIMARY KEY (tzid)
) DEFAULT CHARSET=utf8;
/**
 * Defines a WebCalendar user.
 */
CREATE TABLE webcal_user (
  cal_login varchar(25) NOT NULL COMMENT 'the unique user login',
  cal_passwd varchar(32) DEFAULT NULL COMMENT 'the user''s password. (not used for http)',
  cal_enabled char(1) DEFAULT 'Y' COMMENT 'allow admin to disable account (Y or N)',
  cal_is_admin char(1) DEFAULT 'N' COMMENT 'is the user a WebCalendar administrator (Y or N)',
  cal_last_login int(8) DEFAULT NULL COMMENT 'user''s last log in date',
  cal_firstname varchar(25) DEFAULT NULL COMMENT 'this and following fields are user''s personal info',
  cal_lastname varchar(25) DEFAULT NULL,
  cal_address varchar(75) DEFAULT NULL,
  cal_birthday int(8) DEFAULT NULL,
  cal_email varchar(75) DEFAULT NULL,
  cal_telephone varchar(50) DEFAULT NULL,
  cal_title varchar(75) DEFAULT NULL COMMENT,
  PRIMARY KEY (cal_login)
) DEFAULT CHARSET=utf8;
# create a DEFAULT admin user
INSERT INTO webcal_user (cal_login, cal_passwd, cal_lastname, cal_firstname, cal_is_admin, cal_email, cal_enabled, cal_telephone, cal_address, cal_title, cal_birthday, cal_last_login)
  VALUES ('admin', '21232f297a57a5a743894a0e4a801fc3', 'Administrator', 'Default', 'Y', NULL, 'Y', NULL, NULL, NULL, NULL, NULL);
/**
 * Define layers for a user.
 */
CREATE TABLE webcal_user_layers (
  cal_login varchar(25) NOT NULL COMMENT 'login of owner of this layer',
  cal_layeruser varchar(25) NOT NULL COMMENT 'login name of user that this layer represents',
  cal_layerid int(10) unsigned UNIQUE COMMENT 'unique layer id',
  cal_color varchar(25) DEFAULT NULL COMMENT 'color to display this layer in',
  cal_dups char(1) DEFAULT 'N' COMMENT 'show duplicates (N or Y)',
  PRIMARY KEY (cal_login,cal_layeruser)
) DEFAULT CHARSET=utf8;
/**
 * Specify preferences for a user. Most preferences are set via pref.php.
 * Values in this table are loaded after system settings found in
 * <a href="#webcal_config">webcal_config</a>.
 */
CREATE TABLE webcal_user_pref (
  cal_login varchar(25) NOT NULL COMMENT 'user login',
  cal_setting varchar(25) NOT NULL COMMENT 'setting name',
  cal_value varchar(100) DEFAULT NULL COMMENT 'setting value',
  PRIMARY KEY (cal_login,cal_setting)
) DEFAULT CHARSET=utf8;
/**
 * This table stores the custom header/stylesheet/trailer. If configured properly,
 * each user (or nonuser cal) can have their own custom header/trailer.
 */
CREATE TABLE webcal_user_template (
  cal_login varchar(25) NOT NULL COMMENT 'user login (or nonuser cal name), the DEFAULT for all users is stored with the username ''__system__''',
  cal_type char(1) NOT NULL COMMENT 'type (H=header, S=stylesheet/script, T=trailer)',
  cal_template_text text COMMENT 'text of template',
  PRIMARY KEY (cal_login,cal_type)
) DEFAULT CHARSET=utf8;
/**
 * A "view" allows a user to put the calendars of multiple users all on one page.
 * A "view" is valid only for the owner (cal_owner) of the view.
 * Users for the view are in <a href="#webcal_view_user">webcal_view_user</a>.
 */
CREATE TABLE webcal_view (
  cal_view_id int(10) unsigned NOT NULL COMMENT 'unique view id',
  cal_is_global char(1) NOT NULL DEFAULT 'N' COMMENT 'is this a global view (can it be accessed by other users) (Y or N)',
  cal_name varchar(50) NOT NULL COMMENT 'name of view',
  cal_owner varchar(25) NOT NULL COMMENT 'view owner''s login',
  cal_view_type char(1) DEFAULT NULL COMMENT 'Day, Week or Month view',
  PRIMARY KEY (cal_view_id)
) DEFAULT CHARSET=utf8;
/**
 * Specify users in a view. See <a href="#webcal_view">webcal_view</a>.
 */
CREATE TABLE webcal_view_user (
  cal_view_id int(10) unsigned NOT NULL COMMENT 'view id',
  cal_login varchar(25) NOT NULL COMMENT 'a user in the view',
  PRIMARY KEY (cal_view_id,cal_login)
) DEFAULT CHARSET=utf8;
