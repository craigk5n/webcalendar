<?php // $Id: view_v.php,v 1.85 2009/11/22 22:26:18 bbannon Exp $
/**
 * Page Description:
 * This page will display the month "view" with all users's events on the same
 * calendar. (The other month "view" displays each user calendar in a separate
 * column, side-by-side.) This view gives you the same effect as enabling layers,
 * but with layers you can only have one configuration of users.
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

$DAYS_PER_TABLE = 7;
$error = '';

view_init ( $id );
$printerStr = generate_printer_friendly ( 'view_v.php' );
set_today ( $date );

$nextdate = date ( 'Ymd', mktime ( 0, 0, 0, $thismonth, $thisday + 7, $thisyear ) );
$prevdate = date ( 'Ymd', mktime ( 0, 0, 0, $thismonth, $thisday - 7, $thisyear ) );

$wkstart = get_weekday_before( $thisyear, $thismonth, $thisday + 1 );
$wkend = bump_local_timestamp( $wkstart, 0, 0, 0, 0,
  ( $DISPLAY_WEEKENDS == 'N' ? 5 : 7 ), 0 ) - 1;
$thisdate = date( 'Ymd', $wkstart );

$nextStr = translate ( 'Next' );
$prevStr = translate ( 'Previous' );

$can_add = ( empty ( $ADD_LINK_IN_VIEWS ) || $ADD_LINK_IN_VIEWS != 'N' );

print_header( array( 'js/popups.js/true', 'js/dblclick_add.js/true' ) );
echo '
    <div style="width:99%;">
      <a title="' . $prevStr . '" class="prev" href="view_v.php?id=' . $id
 . '&amp;date=' . $prevdate . '"><img src="images/leftarrow.gif" alt="'
 . $prevStr . '" /></a>
      <a title="' . $nextStr . '" class="next" href="view_v.php?id=' . $id
 . '&amp;date=' . $nextdate . '"><img src="images/rightarrow.gif" alt="'
 . $nextStr . '" /></a>
      <div class="title">
        <span class="date">' . date_to_str ( $thisdate, '', false )
 . '&nbsp;&nbsp;&nbsp; - &nbsp;&nbsp;&nbsp;'
 . date_to_str ( date ( 'Ymd', $wkend ), '', false ) . '</span><br />
        <span class="viewname">' . htmlspecialchars ( $view_name ) . '</span>
      </div>
    </div><br />';

// The table has names across the top and dates for rows. Since we need to spit
// out an entire row before we can move to the next date, we'll save up all the
// HTML for each cell and then print it out when we're done..
// Additionally, we only want to put at most 6 users in one table since
// any more than that doesn't really fit in the page.

// Get users in this view.
$viewusers = view_get_user_list ( $id );
$viewusercnt = count ( $viewusers );
if ( $viewusercnt == 0 )
  // This could happen if user_sees_only_his_groups = Y and
  // this user is not a member of any group assigned to this view.
  $error = translate( 'No users for this view.' );

if ( ! empty( $error ) ) {
  echo print_error( $error ) . print_trailer();
  exit;
}

$e_save = $re_save = array();
for ( $i = 0; $i < $viewusercnt; $i++ ) {
  /* Pre-Load the repeated events for quicker access */
  $re_save[$i] = read_repeated_events ( $viewusers[$i], $wkstart, $wkend, '' );
  /* Pre-load the non-repeating events for quicker access
     subtracting ONE_WEEK to allow cross-day events to display. */
  $e_save[$i] = read_events ( $viewusers[$i], $wkstart - 604800, $wkend );
}

for ( $j = 0; $j < 7; $j += $DAYS_PER_TABLE ) {
  // Since print_date_entries is rather stupid, we can swap the event data
  // around for users by changing what $events points to.

  $tdw = 12; // Column width percent.
  echo '
    <table class="main"';
  if ( $can_add )
    echo 'title="' .
      translate ( 'Double-click on empty cell to add new entry' ) . '"';
  echo '>
      <tr>
        <th class="empty">&nbsp;</th>';

  $body = $header = '';
  $todayYmd = date ( 'Ymd', $today );
  for ( $i = 0; $i < $viewusercnt; $i++ ) {
    $events = $e_save[$i];
    $repeated_events = $re_save[$i];
    $user = $viewusers[$i];
    user_load_variables ( $user, 'temp' );
    $body .= '
      <tr>
        <th class="row" style="width:' . $tdw . '%;">' . $tempfullname . '</th>';
    for ( $date = $wkstart; $date <= $wkend;
     $date = bump_local_timestamp( $date, 0, 0, 0, 0, 1, 0 ) ) {
      $is_weekend = is_weekend( $date );

      if ( $is_weekend && $DISPLAY_WEEKENDS == 'N' )
        continue;

      $dateYmd = date ( 'Ymd', $date );
      $entryStr = print_date_entries ( $dateYmd, $user, true );
      $class = ( $dateYmd == $todayYmd
        ? ' class="today"'
        : ( ! empty ( $entryStr ) && $entryStr != '&nbsp;'
          ? ' class="hasevents"'
          : ( $is_weekend ? ' class="weekend"' : '' ) ) )
       . ' style="width:' . $tdw . '%;"';

      // Build header row.
      if ( $i == 0 ) {
        $header .= '<th' . $class . '>'
         . weekday_name ( date ( 'w', $date ), $DISPLAY_LONG_DAYS ) . ' '
         . date ( 'd', $date ) . '</th>';
      }

      $body .= '<td' . $class;
      if ( $can_add )
        $body .= " ondblclick=\"dblclick_add( '$dateYmd', '$user' )\"";
      $body .= '>' . $entryStr . '
        </td>';
    }
    $body .= '
      </tr>';
  }

  // Output all.
  echo $header . '
      </tr>' . $body . '
    </table>';
}

$user = ''; // reset

echo ( empty ( $eventinfo ) ? '' : $eventinfo ) .$printerStr . print_trailer();

?>
