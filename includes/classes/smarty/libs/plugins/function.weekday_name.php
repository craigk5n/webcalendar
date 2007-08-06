<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * WebCalendar weekday name function plugin
 *
 * Type:     function<br>
 * Name:     weekday_name<br>
 * Purpose:  Returns either the full name or the abbreviation of the day
 * @author Ray Jones
 * @return string The weekday name ("Sunday" or "Sun")
 */
function smarty_function_weekday_name ( $params, &$smarty )
{
  global $lang;
  static $local_lang, $week_names;

  // We may have switched languages.
  if ( $local_lang != $lang )
    $week_names = $weekday_names = array ();

  $local_lang = $lang;
  $w = $params['day'];
  $long = $params['long'];
  // We may pass $DISPLAY_LONG_DAYS as $format.
  if ( $params['long'] == 'N' )
    $long = false;
  if ( $params['long'] == 'Y' )
    $long = true;	


  if ( empty ( $weekday_names[0] ) )
    $weekday_names = array (
      translate ( 'Sunday' ),
      translate ( 'Monday' ),
      translate ( 'Tuesday' ),
      translate ( 'Wednesday' ),
      translate ( 'Thursday' ),
      translate ( 'Friday' ),
      translate ( 'Saturday' )
      );

  if ( empty ( $week_names[0] ) )
    $week_names = array (
      translate ( 'Sun' ),
      translate ( 'Mon' ),
      translate ( 'Tue' ),
      translate ( 'Wed' ),
      translate ( 'Thu' ),
      translate ( 'Fri' ),
      translate ( 'Sat' )
      );

  if ( $w >= 0 && $w < 7 )
    return ( $long ? $weekday_names[$w] : $week_names[$w] );

  return translate ( 'unknown-weekday' ) . " ($w)";
}

/* vim: set expandtab: */

?>
