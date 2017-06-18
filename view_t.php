<?php
/* $Id: view_t.php,v 1.83.2.3 2008/11/25 22:38:56 cknudsen Exp $
 *
 * Page Description:
 * This page will display a timebar for a week or month as specified by timeb.
 *
 * Input Parameters:
 * id (*)    - specify view id in webcal_view table
 * date      - specify the starting date of the view.
 *             If not specified, current date will be used.
 * friendly  - if set to 1, then page does not include links or trailer navigation.
 * timeb     - 1 = week, else month
 * (*) required field
 *
 * Security:
 * Must have "allow view others" enabled ($ALLOW_VIEW_OTHER) in System Settings
 * unless the user is an admin user ($is_admin).
 * If the view is not global, the user must be owner of the view.
 * If the view is global, then and user_sees_only_his_groups is enabled,
 * then we remove users not in this user's groups
 * (except for nonuser calendars... which we allow regardless of group).
 */
include_once 'includes/init.php';
include_once 'includes/views.php';

$error = '';
$USERS_PER_TABLE = 6;

view_init ( $id );

$entrySlots = ( $ENTRY_SLOTS > 144 ? 144 : $ENTRY_SLOTS );
$yardSlots = ( int ) 60 / ( 1440 / $entrySlots ); //Number of divisions per hour.
$slotValue = 60 / $yardSlots; //Minutes per division.
$totalHours = $WORK_DAY_END_HOUR - $WORK_DAY_START_HOUR;
$width = ( 100 / $totalHours );
$yardWidth = round ( $width / $yardSlots, 3 ); //Percentage width of each division.
$totalSlots = ( $totalHours * $yardSlots ); //Number of divisions full page.

/* Prints all the entries in a time bar format for the specified user for the
 * specified date.
 *
 * If we are displaying data from someone other than the logged in user,
 * then check the access permission of the entry.
 *
 * @param string $date Date in YYYYMMDD format
 * @param string $user Username
 * @param bool   $ssi  Should we not include links to add new events?
 */
function print_date_entries_timebar ( $date, $user, $ssi ) {
  global $DISPLAY_UNAPPROVED, $events, $is_admin, $PUBLIC_ACCESS,
  $PUBLIC_ACCESS_CAN_ADD, $readonly, $totalSlots;
  $ret = '';
  $cnt = 0;
  $get_unapproved = ( $DISPLAY_UNAPPROVED == 'Y' );

  $year = substr ( $date, 0, 4 );
  $month = substr ( $date, 4, 2 );
  $day = substr ( $date, 6, 2 );

  $can_add = ( $readonly == 'N' || $is_admin );
  if ( $PUBLIC_ACCESS == 'Y' && $PUBLIC_ACCESS_CAN_ADD != 'Y' &&
      $GLOBALS['login'] == '__public__' )
    $can_add = false;

  $cur_rep = 0;

  // Combine and sort the event arrays.
  $ev = combine_and_sort_events (
    get_entries ( $date, $get_unapproved ),
    get_repeating_entries ( $user, $date ) );
  $evcnt = count ( $ev );
  for ( $i = 0; $i < $evcnt; $i++ ) {
    if ( $get_unapproved || $ev[$i]->getStatus () == 'A' ) {
      $ret .= print_entry_timebar ( $ev[$i], $date );
      $cnt++;
    }
  }
  return $ret . ( $cnt == 0 ? '
            <tr>
              <td colspan="' . $totalSlots . '">&nbsp;</td>
            </tr>' // So the table cell has at least something.
    : '' );
}

/* Prints the HTML for an event with a timebar.
 *
 * @param Event  $event The event
 * @param string $date  Date for which we're printing in YYYYMMDD format
 *
 * @staticvar int Used to ensure all event popups have a unique id
 */
