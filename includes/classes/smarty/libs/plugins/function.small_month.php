<?php

function smarty_function_small_month ($params, &$smarty) {
  global $WC,
  $MINI_TARGET, // Used by minical.php
  $user;
  
  $monthURL = 'month.php?';
  $display_weeknumber = ( isset ( $params['showweeknum'] ) && 
	  $params['showweeknum'] && getPref ( 'DISPLAY_WEEKNUMBER' ) );
  $bold_days_in_year = getPref ( 'BOLD_DAYS_IN_YEAR' );
  $display_all_days_in_month = getPref ( 'DISPLAY_ALL_DAYS_IN_MONTH' );
  $weekstart = getPref ( 'WEEK_START' );
  $nextStr = translate ( 'Next' );
  $previousStr = translate ( 'Previous' );
  $weekStr = translate ( 'Week' );

  $u_url = $WC->getUserUrl();
  $caturl = $WC->getCatUrl();
  
	$showyear  = ( isset ( $params['showyear'] ) ?$params['showyear'] : false );
	$tid = ( isset ( $params['tid'] ) ? ' id="' . $params['tid'] . '"' : '' );
	$get_unapproved = ( isset ( $params['get_unapproved'] ) ? 
	  $params['get_unapproved'] : false );
	
  $year = substr ( $params['dateYmd'], 0, 4 );
  $month = substr ( $params['dateYmd'], 4,2 );
  // Start the minical table for each month.
  $ret = '
    <table class="minical"' .$tid . '>';

  $monthstart = date ( 'Ymd', mktime ( 0, 0, 0, $month, 1, $year ) );
  $monthend = date ( 'Ymd', mktime ( 0, 0, 0, $month + 1, 0, $year ) );
  // Determine if the week starts on Sunday or Monday.
  // TODO:  We need to be able to start a week on ANY day.
  $wkstart = get_weekday_before ( $year, $month );
  $date_formatted = smarty_modifier_date_to_str ( mktime ( 0, 0, 0, $month, 3, $year ),
    ( $showyear ? 'DATE_FORMAT_MY' : '__month__' ), false );
  if ( _WC_SCRIPT == 'day.php' ) {
    $month_ago =
    date ( 'Ymd', mktime ( 0, 0, 0, $month - 1, 1, $year ) );
    $month_ahead =
    date ( 'Ymd', mktime ( 0, 0, 0, $month + 1, 1, $year ) );

    $ret .= '<caption>' . $WC->thisday . '</caption>
      <thead>
        <tr class="monthnav">
          <th colspan="' . ( $display_weeknumber ? 8 : 7 ) . '">
            <a title="' . $previousStr . '" class="prev" href="day.php?' . $u_url
     . 'date=' . $month_ago . $caturl
     . '"><img src="images/leftarrowsmall.gif" alt="' . $previousStr . '" /></a>
            <a title="' . $nextStr . '" class="next" href="day.php?' . $u_url
     . 'date=' . $month_ahead . $caturl
     . '"><img src="images/rightarrowsmall.gif" alt="' . $nextStr . '" /></a>'
     . $date_formatted  . '
          </th>
        </tr>';
  } elseif ( _WC_SCRIPT == 'minical.php' ) {
    $month_ago =
    date ( 'Ymd', mktime ( 0, 0, 0, $month - 1, $WC->thisday, $year ) );
    $month_ahead =
    date ( 'Ymd', mktime ( 0, 0, 0, $month + 1, $WC->thisday, $year ) );

    $ret .= '
      <thead>
        <tr class="monthnav">
          <th colspan="7">
            <a title="' . $previousStr . '" class="prev" href="minical.php?'
     . $u_url . 'date=' . $month_ago
     . '"><img src="images/leftarrowsmall.gif" alt="' . $previousStr . '" /></a>
            <a title="' . $nextStr . '" class="next" href="minical.php?'
     . $u_url . 'date=' . $month_ahead
     . '"><img src="images/rightarrowsmall.gif" alt="' . $nextStr . '" /></a>'
     . $date_formatted  . '
          </th>
        </tr>';
  } else // Not day or minical script.  Print the month name.
    $ret .= '
      <caption><a href="' . $monthURL . $u_url . $caturl 
	  .'date=' . $year . $month . '01">'
     . $date_formatted 
     . '</a></caption>
      <thead>';

  $ret .= '
        <tr>'
  // Print the headers to display the day of the week (Sun, Mon, Tues, etc.).
  // If we're showing week numbers we need an extra column.
  . ( $display_weeknumber ? '
          <th class="empty">&nbsp;</th>' : '' );

  for ( $i = 0; $i < 7; $i++ ) {
    $thday = ( $i + $weekstart ) % 7;
    $ret .= '
          <th' . ( is_weekend ( $thday ) ? ' class="weekend"' : '' ) . '>'
     . weekday_name ( $thday, 'D' ) . '</th>';
  }
  // End the header row.
  $ret .= '
        </tr>
      </thead>
      <tbody>';
  for ( $i = $wkstart; date ( 'Ymd', $i ) <= $monthend; $i += ONE_WEEK ) {
    $ret .= '
        <tr>' . ( $display_weeknumber ? '
          <td class="weeknumber"><a class="weeknumber" ' . 'title="' . $weekStr
       . '&nbsp;' . date ( 'W', $i + ONE_DAY ) . '" ' . 'href="week.php?' . $u_url
       . 'date=' . date ( 'Ymd', $i + ONE_DAY * 2 ) . '">('
       . date ( 'W', $i + ONE_DAY * 2 ) . ')</a></td>' : '' );

    for ( $j = 0; $j < 7; $j++ ) {
      // Add 12 hours just so we don't have DST problems.
      $date = $i + ( $j * 86400 + 43200 );
      $dateYmd = date ( 'Ymd', $date );
      $hasEvents = false;
      $title = '';
      $ret .= '
          <td';

      if ( $bold_days_in_year ) {
        $ev = get_entries ( $dateYmd, $get_unapproved, true, true );
        if ( count ( $ev ) > 0 ) {
          $hasEvents = true;
          $title = $ev[0]->getName ();
        } else {
          $rep = get_repeating_entries ( $user, $dateYmd, $get_unapproved );
          if ( count ( $rep ) > 0 ) {
            $hasEvents = true;
            $title = $rep[0]->getName ();
          }
        }
      }
      if ( ( $dateYmd >= $monthstart && $dateYmd <= $monthend ) ||
          $display_all_days_in_month ) {
        $class =
        // If it's a weekend.
        ( is_weekend ( $date ) ? 'weekend' : '' )
        // If the day being viewed is today AND script = day.php.
        . ( $dateYmd == $year . $month . $WC->thisday && _WC_SCRIPT == 'day.php'
          ? ' selectedday' : '' )
        // Are there any events scheduled for this date?
        . ( $hasEvents ? ' hasevents' : '' );

        $ret .= ( $class != '' ? ' class="' . $class . '"' : '' )
         . ( $dateYmd == $WC->todayYmd ? ' id="today"' : '' )
         . '><a href="';

        if ( _WC_SCRIPT == 'minical.php' )
          $ret .= ( _WC_HTTP_AUTH
            ? 'day.php?user=' . $user
            : 'nulogin.php?login=' . $user . '&amp;return_path=day.php' )
           . '&amp;date=' . $dateYmd . '"'
           . ( ! empty ( $MINI_TARGET ) ? ' target="' . $MINI_TARGET . '"' : '' )
           . ( ! empty ( $title ) ? ' title="' . $title . '"' : '' );
        else
          $ret .= 'day.php?' . $u_url . 'date=' . $dateYmd . '"';

        $ret .= '>' . date ( 'j', $date ) . '</a></td>';
      } else {
			  //No events
				if ( getPref ( 'DISPLAY_EMPTY_CELLS' ) )
				  $ret .= ( is_weekend ( $date ) ?' class="weekend"' : '' ); 
				else
          $ret .= ' class="empty"'; 				    
        $ret .= '>&nbsp;</td>';
			}
    } // end for $j
    $ret .= '
        </tr>';
  } // end for $i
  return $ret . '
      </tbody>
    </table>';
}

?>
