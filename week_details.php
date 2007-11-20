<?php
/* $Id$ */
include_once 'includes/init.php';
send_no_cache_header ();

if ( ! $WC->isLogin( $user ) && $WC->isNonuserAdmin() )
  $layers = loadLayers ($user);
else
  $layers = loadLayers ();



$next = mktime ( 0, 0, 0, $thismonth, $thisday + 7, $thisyear );
$prev = mktime ( 0, 0, 0, $thismonth, $thisday - 7, $thisyear );

$wkstart = get_weekday_before ( $thisyear, $thismonth, $thisday +1 );

$wkend = $wkstart + ( ONE_DAY * ( ! getPref ( 'DISPLAY_WEEKENDS' )? 5 : 7 ));
$thisdate = date ( 'Ymd', $wkstart );

$week_start = getPref ( 'WEEK_START' );
if ( ! getPref ( 'DISPLAY_WEEKENDS'  ) ) {
  if ( $week_start == 1 ) {
    $start_ind = 0;
    $end_ind = 4;
  } else {
    $start_ind = 1;
    $end_ind = 5;
  }
} else {
  $start_ind = 0;
  $end_ind = 6;
}

$previousStr = translate ( 'Previous' );
$nextStr = translate ( 'Next' );

$prevDate = date ('Ymd', $prev );
$nextDate = date ('Ymd', $next );
$dateStr = date_to_str ( date ( 'Ymd', $wkstart ), '', false ) 
  . '&nbsp;&nbsp;&nbsp; - &nbsp;&nbsp;&nbsp;' 
  . date_to_str ( date ( 'Ymd', $wkend ), '', false );
	

build_header ();

/* Pre-Load the repeated events for quckier access */
$repeated_events = read_repeated_events ( $WC->userLoginId(), 
  $wkstart, $wkend, $WC->catId() );

/* Pre-load the non-repeating events for quicker access */
$events = read_events ( $WC->userLoginId(), 
  $wkstart, $wkend, $WC->catId()  );

echo <<<EOT
    <div class="title">
      <a title="{$previousStr}" class="prev" href="week_details.php?{$u_url}date={$prevDate}{$caturl}">
	    <img src="images/leftarrow.gif" alt="{$previousStr}" /></a>
     <a title="{$nextStr}" class="next" href="week_details.php?{$u_url}date={$nextDate}{$caturl}">
	   <img src="images/rightarrow.gif" alt="{$nextStr}" /></a>
     <span class="date">{$dateStr}</span>

EOT;

if (  $week_start == 0 && ! getPref ( 'DISPLAY_WEEKENDS' ) ) 
  $wkstart = $wkstart - ONE_DAY;
for ( $i = 0; $i < 7; $i++ ) {
  $days[$i] = ( $wkstart + ONE_DAY * $i ) + ( 12 * ONE_HOUR );
  $weekdays[$i] = weekday_name ( ( $i + $week_start ) % 7, $DISPLAY_LONG_DAYS );
  $header[$i] = $weekdays[$i] . ' ' .
    date_to_str ( date ( 'Ymd', $days[$i] ), getPref ( 'DATE_FORMAT_MD' ), false );
}

if ( getPref ( 'DISPLAY_WEEKNUMBER' ) ) {
  echo "<br />\n<span class=\"titleweek\">(" .
    translate ( 'Week' ) . ' ' . date ( 'W', $wkstart + ONE_DAY ) . ')</span>';
}
?>
<span class="user"><?php
  if ( ! _WC_SINGLE_USER ) {
    echo "<br />$user_fullname\n";
  }
  if ( $WC->isNonuserAdmin() )
    echo '<br />-- ' . translate( 'Admin mode' ) . ' --';
  if ( $is_assistant )
    echo '<br />-- ' . translate( 'Assistant mode' ) . ' --';
?></span>
<?php
  if ( getPref ( '_ENABLE_CATEGORIES' ) ) {
    echo "<br /><br />\n";
    echo print_category_menu('week', sprintf ( "%04d%02d%02d",$thisyear, $thismonth, $thisday ), $WC->catId() );
  } ?>
</div>
<br />
<center>
<table class="main">
<?php
$untimed_found = false;
for ( $d = 0; $d < 7; $d++ ) {
  $date = date ( 'Ymd', $days[$d] );
  $thiswday = date ( 'w', $days[$d] );
  $is_weekend = ( $thiswday == 0 || $thiswday == 6 );
  if ( $is_weekend && ! getPref ( 'DISPLAY_WEEKENDS' ) ) continue;
  echo '<tr><th';
  if ( $date == date ( 'Ymd', $today ) ) {
    echo ' class="today">';
  } elseif ( $is_weekend ) {
    echo ' class="weekend">';
  } else {
    echo '>';
  }

  if ( $can_add ) {
    echo '<a title="' .
      translate( 'New Entry' ) . '" href="edit_entry.php?' . 
      $u_url . 'date=' . 
      date ( 'Ymd', $days[$d] ) . '"><img src="images/new.gif" class="new" alt="' .
      translate( 'New Entry' ) . "\" /></a>\n";
  }
  echo '<a title="' .
    $header[$d] . '" href="day.php?' . 
    $u_url . 'date=' . 
    date ('Ymd', $days[$d] ) . "$caturl\">" .
    $header[$d] . "</a></th>\n</tr>\n";

  echo "<tr>\n<td";
  if ( $date == date ( 'Ymd', $today ) ) {
    echo ' class="today">';
  } elseif ( $is_weekend ) {
    echo ' class="weekend">';
  } else {
    echo '>';
  }

  print_det_date_entries ( $date, $user, true );
  echo '&nbsp;';
  echo "</td></tr>\n";
}
?>
</table>
</center>
<?php
if ( ! empty ( $eventinfo ) ) echo $eventinfo;
echo '<br />';

