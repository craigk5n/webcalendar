<?php
include_once 'includes/init.php';

if (($user != $login) && $is_nonuser_admin)
  load_user_layers ($user);
else
  load_user_layers ();

load_user_categories ();

$next = mktime ( 3, 0, 0, $thismonth + 1, 1, $thisyear );
$nextyear = date ( "Y", $next );
$nextmonth = date ( "m", $next );
//$nextdate = date ( "Ymd" );

$prev = mktime ( 3, 0, 0, $thismonth - 1, 1, $thisyear );
$prevyear = date ( "Y", $prev );
$prevmonth = date ( "m", $prev );
//$prevdate = date ( "Ymd" );

$startdate = sprintf ( "%04d%02d01", $thisyear, $thismonth );
$enddate = sprintf ( "%04d%02d31", $thisyear, $thismonth );

if ( $auto_refresh == "Y" && ! empty ( $auto_refresh_time ) ) {
  $refresh = $auto_refresh_time * 60; // convert to seconds
  $HeadX = "<META HTTP-EQUIV=\"refresh\" content=\"$refresh; URL=month.php?$u_url" .
    "date=$startdate$caturl\" TARGET=\"_self\">\n";
}
$INC = array('js/popups.php');
print_header($INC,$HeadX);

/* Pre-Load the repeated events for quckier access */
$repeated_events = read_repeated_events (
  ( ! empty ( $user ) && strlen ( $user ) ) ? $user : $login, $cat_id );

/* Pre-load the non-repeating events for quicker access */
$events = read_events ( ( ! empty ( $user ) && strlen ( $user ) )
  ? $user : $login, $startdate, $enddate, $cat_id );

?>

<TABLE BORDER="0" WIDTH="100%">
<TR>
<?php

