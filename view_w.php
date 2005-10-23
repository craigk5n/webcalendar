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

$error = "";
$USERS_PER_TABLE = 6;

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
if ( empty( $view_name ) ) {
  $error = translate ( "You are not authorized" );
}

$INC = array('js/popups.php');
print_header($INC);



set_today($date);

$next = mktime ( 0, 0, 0, $thismonth, $thisday + 7, $thisyear );
$nextyear = date ( "Y", $next );
$nextmonth = date ( "m", $next );
$nextday = date ( "d", $next );
$nextdate = sprintf ( "%04d%02d%02d", $nextyear, $nextmonth, $nextday );

$prev = mktime ( 0, 0, 0, $thismonth, $thisday - 7, $thisyear );
$prevyear = date ( "Y", $prev );
$prevmonth = date ( "m", $prev );
$prevday = date ( "d", $prev );
$prevdate = sprintf ( "%04d%02d%02d", $prevyear, $prevmonth, $prevday );

// We add 2 hours on to the time so that the switch to DST doesn't
// throw us off.  So, all our dates are 2AM for that day.
if ( $WEEK_START == 1 ) {
  $wkstart = get_monday_before ( $thisyear, $thismonth, $thisday );
} else {
  $wkstart = get_sunday_before ( $thisyear, $thismonth, $thisday );
}
$wkend = $wkstart + ( 3600 * 24 * 6 );
$startdate = date ( "Ymd", $wkstart );
$enddate = date ( "Ymd", $wkend );

$thisdate = $startdate;

for ( $i = 0; $i < 7; $i++ ) {
  $days[$i] = $wkstart + ( 24 * 3600 ) * $i;
  $weekdays[$i] = weekday_short_name ( ( $i + $WEEK_START ) % 7 );
  $header[$i] = $weekdays[$i] . "<br />" .
     month_short_name ( date ( "m", $days[$i] ) - 1 ) .
     " " . date ( "d", $days[$i] );
}


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
    $userlookup = array ();
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
?>

<div style="border-width:0px; width:99%;">
<a title="<?php etranslate("Previous")?>" class="prev" 
  href="view_w.php?id=<?php echo $id?>&amp;date=<?php echo $prevdate?>">
  <img src="leftarrow.gif" alt="<?php etranslate("Previous")?>" /></a>
<a title="<?php etranslate("Next")?>" class="next" 
  href="view_w.php?id=<?php echo $id?>&amp;date=<?php echo $nextdate?>">
  <img src="rightarrow.gif" alt="<?php etranslate("Next")?>" /></a>
<div class="title">
<span class="date"><?php
  echo date_to_str ( date ( "Ymd", $wkstart ), false ) .
    "&nbsp;&nbsp;&nbsp; - &nbsp;&nbsp;&nbsp;" .
    date_to_str ( date ( "Ymd", $wkend ), false );
?></span><br />
<span class="viewname"><?php echo $view_name ?></span>
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
for ( $i = 0; $i < count ( $viewusers ); $i++ ) {
  /* Pre-Load the repeated events for quckier access */
  $repeated_events = read_repeated_events ( $viewusers[$i], "", $startdate );
  $re_save[$i] = $repeated_events;
  /* Pre-load the non-repeating events for quicker access */
  $events = read_events ( $viewusers[$i], $startdate, $enddate );
  $e_save[$i] = $events;
}

for ( $j = 0; $j < count ( $viewusers ); $j += $USERS_PER_TABLE ) {
  // since print_date_entries is rather stupid, we can swap the event data
  // around for users by changing what $events points to.

  // Calculate width of columns in this table.
  $num_left = count ( $viewusers ) - $j;
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
    $i < count ( $viewusers ) && $k < $USERS_PER_TABLE; $i++, $k++ ) {
    $user = $viewusers[$i];
    user_load_variables ( $user, "temp" );
    echo "<th style=\"width:$tdw%;\">$tempfullname</th>\n";
  }
  echo "</tr>\n";

  for ( $xdate = $wkstart, $h = 0;
    date ( "Ymd", $xdate ) <= date ( "Ymd", $wkend );
    $xdate += ( 24 * 3600 ), $h++ ) {
    $wday = strftime ( "%w", $xdate );
    $weekday = weekday_short_name ( $wday );
    if ( date ( "Ymd", $xdate ) == date ( "Ymd", $today ) ) {
      echo "<tr><th class=\"today\">";
    } else {
      echo "<tr><th class=\"row\">";
    }
    echo $weekday . " " .
      round ( date ( "d", $xdate ) ) . "</th>\n";
    for ( $i = $j, $k = 0;
      $i < count ( $viewusers ) && $k < $USERS_PER_TABLE; $i++, $k++ ) {
      $user = $viewusers[$i];
      $events = $e_save[$i];
      $repeated_events = $re_save[$i];
    if ( date ( "Ymd", $xdate ) == date ( "Ymd", $today ) ) {
      echo "<td class=\"today\" style=\"width:$tdw%;\">";
    } else {
      if ( $wday == 0 || $wday == 6 ) {
        echo "<td class=\"weekend\" style=\"width:$tdw%;\">";
      } else {
        echo "<td style=\"width:$tdw%;\">";
      }
    }
      //echo date ( "D, m-d-Y H:i:s", $xdate ) . "<br />";
      if ( empty ( $ADD_LINK_IN_VIEWS ) || $ADD_LINK_IN_VIEWS != "N" ) {
        echo html_for_add_icon ( date ( "Ymd", $xdate ), "", "", $user );
      }
      print_date_entries ( date ( "Ymd", $xdate ), $user, true );
      echo "</td>\n";
    }
    echo "</tr>\n";
  }
  echo "</table>\n<br /><br />\n";
}

$user = ""; // reset

if ( ! empty ( $eventinfo ) ) {
  echo $eventinfo;
}

echo "<a title=\"" . translate("Generate printer-friendly version") . 
  "\" class=\"printer\" href=\"view_w.php?id=$id&amp;date=$date&amp;friendly=1\" " .
  "target=\"cal_printer_friendly\" onmouseover=\"window.status='" .
  translate("Generate printer-friendly version") .
  "'\">[" . translate("Printer Friendly") . "]</a>\n";

print_trailer ();
?>
</body>
</html>
