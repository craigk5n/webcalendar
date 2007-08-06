<?php
/* $Id$
 *
 * Page Description:
 * This page will display a timebar for a week or month as
 * specified by timeb
 *
 * Input Parameters:
 * id (*) - specify view id in webcal_view table
 * date - specify the starting date of the view.
 *   If not specified, current date will be used.
 * friendly - if set to 1, then page does not include links or
 *   trailer navigation.
 * timeb - 1 = week, else month
 * (*) required field
 *
 * Security:
 * Must have "allow view others" enabled ($ALLOW_VIEW_OTHER) in
 *   System Settings unless the user is an admin user ($WC->isAdmin()).
 * If the view is not global, the user must be owner of the view.
 * If the view is global, then and user_sees_only_his_groups is
 * enabled, then we remove users not in this user's groups
 * (except for nonuser calendars... which we allow regardless of group).
 */
include_once 'includes/init.php';
include_once 'includes/views.php';

$error = '';
$USERS_PER_TABLE = 6;

view_init ( $eid );

$entry_slots = getPref ( 'ENTRY_SLOTS' );
$entrySlots = ( $entry_slots >144 ? 144 : $entry_slots );
$yardSlots = (int) 60/( 1440 / $entrySlots ); //number of divisions per hour
$slotValue = 60/ $yardSlots; //minutes per division
$totalHours = getPref ( 'WORK_DAY_END_HOUR' ) - getPref ( 'WORK_DAY_START_HOUR' );
$width =  ( 100/ $totalHours);
$yardWidth = round ( $width / $yardSlots, 3 ); //percentage width of each division
$totalSlots = ( $totalHours * $yardSlots ); //number of divisions full page

/**
 * Prints all the entries in a time bar format for the specified user for the
 * specified date.
 *
 * If we are displaying data from someone other than the logged in user, then
 * check the access permission of the entry.
 *
 * @param string $date Date in YYYYMMDD format
 * @param string $user Username
 * @param bool   $ssi  Should we not include links to add new events?
 */
function print_date_entries_timebar ( $date, $user, $ssi ) {
  global $events, $totalSlots;
  $ret = '';
  $cnt = 0;
  $get_unapproved = ( getPref ( 'DISPLAY_UNAPPROVED' ) );

  $year = substr ( $date, 0, 4 );
  $month = substr ( $date, 4, 2 );
  $day = substr ( $date, 6, 2 );

  $can_add = ( ! _WC_READONLY || $WC->isAdmin() );

  // get all the repeating events for this date and store in array $rep
  $rep = get_repeating_entries ( $user, $date );
  $cur_rep = 0;

  // get all the non-repeating events for this date and store in $ev
  $ev = get_entries ( $date, $get_unapproved );

  // combine and sort the event arrays
  $ev = combine_and_sort_events($ev, $rep);
  $evcnt = count ( $ev );
  for ( $i = 0; $i < $evcnt; $i++ ) {
    if ( $get_unapproved || $ev[$i]->getStatus() == 'A' ) {
      $ret .= print_entry_timebar ( $ev[$i], $date );
      $cnt++;
    }
  }
  if ( $cnt == 0 )
    $ret .= '<tr><td colspan="' .$totalSlots . '">&nbsp;</td></tr>'; // so the table cell has at least something

  return $ret;
}

/**
 * Prints the HTML for an event with a timebar.
 *
 * @param Event  $event The event
 * @param string $date  Date for which we're printing in YYYYMMDD format
 *
 * @staticvar int Used to ensure all event popups have a unique id
 */
