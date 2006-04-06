<?php
/* $Id$ */
include_once 'includes/init.php';

// month and year are being overwritten so we will copy vars to fix.
// this will make datesel.php still work where ever it is called from.
// The values $fday, $fmonth and $fyear hold the form variable names
// to update when the user selects a date.  (This is needed in
// the js/datesel.php file that gets included below.)
$fday = getGetValue ( "fday" );
$fmonth = getGetValue ( "fmonth" );
$fyear = getGetValue ( "fyear" );

$INC = array('js/datesel.php');
print_header($INC,'','',true);

if ( strlen ( $date ) > 0 ) {
  $thisyear = substr ( $date, 0, 4 );
  $thismonth = substr ( $date, 4, 2 );
} else {
  $thismonth = date("m");
  $thisyear = date("Y");
}

$next = mktime ( 0, 0, 0, $thismonth + 1, 1, $thisyear );
$nextyear = date ( "Y", $next );
$nextmonth = date ( "m", $next );
$nextdate = date ( "Ym", $next ) . "01";

$prev = mktime ( 0, 0, 0, $thismonth - 1, 1, $thisyear );
$prevyear = date ( "Y", $prev );
$prevmonth = date ( "m", $prev );
$prevdate = date ( "Ym", $prev ) . "01";

?>

<div style="text-align:center;">
<table align="center" >
<tr>
<td><a title="<?php etranslate("Previous")?>"  class="prev" href="datesel.php?form=<?php echo $form?>&amp;fday=<?php echo $fday?>&amp;fmonth=<?php echo $fmonth?>&amp;fyear=<?php echo $fyear?>&amp;date=<?php echo $prevdate?>"><img src="images/leftarrowsmall.gif"  alt="<?php etranslate("Previous")?>" /></a></td>
<th colspan="5"><?php echo month_name ( $thismonth - 1 ) . " " . $thisyear;?></th>
<td><a title="<?php etranslate("Next")?>"class="next"  href="datesel.php?form=<?php echo $form?>&amp;fday=<?php echo $fday?>&amp;fmonth=<?php echo $fmonth?>&amp;fyear=<?php echo $fyear?>&amp;date=<?php echo $nextdate?>"><img src="images/rightarrowsmall.gif"  alt="<?php etranslate("Next")?>" /></a></td>
</tr>
<?php
echo "<tr class=\"day\">\n";
if ( $WEEK_START == 0 ) echo "<td>" .
  weekday_short_name ( 0 ) . "</td>\n";
for ( $i = 1; $i < 7; $i++ ) {
  echo "<td>" .
    weekday_short_name ( $i ) . "</td>\n";
}
if ( $WEEK_START == 1 ) echo "<td>" .
  weekday_short_name ( 0 ) . "</td>\n";
echo "</tr>\n";
$wkstart = get_weekday_before ( $thisyear, $thismonth, 1 );

$monthstart = mktime ( 0, 0, 0, $thismonth, 1, $thisyear );
$monthend = mktime ( 0, 0, 0, $thismonth + 1, 0, $thisyear );
for ( $i = $wkstart; date ( "Ymd", $i ) <= date ( "Ymd", $monthend );
  $i += ( ONE_DAY * 7 ) ) {
  echo "<tr>\n";
  for ( $j = 0; $j < 7; $j++ ) {
    $date = $i + ( $j * ONE_DAY );
    if ( ( date ( "Ymd", $date ) >= date ( "Ymd", $monthstart ) &&
      date ( "Ymd", $date ) <= date ( "Ymd", $monthend ) ) || 
      ( ! empty ( $DISPLAY_ALL_DAYS_IN_MONTH ) && 
      $DISPLAY_ALL_DAYS_IN_MONTH == "Y" ) ) {
      echo "<td><a href=\"javascript:sendDate('" .
        date ( "Ymd", $date ) . "')\">" .
        date ( "j", $date ) . "</a></td>\n";
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
