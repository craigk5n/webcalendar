<?php
/* Generate HTML to create a month display.
 */
function smarty_function_display_month ( $params, &$smarty ) {
  global $user, $WC;
  
  $display_weeknumber = getPref ( 'DISPLAY_WEEKNUMBER' );
  $display_long_days  = getPref ( 'DISPLAY_LONG_DAYS' );
  $display_all_days_in_month = getPref ( 'DISPLAY_ALL_DAYS_IN_MONTH' );
  //if no params are passed, we must be calling a demo 
  if ( empty ( $params ) ) {
	  $thismonth = date ('m');
	  $thisyear = date ( 'Y' );
		$demo = true;
	} else {
		$thismonth = $params['thismonth'];
	  $thisyear = $params['thisyear'];
	  $demo = false;
	}
	//Don't add cat_id if None ( -1 ) is selected
  $urlparams = $WC->getUserUrl('?') . $WC->getCatUrl( -1 );
  $urlparams = ( $urlparams ? $urlparams . '&amp;' : '' );
		
  for ( $i = 0; $i < 7; $i++ ) {
    $thday = ( $i + getPref ( 'WEEK_START' ) ) % 7;
    $th[$i]['class'] = ( is_weekend ( $thday ) ? 'class="weekend"' : '' );
	  $th[$i]['name']  = weekday_name ( $thday, $display_long_days );
  }

  $charset = translate ( 'charset' );
  $weekStr = translate ( 'Week' );
  $WKStr = translate ( 'WK' );

  $wkstart = get_weekday_before ( $thisyear, $thismonth );
  // Generate values for first day and last day of month.
  $monthstart = date ( 'Ymd', mktime ( 0, 0, 0, $thismonth, 1, $thisyear ) );
  $monthend = date ( 'Ymd', mktime ( 0, 0, 0, $thismonth + 1, 0, $thisyear ) );
$ct=0;
  for ( $i = $wkstart; date ( 'Ymd', $i + 43200 ) <= $monthend;
    $i += ( ONE_WEEK ) ) {
    $iDate = date ( 'Ymd', $i + ONE_DAY );
    if ( $display_weeknumber ) {
      $tr[$ct]['title'] = $weekStr . '&nbsp;'
       . date ( 'W', $i + ONE_DAY * 2 );
	  $tr[$ct]['href'] = ( $demo ? '' : 'week.php?date=' 
	    . $iDate ) . $WC->getUserUrl() . $WC->getCatUrl();

      $wkStr = $WKStr . date ( 'W', $i + ONE_DAY * 2 );
      $wkStr2 = '';

      if ( $charset == 'UTF-8' )
        $wkStr2 = $wkStr;
      else {
        for ( $w = 0, $cnt = strlen ( $wkStr ); $w < $cnt; $w++ ) {
          $wkStr2 .= substr ( $wkStr, $w, 1 ) . '<br />';
        }
      }
      $tr[$ct]['weekStr'] = $wkStr2;
    }    
    for ( $j = 0; $j < 7; $j++ ) {
      $date = $i + ( $j * ONE_DAY + 43200 );
      $dateYmd = date ( 'Ymd', $date );
      $dateD = date ( 'd', $date );
      $thiswday = date ( 'w', $date );
      $is_weekend = is_weekend ( $date );
      $currMonth = ( $dateYmd >= $monthstart && $dateYmd <= $monthend );
      if ( $currMonth || $display_all_days_in_month ) {
        $class = ( ! $demo && 
		  $dateYmd == $WC->todayYmd ? 'today' : '' )
         . ( $is_weekend ? ' weekend' : '' )
         . ( ! $currMonth ? ' othermonth' : '' );

        // Get events for this day.
        $ret_events = '';
        if ( ! $demo )
          $ret_events = month_labels ( $dateYmd, $urlparams );
        else {
          // Since we base this calendar on the current month,
          // the placement of the days always change so
          // set 3rd Thursday as "today" for the demo...
          if ( $dateD >= 16 && $dateD < 23 && $thiswday == 4 ) {
            $class = 'today';
            $ret_events = translate ( 'Today' );
          }
          // ... and set 2nd Saturday and 2nd Tuesday as the demo event days.
          if ( $dateD >= 8 && $dateD <= 15 &&
            ( $thiswday == 2 || $thiswday == 6 ) ) {
            $class .= ' entry hasevents';
            $ret_events = translate ( 'My event text' );
          }
        }
        $td[$ct][$j]['class'] = ( strlen ( $class ) 
		  ? ' class="' . $class . '"' : '' );
		$td[$ct][$j]['data']  = $ret_events;
      } else {
        $td[$ct][$j]['class'] = ( $is_weekend ? ' class="weekend"' : '' );
		$td[$ct][$j]['data']  =  '&nbsp;';
	  }
	  $td[$ct][$j]['id']  =  $dateYmd;
    }
	$ct++;
  }
  $smarty->assign ('th', $th );
  $smarty->assign ('th', $th );
  $smarty->assign ('tr', $tr );
  $smarty->assign ('td', $td );
  $smarty->display ("display_month.tpl");
}

/* Prints all the calendar dates and 'Add entry' icons
 *
 * @param string $date  Date in YYYYMMDD format
 */
function month_labels ( $date, $urlparams ) {
  global $WC;
  static $newEntryStr;

  if ( empty ( $newEntryStr ) )
    $newEntryStr = translate ( 'New Entry' );

  $moons = getMoonPhases( $date );
 
  
  $ret = '
    <a class="dayofmonth" href="day.php?' . $urlparams . 'date=' 
	. $date . '">' . substr ( $date, 6, 2 ) . '</a>' . $moons[$date];

  return $ret;
}

?>
