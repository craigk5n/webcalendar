<?php
/*
 * $Id$
 *
 * Page Description:
 *	Display a timebar view of a single day.
 *
 * Input Parameters:
 *	id (*) - specify view id in webcal_view table
 *	date - specify the starting date of the view.
 *	  If not specified, current date will be used.
 *	friendly - if set to 1, then page does not include links or
 *	  trailer navigation.
 *	(*) required field
	*
 * Security:
 *	Must have "allow view others" enabled ($allow_view_other) in
 *	  System Settings unless the user is an admin user ($is_admin).
 *	Must be owner of the view.
 */
//$start = microtime();

include_once 'includes/init.php';

$error = "";
// Don't allow users to use this feature if "allow view others" is
// disabled.
if ( $allow_view_other == "N" && ! $is_admin ) {
  // not allowed...
  do_redirect ( "$STARTVIEW.php" );
}

if ( empty ( $id ) ) {
  do_redirect ( "views.php" );
}

// Find view name in $views[]
$view_name = "";
for ( $i = 0; $i < count ( $views ); $i++ ) {
  if ( $views[$i]['cal_view_id'] == $id ) {
    $view_name = $views[$i]['cal_name'];
  }
}

// If view_name not found, then the specified view id does not
// belong to current user. 
if ( empty( $view_name ) ) {
  $error = translate ( "You are not authorized" );
}

$INC = array ( 'js/view_d.php' );
print_header ( $INC );

// get users in this view
$res = dbi_query (
  "SELECT cal_login FROM webcal_view_user WHERE cal_view_id = $id" );
$participants = array ();
$all_users = false;
if ( $res ) {
  while ( $row = dbi_fetch_row ( $res ) ) {
    $participants[] = $row[0];
    if ( $row[0] == "__all__" )
      $all_users = true;
  }
  dbi_free_result ( $res );
} else {
  $error = translate ( "Database error" ) . ": " . dbi_error ();
}

if ( $all_users ) {
  $participants = array ();
  $users = get_my_users ();
  for ( $i = 0; $i < count ( $users ); $i++ ) {
    $participants[] = $users[$i]['cal_login'];
  }
} else {
  // Make sure this user is allowed to see all users in this view
  // If this is a global view, it may include users that this user
  // is not allowed to see.
  if ( ! empty ( $user_sees_only_his_groups ) &&
    $user_sees_only_his_groups == 'Y' ) {
    $myusers = get_my_users ();
    $userlookup = array ();
    for ( $i = 0; $i < count ( $myusers ); $i++ ) {
      $userlookup[$myusers[$i]['cal_login']] = 1;
    }
    $newlist = array ();
    for ( $i = 0; $i < count ( $participants ); $i++ ) {
      if ( ! empty ( $userlookup[$participants[$i]] ) )
        $newlist[] = $participants[$i];
    }
    $participants = $newlist;
  }
}

if ( ! empty ( $error ) ) {
  echo "<h2>" . translate ( "Error" ) .
    "</h2>\n" . $error;
  print_trailer ();
  exit;
}

set_today($date);
if (!$date) $date = $thisdate;

$wday = strftime ( "%w", mktime ( 2, 0, 0, $thismonth, $thisday, $thisyear ) );
$now = mktime ( 2, 0, 0, $thismonth, $thisday, $thisyear );
$nowYmd = date ( "Ymd", $now );

$next = mktime ( 2, 0, 0, $thismonth, $thisday + 1, $thisyear );
$nextyear = date ( "Y", $next );
$nextmonth = date ( "m", $next );
$nextday = date ( "d", $next );
$nextdate = sprintf ( "%04d%02d%02d", $nextyear, $nextmonth, $nextday );

$prev = mktime ( 2, 0, 0, $thismonth, $thisday - 1, $thisyear );
$prevyear = date ( "Y", $prev );
$prevmonth = date ( "m", $prev );
$prevday = date ( "d", $prev );
$prevdate = sprintf ( "%04d%02d%02d", $prevyear, $prevmonth, $prevday );

$thisdate = sprintf ( "%04d%02d%02d", $thisyear, $thismonth, $thisday );
?>

<div style="border-width:0px; width:99%;">
<a title="<?php etranslate("Previous")?>" class="prev" href="view_d.php?id=<?php echo $id . "&amp;date=" . $prevdate?>"><img src="leftarrow.gif" class="prevnext" alt="<?php etranslate("Previous")?>" /></a>

<a title="<?php etranslate("Next")?>" class="next" href="view_d.php?id=<?php echo $id . "&amp;date=" . $nextdate?>"><img src="rightarrow.gif" class="prevnext" alt="<?php etranslate("Next")?>" /></a>
<div class="title">
<span class="date"><?php 
  printf ( "%s, %s %d, %d", weekday_name ( $wday ),
    month_name ( $thismonth - 1 ), $thisday, $thisyear ); 
?></span><br />
<span class="viewname"><?php echo $view_name; ?></span>
</div></div>

<?php
daily_matrix($date,$participants);
?>
<br />

<!-- Hidden form for booking events -->
<form action="edit_entry.php" method="post" name="schedule">
<input type="hidden" name="date" value="<?php echo $thisyear.$thismonth.$thisday;?>" />
<input type="hidden" name="defusers" value="<?php echo implode ( ",", $participants ); ?>" />
<input type="hidden" name="hour" value="" />
<input type="hidden" name="minute" value="" />
</form>

<?php
echo "<br /><a title=\"" . translate("Generate printer-friendly version") . "\" class=\"printer\" href=\"view_d.php?id=$id&amp;";
echo ( empty ( $u_url ) ? '' : $u_url ) . "date=$nowYmd";
echo ( empty ( $caturl ) ? '' : $caturl );
echo '&amp;friendly=1" target="cal_printer_friendly" onmouseover="window.status=\'' .
translate ( "Generate printer-friendly version" ) .
  '\'">[' . translate("Printer Friendly") . ']</a>';
print_trailer ();
?>

<?php
//$end =  microtime();
//$start = explode(' ',$start);
//$end = explode(' ',$end);
//$total = $end[0]+trim($end[1]) - $start[0]-trim($start[1]);
//printf ("<p>seconds = %8.2f s</p>", $total);
?>
</body>
</html>
