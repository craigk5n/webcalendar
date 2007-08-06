<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * WebCalendar date selection function plugin
 *
 * Type:     function<br>
 * Name:     date_selection<br>
 * Purpose:  Returns the html for Day/Month/Year
 * @param string $prefix   Prefix to use in front of form element names
 * @param string $date     Currently selected date (in YYYYMMDD format)
 * @param bool $trigger    Add onchange event trigger that
 *                         calls javascript function $prefix_datechanged ()
 * @param int  $num_years  Number of years to display
 * @author Ray Jones
 * @return string 
 */
function smarty_function_date_selection ( $params, &$smarty )
{
  $prefix = $params['prefix'];
	$date = ( ! empty ( $params['date'] ) ? $params['date'] : date ( 'Ymd' ) );
	$trigger = $params['trigger'];
	$num_years = $params['num_years'];

  $trigger_str = ( empty ( $trigger ) ? '' : $prefix . 'datechanged ();' );
  $onchange = ( empty ( $trigger_str ) ? '' : 'onchange="$trigger_str"' );
  if ( strlen ( $date ) != 8 )
    $date = date ( 'Ymd' );

  $thisyear = $year = substr ( $date, 0, 4 );
  $thismonth = $month = substr ( $date, 4, 2 );
  $WC->thisday = $day = substr ( $date, 6, 2 );
  if ( $thisyear - date ( 'Y' ) >= ( $num_years - 1 ) )
    $num_years = $thisyear - date ( 'Y' ) + 2;

  $ret = '
      <select name="' . $prefix . 'day" id="' . $prefix . 'day"'
   . $onchange . '>';
  for ( $i = 1; $i <= 31; $i++ ) {
    $ret .= '
        <option value="' . "$i\""
     . ( $i == $WC->thisday ? SELECTED : '' ) . ">$i" . '</option>';
  }
  $ret .= '
      </select>
      <select name="' . $prefix . 'month"' . $onchange . '>';
  for ( $i = 1; $i < 13; $i++ ) {
    $ret .= '
        <option value="' . "$i\""
     . ( $i == $thismonth ? SELECTED : '' )
     . '>' . month_name ( $i - 1, 'M' ) . '</option>';
  }
  $ret .= '
      </select>
      <select name="' . $prefix . 'year"' . $onchange . '>';
  for ( $i = -10; $i < $num_years; $i++ ) {
    $y = $thisyear + $i;
    $ret .= '
        <option value="' . "$y\"" . ( $y == $thisyear ? SELECTED : '' )
     . ">$y" . '</option>';
  }
  return $ret . '
      </select>
      <input type="button" class="btn" name="' . $prefix 
			  . 'btn" onclick="selectDate( \'' . $prefix . 'day\',\'' 
				. $prefix . 'month\',\'' . $prefix . "year','$date'"
        . ', event, this.form );" value="' 
				. translate ( 'Select' ) . '..." />' . "\n";
}
/* vim: set expandtab: */

?>
