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
  global $SERVER_TIMEZONE;

  if ( $control & 4 ) {
    $currentTZ = getenv ( 'TZ' );
    set_env ( 'TZ', $SERVER_TIMEZONE );
  }
  $t_format = ( empty ( $format ) ? getPref ( 'TIME_FORMAT' ) : $format );
  $tzid = date ( ' T' ); //Default tzid for today.

  if ( strlen ( $intime ) > 8 ) {
    $time = date ( 'His', $intime );
    $tzid = date ( ' T', $intime );
    // $control & 1 = do not do timezone calculations
    if ( $control & 1 ) {
      $time = gmdate ( 'His', $intime );
      $tzid = ' GMT';
    }
	} else {
	  $time  = $intime;
	}

  $hour = intval ( $time / 10000 );
  $min = abs ( ( $time / 100 ) % 100 );

  // Prevent goofy times like 8:00 9:30 9:00 10:30 10:00.
  if ( $time < 0 && $min > 0 )
    $hour = $hour - 1;
  while ( $hour < 0 ) {
    $hour += 24;
  }
  while ( $hour > 23 ) {
    $hour -= 24;
  }
  if ( $t_format == '12' ) {
    $ampm = translate ( $hour >= 12 ? 'pm' : 'am' );
    $hour %= 12;
    if ( $hour == 0 )
      $hour = 12;

    $ret = sprintf ( "%d:%02d%s", $hour, $min, $ampm );
  } else
    $ret = sprintf ( "%02d:%02d", $hour, $min );

  if ( $control & 2 )
    $ret .= $tzid;

  // Reset timezone to previous value.
  if ( ! empty ( $currentTZ ) )
    set_env ( 'TZ', $currentTZ );

  return $ret;

}

/* vim: set expandtab: */

?>
