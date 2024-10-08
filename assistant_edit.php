<?php
require_once 'includes/init.php';

if ( empty ( $login ) || $login == '__public__' ) {
  // Do not allow public access.
  do_redirect ( empty ( $STARTVIEW ) ? 'month.php' : "$STARTVIEW" );
  exit;
}

if ( $user != $login )
  $user = ( ( $is_admin || $is_nonuser_admin ) && $user ) ? $user : $login;

print_header ( $GROUPS_ENABLED !== 'Y' ? [] : ['js/assistant_edit.js'] );
echo '
    <form action="assistant_edit_handler.php" method="post" '
 . 'name="assistanteditform">' . csrf_form_key() . ( $user ? '
      <input type="hidden" name="user" value="' . $user . '">' : '' ) . '
      <h2>';

$assistStr = translate ( 'Assistants' );
if ( $is_nonuser_admin ) {
  nonuser_load_variables ( $user, 'nonuser' );
  echo $nonuserfullname . ' ' . $assistStr . '<br><span class="dblHyphens">
      ' . translate ( 'Admin mode' ) . '</span>';
} else
  echo translate ( 'Your assistants' );

echo '</h2>
      ' . display_admin_link() . '</h2>';

echo '<div class="form-inline col-4"><select class="custom-select" name="users[]" id="users" size="10" multiple>';

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
   . ( ! empty ( $assistantuser[$u] ) ? ' selected' : '' ) . '>'
   . $users[$i]['cal_fullname'] . '</option>';
}

echo "</select>\n";

if ( $GROUPS_ENABLED == 'Y' ) {
  echo '<button class="btn btn-primary" type="button" onclick="selectUsers()">'
    . translate ( 'Select' ) . '...</button>';
}
 echo "</div>\n";

?>
<div class="form-inline col-4 pt-2">
  <button class="btn btn-primary" name="action" type="submit">'<?php
etranslate ( 'Save' );?></button>
</div>

</form>

<?php echo print_trailer (); ?>
