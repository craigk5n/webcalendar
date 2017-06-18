<?php
/* Custom theme for use with WebCalendar.
 *
 * Spring - modify colors for spring.
 *
 * @author Ray Jones <rjones@umces.edu>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id: spring_pref.php,v 1.5 2007/02/01 02:23:11 bbannon Exp $:
 * @package WebCalendar
 */

// Define your stuff here...
// Any option in webcal_user_pref can be configured here.

// This theme will be available to both normal users and System Settings.
$webcal_theme = array (
  'MENU_THEME'   => 'spring',
  'BGCOLOR'      => '#CCFFCC',
  'CELLBG'       => '#99FF99',
  'H2COLOR'      => '#006600',
  'HASEVENTSBG'  => '#66FF66',
  'OTHERMONTHBG' => '#999933',
  'POPUP_BG'     => '#6699CC',
  'POPUP_FG'     => '#000000',
  'TABLEBG'      => '#000000',
  'TEXTCOLOR'    => '#000000',
  'THBG'         => '#669900',
  'THFG'         => '#000000',
  'TODAYCELLBG'  => '#FFFF66',
  'WEEKENDBG'    => '#00CC99',
  );

include 'theme_inc.php';

?>
