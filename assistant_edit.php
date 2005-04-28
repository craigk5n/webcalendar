<?php
include_once 'includes/init.php';

if ( empty ( $login) || $login == "__public__" ) {
  // do not allow public access
  do_redirect ( empty ( $STARTVIEW ) ? "month.php" : "$STARTVIEW" );
  exit;
}

if ($user != $login)
  $user = (($is_admin || $is_nonuser_admin) && $user) ? $user : $login;

if ( $groups_enabled == "Y" ) {
  $INC = array('js/assistant_edit.php');
} else {
  $INC = '';
}
print_header($INC);
?>

<form action="assistant_edit_handler.php" method="post" name="assistanteditform">
<?php
  if ($user) echo "<input type=\"hidden\" name=\"user\" value=\"$user\" />\n";
  if ( $is_nonuser_admin ) {
    nonuser_load_variables ( $user, "nonuser" );
    echo "<h2>" . $nonuserfullname . " " . translate("Assistants")
        ."<br />\n-- " . translate("Admin mode") . " --</h2>\n";
  } else {
    echo "<h2>" . translate("Your assistants") . "</h2>\n";
  }
?>
<a title="<?php etranslate("Admin") ?>" class="nav" href="adminhome.php">&laquo;&nbsp;<?php etranslate("Admin") ?></a><br /><br />

<table style="border-width:0px;">
<tr><td style="vertical-align:top;">
	<label for="users"><?php etranslate("Assistants"); ?>:</label></td><td>
	<select name="users[]" id="users" size="10" multiple="multiple">
<?php
  // get list of all users
  $users = get_my_users ();
  // get list of users for this view
  $sql = "SELECT cal_boss, cal_assistant FROM webcal_asst WHERE cal_boss = '$user'";
  $res = dbi_query ( $sql );
  if ( $res ) {
    while ( $row = dbi_fetch_row ( $res ) ) {
      $assistantuser[$row[1]] = 1;
    }
    dbi_free_result ( $res );
  }
  for ( $i = 0; $i < count ( $users ); $i++ ) {
    $u = $users[$i]['cal_login'];
    if ($u == $login ) continue;
    if ($u == '__public__' ) continue;
    echo "<option value=\"$u\"";
    if ( ! empty ( $assistantuser[$u] ) ) {
      echo " selected=\"selected\"";
    }
    echo ">" . $users[$i]['cal_fullname'] . "</option>\n";
  }
?>
</select>
<?php
if ( $groups_enabled == "Y" ) {
  echo "<input type=\"button\" onclick=\"selectUsers()\" value=\"" .
    translate("Select") . "...\" />\n";
}
?>
</td></tr>
<tr><td colspan="2" style="text-align:center;">
<br />
<input type="submit" name="action" value="<?php etranslate("Save"); ?>" />
</td></tr>
</table>
</form>

<?php print_trailer(); ?>
</body>
</html>
