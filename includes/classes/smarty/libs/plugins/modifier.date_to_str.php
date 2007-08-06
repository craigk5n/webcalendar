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

  if ( $indate == '' )
    $indate = time();

  //TODO Temp hack till we convert to 100% Timestamps
  if ( strlen ( $indate ) == 8 )
    $indate = date_to_epoch ( $indate ) + (12 * ONE_HOUR);

  if ( empty ( $format ) ) {
    $format = getPref ( 'DATE_FORMAT' );
  } else if ( substr ( $format,0,4 ) == 'DATE' ) {
    // if they have not set a preference yet...
    $format = getPref ( $format );
	}
   
	$format = ( ! $format || $format == 'LANGUAGE_DEFINED' 
      ? translate ( '__month__ __dd__, __yyyy__' ) : $format );
		
  $y = date ( 'Y', $indate );
  $m = date ('m', $indate );
  $d = date ( 'd', $indate );
  $wday = date ( "w", $indate );
  if ( $short_months ) {
    $month = month_name ( $m - 1, 'M' );
    $weekday = weekday_name ( $wday, 'D' );
  } else {
    $month = month_name ( $m - 1 );
    $weekday = weekday_name ( $wday );
  }

  $ret = str_replace ( '__dd__', $d, $format );
  $ret = str_replace ( '__j__', intval ( $d ), $ret );
  $ret = str_replace ( '__mm__', $m, $ret );
  $ret = str_replace ( '__mon__', $month, $ret );
  $ret = str_replace ( '__month__', $month, $ret );
  $ret = str_replace ( '__n__', sprintf ( "%02d", $m ), $ret );
  $ret = str_replace ( '__yy__', sprintf ( "%02d", $y % 100 ), $ret );
  $ret = str_replace ( '__yyyy__', $y, $ret );

  return ( $show_weekday ? "$weekday, $ret" : $ret );
}

/* vim: set expandtab: */

?>
