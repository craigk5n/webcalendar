<?php // $Id$
include_once 'includes/init.php';

if ( empty ( $login ) || $login == '__public__' ) {
  // Do not allow public access.
  do_redirect ( empty ( $STARTVIEW ) ? 'month.php' : "$STARTVIEW" );
  exit;
}

if ( $user != $login )
  $user = ( ( $is_admin || $is_nonuser_admin ) && $user ) ? $user : $login;

ob_start();
print_header();

echo '
    <form action="assistant_edit_handler.php" method="post" '
 . 'name="assistanteditform">' . ( $user ? '
      <input type="hidden" name="user" value="' . $user . '">' : '' ) . '
      <h2>';

$assistStr = translate ( 'Assistants' );
if ( $is_nonuser_admin ) {
  nonuser_load_variables ( $user, 'nonuser' );
  echo $nonuserfullname . ' ' . $assistStr . '<br>
      -- ' . translate ( 'Admin mode' ) . ' --';
} else
  echo translate ( 'Your assistants' );

echo '</h2>
      ' . display_admin_link() . '
      <table summary="">
        <tr>
          <td class="aligntop"><label for="users">'
 . $assistStr . ':</label></td>
          <td>
            <select name="users[]" id="users" size="10" multiple>';

// Get list of all users.
$users = get_my_users();
// Get list of users for this view.
$res = dbi_execute ( 'SELECT cal_boss, cal_assistant FROM webcal_asst
   WHERE cal_boss = ?', array ( $user ) );

if ( $res ) {
  while ( $row = dbi_fetch_row ( $res ) ) {
    $assistantuser[$row[1]] = 1;
  }
  dbi_free_result ( $res );
}

for ( $i = 0, $cnt = count ( $users ); $i < $cnt; $i++ ) {
  $u = $users[$i]['cal_login'];
  if ( $u == $login || $u == '__public__' )
    continue;
  echo '
              <option value="' . $u
   . ( ! empty ( $assistantuser[$u] ) ? '" selected>' : '">' )
   . $users[$i]['cal_fullname'] . '</option>';
}

echo '
            </select>' . ( $GROUPS_ENABLED == 'Y' ? '
            <input type="button" onclick="selectUsers()" value="'
   . $selectStr . '...">' : '' ) . '
          </td>
        </tr>
        <tr>
          <td colspan="2" class="aligncenter"><br><input type="submit" '
 . 'name="action" value="' . $saveStr . '">
          </td>
        </tr>
      </table>
    </form>' . print_trailer();
ob_end_flush();

?>
