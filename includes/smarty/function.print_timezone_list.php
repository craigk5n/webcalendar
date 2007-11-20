<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * WebCalendar print_timezone_list function plugin
 *
 * Type:     function<br>
 * Name:     print_timezone_list<br>
 * Purpose:  Return the html to display a list of timezones
 * @author Ray Jones
 * @return string
 */
function smarty_function_print_timezone_list ( $params, &$smarty )
{
  $ret = '';
  // Allows different SETTING names between SERVER and USER.
  if ( $params['prefix'] == 'admin_' ) {
    $tz = getPref ( '_SERVER_TIMEZONE',2 );  
  } else {
    $tz = getPref ( '_SERVER_TIMEZONE' );  
  }
  // We may be using php 4.x on Windows, so we can't use set_env () to
  // adjust the user's TIMEZONE.  We'll need to reply on the old fashioned
  // way of using $tz_offset from the server's timezone.
  $can_setTZ = ( substr ( $tz, 0, 11 ) == 'WebCalendar' ? false : true );
  $old_TZ = getenv ( 'TZ' );
  set_env ( 'TZ', 'America/New_York' );
  $tmp_timezone = date ( 'T' );
  set_env ( 'TZ', $old_TZ );
  // Don't change this to date ().
  // if ( date ( 'T' ) == 'Ame' || ! $can_setTZ ) { //We have a problem!!
  if ( 0 ) { // Ignore this code for now.
    $tz_value = ( ! $can_setTZ ? substr ( $tz, 12 ) : 0 );
    $ret = '
        <select name="' . $params['prefix'] 
        . 'TIMEZONE" id="' . $params['prefix'] . 'TIMEZONE">';
    $text_add = translate ( 'Add N hours to' );
    $text_sub = translate ( 'Subtract N hours from' );
    for ( $i = -12; $i <= 13; $i++ ) {
      $ret .= '
          <option value="WebCalendar/' . $i . '"'
       . ( $tz_value == $i ? SELECTED : '' ) . '>' . ( $i < 0
        ? str_replace ( 'N', - $i, $text_sub ) : ( $i == 0
          ? translate ( 'same as' ) : str_replace ( 'N', $i, $text_add ) ) )
       . '</option>';
    }
    $ret .= '
        </select>&nbsp;' . translate ( 'server time' );
  } else { // This installation supports TZ env.
    // Import Timezone name.  This file will not normally be available
    // on windows platforms, so we'll just include it with WebCalendar.
    $tz_file = 'includes/zone.tab';
    if ( ! $fd = @fopen ( $tz_file, 'r', false ) )
      return str_replace ( 'XXX', $tz_file,
        translate ( 'Cannot read timezone file XXX.' ) );
    else {
      while ( ( $data = fgets ( $fd, 1000 ) ) !== false ) {
        if ( ( substr ( trim ( $data ), 0, 1 ) == '#' ) || strlen ( $data ) <= 2 )
          continue;
        else {
          $data = trim ( $data, strrchr ( $data, '#' ) );
          $data = preg_split ( '/[\s,]+/', trim ( $data ) );
          $timezones[] = $data[2];
        }
      }
      fclose ( $fd );
    }
    sort ( $timezones );
    $ret = '
        <select name="' . $params['prefix'] 
        . 'TIMEZONE" id="' . $params['prefix'] . 'TIMEZONE">';
    for ( $i = 0, $cnt = count ( $timezones ); $i < $cnt; $i++ ) {
      $ret .= '
          <option value="' . $timezones[$i] . '"'
       . ( $timezones[$i] == $tz ? SELECTED : '' ) . ' >'
       . unhtmlentities ( $timezones[$i] ) . '</option>';
    }
// translate ( 'Your current GMT offset is' )
    $ret .= '
        </select>&nbsp;&nbsp;' . str_replace (' XXX ',
         '&nbsp;' . date ( 'Z' ) / ONE_HOUR . '&nbsp;',
         translate ( 'Your current GMT offset is XXX hours.' ) );
  }
  return $ret;

}

/* vim: set expandtab: */

?>
