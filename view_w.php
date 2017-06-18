<?php
/* $Id: view_w.php,v 1.74.2.5 2007/11/12 21:27:14 umcesrjones Exp $
 *
 * Page Description:
 * Display view of a week with users side by side.
 *
 * Input Parameters:
 * id (*)    - specify view id in webcal_view table
 * date      - specify the starting date of the view.
 *             If not specified, current date will be used.
 * friendly  - if set to 1, then page does not include links or
 *             trailer navigation.
 * (*) required field
 *
 * Security:
 * Must have "allow view others" enabled ($ALLOW_VIEW_OTHER) in System Settings
 * unless the user is an admin ($is_admin).
 * If the view is not global, the user must be owner of the view.
 * If the view is global, and user_sees_only_his_groups is enabled,
 * then we remove users not in this user's groups
 * (except for nonuser calendars... which we allow regardless of group).
 */
include_once 'includes/init.php';
include_once 'includes/views.php';

$error = '';
$USERS_PER_TABLE = 6;
$id = getValue ( 'id' );
view_init ( $id );
$printerStr = generate_printer_friendly ( 'view_w.php' );
set_today ( $date );

$next = mktime ( 0, 0, 0, $thismonth, $thisday + 7, $thisyear );
$prev = mktime ( 0, 0, 0, $thismonth, $thisday - 7, $thisyear );
$todayYmd = date ( 'Ymd', $today );

$wkstart = get_weekday_before ( $thisyear, $thismonth, $thisday + 1 );
$wkend = $wkstart + ( 86400 * ( $DISPLAY_WEEKENDS == 'N' ? 5 : 7 ) );

$nextStr = translate ( 'Next' );
$prevStr = translate ( 'Previous' );

print_header ( array ( 'js/popups.php/true' ) );

// Get users in this view.
$viewusers = view_get_user_list ( $id );
$viewusercnt = count ( $viewusers );
if ( $viewusercnt == 0 )
  // This could happen if user_sees_only_his_groups = Y and
  // this user is not a member of any group assigned to this view.
  $error = translate ( 'No users for this view' );

if ( ! empty ( $error ) ) {
  echo print_error ( $error ) . print_trailer ();
  exit;
}

ob_start ();

echo '
    <div style="width:99%;">
      <a title="' . $prevStr . '" class="prev" href="view_w.php?id=' . $id
 . '&amp;date=' . sprintf ( "%04d%02d%02d", date ( 'Y', $prev ),
  date ( 'm', $prev ), date ( 'd', $prev ) ) . '">
        <img src="images/leftarrow.gif" alt="' . $prevStr . '" /></a>
      <a title="' . $nextStr . '" class="next" href="view_w.php?id=' . $id
 . '&amp;date=' . sprintf ( "%04d%02d%02d", date ( 'Y', $next ),
  date ( 'm', $next ), date ( 'd', $next ) ) . '">
        <img src="images/rightarrow.gif" alt="' . $nextStr . '" /></a>
      <div class="title">
        <span class="date">' . date_to_str ( date ( 'Ymd', $wkstart ), '',
  false ) . '&nbsp;&nbsp;&nbsp; - &nbsp;&nbsp;&nbsp;'
 . date_to_str ( date ( 'Ymd', $wkend ), '', false ) . '</span><br />
        <span class="viewname">' . htmlspecialchars ( $view_name ) . '</span>
      </div>
    </div><br />';

// The table has names across the top and dates for rows. Since we need to spit
// out an entire row before we can move to the next date, we'll save up all the
// HTML for each cell and then print it out when we're done...
// Additionally, we only want to put at most 6 users in one table since
// any more than that doesn't really fit in the page.

$e_save = $re_save = array ();
for ( $i = 0; $i < $viewusercnt; $i++ ) {
  /* Pre-Load the repeated events for quckier access. */
  $repeated_events = read_repeated_events ( $viewusers[$i], $wkstart, $wkend, '' );
  $re_save[$i] = $repeated_events;
  /* Pre-load the non-repeating events for quicker access
     subtracting ONE_WEEK to allow cross-day events to display. */
  $e_save[$i] = $events = read_events ( $viewusers[$i], $wkstart - 604800, $wkend );
}

for ( $j = 0; $j < $viewusercnt; $j += $USERS_PER_TABLE ) {
  // Since print_date_entries is rather stupid, we can swap the event data
  // around for users by changing what $events points to.

  // Calculate width of columns in this table.
  $num_left = $viewusercnt - $j;
  if ( $num_left > $USERS_PER_TABLE )
    $num_left = $USERS_PER_TABLE;

  $tdw = ( $num_left > 0
    ? intval ( 90 /
      ( $num_left < $USERS_PER_TABLE ? $num_left : $USERS_PER_TABLE ) )
    : 5 );

  echo '
    <table class="main" cellspacing="0" cellpadding="1">
      <tr>
        <th class="empty">&nbsp;</th>';

  // $j points to start of this table/row.
  // $k is counter starting at 0.
  // $i starts at table start and goes until end of this table/row.
  for ( $i = $j, $k = 0; $i < $viewusercnt && $k < $USERS_PER_TABLE; $i++, $k++ ) {
    $user = $viewusers[$i];
    user_load_variables ( $user, 'temp' );
    echo '
        <th style="width:' . $tdw . '%;">' . $tempfullname . '</th>';
  }
  echo '
      </tr>';

  for ( $date = $wkstart; $date < $wkend; $date += 86400 ) {
    $dateYmd = date ( 'Ymd', $date );
    $is_weekend = is_weekend ( $date );
    if ( $is_weekend && $DISPLAY_WEEKENDS == 'N' )
      continue;

    $class = 'class="' . ( $dateYmd == $todayYmd
      ? 'today"' : ( $is_weekend ? 'weekend"' : 'row"' ) );

    echo '
      <tr>
        <th ' . $class . '>'
     . weekday_name ( date ( 'w', $date ), $DISPLAY_LONG_DAYS )
     . ' ' . date ( 'd', $date ) . '</th>';
    for ( $i = $j, $k = 0; $i < $viewusercnt && $k < $USERS_PER_TABLE; $i++, $k++ ) {
      $user = $viewusers[$i];
      $events = $e_save[$i];
      $repeated_events = $re_save[$i];
      $entryStr = print_date_entries ( $dateYmd, $user, true );
      // Unset class from above if needed.
      if ( $class == 'class="row"' ||  $class == 'class="hasevents"' )
        $class = '';
      if ( ! empty ( $entryStr ) && $entryStr != '&nbsp;' )
        $class = 'class="hasevents"';
      else if (  $dateYmd == $todayYmd )
        $class = 'class="today"';
      else if ( $is_weekend )
        $class = 'class="weekend"';
      echo '
        <td ' . $class . ' style="width:' . $tdw . '%;">'
       . ( empty ( $ADD_LINK_IN_VIEWS ) || $ADD_LINK_IN_VIEWS != 'N'
        ? html_for_add_icon ( date ( 'Ymd', $date ), '', '', $user ) : '' )
       . $entryStr . '
        </td>';
    }
    echo '
      </tr>';
  }
  echo '
    </table>';
}

ob_end_flush ();

$user = ''; // reset

echo ( empty ( $eventinfo ) ? '' : $eventinfo ) . $printerStr . print_trailer ();

?>
