<?php

include "includes/config.inc";
include "includes/php-dbi.inc";
include "includes/functions.inc";
include "includes/$user_inc";
include "includes/validate.inc";
include "includes/connect.inc";

load_user_preferences ();
load_user_layers ();
if ( empty ( $friendly ) )
  remember_this_view ();

include "includes/translate.inc";

if ( ! $allow_view_other && ! $is_admin )
  $user = "";

$view = "month";

if ( ! empty ( $user ) ) {
  $u_url = "user=$user&";
  user_load_variables ( $user, "user_" );
} else {
  $u_url = "";
  $user_fullname = $fullname;
}

if ( empty ( $friendly ) )
  $friendly = 0;

?>
<HTML>
<HEAD>
<TITLE><?php etranslate ( "Title") ?></TITLE>
<?php include "includes/styles.inc"; ?>
<?php include "includes/js.inc"; ?>
</HEAD>
<BODY BGCOLOR=<?php echo "\"$BGCOLOR\"";?>>
<?php

if ( ! empty ( $date ) && ! empty ( $date ) ) {
  $thisyear = substr ( $date, 0, 4 );
  $thismonth = substr ( $date, 4, 2 );
  $thisday = substr ( $date, 6, 2 );
} else {
  if ( empty ( $month ) || $month == 0 )
    $thismonth = date("m");
  else
    $thismonth = $month;
  if ( empty ( $year ) || $year == 0 )
    $thisyear = date("Y");
  else
    $thisyear = $year;
}

$next = mktime ( 2, 0, 0, $thismonth + 1, 1, $thisyear );
$nextyear = date ( "Y", $next );
$nextmonth = date ( "m", $next );
//$nextdate = date ( "Ymd" );

$prev = mktime ( 2, 0, 0, $thismonth - 1, 1, $thisyear );
$prevyear = date ( "Y", $prev );
$prevmonth = date ( "m", $prev );
//$prevdate = date ( "Ymd" );

$startdate = sprintf ( "%04d%02d01", $thisyear, $thismonth );
$enddate = sprintf ( "%04d%02d31", $thisyear, $thismonth );

/* Pre-Load the repeated events for quckier access */
$repeated_events = read_repeated_events (
  ( ! empty ( $user ) && strlen ( $user ) ) ? $user : $login );

/* Pre-load the non-repeating events for quicker access */
$events = read_events ( ( ! empty ( $user ) && strlen ( $user ) )
  ? $user : $login, $startdate, $enddate );

?>

<TABLE BORDER=0 WIDTH=100%>
<TR>
<?php

if ( ! $friendly ) {
  echo '<TD ALIGN="left"><TABLE BORDER=0>';
  if ( $WEEK_START == "1" )
    $wkstart = get_monday_before ( $prevyear, $prevmonth, 1 );
  else
    $wkstart = get_sunday_before ( $prevyear, $prevmonth, 1 );
  $monthstart = mktime ( 0, 0, 0, $prevmonth, 1, $prevyear );
  $monthend = mktime ( 0, 0, 0, $prevmonth + 1, 0, $prevyear );
  echo "<TR><TD COLSPAN=7 ALIGN=\"middle\"><FONT SIZE=\"-1\">" .
    "<A HREF=\"month.php?$u_url&";
  $prevmonth_name = month_name ( $prevmonth );
  echo "year=$prevyear&month=$prevmonth\" CLASS=\"monthlink\">" .
    sprintf ( "%s %04d", month_name ( $prevmonth - 1 ), $prevyear ) .
    "</A></FONT></TD></TR>\n";
  echo "<TR>";
  if ( $WEEK_START == 0 ) echo "<TD><FONT SIZE=\"-2\">" .
    weekday_short_name ( 0 ) . "</TD>";
  for ( $i = 1; $i < 7; $i++ ) {
    echo "<TD><FONT SIZE=\"-2\">" .
      weekday_short_name ( $i ) . "</TD>";
  }
  if ( $WEEK_START == 1 ) echo "<TD><FONT SIZE=\"-2\">" .
    weekday_short_name ( 0 ) . "</TD>";
  echo "</TR>\n";
  for ( $i = $wkstart; date ( "Ymd", $i ) <= date ( "Ymd", $monthend );
    $i += ( 24 * 3600 * 7 ) ) {
    print "<TR>\n";
    for ( $j = 0; $j < 7; $j++ ) {
      $date = $i + ( $j * 24 * 3600 );
      if ( date ( "Ymd", $date ) >= date ( "Ymd", $monthstart ) &&
        date ( "Ymd", $date ) <= date ( "Ymd", $monthend ) ) {
        print "<TD><FONT SIZE=\"-2\">" . date ( "d", $date ) . "</FONT></TD>\n";
      } else {
        print "<TD></TD>\n";
      }
    }
    print "</TR>\n";
  }
  echo "</TABLE></TD>\n";
}

