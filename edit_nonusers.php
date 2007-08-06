<?php
/* $Id$ */
include_once 'includes/init.php';
build_header ( array ( 'edit_nonuser.js' ), '', '', 5 );

if ( ! $WC->isAdmin() ) {
  echo print_not_auth ( true ) . '
  </body>
</html>';
  exit;
}
$add = $WC->getValue ( 'add' );
$nid = $WC->getValue ( 'nid' );
$smarty->assign ( 'nid', $nid );
$smarty->assign ( 'add', $add );

if ( ( ( $add == '1' ) || ( ! empty ( $nid ) ) ) && empty ( $error ) ) {
  $smarty->assign ( 'userlist', $WC->User->getUsers () );
}

if ( ! empty ( $nid ) ) {
  $nidData = $WC->User->loadVariables ( $nid, false );
	$nidData['login'] = substr ( $nidData['login'], 
	  strlen ( _WC_NONUSER_PREFIX ) );
  $smarty->assign ( 'nuc_url', getPref ( 'SERVER_URL',2) 
	  .'nulogin.php?login=' . $nidData['login_id'] );
} else {
  $nidData = array ( 'is_public'=>'N', 'is_selected'=>'N','view_part'=>'N' );
}

$smarty->assign ( 'nidData', $nidData );		

		
$smarty->assign ( 'confirmStr', str_replace ( 'XXX', translate ( 'entry' ),
  translate ( 'Are you sure you want to delete this XXX?' ) ) );

$smarty->display ( 'edit_nonusers.tpl' );
?>
