# WebCalendar Database Documentation

**Home Page:** [https://k5n.us/webcalendar](https://k5n.us/webcalendar)  
**Author:** [Craig Knudsen](https://k5n.us)  
**Version:** v1.9.13

> This file is generated from [tables-mysql.sql](../wizard/shared/tables-mysql.sql).  
> Below are the definitions of all WebCalendar tables, along with some descriptions of
> how each table is used. Column names shown in **bold** are primary keys for that table.

> If you update the SQL for WebCalendar, use the [sql2md.py](sql2md.py) script to regenerate this file.

## List of Tables

- [webcal_access_function](#webcal_access_function)
- [webcal_access_user](#webcal_access_user)
- [webcal_asst](#webcal_asst)
- [webcal_blob](#webcal_blob)
- [webcal_categories](#webcal_categories)
- [webcal_config](#webcal_config)
- [webcal_entry](#webcal_entry)
- [webcal_entry_categories](#webcal_entry_categories)
- [webcal_entry_ext_user](#webcal_entry_ext_user)
- [webcal_entry_log](#webcal_entry_log)
- [webcal_entry_repeats](#webcal_entry_repeats)
- [webcal_entry_repeats_not](#webcal_entry_repeats_not)
- [webcal_entry_user](#webcal_entry_user)
- [webcal_group](#webcal_group)
- [webcal_group_user](#webcal_group_user)
- [webcal_import](#webcal_import)
- [webcal_import_data](#webcal_import_data)
- [webcal_nonuser_cals](#webcal_nonuser_cals)
- [webcal_reminders](#webcal_reminders)
- [webcal_report](#webcal_report)
- [webcal_report_template](#webcal_report_template)
- [webcal_site_extras](#webcal_site_extras)
- [webcal_timezones](#webcal_timezones)
- [webcal_user](#webcal_user)
- [webcal_user_layers](#webcal_user_layers)
- [webcal_user_pref](#webcal_user_pref)
- [webcal_user_template](#webcal_user_template)
- [webcal_view](#webcal_view)
- [webcal_view_user](#webcal_view_user)

---

### webcal_access_function
> Specifies what WebCalendar functions a user can access. Each function has a corresponding numeric value (specified in the file includes/access.php). For example, view event is 0, so the very first character in the cal_permissions column is either a "Y" if this user can view events or a "N" if they cannot.

| Column Name | Type | Length | Null | Default | Description |
|-------------|------|--------|------|---------|-------------|
| **cal_login** | VARCHAR | 25 | N |   | user login |
| cal_permissions | VARCHAR | 64 | N |   | a string of 'Y' or 'N' for the various functions |


### webcal_access_user
> Specifies which users can access another user's calendar.

| Column Name | Type | Length | Null | Default | Description |
|-------------|------|--------|------|---------|-------------|
| **cal_login** | VARCHAR | 25 | N |   | the current user who is attempting to look at another user's calendar |
| **cal_other_user** | VARCHAR | 25 | N |   | the login of the other user whose calendar the current user wants to access |
| cal_can_view | INT |   | N | '0' | can current user view events on the other user's calendar? |
| cal_can_edit | INT |   | N | '0' | can current user edit events on the other user's calendar? |
| cal_can_approve | INT |   | N | '0' | can current user approve events on the other user's calendar? |
| cal_can_invite | CHAR | 1 | Y | 'Y' | can current user see other user in Participant lists? |
| cal_can_email | CHAR | 1 | Y | 'Y' | can current user send emails to other user? |
| cal_see_time_only | CHAR | 1 | Y | 'N' | can current user can only see time of other user? |


### webcal_asst
> Define assistant/boss relationship.

| Column Name | Type | Length | Null | Default | Description |
|-------------|------|--------|------|---------|-------------|
| **cal_boss** | VARCHAR | 25 | N |   | user login of boss |
| **cal_assistant** | VARCHAR | 25 | N |   | user login of assistant |


### webcal_blob
> This table stores event attachments and comments.

| Column Name | Type | Length | Null | Default | Description |
|-------------|------|--------|------|---------|-------------|
| **cal_blob_id** | INT |   | N |   | Unique identifier for this object |
| cal_id | INT |   | Y |   | event id (if applicable) |
| cal_login | VARCHAR | 25 | Y |   | login of user who created |
| cal_name | VARCHAR | 30 | Y |   | filename of object (not used for comments) |
| cal_description | VARCHAR | 128 | Y |   | description of what the object is (subject for comment) |
| cal_size | INT |   | Y |   | size of object (not used for comment) |
| cal_mime_type | VARCHAR | 50 | Y |   | MIME type of object (as specified by browser during upload) (not used for comment) |
| cal_type | CHAR | 1 | N |   | type of object: C=Comment, A=Attachment |
| cal_mod_date | INT |   | N |   | date added (in YYYYMMDD format) |
| cal_mod_time | INT |   | N |   | time added in HHMMSS format |
| cal_blob | LONGBLOB |   | Y |   | binary data for object |


### webcal_categories
> Defines user categories. Categories can be specific to a user or global. When a category is global, the cat_owner field will be an empty string. (Only an admin user can create a global category.)

| Column Name | Type | Length | Null | Default | Description |
|-------------|------|--------|------|---------|-------------|
| **cat_id** | INT |   | N |   | unique category id |
| **cat_owner** | VARCHAR | 25 | N | '' | user login of category owner. If this is empty, then it is a global category |
| cat_name | VARCHAR | 80 | N |   | category name |
| cat_color | VARCHAR | 8 | Y |   | RGB color for category |
| cat_status | CHAR |   | Y | 'A' | Status of the category (A = Active, I = Inactive, D = Deleted) |
| cat_icon_mime | VARCHAR | 32 | Y | NULL | category icon mime type (e.g. "image/png") |
| cat_icon_blob | LONGBLOB |   | Y | NULL | category icon image blob |


### webcal_config
> System settings (set by the admin interface in admin.php)

| Column Name | Type | Length | Null | Default | Description |
|-------------|------|--------|------|---------|-------------|
| **cal_setting** | VARCHAR | 50 | N |   | setting name |
| cal_value | VARCHAR | 100 | Y |   | setting value |


### webcal_entry
> Defines a calendar event. Each event in the system has one entry in this table unless the event starts before midnight and ends after midnight. In that case a secondary event will be created with cal_ext_for_id set to the cal_id of the original entry. The following tables contain additional information about each event: - [webcal_entry_user](#webcal_entry_user) - lists participants in the event and specifies the status (accepted, rejected) and category of each participant. - [webcal_entry_repeats](#webcal_entry_repeats) - contains information if the event repeats. - [webcal_entry_repeats_not](#webcal_entry_repeats_not) - specifies which dates the repeating event does not repeat (because they were deleted or modified for just that date by the user) - [webcal_entry_log](#webcal_entry_log) - provides a history of changes to this event. - [webcal_site_extras](#webcal_site_extras) - stores event data as defined in site_extras.php (such as reminders and other custom event fields).

| Column Name | Type | Length | Null | Default | Description |
|-------------|------|--------|------|---------|-------------|
| **cal_id** | INT |   | N |   | cal_id is unique integer id for event |
| cal_group_id | INT |   | Y |   | cal_group_id: the parent event id if this event is overriding an occurrence of a repeating event |
| cal_ext_for_id | INT |   | Y |   | used when an event goes past midnight into the next day, in which case an additional entry in this table will use this field to indicate the original event cal_id |
| cal_create_by | VARCHAR | 25 | N |   | user login of user that created the event |
| cal_date | INT |   | N |   | date of event (in YYYYMMDD format) |
| cal_time | INT |   | Y |   | event time (in HHMMSS format) |
| cal_mod_date | INT |   | Y |   | date the event was last modified (in YYYYMMDD format) |
| cal_mod_time | INT |   | Y |   | time the event was last modified (in HHMMSS format) |
| cal_duration | INT |   | N |   | duration of event in minutes |
| cal_due_date | INT |   | Y | NULL | Task due date |
| cal_due_time | INT |   | Y | NULL | Task due time |
| cal_priority | INT |   | Y | 5 | event priority: 1=High, 5=Med, 9=Low |
| cal_type | CHAR | 1 | Y | 'E' | 'E' = Event, 'M' = Repeating event, 'T' = Task |
| cal_access | CHAR | 1 | Y | 'P' | 'P' = Public, 'R' = Private (others cannot see the event), 'C' = Confidential (others can see time allocated but not what it is) |
| cal_name | VARCHAR | 80 | N |   | brief description of event |
| cal_location | VARCHAR | 100 | Y | NULL | location of event |
| cal_url | VARCHAR | 255 | Y | NULL | URL of event |
| cal_completed | INT |   | Y | NULL | date task completed |
| cal_description | TEXT |   | Y |   | full description of event |


### webcal_entry_categories
> Contains category foreign keys to enable multiple categories for each event or task

| Column Name | Type | Length | Null | Default | Description |
|-------------|------|--------|------|---------|-------------|
| **cal_id** | INT |   | N | 0 | id of event. Not unique |
| **cat_id** | INT |   | N | 0 | id of category. Not unique |
| **cat_order** | INT |   | N | 0 | order that user requests their categories appear. Globals are always last |
| **cat_owner** | VARCHAR | 25 | N | '' | user that owns this record. Global categories will be empty string |


### webcal_entry_ext_user
> This table associates one or more external users (people who do not have a WebCalendar login) with an event by the event id. An event must still have at least one WebCalendar user associated with it. This table is not used unless external users are enabled* in system settings. The event can be found in [webcal_entry](#webcal_entry).

| Column Name | Type | Length | Null | Default | Description |
|-------------|------|--------|------|---------|-------------|
| **cal_id** | INT |   | N | 0 | event id |
| **cal_fullname** | VARCHAR | 50 | N |   | external user fill name |
| cal_email | VARCHAR | 75 | Y |   | external user email (for sending a reminder) |


### webcal_entry_log
> Activity log for an event.

| Column Name | Type | Length | Null | Default | Description |
|-------------|------|--------|------|---------|-------------|
| **cal_log_id** | INT |   | N |   | unique id of this log entry |
| cal_entry_id | INT |   | N |   | event id |
| cal_login | VARCHAR | 25 | N |   | user who performed this action |
| cal_user_cal | VARCHAR | 25 | Y |   | user of calendar affected |
| cal_type | CHAR | 1 | N |   | log types: C: Created, A: Approved/Confirmed by user, R: Rejected by user, U: Updated by user, M: Mail Notification sent, E: Reminder sent |
| cal_date | INT |   | N |   | date in YYYYMMDD format |
| cal_time | INT |   | Y |   | time in HHMMSS format |
| cal_text | TEXT |   | Y |   | optional text |


### webcal_entry_repeats
> Defines repeating info about an event. The event is defined in [webcal_entry](#webcal_entry).

| Column Name | Type | Length | Null | Default | Description |
|-------------|------|--------|------|---------|-------------|
| **cal_id** | INT |   | N | 0 | event id |
| cal_type | VARCHAR | 20 | Y |   | type of repeating: daily - repeats daily, monthlyByDate - repeats on same day of the month, monthlyBySetPos - repeats based on position within other ByXXX values, monthlyByDay - repeats on specified weekday (2nd Monday, for example), weekly - repeats every week, yearly - repeats on same date every year |
| cal_end | INT |   | Y |   | end date for repeating event (in YYYYMMDD format) |
| cal_endtime | INT |   | Y | NULL |   |
| cal_frequency | INT |   | Y | 1 | frequency of repeat: 1 = every, 2 = every other, 3 = every 3rd, etc. |
| cal_days | CHAR | 7 | Y |   | NO LONGER USED. We'll leave it in for now |
| cal_bymonth | VARCHAR | 50 | Y | NULL | the following columns are values as specified in RFC2445 |
| cal_bymonthday | VARCHAR | 100 | Y | NULL |   |
| cal_byday | VARCHAR | 100 | Y | NULL |   |
| cal_bysetpos | VARCHAR | 50 | Y | NULL |   |
| cal_byweekno | VARCHAR | 50 | Y | NULL |   |
| cal_byyearday | VARCHAR | 50 | Y | NULL |   |
| cal_wkst | CHAR | 2 | Y | 'MO' |   |
| cal_count | INT |   | Y | NULL |   |


### webcal_entry_repeats_not
> This table specifies which dates in a repeating event have either been deleted, included, or replaced with a replacement event for that day. When replaced, the cal_group_id (I know... not the best name, but it was not being used) column will be set to the original event. That way the user can delete the original event and (at the same time) delete any exception events.

| Column Name | Type | Length | Null | Default | Description |
|-------------|------|--------|------|---------|-------------|
| **cal_id** | INT |   | N |   | event id of repeating event |
| **cal_date** | INT |   | N |   | cal_date: date event should not repeat (in YYYYMMDD format) |
| cal_exdate | INT | 1 | N | 1 | indicates whether this record is an exclusion (1) or inclusion (0) |


### webcal_entry_user
> This table associates one or more users with an event by the event id. The event can be found in [webcal_entry](#webcal_entry).

| Column Name | Type | Length | Null | Default | Description |
|-------------|------|--------|------|---------|-------------|
| **cal_id** | INT |   | N | 0 | event id |
| **cal_login** | VARCHAR | 25 | N |   | participant in the event |
| cal_status | CHAR | 1 | Y | 'A' | status of event for this user: A=Accepted, C=Completed, D=Deleted, P=In-Progress, R=Rejected/Declined, W=Waiting |
| cal_category | INT |   | Y | NULL | category of the event for this user |
| cal_percent | INT |   | N | 0 | Task percentage of completion for this user's task |


### webcal_group
> Define a group. Group members can be found in [webcal_group_user](#webcal_group_user).

| Column Name | Type | Length | Null | Default | Description |
|-------------|------|--------|------|---------|-------------|
| **cal_group_id** | INT |   | N |   | unique group id |
| cal_owner | VARCHAR | 25 | Y |   | user login of user that created this group |
| cal_name | VARCHAR | 50 | N |   | name of the group |
| cal_last_update | INT |   | N |   | date last updated (in YYYYMMDD format) |


### webcal_group_user
> Specify users in a group. The group is defined in [webcal_group](#webcal_group).

| Column Name | Type | Length | Null | Default | Description |
|-------------|------|--------|------|---------|-------------|
| **cal_group_id** | INT |   | N |   | group id |
| **cal_login** | VARCHAR | 25 | N |   | user login |


### webcal_import
> Used to track import data (one row per import)

| Column Name | Type | Length | Null | Default | Description |
|-------------|------|--------|------|---------|-------------|
| **cal_import_id** | INT |   | N |   | unique id for import |
| cal_name | VARCHAR | 50 | Y |   | name of import (optional) |
| cal_date | INT |   | N |   | date of import (YYYYMMDD format) |
| cal_check_date | INT |   | Y |   | date of last check to see if remote calendar updated (YYYYMMDD format) |
| cal_type | VARCHAR | 10 | N |   | type of import (ical, vcal, palm, outlookcsv) |
| cal_login | VARCHAR | 25 | Y |   | user who performed the import |
| cal_md5 | VARCHAR | 32 | Y | NULL | md5 of last import used to see if a new import changes anything |


### webcal_import_data
> Used to track import data (one row per event)

| Column Name | Type | Length | Null | Default | Description |
|-------------|------|--------|------|---------|-------------|
| cal_import_id | INT |   | N |   | import id (from webcal_import table) |
| **cal_id** | INT |   | N |   | event id in WebCalendar |
| **cal_login** | VARCHAR | 25 | N |   | user login |
| cal_import_type | VARCHAR | 15 | N |   | type of import: 'palm', 'vcal', 'ical' or 'outlookcsv' |
| cal_external_id | VARCHAR | 200 | Y |   | external id used in external calendar system (for example, UID in iCal) |


### webcal_nonuser_cals
> Defines non-user calendars.

| Column Name | Type | Length | Null | Default | Description |
|-------------|------|--------|------|---------|-------------|
| **cal_login** | VARCHAR | 25 | N |   | the unique id for the calendar |
| cal_lastname | VARCHAR | 25 | Y |   | calendar's last name |
| cal_firstname | VARCHAR | 25 | Y |   | calendar's first name |
| cal_admin | VARCHAR | 25 | N |   | who is the calendar administrator |
| cal_is_public | CHAR | 1 | N | 'N' | can this nonuser calendar be a public calendar (no login required) |
| cal_url | VARCHAR | 255 | Y | NULL | url of the remote calendar |


### webcal_reminders
> Stores information about reminders

| Column Name | Type | Length | Null | Default | Description |
|-------------|------|--------|------|---------|-------------|
| **cal_id** | INT |   | N | '0' |   |
| cal_date | INT |   | N | '0' | timestamp that specifies send datetime. Use this or cal_offset, but not both |
| cal_offset | INT |   | N | '0' | offset in minutes from the selected edge |
| cal_related | CHAR | 1 | N | 'S' | S=Start, E=End. Specifies which edge of entry this reminder applies to |
| cal_before | CHAR | 1 | N | 'Y' | specifies whether reminder is sent before or after selected edge |
| cal_last_sent | INT |   | N | '0' | timestamp of last sent reminder |
| cal_repeats | INT |   | N | '0' | number of times to repeat in addition to original occurrence |
| cal_duration | INT |   | N | '0' | time in ISO 8601 format that specifies time between repeated reminders |
| cal_times_sent | INT |   | N | '0' | number of times this reminder has been sent |
| cal_action | VARCHAR | 12 | N | 'EMAIL' | action as imported, may be used in the future |


### webcal_report
> Defines a custom report created by a user.

| Column Name | Type | Length | Null | Default | Description |
|-------------|------|--------|------|---------|-------------|
| cal_login | VARCHAR | 25 | N |   | creator of report |
| **cal_report_id** | INT |   | N |   | unique id of this report |
| cal_is_global | CHAR | 1 | N | 'N' | is this a global report (can it be accessed by other users) ('Y' or 'N') |
| cal_report_type | VARCHAR | 20 | N |   | format of report (html, plain or csv) |
| cal_include_header | CHAR | 1 | N | 'Y' | if cal_report_type is 'html', should the DEFAULT HTML header and trailer be included? ('Y' or 'N') |
| cal_report_name | VARCHAR | 50 | N |   | name of the report |
| cal_time_range | INT |   | N |   | time range for report: 0 = tomorrow, 1 = today, 2 = yesterday, 3 = day before yesterday, 10 = next week, 11 = current week, 12 = last week, 13 = week before last, 20 = next week and week after, 21 = current week and next week, 22 = last week and this week, 23 = last two weeks, 30 = next month, 31 = current month, 32 = last month, 33 = month before last, 40 = next year, 41 = current year, 42 = last year, 43 = year before last |
| cal_user | VARCHAR | 25 | Y |   | user calendar to display (NULL indicates current user) |
| cal_allow_nav | CHAR | 1 | Y | 'Y' | allow user to navigate to different dates with next/previous ('Y' or 'N') |
| cal_cat_id | INT |   | Y |   | category to filter on (optional) |
| cal_include_empty | CHAR | 1 | Y | 'N' | include empty dates in report ('Y' or 'N') |
| cal_show_in_trailer | CHAR | 1 | Y | 'N' | include a link for this report in the "Go to" section of the navigation in the page trailer ('Y' or 'N') |
| cal_update_date | INT |   | N |   | date created or last updated (in YYYYMMDD format) |


### webcal_report_template
> Defines one of the templates used for a report. Each report has three templates: - Page template - Defines the entire page (except for header and footer). The following variables can be defined: ${days}* - the HTML of all dates (generated from the Date template) - Date template - Defines events for one day. If the report is for a week or month, then the results of each day will be concatenated and used as the ${days} variable in the Page template. The following variables can be defined: ${events}* - the HTML of all events for the data (generated from the Event template) - ${date} - the date - ${fulldate} - date (includes weekday) - Event template - Defines a single event. The following variables can be defined: ${name}* - Brief Description of event - ${description} - Full Description of event - ${date} - Date of event - ${fulldate} - Date of event (includes weekday) - ${time} - Time of event (4:00pm - 4:30pm) - ${starttime} - Start time of event - ${endtime} - End time of event - ${duration} - Duration of event (in minutes) - ${priority} - Priority of event - ${href} - URL to view event details * denotes a required template variable

| Column Name | Type | Length | Null | Default | Description |
|-------------|------|--------|------|---------|-------------|
| **cal_report_id** | INT |   | N |   | report id (in webcal_report table) |
| **cal_template_type** | CHAR | 1 | N |   | type of template: 'P': page template represents entire document, 'D': date template represents a single day of events, 'E': event template represents a single event |
| cal_template_text | TEXT |   | Y |   | text of template |


### webcal_site_extras
> This table holds data for site extra fields (customized in site_extra.php).

| Column Name | Type | Length | Null | Default | Description |
|-------------|------|--------|------|---------|-------------|
| **cal_id** | INT |   | N | 0 | event id |
| **cal_name** | VARCHAR | 25 | N |   | the brief name of this type (first field in $site_extra array) |
| cal_type | INT |   | N |   | EXTRA_URL, EXTRA_DATE, etc. |
| cal_date | INT |   | Y | 0 | only used for EXTRA_DATE type fields (in YYYYMMDD format) |
| cal_remind | INT |   | Y | 0 | how many minutes before event should a reminder be sent |
| cal_data | TEXT |   | Y |   | used to store text data |


### webcal_timezones
> This table stores timezones of the world.

| Column Name | Type | Length | Null | Default | Description |
|-------------|------|--------|------|---------|-------------|
| **tzid** | VARCHAR | 100 | N | '' | Unique name of timezone, try to use Olsen naming conventions |
| dtstart | VARCHAR | 25 | Y | NULL | earliest date this timezone represents YYYYMMDDTHHMMSSZ format |
| dtend | VARCHAR | 25 | Y | NULL | last date this timezone represents YYYYMMDDTHHMMSSZ format |
| vtimezone | TEXT |   | Y |   | Complete VTIMEZONE text gleaned from imported ics files |


### webcal_user
> Defines a WebCalendar user.

| Column Name | Type | Length | Null | Default | Description |
|-------------|------|--------|------|---------|-------------|
| **cal_login** | VARCHAR | 25 | N |   | the unique user login |
| cal_passwd | VARCHAR | 255 | Y |   | the user's password. (not used for http) |
| cal_lastname | VARCHAR | 25 | Y |   | user's last name |
| cal_firstname | VARCHAR | 25 | Y |   | user's first name |
| cal_is_admin | CHAR | 1 | Y | 'N' | is the user a WebCalendar administrator ('Y' = yes, 'N' = no) |
| cal_email | VARCHAR | 75 | Y |   | user's email address |
| cal_enabled | CHAR | 1 | Y | 'Y' | allow admin to disable account ('Y' = yes, 'N' = no) |
| cal_telephone | VARCHAR | 50 | Y |   | user's telephone |
| cal_address | VARCHAR | 75 | Y |   | user's address |
| cal_title | VARCHAR | 75 | Y |   | user's title |
| cal_birthday | INT |   | Y |   | user's birthday |
| cal_last_login | INT |   | Y |   | user's last log in date |
| cal_api_token | VARCHAR | 255 | Y |   | user's API token for MCP server |


### webcal_user_layers
> Define layers for a user.

| Column Name | Type | Length | Null | Default | Description |
|-------------|------|--------|------|---------|-------------|
| cal_layerid | INT |   | N | 0 | unique layer id |
| **cal_login** | VARCHAR | 25 | N |   | login of owner of this layer |
| **cal_layeruser** | VARCHAR | 25 | N |   | login name of user that this layer represents |
| cal_color | VARCHAR | 25 | Y |   | color to display this layer in |
| cal_dups | CHAR | 1 | Y | 'N' | show duplicates ('N' or 'Y') |


### webcal_user_pref
> Specify preferences for a user. Most preferences are set via pref.php. Values in this table are loaded after system settings found in [webcal_config](#webcal_config).

| Column Name | Type | Length | Null | Default | Description |
|-------------|------|--------|------|---------|-------------|
| **cal_login** | VARCHAR | 25 | N |   | user login |
| **cal_setting** | VARCHAR | 25 | N |   | setting name |
| cal_value | VARCHAR | 100 | Y |   | setting value |


### webcal_user_template
> This table stores the custom header/stylesheet/trailer. If configured properly, each user (or nonuser cal) can have their own custom header/trailer.

| Column Name | Type | Length | Null | Default | Description |
|-------------|------|--------|------|---------|-------------|
| **cal_login** | VARCHAR | 25 | N |   | user login (or nonuser cal name), the DEFAULT for all users is stored with the username '__system__' |
| **cal_type** | CHAR | 1 | N |   | type ('H' = header, 'S' = stylesheet/script, 'T' = trailer) |
| cal_template_text | TEXT |   | Y |   | text of template |


### webcal_view
> A "view" allows a user to put the calendars of multiple users all on one page. A "view" is valid only for the owner (cal_owner) of the view. Users for the view are in [webcal_view_user](#webcal_view_user).

| Column Name | Type | Length | Null | Default | Description |
|-------------|------|--------|------|---------|-------------|
| **cal_view_id** | INT |   | N |   | unique view id |
| cal_owner | VARCHAR | 25 | N |   | login name of owner of this view |
| cal_name | VARCHAR | 50 | N |   | name of view |
| cal_view_type | CHAR | 1 | Y |   | "W" for week view, "D" for day view, "M" for month view |
| cal_is_global | CHAR | 1 | N | 'N' | is this a global view (can it be accessed by other users) ('Y' or 'N') |


### webcal_view_user
> Specify users in a view. See [webcal_view](#webcal_view).

| Column Name | Type | Length | Null | Default | Description |
|-------------|------|--------|------|---------|-------------|
| **cal_view_id** | INT |   | N |   | view id |
| **cal_login** | VARCHAR | 25 | N |   | a user in the view |

