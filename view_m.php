<?php
/* $Id: view_m.php,v 1.77.2.7 2008/04/03 18:19:51 umcesrjones Exp $
 *
 * Page Description:
 * Display a month view with users side by side.
 *
 * Input Parameters:
 * id (*)   - specify view id in webcal_view table
 * date     - specify the starting date of the view.
 *            If not specified, current date will be used.
 * friendly - if set to 1, then page does not include links or trailer navigation.
 * (*) required field
 *
 * Security:
 * Must have "allow view others" enabled ($ALLOW_VIEW_OTHER) in System Settings
 * unless the user is an admin user ($is_admin). If the view is not global, the
 * user must be owner of the view. If the view is global, then and
 * user_sees_only_his_groups is enabled, then we remove users not in this user's
 * groups (except for nonuser calendars... which we allow regardless of group).
 */
include_once 'includes/init.php';
include_once 'includes/views.php';

$error = '';
$USERS_PER_TABLE = 6;

$printerStr = generate_printer_friendly ( 'view_m.php' );
set_today ( $date );
view_init ( $id );

$next = mktime ( 0, 0, 0, $thismonth + 1, 1, $thisyear );
$nextyear = date ( 'Y', $next );
$nextmonth = date ( 'm', $next );
$nextdate = sprintf ( "%04d%02d01", $nextyear, $nextmonth );

$prev = mktime ( 0, 0, 0, $thismonth - 1, 1, $thisyear );
$prevyear = date ( 'Y', $prev );
$prevmonth = date ( 'm', $prev );
$prevdate = sprintf ( "%04d%02d01", $prevyear, $prevmonth );

$startdate = mktime ( 0, 0, 0, $thismonth, 1, $thisyear );
$enddate = mktime ( 23, 59, 59, $thismonth + 1, 0, $thisyear );

$thisdate = date ( 'Ymd', $startdate );

$nextStr = translate ( 'Next' );
$prevStr = translate ( 'Previous' );

print_header ( array ( 'js/popups.php/true' ) );

echo '
    <div style="width:99%;">
      <a title="' . $prevStr . '" class="prev" href="view_m.php?id=' . $id
 . '&amp;date=' . $prevdate . '"><img src="images/leftarrow.gif" alt="'
 . $prevStr . '" /></a>
      <a title="' . $nextStr . '" class="next" href="view_m.php?id=' . $id
 . '&amp;date=' . $nextdate . '"><img src="images/rightarrow.gif" alt="'
 . $nextStr . '" /></a>
      <div class="title">
        <span class="date">';
printf ( "%s %d", month_name ( $thismonth - 1 ), $thisyear );
echo '</span><br />
        <span class="viewname">' . htmlspecialchars ( $view_name ) . '</span>
      </div>
    </div><br />';
// .
// The table has names across the top and dates for rows. Since we need to spit
// out an entire row before we can move to the next date, we'll save up all the
// HTML for each cell and then print it out when we're done....
// Additionally, we only want to put at most 6 users in one table
// since any more than that doesn't really fit in the page.
// .
// Get users in this view.
$viewusers = view_get_user_list ( $id );
if ( count ( $viewusers ) == 0 )
  // This could happen if user_sees_only_his_groups  = Y and
  // this user is not a member of any  group assigned to this view.
  $error = translate ( 'No users for this view' );

if ( ! empty ( $error ) ) {
  echo print_error ( $error ) . print_trailer ();
  exit;
}

$e_save = $re_save = array ();
$viewusercnt = count ( $viewusers );
for ( $i = 0; $i < $viewusercnt; $i++ ) {
  /* Pre-Load the repeated events for quckier access */
  $repeated_events = read_repeated_events ( $viewusers[$i], $startdate, $enddate, '' );
  $re_save[$i] = $repeated_events;
  /* Pre-load the non-repeating events for quicker access */
  $events = read_events ( $viewusers[$i], $startdate, $enddate );
  $e_save[$i] = $events;
}

for ( $j = 0; $j < $viewusercnt; $j += $USERS_PER_TABLE ) {
  // Since print_date_entries is rather stupid, we can swap the event data
  // around for users by changing what $events points to.
  // .
  // Calculate width of columns in this table.
  $num_left = count ( $viewusers ) - $j;
  if ( $num_left > $USERS_PER_TABLE )
    $num_left = $USERS_PER_TABLE;

  $tdw = ( $num_left > 0
    ? intval ( 90 / ( $num_left < $USERS_PER_TABLE ? $num_left : $USERS_PER_TABLE ) )
    : 5 );

  echo '
    <br /><br />
    <table class="main">
      <tr>
        <th class="empty">&nbsp;</th>';
  // .
  // $j points to start of this table/row.
  // $k is counter starting at 0.
  // $i starts at table start and goes until end of this table/row.
  for ( $i = $j, $k = 0;
    $i < $viewusercnt && $k < $USERS_PER_TABLE; $i++, $k++ ) {
    $user = $viewusers[$i];
    user_load_variables ( $user, 'temp' );
    echo '
        <th style="width:' . $tdw . '%;">' . $tempfullname . '</th>';
  } //end for
  echo '
      </tr>';

  for ( $date = $startdate; $date <= $enddate; $date += 86400 ) {
    $dateYmd = date ( 'Ymd', $date );
    $todayYmd = date ( 'Ymd', $today );
    $is_weekend = is_weekend ( $date );
    if ( $is_weekend && $DISPLAY_WEEKENDS == 'N' )
      continue;

    $weekday = weekday_name ( date ( 'w', $date ), $DISPLAY_LONG_DAYS );
    $class = 'class="' . ( $dateYmd == $todayYmd
      ? 'today"'
      : ( $is_weekend ? 'weekend"' : 'row"' ) );
    // .
    // Non-breaking space below keeps event from wrapping prematurely.
    echo '
      <tr>
        <th ' . $class . '>' . $weekday . '&nbsp;' . date ( 'd', $date ) . '</th>';
    for ( $i = $j, $k = 0;
      $i < $viewusercnt && $k < $USERS_PER_TABLE; $i++, $k++ ) {
      $events = $e_save[$i];
      $repeated_events = $re_save[$i];
      $user = $viewusers[$i];
      $entryStr = print_date_entries ( $dateYmd, $user, true );
      // Unset class from above if needed.
      if ( $class == 'class="row"' ||  $class == 'class="hasevents"' )
        $class = '';
      if ( ! empty ( $entryStr ) && $entryStr != '&nbsp;' )
        $class = 'class="hasevents"';
			else
      if (  $dateYmd == $todayYmd )
        $class = 'class="today"';
			else
      if ( $is_weekend )
        $class = 'class="weekend"';
      echo '
        <td ' . $class . ' style="width:' . $tdw . '%;">'
       . ( empty ( $ADD_LINK_IN_VIEWS ) || $ADD_LINK_IN_VIEWS != 'N'
        ? html_for_add_icon ( $dateYmd, '', '', $user ) : '' )
       . $entryStr . '</td>';
    } //end for
    echo '
      </tr>';
  }

  echo '
    </table>';
}
// .
$user = ''; // reset
// .
echo ( empty ( $eventinfo ) ? '' : $eventinfo ) . $printerStr . print_trailer ();

?>