echo print_trailer(); 


/**
 * Prints the HTML for one event in detailed view.
 *
 * @param Event $event The event
 * @param string $date The date for which we're printing (in YYYYMMDD format)
 *
 */
function print_detailed_entry ( $event, $date ) {
  global $eventinfo, $WC, $user;
  static $key = 0;

  global $layers;

  if ( ! $WC->isLogin( $event->getLoginId() ) && 
    strlen ( $event->getLogin() ) ) {
    $class = 'layerentry';
  } else {
    $class = 'entry';
    if ( $event->getStatus() == 'W' ) $class = 'unapprovedentry';
  }

  if ( $event->getPriority() == 3 ) echo '<strong>';

  if ( $event->getExtForID() != '' ) {
    $eid = $event->getExtForID();
    $name = $event->getName() . ' (' . translate ( 'cont.' ) . ')';
  } else {
    $eid = $event->getId();
    $name = $event->getName();
  }

  $popupid = "eventinfo-pop$eid-$key";
  $linkid  = "pop$eid-$key";

  $key++;

  echo '<a title="' . translate( 'View this entry' ) . 
    "\" class=\"$class\" id=\"$linkid\"  href=\"view_entry.php?eid=$eid&amp;date=$date";
  if ( strlen ( $user ) > 0 ) {
    echo '&amp;user=' . $user;
  } else if ( $class == 'layerentry' ) {
    echo '&amp;user=' . $event->getLoginId();
  }
  echo '<img src="images/circle.gif" class="bullet" alt="view icon" />';
  if ( ! $WC->isLogin( $event->getLoginId() ) && strlen ( $event->getLoginId() ) ) {
    if ($layers) foreach ($layers as $layer) {
      if($layer['cal_layeruser'] == $event->getLoginId()) {
        $in_span = true;
        echo '<span style="color:#' . $layer['cal_color'] . ';">';
      }
    }
  }

  $timestr = '';

 if ( $event->isAllDay() ) {
  $timestr = translate( 'All day event' );
 } else if ( $event->getDuration() > 0 ) {
  $timestr = display_time ( $event->getDateTime() ) .
   ' - ' . display_time ( $event->getEndDate( 'YmdHis' ) );
  echo $timestr . '&raquo;&nbsp;';
 }

  if ( ! $WC->isLogin( $user ) && 
    $event->getAccess() == 'R' && strlen ( $user ) ) {
    $PN =  $PD = '(' . translate ( 'Private' ) . ')';
  } elseif ( ! $WC->isLogin( $event->getLoginId() ) && 
    $event->getAccess() == 'R' && strlen ( $event->getLoginId() ) ) {
    $PN = $PD = '(' . translate ( 'Private' ) . ')';
  } elseif ( ! $WC->isLogin( $event->getLoginId() ) && 
    strlen ( $event->getLoginId() ) ) {
    $PN = htmlspecialchars ( $name );
    $PD = activate_urls ( htmlspecialchars ( $event->getDescription() ) );
  } else {
    $PN = htmlspecialchars ( $name );
    $PD = activate_urls ( htmlspecialchars ( $event->getDescription() ) );
  }
  if ( ! empty ( $in_span ) ) 
   $PN .= '</span>';

  echo $PN;
  echo '</a>';
  if ( $event->getPriority() == 3 ) echo '</strong>';
  # Only display description if it is different than the event name.
  if ( $PN != $PD )
    echo " - " . $PD;
  echo "<br />\n";
  $eventinfo .= build_entry_popup ( $popupid, $event->getLoginId(),
    $event->getDescription(), $timestr, site_extras_for_popup ( $eid ) );
}

//
// Print all the calendar entries for the specified user for the
// specified date.  If we are displaying data from someone other than
// the logged in user, then check the access permission of the entry.
// params:
//   $date - date in YYYYMMDD format
//   $user - username
//   $is_ssi - is this being called from week_ssi.php?
function print_det_date_entries ( $date, $user, $ssi ) {
  global $events;

  $year = substr ( $date, 0, 4 );
  $month = substr ( $date, 4, 2 );
  $day = substr ( $date, 6, 2 );

  $dateu = mktime ( 0, 0, 0, $month, $day, $year );

  // get all the repeating events for this date and store in array $rep
  $rep = get_repeating_entries ( $user, $date );

  // get all the non-repeating events for this date and store in $ev
  $ev = get_entries ( $date );

  // combine and sort the event arrays
  $ev = combine_and_sort_events($ev, $rep);
  for ( $i = 0, $cnt = count ( $ev ); $i < $cnt; $i++ ) {
    if ( getPref ( 'DISPLAY_UNAPPROVED' ) || $ev[$i]->getStatus() == 'A' )
      print_detailed_entry ( $ev[$i], $date );
  }
}
?>
