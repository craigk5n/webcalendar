<?php

// Site-specific settings.
// Configure these for your site.
// You shouldn't have to modify anything outside of this file.
//
/////////////////////////////////////////////////////////////////

// Do not remove the following line (for security reasons)
unset ( $single_user_login );


$PROGRAM_NAME = "WebCalendar v0.9.34 (16 Dec 2001)";
$PROGRAM_URL = "http://webcalendar.sourceforge.net/";

// demo_mode is used on the sourceforge demo.  It disables changing the
// demo password and adds a note on what the demo login is on the login page.
$demo_mode = false;

// Database settings
// db_type should be one of: "mysql", "oracle", "postgresql", "odbc"

// MySQL example
$db_type = "mysql";
$db_host = "localhost";
$db_login = "webcalendar";
$db_password = "web!calendar";
$db_database = "intranet";

// Oracle example
//$db_type = "oracle";
//$db_host = ""; // use localhost
//$db_login = "webcalendar";
//$db_password = "webcal01";
// for oracle, db_database should be the name in tnsnames.ora
//$db_database = "orcl";

// Postgres example
//$db_type = "postgresql";
//$db_host = "localhost";
//$db_login = "webcalendar";
//$db_password = "webcalendar";
//$db_database = "webcalendar";

// ODBC example
//$db_type = "odbc";
//$db_host = ""; // not used for odbc
//$db_login = "webcalendar";
//$db_password = "webcal01";
// for oracle, db_database should be the name in tnsnames.ora
//$db_database = "webcalendar"; // this is the ODBC DSN


// Read-only mode: You can set this to true to create a read-only calendar.
// If you enable $single_user_login (below), no login will be required,
// making this a publicly viewable calendar.  In order to add events to
// a setup like this, you will need to setup another installation of this
// application that is not read-only.
// If $readonly is enabled in multi-user mode, only admin users will able
// to add/edit/delete events.
// NOTE: Approvals are not disabled in read-only.  You must also disable
// approvals if you don't want to use them.
$readonly = false;

// Are you setting this up as a multi-user system?
// You can always start as a single-user system and change to multi-user
// later.  To enable single-user mode, uncomment out the following line
// and set it to a login name (that you would use if you ever switched to
// multi-user).  In single-user mode, you will not be prompted for a login,
// nor will you be asked to select participants for events.
// NOTE: If you select single-user and then upgrade to multi-user later,
// you'll have to add in the login name you've set below to the cal_user
// table.  Set $single_user to either true or false.  If true, make sure
// $single_user_login is defined.
$single_user = false;
//$single_user_login = "cknudsen";

// Do you want to use web-based login or use HTTP authorization?
// NOTE: You can only use HTTP authorization if PHP is built as
// an Apache module.
// NOTE #2: There's no need to use this if you're running single
// user mode.
// Set the following to true to use http-based authorization.
// web-based login.)
$use_http_auth = false;

// Which user schema to use.  Currently, you can use the default webcal_user
// table or LDAP.  These files are found in the includes directory.
// Pick just one of the following:

// webcal_user table: default
$user_inc = "user.inc";
// LDAP: if you select this, you must also configure some variables
// in includes/user-ldap.inc such as your LDAP server...
//$user_inc = "user-ldap.inc";

// Approval: this doesn't apply if you're running single-user.  If you
// are running multi-user, you can optionally allow users to approve/confirm
// when events are added to their calendar.
// Users that have not approved/confirmed will be displayed with "(?)" next
// to their name when you view the event.
// NOTE: you will never need to approve an event that you created, only an
// an event that another user added to your calendar.
$require_approvals = true; // set to true or false

// Allow event conflicts (two events scheduled for the same time for the
// same person).  If you set this to false, you will still be able to
// override this in the user interface.  If you set this to true, no
// checking for conflicts will be done.
// You probably want to set this to false, so conflict checking occurs.
$allow_conflicts = false; // set to true or false

// How far into the future should we check for conflicts of repeating
// events?  6 months is probably sufficient.  If you find it's taking
// a long time to save repeating events, try making this less.
// Set it to 0 to not check for conflicts except for the original date
// of the repeating event.
$conflict_repeat_months = 6;

// You can disable some standard event fields if you want to simplify
// the interface.  This will only remove them from the interface, and
// you can always change this to add them back later.  The repeating
// event stuff seems to be confusing to novice users.  Note that
// the $disable_participants_field setting will allow you to prevent
// users from adding other users to events they create.  If you set
// this to true, you might want to set $allow_view_other to false
// below.
$disable_priority_field = false;
$disable_access_field = false;
$disable_participants_field = false;
$disable_repeating_field = false;

// Can non-admin users view the calendar of another user?
// Note: An admin user can still view another user's calendar.
$allow_view_other = true;

// If email addresses are provided for users, then they will receive
// email notifications when an event is added/modified in their calendar.
// Set the from address here.  This will only be used if the person adding
// the event does not have an email address specified.
$email_fallback_from = "youremailhere@somewhere.com";

// Should we use a cookie to remember the last login?  When returning
// to the login page, users will see their login already filled in
// (without the password filled in) and the user preferences will have
// been loaded (color settings and language setting).
$remember_last_login = true;

