<?php
/*
 * $Id$
 *
 * WARNING * WARNING * WARNING * WARNING * WARNING * WARNING
 * This script is still considered alpha level.  Please backup
 * your database before using it.
 * WARNING * WARNING * WARNING * WARNING * WARNING * WARNING
 *
 * Description:
 * Creates the iCal output for a single user's calendar so
 * that remote users can "subscribe" to a WebCalendar calendar.
 * Both Apple iCal and Mozilla's Calendar (Sunbird) support subscribing
 * to remote calendars and publishing events back to the server
 * (WebCalendar in this case).
 *
 * This file was based on publish.php and may replace it when
 * it is found to be stable.
 *
 * Note that unlike the export to iCal, this page does not include
 * attendee info.  This improves the performance considerably, BTW.
 *
 * ERROR !!!!!
 * There seems to be a bug in certain versions of PHP where the fgets()
 * returns a blank string when reading stdin.  I found this to be
 * a problem with PHP 4.1.2 on Linux.  If this is true for your PHP,
 * you will not be able to import the events back from the ical client.
 * It did work correctly with PHP 5.0.2.
 *
 * The script sends an error message back to the iCal client, but
 * Mozilla Calendar does not seem to display the message.  (Strange,
 * since it did display a PHP compile error message...)
 *
 * Usage Requirements:
 * For this work, at least on some Apache intallations, may need
 * to be added to the httpd.conf file:
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
 * If _ENABLE_ICALCLIENT is not 'Y' (set in Admin System Settings),
 *   do not allow.
 * If _ENABLE_ICALCLIENT is not 'Y' (set in each user's
 *   Preferences), do not allow 
 *
 * Change List:
 * 06-Jan-2005 Ray Jones
 *   Added logic to publish calendars to remote iCal clients
 *   The clients I've tested use METHOD:PUT to upload
 *   their data to the server.  This file does not use
 *   WEBDAV, but the client doesn't know or seem to care
 *
 * Notes:
 * Because data is being written back to WebCalendar, the user is prompted 
 * for username and password via the 401 HEADER 
 * SEE TO DO for needed work
 * 
 * To Delete an event from the iCal client, mark it as 'Cancelled'.
 * This will translate into a 'D' in webcal_entry_user.cal_status.
 * 
 * TODO:
 * Security!  If an event update comes back from an iCal client,
 * we need to make sure that the remote user has the authority to
 * modify the event.  (If they are only a participant and not the
 * creator of the event or an admin, then they should not be able
 * to update an event.)
 *
 * MAYBE add logic to loop through webcal_import_data and delete 
 * any records that don't come back from the iCal client. This would
 * indicate events were deleted from the client instead of being marked
 * 'Cancelled'.
 *
 * HTML in cal_description gets escaped when coming back from iCal client
 * some formatting is getting deleted. I added a couple lines to modify
 * these and it seems to work. However....you never know what it might
 * break.
 *
 * Testing needs to be done with various RRULE options on import.
 *
 * Better support for event reminders.  Reminders for past events
 * are not sent currently.  This is because Mozilla Calendar may
 * popup all reminders (even ones that are years old) when the
 * calendar is loaded.  Ideally, we should check the webcal_reminders
 * table to see if an event reminder was already sent.  Also, not
 * sure if reminders for repeated events are handled properly yet.
 *  
 */

 require_once 'includes/classes/WebCalendar.class.php';
     
 $WC =& new WebCalendar ( __FILE__ );    
     
 include 'includes/translate.php';    
 include 'includes/config.php';    
 include 'includes/dbi4php.php';    
 include 'includes/functions.php';    
     
 $WC->initializeFirstPhase();  
      
 include 'includes/site_extras.php';
 include 'includes/access.php'; 
 include_once 'includes/xcal.php';

$appStr = generate_application_name ();


//if running as CGI, the following should set the
// PHP_AUTH_xxxx variables if the following instructions are
//followed. This has been tested with apache2 only so far
// If using php as CGI, you'll need to include this
// in you php.ini file or possibly in a .htaccess file
//       <IfModule mod_rewrite.c>
//          RewriteEngine on
//          RewriteRule .* - [E=REMOTE_USER:%{HTTP:Authorization},L]
//       </IfModule>
if ( empty ( $_SERVER['PHP_AUTH_USER'] ) && ! empty ( $_ENV['REMOTE_USER'] ) ){
  list($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']) =
   explode(': ', base64_decode(substr($_ENV['REMOTE_USER'], 6))); 
}
   
unset ($_ENV['REMOTE_USER']);      
if ( ! $WC->loginId() ) {
  if (!isset($_SERVER['PHP_AUTH_USER'])) {
    unset($_SERVER['PHP_AUTH_PW']);
    header('WWW-Authenticate: Basic realm="' . $appStr . '"' );
    header('HTTP/1.0 401 Unauthorized');
    exit;
  } else {
    if ( $WC->User->validLogin ( $_SERVER['PHP_AUTH_USER'],
      $_SERVER['PHP_AUTH_PW'], true ) ) {
			$WC->_initLoginId ( $_SERVER['PHP_AUTH_USER'] );
    } else {
      unset($_SERVER['PHP_AUTH_USER']);
      unset($_SERVER['PHP_AUTH_PW']);
      //TODO should be able code this better to eliminate duplicate code
      header('WWW-Authenticate: Basic realm="' . $appStr . '"' );
      header('HTTP/1.0 401 Unauthorized');
      exit;
    }
  }  
}

$WC->initializeSecondPhase();
 
$WC->setLanguage();

if ( ! getPref ( '_ENABLE_ICALCLIENT', 2 ) ) {
  header ( 'Content-Type: text/plain' );
  echo translate ( 'IcalClient Disabled' ) . translate ( 'System Settings' ) ;
  exit;
}
if ( ! getPref ( '_ENABLE_ICALCLIENT', 0, $WC->loginId() ) ) {
  header ( 'Content-Type: text/plain' );
  echo translate ( 'IcalClient Disabled' ) . translate ( 'User' );
  exit;
}

$prodid = 'WebCalendar iCal client';


function dump_globals ()
{
  foreach ( $GLOBALS as $K => $V ) {
    if ( strlen ( $V ) < 70 )
      do_debug ( "GLOBALS[$K] => $V" );
    else
      do_debug ( "GLOBALS[$K] => (too long)" );
  }
  foreach ( $GLOBALS['HTTP_POST_VARS'] as $K => $V ) {
    if ( strlen ( $V ) < 70 )
      do_debug ( "GLOBALS[$_POST[$K]] => $V" );
    else
      do_debug ( "GLOBALS[$_POST[$K]] => (too long)" );
  }
}

$calUser = $WC->loginId();
if ( $_SERVER['REQUEST_METHOD'] == 'PUT' ) {
  //do_debug ( "Importing updated remote calendar" );
 $type = 'icalclient';
 $overwrite = true;
 $data = parse_ical( '', $type );
 import_data ( $data, $overwrite, $type );
} else {
  //do_debug ( "Exporting updated remote calendar" );
  header ( 'Content-Type: text/calendar' );
  header ( 'Content-Disposition: attachment; filename="' . $calUser .  '.ics"' );
  $use_all_dates = true;
  echo export_ical();
}
?>
