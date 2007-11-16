<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
 * WebCalendar date_to_str modifier plugin
 *
 * Type:     modifier<br>
 * Name:     date_to_str<br>
 * Converts a date UNIX Timestamp format into "Friday, December 31, 1999",
 * "Friday, 12-31-1999" or whatever format the user prefers.
 *
 * @param string $indate       Date in UNIX Timestamp format
 * @param string $format       Format to use for date (default is "__month__
 *                             __dd__, __yyyy__")
 * @param bool   $show_weekday Should the day of week also be included?
 * @param bool   $short_months Should the abbreviated month names be used
 *                             instead of the full month names?
 *
 * @return string  Date in the specified format.
 *
 */
function smarty_modifier_date_to_str ( $indate='', 
  $format='', $show_weekday = true, $short_months = false ) {

  return date_to_str ( $indate, $format, $show_weekday, $short_months );
}

/* vim: set expandtab: */

?>
