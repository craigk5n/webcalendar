<?php

// This page is intended to be used as a server-side include
// for another page.
// (Such as an intranet home page or something.)
// As such, no login is required.  Instead, the login id is either
// passed in the URL "week_ssi.php?login=cknudsen".  Unless, of course,
// we are in single-user mode, where no login info is needed.
// If no login info is passed, we check for the last login used.

$user = "__none__"; // don't let user specify in URL

if ( strlen ( $login ) == 0 ) {
  if ( $single_user == "Y" ) {
    $login = $user = $single_user_login;
  } else if ( strlen ( $webcalendar_login ) > 0 ) {
    $login = $user = $webcalendar_login;
  } else {
	echo "<span style=\"color:#FF0000; font-weight:bold;\">Error:</span><span style=\"color:#FF0000;\"> No calendar user specified.</span>";
    exit;
  }
}

include "includes/config.php";
include "includes/php-dbi.php";
include "includes/functions.php";
include "includes/$user_inc";
include "includes/connect.php";

load_global_settings ();
load_user_preferences ();
load_user_layers ();

$view = "week";

include "includes/translate.php";


$today = time() + ($TZ_OFFSET * 60 * 60);

if ( ! empty ( $date ) && ! empty ( $date ) ) {
  $thisyear = substr ( $date, 0, 4 );
  $thismonth = substr ( $date, 4, 2 );
  $thisday = substr ( $date, 6, 2 );
} else {
  if ( empty ( $month ) || $month == 0 )
    $thismonth = date("m", $today);
  else
    $thismonth = $month;
  if ( empty ( $year ) || $year == 0 )
    $thisyear = date("Y", $today);
  else
    $thisyear = $year;
  if ( empty ( $day ) || $day == 0 )
    $thisday = date("d", $today);
  else
    $thisday = $day;
}

$next = mktime ( 3, 0, 0, $thismonth, $thisday + 7, $thisyear );
$prev = mktime ( 3, 0, 0, $thismonth, $thisday - 7, $thisyear );

// We add 2 hours on to the time so that the switch to DST doesn't
// throw us off.  So, all our dates are 2AM for that day.
if ( $WEEK_START == 1 )
  $wkstart = get_monday_before ( $thisyear, $thismonth, $thisday );
else
  $wkstart = get_sunday_before ( $thisyear, $thismonth, $thisday );
$wkend = $wkstart + ( 3600 * 24 * 6 );
$startdate = date ( "Ymd", $wkstart );
$enddate = date ( "Ymd", $wkend );

/* Pre-Load the repeated events for quckier access */
$repeated_events = read_repeated_events ( $login, "", $startdate );

/* Pre-load the non-repeating events for quicker access */
$events = read_events ( $login, $startdate, $enddate );

for ( $i = 0; $i < 7; $i++ ) {
  $days[$i] = $wkstart + ( 24 * 3600 ) * $i;
  $weekdays[$i] = weekday_short_name ( ( $i + $WEEK_START ) % 7 );
  $header[$i] = $weekdays[$i] . "<br />" .
     month_short_name ( date ( "m", $days[$i] ) - 1 ) .
     " " . date ( "d", $days[$i] );
}

?>


<table style="border-width:0px; width:100%;" cellspacing="0" cellpadding="0">
<tr><td style="background-color:<?php echo $TABLEBG?>;">
<table style="border-width:0px; width:100%;" cellspacing="1" cellpadding="2">

<tr>
<?php
for ( $d = 0; $d < 7; $d++ ) {
  if ( date ( "Ymd", $days[$d] ) == date ( "Ymd", $today ) )
    $color = $TODAYCELLBG;
  else
    $color = $THBG;
  echo "<th style=\"width:13%; background-color:$color;\">$header[$d]</th>";
}
?>
</tr>

<tr>
<?php
$first_hour = $WORK_DAY_START_HOUR - $TZ_OFFSET;
$last_hour = $WORK_DAY_END_HOUR + $TZ_OFFSET;
$untimed_found = false;
for ( $d = 0; $d < 7; $d++ ) {
  $date = date ( "Ymd", $days[$d] );

  print "<td style=\"vertical-align:top; width:75px; height:75px;";
  if ( $date == date ( "Ymd" ) )
    echo " background-color:$TODAYCELLBG;\">";
  else
    echo " background-color:$CELLBG;\">";

  print_date_entries ( $date, $login, true, true );
  echo "&nbsp;";
  echo "</td>\n";
}
?>
</tr>
</table>
</td></tr></table>
