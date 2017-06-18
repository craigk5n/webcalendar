<?php
/* Custom theme for use with WebCalendar.
 *
 * @author Ray Jones <rjones@umces.edu>
 * @copyright Craig Knudsen, <cknudsen@cknudsen.com>, http://www.k5n.us/cknudsen
 * @license http://www.gnu.org/licenses/gpl.html GNU GPL
 * @version $Id: basic_admin.php,v 1.4 2007/02/01 02:23:11 bbannon Exp $:
 * @package WebCalendar
 */

// Define your stuff here...
// Any option in webcal_user_pref can be configured here.

// This theme sets the default System Settings for a few display options.
// This will only affect new users or users who have not set their own preferences.
$webcal_theme = array (
  'DISPLAY_SM_MONTH'      => 'N',
  'DISPLAY_TASKS'         => 'N',
  'DISPLAY_TASKS_IN_GRID' => 'N',
  'DISPLAY_WEEKENDS'      => 'N',
  );

include 'theme_inc.php';

?>
