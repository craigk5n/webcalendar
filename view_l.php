<?php
/*
 * $Id$
 *
 * Page Description:
 * This page will display the month "view" with all users's events
 * on the same calendar.  (The other month "view" displays each user
 * calendar in a separate column, side-by-side.)  This view gives you
 * the same effect as enabling layers, but with layers you can only
 * have one configuration of users.
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

$error = "";

if ( $ALLOW_VIEW_OTHER == "N" && ! $is_admin ) {
  // not allowed...
  send_to_preferred_view ();
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
if ( empty ( $view_name ) ) {
  $error = translate ( "You are not authorized" );
}

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

if ( ! empty ( $BOLD_DAYS_IN_YEAR ) && $BOLD_DAYS_IN_YEAR == 'Y' ) {
  $boldDays = true;
  $startdate = sprintf ( "%04d%02d01", $prevyear, $prevmonth );
  $enddate = sprintf ( "%04d%02d31", $nextyear, $nextmonth );
} else {
  $boldDays = false;
  $startdate = sprintf ( "%04d%02d01", $thisyear, $thismonth );
  $enddate = sprintf ( "%04d%02d31", $thisyear, $thismonth );
}

$monthstart = mktime ( 0, 0, 0, $thismonth, 1, $thisyear );
$monthend = mktime ( 0, 0, 0, $thismonth + 1, 0, $thisyear );

$thisdate = $startdate;

// get users in this view
$res = dbi_query (
  "SELECT cal_login FROM webcal_view_user WHERE cal_view_id = $id" );
$viewusers = array ();
$all_users = false;
if ( $res ) {
  while ( $row = dbi_fetch_row ( $res ) ) {
    $viewusers[] = $row[0]; 
    if ( $row[0] == "__all__" ) {
      $all_users = true;
    }
  }
  dbi_free_result ( $res );
} else {
  $error = translate ( "Database error" ) . ": " . dbi_error ();
}
if ( $all_users ) {
  $viewusers = array ();
  $users = get_my_users ();
  for ( $i = 0; $i < count ( $users ); $i++ ) {
    $viewusers[] = $users[$i]['cal_login'];
  }
} else {
  // Make sure this user is allowed to see all users in this view
  // If this is a global view, it may include users that this user
  // is not allowed to see.
  if ( ! empty ( $USER_SEES_ONLY_HIS_GROUPS ) &&
    $USER_SEES_ONLY_HIS_GROUPS == 'Y' ) {
    $myusers = get_my_users ();
    if ( ! empty ( $NONUSER_ENABLED ) && $NONUSER_ENABLED == "Y" ) {
      $myusers = array_merge ( $myusers, get_nonuser_cals () );
    }
    $userlookup = array();
    for ( $i = 0; $i < count ( $myusers ); $i++ ) {
      $userlookup[$myusers[$i]['cal_login']] = 1;
    }
    $newlist = array ();
    for ( $i = 0; $i < count ( $viewusers ); $i++ ) {
      if ( ! empty ( $userlookup[$viewusers[$i]] ) ) {
        $newlist[] = $viewusers[$i];
      }
    }
    $viewusers = $newlist;
  }
}
if ( count ( $viewusers ) == 0 ) {
  // This could happen if user_sees_only_his_groups  = Y and
  // this user is not a member of any  group assigned to this view
  $error = translate ( "No users for this view" );
}

if ( ! empty ( $error ) ) {
  echo "<h2>" . translate ( "Error" ) .
    "</h2>\n" . $error;
  print_trailer ();
  exit;
}

$e_save = array ();
$re_save = array ();
for ( $i = 0; $i < count ( $viewusers ); $i++ ) {
  /* Pre-Load the repeated events for quckier access */
  $repeated_events = read_repeated_events ( $viewusers[$i], "", $startdate ); 
  $re_save = array_merge($re_save, $repeated_events);
  /* Pre-load the non-repeating events for quicker access */
  $events = read_events ( $viewusers[$i], $startdate, $enddate );
  $e_save = array_merge($e_save, $events);
} 
$events = array ();
$repeated_events = array ();

for ( $i = 0; $i < count ( $e_save ); $i++ ) {
  $should_add = 1;
  for ( $j = 0; $j < count ( $events ) && $should_add; $j++ ) {
    if ( $e_save[$i]->getID() == $events[$j]->getID() ) {
      $should_add = 0;
    }
  }
  if ( $should_add ) {
    array_push ( $events, $e_save[$i] );
  }
}

for ( $i = 0; $i < count ( $re_save ); $i++ ) {
  $should_add = 1;
  for ( $j = 0; $j < count ( $repeated_events ) && $should_add; $j++ ) {
    if ( $re_save[$i]->getID() == $repeated_events[$j]->getID() ) {
      $should_add = 0;
    }
  }
  if ( $should_add ) {
    array_push ( $repeated_events, $re_save[$i] );
  }
}

display_small_month ( $prevmonth, $prevyear, true, true, "prevmonth", 
  "view_l.php?id=$id&amp;" );
display_small_month ( $nextmonth, $nextyear, true, true, "nextmonth", 
  "view_l.php?id=$id&amp;" );
?>

<div class="title">
<?php display_navigation( 'view_l', false ); ?>

<span class="viewname"><br /><?php echo $view_name; ?></span></div>
<br /><br />
<?php
display_month ( $thismonth, $thisyear );
echo "<br />";

if ( ! empty ( $eventinfo ) ) {
  echo $eventinfo;
}

display_unapproved_events ( ( $is_assistant || 
  $is_nonuser_admin ? $user : $login ) );
?>

<br />
<a title="<?php 
 etranslate("Generate printer-friendly version")
?>" class="printer" href="view_l.php?id=<?php echo $id?>&amp;<?php
 if ( $thisyear ) {
  echo "year=$thisyear&amp;month=$thismonth&amp;";
 }
 if ( ! empty ( $user ) ) echo "user=$user&amp;";
 if ( ! empty ( $cat_id ) ) echo "cat_id=$cat_id&amp;";
?>friendly=1" target="cal_printer_friendly" onmouseover="window.status = '<?php 
 etranslate("Generate printer-friendly version")?>'">[<?php 
 etranslate("Printer Friendly")?>]</a>

<?php print_trailer ();?>
</body>
</html>
