<?php
/**
 * Custom theme for use with WebCalendar
 *
 * @author Ray Jones <rjones@umces.edu>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id: 
 * @package WebCalendar
 */

// Define your stuff here...
// Any option in webcal_user_pref can be configured here
//

//This theme sets the default System Settings for a few display options.
//This will only affect new users or users who have not selected their own preferences.
$webcal_theme = array (
  'DISPLAY_SM_MONTH'=>'N',
  'DISPLAY_WEEKENDS'=>'N',
  'DISPLAY_TASKS'=>'N',
  'DISPLAY_TASKS_IN_GRID'=>'N'
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
