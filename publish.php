<?php
/*
 * $Id$
 *
 * Description:
 * Creates the iCal output for a single user's calendar so
 * that remote users can "subscribe" to a WebCalendar calendar.
 * Both Apple iCal and Mozilla's Calendar support subscribing
 * to remote calendars.
 *
 * Note that unlink the export to iCal, this page does not include
 * attendee info.  This improves the performance considerably, BTW.
 *
 * Notes:
 * Does anyone know when a client (iCal, for example) refreshes its
 * data, does it delete all old data and reload?  Just wondering
 * if we need to somehow send a delete notification on updates...
 *
 * Input parameters:
 * URL should be the form of /xxx/publish.php/username.ics
 * or /xxx/publish.php?user=username
 *
 * Security:
 * If $PUBLISH_ENABLED is not 'Y' (set in Admin System Settings),
 *   do not allow.
 * If $USER_PUBLISH_ENABLED is not 'Y' (set in each user's
 *   Preferences), do not allow.
 */

require_once 'includes/classes/WebCalendar.class';

$WebCalendar =& new WebCalendar ( __FILE__ );

include 'includes/config.php';
include 'includes/dbi4php.php';
include 'includes/functions.php';

$WebCalendar->initializeFirstPhase();

include "includes/$user_inc";
include 'includes/translate.php';

include 'includes/site_extras.php';
include_once 'includes/xcal.php';

$WebCalendar->initializeSecondPhase();

// Calculate username.
if ( empty ( $user ) ) {
  $arr = explode ( "/", $PHP_SELF );
  $user = $arr[count($arr)-1];
  # remove any trailing ".ics" in user name
  $user = preg_replace ( "/\.[iI][cC][sS]$/", '', $user );
}

if ( $user == 'public' )
  $user = '__public__';
  
load_global_settings ();

$WebCalendar->setLanguage();

if ( empty ( $PUBLISH_ENABLED ) || $PUBLISH_ENABLED != 'Y' ) {
  header ( "Content-Type: text/plain" );
  etranslate("You are not authorized");
  exit;
}

// Make sure they specified a username
if ( empty ( $user ) ) {
  echo "<?xml version=\"1.0\" encoding=\"utf8\"?>\n<!DOCTYPE html
    PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"
    \"DTD/xhtml1-transitional.dtd\">
<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\" lang=\"en\">\n
 <head>\n<title>" . translate("Error") . "</title>\n</head>\n" .
    "<body>\n<h2>" . translate("Error") . "</h2>\n" .
    "No user specified.\n</body>\n</html>";
}

// Load user preferences (to get the USER_PUBLISH_ENABLED and
// DISPLAY_UNAPPROVED setting for this user).
$login = $user;
load_user_preferences ();

if ( empty ( $USER_PUBLISH_ENABLED ) || $USER_PUBLISH_ENABLED != 'Y' ) {
  header ( "Content-Type: text/plain" );
  etranslate("You are not authorized");
  exit;
}

// Load user name, etc.
user_load_variables ( $user, "publish_" );


//header ( "Content-Type: text/plain" );
header ( "Content-Type: text/calendar" );
header ( 'Content-Disposition: attachment; filename="' . $user .  '.ics"' );
$use_all_dates = true;
export_ical();
?>
