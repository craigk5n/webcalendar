<?php
/*
 * $Id$
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
 *   System Settings unless the user is an admin user ($is_admin).
 * If the view is not global, the user must be owner of the view.
 * If the view is global, then and user_sees_only_his_groups is
 * enabled, then we remove users not in this user's groups
 * (except for nonuser calendars... which we allow regardless of group).
 */
include_once 'includes/init.php';
include_once 'includes/views.php';

$error = "";
$USERS_PER_TABLE = 6;

view_init ( $id );

$INC = array('js/popups.php');
print_header($INC);

set_today($date);

$next = mktime ( 0, 0, 0, $thismonth + 1, 1, $thisyear );
$nextyear = date ( "Y", $next );
$nextmonth = date ( "m", $next );
$nextdate = sprintf ( "%04d%02d01", $nextyear, $nextmonth );

$prev = mktime ( 0, 0, 0, $thismonth - 1, 1, $thisyear );
$prevyear = date ( "Y", $prev );
$prevmonth = date ( "m", $prev );
$prevdate = sprintf ( "%04d%02d01", $prevyear, $prevmonth );

$startdate = mktime ( 0, 0, 0, $thismonth, 1, $thisyear );
$enddate = mktime ( 0, 0, 0, $thismonth + 1, 0, $thisyear );;

$thisdate = date ( 'Ymd', $startdate );
?>

<div style="border-width:0px; width:99%;">
<a title="<?php etranslate ( 'Previous' )?>" class="prev" href="view_m.php?id=<?php echo $id?>&amp;date=<?php echo $prevdate?>"><img src="images/leftarrow.gif" alt="<?php etranslate ( 'Previous' )?>" /></a>
<a title="<?php etranslate ( 'Next' )?>" class="next" href="view_m.php?id=<?php echo $id?>&amp;date=<?php echo $nextdate?>"><img src="images/rightarrow.gif" alt="<?php etranslate ( 'Next' )?>" /></a>
<div class="title">
<span class="date"><?php
  printf ( "%s %d", month_name ( $thismonth - 1 ), $thisyear );
?></span><br />
<span class="viewname"><?php echo htmlspecialchars ( $view_name ); ?></span>
</div>
</div><br />

<?php
// The table has names across the top and dates for rows.  Since we need
// to spit out an entire row before we can move to the next date, we'll
// save up all the HTML for each cell and then print it out when we're
// done....
// Additionally, we only want to put at most 6 users in one table since
// any more than that doesn't really fit in the page.

// get users in this view
$viewusers = view_get_user_list ( $id );
if ( count ( $viewusers ) == 0 ) {
  // This could happen if user_sees_only_his_groups  = Y and
  // this user is not a member of any  group assigned to this view
  $error = translate ( 'No users for this view' ) ;
}

if ( ! empty ( $error ) ) {
  echo '<h2>' . translate ( 'Error' ) .
    "</h2>\n" . $error;
  print_trailer ();
  exit;
}

$e_save = array ();
$re_save = array ();
for ( $i = 0; $i < count ( $viewusers ); $i++ ) {
  /* Pre-Load the repeated events for quckier access */
  $repeated_events = read_repeated_events ( $viewusers[$i], "", $startdate );
  $re_save[$i] = $repeated_events;
  /* Pre-load the non-repeating events for quicker access */
  $events = read_events ( $viewusers[$i], $startdate, $enddate );
  $e_save[$i] = $events;
}

for ( $j = 0; $j < count ($viewusers); $j += $USERS_PER_TABLE ) {
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

<table class="main" cellspacing="0" cellpadding="0">
<tr><th class="empty">&nbsp;</th>
<?php
  // $j points to start of this table/row
  // $k is counter starting at 0
  // $i starts at table start and goes until end of this table/row.
  for ( $i = $j, $k = 0;
    $i < count ($viewusers) && $k < $USERS_PER_TABLE; $i++, $k++ ) {
 $user = $viewusers[$i];
 user_load_variables ($user, "temp");
 echo "<th style=\"width:$tdw%;\">$tempfullname</th>\n";
  } //end for
  echo "</tr>\n";

  for ( $date = $startdate; date ('Ymd', $date) <= date ('Ymd', $enddate);
    $date += ONE_DAY, $wday++ ) {
 $wday = strftime ("%w", $date);
 if ( ( $wday == 0 || $wday == 6 ) && $DISPLAY_WEEKENDS == 'N' ) continue; 
 $weekday = weekday_short_name ($wday);
 echo "<tr><th";
 if ( date ('Ymd', $date) == date ('Ymd', $today) ) {
   echo " class=\"today\">";
 } else {
  if ($wday == 0 || $wday == 6) {
    echo " class=\"weekend\">";
  } else {
    echo " class=\"row\">";
  }
 }
 //non-breaking space below keeps event from wrapping prematurely
 echo $weekday . "&nbsp;" .
  round ( date ("d", $date) ) . "</th>\n";
    for ( $i = $j, $k = 0;
      $i < count ($viewusers) && $k < $USERS_PER_TABLE; $i++, $k++ ) {
 $user = $viewusers[$i];
 $events = $e_save[$i];
 $repeated_events = $re_save[$i];
 if ( date ('Ymd', $date) == date ('Ymd', $today) ) {
  echo "<td class=\"today\"";
 } else {
  if ($wday == 0 || $wday == 6) {
   echo "<td class=\"weekend\"";
  } else {
   echo "<td";
  }
 }
 echo " style=\"width:$tdw%;\">";
 //echo date ( "D, m-d-Y H:i:s", $date ) . "<br />";
      if ( empty ($ADD_LINK_IN_VIEWS) || $ADD_LINK_IN_VIEWS != 'N' ) {
        echo html_for_add_icon ( date ('Ymd', $date), "", "", $user );
      }
      print_date_entries ( date ('Ymd', $date), $user, true );
      echo "</td>";
    } //end for
    echo "</tr>\n";
  }

  echo "</table>\n<br /><br />\n";
}

$user = ""; // reset

if ( ! empty ( $eventinfo ) ) {
  echo $eventinfo;
}

echo "<a title=\"" . 
  translate ( 'Generate printer-friendly version' ) . "\" class=\"printer\" " .
  "href=\"view_m.php?id=$id&amp;date=$thisdate&amp;friendly=1\" " .
  "target=\"cal_printer_friendly\" onmouseover=\"window.status='" .
  translate ( 'Generate printer-friendly version' ) . "'\">[" . 
  translate ( 'Printer Friendly' ) . "]</a>\n";

print_trailer (); ?>
</body>
</html>
