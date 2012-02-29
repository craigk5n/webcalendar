<?php // $Id$
include_once 'includes/init.php';

$date  = getGetValue( 'date' );
$fday  = getGetValue( 'fday' );
$fmonth= getGetValue( 'fmonth' );
$fyear = getGetValue( 'fyear' );
$form  = getGetValue( 'form' );

if ( strlen ( $date ) > 0 ) {
  $thisyear = substr ( $date, 0, 4 );
  $thismonth = substr ( $date, 4, 2 );
} else {
  $thisyear = date ( 'Y' );
  $thismonth = date ( 'm' );
}

$href = 'href="datesel.php?form=' . $form . '&amp;fday=' . $fday
 . '&amp;fmonth=' . $fmonth . '&amp;fyear=' . $fyear . '&amp;date=';

$nextdate = $href . date ( 'Ym01"', mktime ( 0, 0, 0, $thismonth + 1, 1, $thisyear ) );

$prevdate = $href . date ( 'Ym01"', mktime ( 0, 0, 0, $thismonth - 1, 1, $thisyear ) );

$monthStr = month_name ( $thismonth - 1 );

$wkstart = get_weekday_before ( $thisyear, $thismonth );

$monthstartYmd = date ( 'Ymd', mktime ( 0, 0, 0, $thismonth, 1, $thisyear ) );
$monthendYmd = date ( 'Ymd', mktime ( 23, 59, 59, $thismonth + 1, 0, $thisyear ) );

$mdays = $wkdys = '';

// build weekday names
for ( $i = 0; $i < 7; $i++ ) {
  $wkdys .= '
        <td>' . weekday_name ( ( $i + $WEEK_START ) % 7, 'D' ) . '</td>';
}
// build month grid
for ( $i = $wkstart; date ( 'Ymd', $i ) <= $monthendYmd; $i += 604800 ) {
  $mdays .= '
              <tr>';
  for ( $j = 0; $j < 7; $j++ ) {
    $date = $i + ( $j * 86400 ) + 43200;
    $dateYmd = date ( 'Ymd', $date );
    $mdays .= '
                <td' . ( ( $dateYmd >= $monthstartYmd
        && $dateYmd <= $monthendYmd ) || $DISPLAY_ALL_DAYS_IN_MONTH == 'Y'
      ? ' class="field"><a href="javascript:sendDate(\''
       . $dateYmd . '\')">' . translate( date( 'j', $date ), false, 'N' ) . '</a>'
      : '>' ) . '</td>';
  }
  $mdays .= '
              </tr>';
}

ob_start();
setcookie( 'fday', $fday );
setcookie( 'fmonth', $fmonth );
setcookie( 'fyear', $fyear );
setcookie( 'fform', $form );
$thisyear = translate( $thisyear, false, 'N' );
print_header( '','', '', true, false, true, true, true );

echo <<<EOT
    <div>
      <table summary="">
        <tr>
          <td align="center" valign="top">
            <table cellspacing="2" summary="">
              <tr>
                <td><a {$prevdate} class="prev" title="{$prevStr}">
                  <img src="images/leftarrowsmall.gif" alt="{$prevStr}"></a></td>
                <th colspan="5">&nbsp;{$monthStr}&nbsp;{$thisyear}&nbsp;</th>
                <td><a {$nextdate} class="next" title="{$nextStr}">
                  <img src="images/rightarrowsmall.gif" alt="{$nextStr}"></a></td>
              </tr>
              <tr class="day">{$wkdys}
              </tr>{$mdays}
            </table>
          </td>
        </tr>
      </table>
    </div>';
EOT;

echo print_trailer ( false, true, true );
ob_end_flush();

?>
