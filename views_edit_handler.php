<?php
include_once 'includes/init.php';
require_valid_referring_url ();

$error = '';

$viewisglobal = getPostValue ( 'is_global' );
$viewname = getPostValue ( 'viewname' );
$viewtype = getPostValue ( 'viewtype' );
$users = getPostValue ( 'users' );
$delete = getPostValue ( 'delete' );

if ( ! $is_admin || $viewisglobal != 'Y' )
  $viewisglobal = 'N'; // Only admin can create global view.
  //.
if ( ! empty ( $delete ) )
  // Delete this view.
  dbi_execute ( 'DELETE FROM webcal_view WHERE cal_view_id = ? AND cal_owner = ?',
    [$id, $login] );
else {
  if ( empty ( $viewname ) )
    $error = translate ( 'You must specify a view name' );
  else
  if ( ! empty ( $id ) ) {
    // update
    if ( ! dbi_execute ( 'UPDATE webcal_view SET cal_name = ?, cal_view_type = ?,
      cal_is_global = ? WHERE cal_view_id = ? AND cal_owner = ?',
        [$viewname, $viewtype, $viewisglobal, $id, $login] ) )
      $error = db_error();
  } else {
    # new... Get new id first.
    $res = dbi_execute ( 'SELECT MAX( cal_view_id ) FROM webcal_view',
      [] );
    if ( $res ) {
      $row = dbi_fetch_row ( $res );
      $id = $row[0];
      $id++;
      dbi_free_result ( $res );
      $sql_params = [$id, $login, $viewname, $viewtype, $viewisglobal];
      if ( ! dbi_execute ( 'INSERT INTO webcal_view ( cal_view_id, cal_owner,
        cal_name, cal_view_type, cal_is_global ) VALUES ( ?, ?, ?, ?, ? )',
          $sql_params ) )
        $error = db_error();
    } else
      $error = db_error();
  }
  # update user list
  if ( $error == '' ) {
    dbi_execute ( 'DELETE FROM webcal_view_user WHERE cal_view_id = ?',
      [$id] );
    // If selected "All", then just put "__all__" in for username.
    if ( getPostValue ( 'viewuserall' ) == 'Y' )
      $users = ['__all__'];

    for ( $i = 0, $cnt = count ( $users );
      ! empty ( $users ) && $i < $cnt; $i++ ) {
      dbi_execute ( 'INSERT INTO webcal_view_user ( cal_view_id, cal_login )
        VALUES ( ?, ? )', [$id, $users[$i]] );
    }
  }
}

echo error_check ( 'views.php', false );

?>
