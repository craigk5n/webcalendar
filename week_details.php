<?php
include_once 'includes/init.php';
send_no_cache_header ();

if (($user != $login) && $is_nonuser_admin)
  load_user_layers ($user);
else
  load_user_layers ();

load_user_categories ();


$next = mktime ( 2, 0, 0, $thismonth, $thisday + 7, $thisyear );
$prev = mktime ( 2, 0, 0, $thismonth, $thisday - 7, $thisyear );

// We add 2 hours on to the time so that the switch to DST doesn't
// throw us off.  So, all our dates are 2AM for that day.
if ( $WEEK_START == 1 )
  $wkstart = get_monday_before ( $thisyear, $thismonth, $thisday );
else
  $wkstart = get_sunday_before ( $thisyear, $thismonth, $thisday );
$wkend = $wkstart + ( 3600 * 24 * 6 );

$startdate = date ( "Ymd", $wkstart );
$enddate = date ( "Ymd", $wkend );

if ( $DISPLAY_WEEKENDS == "N" ) {
  if ( $WEEK_START == 1 ) {
    $start_ind = 0;
    $end_ind = 5;
  } else {
    $start_ind = 1;
    $end_ind = 6;
  }
} else {
  $start_ind = 0;
  $end_ind = 7;
}

$HeadX = '';
if ( $auto_refresh == "Y" && ! empty ( $auto_refresh_time ) ) {
  $refresh = $auto_refresh_time * 60; // convert to seconds
  $HeadX = "<meta http-equiv=\"refresh\" content=\"$refresh; url=week_details.php?$u_url" .
    "date=$startdate$caturl\" />\n";
}
$INC = array('js/popups.php');
print_header($INC,$HeadX);

/* Pre-Load the repeated events for quckier access */
$repeated_events = read_repeated_events ( strlen ( $user ) ? $user : $login, $cat_id, $startdate  );

/* Pre-load the non-repeating events for quicker access */
$events = read_events ( strlen ( $user ) ? $user : $login, $startdate, $enddate, $cat_id  );

for ( $i = 0; $i < 7; $i++ ) {
  $days[$i] = $wkstart + ( 24 * 3600 ) * $i;
  $weekdays[$i] = weekday_short_name ( ( $i + $WEEK_START ) % 7 );
  $header[$i] = $weekdays[$i] . " " .
    date_to_str ( date ( "Ymd", $days[$i] ), $DATE_FORMAT_MD, false );
}
?>

<div class="title">
<a title="Previous" class="prev" href="week_details.php?<?php echo $u_url; ?>date=<?php echo date("Ymd", $prev ) . $caturl;?>"><img src="leftarrow.gif" alt="Previous" /></a>
<a title="Next" class="next" href="week_details.php?<?php echo $u_url;?>date=<?php echo date ("Ymd", $next ) . $caturl;?>"><img src="rightarrow.gif" alt="Next" /></a>
<span class="date"><?php
  echo date_to_str ( date ( "Ymd", $wkstart ), "", false ) .
    "&nbsp;&nbsp;&nbsp; - &nbsp;&nbsp;&nbsp;" .
    date_to_str ( date ( "Ymd", $wkend ), "", false );
?></span>
<?php
if ( $GLOBALS["DISPLAY_WEEKNUMBER"] == "Y" ) {
  echo "<br />\n<span class=\"weeknumber\">(" .
    translate("Week") . " " . week_number ( $wkstart ) . ")</span>";
}
?>
<span class="user"><?php
  if ( $single_user == "N" ) {
    echo "<br />$user_fullname\n";
  }
  if ( $is_nonuser_admin )
    echo "<br />-- " . translate("Admin mode") . " --";
  if ( $is_assistant )
    echo "<br />-- " . translate("Assistant mode") . " --";
?></span>
<?php
  if ( $categories_enabled == "Y" ) {
    echo "<br /><br />\n";
    print_category_menu('week', sprintf ( "%04d%02d%02d",$thisyear, $thismonth, $thisday ), $cat_id );
  } ?>
</div>