?>

<TD ALIGN="middle">
<FONT SIZE="+2" COLOR="<?php echo $H2COLOR?>">
<B>
<?php
  printf ( "%s %d", month_name ( $thismonth - 1 ), $thisyear );
?>
</B></FONT>
<FONT COLOR="<?php echo $H2COLOR?>" SIZE="+1">
<?php
  if ( ! $single_user ) {
    echo "<BR>\n";
    echo $user_fullname;
  }
?>
</FONT></TD>
<?php
if ( ! $friendly ) {
  echo '<TD ALIGN="right"><TABLE BORDER=0>';
  if ( $WEEK_START == "1" )
    $wkstart = get_monday_before ( $nextyear, $nextmonth, 1 );
  else
    $wkstart = get_sunday_before ( $nextyear, $nextmonth, 1 );
  $monthstart = mktime ( 2, 0, 0, $nextmonth, 1, $nextyear );
  $monthend = mktime ( 2, 0, 0, $nextmonth + 1, 0, $nextyear );
  echo "<TR><TD COLSPAN=7 ALIGN=\"middle\"><FONT SIZE=\"-1\">" .
    "<A HREF=\"month.php?$u_url";
  echo "year=$nextyear&month=$nextmonth\" CLASS=\"monthlink\">" .
    sprintf ( "%s %04d", month_name ( $nextmonth - 1 ), $nextyear ) .
    "</A></FONT></TD></TR>\n";
  echo "<TR>";
  if ( $WEEK_START == 0 ) echo "<TD><FONT SIZE=\"-2\">" .
    weekday_short_name ( 0 ) . "</TD>";
  for ( $i = 1; $i < 7; $i++ ) {
    echo "<TD><FONT SIZE=\"-2\">" .
      weekday_short_name ( $i ) . "</TD>";
  }
  if ( $WEEK_START == 1 ) echo "<TD><FONT SIZE=\"-2\">" .
    weekday_short_name ( 0 ) . "</TD>";
  echo "</TR>\n";
  for ( $i = $wkstart; date ( "Ymd", $i ) <= date ( "Ymd", $monthend );
    $i += ( 24 * 3600 * 7 ) ) {
    print "<TR>\n";
    for ( $j = 0; $j < 7; $j++ ) {
      $date = $i + ( $j * 24 * 3600 );
      if ( date ( "Ymd", $date ) >= date ( "Ymd", $monthstart ) &&
        date ( "Ymd", $date ) <= date ( "Ymd", $monthend ) ) {
        print "<TD><FONT SIZE=\"-2\">" . date ( "d", $date ) . "</FONT></TD>\n";
      } else {
        print "<TD></TD>\n";
      }
    }
    print "</TR>\n";
  }
  echo "</TABLE></TD>\n";
}

?>
</TR>
</TABLE>

<TABLE BORDER="0" WIDTH="100%" CELLSPACING="0" CELLPADDING="0">
<TR><TD BGCOLOR="<?php echo $TABLEBG?>">
<TABLE BORDER="0" WIDTH="100%" CELLSPACING="1" CELLPADDING="2" BORDER="0">

