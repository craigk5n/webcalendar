<?php
include_once 'includes/init.php';

if ( empty ( $login ) || $login == '__public__' ) {
  // Do not allow public access.
  do_redirect ( empty ( $STARTVIEW ) ? 'month.php' : "$STARTVIEW" );
  exit;
}

if ( $user != $login )
  $user = ( ( $is_admin || $is_nonuser_admin ) && $user ) ? $user : $login;

print_header( '', ! $GROUPS_ENABLED == 'Y' ? '' :
  '<script type="text/javascript" src="includes/js/assistant_edit.js"></script>' );
echo '
    <form action="assistant_edit_handler.php" method="post" '
 . 'name="assistanteditform">' . ( $user ? '
      <input type="hidden" name="user" value="' . $user . '" />' : '' ) . '
      <h2>';

$assistStr = translate ( 'Assistants' );
if ( $is_nonuser_admin ) {
  nonuser_load_variables ( $user, 'nonuser' );
  echo $nonuserfullname . ' ' . $assistStr . '<br /><span class="dblHyphens">
      ' . translate ( 'Admin mode' ) . '</span>';
} else
  echo translate ( 'Your assistants' );

echo '</h2>
      ' . display_admin_link() . '
      <table>
        <tr>
          <td class="aligntop colon"><label for="users">' . $assistStr . '</label></td>
          <td>
            <select name="users[]" id="users" size="10" multiple="multiple">';

// Get list of all users.
$users = get_my_users();
// Get list of users for this view.
$res = dbi_execute ( 'SELECT cal_boss, cal_assistant FROM webcal_asst
  WHERE cal_boss = ?', [$user] );

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
              <option value="' . $u . '"'
   . ( ! empty ( $assistantuser[$u] ) ? ' selected="selected"' : '' ) . '>'
   . $users[$i]['cal_fullname'] . '</option>';
}

echo '
            </select>' . ( $GROUPS_ENABLED == 'Y' ? '
            <input type="button" onclick="selectUsers()" value="'
   . translate ( 'Select' ) . '..." />' : '' ) . '
          </td>
        </tr>
        <tr>
          <td colspan="2" class="aligncenter"><br /><input type="submit" '
 . 'name="action" value="' . translate ( 'Save' ) . '" />
          </td>
        </tr>
      </table>
    </form>
    ' . print_trailer ();

?>
