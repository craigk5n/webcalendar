<?php
/* $Id$ */
include_once 'includes/init.php';
$layers = loadLayers ();

if ( ! $WC->isAdmin() ) {
  echo print_not_auth ( true );
  echo "</body>\n</html>";
  exit;
}
$error = '';

$nid = $WC->getPOST ( 'nid' );
$nadmin = $WC->getPOST ( 'nadmin' );
$old_admin = $WC->getPOST ( 'old_admin' );

if ( $WC->getPOST ( 'delete' ) ) {
  // delete this nonuser calendar
  $WC->User->deleteUser ( $nid );

} else {
  if ( $WC->getPOST ( 'action' ) == 'Save' 
	  || $WC->getPOST ( 'action' ) == translate( 'Save' ) ) {
    // Updating
    $params = array ( 'cal_login_id'=>$user,
		  'cal_firstname'=>$WC->getPOST ( 'ufirstname' ),
			'cal_lastname'=>$WC->getPOST ( 'ulastname' ),
			'cal_is_public'=>$WC->getPOST ( '$ispublic' ) );
						
    $WC->User->updateUser ( $params );
  } else {
    // Adding
    if (preg_match( "/^[\w]+$/", $nid ) ) {
		  $nid = _WC_NONUSER_PREFIX . $nid;
			$params = array ( 'cal_login'=>$nid,
			  'cal_lastname'=>$WC->getPOST ( 'nlastname' ),
				'cal_firstname'=>$WC->getPOST ( 'nfirstname' ),
				'cal_is_nuc'=>'Y',
				'cal_is_public'=>$WC->getPOST ( 'nispublic', 'N' ),
				'cal_admin'=>$nadmin,
				'cal_selected'=>$WC->getPOST ( 'nisselected', 'N' ),
				'cal_view_part'=>$WC->getPOST ( 'nviewpart', 'N' )
		  );
      $WC->User->addUser ( $params  );
    } else {
      $error = translate ( 'Calendar ID' ).' ' 
			  .translate ( 'word characters only' ).'.';
    }
  }
  //Add entry in UAC access table for new admin and remove for old admin
  //first delete any record for this user/nuc combo
  dbi_execute ( 'DELETE FROM webcal_access_user WHERE cal_login_id = ? ' .
    'AND cal_other_user_id = ?', array( $nadmin, $nid ) );  
  $sql = 'INSERT INTO webcal_access_user ' .
    '( cal_login_id, cal_other_user_id, cal_can_view, cal_can_edit, ' .
    'cal_can_approve, cal_can_invite, cal_can_email, cal_see_time_only ) VALUES ' .
    '( ?, ?, ?, ?, ?, ?, ?, ? )';
  if ( ! dbi_execute ( $sql, array( $nadmin, $nid, 511, 511, 511, 'Y', 'Y', 'N' ) ) ) {
    die_miserable_death ( translate ( 'Database error' ) . ': ' .
      dbi_error () );
  }
  // Delete old admin...
  //TODO Make this an optional step
  if ( ! empty ( $old_admin ) )
    dbi_execute ( 'DELETE FROM webcal_access_user WHERE cal_login_id = ? ' .
      'AND cal_other_user_id = ?', array( $old_admin, $nid ) );  
}

echo error_check('users.php?tab=nonusers', false);
?>
