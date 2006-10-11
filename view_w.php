<?php
/*
 * $Id$
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
 *   System Settings unless the user is an admin user ($is_admin).
 * If the view is not global, the user must be owner of the view.
 * If the view is global, then and user_sees_only_his_groups is
 * enabled, then we remove users not in this user's groups
 * (except for nonuser calendars... which we allow regardless of group).
 */
include_once 'includes/init.php';
include_once 'includes/views.php';

$error = '';
$USERS_PER_TABLE = 6;

view_init ( $id );

$INC = array('js/popups.php');
print_header($INC);



set_today($date);

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

$wkend = $wkstart + ( ONE_DAY * ( $DISPLAY_WEEKENDS == 'N'? 4 : 6 ) );
$thisdate = date ( 'Ymd', $wkstart );


for ( $i = 0; $i < 7; $i++ ) {
  $days[$i] = $wkstart + ONE_DAY * $i;
  $weekdays[$i] = weekday_short_name ( ( $i + $WEEK_START ) % 7 );
  $header[$i] = $weekdays[$i] . '<br />' .
     month_short_name ( date ( 'm', $days[$i] ) - 1 ) .
     ' ' . date ( 'd', $days[$i] );
}


// get users in this view
$viewusers = view_get_user_list ( $id );
$viewusercnt = count ( $viewusers );
if ( $viewusercnt == 0 ) {
  // This could happen if user_sees_only_his_groups  = Y and
  // this user is not a member of any  group assigned to this view
  $error = translate ( 'No users for this view' ) ;
}

if ( ! empty ( $error ) ) {
  echo print_error ( $error );
  echo print_trailer ();
  exit;
}
?>

<div style="width:99%;">
<a title="<?php etranslate ( 'Previous' )?>" class="prev" 
  href="view_w.php?id=<?php echo $id?>&amp;date=<?php echo $prevdate?>">
  <img src="images/leftarrow.gif" alt="<?php etranslate ( 'Previous' )?>" /></a>
<a title="<?php etranslate ( 'Next' )?>" class="next" 
  href="view_w.php?id=<?php echo $id?>&amp;date=<?php echo $nextdate?>">
  <img src="images/rightarrow.gif" alt="<?php etranslate ( 'Next' )?>" /></a>
<div class="title">
<span class="date"><?php
  echo date_to_str ( date ( 'Ymd', $wkstart ), '', false ) .
    '&nbsp;&nbsp;&nbsp; - &nbsp;&nbsp;&nbsp;' .
    date_to_str ( date ( 'Ymd', $wkend ), '', false );
?></span><br />
<span class="viewname"><?php echo htmlspecialchars ( $view_name ); ?></span>
</div>
</div><br />

<?php
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
  $repeated_events = read_repeated_events ( $viewusers[$i], '', $wkstart, $wkend );
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
    user_load_variables ( $user, 'temp' );
    echo "<th style=\"width:$tdw%;\">$tempfullname</th>\n";
  }
  echo "</tr>\n";

  for ( $xdate = $wkstart, $h = 0;
    date ( 'Ymd', $xdate ) <= date ( 'Ymd', $wkend );
    $xdate += ONE_DAY, $h++ ) {
    $wday = strftime ( "%w", $xdate );
    if ( ( $wday == 0 || $wday == 6 ) && $DISPLAY_WEEKENDS == 'N' ) continue;
    $weekday = weekday_short_name ( $wday );
    if ( date ( 'Ymd', $xdate ) == date ( 'Ymd', $today ) ) {
      echo '<tr><th class="today">';
    } else {
      echo '<tr><th class="row">';
    }
    echo $weekday . ' ' .
      round ( date ( 'd', $xdate ) ) . "</th>\n";
    for ( $i = $j, $k = 0;
      $i < $viewusercnt && $k < $USERS_PER_TABLE; $i++, $k++ ) {
      $user = $viewusers[$i];
      $events = $e_save[$i];
      $repeated_events = $re_save[$i];
      $entryStr = print_date_entries ( date ( 'Ymd', $xdate ), $user, true );
      if ( ! empty ( $entryStr ) && $entryStr != '&nbsp;' ) {
        $class = 'class="hasevents"';
      } else if ( date ( 'Ymd', $xdate ) == date ( 'Ymd', $today ) ) {
        $class = 'class=\"today\"';
      } else { if ( $wday == 0 || $wday == 6 ) {
        $class = 'class=\"weekend\"';
      } else {
        $class = '';
      }
    }
    echo "<td $class style=\"width:$tdw%;\">";
      //echo date ( 'D, m-d-Y H:i:s', $xdate ) . '<br />';
      if ( empty ( $ADD_LINK_IN_VIEWS ) || $ADD_LINK_IN_VIEWS != 'N' ) {
        echo html_for_add_icon ( date ( 'Ymd', $xdate ), '', '', $user );
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

echo generate_printer_friendly ( 'view_w.php' );
echo print_trailer ();
?>