// DEFAULT SETTINGS: everything below here can be customized by the
// user in the Preferences page.  User settings will be stored in the
// webcal_user_pref table.
$allow_color_customization = true; // can users change their own color settings
$BGCOLOR = "#C0C0C0"; // document background color
$H2COLOR = "#000000"; // color of page titles
$CELLBG = "#C0C0C0"; // color of table cells in month view
$TABLEBG = "#000000"; // lines separating table cells
$THBG = "#FFFFFF"; // background color of table column headers
$THFG = "#000000"; // text color of table column headers
$POPUP_FG = "#000000"; // text color in popup of event description
$POPUP_BG = "#FFFFFF"; // background color in popup of event description
$TODAYCELLBG = "#E0E0E0"; // color of table cells of current day in month view
//$UNAPPROVED_COLOR = "#008000"; // color of unapproved events

// Default view: should be "month", "year", "week", or "day".
$STARTVIEW = "week";

// Weeks can start on either Sunday or Monday.
// If Monday, weeks will be numbered using ISO week numbers (which start
// on the first Monday of the year.)
// US week numbers will be shown
$WEEK_START = 0; // either 0=Sunday or 1=Monday

// Preferences for time.
// Options are "24", "12"
$TIME_FORMAT = "12";

// Display unapproved events in the calendar.
// If set to "N", then they must be approved before they are displayed.
$DISPLAY_UNAPPROVED = "N";

// Display small icons for view, edit, delete next to events.  If set
// to no, then you must click on the event to get to the view page, which
// then gives you access to these features.
$DISPLAY_ICONS = "Y";

// Display the week number in the calendar
$DISPLAY_WEEKNUMBER = "Y";

// Default hours to display when viewing day-at-a-glance
$WORK_DAY_START_HOUR = 8;
$WORK_DAY_END_HOUR = 17;

// Default email settings (set to "Y" or "N")
$EMAIL_REMINDER = "Y"; // send user event reminders
$EMAIL_EVENT_ADDED = "Y"; // send email when event added to calendar
$EMAIL_EVENT_UPDATED = "Y"; // send email when event updated on calendar
$EMAIL_EVENT_DELETED = "Y"; // send email when event deleted on calendar
$EMAIL_EVENT_REJECTED = "Y"; // send email when a participant rejects an event

// Language options  The first is the name presented to users while
// the second is the filename (without the ".txt") that must exist
// in the translations subdirectory.
$languages = array (
  "Browser-defined" =>"none",
  "English" =>"English-US",
  "Chinese-Big5" => "Chinese-Big5",
  "Czech" => "Czech",
  "Danish" => "Danish",
  "Dutch" =>"Dutch",
  "Finnish" =>"Finnish",
  "French" =>"French",
  "Galician" => "Galician",
  "German" =>"German",
  "Hungarian" =>"Hungarian",
  "Icelandic" => "Icelandic",
  "Italian" => "Italian",
  "Korean" =>"Korean",
  "Norwegian" => "Norwegian",
  "Polish" => "Polish",
  "Portuguese" =>"Portuguese",
  "Portuguese/Brazil" => "Portuguese_BR",
  "Russian" => "Russian",
  "Spanish" =>"Spanish",
  "Swedish" =>"Swedish",
  "Turkish" =>"Turkish"
  // add new languages here!  (don't forget to add a comma at the end of
  // last line above.)
);

// If the user sets "Browser-defined" as their language setting, then
// use the $HTTP_ACCEPT_LANGUAGE settings to determine the language.
// The array below translates browser language abbreviations into
// our available language files.
// NOTE: These should all be lowercase on the left side even though
// the proper listing is like "en-US"!
$browser_languages = array (
  "zh" => "Chinese-Big5",
  "zh-tw" => "Chinese-Big5",
  "cs" => "Czech",
  "en" => "English-US",
  "en-us" => "English-US",
  "en-gb" => "English-US",
  "da" => "Danish",
  "nl" =>"Dutch",
  "fi" =>"Finnish",
  "fr" =>"French",
  "fr-ch" =>"French", // French/Swiss
  "fr-ca" =>"French", // French/Canada
  "gl" => "Galician",
  "de" =>"German",
  "de-at" =>"German", // German/Austria
  "de-ch" =>"German", // German/Switzerland
  "de-de" =>"German", // German/German
  "hu" => "Hungarian",
  "is" => "Icelandic",
  "it" => "Italian",
  "it-ch" => "Italian", // Italian/Switzerland
  "ko" =>"Korean",
  "no" => "Norwegian",
  "pl" => "Polish",
  "pt" =>"Portuguese",
  "pt-br" => "Portuguese_BR", // Portuguese/Brazil
  "ru" =>"Russian",
  "es" =>"Spanish",
  "sv" =>"Swedish",
  "tr" =>"Turkish"
);

// Language default setting.
// Users can override this in the preferences settings for all pages except
// the login page.  This should be something like "Browser-defined",
// "Spanish", or "English-US".
$LANGUAGE = "Browser-defined";

// error check
if ( $use_http_auth && strlen ( $single_user_login ) ) {
  echo "Error in config.inc: No need to define \$use_http_auth " .
    "and \$single_user_login";
  exit;
}

if ( $single_user && ! strlen ( $single_user_login ) ) {
  echo "Error in config.inc: \$single_user set to true, " .
    "but \$single_user_login not defined";
  exit;
}

?>
