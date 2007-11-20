<?php
/* $Id$
 *
 * Page Description:
 * Display a timebar view of a single day.
 *
 * Input Parameters:
 * vid (*)   - Specify view id in webcal_view table.
 * date     - Specify the starting date of the view.
 *            If not specified, current date will be used.
 * friendly - If set to 1, then page does not include links or
 *            trailer navigation.
 * (*) required field
 *
 * Security:
 * Must have "allow view others" enabled (_ALLOW_VIEW_OTHER) in System Settings
 * unless the user is an admin ($WC->isAdmin()).
 * If the view is not global, the user must own the view.
 * If the view is global and user_sees_only_his_groups is enabled,
 * then we remove users not in this user's groups
 * (except for nonuser calendars... which we allow regardless of group).
 */
define ( 'CALTYPE', 'day' );
include_once 'includes/init.php';
include_once 'includes/views.php';

$BodyX = ( $WC->friendly () ? '' : 'onload="matrixMagic();"' );
build_header ( array ( 'matrix.js' ), '', $BodyX );
 	
$partArray = array();
foreach ( $participants as $participant ) {
  $partArray[] = $participant['cal_login_id'];
}

$smarty->assign ( 'partArray', $partArray );
$smarty->assign ( 'partStr', implode ( ',', $partArray ) );
$smarty->assign ( 'vid', $vid );
$smarty->display ( 'view_d.tpl' );
?>