<?php
require_once 'includes/init.php';

// Public and nonuser (resource-calendar) sessions have no assistants to
// manage; allowing them let an anonymous __public__ visitor grant moderation
// authority over the public calendar.
if ( empty ( $login ) || $login == '__public__' || ! empty ( $is_nonuser ) ) {
  echo print_not_auth();
  exit;
}

$user = getPostValue ( 'user' );
$users = getPostValue ( 'users' );

$error = '';
if ( $user != $login )
  $user = ( ( $is_admin || $is_nonuser_admin ) && $user ) ? $user : $login;
# update user list
dbi_execute ( 'DELETE FROM webcal_asst
  WHERE cal_boss = ?', [$user] );
if ( ! empty ( $users ) ) {
  for ( $i = 0, $cnt = count ( $users ); $i < $cnt; $i++ ) {
    // Skip the boss itself, pseudo-users, and logins that do not exist.
    if ( $users[$i] === $user || $users[$i] === '__public__'
      || ! user_load_variables ( $users[$i], 'asstchk_' ) )
      continue;
    dbi_execute ( 'INSERT INTO webcal_asst ( cal_boss, cal_assistant )
      VALUES
( ?, ? )', [$user, $users[$i]] );
  }
}

echo error_check ( 'assistant_edit.php'
   . ( ( $is_admin || $is_nonuser_admin ) && $login != $user
    ? '?user=' . $user : '' ) );

?>
