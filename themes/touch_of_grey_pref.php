<?php
/**
 * Custom theme for use with WebCalendar
 *
 * Touch of Grey
 *
 * @author Jeff Hoover
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id: 
 * @package WebCalendar
 */

// Define your stuff here...
// Any option in webcal_user_pref can be configured here
//

//This theme will be available to both normal users and System Settings.
$webcal_theme = array (
  'BGCOLOR'      => '#E0E2EB',  // Document background
  'H2COLOR'      => '#000000',  // Document title
  'TEXTCOLOR'    => '#000000',  // Document text
  'MYEVENTS'     => '#000000',  // My events text
  'TABLEBG'      => '#000000',  // Table grid color
  'THBG'         => '#B4B7CA',  // Table header background
  'THFG'         => '#000000',  // Table header text
  'CELLBG'       => '#FFFFFF',  // Table cell background
  'TODAYCELLBG'  => '#E0E2EB',  // Table cell background for current day
  'HASEVENTSBG'  => '#E0E2EB',  // Table cell background for days with events
  'WEEKENDBG'    => '#F0F1F5',  // Table cell background for weekends
  'OTHERMONTHBG' => '#F0F1F5',  // Table cell background for other month
  'POPUP_BG'     => '#E0E2EB',  // Event popup background
  'POPUP_FG'     => '#000000'   // Event popup text
 );
 
// Displays a screenshot if called directly and a file exists that matches
//this script name 
//Place this in all themes
if ( empty ( $PHP_SELF ) && ! empty ( $_SERVER ) &&
  ! empty ( $_SERVER['PHP_SELF'] ) ) {
  $PHP_SELF = $_SERVER['PHP_SELF'];
}

if ( ! empty ( $PHP_SELF ) && preg_match ( "/\/themes\//", $PHP_SELF ) ) {
  $filename = basename($PHP_SELF, ".php") . ".gif";
  echo "<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>\n" .
   "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" " .
   "\"DTD/xhtml1-transitional.dtd\">\n" .
   "<html xmlns=\"http://www.w3.org/1999/xhtml\" " .
   "xml:lang=\"en\" lang=\"en\">\n" .
   "<head><body>\n"; 
   
  if (file_exists( $filename )) {   
    echo "<img src=\"$filename\" />   ";
  } else {
    echo "<h2>NO PREVIEW AVAILABLE</H2>";
  }
  echo "</body></html>";
}
?>
