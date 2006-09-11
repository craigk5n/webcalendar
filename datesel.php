<?php
/* $Id$ */
include_once 'includes/init.php';

// month and year are being overwritten so we will copy vars to fix.
// this will make datesel.php still work where ever it is called from.
// The values $fday, $fmonth and $fyear hold the form variable names
// to update when the user selects a date.  (This is needed in
// the js/datesel.php file that gets included below.)
$fday = getGetValue ( 'fday' );
$fmonth = getGetValue ( 'fmonth' );
$fyear = getGetValue ( 'fyear' );

$form = getGetValue ( 'form' );

$INC = array("js/datesel.php/false/$form/$fmonth/$fday/$fyear");
print_header( $INC, '', '', true, false, true );

if ( strlen ( $date ) > 0 ) {
  $thisyear = substr ( $date, 0, 4 );
  $thismonth = substr ( $date, 4, 2 );
} else {
  $thismonth = date('m');
  $thisyear = date('Y');
}

$next = mktime ( 0, 0, 0, $thismonth + 1, 1, $thisyear );
$nextyear = date ( 'Y', $next );
$nextmonth = date ( 'm', $next );
$nextdate = date ( 'Ym', $next ) . '01"';

$prev = mktime ( 0, 0, 0, $thismonth - 1, 1, $thisyear );
$prevyear = date ( 'Y', $prev );
$prevmonth = date ( 'm', $prev );
$prevdate = date ( 'Ym', $prev ) . '01"';

$previousStr = translate( 'Previous' );
$nextStr = translate( 'Next' );
$monthStr = month_name ( $thismonth - 1 );
$href = "href=\"datesel.php?form={$form}&amp;fday={$fday}&amp;" .
  "fmonth={$fmonth}&amp;fyear={$fyear}&amp;date=";

echo <<<EOT
 <div class="aligncenter">
  <table class="aligncenter">
   <tr>
     <td><a title="{$previousStr}" class="prev" {$href}{$prevdate}>
     <img src="images/leftarrowsmall.gif"  alt="{$previousStr}" /></a></td>
    <th colspan="5">{$monthStr}{$thisyear}</th>
      <td><a title="{$nextStr}"class="next"  {$href}{$nextdate}>
      <img src="images/rightarrowsmall.gif"  alt="{$nextStr}" /></a></td>
  </tr>
  <tr class="day">
EOT;

if ( $WEEK_START == 0 ) echo '<td>' .
  weekday_short_name ( 0 ) . "</td>\n";
for ( $i = 1; $i < 7; $i++ ) {
  echo "<td>" .
    weekday_short_name ( $i ) . "</td>\n";
}
if ( $WEEK_START == 1 ) echo '<td>' .
  weekday_short_name ( 0 ) . "</td>\n";
echo "</tr>\n";
$wkstart = get_weekday_before ( $thisyear, $thismonth );

$monthstart = mktime ( 0, 0, 0, $thismonth, 1, $thisyear );
$monthend = mktime ( 23, 59, 59, $thismonth + 1, 0, $thisyear );
for ( $i = $wkstart; date ( 'Ymd', $i ) <= date ( 'Ymd', $monthend );
  $i += ( ONE_DAY * 7 ) ) {
  echo "<tr>\n";
  for ( $j = 0; $j < 7; $j++ ) {
    $date = $i + ( $j * ONE_DAY );
    if ( ( date ( 'Ymd', $date ) >= date ( 'Ymd', $monthstart ) &&
      date ( 'Ymd', $date ) <= date ( 'Ymd', $monthend ) ) || 
      ( ! empty ( $DISPLAY_ALL_DAYS_IN_MONTH ) && 
      $DISPLAY_ALL_DAYS_IN_MONTH == 'Y' ) ) {
      echo "<td><a href=\"javascript:sendDate('" .
        date ( 'Ymd', $date ) . "')\">" .
        date ( 'j', $date ) . "</a></td>\n";
    } else {
      echo "<td></td>\n";
    }
  }
  echo "</tr>\n";
}
?>
</table>
</div>

<?php echo print_trailer ( false, true, true ); ?>

