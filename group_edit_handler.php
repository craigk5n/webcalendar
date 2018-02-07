<?php // $Id: group_edit_handler.php,v 1.29.2.1 2012/02/28 15:43:10 cknudsen Exp $
include_once 'includes/init.php';
require_valid_referring_url ();

$id = getPostValue ( 'id' );
$groupname = getPostValue ( 'groupname' );
$users = getPostValue ( 'users' );

if ( ! $is_admin )
  $error = print_not_auth();
else {
  $delete = getPostValue ( 'delete' );
  if ( ! empty ( $delete ) ) {
    // Delete this group.
    dbi_execute ( 'DELETE FROM webcal_group WHERE cal_group_id = ? ',
      [$id] );
    dbi_execute ( 'DELETE FROM webcal_group_user WHERE cal_group_id = ? ',
     [$id] );
  } else {
    $dateYmd = date ( 'Ymd' );
    if ( empty ( $groupname ) )
      $error = translate ( 'You must specify a group name' );
    else
    if ( ! empty ( $id ) ) {
      # update
      if ( ! dbi_execute ( 'UPDATE webcal_group SET cal_name = ?,
        cal_last_update = ? WHERE cal_group_id = ?',
        [$groupname, $dateYmd, $id] ) )
        $error = db_error();
    } else {
      # new... get new id first
      $res = dbi_execute ( 'SELECT MAX( cal_group_id ) FROM webcal_group' );
      if ( $res ) {
        $row = dbi_fetch_row ( $res );
        $id = $row[0];
        $id++;
        dbi_free_result ( $res );
        if ( ! dbi_execute ( 'INSERT INTO webcal_group ( cal_group_id, cal_owner,
          cal_name, cal_last_update ) VALUES ( ?, ?, ?, ? )',
          [$id, $login, $groupname, $dateYmd] ) )
          $error = db_error();
      } else
        $error = db_error();
    }
    # update user list
    if ( empty ( $error ) && ! empty ( $users ) ) {
      dbi_execute ( 'DELETE FROM webcal_group_user WHERE cal_group_id = ?',
        [$id] );
      for ( $i = 0, $cnt = count ( $users ); $i < $cnt; $i++ ) {
        dbi_execute ( 'INSERT INTO webcal_group_user ( cal_group_id, cal_login )
          VALUES ( ?, ? )', [$id, $users[$i]] );
      }
    }
  }
}

echo error_check ( 'users.php', false );

?>
