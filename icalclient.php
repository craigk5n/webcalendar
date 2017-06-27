<?php // $Id: icalclient.php,v 1.42 2010/02/21 08:27:48 bbannon Exp $
/**
 *               WARNING * WARNING * WARNING * WARNING * WARNING
 *                 This script is still considered alpha level.
 *                 Please backup your database before using it.
 *               WARNING * WARNING * WARNING * WARNING * WARNING
 *
 * Description:
 * Creates the iCal output for a single user's calendar so that remote users can
 * "subscribe" to a WebCalendar calendar. Both Apple iCal and Mozilla's calendar
 * (Sunbird) support subscribing to remote calendars and publishing events back
 * to the server (WebCalendar in this case).
 *
 * This file was based on publish.php
 * and may replace it when it is found to be stable.
 *
 * Note that unlike the export to iCal, this page does not include
 * attendee info. This improves the performance considerably, BTW.
 *
 * ERROR !!!!!
 * There seems to be a bug in certain versions of PHP where the fgets() returns
 * a blank string when reading stdin. I found this to be a problem with
 * PHP 4.1.2 on Linux. If this is true for your PHP, you will not be able to
 * import the events back from the ical client.
 * It did work correctly with PHP 5.0.2.
 *
 * The script sends an error message back to the iCal client,
 * but Mozilla Calendar does not seem to display the message.
 * (Strange, since it did display a PHP compile error message...)
 *
 * Usage Requirements:
 * For this work, at least on some Apache intallations,
 * the following may need to be added to the httpd.conf file:
 *  <Directory "/var/www/html/webcalendar">
 *    Script PUT /icalclient.php
 *  </Directory>
 * Of course, replace "/var/www/html/webcalendar" with the
 * directory where you installed WebCalendar.
 *
 * Input parameters:
 * None
 *
 * Security:
 * If $PUBLISH_ENABLED is not 'Y' (set in Admin System Settings), do not allow.
 * If $USER_PUBLISH_RW_ENABLED is not 'Y' (set in each user's Preferences), do not allow.
 *
 * Change List:
 * 06-Jan-2005 Ray Jones
 *   Added logic to publish calendars to remote iCal clients.
 *   The clients I've tested use METHOD:PUT to upload their data to the server.
 *   This file does not use WEBDAV, but the client doesn't know or seem to care.
 *
 * Notes:
 * Because data is being written back to WebCalendar, the user is prompted
 * for username and password via the 401 HEADER.
 * SEE TO DO for needed work.
 *
 * To Delete an event from the iCal client, mark it as 'Cancelled'.
 * This will translate into a 'D' in webcal_entry_user.cal_status.
 *
 * TODO:
 * Security! If an event update comes back from an iCal client, we need to make
 * sure that the remote user has the authority to modify the event. (If they are
 * only a participant and not the creator of the event or an admin, then they
 * should not be able to update an event.)
 *
 * MAYBE add logic to loop through webcal_import_data and delete any records that
 * don't come back from the iCal client. This would indicate events were deleted
 * from the client instead of being marked 'Cancelled'.
 *
 * HTML in cal_description gets escaped when coming back from iCal client.
 * Some formatting is getting deleted. I added a couple lines to modify these
 * and it seems to work. However....you never know what it might break.
 *
 * Testing needs to be done with various RRULE options on import.
 *
 * Better support for event reminders. Reminders for past events are not sent
 * currently. This is because Mozilla Calendar may popup all reminders (even
 * ones that are years old) when the calendar is loaded. Ideally, we should
 * check the webcal_reminders table to see if an event reminder was already sent.
 * Also, not sure if reminders for repeated events are handled properly yet.
 */

include_once 'includes/translate.php';
require_once 'includes/classes/WebCalendar.class';

$WebCalendar = new WebCalendar( __FILE__ );

include 'includes/config.php';
include 'includes/dbi4php.php';
include 'includes/formvars.php';
include 'includes/functions.php';

$WebCalendar->initializeFirstPhase();

include 'includes/' . $user_inc;

include_once 'includes/validate.php';
include 'includes/site_extras.php';

include_once 'includes/xcal.php';

$WebCalendar->initializeSecondPhase();

