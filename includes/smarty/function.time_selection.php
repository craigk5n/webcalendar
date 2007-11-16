<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * WebCalendar time selection function plugin
 *
 * Type:     function<br>
 * Name:     time_selection<br>
 * Purpose:  Returns the html for Hours/Minutes?AMPM
 * @param string $prefix Prefix to use in front of form element names
 * @param string $time   Currently selected time in HHMMSS
 * @param bool $trigger   Add onchange event trigger that
 *  calls javascript function $prefix_timechanged()
 * @author Ray Jones
 * @return string 
 */
function smarty_function_time_selection ( $params, &$smarty )
{
  $prefix = $params['prefix'];
	$time = $params['time'];
	$trigger = $params['trigger'];

  $ret = '';
  $hournameid = 'name="' . $prefix . 'hour" id="' . $prefix . 'hour" ';
  $minnameid = 'name="' . $prefix . 'minute" id="' . $prefix . 'minute" ';
  $trigger_str = ( $trigger ? 'onchange="' . $prefix . 'timechanged() ' : '');
  $time_format = getPref ( 'TIME_FORMAT' );
  $entry_slots = getPref ( 'ENTRY_SLOTS' );
	if ( $entry_slots == 0 ) $entry_slots = 12;
  if ( ! isset ( $time ) && $time != 0 ) {
    $hour = getPref ( 'WORK_DAY_START_HOUR' );
    $minute = 0;
  } else {
    $hour = floor($time / 10000);
    $minute = ( ( $time / 100 ) % 100 ) % 60;  
  }
  if ( $time_format == '12' ) {
    $maxhour = 12;
    if ( $hour < 12 ) {
      $amsel = CHECKED ; $pmsel = '';
    } else {
      $amsel = ''; $pmsel = CHECKED ;
    }
    $hour %= 12;
    if ( $hour === 0 ) $hour = 12;
  } else {
    $maxhour = 24;
    $hour = sprintf ( "%02d", $hour );  
  }
  $minute = sprintf ( "%02d", $minute ); 
  $ret .= '<select ' . $hournameid . $trigger_str . " >\n";
  for ( $i = 0; $i < $maxhour; $i++ ) {
    $ihour = ( $time_format == '24' ? sprintf ( "%02d", $i ) : $i );
    if ( $i == 0 && $time_format == '12' ) $ihour = 12;
    $ret .= "<option value=\"$i\"" .
      ( $ihour == $hour ? SELECTED : '' ) . ">$ihour</option>\n";
  }
  $ret .= '</select> : <select style="margin-left:0px" ' 
	  . $minnameid . $trigger_str . " >\n";
  //we use TIME_SLOTS to populate the minutes pulldown
  $found = false;
  for ( $i = 0; $i <= 59; ) {
    $imin = sprintf ( "%02d", $i );
    $isselected = '';
    if ( $imin == $minute ) {
      $found = true;
      $isselected = SELECTED;  
    }
    $ret .= "<option value=\"$i\"$isselected >$imin</option>\n";
    $i += (1440 / $entry_slots);
  }
  //we'll add an option with the exact time if not found above
  if ( $found == false ) {
    $ret .= "<option value=\"$minute\" " . SELECTED. " >$minute</option>\n";
  }
  $ret .= "</select>\n";

  if ( $time_format == '12' ) {
    $ret .= '<label><input type="radio" name="' . $prefix . 
      'ampm" id="'. $prefix . 'ampmA" value="0" ' ."$amsel />&nbsp;" . 
      translate( 'am' ) . "</label>\n";
    $ret .= '<label><input type="radio" name="' . $prefix . 
      "ampm\" id=\"". $prefix . "ampmP\" value=\"12\" $pmsel />&nbsp;" . 
      translate( 'pm' ) . "</label>\n";
  } else {
    $ret .= '<input type="hidden" name="' . $prefix . 'ampm" value="0" />' ."\n";
  }
  return $ret;
}
/* vim: set expandtab: */

?>
