<?php // $Id: datesel.php,v 1.57 2009/11/22 16:47:44 bbannon Exp $
include_once 'includes/init.php';

$fday = getGetValue ( 'fday' );
$fmonth = getGetValue ( 'fmonth' );
$fyear = getGetValue ( 'fyear' );

$form = getGetValue ( 'form' );
$date = getGetValue ( 'date' );

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
$nextStr = translate ( 'Next' );

$prevdate = $href . date ( 'Ym01"', mktime ( 0, 0, 0, $thismonth - 1, 1, $thisyear ));
$previousStr = translate ( 'Previous' );

$monthStr = month_name ( $thismonth - 1 );

$wkstart = get_weekday_before ( $thisyear, $thismonth );

$monthstartYmd = date ( 'Ymd', mktime ( 0, 0, 0, $thismonth, 1, $thisyear ) );
$monthendYmd = date ( 'Ymd', mktime ( 23, 59, 59, $thismonth + 1, 0, $thisyear ) );

print_header ( '','', '', true, false, true, true, true );

//build weekday names
$wkdys = '';
for ( $i = 0; $i < 7; $i++ ) {
  $wkdys .= '<td>' . weekday_name ( ( $i + $WEEK_START ) % 7, 'D' ) . '</td>';
}
//build month grid
$todayYmd = date ( 'Ymd' );
$mdays = '';
for ( $i = $wkstart; date ( 'Ymd', $i ) <= $monthendYmd; $i += 604800 ) {
  $mdays .= '
             <tr>';
  for ( $j = 0; $j < 7; $j++ ) {
    $date = $i + ( $j * 86400 ) + 43200;
    $dateYmd = date ( 'Ymd', $date );
    $class=' class="field "';
    if ( $dateYmd == $todayYmd )
      $class .= ' id="today" ';
    $mdays .= '
               <td'
     . ( ( $dateYmd >= $monthstartYmd && $dateYmd <= $monthendYmd ) ||
      $DISPLAY_ALL_DAYS_IN_MONTH == 'Y'
      ? $class . '><a href="javascript:sendDate(\''
       . $dateYmd . '\')">' . date ( 'j', $date ) . '</a>'
      : '>' ) . '</td>';
  }
  $mdays .= '
             </tr>';
}

$mdays .= '
             </table>
            </td>
          </tr>
      </table>
    </div>
    ';

echo <<<EOT
    <div class="aligncenter">
      <table class="aligncenter">
        <tr>
          <td class="aligncenter aligntop">
            <table class="aligncenter" cellpadding="3" cellspacing="2">
              <tr>
                <td><a title="{$previousStr}" class="prev" {$prevdate}>
                  <img src="images/leftarrowsmall.gif"
                     alt="{$previousStr}" /></a></td>
                <th colspan="5">&nbsp;{$monthStr}&nbsp;{$thisyear}&nbsp;</th>
                <td><a title="{$nextStr}"class="next" {$nextdate}>
                  <img src="images/rightarrowsmall.gif"
                     alt="{$nextStr}" /></a></td>
              </tr>
              <tr class="day">
               {$wkdys}
              </tr>
              {$mdays}

  <!--We'll leave this javascript here to speed things up. -->
  <script>
  <!-- <![CDATA[
  function sendDate ( date ) {
    year = date.substring ( 0, 4 );
    month = date.substring ( 4, 6 );
    day = date.substring ( 6, 8 );
    sday = window.opener.document.{$form}.{$fday};
    smonth = window.opener.document.{$form}.{$fmonth};
    syear = window.opener.document.{$form}.{$fyear};
    sday.selectedIndex = day - 1;
    smonth.selectedIndex = month - 1;
    for ( i = 0; i < syear.length; i++ ) {
      if ( syear.options[i].value == year ) {
        syear.selectedIndex = i;
      }
    }
    window.close();
  }
  //]]> -->
  </script>
EOT;

echo print_trailer ( false, true, true );

?>