function print_entry_timebar ( $event, $date ) {
  global $eventinfo, $WC, $user, $slotValue,
    $entrySlots, $yardSlots, $totalHours, $width, $yardWidth, $totalSlots;
  static $key = 0;
  $insidespan = false;
  $ret = '';
  $time_only = access_user_calendar ( 'time', $event->getLogin() );
  $can_access = access_user_calendar ( 'view', $event->getLogin(), '', 
    $event->getCalType(), $event->getAccess() );    
  $eid = $event->getId();
  $name = $event->getName();

  $popupid = "eventinfo-pop$eid-$key";
  $linkid  = "pop$eid-$key";
  $key++;

  $day_start= getPref ( 'WORK_DAY_START_HOUR' ) * 60;
  $day_end= getPref ( 'WORK_DAY_END_HOUR' ) * 60;
  if ( $day_end <= $day_start ) $day_end = $day_start + 60; //avoid exceptions

  $time = date ( 'His', $event->getDate() );
  $startminutes = time_to_minutes ( $time );
  $endminutes = time_to_minutes ( date ( 'His', $event->getEndDate() ) );
  $duration = $event->getDuration(); 
  if ( $event->isAllDay() ) {
    // All day event
    $start_padding = 0;
    $ev_duration = $totalSlots;
  } else  if ( $event->isUntimed() ) {
    $start_padding = 0;
    $ev_duration = 0; 
  } else {  //must be timed
    $start_padding = round ( ( $startminutes - $day_start ) / $slotValue );
    if ($start_padding < 0) $start_padding = 0;
    if ( $startminutes > $day_end || $endminutes < $day_start ) {
      $ev_duration = 1; 
    } else  if ( $duration > 0 ) {
      $ev_duration = (int) ( $duration / $slotValue );
      // event starts before workday
      if ( $startminutes < $day_start ) {
        $ev_duration = $ev_duration - (  (int)( $day_start - $startminutes )/ $slotValue );
      } 
      // event ends after workday
      if ( $endminutes > $day_end ) {
        $ev_duration = $ev_duration - (  (int)( $endminutes - $day_end )/ $slotValue );
      }
    } 
  }
  $end_padding = $totalSlots - $start_padding - $ev_duration + 1;
  //if event is past viewing area
  if ( $start_padding >= $totalSlots ) {
    $start_padding = $totalSlots -1;
    $ev_duration =1;
  }
  // choose where to position the text (pos=0->before,pos=1->on,pos=2->after)
  if ( $ev_duration / $totalSlots >= .3 )   { $pos = 1; }
   elseif ( $end_padding / $totalSlots >= .3 )   { $pos = 2; }
   else        { $pos = 0; }
 
  $ret .= "\n<!-- ENTRY BAR -->\n\n";
  $ret .= "<tr class=\"entrycont\" >\n";
  $fill = ( $start_padding == 0 && $ev_duration ==1 ? '&nbsp;' : '&nbsp;' );
  $ret .= ($start_padding > 0 ?  "<td class=\"alignright\" colspan=\"$start_padding\">": '' );
  if ( $pos > 0 ) {
    if ( ! $event->isUntimed() ) {
      $ret .= ($start_padding > 0 ?  "&nbsp;</td>\n": '' );
      $ret .= "<td  class=\"entry\" colspan=\"$ev_duration\">\n";
      if ( $pos > 1 ) {
        $ret .= "$fill</td>\n";
        $ret .= "<td class=\"alignleft\" colspan=\"$end_padding\">";
      }
    } else { // Untimed, just display text
      $ret .= '<td colspan="' . $totalSlots . '">';
    }
  } 

  if ( $event->getPriority() == 3 ) $ret .= '<strong>';

  if ( $can_access != 0 && $time_only != 'Y' ) {
    //make sure clones have parents url date
    $linkDate = (  $event->getClone()?$event->getClone(): $date ); 
    $ret .= "<a class=\"entry\" id=\"$linkid\" " . 
      " href=\"view_entry.php?eid=$eid&amp;date=$linkDate";
    if ( strlen ( $user ) > 0 )
      $ret .= "&amp;user=" . $user;
    $ret .= '">';
  }

  $ret .= '[' . $event->getLogin() . ']&nbsp;';
  $timestr = '';
  if ( $event->isAllDay() ) {
    $timestr = translate('All day event');
  } else if ( ! $event->isUntimed() ) {
    $timestr = smarty_modifier_display_time ( $event->getDatetime() );
    if ( $event->getDuration() > 0 ) {
      $timestr .= ' - ' 
			. smarty_modifier_display_time ( $event->getEndDate( 'YmdHis' ), 2 );
    }
  }
  $ret .= build_entry_label ( $event, $popupid, $can_access, $timestr, $time_only );

  if ( $insidespan ) { $ret .= ('</span>'); } //end color span
  $ret .= '</a>';
  if ( $event->getPriority() == 3 ) $ret .= '</strong>'; //end font-weight span
  $ret .= "</td>\n";
  if ( $pos < 2 ) {
    if ( $pos < 1 ) {
      $fill = ( $ev_duration ==1 ? '&nbsp;' : '&nbsp;' );
      $ret .= "<td class=\"entry\" colspan=\"$ev_duration\">$fill</td>\n";
    }
    $ret .= ($end_padding > 1 ? "<td class=\"alignleft\" colspan=\"$end_padding\">&nbsp;</td>\n": '' );
  }
  //we'll close the table later
  $ret .= "</tr>\n";

  return $ret;
}