function print_entry_timebar ( $event, $date ) {
  global $ENTRY_SLOTS, $entrySlots, $eventinfo, $login, $PHP_SELF,
  $PUBLIC_ACCESS_FULLNAME, $slotValue, $totalHours, $totalSlots, $user, $width,
  $WORK_DAY_END_HOUR, $WORK_DAY_START_HOUR, $yardSlots, $yardWidth;
  static $key = 0;

  $insidespan = false;
  $ret = '';
  if ( access_is_enabled () ) {
    $temp = $event->getLogin ();
    $can_access = access_user_calendar ( 'view', $temp, '',
      $event->getCalType (), $event->getAccess () );
    $time_only = access_user_calendar ( 'time', $temp );
  } else {
    $can_access = CAN_DOALL;
    $time_only = 'N';
  }
  $id = $event->getID ();
  $name = $event->getName ();

  $linkid = "pop$id-$key";
  $key++;

  $day_start = $WORK_DAY_START_HOUR * 60;
  $day_end = $WORK_DAY_END_HOUR * 60;
  if ( $day_end <= $day_start )
    $day_end = $day_start + 60; //Avoid exceptions.
  $time = date ( 'His', $event->getDateTimeTS () );
  $startminutes = time_to_minutes ( $time );
  $endminutes = time_to_minutes ( date ( 'His', $event->getEndDateTimeTS () ) );
  $duration = $event->getDuration ();
  if ( $event->isAllDay () ) {
    // All day event.
    $ev_duration = $totalSlots;
    $start_padding = 0;
  } else if ( $event->isUntimed () )
    $ev_duration = $start_padding = 0;
  else { // Must be timed.
    $start_padding = round ( ( $startminutes - $day_start ) / $slotValue );
    if ( $start_padding < 0 )
      $start_padding = 0;

    if ( $startminutes > $day_end || $endminutes < $day_start )
      $ev_duration = 1;
    else if ( $duration > 0 ) {
      $ev_duration = intval ( $duration / $slotValue );
      // Event starts before workday.
      if ( $startminutes < $day_start )
        $ev_duration = $ev_duration -
        ( ( int )( $day_start - $startminutes ) / $slotValue );

      // Event ends after workday.
      if ( $endminutes > $day_end )
        $ev_duration = $ev_duration -
        ( ( int )( $endminutes - $day_end ) / $slotValue );
    }
  }
  $end_padding = $totalSlots - $start_padding - $ev_duration + 1;
  // If event is past viewing area.
  if ( $start_padding >= $totalSlots ) {
    $ev_duration = 1;
    $start_padding = $totalSlots -1;
  }
  // Choose where to position the text (pos=0->before,pos=1->on,pos=2->after).
  if ( $ev_duration / $totalSlots >= .3 )
    $pos = 1;
  elseif ( $end_padding / $totalSlots >= .3 )
    $pos = 2;
  else
    $pos = 0;

  $ret .= '
<!-- ENTRY BAR -->
            <tr class="entrycont">' . ( $start_padding > 0 ? '
              <td class="alignright" colspan="' . $start_padding . '">' : '' );
  if ( $pos > 0 ) {
    if ( ! $event->isUntimed () ) {
      $ret .= ( $start_padding > 0 ? '&nbsp;</td>': '' ) . '
              <td class="entry" colspan="' . $ev_duration . '">'
       . ( $pos > 1 ? '&nbsp;</td>
              <td class="alignleft" colspan="' . $end_padding . '">' : '' );
    } else // Untimed, just display text.
      $ret .= '
              <td colspan="' . $totalSlots . '">';
  }
  $tempClone = $event->getClone ();
  $tempPri = ( $event->getPriority () < 4 );

  return $ret . ( $tempPri ? '<strong>' : '' )
  // Make sure clones have parents URL date.
  . ( $can_access != 0 && $time_only != 'Y' ? '
          <a class="entry" id="' . $linkid . '" href="view_entry.php?id='
     . $id . '&amp;date=' . ( $tempClone ? $tempClone: $date )
     . ( strlen ( $user ) > 0 ? '&amp;user=' . $user : '' ) . '">' : '' ) . '['
   . ( $event->getLogin () == '__public__'
    ? $PUBLIC_ACCESS_FULLNAME : $event->getLogin () )
   . ']&nbsp;' . build_entry_label ( $event, 'eventinfo-' . $linkid, $can_access,
    ( $event->isAllDay ()
      ? translate ( 'All day event' )
      : ( ! $event->isUntimed () ? display_time ( $event->getDatetime () )
         . ( $event->getDuration () > 0
          ? ' - ' . display_time ( $event->getEndDateTime (), 2 ) : '' ) : '' ) ),
    $time_only ) . ( $insidespan ? '</span>' : '' ) // end color span
  . '</a>' . ( $tempPri ? '</strong>' : '' ) // end font-weight span
  . '</td>' . ( $pos < 2 ? ( $pos < 1 ? '
        <td class="entry" colspan="' . $ev_duration . '">&nbsp;</td>' : '' )
     . ( $end_padding > 1 ? '
        <td class="alignleft" colspan="'
       . $end_padding . '">&nbsp;</td>' : '' ) : '' )
  // We'll close the table later.
  . '
      </tr>';
}

/* Prints the header for the timebar.
 */
function print_header_timebar () {
  global $ENTRY_SLOTS, $entrySlots, $TIME_FORMAT, $totalHours, $totalSlots,
  $width, $WORK_DAY_END_HOUR, $WORK_DAY_START_HOUR, $yardSlots, $yardWidth;
  // sh   ...   eh
  // +------+----....----+------+
  // |      |            |      |
  // Print hours.
  $ret = '
<!-- TIMEBAR -->
          <table class="timebar">
            <tr>';
  for ( $i = $WORK_DAY_START_HOUR; $i < $WORK_DAY_END_HOUR; $i++ ) {
    $hour = ( $i < 13 || $TIME_FORMAT == 24 ? $i : $i % 12 );
    if ( $hour == 0 )
      $hour = 12;
    $ret .= '
              <td colspan="' . "$yardSlots\">$hour" . '</td>';
  }
  $ret .= '
            </tr>'

  // Print yardstick.
  . '
<!-- YARDSTICK -->
            <tr class="yardstick">';
  for ( $i = 0; $i < ( $totalSlots ); $i++ ) {
    $ret .= '
              <td width="' . $yardWidth . '%">&nbsp;</td>';
  }
  // We'll close the table later.
  return $ret . '
            </tr>
<!-- /YARDSTICK -->';
}

$date = ( empty ( $date ) ? date ( 'Ymd' ) : $date );
// Initialize date to first of current month.
if ($view_type != 'S')
  $date = substr ( $date, 0, 6 ) . '01';

set_today ( $date );

// Week timebar.
if ($view_type == 'S') {
  $next = mktime ( 0, 0, 0, $thismonth, $thisday + 7, $thisyear );
  $prev = mktime ( 0, 0, 0, $thismonth, $thisday - 7, $thisyear );
  $wkstart = get_weekday_before ( $thisyear, $thismonth, $thisday + 1 );
  $wkend = $wkstart + ( 86400 * 6 );
  $val_boucle = 7;
} else {
  $next = mktime ( 0, 0, 0, $thismonth + 1, $thisday, $thisyear );
  $prev = mktime ( 0, 0, 0, $thismonth - 1, $thisday, $thisyear );
  $wkstart = mktime ( 0, 0, 0, $thismonth, 1, $thisyear );
  $wkend = mktime ( 23, 59, 59, $thismonth + 1, 0, $thisyear );
  $val_boucle = date ( 't', $wkstart );
}
$nextyear = date ( 'Y', $next );
$nextmonth = date ( 'm', $next );
$nextday = date ( 'd', $next );
$nextdate = sprintf ( "%04d%02d%02d", $nextyear, $nextmonth, $nextday );

$prevyear = date ( 'Y', $prev );
$prevmonth = date ( 'm', $prev );
$prevday = date ( 'd', $prev );
$prevdate = sprintf ( "%04d%02d%02d", $prevyear, $prevmonth, $prevday );

// Get users in this view.
$viewusers = view_get_user_list ( $id );
$viewusercnt = count ( $viewusers );
if ( $viewusercnt == 0 )
  // This could happen if user_sees_only_his_groups  = Y and
  // this user is not a member of any  group assigned to this view.
  $error = translate ( 'No users for this view' );

$printerStr = generate_printer_friendly ( 'view_t.php' );

print_header ( array ( 'js/popups.php/true' ) );

if ( ! empty ( $error ) ) {
  echo print_error ( $error ) . print_trailer ();
  exit;
}

$nextStr = translate ( 'Next' );
$prevStr = translate ( 'Previous' );

ob_start ();

echo '
    <div style="width:99%;">
      <a title="' . $prevStr . '" class="prev" href="view_t.php?id=' . $id .
	'&amp;date=' . $prevdate
 . '"><img src="images/leftarrow.gif" alt="' . $prevStr . '" /></a>
      <a title="' . $nextStr . '" class="next" href="view_t.php?id=' . $id .
	'&amp;date=' . $nextdate
 . '"><img src="images/rightarrow.gif" alt="' . $nextStr . '" /></a>
      <div class="title">
        <span class="date">' . date_to_str ( date ( 'Ymd', $wkstart ), '', false )
 . '&nbsp;&nbsp;&nbsp; - &nbsp;&nbsp;&nbsp;'
 . date_to_str ( date ( 'Ymd', $wkend ), '', false ) . '</span><br />
        <span class="viewname">' . htmlspecialchars ( $view_name ) . '</span>
      </div>
    </div><br /><br />';

// The table has names across the top and dates for rows. Since we need to
// spit out an entire row before we can move to the next date, we'll save up all
// the HTML for each cell and then print it out when we're done..
// Additionally, we only want to put at most 6 users in one table since
// any more than that doesn't really fit in the page.

$e_save = $re_save = array ();
for ( $i = 0; $i < $viewusercnt; $i++ ) {
  /* Pre-Load the repeated events for quckier access */
  $repeated_events = read_repeated_events ( $viewusers[$i], $wkstart, $wkend, '' );
  $re_save = array_merge ( $re_save, $repeated_events );
  /* Pre-load the non-repeating events for quicker access
      subtracting ONE_WEEK to allow cross-day events to display*/
  $events = read_events ( $viewusers[$i], $wkstart - 604800, $wkend );
  $e_save = array_merge ( $e_save, $events );
}
$events = $e_save;
$repeated_events = $re_save;
$timeBarHeader = print_header_timebar ();

echo '
    <table class="main">';

for ( $date = $wkstart; $date <= $wkend; $date += 86400 ) {
  $dateYmd = date ( 'Ymd', $date );
  $is_weekend = is_weekend ( $date );
  if ( $is_weekend && $DISPLAY_WEEKENDS == 'N' )
    continue;

  echo '
      <tr' . ( $dateYmd == date ( 'Ymd', $today ) ? '>
        <th class="today">' : ( $is_weekend ? ' class="weekend">
        <th class="weekend">' : '>
        <th class="row">' ) )
   . ( empty ( $ADD_LINK_IN_VIEWS ) || $ADD_LINK_IN_VIEWS != 'N'
    ? html_for_add_icon ( $dateYmd, '', '', $user ) : '' )
     . weekday_name ( date ( 'w', $date ), $DISPLAY_LONG_DAYS ) . '&nbsp;'
   . date ( 'd', $date ) . '</th>
        <td class="timebar">' . $timeBarHeader
   . print_date_entries_timebar ( $dateYmd, $login, true ) . '
          </table>
        </td>
      </tr>';
}

$user = ''; // reset

ob_end_flush ();

echo '
    </table>'
 . ( empty ( $eventinfo ) ? '' : $eventinfo ) . $printerStr . print_trailer ();

?>
