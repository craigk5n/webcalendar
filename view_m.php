<?php
/* $Id$
 *
 * Page Description:
 * Display a month view with users side by side.
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

view_init ( $eid );
$WC->setToday ($date);

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

$previousStr = translate ( 'Previous' );
$nextStr = translate ( 'Next' );
$dateStr = sprintf ( "%s %d", month_name ( $thismonth - 1 ), $thisyear );
$view_name = htmlspecialchars ( $view_name );

$INC = array('popups.js');
build_header ($INC);

echo <<<EOT
  <div style="width:99%;">
   <a title="{$previousStr}" class="prev" href"view_m.php?eid={$eid}&amp;date={$prevdate}">
    <img src="images/leftarrow.gif" alt="{$previousStr}" /></a>
   <a title="{$nextStr}" class="next" href="view_m.php?eid={$eid}&amp;date={$nextdate}">
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
// done....
// Additionally, we only want to put at most 6 users in one table since
// any more than that doesn't really fit in the page.

// get users in this view
$viewusers = view_get_user_list ( $eid );
if ( count ( $viewusers ) == 0 ) {
  // This could happen if user_sees_only_his_groups  = Y and
  // this user is not a member of any  group assigned to this view
  $error = translate ( 'No users for this view' );
}

if ( ! empty ( $error ) ) {
  echo print_error ( $error );
  echo print_trailer ();
  exit;
}

$e_save = array ();
$re_save = array ();
$viewusercnt = count ( $viewusers );
$display_weekends = getPref ( 'DISPLAY_WEEKENDS' );

for ( $i = 0; $i < $viewusercnt; $i++ ) {
  /* Pre-Load the repeated events for quckier access */
  $repeated_events = read_repeated_events ( $viewusers[$i], $startdate, $enddate, '' );
  $re_save[$i] = $repeated_events;
  /* Pre-load the non-repeating events for quicker access */
  $events = read_events ( $viewusers[$i], $startdate, $enddate );
  $e_save[$i] = $events;
}

for ( $j = 0; $j < $viewusercnt; $j += $USERS_PER_TABLE ) {
  // since print_date_entries is rather stupid, we can swap the event data
  // around for users by changing what $events points to.

  // Calculate width of columns in this table.
  $num_left = count ($viewusers) - $j;
  if ($num_left > $USERS_PER_TABLE) {
    $num_left = $USERS_PER_TABLE;
  }
  if ($num_left > 0) {
    if ($num_left < $USERS_PER_TABLE) {
      $tdw = (int) (90 / $num_left);
    } else {
      $tdw = (int) (90 / $USERS_PER_TABLE);
    }
  } else {
    $tdw = 5;
  }
?>
<br /><br />

<table class="main">
<tr><th class="empty">&nbsp;</th>
<?php
  // $j points to start of this table/row
  // $k is counter starting at 0
  // $i starts at table start and goes until end of this table/row.
  for ( $i = $j, $k = 0;
    $i < $viewusercnt && $k < $USERS_PER_TABLE; $i++, $k++ ) {
 $user = $viewusers[$i];
 $WC->User->loadVariables ($user, "temp");
 echo "<th style=\"width:$tdw%;\">$tempfullname</th>\n";
  } //end for
  echo "</tr>\n";

 for ( $date = $startdate; $date <= $enddate; $date += ONE_DAY ) {
   $dateYmd = date ('Ymd', $date);
   $todayYmd = date ('Ymd', $today);
   $is_weekend = is_weekend( $date ); 
   if ( $is_weekend && ! $display_weekends ) continue; 
   $weekday = weekday_name ( date ( 'w', $date ), $DISPLAY_LONG_DAYS );
   if ( $dateYmd == $todayYmd )
     $class = 'class="today"';
   else if ( $is_weekend )
     $class = 'class="weekend"';
   else
     $class = 'class="row"';
    
   //non-breaking space below keeps event from wrapping prematurely
   echo "<tr><th $class>" . $weekday . '&nbsp;' .
     date ('d', $date) . "</th>\n";
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
     if ( getPref ( 'ADD_LINK_IN_VIEWS' ) ) {
       echo html_for_add_icon ( $dateYmd, '', '', $user );
     }
     echo $entryStr;
     echo '</td>';
   } //end for
   echo "</tr>\n";
  }

  echo "</table>\n";
}

$user = ''; // reset

if ( ! empty ( $eventinfo ) ) {
  echo $eventinfo;
}

echo print_trailer (); ?>

