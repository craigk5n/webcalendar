<?php
// month and year are being overwritten so we will copy vars to fix.
// this will make datesel.php still work where ever it is called from.
$fday = $day;$fmonth = $month;$fyear = $year;

include_once 'includes/init.php';
$INC = array('js/datesel.php');
print_header($INC,'','',false);

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

<div style="text-align:center;">
<table class="minical">
<tr>
<td><a title="<?php etranslate("Previous")?>" href="datesel.php?form=<?php echo $form?>&amp;fday=<?php echo $fday?>&amp;fmonth=<?php echo $fmonth?>&amp;fyear=<?php echo $fyear?>&amp;date=<?php echo $prevdate?>"><img src="leftarrowsmall.gif" class="prevnextsmall" alt="<?php etranslate("Previous")?>" /></a></td>
<th colspan="5"><?php echo month_name ( $thismonth - 1 ) . " " . $thisyear;?></th>
<td><a title="<?php etranslate("Next")?>" href="datesel.php?form=<?php echo $form?>&amp;fday=<?php echo $fday?>&amp;fmonth=<?php echo $fmonth?>&amp;fyear=<?php echo $fyear?>&amp;date=<?php echo $nextdate?>"><img src="rightarrowsmall.gif" class="prevnextsmall" alt="<?php etranslate("Next")?>" /></a></td>
</tr>
<?php
echo "<tr class=\"day\">";
if ( $WEEK_START == 0 ) echo "<td>" .
  weekday_short_name ( 0 ) . "</td>";
for ( $i = 1; $i < 7; $i++ ) {
  echo "<td>" .
    weekday_short_name ( $i ) . "</td>";
}
if ( $WEEK_START == 1 ) echo "<td>" .
  weekday_short_name ( 0 ) . "</td>";
echo "</tr>\n";
if ( $WEEK_START == "1" )
  $wkstart = get_monday_before ( $thisyear, $thismonth, 1 );
else
  $wkstart = get_sunday_before ( $thisyear, $thismonth, 1 );
$monthstart = mktime ( 3, 0, 0, $thismonth, 1, $thisyear );
$monthend = mktime ( 3, 0, 0, $thismonth + 1, 0, $thisyear );
for ( $i = $wkstart; date ( "Ymd", $i ) <= date ( "Ymd", $monthend );
  $i += ( 24 * 3600 * 7 ) ) {
  echo "<tr>\n";
  for ( $j = 0; $j < 7; $j++ ) {
    $date = $i + ( $j * 24 * 3600 );
    if ( date ( "Ymd", $date ) >= date ( "Ymd", $monthstart ) &&
      date ( "Ymd", $date ) <= date ( "Ymd", $monthend ) ) {
      echo "<td><a href=\"javascript:sendDate('" .
        date ( "Ymd", $date ) . "')\">" .
        date ( "d", $date ) . "</a></td>";
    } else {
      echo "<td></td>\n";
    }
  }
  echo "</tr>\n";
}
?>
</table>
</div>

<?php print_trailer ( false, true, true ); ?>
</body>
</html>