<?php
include_once 'includes/init.php';
send_no_cache_header ();

if (($user != $login) && $is_nonuser_admin)
  load_user_layers ($user);
else
  load_user_layers ();

function display_small_month ( $thismonth, $thisyear, $showyear ) {
  global $WEEK_START, $user, $login, $boldDays, $get_unapproved;

  if ( $user != $login && ! empty ( $user ) )
    $u_url = "&user=$user";
  else
    $u_url = "";

  echo "<TABLE BORDER=\"0\" CELLPADDING=\"1\" CELLSPACING=\"2\">";
  if ( $WEEK_START == "1" )
    $wkstart = get_monday_before ( $thisyear, $thismonth, 1 );
  else
    $wkstart = get_sunday_before ( $thisyear, $thismonth, 1 );

  $monthstart = mktime(2,0,0,$thismonth,1,$thisyear);
  $monthend = mktime(2,0,0,$thismonth + 1,0,$thisyear);
  echo "<TR><TD COLSPAN=\"7\" ALIGN=\"center\">"
     . "<A HREF=\"month.php?year=$thisyear&month=$thismonth"
     . $u_url . "\" CLASS=\"monthlink\">";
  echo month_name ( $thismonth - 1 ) .
    "</A></TD></TR>";
  echo "<TR>";
  if ( $WEEK_START == 0 ) echo "<TD><FONT SIZE=\"-3\">" .
    weekday_short_name ( 0 ) . "</TD>";
  for ( $i = 1; $i < 7; $i++ ) {
    echo "<TD><FONT SIZE=\"-3\">" .
      weekday_short_name ( $i ) . "</TD>";
  }
  if ( $WEEK_START == 1 ) echo "<TD><FONT SIZE=\"-3\">" .
    weekday_short_name ( 0 ) . "</TD>";
  for ($i = $wkstart; date("Ymd",$i) <= date ("Ymd",$monthend);
    $i += (24 * 3600 * 7) ) {
    echo "<TR>";
    for ($j = 0; $j < 7; $j++) {
      $date = $i + ($j * 24 * 3600);
      $dateYmd = date ( "Ymd", $date );
      $hasEvents = false;
      if ( $boldDays ) {
        $ev = get_entries ( $user, $dateYmd, $get_unapproved );
        if ( count ( $ev ) > 0 ) {
          $hasEvents = true;
        } else {
          $rep = get_repeating_entries ( $user, $dateYmd, $get_unapproved );
          if ( count ( $rep ) > 0 )
            $hasEvents = true;
        }
      }
      if ( $dateYmd >= date ("Ymd",$monthstart) &&
        $dateYmd <= date ("Ymd",$monthend) ) {
        echo "<TD ALIGN=\"right\"><A HREF=\"day.php?date=" .
          $dateYmd . $u_url .
          "\" CLASS=\"dayofmonthyearview\">";
        echo "<FONT SIZE=\"-1\">" .
          ( $hasEvents ? "<b>" : "" ) .
          date ( "j", $date ) .
          ( $hasEvents ? "</b>" : "" ) .
          "</A></FONT></TD>";
      } else
        echo "<TD></TD>";
    }                 // end for $j
    echo "</TR>";
  }                         // end for $i
  echo "</TABLE>";
}

if ( empty ( $year ) )
  $year = date("Y");

$thisyear = $year;
if ( $year != date ( "Y") )
  $thismonth = 1;

if ( $year > "1903" )
  $prevYear = $year - 1;
else
  $prevYear=$year;

$nextYear= $year + 1;

if ( $allow_view_other != "Y" && ! $is_admin )
  $user = "";

$boldDays = false;
if ( ! empty ( $bold_days_in_year ) && $bold_days_in_year == 'Y' ) {
  /* Pre-Load the repeated events for quckier access */
  $repeated_events = read_repeated_events (
    ( ! empty ( $user ) && strlen ( $user ) ) ? $user : $login, $cat_id );

  /* Pre-load the non-repeating events for quicker access */
  $events = read_events ( ( ! empty ( $user ) && strlen ( $user ) )
    ? $user : $login, $year . "0101", $year . "1231", $cat_id );
  $boldDays = true;
}

