<?php
// month and year are being overwritten so we will copy vars to fix.
// this will make datesel.php still work where ever it is called from.
$fday = $day;$fmonth = $month;$fyear = $year;

include_once 'includes/init.php';
$INC = array('js/datesel.php');
print_header($INC);

if ( strlen ( $date ) > 0 ) {
  $thisyear = substr ( $date, 0, 4 );
  $thismonth = substr ( $date, 4, 2 );
} else {
  $thismonth = date("m");
  $thisyear = date("Y");
}

$next = mktime ( 3, 0, 0, $thismonth + 1, 1, $thisyear );
$nextyear = date ( "Y", $next );
$nextmonth = date ( "m", $next );
$nextdate = date ( "Ym", $next ) . "01";

$prev = mktime ( 3, 0, 0, $thismonth - 1, 1, $thisyear );
$prevyear = date ( "Y", $prev );
$prevmonth = date ( "m", $prev );
$prevdate = date ( "Ym", $prev ) . "01";

?>
<CENTER>
<TABLE BORDER=0>
<TR>
<TD><A HREF="datesel.php?form=<?php echo $form?>&fday=<?php echo $fday?>&fmonth=<?php echo $fmonth?>&fyear=<?php echo $fyear?>&date=<?php echo $prevdate?>"><IMG SRC="leftarrowsmall.gif" WIDTH="18" HRIGHT="18" BORDER="0" ALT="<?php etranslate("Previous")?>"></A></TD>
<TH COLSPAN="5"><?php echo month_name ( $thismonth - 1 ) . " " . $thisyear;?></TH>
<TD><A HREF="datesel.php?form=<?php echo $form?>&fday=<?php echo $fday?>&fmonth=<?php echo $fmonth?>&fyear=<?php echo $fyear?>&date=<?php echo $nextdate?>"><IMG SRC="rightarrowsmall.gif" WIDTH="18" HEIGHT="18" BORDER="0" ALT="<?php etranslate("Next")?>"></A></TD>
</TR>
<?php
echo "<TR>";
if ( $WEEK_START == 0 ) echo "<TD><FONT SIZE=\"-1\">" .
  weekday_short_name ( 0 ) . "</TD>";
for ( $i = 1; $i < 7; $i++ ) {
  echo "<TD><FONT SIZE=\"-1\">" .
    weekday_short_name ( $i ) . "</TD>";
}
if ( $WEEK_START == 1 ) echo "<TD><FONT SIZE=\"-1\">" .
  weekday_short_name ( 0 ) . "</TD>";
echo "</TR>\n";
if ( $WEEK_START == "1" )
  $wkstart = get_monday_before ( $thisyear, $thismonth, 1 );
else
  $wkstart = get_sunday_before ( $thisyear, $thismonth, 1 );
$monthstart = mktime ( 3, 0, 0, $thismonth, 1, $thisyear );
$monthend = mktime ( 3, 0, 0, $thismonth + 1, 0, $thisyear );
for ( $i = $wkstart; date ( "Ymd", $i ) <= date ( "Ymd", $monthend );
  $i += ( 24 * 3600 * 7 ) ) {
  echo "<TR>\n";
  for ( $j = 0; $j < 7; $j++ ) {
    $date = $i + ( $j * 24 * 3600 );
    if ( date ( "Ymd", $date ) >= date ( "Ymd", $monthstart ) &&
      date ( "Ymd", $date ) <= date ( "Ymd", $monthend ) ) {
      echo "<TD><A HREF=\"javascript:sendDate('" .
        date ( "Ymd", $date ) . "')\">" .
        date ( "d", $date ) . "</A></TD>";
    } else {
      echo "<TD></TD>\n";
    }
  }
  echo "</TR>\n";
}
?>
</TABLE>
</CENTER>

</BODY>
</HTML>