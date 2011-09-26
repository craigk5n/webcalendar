<?php // $Id$
/**
 * Description:
 * Creates the iCal output for a single user's calendar so that remote users can
 * "subscribe" to a WebCalendar calendar. Both Apple iCal and Mozilla's calendar
 * support subscribing to remote calendars.
 *
 * Note that unlike the export to iCal, this page does not include
 * attendee info. This improves the performance considerably, BTW.
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
 * DO NOT ALLOW if either;
 * $PUBLISH_ENABLED is not 'Y' (set in Admin System Settings).
 * $USER_PUBLISH_ENABLED is not 'Y' (set in each user's Preferences).
 */

foreach( array(
    'config',
    'dbi4php',
    'formvars',
    'functions',
    'site_extras',
    'translate',
    'validate',
    'xcal',
  ) as $i ) {
  include_once 'includes/' . $i . '.php';
}
require_once 'includes/classes/WebCalendar.class';

$WebCalendar = new WebCalendar( __FILE__ );
$WebCalendar->initializeFirstPhase();

include 'includes/' . $user_inc;

$WebCalendar->initializeSecondPhase();

// Calculate username.
// If using http_auth, use those credentials.
if ( $use_http_auth && empty ( $user ) )
  $user = $login;

if ( empty ( $user ) ) {
  $arr = explode ( '/', $PHP_SELF );
  $user = $arr[count ( $arr )-1];
  # remove any trailing ".ics" in user name
  $user = preg_replace ( "/\.[iI][cC][sS]$/", '', $user );
}

if ( $user == 'publish.php' )
  $user = '';

if ( $user == 'public' )
  $user = '__public__';

load_global_settings();

$WebCalendar->setLanguage();

if ( empty ( $PUBLISH_ENABLED ) || $PUBLISH_ENABLED != 'Y' ) {
  header ( 'Content-Type: text/plain' );
  echo print_not_auth();
  exit;
}

$errorStr = translate ( 'Error' );
$nouser = translate( 'No user specified.' );
// Make sure they specified a username.
if ( empty ( $user ) ) {
  echo send_doctype ( $errorStr );
  echo <<<EOT
  </head>
  <body>
    <h2>{$errorStr}</h2>
    {$nouser}.
  </body>
</html>
EOT;
  exit;
}

// Load user preferences (to get the USER_PUBLISH_ENABLED and
// DISPLAY_UNAPPROVED setting for this user).
$login = $user;
load_user_preferences();

if ( empty ( $USER_PUBLISH_ENABLED ) || $USER_PUBLISH_ENABLED != 'Y' ) {
  header ( 'Content-Type: text/plain' );
  echo print_not_auth();
  exit;
}

// Load user name, etc.
user_load_variables ( $user, 'publish_' );

// header ( 'Content-Type: text/plain' );
header ( 'Content-Type: text/calendar' );
header ( 'Content-Disposition: attachment; filename="' . $user . '.ics"' );
$use_all_dates = true;
$type = 'publish';
export_ical();

?>
