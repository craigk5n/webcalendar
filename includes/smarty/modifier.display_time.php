<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * WebCalendar display time modifier plugin
 *
 * Type:     modifier<br>
 * Name:     display_time<br>
 * Purpose:  Displays a time in either 12 or 24 hour format.
 * @author Ray Jones
 * @param int       $intime        input time in timestamp/HHMMSS format
 * @param int       $control     bitwise command value
 * @param string    $format      12 or 24
 *   0 default
 *   1 ignore_offset Do not use the timezone offset
 *   2 show_tzid Show abbrev TZ id ie EST after time
 *   4 use server's timezone
 * @param string $format     user's TIME_FORMAT when sending emails
 */

function smarty_modifier_display_time ( $intime, $control = 0, $format = '' )
{
 
  return display_time ( $intime, $control, $format );

}

/* vim: set expandtab: */

?>
