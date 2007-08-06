<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * WebCalendar print_date_selectors function plugin
 *
 * Type:     function<br>
 * Name:     date_selectors<br>
 * Purpose:  Return the value of user or server preferences
 * @author Ray Jones
 * @return string
 */
function smarty_function_date_selectors( $params, &$smarty ) {
  global $WC;

  //Generate the Date Select menu options
  $menu = getPref ( 'MENU_DATE_TOP' );
	$padding = ( $menu ? '10px' : '60px' );
  $goStr = translate ( 'Go' );
  $ret = $urlArgs = $include_id = '';
  $categories_enabled = getPref ( 'CATEGORIES_ENABLED' );
  $startview = getPref ( 'STARTVIEW' );
  //TODO add this to admin and pref
  //Change this value to 'Y' to enable staying in custom views 
  $STAY_IN_VIEW = 'N';
  if ( $STAY_IN_VIEW == 'Y' && defined ( '_WC_CUSTOM_VIEW' ) ) {
    $include_id = true;
    $monthUrl = _WC_SCRIPT;
  } else if ( access_can_view_page ( 'month.php' ) ) {
    $monthUrl = 'month.php';
  } else {
    $monthUrl = $startview;
    if ( preg_match ( "/[?&](\S+)=(\S+)/", $monthUrl, $match ) ) {
      $monthUrl = $match[0];
      $urlArgs = '
              <input type="hidden" name="' . $match[1] . '" value="' . $match[2]
       . '" />';
    }
  }
  $ret .= '<table id="dateselector">
	  <tr>
		  <td id="dsleft" style="padding-left:'. $padding .'">
		  <form action="' . $monthUrl
   . '" method="get" name="SelMonth" id="monthform"> ' . $urlArgs
   . ( $WC->isUser() ? '
            <input type="hidden" name="user" value="' 
			. $WC->userId() . '" />' : '' )
   . ( $WC->getId() && $include_id ? '
            <input type="hidden" name="id" value="' 
			. $WC->getId() . '" />' : '' )
   . ( $WC->catId() &&
    ( $WC->isUser( false ) ) ? '
            <input type="hidden" name="cat_id" value="'
   . $WC->catId() . '" />' : '' ) . '
			 <label for="monthselect"><a '
   . 'href="javascript:document.SelMonth.submit()">'
   . translate ( 'Month' ) . '</a>:&nbsp;</label>
            <select name="date" id="monthselect" '
   . 'onchange="document.SelMonth.submit()">';

  if ( ! empty ( $thisyear ) && ! empty ( $thismonth ) ) {
    $m = $thismonth;
    $y = $thisyear;
  } else {
    $m = date ( 'm' );
    $y = date ( 'Y' );
  }
  $d_time = mktime ( 0, 0, 0, $m, 1, $y );
  $thisdate = date ( 'Ymd', $d_time );
  // $y--;
  $m -= 7;
  for ( $i = 0; $i < 25; $i++ ) {
    $m++;
    if ( $m > 12 ) {
      $m = 1;
      $y++;
    }
    if ( $y >= 1970 && $y < 2038 ) {
      $d = mktime ( 0, 0, 0, $m, 1, $y );
      $dateYmd = date ( 'Ymd', $d );
      $ret .= '
                <option value="' . $dateYmd . '"'
       . ( $dateYmd == $thisdate ? SELECTED : '' ) . '>'
       . smarty_modifier_date_to_str ( $dateYmd, 'DATE_FORMAT_MY', 
	     false, true) . '</option>';
    }
  }

  $ret .= '
            </select>' . ( $menu == false ? '
            <input type="submit" value="' . $goStr . '" />' : '' ) . '
          </form></td>';

  if ( $STAY_IN_VIEW == 'Y'  && defined ( '_WC_CUSTOM_VIEW' ) ) {
    $weekUrl = _WC_SCRIPT;
  } else if ( access_can_view_page ( 'week.php' ) ) {
    $weekUrl = 'week.php';
    $urlArgs = '';
  } else {
    $weekUrl = startview;
    if ( preg_match ( "/[?&](\S+)=(\S+)/", $weekUrl, $match ) ) {
      $weekUrl = $match[0];
      $urlArgs = '
              <input type="hidden" name="' . $match[1] . '" value="' . $match[2]
       . '" />';
    }
  }
  $ret .= '<td  id="dscenter"><form action="' . $weekUrl
   . '" method="get" name="SelWeek" id="weekform">' . $urlArgs
   . ( $WC->isUser() ? '
            <input type="hidden" name="user" value="' 
			. $WC->userId() . '" />' : '' )
   . ( $WC->getId() && $include_id ? '
            <input type="hidden" name="id" value="' 
			. $WC->getId() . '" />' : '' )
   . ( $WC->catId() &&
    ( $WC->isUser( false ) ) ? '
            <input type="hidden" name="cat_id" value="'
   . $WC->catId() . '" />' : '' ) . '
            <label for="weekselect"><a '
   . 'href="javascript:document.SelWeek.submit()">'
   . translate ( 'Week' ) . '</a>:&nbsp;</label>
            <select name="date" id="weekselect" '
   . 'onchange="document.SelWeek.submit()">';
  if ( ! empty ( $thisyear ) && ! empty ( $thismonth ) ) {
    $m = $thismonth;
    $y = $thisyear;
  } else {
    $m = date ( 'm' );
    $y = date ( 'Y' );
  }
  $d = ( ! empty ( $thisday ) ? $thisday : date ( 'd' ) );
  $d_time = mktime ( 0, 0, 0, $m, $d, $y );
  $thisweek = date ( 'W', $d_time );
  $wday = date ( 'w', $d_time );
  $wkstart = mktime ( 12, 0, 0, $m, $d - ( $wday - getPref ( 'WEEK_START' ) ), $y );
  $lastDay = ( ! getPref ( 'DISPLAY_WEEKENDS' ) ? 4 : 6 );
  for ( $i = -5; $i <= 9; $i++ ) {
    $twkstart = $wkstart + ( ONE_WEEK * $i );
    $twkend = $twkstart + ( ONE_DAY * $lastDay );
    $dateSYmd = date ( 'Ymd', $twkstart );
	$dateEYmd = date ( 'Ymd', $twkend );
    $dateW = date ( 'W', $twkstart + ONE_DAY  );
    // echo $twkstart . " " . $twkend;
    if ( $twkstart > 0 && $twkend < 2146021200 ) {
      $ret .= '
              <option value="' . $dateSYmd . '"'
       . ( $dateW == $thisweek ? SELECTED : '' ) . '>'
       . ( getPref ( 'PULLDOWN_WEEKNUMBER' )
        ? '( ' . $dateW . ' )&nbsp;&nbsp;' : '' )
       . sprintf ( "%s - %s",
        smarty_modifier_date_to_str ( $dateSYmd, 'DATE_FORMAT_MD', false, true ),
        smarty_modifier_date_to_str ( $dateEYmd, 'DATE_FORMAT_MD', false, true ) )
       . '</option>';
    }
  }

  $ret .= '
              </select>' . ( $menu == false ? '
            <input type="submit" value="' . $goStr . '" />' : '' ) . '
          </form></td>';

  if ( $STAY_IN_VIEW == 'Y' && defined ( '_WC_CUSTOM_VIEW' ) ) {
    $yearUrl = _WC_SCRIPT;
  } else if ( access_can_view_page ( 'year.php' ) ) {
    $yearUrl = 'year.php';
    $urlArgs = '';
  } else {
    $yearUrl = startview;
    if ( preg_match ( "/[?&](\S+)=(\S+)/", $yearUrl, $match ) ) {
      $yearUrl = $match[0];
      $urlArgs = '
            <input type="hidden" name="' . $match[1] . '" value="' . $match[2]
       . '" />';
    }
  }
  $ret .= '
	  <td  id="dsright" style="padding-right:' . $padding .'">
		  <form action="' . $yearUrl
   . '" method="get" name="SelYear" id="yearform">' . $urlArgs
   . ( $WC->isUser() ? '
            <input type="hidden" name="user" value="' 
			. $WC->userId() . '" />' : '' )
   . ( $WC->getId() && $include_id ? '
            <input type="hidden" name="id" value="' 
			. $WC->getId() . '" />' : '' )
   . ( $WC->catId() &&
    ( $WC->isUser( false ) ) ? '
            <input type="hidden" name="cat_id" value="'
   . $WC->catId() . '" />' : '' ) . '
            <label for="yearselect"><a '
   . 'href="javascript:document.SelYear.submit()">'
   . translate ( 'Year' ) . '</a>:&nbsp;</label>
            <select name="year" id="yearselect" '
   . 'onchange="document.SelYear.submit()">';

  $y = ( ! empty ( $thisyear ) ? $thisyear : date ( 'Y' ) );

  for ( $i = $y - 2; $i < $y + 6; $i++ ) {
    if ( $i >= 1970 && $i < 2038 )
      $ret .= '
              <option value="' . $i . '"'
       . ( $i == $y ? SELECTED : '' ) . ">$i" . '</option>';
  }

  $ret .= '
            </select>'
   . ( $menu == false ? '
            <input type="submit" value="' . $goStr . '" />' : '' ) . '
          </form></td></tr></table>';

  return $ret;
}

/* vim: set expandtab: */

?>