<TR>
<?php if ( $WEEK_START == 0 ) { ?>
<TH WIDTH="14%" BGCOLOR="<?php echo $THBG?>"><FONT COLOR="<?php echo $THFG?>"><?php etranslate("Sun")?></FONT></TH>
<?php } ?>
<TH WIDTH="14%" BGCOLOR="<?php echo $THBG?>"><FONT COLOR="<?php echo $THFG?>"><?php etranslate("Mon")?></FONT></TH>
<TH WIDTH="14%" BGCOLOR="<?php echo $THBG?>"><FONT COLOR="<?php echo $THFG?>"><?php etranslate("Tue")?></FONT></TH>
<TH WIDTH="14%" BGCOLOR="<?php echo $THBG?>"><FONT COLOR="<?php echo $THFG?>"><?php etranslate("Wed")?></FONT></TH>
<TH WIDTH="14%" BGCOLOR="<?php echo $THBG?>"><FONT COLOR="<?php echo $THFG?>"><?php etranslate("Thu")?></FONT></TH>
<TH WIDTH="14%" BGCOLOR="<?php echo $THBG?>"><FONT COLOR="<?php echo $THFG?>"><?php etranslate("Fri")?></FONT></TH>
<TH WIDTH="14%" BGCOLOR="<?php echo $THBG?>"><FONT COLOR="<?php echo $THFG?>"><?php etranslate("Sat")?></FONT></TH>
<?php if ( $WEEK_START == 1 ) { ?>
<TH WIDTH="14%" BGCOLOR="<?php echo $THBG?>"><FONT COLOR="<?php echo $THFG?>"><?php etranslate("Sun")?></FONT></TH>
<?php } ?>
</TR>

<?php

// We add 2 hours on to the time so that the switch to DST doesn't
// throw us off.  So, all our dates are 2AM for that day.
//$sun = get_sunday_before ( $thisyear, $thismonth, 1 );
if ( $WEEK_START == 1 )
  $wkstart = get_monday_before ( $thisyear, $thismonth, 1 );
else
  $wkstart = get_sunday_before ( $thisyear, $thismonth, 1 );
// generate values for first day and last day of month
$monthstart = mktime ( 2, 0, 0, $thismonth, 1, $thisyear );
$monthend = mktime ( 2, 0, 0, $thismonth + 1, 0, $thisyear );

// debugging
//echo "<P>sun = " . date ( "D, m-d-Y", $sun ) . "<BR>";
//echo "<P>monthstart = " . date ( "D, m-d-Y", $monthstart ) . "<BR>";
//echo "<P>monthend = " . date ( "D, m-d-Y", $monthend ) . "<BR>";

$today = mktime ( 2, 0, 0, date ( "m" ), date ( "d" ), date ( "Y" ) );
for ( $i = $wkstart; date ( "Ymd", $i ) <= date ( "Ymd", $monthend );
  $i += ( 24 * 3600 * 7 ) ) {
  print "<TR>\n";
  for ( $j = 0; $j < 7; $j++ ) {
    $date = $i + ( $j * 24 * 3600 );
    if ( date ( "Ymd", $date ) >= date ( "Ymd", $monthstart ) &&
      date ( "Ymd", $date ) <= date ( "Ymd", $monthend ) ) {
      print "<TD VALIGN=\"top\" WIDTH=75 HEIGHT=75 ID=\"tablecell\" ";
      if ( date ( "Ymd", $date ) == date ( "Ymd", $today ) )
        echo "BGCOLOR=\"$TODAYCELLBG\">";
      else
        echo "BGCOLOR=\"$CELLBG\">";
      //echo date ( "D, m-d-Y H:i:s", $date ) . "<BR>";
      print_date_entries ( date ( "Ymd", $date ),
        ( ! empty ( $user ) ) ? $user : $login,
        $friendly, false );
      print "</TD>\n";
    } else {
      print "<TD VALIGN=\"top\" WIDTH=75 HEIGHT=75 ID=\"tablecell\" BGCOLOR=\"$CELLBG\">&nbsp;</TD>\n";
    }
  }
  print "</TR>\n";
}

?>


</TABLE>
</TD></TR></TABLE>

<P>

<?php echo $eventinfo; ?>

<?php if ( ! $friendly ) {
  display_unapproved_events ( $login );
?>

<P>
<A HREF="month.php?<?php
  if ( $thisyear ) {
    echo "year=$thisyear&month=$thismonth&";
  }
  if ( ! empty ( $user ) ) echo "user=$user&";
?>friendly=1" TARGET="cal_printer_friendly"
onMouseOver="window.status = '<?php etranslate("Generate printer-friendly version")?>'">[<?php etranslate("Printer Friendly")?>]</A>

<?php include "includes/trailer.inc"; ?>

<?php } ?>

</BODY>
</HTML>
