<?php
/* Custom theme for use with WebCalendar.
 *
 * Touch of Grey
 *
 * @author Jeff Hoover
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id: touch_of_grey_pref.php,v 1.5 2007/02/01 02:23:11 bbannon Exp $:
 * @package WebCalendar
 */

// Define your stuff here...
// Any option in webcal_user_pref can be configured here.

//This theme will be available to both normal users and System Settings.
$webcal_theme = array (
  'MENU_THEME'   => 'touch_of_grey',
  'BGCOLOR'      => '#E0E2EB',  // Document background
  'CELLBG'       => '#FFFFFF',  // Table cell background
  'H2COLOR'      => '#000000',  // Document title
  'HASEVENTSBG'  => '#E0E2EB',  // Table cell background for days with events
  'MYEVENTS'     => '#000000',  // My events text
  'OTHERMONTHBG' => '#F0F1F5',  // Table cell background for other month
  'POPUP_BG'     => '#E0E2EB',  // Event popup background
  'POPUP_FG'     => '#000000',  // Event popup text
  'TABLEBG'      => '#000000',  // Table grid color
  'TEXTCOLOR'    => '#000000',  // Document text
  'THBG'         => '#B4B7CA',  // Table header background
  'THFG'         => '#000000',  // Table header text
  'TODAYCELLBG'  => '#E0E2EB',  // Table cell background for current day
  'WEEKENDBG'    => '#F0F1F5',  // Table cell background for weekends
 );

include 'theme_inc.php';

?>
