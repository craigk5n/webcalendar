<?php
/* $Id: assistant_edit_handler.php,v 1.19.2.2 2012/02/28 02:07:45 cknudsen Exp $ */
include_once 'includes/init.php';
require_valide_referring_url ();

$user = getPostValue ( 'user' );
$users = getPostValue ( 'users' );

$error = '';
if ( $user != $login )
  $user = ( ( $is_admin || $is_nonuser_admin ) && $user ) ? $user : $login;
# update user list
dbi_execute ( 'DELETE FROM webcal_asst WHERE cal_boss = ?', array ( $user ) );
if ( ! empty ( $users ) ) {
  for ( $i = 0, $cnt = count ( $users ); $i < $cnt; $i++ ) {
    dbi_execute ( 'INSERT INTO webcal_asst ( cal_boss, cal_assistant )
      VALUES ( ?, ? )', array ( $user, $users[$i] ) );
  }
}

echo error_check ( 'assistant_edit.php'
   . ( ( $is_admin || $is_nonuser_admin ) && $login != $user
    ? '?user=' . $user : '' ) );

?>
