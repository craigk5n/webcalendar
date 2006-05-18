<?php
/**
 * Custom theme for use with WebCalendar
 *
 * Default System Settings
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

//This theme will reset the System Settings to the default values from the 
//installation script. This will not affect colors or options that users have
//already saved under preferences.
$webcal_theme = array (
'BGCOLOR'              =>'#FFFFFF',
'CELLBG'               =>'#C0C0C0',
'DISPLAY_SM_MONTH'     =>'Y',
'DISPLAY_TASKS'        =>'N',
'DISPLAY_TASKS_IN_GRID'=>'N',
'DISPLAY_WEEKENDS'     =>'Y',
'DISPLAY_WEEKNUMBER'   =>'Y',
'FONTS'                =>'Arial, Helvetica, sans-serif',
'HASEVENTSBG'          =>'#FFFF33',
'H2COLOR'              =>'#000000',
'OTHERMONTHBG'         =>'#D0D0D0',
'POPUP_BG'             =>'#FFFFFF',
'POPUP_FG'             =>'#000000',
'TABLEBG'              =>'#000000',
'TEXTCOLOR'            =>'#000000',
'THBG'                 =>'#FFFFFF',
'THFG'                 =>'#000000',
'TODAYCELLBG'          =>'#FFFF33',
'WEEKENDBG'            =>'#D0D0D0'
 );
 
include 'theme_inc.php';
?>
