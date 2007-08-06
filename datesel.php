<?php
/* $Id$ */
include_once 'includes/init.php';

$fday = $WC->getGET ( 'fday' );
$fmonth = $WC->getGET ( 'fmonth' );
$fyear = $WC->getGET ( 'fyear' );

$form = $WC->getGET ( 'form' );
$date = $WC->getGET ( 'date' );

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

$prevdate = $href . date ( 'Ym01"', mktime ( 0, 0, 0, $thismonth - 1, 1, $thisyear ));

$monthStr = month_name ( $thismonth - 1 );

$wkstart = get_weekday_before ( $thisyear, $thismonth );

$monthstartYmd = date ( 'Ymd', mktime ( 0, 0, 0, $thismonth, 1, $thisyear ) );
$monthendYmd = date ( 'Ymd', mktime ( 23, 59, 59, $thismonth + 1, 0, $thisyear ) );

build_header ( '','', '', 29 );

//build weekday names
for ( $i = 0; $i < 7; $i++ ) {
  $smarty->append ( 'wkdys', weekday_name ( ( $i + getPref ( 'WEEK_START' ) ) % 7, 'D' ) );
}
//build month grid
$mdays = '';
for ( $i = $wkstart; date ( 'Ymd', $i ) <= $monthendYmd;
  $i += ( ONE_WEEK ) ) {
	$smarty->append ( 'mweeks', $i );
  for ( $j = 0; $j < 7; $j++ ) {
    $date = $i + ( $j * ONE_DAY ) + 43200;
    $dateYmd = date ( 'Ymd', $date );
		if ( ( $dateYmd >= $monthstartYmd && $dateYmd <= $monthendYmd ) ||
      getpref ( 'DISPLAY_ALL_DAYS_IN_MONTH' ) ) {
      $mdays[$j]['class'] = 'class="field"';
      $mdays[$j]['display'] = '<a href="sendDate(\''
       . $dateYmd . '\')">' . date ( 'j', $date ) . '</a>';
		}
  }
}

$smarty->assign ('fday', $fday );
$smarty->assign ('fmonth', $fmonth );
$smarty->assign ('fyear', $fyear );
$smarty->assign ('form', $form );

$smarty->assign ('prevdate', $prevdate );
$smarty->assign ('nextdate', $nextdate );

$smarty->assign ('mdays', $mdays );
$smarty->display ( 'datesel.tpl' );
?>