if ( ! $friendly ) {
  echo '<TD ALIGN="left"><TABLE BORDER=0>';
  if ( $WEEK_START == "1" )
    $wkstart = get_monday_before ( $prevyear, $prevmonth, 1 );
  else
    $wkstart = get_sunday_before ( $prevyear, $prevmonth, 1 );
  $monthstart = mktime ( 3, 0, 0, $prevmonth, 1, $prevyear );
  $monthend = mktime ( 3, 0, 0, $prevmonth + 1, 0, $prevyear );
  echo "<TR><TD COLSPAN=7 ALIGN=\"middle\"><FONT SIZE=\"-1\">" .
    "<A HREF=\"month.php?$u_url&";
  $prevmonth_name = month_name ( $prevmonth );
  echo "year=$prevyear&month=$prevmonth$caturl\" CLASS=\"monthlink\">" .
    date_to_str ( sprintf ( "%04d%02d01", $prevyear, $prevmonth ),
    $DATE_FORMAT_MY, false, false ) .
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
  echo date_to_str ( sprintf ( "%04d%02d01", $thisyear, $thismonth ),
    $DATE_FORMAT_MY, false, false );
?>
</B></FONT>
<FONT COLOR="<?php echo $H2COLOR?>" SIZE="+1">
<?php
  if ( $single_user == "N" ) {
    echo "<BR>\n";
    echo $user_fullname;
  }
  if ( $is_nonuser_admin )
    echo "<B><BR>-- " . translate("Admin mode") . " --</B>";
  if ( $is_assistant )
    echo "<B><BR>-- " . translate("Assistant mode") . " --</B>";
  if ( $categories_enabled == "Y" && (!$user || $user == $login)) {
    echo "<BR>\n<BR>\n";
    print_category_menu('month',sprintf ( "%04d%02d01",$thisyear, $thismonth ),$cat_id, $friendly );
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
  $monthstart = mktime ( 3, 0, 0, $nextmonth, 1, $nextyear );
  $monthend = mktime ( 3, 0, 0, $nextmonth + 1, 0, $nextyear );
  echo "<TR><TD COLSPAN=7 ALIGN=\"middle\"><FONT SIZE=\"-1\">" .
    "<A HREF=\"month.php?$u_url";
  echo "year=$nextyear&month=$nextmonth$caturl\" CLASS=\"monthlink\">" .
    date_to_str ( sprintf ( "%04d%02d01", $nextyear, $nextmonth ),
    $DATE_FORMAT_MY, false, false ) .
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

<?php if ( empty ( $friendly ) || ! $friendly ) { ?>
<TABLE BORDER="0" WIDTH="100%" CELLSPACING="0" CELLPADDING="0">
<TR><TD BGCOLOR="<?php echo $TABLEBG?>">
<TABLE BORDER="0" WIDTH="100%" CELLSPACING="1" CELLPADDING="2">
<?php } else { ?>
<TABLE BORDER="1" WIDTH="100%" CELLSPACING="0" CELLPADDING="0">
<?php } ?>

<TR>
<?php if ( $WEEK_START == 0 ) { ?>
<TH WIDTH="14%" CLASS="tableheader" BGCOLOR="<?php echo $THBG?>"><FONT COLOR="<?php echo $THFG?>"><?php etranslate("Sun")?></FONT></TH>
<?php } ?>
<TH WIDTH="14%" CLASS="tableheader" BGCOLOR="<?php echo $THBG?>"><FONT COLOR="<?php echo $THFG?>"><?php etranslate("Mon")?></FONT></TH>
<TH WIDTH="14%" CLASS="tableheader" BGCOLOR="<?php echo $THBG?>"><FONT COLOR="<?php echo $THFG?>"><?php etranslate("Tue")?></FONT></TH>
<TH WIDTH="14%" CLASS="tableheader" BGCOLOR="<?php echo $THBG?>"><FONT COLOR="<?php echo $THFG?>"><?php etranslate("Wed")?></FONT></TH>
<TH WIDTH="14%" CLASS="tableheader" BGCOLOR="<?php echo $THBG?>"><FONT COLOR="<?php echo $THFG?>"><?php etranslate("Thu")?></FONT></TH>
<TH WIDTH="14%" CLASS="tableheader" BGCOLOR="<?php echo $THBG?>"><FONT COLOR="<?php echo $THFG?>"><?php etranslate("Fri")?></FONT></TH>
<TH WIDTH="14%" CLASS="tableheader" BGCOLOR="<?php echo $THBG?>"><FONT COLOR="<?php echo $THFG?>"><?php etranslate("Sat")?></FONT></TH>
<?php if ( $WEEK_START == 1 ) { ?>
<TH WIDTH="14%" CLASS="tableheader" BGCOLOR="<?php echo $THBG?>"><FONT COLOR="<?php echo $THFG?>"><?php etranslate("Sun")?></FONT></TH>
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
$monthstart = mktime ( 3, 0, 0, $thismonth, 1, $thisyear );
$monthend = mktime ( 3, 0, 0, $thismonth + 1, 0, $thisyear );

// debugging
//echo "<P>sun = " . date ( "D, m-d-Y", $sun ) . "<BR>";
//echo "<P>monthstart = " . date ( "D, m-d-Y", $monthstart ) . "<BR>";
//echo "<P>monthend = " . date ( "D, m-d-Y", $monthend ) . "<BR>";

// NOTE: if you make HTML changes to this table, make the same changes
// to the example table in pref.php.
for ( $i = $wkstart; date ( "Ymd", $i ) <= date ( "Ymd", $monthend );
  $i += ( 24 * 3600 * 7 ) ) {
  print "<TR>\n";
  for ( $j = 0; $j < 7; $j++ ) {
    $date = $i + ( $j * 24 * 3600 );
    if ( date ( "Ymd", $date ) >= date ( "Ymd", $monthstart ) &&
      date ( "Ymd", $date ) <= date ( "Ymd", $monthend ) ) {
      $thiswday = date ( "w", $date );
      $is_weekend = ( $thiswday == 0 || $thiswday == 6 );
      if ( empty ( $WEEKENDBG ) ) $is_weekend = false;
      $class = $is_weekend ? "tablecellweekend" : "tablecell";
      $color = $is_weekend ? $WEEKENDBG : $CELLBG;
      if ( empty ( $color ) )
        $color = "#C0C0C0";
      print "<TD VALIGN=\"top\" HEIGHT=75 ID=\"$class\" ";
      if ( date ( "Ymd", $date ) == date ( "Ymd", $today ) )
        echo "BGCOLOR=\"$TODAYCELLBG\">";
      else
        echo "BGCOLOR=\"$color\">";
      //echo date ( "D, m-d-Y H:i:s", $date ) . "<BR>";
      print_date_entries ( date ( "Ymd", $date ),
        ( ! empty ( $user ) ) ? $user : $login,
        $friendly, false );
      print "</TD>\n";
    } else {
      print "<TD VALIGN=\"top\" HEIGHT=75 ID=\"tablecell\" BGCOLOR=\"$CELLBG\">&nbsp;</TD>\n";
    }
  }
  print "</TR>\n";
}

?>

<?php if ( empty ( $friendly ) || ! $friendly ) { ?>
</TABLE>
</TD></TR></TABLE>
<?php } else { ?>
</TABLE>
<?php } ?>


<P>

<?php if ( empty ( $friendly ) ) echo $eventinfo; ?>

<?php if ( ! $friendly ) {
  display_unapproved_events ( ( $is_assistant || $is_nonuser_admin ? $user : $login ) );
?>

<P>
<A CLASS="navlinks" HREF="month.php?<?php
  if ( $thisyear ) {
    echo "year=$thisyear&month=$thismonth&";
  }
  if ( ! empty ( $user ) ) echo "user=$user&";
  if ( ! empty ( $cat_id ) ) echo "cat_id=$cat_id&";
?>friendly=1" TARGET="cal_printer_friendly"
onMouseOver="window.status = '<?php etranslate("Generate printer-friendly version")?>'">[<?php etranslate("Printer Friendly")?>]</A>

<?php }
print_trailer ();
?>

</BODY>
</HTML>