/**
 * Prints the header for the timebar.
 *
 */
function print_header_timebar() {
  global $entrySlots, $yardSlots, $totalHours, $width, $yardWidth, $totalSlots;
  //      sh   ...   eh
  // +------+----....----+------+
  // |      |            |      |
  $ret = ''; 
 // print hours
  $ret .= "\n<!-- TIMEBAR -->\n<table class=\"timebar\">\n<tr>\n";
  $work_day_end_hour = getPref ( 'WORK_DAY_END_HOUR' );
  for ($i = getPref ( 'WORK_DAY_START_HOUR' ); $i < $work_day_end_hour; $i++) {
    $hour = ( $i <= 12 || getPref ( 'TIME_FORMAT' ) == 24 ? $i : $i%12 );
    if ( $hour == 0 ) $hour = 12;
    $ret .= "<td colspan=\"$yardSlots\">$hour</td>\n";
  }
  $ret .= "</tr>\n";
 
  // print yardstick
  $ret .= "\n<!-- YARDSTICK -->\n<tr class=\"yardstick\">\n";
  for ($i = 0; $i < ( $totalSlots ); $i++) {
    $ret .= "<td width=\"$yardWidth%\">&nbsp;</td>\n";
  }
  //we'll close the table later
  $ret .= "</tr>\n\n<!-- /YARDSTICK -->\n";

  return $ret;
}

$date = ! empty ($date) ? $date : date ( 'Ymd' );
// Initialize date to first of current month
if ( empty ( $timeb ) || $timeb == 0 ) {
  $date = substr($date,0,6).'01';
}

$WC->setToday ( $date );

// Week timebar
if ( ! empty ( $timeb) && $timeb == 1 ) {
  $next = mktime ( 0, 0, 0, $thismonth, $thisday + 7, $thisyear );
  $prev = mktime ( 0, 0, 0, $thismonth, $thisday - 7, $thisyear );
  $wkstart = get_weekday_before ( $thisyear, $thismonth, $thisday +1 );
  $wkend = $wkstart + ( ONE_DAY * 6 );
  $val_boucle = 7;
} else {
  $next = mktime ( 0, 0, 0, $thismonth + 1, $thisday, $thisyear );
  $prev = mktime ( 0, 0, 0, $thismonth - 1, $thisday, $thisyear );
  $wkstart = mktime ( 0, 0, 0, $thismonth, 1, $thisyear );
  $wkend = mktime ( 23, 59, 59, $thismonth + 1, 0, $thisyear );
  $val_boucle = date ('t', $wkstart);
}
$nextyear = date ( 'Y', $next );
$nextmonth = date ( 'm', $next );
$nextday = date ( 'd', $next );
$nextdate = sprintf ( "%04d%02d%02d", $nextyear, $nextmonth, $nextday );

$prevyear = date ( 'Y', $prev );
$prevmonth = date ( 'm', $prev );
$prevday = date ( 'd', $prev );
$prevdate = sprintf ( "%04d%02d%02d", $prevyear, $prevmonth, $prevday );

