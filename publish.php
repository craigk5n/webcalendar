<?php
/* $Id$
 *
 * Description:
 * Creates the iCal output for a single user's calendar so that remote users can
 * "subscribe" to a WebCalendar calendar. Both Apple iCal and Mozilla's Calendar
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
 * _ENABLE_PUBLISH is not 'Y' (set in Admin System Settings).
 * ENABLE_USER_PUBLISH is not 'Y' (set in each user's Preferences).
 */

require_once 'includes/classes/WebCalendar.class.php';

$WC =& new WebCalendar ( __FILE__ );

include 'includes/translate.php';
include 'includes/config.php';
include 'includes/dbi4php.php';
include 'includes/functions.php';

$WC->initializeFirstPhase ();
 
include 'includes/site_extras.php';
include_once 'includes/xcal.php';

$WC->initializeSecondPhase ();

// Calculate username.
// If using http_auth, use those credentials.
if ( _WC_HTTP_AUTH && empty ( $user ) )
  $user = $WC->loginId();

if ( empty ( $user ) ) {
  $arr = explode ( '/', $_SERVER['PHP_SELF'] );
  $user = $arr[count ( $arr )-1];
  # remove any trailing ".ics" in user name
  $user = preg_replace ( "/\.[iI][cC][sS]$/", '', $user );
}

if ( $user == 'publish.php' )
  $user = '';


$WC->setLanguage ();

if ( ! getPref ( '_ENABLE_PUBLISH' ) ) {
  header ( 'Content-Type: text/plain' );
  echo print_not_auth ();
  exit;
}

$errorStr = translate ( 'Error' );
$nouser = translate ( 'No user specified' );
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

// Load user preferences (to get the ENABLE_USER_PUBLISH and
// DISPLAY_UNAPPROVED setting for this user).
//TODO
$login = $user;

if ( ! getPref ( 'ENABLE_USER_PUBLISH' ) ) {
  header ( 'Content-Type: text/plain' );
  echo print_not_auth ();
  exit;
}

// Load user name, etc.
$WC->User->loadVariables ( $user, 'publish_' );

$calUser = $user;
// header ( 'Content-Type: text/plain' );
header ( 'Content-Type: text/calendar' );
header ( 'Content-Disposition: attachment; filename="' . $user . '.ics"' );
$use_all_dates = true;
$type = 'publish';
echo export_ical ();

?>