$appStr = generate_application_name();
// If WebCalendar is using http auth, then $login will be set in validate.php.
/*

If running as CGI, the following instructions should set the PHP_AUTH_xxxx
variables. This has only been tested with apache2, so far. If using php as CGI,
you'll need to include this in your httpd.conf file or possibly in an .htaccess file.

Method 1: If this method fails, try method 2

  <IfModule mod_rewrite.c>
    RewriteEngine on
    RewriteRule .* - [E=REMOTE_USER:%{HTTP:Authorization},L]
  </IfModule>

Method 2:
  <IfModule mod_rewrite.c>
    RewriteEngine on

    RewriteCond %{QUERY_STRING} ^$
    RewriteRule ([^\s]+).php$ $1.php?BAD_HOSTING=%{HTTP:Authorization}

    RewriteCond %{QUERY_STRING} ^(.+)$
    RewriteRule ([^\s]+).php $1.php?%1&BAD_HOSTING=%{HTTP:Authorization}
  </IfModule>

*/

//Method 1
if ( empty( $_SERVER['PHP_AUTH_USER'] ) && ! empty( $_ENV['REMOTE_USER'] ) ) {
  list( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] ) =
  explode( ':', base64_decode( substr( $_ENV['REMOTE_USER'], 6 ) ) );

  $_SERVER['PHP_AUTH_USER'] = trim ( $_SERVER['PHP_AUTH_USER'] );
  $_SERVER['PHP_AUTH_PW'] = trim ( $_SERVER['PHP_AUTH_PW'] );
}

//Method 2
if ( ( empty( $_SERVER['PHP_AUTH_USER'] )
    or empty( $_SERVER['PHP_AUTH_PW'] ) )
    and isset( $_REQUEST['BAD_HOSTING'] )
    and preg_match( '/Basic\s+(.*)$/i', $_REQUEST['BAD_HOSTING'], $matc ) )
  list( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] ) =
    explode( ':', base64_decode( $matc[1] ) );

unset( $_ENV['REMOTE_USER'] );

if ( empty ( $login ) ) {
  if ( isset ( $_SERVER['PHP_AUTH_USER'] ) &&
      user_valid_login ( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'], true ) )
    $login = $_SERVER['PHP_AUTH_USER'];

  if ( empty ( $login ) || $login != $_SERVER['PHP_AUTH_USER'] ) {
    $_SERVER['PHP_AUTH_PW'] = $_SERVER['PHP_AUTH_USER'] = '';
    unset ( $_SERVER['PHP_AUTH_USER'] );
    unset ( $_SERVER['PHP_AUTH_PW'] );
    header ( 'WWW-Authenticate: Basic realm="' . $appStr . '"' );
    header ( 'HTTP/1.0 401 Unauthorized' );
    exit;
  }
}

load_global_settings();
load_user_preferences();

$WebCalendar->setLanguage();

if ( empty ( $PUBLISH_ENABLED ) || $PUBLISH_ENABLED != 'Y' ) {
  header ( 'Content-Type: text/plain' );
  // Mozilla Calendar does not bother showing errors, so they won't see this
  // error message anyhow... Not sure about Apple iCal or other clients.
  etranslate ( 'Publishing Disabled (Admin)' );
  exit;
}

if ( empty ( $USER_PUBLISH_RW_ENABLED ) || $USER_PUBLISH_RW_ENABLED != 'Y' ) {
  header ( 'Content-Type: text/plain' );
  etranslate ( 'Publishing Disabled (User)' );
  exit;
}

$prodid = 'Unnamed iCal client';

// Load user name, etc.
user_load_variables ( $login, 'publish_' );

function dump_globals() {
  foreach ( $GLOBALS as $K => $V ) {
    do_debug ( "GLOBALS[$K] => " . ( strlen ( $V ) < 70 ? $V : '(too long)' ) );
  }
  foreach ( $GLOBALS['HTTP_POST_VARS'] as $K => $V ) {
    do_debug ( "GLOBALS[$_POST[$K]] => "
       . ( strlen ( $V ) < 70 ? $V : '(too long)' ) );
  }
}

switch ( $_SERVER['REQUEST_METHOD'] ) {
  case 'PUT':
    // do_debug ( "Importing updated remote calendar" );
    $calUser = $login;
    $overwrite = true;
    $type = 'icalclient';

    $data = parse_ical ( '', $type );
    import_data ( $data, $overwrite, $type );
    break;

  case 'GET':
    // do_debug ( "Exporting updated remote calendar" );
    header ( 'Content-Type: text/calendar' );
    header ( 'Content-Disposition: attachment; filename="' . $login . '.ics"' );
    $use_all_dates = true;
    echo export_ical();
    break;

  case 'OPTIONS';
    header ( 'Allow: GET, PUT, OPTIONS' );
    break;

  default:
    header ( 'Allow: GET, PUT, OPTIONS' );
    header( 'HTTP/1.0 405 Method Not Allowed' );
    break;
}

?>
