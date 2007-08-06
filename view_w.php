<?php
/* $Id$
 *
 * Page Description:
 * Display view of a week with users side by side.
 *
 * Input Parameters:
 * id (*) - specify view id in webcal_view table
 * date - specify the starting date of the view.
 *   If not specified, current date will be used.
 * friendly - if set to 1, then page does not include links or
 *   trailer navigation.
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
$eid = $WC->getValue ( 'eid' );
view_init ( $eid );

$display_weekends = getPref ( 'DISPLAY_WEEKENDS' );
$display_long_days = getPref ( 'DISPLAY_LONG_DAYS' );
$add_link_in_views = getPref ( 'ADD_LINK_IN_VIEWS' );	

$INC = array('popups.js');
build_header ($INC);


$next = mktime ( 0, 0, 0, $thismonth, $thisday + 7, $thisyear );
$nextyear = date ( 'Y', $next );
$nextmonth = date ( 'm', $next );
$nextday = date ( 'd', $next );
$nextdate = sprintf ( "%04d%02d%02d", $nextyear, $nextmonth, $nextday );

$prev = mktime ( 0, 0, 0, $thismonth, $thisday - 7, $thisyear );
$prevyear = date ( 'Y', $prev );
$prevmonth = date ( 'm', $prev );
$prevday = date ( 'd', $prev );
$prevdate = sprintf ( "%04d%02d%02d", $prevyear, $prevmonth, $prevday );

$wkstart = get_weekday_before ( $thisyear, $thismonth, $thisday +1);

$wkend = $wkstart + ( ONE_DAY * ( ! $display_weekends ? 5 : 7 ) );

$todayYmd = date ( 'Ymd', $today );

$previousStr = translate ( 'Previous' );
$nextStr = translate ( 'Next' );
$dateStr = date_to_str ( date ( 'Ymd', $wkstart ), '', false ) 
  . '&nbsp;&nbsp;&nbsp; - &nbsp;&nbsp;&nbsp;' 
  . date_to_str ( date ( 'Ymd', $wkend ), '', false );

$view_name = htmlspecialchars ( $view_name );

// get users in this view
$viewusers = view_get_user_list ( $eid );
$viewusercnt = count ( $viewusers );
if ( $viewusercnt == 0 ) {
  // This could happen if user_sees_only_his_groups  = Y and
  // this user is not a member of any  group assigned to this view
  $error = translate ( 'No users for this view' );
}

if ( ! empty ( $error ) ) {
  echo print_error ( $error );
  echo print_trailer ();
  exit;
}

echo <<<EOT
    <div style="width:99%;">
      <a title="{$previousStr}" class="prev" href="view_w.php?eid={$eid}&amp;date={$prevdate}">
        <img src="images/leftarrow.gif" alt="{$previousStr}" /></a>
      <a title="{$nextStr}" class="next" href="view_w.php?eid={$eid}&amp;date={$nextdate}">
        <img src="images/rightarrow.gif" alt="{$nextStr}" /></a>
      <div class="title">
        <span class="date">{$dateStr}</span><br />
        <span class="viewname">{$view_name}</span>
      </div>
   </div><br />

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
  $re_save[$i] = $repeated_events;
  /* Pre-load the non-repeating events for quicker access 
      subtracting ONE_WEEK to allow cross-day events to display*/
  $events = read_events ( $viewusers[$i], $wkstart - ONE_WEEK, $wkend );
  $e_save[$i] = $events;
}

for ( $j = 0; $j < $viewusercnt; $j += $USERS_PER_TABLE ) {
  // since print_date_entries is rather stupid, we can swap the event data
  // around for users by changing what $events points to.

  // Calculate width of columns in this table.
  $num_left = $viewusercnt - $j;
  if ( $num_left > $USERS_PER_TABLE ) {
    $num_left = $USERS_PER_TABLE;
  }
  if ( $num_left > 0 ) {
    if ( $num_left < $USERS_PER_TABLE ) {
      $tdw = (int) ( 90 / $num_left );
    } else {
      $tdw = (int) ( 90 / $USERS_PER_TABLE );
    }
  } else {
    $tdw = 5;
  }
?>

<table class="main" cellspacing="0" cellpadding="1">
<tr><th class="empty">&nbsp;</th>

<?php
  // $j points to start of this table/row
  // $k is counter starting at 0
  // $i starts at table start and goes until end of this table/row.
  for ( $i = $j, $k = 0;
    $i < $viewusercnt && $k < $USERS_PER_TABLE; $i++, $k++ ) {
    $user = $viewusers[$i];
    $WC->User->loadVariables ( $user, 'temp' );
    echo "<th style=\"width:$tdw%;\">$tempfullname</th>\n";
  }
  echo "</tr>\n";

  for ( $date = $wkstart; $date < $wkend; $date += ONE_DAY ) {
    $dateYmd = date ( 'Ymd', $date );
    $is_weekend = is_weekend ( $date );
    if ( $is_weekend && ! $display_weekends ) continue;
    $weekday = weekday_name ( date ( 'w', $date ), $display_long_days );
    if ( $dateYmd == $todayYmd )
      $class = 'class="today"';
    else if ( $is_weekend )
      $class = 'class="weekend"';
    else
      $class = 'class="row"';
    
    echo  "<tr><th $class>" . $weekday . ' ' . date ( 'd', $date ) . "</th>\n";
    for ( $i = $j, $k = 0;
      $i < $viewusercnt && $k < $USERS_PER_TABLE; $i++, $k++ ) {
      $user = $viewusers[$i];
      $events = $e_save[$i];
      $repeated_events = $re_save[$i];
      $entryStr = print_date_entries ( $dateYmd, $user, true );
      if ( ! empty ( $entryStr ) && $entryStr != '&nbsp;' )
        $class = 'class="hasevents"';
      //unset class from above if needed
      if ( $class == 'class="row"' )
        $class = '';
      echo "<td $class style=\"width:$tdw%;\">";
      if ( $add_link_in_views ) {
        echo html_for_add_icon ( date ( 'Ymd', $date ), '', '', $user );
      }
      echo $entryStr;
      echo "</td>\n";
    }
    echo "</tr>\n";
  }
  echo "</table>\n";
}

$user = ''; // reset

if ( ! empty ( $eventinfo ) ) {
  echo $eventinfo;
}

echo print_trailer ();
?>

