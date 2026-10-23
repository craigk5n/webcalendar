<?php
require_once 'includes/init.php';

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
    // Only the view's owner may change its user list. The UPDATE above is
    // owner-scoped, but this rewrite used $id directly, so an attacker could
    // repoint another user's view. Verify ownership first.
    $ownRes = dbi_execute ( 'SELECT cal_view_id FROM webcal_view
      WHERE cal_view_id = ? AND cal_owner = ?', [$id, $login] );
    $owns = ( $ownRes && dbi_fetch_row ( $ownRes ) );
    if ( $ownRes )
      dbi_free_result ( $ownRes );
    if ( ! $owns ) {
      $error = print_not_auth ();
    } else {
      dbi_execute ( 'DELETE FROM webcal_view_user WHERE cal_view_id = ?',
        [$id] );
      // If selected "All", then just put "__all__" in for username.
      if ( getPostValue ( 'viewuserall' ) == 'Y' )
        $users = ['__all__'];

      $cnt = empty($users) ? 0 : count($users);
      for ($i = 0; $i < $cnt; $i++ ) {
        dbi_execute ( 'INSERT INTO webcal_view_user ( cal_view_id, cal_login )
          VALUES ( ?, ? )', [$id, $users[$i]] );
      }
    }
  }
}

echo error_check ( 'views.php', false );

?>