<center>
<table cellspacing="0" cellpadding="0">
<?php
$untimed_found = false;
for ( $d = 0; $d < 7; $d++ ) {
  $date = date ( "Ymd", $days[$d] );
  $thiswday = date ( "w", $days[$d] );
  $is_weekend = ( $thiswday == 0 || $thiswday == 6 );

  print "<tr><th";
  if ( $date == date ( "Ymd", $today ) ) {
    echo " class=\"today\">";
  } elseif ( $is_weekend ) {
    echo " class=\"weekend\">";
  } else {
    echo ">";
  }

  if ( $can_add ) {
    echo "<a title=\"" .
      translate("New Entry") . "\" href=\"edit_entry.php?" . 
      $u_url . "date=" . 
      date ( "Ymd", $days[$d] ) . "\"><img src=\"new.gif\" class=\"new\" alt=\"" .
      translate("New Entry") . "\" /></a>\n";
  }
  echo "<a title=\"" .
    $header[$d] . "\" href=\"day.php?" . 
    $u_url . "date=" . 
    date("Ymd", $days[$d] ) . "$caturl\">" .
    $header[$d] . "</a></th>\n</tr>\n";

  print "<tr>\n<td";
  if ( $date == date ( "Ymd", $today ) ) {
    echo " class=\"today\">";
  } elseif ( $is_weekend ) {
    echo " class=\"weekend\">";
  } else {
    echo ">";
  }

  print_det_date_entries ( $date, $user, true );
  echo "&nbsp;";
  echo "</td></tr>\n";
}
?>
</table>
</center>

<?php echo $eventinfo; ?>
<br />
<a title="<?php etranslate("Generate printer-friendly version")?>" class="printer" href="week_details.php?<?php
  echo $u_url;
  if ( $thisyear ) {
    echo "year=$thisyear&amp;month=$thismonth&amp;day=$thisday";
  }
  echo $caturl . "&amp;";
?>friendly=1" target="cal_printer_friendly" 
onmouseover="window.status = '<?php etranslate("Generate printer-friendly version")?>'">[<?php 
	etranslate("Printer Friendly")
?>]</a>

<?php print_trailer(); ?>
</body>
</html><?php

// Print the HTML for one day's events in detailed view.
// params:
//   $id - event id
//   $date - date (not used)
//   $time - time (in HHMMSS format)
//   $name - event name
//   $description - long description of event
//   $status - event status
//   $pri - event priority
//   $access - event access
//   $event_owner - user associated with this event
function print_detailed_entry ( $id, $date, $time, $duration,
  $name, $description, $status,
  $pri, $access, $event_owner ) {
  global $eventinfo, $login, $user, $TZ_OFFSET;
  static $key = 0;

  global $layers;

  if ( $login != $event_owner && strlen ( $event_owner ) ) {
    $class = "layerentry";
  } else {
    $class = "entry";
    if ( $status == "W" ) $class = "unapprovedentry";
  }

  if ( $pri == 3 ) echo "<strong>";
	$divname = "eventinfo-$id-$key";
	$key++;
	echo "<a title=\"" . 
		translate("View this entry") . "\" class=\"$class\" href=\"view_entry.php?id=$id&amp;date=$date";
	if ( strlen ( $user ) > 0 )
		echo "&amp;user=" . $user;
	echo "\" onmouseover=\"window.status='" . 
		translate("View this entry") .	"'; return true;\" onmouseout=\"window.status=''; return true;\">";
	echo "<img src=\"circle.gif\" class=\"bullet\" alt=\"view icon\" />";

  if ( $login != $event_owner && strlen ( $event_owner ) ) {
    if ($layers) foreach ($layers as $layer) {
      if($layer['cal_layeruser'] == $event_owner) {
        echo("<span style=\"color:#" . $layer['cal_color'] . ";\">");
      }
    }
  }

  $timestr = "";
  $my_time = $time + ( $TZ_OFFSET * 10000 );
  if ( $time >= 0 ) {
    if ( $GLOBALS["TIME_FORMAT"] == "24" ) {
      printf ( "%02d:%02d", $my_time / 10000, ( $my_time / 100 ) % 100 );
    } else {
      $h = ( (int) ( $my_time / 10000 ) ) % 12;
      if ( $h == 0 ) $h = 12;
      echo $h;
      $m = ( $my_time / 100 ) % 100;
      if ( $m > 0 )
        printf ( ":%02d", $m );
      else
        print (":00");
      echo ( (int) ( $my_time / 10000 ) ) < 12 ? translate("am") : translate("pm");
    }
    $timestr = display_time ( $time );
    if ( $duration > 0 ) {
      // calc end time
      $h = (int) ( $time / 10000 );
      $m = ( $time / 100 ) % 100;
      $m += $duration;
      $d = $duration;
      while ( $m >= 60 ) {
        $h++;
        $m -= 60;
      }
      $end_time = sprintf ( "%02d%02d00", $h, $m );
      $timestr .= " - " . display_time ( $end_time );
      echo " - " .display_time ( $end_time ). "";
      echo "&raquo;&nbsp;";
    } else {
	echo "&raquo;&nbsp;";
    }
  }
  if ( $login != $user && $access == 'R' && strlen ( $user ) ) {
    $PN = "(" . translate("Private") . ")"; $PD = "(" . translate("Private") . ")";
  } elseif ( $login != $event_owner && $access == 'R' && strlen ( $event_owner ) ) {
    $PN = "(" . translate("Private") . ")";$PD ="(" . translate("Private") . ")";
  } elseif ( $login != $event_owner && strlen ( $event_owner ) ) {
    $PN = htmlspecialchars ( $name ) ."</span>";
    $PD = activate_urls ( htmlspecialchars ( $description ) );
  } else {
    $PN = htmlspecialchars ( $name );
    $PD = activate_urls ( htmlspecialchars ( $description ) );
  }
  echo $PN;
  echo "</a>";
  if ( $pri == 3 ) echo "</strong>";
  # Only display description if it is different than the event name.
  if ( $PN != $PD )
    echo " - " . $PD;
  echo "<br />\n";
}