// get users in this view
$viewusers = view_get_user_list ( $eid );
$viewusercnt = count ( $viewusers );
if ( $viewusercnt == 0 ) {
  // This could happen if user_sees_only_his_groups  = Y and
  // this user is not a member of any  group assigned to this view
  $error = translate ( 'No users for this view' );
}

$display_weekends = getPref ( 'DISPLAY_WEEKENDS' );
$add_link_in_views = getPref ( 'ADD_LINK_IN_VIEWS' );
$display_long_days = getPref ( 'DISPLAY_LONG_DAYS' );

$previousStr = translate ( 'Previous' );
$nextStr = translate ( 'Next' );
$dateStr = date_to_str ( date ( 'Ymd', $wkstart ), '', false ) .
  '&nbsp;&nbsp;&nbsp; - &nbsp;&nbsp;&nbsp;' .
  date_to_str ( date ( 'Ymd', $wkend ), '', false );
$view_name = htmlspecialchars ( $view_name );

$INC = array('popups.js');
build_header ($INC);

if ( ! empty ( $error ) ) {
  echo print_error ( $error );
  echo print_trailer ();
  exit;
}

echo <<<EOT
   <div style="width:99%;">
     <a title="{$previousStr}" class="prev" href="view_t.php?timeb={$timeb}&amp;id={$eid}&amp;date={$prevdate}">
	   <img src="images/leftarrow.gif" alt="{$previousStr}" /></a>
     <a title="{$nextStr}" class="next" href="view_t.php?timeb={$timeb}&amp;id={$eid}&amp;date={$nextdate}">
	   <img src="images/rightarrow.gif" alt="{$nextStr}" /></a>
     <div class="title">
      <span class="date">{$dateStr}</span><br />
      <span class="viewname">{$view_name}</span>
     </div>
  </div><br /><br />
  
EOT;

// The table has names across the top and dates for rows.  Since we need
// to spit out an entire row before we can move to the next date, we'll
// save up all the HTML for each cell and then print it out when we're
// done..
// Additionally, we only want to put at most 6 users in one table since
// any more than that doesn't really fit in the page.


$e_save = array ();
$re_save = array ();
for ( $i = 0; $i < $viewusercnt; $i++ ) {
  /* Pre-Load the repeated events for quckier access */
  $repeated_events = read_repeated_events ( $viewusers[$i], $wkstart, $wkend, '' );
  $re_save = array_merge($re_save, $repeated_events);
  /* Pre-load the non-repeating events for quicker access 
      subtracting ONE_WEEK to allow cross-day events to display*/
  $events = read_events ( $viewusers[$i], $wkstart - ONE_WEEK, $wkend );
  $e_save = array_merge($e_save, $events);
}
$events = $e_save;
$repeated_events = $re_save;
$timeBarHeader = print_header_timebar ();
?>

<table class="main">
<?php
for ( $date = $wkstart; $date <= $wkend; $date += ONE_DAY ) {
  $dateYmd = date ( 'Ymd', $date );
  $is_weekend = is_weekend ( $date );
  if ( $is_weekend && ! $display_weekends ) continue; 
  $weekday = weekday_name ( date ( 'w', $date ), $display_long_days );
  if ( $dateYmd == date ( 'Ymd', $today ) ) {
    echo '<tr><th class="today">';
  } else if ( $is_weekend ) {
      echo '<tr class="weekend"><th class="weekend">';
  } else {
    echo '<tr><th class="row">';
  }
  if ( $add_link_in_views )  {
    echo html_for_add_icon ( $dateYmd, '', '', $user );
  }
  echo $weekday . '&nbsp;' . date ( 'd', $date ) . "</th>\n";
  echo '<td class="timebar">'; 
  echo $timeBarHeader;
  echo print_date_entries_timebar ( $dateYmd, $WC->loginId(), true );
  echo '</table></td>';
  echo "</tr>\n";
}

echo "</table>\n";

$user = ''; // reset

if ( ! empty ( $eventinfo ) ) {
  echo $eventinfo;
}

echo print_trailer (); 
?>

