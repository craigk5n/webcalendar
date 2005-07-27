<?php
include_once 'includes/init.php';
send_no_cache_header ();

if (($user != $login) && $is_nonuser_admin)
  load_user_layers ($user);
else
  load_user_layers ();

load_user_categories ();


$next = mktime ( 0, 0, 0, $thismonth, $thisday + 7, $thisyear );
$prev = mktime ( 0, 0, 0, $thismonth, $thisday - 7, $thisyear );

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
<table class="main" cellspacing="0" cellpadding="0">
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

<?php  if ( ! empty ( $eventinfo ) ) echo $eventinfo; ?>
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

/**
 * Prints the HTML for one event in detailed view.
 *
 * @param Event $event The event
 * @param string $date The date for which we're printing (in YYYYMMDD format)
 *
 */
function print_detailed_entry ( $event, $date ) {
  global $eventinfo, $login, $user;
  static $key = 0;

  global $layers;

  if ( $login != $event->get_login() && strlen ( $event->get_login() ) ) {
    $class = "layerentry";
  } else {
    $class = "entry";
    if ( $event->get_status() == "W" ) $class = "unapprovedentry";
  }

  if ( $event->get_priority() == 3 ) echo "<strong>";

  if ( $event->get_ext_for_id() != '' ) {
    $id = $event->get_ext_for_id();
    $name = $event->get_name() . ' (' . translate ( 'cont.' ) . ')';
  } else {
    $id = $event->get_id();
    $name = $event->get_name();
  }

  $popupid = "eventinfo-pop$id-$key";
  $linkid  = "pop$id-$key";

  $key++;

  echo "<a title=\"" . translate("View this entry") . 
    "\" class=\"$class\" id=\"$linkid\"  href=\"view_entry.php?id=$id&amp;date=$date";
  if ( strlen ( $user ) > 0 )
    echo "&amp;user=" . $user;
  echo "<img src=\"circle.gif\" class=\"bullet\" alt=\"view icon\" />";
  if ( $login != $event->get_login() && strlen ( $event->get_login() ) ) {
    if ($layers) foreach ($layers as $layer) {
      if($layer['cal_layeruser'] == $event->get_login()) {
        $in_span = true;
        echo("<span style=\"color:#" . $layer['cal_color'] . ";\">");
      }
    }
  }

  $timestr = "";

 if ( $event->is_allday() ) {
  $timestr = translate("All day event");
 } else if ( $event->get_duration() > 0 ) {
  $timestr = display_time ( $event->get_datetime() ) .
   " - " . display_time ( $event->get_enddatetime() );
  echo $timestr . "&raquo;&nbsp;";
 }

  if ( $login != $user && $event->get_access() == 'R' && strlen ( $user ) ) {
    $PN = "(" . translate("Private") . ")"; $PD = "(" . translate("Private") . ")";
  } elseif ( $login != $event->get_login() && $event->get_access() == 'R' && strlen ( $event->get_login() ) ) {
    $PN = "(" . translate("Private") . ")";$PD ="(" . translate("Private") . ")";
  } elseif ( $login != $event->get_login() && strlen ( $event->get_login() ) ) {
    $PN = htmlspecialchars ( $name );
    $PD = activate_urls ( htmlspecialchars ( $event->get_description() ) );
  }
  if ( ! empty ( $in_span ) ) 
   $PN .= "</span>";

  echo $PN;
  echo "</a>";
  if ( $event->get_priority() == 3 ) echo "</strong>";
  # Only display description if it is different than the event name.
  if ( $PN != $PD )
    echo " - " . $PD;
  echo "<br />\n";
  $eventinfo .= build_event_popup ( $popupid, $event->get_login(),
    $event->get_description(), $timestr, site_extras_for_popup ( $id ) );
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

  $dateu = mktime ( 0, 0, 0, $month, $day, $year );

  // get all the repeating events for this date and store in array $rep
  $rep = get_repeating_entries ( $user, $date );
  $cur_rep = 0;

  // get all the non-repeating events for this date and store in $ev
  $ev = get_entries ( $user, $date );

  for ( $i = 0; $i < count ( $ev ); $i++ ) {
    // print out any repeating events that are before this one...
    while ( $cur_rep < count ( $rep ) &&
      $rep[$cur_rep]->get_time() < $ev[$i]->get_time() ) {
      if ( $GLOBALS["DISPLAY_UNAPPROVED"] != "N" ||
        $rep[$cur_rep]->get_status() == 'A' )
        print_detailed_entry ( $rep[$cur_rep], $date );
      $cur_rep++;
    }
    if ( $GLOBALS["DISPLAY_UNAPPROVED"] != "N" ||
      $ev[$i]->get_status() == 'A' )
      print_detailed_entry ( $ev[$i], $date );
  }
  // print out any remaining repeating events
  while ( $cur_rep < count ( $rep ) ) {
    if ( $GLOBALS["DISPLAY_UNAPPROVED"] != "N" ||
      $rep[$cur_rep]->get_status() == 'A' )
      print_detailed_entry ( $rep[$cur_rep], $date );
    $cur_rep++;
  }
}
?>
