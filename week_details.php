<?php
/* $Id$ */
include_once 'includes/init.php';
send_no_cache_header ();

if (($user != $login) && $is_nonuser_admin)
  load_user_layers ($user);
else
  load_user_layers ();

load_user_categories ();


$next = mktime ( 0, 0, 0, $thismonth, $thisday + 7, $thisyear );
$prev = mktime ( 0, 0, 0, $thismonth, $thisday - 7, $thisyear );

$wkstart = get_weekday_before ( $thisyear, $thismonth, $thisday +1 );

$wkend = $wkstart + ( ONE_DAY * ( $DISPLAY_WEEKENDS == 'N'? 5 : 7 ));
$thisdate = date ( 'Ymd', $wkstart );


if ( $DISPLAY_WEEKENDS == 'N' ) {
  if ( $WEEK_START == 1 ) {
    $start_ind = 0;
    $end_ind = 4;
  } else {
    $start_ind = 1;
    $end_ind = 5;
  }
} else {
  $start_ind = 0;
  $end_ind = 6;
}

$HeadX = generate_refresh_meta ();
$printerStr = generate_printer_friendly ( 'week_details.php' );

$INC = array('js/popups.php/true');
print_header($INC,$HeadX);

/* Pre-Load the repeated events for quckier access */
$repeated_events = read_repeated_events ( strlen ( $user ) ? 
  $user : $login, $wkstart, $wkend, $cat_id );

/* Pre-load the non-repeating events for quicker access */
$events = read_events ( strlen ( $user ) ? $user : $login, $wkstart, $wkend, $cat_id  );

?>

<div class="title">
<a title="Previous" class="prev" href="week_details.php?<?php echo $u_url; ?>date=<?php echo date ('Ymd', $prev ) . $caturl;?>"><img src="images/leftarrow.gif" alt="Previous" /></a>
<a title="Next" class="next" href="week_details.php?<?php echo $u_url;?>date=<?php echo date ('Ymd', $next ) . $caturl;?>"><img src="images/rightarrow.gif" alt="Next" /></a>
<span class="date"><?php
  echo date_to_str ( date ( 'Ymd', $wkstart ), '', false ) .
    '&nbsp;&nbsp;&nbsp; - &nbsp;&nbsp;&nbsp;' .
    date_to_str ( date ( 'Ymd', $wkend ), '', false );
?></span>
<?php
if (  $WEEK_START == 0 && $DISPLAY_WEEKENDS == 'N' ) $wkstart = $wkstart - ONE_DAY;
for ( $i = 0; $i < 7; $i++ ) {
  $days[$i] = ( $wkstart + ONE_DAY * $i ) + ( 12 * 3600 );
  $weekdays[$i] = weekday_name ( ( $i + $WEEK_START ) % 7, $DISPLAY_LONG_DAYS );
  $header[$i] = $weekdays[$i] . ' ' .
    date_to_str ( date ( 'Ymd', $days[$i] ), $DATE_FORMAT_MD, false );
}

if ( $DISPLAY_WEEKNUMBER == 'Y' ) {
  echo "<br />\n<span class=\"titleweek\">(" .
    translate ( 'Week' ) . ' ' . date ( 'W', $wkstart + ONE_DAY ) . ')</span>';
}
?>
<span class="user"><?php
  if ( $single_user == 'N' ) {
    echo "<br />$user_fullname\n";
  }
  if ( $is_nonuser_admin )
    echo '<br />-- ' . translate( 'Admin mode' ) . ' --';
  if ( $is_assistant )
    echo '<br />-- ' . translate( 'Assistant mode' ) . ' --';
?></span>
<?php
  if ( $CATEGORIES_ENABLED == 'Y' ) {
    echo "<br /><br />\n";
    echo print_category_menu('week', sprintf ( "%04d%02d%02d",$thisyear, $thismonth, $thisday ), $cat_id );
  } ?>
