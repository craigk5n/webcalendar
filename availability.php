<?php
/* $Id$
 * Page Description:
 * Display a timebar view of a single day.
 *
 * Input Parameters:
 * month (*) - specify the starting month of the timebar
 * day (*)   - specify the starting day of the timebar
 * year (*)  - specify the starting year of the timebar
 * users (*) - csv of users to include
 * (*) required field
 *
 * Security:
 * Must have "allow view others" enabled ($ALLOW_VIEW_OTHER) in
 *   System Settings unless the user is an admin user ($WC->isAdmin()).
 */

include_once 'includes/init.php';

$error = '';

// Don't allow users to use this feature if "allow view others" is disabled.
if ( ! getPref ( 'ALLOW_VIEW_OTHER' ) && ! $WC->isAdmin() )
 $error = 'not_auth';

// Input args in URL.
// users: list of comma-separated users.
$users = $WC->getGET ( 'users' );

$programStr = translate ( 'Program Error' ) . ' ';
if ( empty ( $users ) ) {
  $error = $programStr . str_replace ( 'XXX', translate ( 'user' ),
    translate ( 'No XXX specified!' ) );
} elseif ( empty ( $WC->thisdate ) ) {
  $error = $programStr . str_replace ( 'XXX', translate ( 'date' ),
    translate ( 'No XXX specified!' ) );
}
$BodyX ='onload="initAvail( \''
  .$WC->thismonth .'\',\'' . $WC->thisday .'\',\'' . $WC->thisyear. '\' );matrixMagic();"';
build_header ( array ( 'matrix.js' ), '', $BodyX, 5 );

if ( ! empty ( $error ) ) {
	$smarty->assign ( 'not_auth', $error == 'not_auth' );
	$smarty->assign ( 'errorStr', $error );
  $smarty->display ( 'error.tpl' );
  exit;
}

$smarty->assign ( 'partArray', explode ( ',', $users ) );
$smarty->assign ( 'partStr', $users );

$smarty->assign ( 'prevDate', date ( 'Ymd', 
  mktime ( 0, 0, 0, $WC->thismonth, $WC->thisday - 1, $WC->thisyear ) ) );
$smarty->assign ( 'nextDate', date ( 'Ymd', 
  mktime ( 0, 0, 0, $WC->thismonth, $WC->thisday + 1, $WC->thisyear ) ) );

$smarty->display ( 'availability.tpl' );
?>
