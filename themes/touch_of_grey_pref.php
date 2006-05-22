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
  'MENU_THEME'   => 'touch_of_grey',  // Document background  
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
 
include 'theme_inc.php';
?>