// Include unapproved events?
$get_unapproved = ( $DISPLAY_UNAPPROVED == 'Y' );
if ( $user == "__public__" )
  $get_unapproved = false;

print_header();
?>

<TABLE WIDTH="100%">
<TR>
<?php if ( empty ( $friendly ) ) { ?>
<TD ALIGN="left"><FONT SIZE="-1">
<A HREF="year.php?year=<?php echo $prevYear; if ( ! empty ( $user ) ) echo "&user=$user";?>" CLASS="monthlink"><IMG SRC="leftarrow.gif" WIDTH="36" HEIGHT="32" BORDER="0" ALT="<?php etranslate("Previous")?>"></A>
</FONT></TD>
<?php } ?>
<TD ALIGN="center">
<FONT SIZE="+2" COLOR="<?php echo $H2COLOR?>"><B>
<?php echo $thisyear ?>
</B></FONT>
<FONT COLOR="<?php echo $H2COLOR?>" SIZE="+1">
<?php
  if ( $single_user == "N" ) {
    echo "<BR>\n";
    if ( ! empty ( $user ) ) {
      user_load_variables ( $user, "user_" );
      echo $user_fullname;
    } else
      echo $fullname;
    if ( $is_assistant )
      echo "<B><BR>-- " . translate("Assistant mode") . " --</B>";
  }
?>
</FONT></TD>
<?php if ( empty ( $friendly ) ) { ?>
<TD ALIGN="right">
<A HREF="year.php?year=<?php echo $nextYear; if ( ! empty ( $user ) ) echo "&user=$user";?>" CLASS="monthlink"><IMG SRC="rightarrow.gif" WIDTH="36" HEIGHT="32" BORDER="0" ALT="<?php etranslate("Next")?>"></A>
</FONT></TD>
<?php } ?>
</TR>
</TABLE>

<CENTER>
<TABLE BORDER="0" CELLSPACING="4" CELLPADDING="4">
<TR>
<TD VALIGN="top"><?php display_small_month(1,$year,False); ?></TD>
<TD VALIGN="top"><?php display_small_month(2,$year,False); ?></TD>
<TD VALIGN="top"><?php display_small_month(3,$year,False); ?></TD>
<TD VALIGN="top"><?php display_small_month(4,$year,False); ?></TD>
</TR>
<TR>
<TD VALIGN="top"><?php display_small_month(5,$year,False); ?></TD>
<TD VALIGN="top"><?php display_small_month(6,$year,False); ?></TD>
<TD VALIGN="top"><?php display_small_month(7,$year,False); ?></TD>
<TD VALIGN="top"><?php display_small_month(8,$year,False); ?></TD>
</TR>
<TR>
<TD VALIGN="top"><?php display_small_month(9,$year,False); ?></TD>
<TD VALIGN="top"><?php display_small_month(10,$year,False); ?></TD>
<TD VALIGN="top"><?php display_small_month(11,$year,False); ?></TD>
<TD VALIGN="top"><?php display_small_month(12,$year,False); ?></TD>
</TR>
</TABLE>
</CENTER>

<P>

<?php if ( empty ( $friendly ) ) {

display_unapproved_events ( $login );

?>
<P>
<A CLASS="navlinks" HREF="year.php?<?php
  if ( $thisyear )
    echo "year=$thisyear&";
  if ( $user != $login && ! empty ( $user ) )
    echo "user=$user&";
?>friendly=1" TARGET="cal_printer_friendly"
onMouseOver="window.status = '<?php etranslate("Generate printer-friendly version")?>'">[<?php etranslate("Printer Friendly")?>]</A>

<?php }
print_trailer();
?>

</BODY>
</HTML>