</div>
<br />
<center>
<table class="main">
<?php
$untimed_found = false;
for ( $d = 0; $d < 7; $d++ ) {
  $date = date ( 'Ymd', $days[$d] );
  $thiswday = date ( 'w', $days[$d] );
  $is_weekend = ( $thiswday == 0 || $thiswday == 6 );
  if ( $is_weekend && $DISPLAY_WEEKENDS == 'N' ) continue;
  echo '<tr><th';
  if ( $date == date ( 'Ymd', $today ) ) {
    echo ' class="today">';
  } elseif ( $is_weekend ) {
    echo ' class="weekend">';
  } else {
    echo '>';
  }

  if ( $can_add ) {
    echo '<a title="' .
      translate( 'New Entry' ) . '" href="edit_entry.php?' . 
      $u_url . 'date=' . 
      date ( 'Ymd', $days[$d] ) . '"><img src="images/new.gif" class="new" alt="' .
      translate( 'New Entry' ) . "\" /></a>\n";
  }
  echo '<a title="' .
    $header[$d] . '" href="day.php?' . 
    $u_url . 'date=' . 
    date ('Ymd', $days[$d] ) . "$caturl\">" .
    $header[$d] . "</a></th>\n</tr>\n";

  echo "<tr>\n<td";
  if ( $date == date ( 'Ymd', $today ) ) {
    echo ' class="today">';
  } elseif ( $is_weekend ) {
    echo ' class="weekend">';
  } else {
    echo '>';
  }

  print_det_date_entries ( $date, $user, true );
  echo '&nbsp;';
  echo "</td></tr>\n";
}
?>
</table>
</center>
<?php
if ( ! empty ( $eventinfo ) ) echo $eventinfo;
echo '<br />';

echo $printerStr;
echo print_trailer(); 


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

  if ( $login != $event->getLogin() && strlen ( $event->getLogin() ) ) {
    $class = 'layerentry';
  } else {
    $class = 'entry';
    if ( $event->getStatus() == 'W' ) $class = 'unapprovedentry';
  }

  if ( $event->getPriority() == 3 ) echo '<strong>';

  if ( $event->getExtForID() != '' ) {
    $id = $event->getExtForID();
    $name = $event->getName() . ' (' . translate ( 'cont.' ) . ')';
  } else {
    $id = $event->getID();
    $name = $event->getName();
  }

  $popupid = "eventinfo-pop$id-$key";
  $linkid  = "pop$id-$key";

  $key++;

  echo '<a title="' . translate( 'View this entry' ) . 
    "\" class=\"$class\" id=\"$linkid\"  href=\"view_entry.php?id=$id&amp;date=$date";
  if ( strlen ( $user ) > 0 ) {
    echo '&amp;user=' . $user;
  } else if ( $class == 'layerentry' ) {
    echo '&amp;user=' . $event->getLogin();
  }
  echo '<img src="images/circle.gif" class="bullet" alt="view icon" />';
  if ( $login != $event->getLogin() && strlen ( $event->getLogin() ) ) {
    if ($layers) foreach ($layers as $layer) {
      if($layer['cal_layeruser'] == $event->getLogin()) {
        $in_span = true;
        echo '<span style="color:#' . $layer['cal_color'] . ';">';
      }
    }
  }

  $timestr = '';

 if ( $event->isAllDay() ) {
  $timestr = translate( 'All day event' );
 } else if ( $event->getDuration() > 0 ) {
  $timestr = display_time ( $event->getDateTime() ) .
   ' - ' . display_time ( $event->getEndDateTime() );
  echo $timestr . '&raquo;&nbsp;';
 }

  if ( $login != $user && $event->getAccess() == 'R' && strlen ( $user ) ) {
    $PN =  $PD = '(' . translate ( 'Private' ) . ')';
  } elseif ( $login != $event->getLogin() && $event->getAccess() == 'R' && strlen ( $event->getLogin() ) ) {
    $PN = $PD = '(' . translate ( 'Private' ) . ')';
  } elseif ( $login != $event->getLogin() && strlen ( $event->getLogin() ) ) {
    $PN = htmlspecialchars ( $name );
    $PD = activate_urls ( htmlspecialchars ( $event->getDescription() ) );
  } else {
    $PN = htmlspecialchars ( $name );
    $PD = activate_urls ( htmlspecialchars ( $event->getDescription() ) );
  }
  if ( ! empty ( $in_span ) ) 
   $PN .= '</span>';

  echo $PN;
  echo '</a>';
  if ( $event->getPriority() == 3 ) echo '</strong>';
  # Only display description if it is different than the event name.
  if ( $PN != $PD )
    echo " - " . $PD;
  echo "<br />\n";
  $eventinfo .= build_entry_popup ( $popupid, $event->getLogin(),
    $event->getDescription(), $timestr, site_extras_for_popup ( $id ) );
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

  // get all the non-repeating events for this date and store in $ev
  $ev = get_entries ( $date );

  // combine and sort the event arrays
  $ev = combine_and_sort_events($ev, $rep);
  for ( $i = 0, $cnt = count ( $ev ); $i < $cnt; $i++ ) {
    if ( ( ! empty ( $DISPLAY_UNAPPROVED ) && $DISPLAY_UNAPPROVED != 'N' ) ||
      $ev[$i]->getStatus() == 'A' )
      print_detailed_entry ( $ev[$i], $date );
  }
}
?>
