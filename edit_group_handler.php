<?php
/* $Id$ */
include_once 'includes/init.php';

$gid = $WC->getPOST ( 'gid' );

if ( ! $WC->isAdmin() )
  $error = print_not_auth ();
else {
  $delete = $WC->getPOST ( 'delete' );
  if ( ! empty ( $delete ) ) {
    // delete this group
    dbi_execute ( 'DELETE FROM webcal_group WHERE cal_group_id = ? ',
      array ( $gid ) );
    dbi_execute ( 'DELETE FROM webcal_group_user WHERE cal_group_id = ? ',
      array ( $gid ) );
  } else {
    $dateYmd = date ( 'Ymd' );
		$groupname = $WC->getPOST ('groupname' );
		$group = $WC->getPOST ( 'group' );
    if ( empty ( $groupname ) )
      $error = translate ( 'You must specify a group name' );
    else
    if ( ! empty ( $gid ) ) {
      # update
      if ( ! dbi_execute ( 'UPDATE webcal_group SET cal_name = ?,
        cal_last_update = ? WHERE cal_group_id = ?',
          array ( $groupname, $dateYmd, $gid ) ) )
        $error = db_error ();
    } else {
      # new... get new id first
      $res = dbi_execute ( 'SELECT MAX( cal_group_id ) FROM webcal_group' );
      if ( $res ) {
        $row = dbi_fetch_row ( $res );
        $gid = $row[0];
        $gid++;
        dbi_free_result ( $res );
        if ( ! dbi_execute ( 'INSERT INTO webcal_group ( cal_group_id, cal_owner,
          cal_name, cal_last_update ) VALUES ( ?, ?, ?, ? )',
            array ( $gid, $WC->loginId(), $groupname, $dateYmd ) ) )
          $error = db_error ();
      } else
        $error = db_error ();
    }
    # update group list
    if ( empty ( $error ) && ! empty ( $group ) ) {
      dbi_execute ( 'DELETE FROM webcal_group_user WHERE cal_group_id = ?',
        array ( $gid ) );
      for ( $i = 0, $cnt = count ( $group ); $i < $cnt; $i++ ) {
        dbi_execute ( 'INSERT INTO webcal_group_user ( cal_group_id, cal_login_id )
          VALUES ( ?, ? )', array ( $gid, $group[$i] ) );
      }
    }
  }
}

echo error_check ( 'users.php?tab=groups', false );

?>
