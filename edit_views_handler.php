<?php
/* $Id$ */
include_once 'includes/init.php';

$error = '';

$eid = $WC->getId ();
$viewisglobal = $WC->getPOST ( 'is_global' );
$viewname = $WC->getPOST ( 'viewname' );
$viewtype = $WC->getPOST ( 'viewtype' );
$users = $WC->getPOST ( 'users' );

if ( ! $WC->isAdmin() || $viewisglobal != 'Y' )
  $viewisglobal = 'N'; // only admin can create global view

$delete = $WC->getPOST ( 'delete' );
if ( ! empty ( $delete ) )
  // delete this view
  dbi_execute ( 'DELETE FROM webcal_view WHERE cal_view_id = ? AND cal_owner = ?', 
	  array ( $eid, $WC->loginId() ) );
else {
  if ( empty ( $viewname ) )
    $error = translate ( 'You must specify a view name' );
  else
  if ( ! empty ( $eid ) ) {
    // update
    if ( ! dbi_execute ( 'UPDATE webcal_view SET cal_name = ?, cal_view_type = ?,
      cal_is_global = ? WHERE cal_view_id = ? AND cal_owner = ?',
        array ( $viewname, $viewtype, $viewisglobal, $eid, $WC->loginId() ) ) )
      $error = db_error ();
  } else {
    # new... get new id first
    $res = dbi_execute ( 'SELECT MAX( cal_view_id ) FROM webcal_view',
      array () );
    if ( $res ) {
      $row = dbi_fetch_row ( $res );
      $eid = $row[0];
      $eid++;
      dbi_free_result ( $res );
      $sql_params = array ( $eid, $WC->loginId(), 
	    $viewname, $viewtype, $viewisglobal );
      if ( ! dbi_execute ( 'INSERT INTO webcal_view ( cal_view_id, cal_owner,
        cal_name, cal_view_type, cal_is_global ) VALUES ( ?, ?, ?, ?, ? )',
          $sql_params ) )
        $error = db_error ();
    } else
      $error = db_error ();
  }
  # update user list
  if ( $error == '' ) {
    dbi_execute ( 'DELETE FROM webcal_view_user WHERE cal_view_id = ?',
      array ( $eid ) );
    // If selected "All", then just put "__all__" in for username.
    if ( $WC->getPOST ( 'viewuserall' ) == 'Y' )
      $users = array ( '__all__' );
    for ( $i = 0, $cnt = count ( $users );
      ! empty ( $users ) && $i < $cnt; $i++ ) {
      dbi_execute ( 'INSERT INTO webcal_view_user ( cal_view_id, cal_login_id )
        VALUES ( ?, ? )', array ( $eid, $users[$i] ) );
    }
  }
}

echo error_check ( 'views.php', false );

?>
