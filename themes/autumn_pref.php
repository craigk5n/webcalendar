<?php
/* Custom theme for use with WebCalendar.
 *
 * Autumn - modify colors for autumn.
 *
 * @author Ray Jones <rjones@umces.edu>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id: autumn_pref.php,v 1.5 2007/02/01 02:23:11 bbannon Exp $:
 * @package WebCalendar
 */

// Define your stuff here...
// Any option in webcal_user_pref can be configured here,

// This theme will be available to both normal users and System Settings.
$webcal_theme = array (
  'MENU_THEME'   => 'autumn',
  'BGCOLOR'      => '#F4DD65',
  'CELLBG'       => '#F4DD65',
  'H2COLOR'      => '#000000',
  'HASEVENTSBG'  => '#CC9900',
  'OTHERMONTHBG' => '#CC9900',
  'POPUP_BG'     => '#F4DD65',
  'POPUP_FG'     => '#000000',
  'TABLEBG'      => '#000000',
  'TEXTCOLOR'    => '#000000',
  'THBG'         => '#F4DD65',
  'THFG'         => '#000000',
  'TODAYCELLBG'  => '#E7D03A',
  'WEEKENDBG'    => '#FF6633',
  );

include 'theme_inc.php';

?>
