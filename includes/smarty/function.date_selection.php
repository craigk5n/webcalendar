<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * WebCalendar date selection function plugin
 *
 * Type:     function<br />
 * Name:     date_selection<br />
 * Purpose:  Returns the html for Day/Month/Year
 * @param string $prefix   Prefix to use in front of form element id
 * @param string $date     Currently selected date (in YYYYMMDD format)
 * @param bool $trigger    Add onchange event trigger that
 *                         calls javascript function $prefix_datechanged ()
 * @param int  $year_pre   Previous years to display (default 10 years back)
 * @param int  $year_post  Future years to display (default 2 years)
 * @param bool $blank      Display empty date feilds for optional input
 * @author Ray Jones
 * @return string 
 */
function smarty_function_date_selection ( $params, &$smarty )
{

	$date = ( ! empty ( $params['date'] ) ? $params['date'] : date ( 'Ymd' ) );
  if ( strlen ( $date ) != 8 )
    $date = date ( 'Ymd' );
  $thisyear = $year = substr ( $date, 0, 4 );
  $thismonth = $month = substr ( $date, 4, 2 );
  $thisday = $day = substr ( $date, 6, 2 );
	$blank = ( ! empty ( $params['blank'] ) 
	  && $date == date ( 'Ymd' )? true : false );
	
	$blankStr = ( $blank ? '<option value="" selected="selected">&nbsp;</option>'
	  : '' );
	
	$year_pre = ( ! empty ( $params['year_pre'] ) ?
	  $params['year_pre'] : getPref ( 'YEARS_PRE', 2, '', 10 ) );
	$year_pre = ( $year_pre > 0 ? -$year_pre : $year_pre );
	$year_post = ( ! empty ( $params['year_post'] ) ? 
	  $params['year_post'] : getPref ( 'YEARS_POST', 2, '', 2 ) );

  $prefix = $params['prefix'];
	$trigger = $params['trigger'];	
  $trigger_str = ( empty ( $trigger ) ? '' : $prefix . 'datechanged ();' );
  $onchange = ( empty ( $trigger_str ) ? '' : 'onchange="$trigger_str"' );

  $ret = '
    <select name="' . $prefix . 'day" id="' . $prefix . 'day"'
     . $onchange . '>
	 '. $blankStr;
  for ( $i = 1; $i <= 31; $i++ ) {
    $ret .= '
        <option value="' . "$i\""
     . ( $i == $thisday && !$blank ? SELECTED : '' ) . ">$i" . '</option>';
  }
  $ret .= '
      </select>
      <select name="' . $prefix . 'month" id="' . $prefix . 'month"' 
			  . $onchange . '>
			' . $blankStr;
  for ( $i = 1; $i < 13; $i++ ) {
    $ret .= '
        <option value="' . "$i\""
     . ( $i == $thismonth  && !$blank ? SELECTED : '' )
     . '>' . month_name ( $i - 1, 'M' ) . '</option>';
  }
  $ret .= '
      </select>
      <select name="' . $prefix . 'year" id="' . $prefix . 'year"' 
			  . $onchange . '>
			' . $blankStr;
  for ( $i = $year_pre; $i < $year_post; $i++ ) {
    $y = $thisyear + $i;
    $ret .= '
        <option value="' . "$y\"" . ( $y == $thisyear  && !$blank ? SELECTED : '' )
     . ">$y" . '</option>';
  }
  return $ret . '
      </select>
      <input type="button" class="btn" id="' . $prefix 
			  . 'btn" onclick="selectDate( \'' . $prefix . 'day\',\'' 
				. $prefix . 'month\',\'' . $prefix . "year','$date'"
        . ', event, this.form );" value="' 
				. translate ( 'Select' ) . '..." />' . "\n";
}
/* vim: set expandtab: */

?>