//
// Print all the calendar entries for the specified user for the
// specified date.  If we are displaying data from someone other than
// the logged in user, then check the access permission of the entry.
// params:
//   $date - date in YYYYMMDD format
//   $user - username
//   $is_ssi - is this being called from week_ssi.php?
function print_det_date_entries ( $date, $user, $ssi ) {
  global $events, $readonly, $is_admin;

  $year = substr ( $date, 0, 4 );
  $month = substr ( $date, 4, 2 );
  $day = substr ( $date, 6, 2 );

  $dateu = mktime ( 2, 0, 0, $month, $day, $year );

  // get all the repeating events for this date and store in array $rep
  $rep = get_repeating_entries ( $user, $date );
  $cur_rep = 0;

  // get all the non-repeating events for this date and store in $ev
  $ev = get_entries ( $user, $date );

  for ( $i = 0; $i < count ( $ev ); $i++ ) {
    // print out any repeating events that are before this one...
    while ( $cur_rep < count ( $rep ) &&
      $rep[$cur_rep]['cal_time'] < $ev[$i]['cal_time'] ) {
      if ( $GLOBALS["DISPLAY_UNAPPROVED"] != "N" ||
        $rep[$cur_rep]['cal_status'] == 'A' )
        print_detailed_entry ( $rep[$cur_rep]['cal_id'],
          $date, $rep[$cur_rep]['cal_time'], $rep[$cur_rep]['cal_duration'],
          $rep[$cur_rep]['cal_name'], $rep[$cur_rep]['cal_description'],
          $rep[$cur_rep]['cal_status'], $rep[$cur_rep]['cal_priority'],
          $rep[$cur_rep]['cal_access'], $rep[$cur_rep]['cal_login'] );
      $cur_rep++;
    }
    if ( $GLOBALS["DISPLAY_UNAPPROVED"] != "N" ||
      $ev[$i]['cal_status'] == 'A' )
      print_detailed_entry ( $ev[$i]['cal_id'],
        $date, $ev[$i]['cal_time'], $ev[$i]['cal_duration'],
        $ev[$i]['cal_name'], $ev[$i]['cal_description'],
        $ev[$i]['cal_status'], $ev[$i]['cal_priority'],
        $ev[$i]['cal_access'], $ev[$i]['cal_login'] );
  }
  // print out any remaining repeating events
  while ( $cur_rep < count ( $rep ) ) {
    if ( $GLOBALS["DISPLAY_UNAPPROVED"] != "N" ||
      $rep[$cur_rep]['cal_status'] == 'A' )
      print_detailed_entry ( $rep[$cur_rep]['cal_id'],
        $date, $rep[$cur_rep]['cal_time'], $rep[$cur_rep]['cal_duration'],
        $rep[$cur_rep]['cal_name'], $rep[$cur_rep]['cal_description'],
        $rep[$cur_rep]['cal_status'], $rep[$cur_rep]['cal_priority'],
        $rep[$cur_rep]['cal_access'], $rep[$cur_rep]['cal_login'] );
    $cur_rep++;
  }
}
?>